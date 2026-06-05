<?php

namespace App\Http\Controllers;

use App\Models\Cinema;
use App\Models\City;
use App\Models\Event;
use App\Models\Movie;
use App\Models\Sport;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Serves the custom BOLETO single-file design (resources/design/*.html) and
 * injects live catalog data from the database into window.BOLETO_DATA so the
 * static React prototype renders real movies / events / sports / cities.
 */
class DesignController extends Controller
{
    /** Whitelisted design pages. */
    private const PAGES = [
        'index.html', 'movie.html', 'showtimes.html', 'seats.html', 'checkout.html',
        'event.html', 'event-seats.html', 'event-checkout.html',
        'sign-in.html', 'account.html', 'search.html',
    ];

    /** Accent tint pairs reused when the DB has no per-item colour. */
    private const COLORS = [
        ['#2b2b30', '#6c7a89'], ['#1b2a3a', '#2c6e9b'], ['#3a1414', '#a3382f'],
        ['#2a2233', '#7a5fa3'], ['#10202a', '#1d5a66'], ['#241018', '#8e2f4f'],
        ['#3a1024', '#a3306f'], ['#101e3a', '#2f4fb0'], ['#102a24', '#15a06a'],
        ['#0b3d2e', '#13a05a'], ['#1a2a4a', '#2f5fb0'], ['#3a2a10', '#cf8a1e'],
    ];

    public function page(Request $request, string $page = 'index')
    {
        $file = $page . '.html';
        abort_unless(in_array($file, self::PAGES, true), 404);

        $path = resource_path('design/' . $file);
        abort_unless(is_file($path), 404);

        $html = file_get_contents($path);

        $data = $this->catalog();
        if ($page === 'movie') {
            $data = array_merge($data, $this->movieDetail($request->query('m')));
        }

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $override = '<script>Object.assign(window.BOLETO_DATA, ' . $json . ');</script>';

        // Inject right after the default data IIFE so window.BOLETO_DATA already exists.
        $html = preg_replace('/(\}\)\(\);\s*<\/script>)/s', '$1' . "\n  " . $override, $html, 1);

        return response($html, 200)->header('Content-Type', 'text/html; charset=UTF-8');
    }

    /** Resolve a stored image path to an absolute URL (mirrors the API helper). */
    private function img(?string $path): ?string
    {
        if (!$path) return null;
        if (str_starts_with($path, 'http')) return $path;
        return str_starts_with($path, 'assets/') ? asset($path) : asset('storage/' . ltrim($path, '/'));
    }

    /** Set the active city (session) and return to the page. */
    public function setCity(Request $request)
    {
        $name = trim((string) $request->query('name', ''));
        $city = $name === '' ? null
            : City::where('name', $name)->orWhere('slug', Str::slug($name))->first();

        if ($city) {
            session(['selected_city_id' => $city->id, 'selected_city_name' => $city->name]);
        } else {
            // Unknown / "All cities" -> clear the filter (still remember a label if given).
            session()->forget('selected_city_id');
            $name === '' ? session()->forget('selected_city_name') : session(['selected_city_name' => $name]);
        }
        return back(302, [], '/');
    }

    /** Build the dynamic slice of window.BOLETO_DATA from the database. */
    private function catalog(): array
    {
        $cityId   = session('selected_city_id');
        $cityName = session('selected_city_name');

        $movies = $this->movies($cityId);
        $events = $this->events($cityId);
        $sports = $this->sports($cityId);
        $u = auth()->user();
        $customer = ($u && ! $u->is_admin) ? ['name' => $u->name, 'email' => $u->email] : null;

        return [
            'movies'       => $movies,
            'events'       => $events,
            'sports'       => $sports,
            'cities'       => City::orderBy('name')->pluck('name')->all(),
            'selectedCity' => $cityName,
            'searchDates'  => collect(range(0, 9))->map(fn ($i) => [
                'label' => Carbon::today()->addDays($i)->format('D, d M'),
                'value' => Carbon::today()->addDays($i)->toDateString(),
            ])->all(),
            'searchCinemas' => Cinema::orderBy('name')->pluck('name')->all(),
            'auth'         => ['user' => $customer],
            'nav'          => $this->nav($movies, $events, $sports),
        ];
    }

    /** GET /design-api/search?type=&q=&city=&date=&cinema= — live catalog search. */
    public function search(Request $request)
    {
        $type    = strtolower((string) $request->query('type', 'movie'));
        $q       = trim((string) $request->query('q', '')) ?: null;
        $cityNm  = $request->query('city');
        $cinema  = $request->query('cinema');
        $cinema  = ($cinema && $cinema !== 'All cinemas') ? $cinema : null;
        $date    = $request->query('date') ?: null;
        $cityId  = ($cityNm && $cityNm !== 'All cities')
            ? City::where('name', $cityNm)->orWhere('slug', Str::slug($cityNm))->value('id') : null;

        if (in_array($type, ['event', 'events'], true)) {
            $kind = 'events';
            $results = $this->events($cityId, $q);
        } elseif (in_array($type, ['sport', 'sports'], true)) {
            $kind = 'sports';
            $results = $this->sports($cityId, $q);
        } else {
            $kind = 'movie';
            $results = $this->movies($cityId, $q, $cinema, $date);
        }

        return response()->json(['type' => $kind, 'count' => count($results), 'results' => $results]);
    }

    /** Build a functional, data-driven top nav (sections + real item dropdowns). */
    private function nav(array $movies, array $events, array $sports): array
    {
        $drop = fn (array $items, string $page, string $key) => collect($items)->take(6)
            ->map(fn ($x) => ['label' => $x['title'], 'href' => $page . '?' . $key . '=' . \Illuminate\Support\Str::slug($x['title'])])
            ->all();

        return [
            ['label' => 'Home', 'href' => '/', 'on' => true],
            ['label' => 'Movies', 'href' => '/#movies', 'drop' => $drop($movies, '/movie', 'm')],
            ['label' => 'Events', 'href' => '/#events', 'drop' => $drop($events, '/event', 'e')],
            ['label' => 'Sports', 'href' => '/#sports', 'drop' => $drop($sports, '/event', 'e')],
            ['label' => 'My Bookings', 'href' => '/account'],
            ['label' => 'Contact', 'href' => '/#subscribe'],
        ];
    }

    private function movies(?int $cityId = null, ?string $q = null, ?string $cinema = null, ?string $date = null): array
    {
        return Movie::whereIn('status', ['now_showing', 'coming_soon', 'active'])
            ->when($cityId, fn ($x) => $x->whereHas('showtimes.screen.cinema', fn ($c) => $c->where('city_id', $cityId)))
            ->when($q, fn ($x) => $x->where('title', 'like', "%{$q}%"))
            ->when($cinema, fn ($x) => $x->whereHas('showtimes.screen.cinema', fn ($c) => $c->where('name', 'like', "%{$cinema}%")))
            ->when($date, fn ($x) => $x->whereHas('showtimes', fn ($s) => $s->whereDate('show_date', $date)))
            ->with(['genres:id,name', 'languages:id,name', 'formats:id,name'])
            ->latest()->take(24)->get()
            ->values()
            ->map(function ($m, $i) {
                $rating = (float) $m->user_rating;
                return [
                    'id'       => $m->id,
                    'slug'     => $m->slug,
                    'title'    => $m->title,
                    'poster'   => $this->img($m->poster_image),
                    'banner'   => $this->img($m->banner_image),
                    'genre'    => $m->genres->pluck('name')->implode(' • ') ?: 'Drama',
                    'rating'   => round($rating, 1),
                    'runtime'  => (int) $m->duration_minutes,
                    'critic'   => (int) ($m->rating_tomato ?: round($rating * 20)),
                    'audience' => (int) ($m->rating_audience ?: round($rating * 19)),
                    'colors'   => self::COLORS[$i % count(self::COLORS)],
                    'cert'     => 'UA',
                    'langs'    => $m->languages->pluck('name')->all() ?: ['Hindi'],
                    'formats'  => $m->formats->pluck('name')->all() ?: ['2D'],
                    'desc'     => \Illuminate\Support\Str::limit((string) $m->synopsis, 150) ?: ($m->title . ' — now showing.'),
                ];
            })->all();
    }

    /** Real synopsis + cast + crew for the movie detail page (by slug). */
    private function movieDetail(?string $slug): array
    {
        if (!$slug) return [];
        $movie = Movie::where('slug', $slug)->with('cast')->first()
            ?: Movie::with('cast')->get()->first(fn ($m) => Str::slug($m->title) === $slug);
        if (!$movie) return [];

        $cast = [];
        $crew = [];
        foreach ($movie->cast->sortBy(fn ($p) => $p->pivot->order ?? 0) as $p) {
            $role = strtolower((string) ($p->pivot->role ?? 'actor'));
            $row = ['name' => $p->name, 'photo' => $this->img($p->photo)];
            if ($role === 'actor' || $role === '') {
                $row['role'] = 'Actor';
                $row['as'] = (string) ($p->pivot->character_name ?? '');
                $cast[] = $row;
            } else {
                $row['role'] = ucwords($role);
                $crew[] = $row;
            }
        }

        $out = [];
        if ($movie->synopsis) $out['synopsis'] = (string) $movie->synopsis;
        if ($cast) $out['cast'] = $cast;
        if ($crew) $out['crew'] = $crew;
        return $out;
    }

    private function events(?int $cityId = null, ?string $q = null): array
    {
        return Event::whereIn('status', ['upcoming', 'ongoing', 'live', 'active'])
            ->when($cityId, fn ($x) => $x->where('city_id', $cityId))
            ->when($q, fn ($x) => $x->where('title', 'like', "%{$q}%"))
            ->with(['tickets', 'categories:id,name', 'city:id,name'])
            ->orderBy('event_date')->take(24)->get()
            ->values()
            ->map(function ($e, $i) {
                $min = collect($e->seatTiers())->min('price');
                $d   = $e->event_date instanceof Carbon ? $e->event_date : Carbon::parse($e->event_date);
                return [
                    'id'     => $e->id,
                    'slug'   => $e->slug,
                    'kind'   => 'event',
                    'title'  => $e->title,
                    'image'  => $this->img($e->banner_image),
                    'addr1'  => (string) $e->address,
                    'addr2'  => $e->city->name ?? '',
                    'day'    => $d->format('d'),
                    'mon'    => $d->format('M'),
                    'colors' => self::COLORS[($i + 6) % count(self::COLORS)],
                    'price'  => $min ? '₹' . (int) round($min) . ' onwards' : null,
                    'cat'    => $e->categories->first()->name ?? 'Event',
                    'desc'   => (string) $e->description,
                ];
            })->all();
    }

    private function sports(?int $cityId = null, ?string $q = null): array
    {
        return Sport::whereIn('status', ['upcoming', 'live', 'active'])
            ->when($cityId, fn ($x) => $x->where('city_id', $cityId))
            ->when($q, fn ($x) => $x->where('title', 'like', "%{$q}%"))
            ->with(['tickets', 'categories:id,name', 'city:id,name'])
            ->orderBy('sport_date')->take(24)->get()
            ->values()
            ->map(function ($s, $i) {
                $min  = collect($s->seatTiers())->min('price');
                $d    = $s->sport_date instanceof Carbon ? $s->sport_date : Carbon::parse($s->sport_date);
                $time = $s->start_time ? Carbon::parse($s->start_time)->format('g A') : null;
                return [
                    'id'     => $s->id,
                    'slug'   => $s->slug,
                    'kind'   => 'sport',
                    'title'  => $s->title,
                    'image'  => $this->img($s->banner_image),
                    'venue'  => $s->venue . ($s->city ? ', ' . $s->city->name : ''),
                    'day'    => $d->format('d'),
                    'mon'    => $d->format('M'),
                    'time'   => $time,
                    'colors' => self::COLORS[($i + 9) % count(self::COLORS)],
                    'price'  => $min ? '₹' . (int) round($min) . ' onwards' : null,
                    'cat'    => $s->categories->first()->name ?? 'Sports',
                    'desc'   => (string) ($s->description ?: (($s->team_home && $s->team_away) ? $s->team_home . ' vs ' . $s->team_away : $s->title)),
                ];
            })->all();
    }
}
