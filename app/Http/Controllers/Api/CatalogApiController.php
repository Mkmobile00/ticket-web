<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Event;
use App\Models\Movie;
use App\Models\Showtime;
use App\Models\Sport;
use App\Services\SeatLockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/** Public read-only catalog + seat availability for the mobile app. */
class CatalogApiController extends Controller
{
    public function __construct(private SeatLockService $locks) {}

    private function img(?string $path, string $fallback = 'assets/images/banner/banner04.jpg'): string
    {
        $path = $path ?: $fallback;
        return str_starts_with($path, 'http') ? $path
            : (str_starts_with($path, 'assets/') ? asset($path) : asset('storage/' . ltrim($path, '/')));
    }

    /** GET /api/v1/cities */
    public function cities()
    {
        return response()->json(['data' => City::orderBy('name')->get(['id', 'name', 'slug'])]);
    }

    /** GET /api/v1/genres · /languages · /formats — filter dropdown options. */
    public function genres()
    {
        return response()->json(['data' => \App\Models\Genre::orderBy('name')->get(['id', 'name', 'slug'])]);
    }

    public function languages()
    {
        return response()->json(['data' => \App\Models\Language::orderBy('name')->get(['id', 'name', 'code'])]);
    }

    public function formats()
    {
        return response()->json(['data' => \App\Models\Format::orderBy('name')->get(['id', 'name'])]);
    }

    /** GET /api/v1/movies?search=&city=&genre=&language=&page= */
    public function movies(Request $request)
    {
        $q = Movie::whereIn('status', ['now_showing', 'coming_soon', 'active'])
            ->with(['genres:id,name', 'languages:id,name,code', 'formats:id,name']);

        if ($s = $request->query('search')) $q->where('title', 'like', "%{$s}%");
        if ($g = $request->query('genre')) $q->whereHas('genres', fn ($x) => $x->where('genres.slug', $g)->orWhere('genres.id', $g));
        if ($city = $request->query('city')) {
            $q->whereHas('showtimes.screen.cinema', fn ($x) => $x->where('city_id', $city));
        }

        $movies = $q->latest()->paginate(12)->withQueryString();
        $movies->getCollection()->transform(fn ($m) => $this->movieCard($m));
        return response()->json($movies);
    }

    /** GET /api/v1/movies/{slug} */
    public function movie(Movie $movie)
    {
        $movie->load(['genres:id,name', 'languages:id,name,code', 'formats:id,name', 'cast']);
        return response()->json(['data' => array_merge($this->movieCard($movie), [
            'synopsis' => $movie->synopsis,
            'banner_image' => $this->img($movie->banner_image),
            'trailer_url' => $movie->trailer_url,
            'cast' => $movie->cast->map(fn ($c) => [
                'name' => $c->name,
                'character' => $c->pivot->character_name ?? null,
                'role' => $c->pivot->role ?? null,
            ]),
        ])]);
    }

    private function movieCard(Movie $m): array
    {
        return [
            'id' => $m->id,
            'title' => $m->title,
            'slug' => $m->slug,
            'poster_image' => $this->img($m->poster_image),
            'duration_minutes' => $m->duration_minutes,
            'release_date' => optional($m->release_date)->toDateString(),
            'user_rating' => (float) $m->user_rating,
            'status' => $m->status,
            'genres' => $m->genres->pluck('name'),
            'languages' => $m->languages->pluck('name'),
            'formats' => $m->formats->pluck('name'),
        ];
    }

    /** GET /api/v1/movies/{slug}/showtimes?city=&date= — grouped by cinema */
    public function movieShowtimes(Request $request, Movie $movie)
    {
        $cityId = $request->query('city');
        $showtimes = $movie->showtimes()
            ->with(['screen.cinema.city', 'language:id,name', 'format:id,name'])
            ->where('show_date', '>=', now()->toDateString())
            ->when($cityId, fn ($q) => $q->whereHas('screen.cinema', fn ($c) => $c->where('city_id', $cityId)))
            ->when($request->query('date'), fn ($q, $d) => $q->whereDate('show_date', $d))
            ->orderBy('show_date')->orderBy('show_time')
            ->get();

        $grouped = $showtimes->groupBy(fn ($s) => $s->screen->cinema_id)->map(fn ($g) => [
            'cinema' => [
                'id' => $g->first()->screen->cinema->id,
                'name' => $g->first()->screen->cinema->name,
                'city' => $g->first()->screen->cinema->city->name ?? null,
            ],
            'showtimes' => $g->map(fn ($s) => [
                'id' => $s->id,
                'screen' => $s->screen->name,
                'date' => $s->show_date->toDateString(),
                'time' => substr($s->show_time, 0, 5),
                'language' => $s->language->name ?? null,
                'format' => $s->format->name ?? null,
                'available_seats' => $s->available_seats,
            ])->values(),
        ])->values();

        return response()->json(['data' => $grouped]);
    }

