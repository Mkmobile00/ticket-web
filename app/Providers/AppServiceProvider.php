<?php

namespace App\Providers;

use App\Models\City;
use App\Support\LfmImageManager;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Drivers\Imagick\Driver as ImagickDriver;
use Intervention\Image\Interfaces\ImageManagerInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Override LFM's binding with a v2-compatible wrapper so $imageService->read()
        // (still used in LFM 2.14 for uploads/crop/resize) works on Intervention 3.x.
        $this->app->singleton(ImageManagerInterface::class, function () {
            $driver = config('lfm.intervention_driver', 'gd') === 'imagick'
                ? new ImagickDriver()
                : new GdDriver();
            return new LfmImageManager($driver);
        });
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Rate limiters (BookMyShow "Security" section), per-user where logged in.
        RateLimiter::for('seat-lock', fn (Request $r) => Limit::perMinute(10)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('payments', fn (Request $r) => Limit::perMinute(5)->by($r->user()?->id ?: $r->ip()));
        RateLimiter::for('login', fn (Request $r) => Limit::perMinutes(10, 5)->by($r->ip()));

        // Make the city list + currently-selected city available to the header
        // and showtime pages (the BookMyShow-style city selector).
        View::composer(['partials.header', 'movies.showtimes'], function ($view) {
            $view->with('allCities', City::orderBy('name')->get());
            $cityId = session('selected_city_id');
            $view->with('selectedCity', $cityId ? City::find($cityId) : null);
        });
    }
}
