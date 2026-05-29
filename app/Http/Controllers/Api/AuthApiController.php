<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\LoginAlertMail;
use App\Mail\OtpMail;
use App\Mail\WelcomeMail;
use App\Models\User;
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
    /** POST /api/v1/register  { name, email, password, password_confirmation, phone? } */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160|unique:users',
            'phone' => 'nullable|string|max:20',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'customer';

        $user = User::create($data);
        $this->safeMail($user->email, new WelcomeMail($user));

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('flutter')->plainTextToken,
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
            'token' => $user->createToken('flutter')->plainTextToken,
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
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if ($user) {
            $code = (string) random_int(100000, 999999);
            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($code), 'created_at' => now()]
            );
            $this->safeMail($user->email, new OtpMail($code, 'Reset your password', 'Enter this code in the app to reset your password:'));
        }

        return response()->json(['message' => 'If that email exists, a reset code has been sent.']);
    }

    /** POST /api/v1/password/reset  { email, code, password, password_confirmation } */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $row = DB::table('password_reset_tokens')->where('email', $request->email)->first();
        $expired = ! $row || \Illuminate\Support\Carbon::parse($row->created_at)->addMinutes(30)->isPast();

        if ($expired || ! Hash::check($request->code, $row->token)) {
            return response()->json(['message' => 'Invalid or expired code.', 'errors' => ['code' => ['Invalid or expired code.']]], 422);
        }

        User::where('email', $request->email)->update(['password' => Hash::make($request->password)]);
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

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

        $user = User::firstOrCreate(
            ['email' => $g['email']],
            ['name' => $g['name'] ?? $g['email'], 'password' => Hash::make(Str::random(32)), 'role' => 'customer', 'email_verified_at' => now()]
        );

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $user->createToken('flutter-google')->plainTextToken,
        ]);
    }

    /** POST /api/v1/email/verify/send  (auth) — emails a code (cached 30 min). */
    public function sendEmailVerification(Request $request)
    {
        $user = $request->user();
        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email already verified.']);
        }
        $code = (string) random_int(100000, 999999);
        cache()->put('email_verify:' . $user->id, $code, now()->addMinutes(30));
        $this->safeMail($user->email, new OtpMail($code, 'Verify your email', 'Enter this code in the app to verify your email:'));
        return response()->json(['message' => 'Verification code sent.']);
    }

    /** POST /api/v1/email/verify  (auth)  { code } */
    public function verifyEmail(Request $request)
    {
        $request->validate(['code' => 'required|string']);
        $cached = cache()->get('email_verify:' . $request->user()->id);
        if (! $cached || $cached !== $request->code) {
            return response()->json(['message' => 'Invalid or expired code.', 'errors' => ['code' => ['Invalid code.']]], 422);
        }
        $request->user()->update(['email_verified_at' => now()]);
        cache()->forget('email_verify:' . $request->user()->id);
        return response()->json(['message' => 'Email verified.']);
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
            Mail::to($to)->send($mailable);
        } catch (\Throwable $e) {
            Log::warning('API mail failed', ['to' => $to, 'error' => $e->getMessage()]);
        }
    }
}
