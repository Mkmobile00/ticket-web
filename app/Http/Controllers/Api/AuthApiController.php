<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\LoginAlertMail;
use App\Mail\WelcomeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
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
