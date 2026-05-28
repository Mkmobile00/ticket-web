<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Movie;
use App\Models\Sport;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function movies(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = Movie::query()->whereIn('status', ['now_showing', 'coming_soon', 'active']);
        if ($q !== '') {
            $query->where('title', 'like', "%{$q}%");
        }
        return $query->orderBy('title')->limit(8)->get(['id', 'title', 'slug', 'poster_image']);
    }

    public function events(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = Event::query()->whereIn('status', ['upcoming', 'active', 'live']);
        if ($q !== '') {
            $query->where('title', 'like', "%{$q}%");
        }
        return $query->orderBy('event_date')->limit(8)->get(['id', 'title', 'slug', 'banner_image', 'event_date', 'address']);
    }

    public function sports(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $query = Sport::query()->whereIn('status', ['upcoming', 'active', 'live']);
        if ($q !== '') {
            $query->where(function ($qq) use ($q) {
                $qq->where('title', 'like', "%{$q}%")
                   ->orWhere('team_home', 'like', "%{$q}%")
                   ->orWhere('team_away', 'like', "%{$q}%");
            });
        }
        return $query->orderBy('sport_date')->limit(8)->get(['id', 'title', 'slug', 'banner_image', 'sport_date', 'venue', 'city_id']);
    }

    public function movieAvailability(Movie $movie)
    {
        $showtimes = $movie->showtimes()
            ->with('screen.cinema.city')
            ->where('show_date', '>=', now()->toDateString())
            ->get();

        $cities = $showtimes->pluck('screen.cinema.city')->filter()->unique('id')->values()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name]);
        $cinemas = $showtimes->pluck('screen.cinema')->filter()->unique('id')->values()
            ->map(fn ($c) => ['id' => $c->id, 'name' => $c->name, 'city_id' => $c->city_id]);
        $dates = $showtimes->pluck('show_date')->map(fn ($d) => Carbon::parse($d)->toDateString())
            ->unique()->sort()->values()
            ->map(fn ($d) => ['value' => $d, 'label' => Carbon::parse($d)->format('D, d M Y')]);

        return [
            'movie' => ['id' => $movie->id, 'title' => $movie->title, 'slug' => $movie->slug],
            'cities' => $cities,
            'cinemas' => $cinemas,
            'dates' => $dates,
            'redirect' => route('movies.show', $movie->slug),
        ];
    }

    public function eventAvailability(Event $event)
    {
        $cityId = null;
        // Try to derive city from address — match against known cities
        $cityName = null;
        if ($event->address) {
            foreach (\App\Models\City::all() as $c) {
                if (str_contains(strtolower($event->address), strtolower($c->name))) {
                    $cityId = $c->id;
                    $cityName = $c->name;
                    break;
                }
            }
        }
        $categories = $event->categories()->get(['event_categories.id', 'event_categories.name', 'event_categories.slug']);

        return [
            'event' => ['id' => $event->id, 'title' => $event->title, 'slug' => $event->slug],
            'cities' => $cityId ? [['id' => $cityId, 'name' => $cityName]] : [],
            'dates' => [['value' => Carbon::parse($event->event_date)->toDateString(), 'label' => Carbon::parse($event->event_date)->format('D, d M Y')]],
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'slug' => $c->slug ?? $c->id, 'name' => $c->name]),
            'redirect' => route('events.show', $event->slug),
        ];
    }

    public function sportAvailability(Sport $sport)
    {
        $city = $sport->city_id ? \App\Models\City::find($sport->city_id) : null;
        $categories = $sport->categories()->get(['sport_categories.id', 'sport_categories.name', 'sport_categories.slug']);

        return [
            'sport' => ['id' => $sport->id, 'title' => $sport->title, 'slug' => $sport->slug],
            'cities' => $city ? [['id' => $city->id, 'name' => $city->name]] : [],
            'dates' => [['value' => Carbon::parse($sport->sport_date)->toDateString(), 'label' => Carbon::parse($sport->sport_date)->format('D, d M Y')]],
            'categories' => $categories->map(fn ($c) => ['id' => $c->id, 'slug' => $c->slug ?? $c->id, 'name' => $c->name]),
            'redirect' => route('sports.show', $sport->slug),
        ];
    }
}
