<?php

use App\Http\Controllers\Admin\BannerController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogCommentController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\BlogTagController;
use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Admin\CastMemberController;
use App\Http\Controllers\Admin\CinemaController;
use App\Http\Controllers\Admin\CityController;
use App\Http\Controllers\Admin\ContactMessageController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EventCategoryController;
use App\Http\Controllers\Admin\EventController as AdminEventController;
use App\Http\Controllers\Admin\EventSpeakerController;
use App\Http\Controllers\Admin\FaqController;
use App\Http\Controllers\Admin\FormatController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\MovieController as AdminMovieController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\PopcornItemController;
use App\Http\Controllers\Admin\PromoCodeController;
use App\Http\Controllers\Admin\ScreenController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ShowtimeController;
use App\Http\Controllers\Admin\SportCategoryController;
use App\Http\Controllers\Admin\SportController as AdminSportController;
use App\Http\Controllers\Admin\TicketClassController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('movies', AdminMovieController::class);
    // Drill-down: which cinemas / screens / showtimes / ticket classes a movie plays in.
    Route::get('movies/{movie}/playing', [AdminMovieController::class, 'playing'])->name('movies.playing');
    // Cast & crew per movie (select existing members + role/character)
    Route::get('movies/{movie}/cast', [AdminMovieController::class, 'cast'])->name('movies.cast');
    Route::post('movies/{movie}/cast', [AdminMovieController::class, 'attachCast'])->name('movies.cast.attach');
    Route::delete('movies/{movie}/cast/{castMember}', [AdminMovieController::class, 'detachCast'])->name('movies.cast.detach');
    Route::resource('cast-members', CastMemberController::class);
    Route::resource('cinemas', CinemaController::class);
    Route::resource('screens', ScreenController::class);
    Route::resource('showtimes', ShowtimeController::class);
    // Set a price per seat row for one showtime (groups rows into ticket-class tiers).
    Route::get('showtimes/{showtime}/pricing', [TicketClassController::class, 'pricing'])->name('showtimes.pricing');
    Route::post('showtimes/{showtime}/pricing', [TicketClassController::class, 'savePricing'])->name('showtimes.pricing.save');
    Route::resource('ticket-classes', TicketClassController::class);
    Route::resource('languages', LanguageController::class);
    Route::resource('formats', FormatController::class);
    Route::resource('genres', GenreController::class);
    Route::resource('promo-codes', PromoCodeController::class);
    Route::resource('popcorn-items', PopcornItemController::class);

    Route::resource('events', AdminEventController::class);
    Route::resource('event-categories', EventCategoryController::class);
    Route::resource('speakers', EventSpeakerController::class);

    Route::resource('sports', AdminSportController::class);
    Route::resource('sport-categories', SportCategoryController::class);

    Route::resource('blog-posts', BlogPostController::class);
    Route::resource('blog-categories', BlogCategoryController::class);
    Route::resource('blog-tags', BlogTagController::class);
    Route::resource('blog-comments', BlogCommentController::class)->only(['index', 'update', 'destroy']);

    Route::resource('users', UserController::class);
    Route::resource('bookings', AdminBookingController::class)->only(['index', 'show', 'destroy']);
    Route::post('bookings/{booking}/refund', [AdminBookingController::class, 'refund'])->name('bookings.refund');

    Route::resource('cities', CityController::class);
    Route::resource('contact-messages', ContactMessageController::class)->only(['index', 'show', 'destroy']);
    Route::resource('newsletter', AdminNewsletterController::class)->only(['index', 'destroy']);
    Route::resource('banners', BannerController::class);
    Route::resource('menus', \App\Http\Controllers\Admin\MenuController::class);
    Route::resource('faqs', FaqController::class);
    Route::resource('partners', PartnerController::class);

    Route::get('notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'index'])->name('notifications.index');
    Route::post('notifications', [\App\Http\Controllers\Admin\NotificationController::class, 'send'])->name('notifications.send');

    Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('settings', [SettingController::class, 'update'])->name('settings.update');

    Route::get('filemanager', fn () => view('admin.filemanager'))->name('filemanager');
});
