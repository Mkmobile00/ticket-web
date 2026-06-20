<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\LoginAlertMail;
use App\Mail\OtpMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

/**
 * Mobile auth (Sanctum personal access tokens).
 * Clients send the returned token as:  Authorization: Bearer {token}
 */
class AuthApiController extends Controller
{
    /** Personal access token lifetime (days) — stolen tokens expire instead of living forever. */
    private const TOKEN_TTL_DAYS = 30;

    public function __construct(private SmsService $sms) {}

    /**
     * Generate a 6-digit code, cache it (30 min) and send it to the user's
     * email AND phone (one code, both channels).
     */
    private function issueOtp(User $user, string $cacheKey, string $subject): string
    {
        $code = (string) random_int(100000, 999999);
        cache()->put($cacheKey, $code, now()->addMinutes(30));

        $this->safeMail($user->email, new OtpMail($code, $subject, 'Use this code in the app. It expires in 30 minutes:'));
        if ($user->phone) {
            $this->sms->send($user->phone, "Your Boleto code is {$code}. It expires in 30 minutes.");
        }
        return $code;
    }

    /** Find a user by email OR phone (for forgot/reset). */
    private function findByIdentifier(?string $id): ?User
    {
        $id = trim((string) $id);
        if ($id === '') return null;
        return User::where('email', $id)->orWhere('phone', $id)->first();
    }

    /** POST /api/v1/register  { name, email, password, password_confirmation, phone? } */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'customer';

        $user = User::create($data);
        $this->safeMail($user->email, new WelcomeMail($user));
        // One verification code to BOTH email and phone.
        $this->issueOtp($user, 'acct_verify:' . $user->id, 'Verify your account');

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('flutter', ['*'], now()->addDays(self::TOKEN_TTL_DAYS))->plainTextToken,
            'verification_required' => true,
        ], 201);
    }

    /** POST /api/v1/login  { email, password } */
    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);

        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $this->safeMail($user->email, new LoginAlertMail($user, now()->format('D, M d Y H:i'), $request->ip()));

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('flutter', ['*'], now()->addDays(self::TOKEN_TTL_DAYS))->plainTextToken,
        ]);
    }

    /** GET /api/v1/me  (auth) */
    public function me(Request $request)
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    /**
     * POST /api/v1/password/forgot  { email }
     * Emails a 6-digit reset code. Always 200 (don't reveal whether the email exists).
     */
    public function forgotPassword(Request $request)
    {
        // Accept `identifier` (email OR phone); keep `email` for backward-compat.
        $id = $request->input('identifier') ?: $request->input('email');
        $user = $this->findByIdentifier($id);

        if ($user) {
            $this->issueOtp($user, 'pwreset:' . $user->id, 'Reset your password');
        }

        return response()->json(['message' => 'If that account exists, a reset code has been sent to its email and phone.']);
    }

    /** POST /api/v1/password/reset  { identifier (email|phone), code, password, password_confirmation } */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $id = $request->input('identifier') ?: $request->input('email');
        $user = $this->findByIdentifier($id);
        $cached = $user ? cache()->get('pwreset:' . $user->id) : null;

        if (! $user || ! $cached || ! hash_equals((string) $cached, (string) $request->code)) {
            return response()->json(['message' => 'Invalid or expired code.', 'errors' => ['code' => ['Invalid or expired code.']]], 422);
        }

        $user->forceFill(['password' => Hash::make($request->password)])->save();
        // Revoke every existing token so a leaked session can't outlive the reset.
        $user->tokens()->delete();
        cache()->forget('pwreset:' . $user->id);

        return response()->json(['message' => 'Password has been reset. Please log in.']);
    }

    /**
     * POST /api/v1/auth/google  { id_token }
     * Verifies a Google ID token (from the google_sign_in Flutter plugin),
     * finds/creates the user, returns a Sanctum token.
     */
    public function google(Request $request)
    {
        $request->validate(['id_token' => 'required|string']);

        try {
            $resp = Http::acceptJson()->get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $request->id_token]);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Could not verify Google token.'], 502);
        }

        $g = $resp->json();
        if (! $resp->successful() || empty($g['email']) || ($g['email_verified'] ?? 'false') === 'false') {
            return response()->json(['message' => 'Invalid Google token.'], 401);
        }

        // Verify the token was minted for THIS app (audience). Without this, a token
        // issued to any other Google OAuth client would be accepted -> account takeover.
        // Client ID comes from admin Settings (fallback to env); enforced when set.
        $expectedAud = \App\Models\Setting::where('key', 'google_client_id')->value('value')
            ?: config('services.google.client_id');
        if ($expectedAud && ($g['aud'] ?? null) !== $expectedAud) {
            return response()->json(['message' => 'Invalid Google token.'], 401);
        }

        $user = User::firstOrCreate(
            ['email' => $g['email']],
            ['name' => $g['name'] ?? $g['email'], 'password' => Hash::make(Str::random(32)), 'role' => 'customer']
        );
        // Google accounts are pre-verified; persist it (not in $fillable).
        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('flutter-google', ['*'], now()->addDays(self::TOKEN_TTL_DAYS))->plainTextToken,
        ]);
    }

    /** POST /api/v1/email/verify/send  (auth) — (re)send the code to email + phone. */
    public function sendEmailVerification(Request $request)
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Account already verified.']);
        }
        $this->issueOtp($user, 'acct_verify:' . $user->id, 'Verify your account');
        return response()->json(['message' => 'Verification code sent to your email and phone.']);
    }

    /** POST /api/v1/email/verify  (auth)  { code } — verify the signup code. */
    public function verifyEmail(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $user = $request->user();
        $cached = cache()->get('acct_verify:' . $user->id);
        if (! $cached || ! hash_equals((string) $cached, (string) $request->code)) {
            return response()->json(['message' => 'Invalid or expired code.', 'errors' => ['code' => ['Invalid code.']]], 422);
        }
        // forceFill: email_verified_at is not in the model's $fillable.
        $user->forceFill(['email_verified_at' => now()])->save();
        cache()->forget('acct_verify:' . $user->id);
        return response()->json(['message' => 'Account verified.', 'user' => $this->userPayload($user)]);
    }

    /** POST /api/v1/logout  (auth) — revokes the current token */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out.']);
    }

    private function userPayload(User $u): array
    {
        return [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'role' => $u->role,
            'is_admin' => (bool) $u->is_admin,
            'email_verified' => (bool) $u->email_verified_at,
        ];
    }

    private function safeMail(?string $to, $mailable): void
    {
        if (! $to) return;
        try {
            // Send synchronously (not queued) so time-sensitive OTP / verification
            // emails go out immediately and don't depend on a running queue worker.
            Mail::to($to)->send($mailable);
        } catch (\Throwable $e) {
            // Redact the recipient address — don't write PII to logs.
            Log::warning('API mail failed', ['to_hash' => hash('sha256', $to), 'error' => $e->getMessage()]);
        }
    }
}
