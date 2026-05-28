<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();
            return redirect()->intended(Auth::user()->is_admin ? '/admin' : route('account.dashboard'));
        }

        return back()->withErrors(['email' => 'Invalid credentials.'])->onlyInput('email');
    }

    public function showAdminLogin()
    {
        return view('auth.admin-login');
    }

    public function adminLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            if (! Auth::user()->is_admin) {
                Auth::logout();
                return redirect()->route('admin.login')
                    ->withErrors(['email' => 'This area is restricted to administrators.'])
                    ->onlyInput('email');
            }
            $request->session()->regenerate();
            return redirect()->intended('/admin');
        }

        return redirect()->route('admin.login')
            ->withErrors(['email' => 'Invalid credentials.'])
            ->onlyInput('email');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:160|unique:users',
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['role'] = 'customer';

        $user = User::create($data);
        Auth::login($user);

        return redirect()->route('account.dashboard')->with('status', 'Welcome aboard, ' . $user->name . '!');
    }

    public function logout(Request $request)
    {
        $wasAdmin = (bool) $request->user()?->is_admin;
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route($wasAdmin ? 'admin.login' : 'home');
    }
}
