<?php

use App\Http\Controllers\Api\SeatController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BlogController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PopcornController;
use App\Http\Controllers\SpeakerController;
use App\Http\Controllers\SportController;
use Illuminate\Support\Facades\Route;

// Home + booking-flow now serve the custom BOLETO design (resources/design/*.html)
// with live catalog data injected into window.BOLETO_DATA from the database.
// Original dynamic home is still available via HomeController if needed.
Route::get('/', [\App\Http\Controllers\DesignController::class, 'page'])->name('home');

$designPages = 'index|movie|showtimes|seats|checkout|event|event-seats|event-checkout|sign-in|account|search';

// Clean URLs: /movie, /showtimes, /seats, /checkout, /event, ...
Route::get('/{page}', [\App\Http\Controllers\DesignController::class, 'page'])
    ->where('page', $designPages)
    ->name('design.page');

// Back-compat: old *.html links 301 to the clean URL.
Route::get('/{page}.html', fn (string $page) => redirect($page === 'index' ? '/' : '/' . $page, 301))
    ->where('page', $designPages);

// Design booking flow (CSRF-exempt) — real seat map + booking + payment, attributed to the logged-in customer.
Route::get('/design-api/showtimes/{movie:slug}', [\App\Http\Controllers\DesignBookingController::class, 'showtimes']);
Route::get('/design-api/seats/{type}/{id}', [\App\Http\Controllers\DesignBookingController::class, 'seats'])
    ->where('type', 'showtime|event|sport')->where('id', '[0-9]+');
Route::post('/design-api/checkout', [\App\Http\Controllers\DesignBookingController::class, 'checkout']);
Route::get('/design-api/my-bookings', [\App\Http\Controllers\DesignBookingController::class, 'myBookings']);
Route::get('/design-api/popcorn', [\App\Http\Controllers\DesignBookingController::class, 'popcorn']);
Route::post('/design-api/promo', [\App\Http\Controllers\DesignBookingController::class, 'promo']);
Route::get('/design-api/city', [\App\Http\Controllers\DesignController::class, 'setCity']);
Route::get('/design-api/search', [\App\Http\Controllers\DesignController::class, 'search']);

// Customer auth for the design (session-based, CSRF-exempt; protected by Origin check).
Route::post('/design-api/login', [\App\Http\Controllers\DesignAuthController::class, 'login'])->middleware('throttle:login');
Route::post('/design-api/google', [\App\Http\Controllers\DesignAuthController::class, 'google'])->middleware('throttle:login');
Route::post('/design-api/register', [\App\Http\Controllers\DesignAuthController::class, 'register'])->middleware('throttle:register');
Route::post('/design-api/verify', [\App\Http\Controllers\DesignAuthController::class, 'verify'])->middleware('throttle:otp');
Route::post('/design-api/verify/send', [\App\Http\Controllers\DesignAuthController::class, 'sendOtp'])->middleware('throttle:otp');
Route::post('/design-api/password/forgot', [\App\Http\Controllers\DesignAuthController::class, 'forgotPassword'])->middleware('throttle:login');
Route::post('/design-api/password/reset', [\App\Http\Controllers\DesignAuthController::class, 'resetPassword'])->middleware('throttle:login');
Route::post('/design-api/logout', [\App\Http\Controllers\DesignAuthController::class, 'logout']);
Route::get('/design-api/profile', [\App\Http\Controllers\DesignAuthController::class, 'profile']);
Route::post('/design-api/profile', [\App\Http\Controllers\DesignAuthController::class, 'updateProfile']);
Route::post('/design-api/profile/password', [\App\Http\Controllers\DesignAuthController::class, 'updatePassword']);
Route::post('/design-api/bookings/{booking}/cancel', [\App\Http\Controllers\DesignBookingController::class, 'cancel']);

// City selector (BookMyShow-style) — remembers the visitor's city in the session.
Route::get('/city/{city:slug}', function (\App\Models\City $city) {
    session(['selected_city_id' => $city->id, 'selected_city_name' => $city->name]);
    return back();
})->name('city.set');
Route::get('/city-clear/all', function () {
    session()->forget(['selected_city_id', 'selected_city_name']);
    return back();
})->name('city.clear');

// Movies — "view all" listing now uses the BOLETO design (list.html).
Route::get('/movies', fn (\Illuminate\Http\Request $r) => app(\App\Http\Controllers\DesignController::class)->page($r, 'list'))->name('movies.index');
Route::get('/movies/{movie:slug}', [MovieController::class, 'show'])->name('movies.show');
Route::get('/movies/{movie:slug}/showtimes', [MovieController::class, 'showtimes'])->name('movies.showtimes');

// Bookings (seat plan + checkout)
Route::get('/showtimes/{showtime}/seats', [BookingController::class, 'seats'])->name('showtimes.seats');
Route::post('/showtimes/{showtime}/seats', [BookingController::class, 'storeSeats'])->name('showtimes.seats.store')->middleware('auth');
Route::get('/checkout/movie/{booking}', [CheckoutController::class, 'movie'])->name('checkout.movie')->middleware('auth');
Route::get('/checkout/event/{booking}', [CheckoutController::class, 'event'])->name('checkout.event')->middleware('auth');
Route::get('/checkout/sport/{booking}', [CheckoutController::class, 'sport'])->name('checkout.sport')->middleware('auth');
Route::post('/checkout/{booking}/addons', [CheckoutController::class, 'addons'])->name('checkout.addons')->middleware('auth');
Route::post('/checkout/{booking}/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm')->middleware(['auth', 'throttle:payments']);
Route::get('/payment/callback/{booking}', [CheckoutController::class, 'paymentCallback'])->name('payment.callback')->middleware('auth');
Route::get('/bookings/{booking}/ticket', [CheckoutController::class, 'ticket'])->name('bookings.ticket')->middleware('auth');