    /** GET /api/v1/events  &  GET /api/v1/sports */
    public function events()
    {
        $events = Event::whereIn('status', ['upcoming', 'active', 'live'])->orderBy('event_date')->paginate(12);
        $events->getCollection()->transform(fn ($e) => [
            'id' => $e->id, 'title' => $e->title, 'slug' => $e->slug,
            'banner_image' => $this->img($e->banner_image),
            'date' => optional($e->event_date)->toDateString(),
            'venue' => $e->address, 'organizer' => $e->organizer,
        ]);
        return response()->json($events);
    }

    public function event(Event $event)
    {
        $event->load('tickets', 'speakers');
        return response()->json(['data' => [
            'id' => $event->id, 'title' => $event->title, 'slug' => $event->slug,
            'description' => $event->description, 'banner_image' => $this->img($event->banner_image),
            'date' => optional($event->event_date)->toDateString(),
            'start_time' => $event->start_time, 'venue' => $event->address, 'organizer' => $event->organizer,
            'tiers' => $event->seatTiers(),
            'speakers' => $event->speakers->map(fn ($s) => [
                'name' => $s->name, 'designation' => $s->designation,
                'photo' => $this->img($s->photo), 'about' => $s->about,
            ]),
        ]]);
    }

    public function sports()
    {
        $sports = Sport::whereIn('status', ['upcoming', 'active', 'live'])->with('city:id,name')->orderBy('sport_date')->paginate(12);
        $sports->getCollection()->transform(fn ($s) => [
            'id' => $s->id, 'title' => $s->title, 'slug' => $s->slug,
            'matchup' => $s->team_home && $s->team_away ? $s->team_home . ' vs ' . $s->team_away : $s->title,
            'banner_image' => $this->img($s->banner_image),
            'date' => optional($s->sport_date)->toDateString(),
            'venue' => $s->venue, 'city' => $s->city->name ?? null,
        ]);
        return response()->json($sports);
    }

    public function sport(Sport $sport)
    {
        $sport->load('tickets', 'city:id,name');
        return response()->json(['data' => [
            'id' => $sport->id, 'title' => $sport->title, 'slug' => $sport->slug,
            'matchup' => $sport->team_home && $sport->team_away ? $sport->team_home . ' vs ' . $sport->team_away : $sport->title,
            'description' => $sport->description, 'banner_image' => $this->img($sport->banner_image),
            'date' => optional($sport->sport_date)->toDateString(),
            'start_time' => $sport->start_time, 'venue' => $sport->venue, 'city' => $sport->city->name ?? null,
            'tiers' => $sport->seatTiers(),
        ]]);
    }

    /**
     * GET /api/v1/seats/{type}/{id}   type ∈ showtime|event|sport
     * Returns the seat map with per-seat status (available|locked|mine|booked).
     */
    public function seats(Request $request, string $type, int $id)
    {
        $seatable = $this->resolveSeatable($type, $id);
        abort_unless($seatable, 404);

        $layout = $seatable->seatLayoutArray();
        $rows = $layout['rows'] ?: ['A', 'B', 'C', 'D', 'E'];
        $perRow = $layout['seats_per_row'] ?: array_fill(0, count($rows), 20);

        $owner = $request->user() ? 'user:' . $request->user()->id : null;
        $lockMap = $this->locks->lockedSeatMap($seatable->seatContext());

        $booked = \App\Models\BookingSeat::where('seatable_type', $seatable->getMorphClass())
            ->where('seatable_id', $seatable->getKey())
            ->whereHas('booking', fn ($q) => $q->whereIn('status', ['pending', 'confirmed', 'completed']))
            ->get(['seat_row', 'seat_number'])
            ->map(fn ($s) => strtoupper($s->seat_row . '-' . $s->seat_number))->flip();

        $tierByRow = [];
        foreach ($seatable->seatTiers() as $t) {
            foreach ($t['rows'] as $r) $tierByRow[strtoupper($r)] = $t;
        }

        $grid = [];
        foreach ($rows as $i => $row) {
            $row = strtoupper($row);
            $seats = [];
            for ($n = 1; $n <= ($perRow[$i] ?? 0); $n++) {
                $sid = $row . '-' . $n;
                $status = $booked->has($sid) ? 'booked'
                    : (isset($lockMap[$sid]) ? ($lockMap[$sid] === $owner ? 'mine' : 'locked') : 'available');
                $seats[] = [
                    'id' => $sid, 'status' => $status,
                    'tier' => $tierByRow[$row]['name'] ?? null,
                    'price' => $tierByRow[$row]['price'] ?? null,
                ];
            }
            $grid[] = ['row' => $row, 'tier' => $tierByRow[$row]['name'] ?? null, 'seats' => $seats];
        }

        return response()->json([
            'context' => $seatable->seatContext(),
            'tiers' => $seatable->seatTiers()->values(),
            'rows' => $grid,
            'lock_ttl' => SeatLockService::TTL,
        ]);
    }

    private function resolveSeatable(string $type, int|string $id): ?Model
    {
        return match ($type) {
            'showtime' => Showtime::with('screen', 'ticketClasses')->find($id),
            'event' => Event::with('tickets')->find($id),
            'sport' => Sport::with('tickets')->find($id),
            default => null,
        };
    }
}
