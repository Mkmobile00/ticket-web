<?php

namespace App\Http\Controllers\Admin;

use App\Models\CastMember;
use App\Models\Cinema;
use App\Models\Format;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Showtime;
use App\Models\TicketClass;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class MovieController extends AdminController
{
    protected string $modelClass = \App\Models\Movie::class;
    protected string $resource = 'movie';

    /** Relations editable on the movie form: [name, label, model]. */
    private const M2M = [
        ['genres', 'Genres', Genre::class],
        ['languages', 'Languages', Language::class],
        ['formats', 'Formats', Format::class],
    ];

    /** Add genre/language/format multi-selects to the auto-generated form. */
    protected function fields(?Model $item = null): array
    {
        $fields = parent::fields();
        foreach (self::M2M as [$key, $label, $cls]) {
            $fields[] = [
                'name' => $key,
                'label' => $label,
                'type' => 'multiselect',
                'options' => $cls::orderBy('name')->pluck('name', 'id')->all(),
                'selected' => ($item && $item->exists) ? $item->{$key}->pluck('id')->all() : [],
            ];
        }
        $fields[] = [
            'name' => 'gallery',
            'label' => 'Gallery Photos (movie detail "Photos" section)',
            'type' => 'image-repeater',
            'images' => ($item && $item->exists) ? $item->gallery()->orderBy('order')->pluck('image')->all() : [],
        ];
        return $fields;
    }

    private function syncM2M(Request $request, Movie $movie): void
    {
        foreach (self::M2M as [$key]) {
            $movie->{$key}()->sync(array_map('intval', (array) $request->input($key, [])));
        }
    }

    private function syncGallery(Request $request, Movie $movie): void
    {
        $images = array_values(array_filter(
            array_map('trim', (array) $request->input('gallery', [])),
            fn ($v) => $v !== ''
        ));
        $movie->gallery()->delete();
        foreach ($images as $i => $image) {
            $movie->gallery()->create(['image' => $image, 'order' => $i]);
        }
    }

    /**
     * Drill-down for one movie: Cinemas → Screens → Showtimes → Ticket classes,
     * each level filtered by the one above via query params.
     */
    public function playing(Request $request, $movie)
    {
        $movie = Movie::findOrFail($movie);
        $cinemaId = $request->integer('cinema_id') ?: null;
        $screenId = $request->integer('screen_id') ?: null;
        $showtimeId = $request->integer('showtime_id') ?: null;

        // Spine: every showtime of this movie, with its screen + cinema.
        $showtimes = Showtime::where('movie_id', $movie->id)->with('screen.cinema')->get();

        // Level 1 — cinemas this movie plays in (+ showtime count).
        $byCinema = $showtimes->groupBy(fn ($s) => $s->screen?->cinema?->id);
        $cinemas = Cinema::whereIn('id', $byCinema->keys()->filter()->all())
            ->orderBy('name')->get()
            ->map(fn ($c) => ['model' => $c, 'count' => $byCinema[$c->id]->count()]);

        $screens = collect();
        $shows = collect();
        $ticketClasses = collect();
        $cinema = $screen = $showtime = null;

        // Level 2 — screens of the chosen cinema (for this movie).
        if ($cinemaId) {
            $cinema = Cinema::find($cinemaId);
            $inCinema = $showtimes->filter(fn ($s) => $s->screen?->cinema?->id === $cinemaId);
            $byScreen = $inCinema->groupBy(fn ($s) => $s->screen?->id);
            $screens = Screen::whereIn('id', $byScreen->keys()->filter()->all())
                ->orderBy('name')->get()
                ->map(fn ($sc) => ['model' => $sc, 'count' => $byScreen[$sc->id]->count()]);
        }

        // Level 3 — showtimes on the chosen screen (for this movie).
        if ($cinemaId && $screenId) {
            $screen = Screen::find($screenId);
            $shows = $showtimes->filter(fn ($s) => (int) $s->screen_id === $screenId)
                ->sortBy(fn ($s) => $s->show_date . ' ' . $s->show_time)->values();
        }

        // Level 4 — ticket classes (price tiers) of the chosen showtime.
        if ($showtimeId) {
            $showtime = Showtime::with('screen.cinema')->find($showtimeId);
            $ticketClasses = TicketClass::where('showtime_id', $showtimeId)->orderBy('price')->get();
        }

        return view('admin.movies.playing', compact(
            'movie', 'cinemas', 'screens', 'shows', 'ticketClasses',
            'cinema', 'screen', 'showtime', 'cinemaId', 'screenId', 'showtimeId'
        ));
    }

    public function index()
    {
        $query = ($this->modelClass)::query()->latest('id');
        $filters = $this->applyIndexFilters($query);
        $items = $query->paginate(15)->withQueryString();
        return view('admin.crud.index', [
            'items' => $items,
            'filters' => $filters,
            'resource' => $this->resource,
            'columns' => $cols = $this->columns(),
            'fkLabels' => $this->fkLabelMap($cols),
            'title' => ucwords(str_replace('-', ' ', $this->resource)) . 's',
        ]);
    }

    public function create()
    {
        return view('admin.crud.form', [
            'item' => new ($this->modelClass),
            'resource' => $this->resource,
            'fields' => $this->fields(),
            'title' => 'Create ' . ucwords(str_replace('-', ' ', $this->resource)),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $movie = Movie::create($data);
        $this->syncM2M($request, $movie);
        $this->syncGallery($request, $movie);
        return redirect()->route('admin.movies.index')->with('status', 'Created.');
    }

    public function show($id)
    {
        $item = ($this->modelClass)::findOrFail($id);
        return view('admin.crud.show', ['item' => $item, 'resource' => $this->resource, 'fields' => $this->fields()]);
    }

    public function edit($id)
    {
        $item = ($this->modelClass)::findOrFail($id);
        return view('admin.crud.form', [
            'item' => $item,
            'resource' => $this->resource,
            'fields' => $this->fields($item),
            'title' => 'Edit ' . ucwords(str_replace('-', ' ', $this->resource)),
        ]);
    }

    public function update(Request $request, $id)
    {
        $item = ($this->modelClass)::findOrFail($id);
        $data = $request->validate($this->rules($item));
        $item->update($data);
        $this->syncM2M($request, $item);
        $this->syncGallery($request, $item);
        return redirect()->route('admin.movies.index')->with('status', 'Updated.');
    }

    public function destroy($id)
    {
        ($this->modelClass)::findOrFail($id)->delete();
        return back()->with('status', 'Deleted.');
    }

    /** GET movies/{movie}/cast — manage this movie's cast & crew. */
    public function cast($movie)
    {
        $movie = Movie::with(['cast' => fn ($q) => $q->orderByPivot('order')])->findOrFail($movie);
        $all = CastMember::orderBy('name')->get(['id', 'name']);
        return view('admin.movies.cast', compact('movie', 'all'));
    }

    /** POST movies/{movie}/cast — attach (or update) a cast/crew member. */
    public function attachCast(Request $request, $movie)
    {
        $movie = Movie::findOrFail($movie);
        $data = $request->validate([
            'cast_member_id' => 'required|exists:cast_members,id',
            'role'           => 'required|string|max:40',
            'character_name' => 'nullable|string|max:120',
            'order'          => 'nullable|integer|min:0|max:999',
        ]);
        $movie->cast()->syncWithoutDetaching([
            $data['cast_member_id'] => [
                'role'           => strtolower($data['role']),
                'character_name' => $data['character_name'] ?: null,
                'order'          => $data['order'] ?? 0,
            ],
        ]);
        return back()->with('status', 'Cast/crew member added.');
    }

    /** DELETE movies/{movie}/cast/{castMember} — remove from this movie. */
    public function detachCast($movie, $castMember)
    {
        Movie::findOrFail($movie)->cast()->detach($castMember);
        return back()->with('status', 'Removed from this movie.');
    }
}