// Polymorphic seat availability + atomic locking API (movies/events/sports)
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/seats/{type}/{id}', [SeatController::class, 'status'])->name('seats.status');
    Route::middleware('auth')->group(function () {
        Route::post('/seats/lock', [SeatController::class, 'lock'])->middleware('throttle:seat-lock')->name('seats.lock');
        Route::delete('/seats/lock', [SeatController::class, 'release'])->name('seats.release');
        Route::post('/seats/extend', [SeatController::class, 'extend'])->name('seats.extend');
    });
});

// Events — "view all" listing now uses the BOLETO design (list.html).
Route::get('/events', fn (\Illuminate\Http\Request $r) => app(\App\Http\Controllers\DesignController::class)->page($r, 'list'))->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::get('/events/{event:slug}/tickets', [EventController::class, 'tickets'])->name('events.tickets');
Route::post('/events/{event:slug}/tickets', [EventController::class, 'storeTickets'])->name('events.tickets.store')->middleware('auth');

// Speakers
Route::get('/speakers/{speaker}', [SpeakerController::class, 'show'])->name('speakers.show');

// Sports — "view all" listing now uses the BOLETO design (list.html).
Route::get('/sports', fn (\Illuminate\Http\Request $r) => app(\App\Http\Controllers\DesignController::class)->page($r, 'list'))->name('sports.index');
Route::get('/sports/{sport:slug}', [SportController::class, 'show'])->name('sports.show');
Route::get('/sports/{sport:slug}/tickets', [SportController::class, 'tickets'])->name('sports.tickets');
Route::post('/sports/{sport:slug}/tickets', [SportController::class, 'storeTickets'])->name('sports.tickets.store')->middleware('auth');

// Blog
// Blog — BOLETO design listing + detail (managed in admin → Blog Posts).
Route::get('/blog', [\App\Http\Controllers\DesignController::class, 'blogIndex'])->name('blog.index');
Route::get('/blog/{post:slug}', [\App\Http\Controllers\DesignController::class, 'blogShow'])->name('blog.show');
Route::post('/blog/{post:slug}/comment', [BlogController::class, 'storeComment'])->name('blog.comment')->middleware('throttle:public-form');

// Static pages
// About — BOLETO design, content from admin → Settings.
Route::get('/about', [\App\Http\Controllers\DesignController::class, 'about'])->name('about');
Route::get('/apps', [PageController::class, 'apps'])->name('apps');
// Contact — BOLETO design; the form posts to /design-api/contact (Origin-checked).
Route::get('/contact', [\App\Http\Controllers\DesignController::class, 'contact'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store')->middleware('throttle:public-form');
Route::post('/design-api/contact', [\App\Http\Controllers\DesignController::class, 'contactSubmit'])->middleware('throttle:public-form');
Route::get('/popcorn', [PopcornController::class, 'index'])->name('popcorn');

// Newsletter
Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe')->middleware('throttle:public-form');

// Search / availability API for live home dropdowns
Route::prefix('api')->name('api.')->group(function () {
    Route::get('/search/movies', [\App\Http\Controllers\SearchController::class, 'movies'])->name('search.movies');
    Route::get('/search/events', [\App\Http\Controllers\SearchController::class, 'events'])->name('search.events');
    Route::get('/search/sports', [\App\Http\Controllers\SearchController::class, 'sports'])->name('search.sports');
    Route::get('/availability/movie/{movie:slug}', [\App\Http\Controllers\SearchController::class, 'movieAvailability'])->name('availability.movie');
    Route::get('/availability/event/{event:slug}', [\App\Http\Controllers\SearchController::class, 'eventAvailability'])->name('availability.event');
    Route::get('/availability/sport/{sport:slug}', [\App\Http\Controllers\SearchController::class, 'sportAvailability'])->name('availability.sport');
});

// Customer account area
Route::middleware('auth')->prefix('account')->name('account.')->group(function () {
    Route::get('/', [\App\Http\Controllers\Account\AccountController::class, 'dashboard'])->name('dashboard');
    Route::get('/bookings', [\App\Http\Controllers\Account\BookingController::class, 'index'])->name('bookings.index');
    Route::get('/bookings/{booking}', [\App\Http\Controllers\Account\BookingController::class, 'show'])->name('bookings.show');
    Route::post('/bookings/{booking}/cancel', [\App\Http\Controllers\Account\BookingController::class, 'cancel'])->name('bookings.cancel');
    Route::get('/profile', [\App\Http\Controllers\Account\ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [\App\Http\Controllers\Account\ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [\App\Http\Controllers\Account\ProfileController::class, 'password'])->name('profile.password');
});

// Auth
Route::middleware('guest')->group(function () {
    // Customer
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
});

// Admin login — reachable even while signed in as a customer, so you can switch accounts.
Route::get('/admin/login', [AuthController::class, 'showAdminLogin'])->name('admin.login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:login');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Admin (loaded from separate file)
require __DIR__ . '/admin.php';

// Fallback 404
Route::fallback(fn () => response()->view('errors.404', [], 404));
