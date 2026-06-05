<?php

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withSchedule(function (Schedule $schedule) {
        // Remind users ~1 hour before their booked showtime (runs only if a
        // scheduler/cron drives `php artisan schedule:run`).
        $schedule->command('bookings:remind --within=60')->everyFifteenMinutes();
    })
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
        ]);

        // Trust the load balancer / reverse proxy so HTTPS, host and client IP are
        // detected correctly behind TLS termination (needed for secure cookies + HSTS).
        $middleware->trustProxies(at: '*');

        // Baseline security headers on every response.
        $middleware->append(\App\Http\Middleware\SecurityHeaders::class);

        // The BOLETO design talks to /design-api/* with plain fetch (no CSRF token),
        // so those routes are token-CSRF-exempt — but an Origin/Referer same-origin
        // check is enforced on them instead (see VerifyDesignApiOrigin).
        $middleware->validateCsrfTokens(except: ['design-api/*']);
        $middleware->appendToGroup('web', \App\Http\Middleware\VerifyDesignApiOrigin::class);

        // Send unauthenticated admin-area visitors (dashboard + file manager) to the
        // admin login page, everyone else to the customer login.
        $middleware->redirectGuestsTo(fn (Request $request) => $request->is('admin', 'admin/*', 'filemanager', 'filemanager/*')
            ? route('admin.login')
            : route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
