<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Illuminate\Http\Request;

class MovieController extends Controller
{
    public function index(Request $request)
    {
        $query = Movie::whereIn('status', ['now_showing', 'coming_soon', 'active'])
            ->with(['genres', 'languages', 'formats']);

        if ($search = $request->query('search')) {
            $query->where('title', 'like', "%{$search}%");
        }
        if ($city = $request->query('city')) {
            $query->whereHas('showtimes.screen.cinema', fn ($q) => $q->where('city_id', $city));
        }
        if ($date = $request->query('date')) {
            $query->whereHas('showtimes', fn ($q) => $q->whereDate('show_date', $date));
        }
        if ($cinemaId = $request->query('cinema')) {
            $query->whereHas('showtimes.screen', fn ($q) => $q->where('cinema_id', $cinemaId));
        }
        if ($genre = $request->query('genre')) {
            $query->whereHas('genres', fn ($q) => $q->where('genres.id', $genre)->orWhere('genres.slug', $genre));
        }

        $movies = $query->latest()->paginate(12)->withQueryString();
        $view = $request->query('view') === 'list' ? 'movies.list' : 'movies.index';
        return view($view, compact('movies'));
    }

    public function show(Movie $movie)
    {
        $movie->load(['genres', 'languages', 'formats', 'cast', 'gallery']);
        $related = Movie::where('id', '!=', $movie->id)->whereIn('status', ['now_showing', 'coming_soon', 'active'])->inRandomOrder()->take(4)->get();
        return view('movies.show', compact('movie', 'related'));
    }

    public function showtimes(Movie $movie)
    {
        $movie->load('languages');

        // Respect the visitor's selected city (BookMyShow-style), if any.
        $cityId = session('selected_city_id');

        $showtimes = $movie->showtimes()
            ->with(['screen.cinema.city', 'language', 'format'])
            ->where('show_date', '>=', now()->toDateString())
            ->when($cityId, fn ($q) => $q->whereHas('screen.cinema', fn ($c) => $c->where('city_id', $cityId)))
            ->orderBy('show_date')->orderBy('show_time')
            ->get();

        // Group by cinema so each cinema gets a row of time-slot links.
        $byCinema = $showtimes->groupBy(fn ($s) => $s->screen->cinema_id);
        $cinemas = $byCinema->map(fn ($group) => [
            'cinema' => $group->first()->screen->cinema,
            'times' => $group->sortBy(fn ($s) => $s->show_date . ' ' . $s->show_time),
        ]);

        return view('movies.showtimes', compact('movie', 'cinemas'));
    }
}
