<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('admin.login'));
        }

        // Signed in, but not as an admin (e.g. a customer session): send them to the
        // admin login so they can switch accounts, instead of a dead-end 403.
        if ($user->role !== 'admin') {
            return redirect()->route('admin.login')
                ->withErrors(['email' => 'You are signed in as a customer. Sign in with an administrator account to continue.']);
        }

        return $next($request);
    }
}
