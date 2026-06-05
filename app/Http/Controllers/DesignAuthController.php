<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Session-based customer auth for the BOLETO design (CSRF-exempt design-api/*).
 * Bookings made while logged in are attributed to the real customer.
 */
class DesignAuthController extends Controller
{
    private function payload(User $u): array
    {
        return ['name' => $u->name, 'email' => $u->email];
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

    /** POST /design-api/register */
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160|unique:users',
            'password' => ['required', 'confirmed', 'min:8'],
        ]);
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'role' => 'customer',
        ]);
        Auth::login($user);
        $request->session()->regenerate();

        return response()->json(['user' => $this->payload($user)], 201);
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
}
