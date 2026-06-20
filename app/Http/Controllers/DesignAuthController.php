<?php

namespace App\Http\Controllers;

use App\Mail\OtpMail;
use App\Models\User;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Session-based customer auth for the BOLETO design (CSRF-exempt design-api/*).
 * Mirrors the mobile API: phone required at signup, one OTP to email + SMS,
 * forgot password by email OR phone. Bookings made while logged in are
 * attributed to the real customer.
 */
class DesignAuthController extends Controller
{
    public function __construct(private SmsService $sms) {}

    private function payload(User $u): array
    {
        return ['name' => $u->name, 'email' => $u->email, 'verified' => (bool) $u->email_verified_at];
    }

    /** POST /design-api/login */
    public function login(Request $request)
    {
        $cred = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($cred, $request->boolean('remember'))) {
            if (Auth::user()->is_admin) {
                Auth::logout();
                return response()->json(['message' => 'Admins sign in via the admin panel.'], 422);
            }
            $request->session()->regenerate();
            return response()->json(['user' => $this->payload(Auth::user())]);
        }

        return response()->json(['message' => 'Invalid email or password.'], 422);
    }

    /** POST /design-api/register — phone required; sends one OTP to email + SMS. */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160|unique:users',
            'phone' => 'required|string|max:20|unique:users',
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'password' => Hash::make($data['password']),
            'role' => 'customer',
        ]);
        Auth::login($user);
        $request->session()->regenerate();
        $this->issueOtp($user, 'acct_verify:' . $user->id, 'Verify your account');

        return response()->json(['user' => $this->payload($user), 'verification_required' => true], 201);
    }

    /** POST /design-api/verify/send — (re)send the verification code to email + phone. */
    public function sendOtp(Request $request)
    {
        $u = Auth::user();
        abort_unless($u && ! $u->is_admin, 401, 'Please sign in.');
        if ($u->email_verified_at) {
            return response()->json(['message' => 'Account already verified.']);
        }
        $this->issueOtp($u, 'acct_verify:' . $u->id, 'Verify your account');
        return response()->json(['message' => 'Verification code sent to your email and phone.']);
    }

    /** POST /design-api/verify  { code } */
    public function verify(Request $request)
    {
        $u = Auth::user();
        abort_unless($u && ! $u->is_admin, 401, 'Please sign in.');
        $request->validate(['code' => 'required|string']);

        $cached = cache()->get('acct_verify:' . $u->id);
        if (! $cached || ! hash_equals((string) $cached, (string) $request->code)) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }
        $u->forceFill(['email_verified_at' => now()])->save();
        cache()->forget('acct_verify:' . $u->id);
        return response()->json(['user' => $this->payload($u), 'verified' => true]);
    }

    /** POST /design-api/password/forgot  { identifier (email|phone) } */
    public function forgotPassword(Request $request)
    {
        $user = $this->findByIdentifier($request->input('identifier') ?: $request->input('email'));
        if ($user) {
            $this->issueOtp($user, 'pwreset:' . $user->id, 'Reset your password');
        }
        return response()->json(['message' => 'If that account exists, a reset code has been sent to its email and phone.']);
    }

    /** POST /design-api/password/reset  { identifier, code, password, password_confirmation } */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        $user = $this->findByIdentifier($request->input('identifier') ?: $request->input('email'));
        $cached = $user ? cache()->get('pwreset:' . $user->id) : null;

        if (! $user || ! $cached || ! hash_equals((string) $cached, (string) $request->code)) {
            return response()->json(['message' => 'Invalid or expired code.'], 422);
        }
        $user->forceFill(['password' => Hash::make($request->password)])->save();
        cache()->forget('pwreset:' . $user->id);
        return response()->json(['message' => 'Password has been reset. Please sign in.']);
    }

    /** POST /design-api/logout */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json(['ok' => true]);
    }

    /** GET /design-api/profile — current customer profile. */
    public function profile()
    {
        $u = Auth::user();
        if (! $u || $u->is_admin) return response()->json(['user' => null]);
        return response()->json(['user' => ['name' => $u->name, 'email' => $u->email, 'phone' => $u->phone]]);
    }

    /** POST /design-api/profile — update name / phone. */
    public function updateProfile(Request $request)
    {
        $u = Auth::user();
        abort_unless($u && ! $u->is_admin, 401, 'Please sign in.');
        $data = $request->validate([
            'name'  => 'required|string|max:120',
            'phone' => 'nullable|string|max:30',
        ]);
        $u->update($data);
        return response()->json(['user' => ['name' => $u->name, 'email' => $u->email, 'phone' => $u->phone]]);
    }

    /** POST /design-api/profile/password — change password. */
    public function updatePassword(Request $request)
    {
        $u = Auth::user();
        abort_unless($u && ! $u->is_admin, 401, 'Please sign in.');
        $data = $request->validate([
            'current'  => 'required|string',
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        if (! Hash::check($data['current'], $u->password)) {
            return response()->json(['message' => 'Your current password is incorrect.'], 422);
        }
        $u->update(['password' => Hash::make($data['password'])]);
        return response()->json(['ok' => true]);
    }

    // ---- helpers (mirror the mobile API) ----

    /** One 6-digit code, cached 30 min, sent to email AND phone. */
    private function issueOtp(User $user, string $cacheKey, string $subject): void
    {
        $code = (string) random_int(100000, 999999);
        cache()->put($cacheKey, $code, now()->addMinutes(30));

        $this->safeMail($user->email, new OtpMail($code, $subject, 'Use this code on the site. It expires in 30 minutes:'));
        if ($user->phone) {
            $this->sms->send($user->phone, "Your Boleto code is {$code}. It expires in 30 minutes.");
        }
    }

    private function findByIdentifier(?string $id): ?User
    {
        $id = trim((string) $id);
        if ($id === '') return null;
        return User::where('email', $id)->orWhere('phone', $id)->first();
    }

    private function safeMail(?string $to, $mailable): void
    {
        if (! $to) return;
        try {
            Mail::to($to)->send($mailable);
        } catch (\Throwable $e) {
            Log::warning('Design mail failed', ['error' => $e->getMessage()]);
        }
    }
}
