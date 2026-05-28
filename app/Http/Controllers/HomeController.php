<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Cinema;
use App\Models\City;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\Movie;
use App\Models\Sport;
use App\Models\SportCategory;
use Carbon\Carbon;

class HomeController extends Controller
{
    public function index()
    {
        // Build a 7-day date list for the search dropdowns
        $dates = collect(range(0, 6))->map(fn ($i) => [
            'value' => Carbon::today()->addDays($i)->toDateString(),
            'label' => Carbon::today()->addDays($i)->format('D, d M Y'),
        ]);

        return view('home', [
            'movies' => Movie::whereIn('status', ['now_showing', 'coming_soon', 'active'])->latest()->take(6)->get(),
            'events' => Event::whereIn('status', ['upcoming', 'active', 'live'])->orderBy('event_date')->take(3)->get(),
            'sports' => Sport::whereIn('status', ['upcoming', 'active', 'live'])->orderBy('sport_date')->take(3)->get(),
            'blogPosts' => BlogPost::whereNotNull('published_at')->latest('published_at')->take(3)->get(),

            'cities' => City::orderBy('name')->get(),
            'cinemas' => Cinema::orderBy('name')->get(),
            'eventCategories' => EventCategory::orderBy('name')->get(),
            'sportCategories' => SportCategory::orderBy('name')->get(),
            'dates' => $dates,

            'trending' => [
                Movie::latest()->take(2)->get()->map(fn($m) => ['title' => $m->title, 'tag' => 'Movies', 'url' => route('movies.show', $m->slug)]),
                Event::orderBy('event_date')->take(1)->get()->map(fn($e) => ['title' => $e->title, 'tag' => 'Event', 'url' => route('events.show', $e->slug)]),
                Sport::orderBy('sport_date')->take(1)->get()->map(fn($s) => ['title' => $s->title, 'tag' => 'Sports', 'url' => route('sports.show', $s->slug)]),
            ],
        ]);
    }
}
