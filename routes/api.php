<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\Api\CatalogApiController;
use App\Http\Controllers\Api\ContentApiController;
use App\Http\Controllers\Api\ProfileApiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Mobile / Flutter REST API  (prefix: /api/v1)
|--------------------------------------------------------------------------
| Token auth via Laravel Sanctum. After register/login, send the token as:
|     Authorization: Bearer {token}
*/

Route::prefix('v1')->group(function () {

    // ---- Auth ----
    Route::post('/register', [AuthApiController::class, 'register'])->middleware('throttle:register');
    Route::post('/login', [AuthApiController::class, 'login'])->middleware('throttle:login');
    Route::post('/auth/google', [AuthApiController::class, 'google'])->middleware('throttle:login');
    Route::post('/password/forgot', [AuthApiController::class, 'forgotPassword'])->middleware('throttle:login');
    Route::post('/password/reset', [AuthApiController::class, 'resetPassword'])->middleware('throttle:login');

    // ---- Public catalog ----
    Route::get('/cities', [CatalogApiController::class, 'cities']);
    Route::get('/genres', [CatalogApiController::class, 'genres']);
    Route::get('/languages', [CatalogApiController::class, 'languages']);
    Route::get('/formats', [CatalogApiController::class, 'formats']);
    Route::get('/movies', [CatalogApiController::class, 'movies']);
    Route::get('/movies/{movie:slug}', [CatalogApiController::class, 'movie']);
    Route::get('/movies/{movie:slug}/showtimes', [CatalogApiController::class, 'movieShowtimes']);
    Route::get('/events', [CatalogApiController::class, 'events']);
    Route::get('/events/{event:slug}', [CatalogApiController::class, 'event']);
    Route::get('/sports', [CatalogApiController::class, 'sports']);
    Route::get('/sports/{sport:slug}', [CatalogApiController::class, 'sport']);

    // Seat map (status) — works logged-out (shows available/booked/locked);
    // "mine" status only when authenticated.
    Route::get('/seats/{type}/{id}', [CatalogApiController::class, 'seats']);

    // ---- Public content ----
    Route::get('/home', [ContentApiController::class, 'home']);
    Route::get('/banners', [ContentApiController::class, 'banners']);
    Route::get('/search', [ContentApiController::class, 'search']);
    Route::get('/popcorn', [ContentApiController::class, 'popcorn']);
    Route::get('/faqs', [ContentApiController::class, 'faqs']);
    Route::get('/partners', [ContentApiController::class, 'partners']);
    Route::get('/blog', [ContentApiController::class, 'blog']);
    Route::get('/blog/{post:slug}', [ContentApiController::class, 'blogShow']);
    Route::post('/promo/validate', [ContentApiController::class, 'validatePromo'])->middleware('throttle:public-form');
    Route::post('/contact', [ContentApiController::class, 'contact'])->middleware('throttle:public-form');
    Route::post('/newsletter', [ContentApiController::class, 'newsletter'])->middleware('throttle:public-form');

    // ---- Authenticated (Bearer token) ----
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthApiController::class, 'me']);
        Route::post('/logout', [AuthApiController::class, 'logout']);

        // Profile
        Route::get('/profile', [ProfileApiController::class, 'show']);
        Route::put('/profile', [ProfileApiController::class, 'update']);
        Route::put('/profile/password', [ProfileApiController::class, 'password']);

        // Email verification (OTP) — throttle the send + the 6-digit code submission.
        Route::post('/email/verify/send', [AuthApiController::class, 'sendEmailVerification'])->middleware('throttle:otp');
        Route::post('/email/verify', [AuthApiController::class, 'verifyEmail'])->middleware('throttle:otp');

        // Push notification device tokens
        Route::post('/device-token', [ProfileApiController::class, 'registerDevice']);
        Route::delete('/device-token', [ProfileApiController::class, 'removeDevice']);

        // Bookings
        Route::get('/bookings', [BookingApiController::class, 'index']);
        Route::post('/bookings', [BookingApiController::class, 'store'])->middleware('throttle:seat-lock');
        Route::get('/bookings/{booking}', [BookingApiController::class, 'show']);
        Route::post('/bookings/{booking}/addons', [BookingApiController::class, 'addons']);
        Route::post('/bookings/{booking}/apply-promo', [BookingApiController::class, 'applyPromo']);
        Route::post('/bookings/{booking}/pay', [BookingApiController::class, 'pay'])->middleware('throttle:payments');
        Route::post('/bookings/{booking}/verify-payment', [BookingApiController::class, 'verifyPayment'])->middleware('throttle:payments');
        Route::post('/bookings/{booking}/cancel', [BookingApiController::class, 'cancel']);
        Route::delete('/bookings/{booking}/release', [BookingApiController::class, 'release']);
    });
});
