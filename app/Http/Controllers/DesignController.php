<?php

namespace App\Http\Controllers;

use App\Models\BlogPost;
use App\Models\Cinema;
use App\Models\City;
use App\Models\Event;
use App\Models\Faq;
use App\Models\Movie;
use App\Models\Partner;
use App\Models\Setting;
use App\Models\SidebarBanner;
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
        'sign-in.html', 'account.html', 'search.html', 'list.html', 'blog.html', 'about.html',
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
        $data = $this->catalog();
        if ($page === 'movie') {
            $data = array_merge($data, $this->movieDetail($request->query('m')));
        }
        if ($page === 'event') {
            $data = array_merge($data, $this->eventDetail($request->query('e')));
        }

        return $this->serve($page, $data);
    }

    /** Blog listing (BOLETO design). */
    public function blogIndex(Request $request)
    {
        return $this->serve('blog', array_merge($this->catalog(), ['blogPosts' => $this->blogList()]));
    }

    /** About page (BOLETO design, content from admin → Settings). */
    public function about(Request $request)
    {
        $partners = Partner::where('is_active', true)->orderBy('name')->get()
            ->map(fn ($p) => ['name' => $p->name, 'logo' => $this->img($p->logo), 'url' => (string) $p->url])->all();
        $faqs = Faq::where('is_active', true)->orderBy('order')->take(8)->get()
            ->map(fn ($f) => ['q' => $f->question, 'a' => $f->answer])->all();

        return $this->serve('about', array_merge($this->catalog(), [
            'about'    => $this->aboutData(),
            'partners' => $partners,
            'faqs'     => $faqs,
        ]));
    }

    /** Build the About content object from settings (admin-editable). */
    private function aboutData(): array
    {
        $s = Setting::whereIn('key', [
            'about_hero_title', 'about_hero_subtitle', 'about_story_title', 'about_story_body',
            'about_story_image', 'about_philosophy_title', 'about_philosophy_body', 'about_values', 'about_stats',
        ])->pluck('value', 'key');

        $lines = fn (?string $v) => collect(preg_split('/\R/', (string) $v))->map(fn ($x) => trim($x))->filter()->values();

        return [
            'heroTitle'       => (string) ($s['about_hero_title'] ?? 'About Us'),
            'heroSubtitle'    => (string) ($s['about_hero_subtitle'] ?? ''),
            'storyTitle'      => (string) ($s['about_story_title'] ?? 'Get to know us'),
            'storyBody'       => $lines($s['about_story_body'] ?? '')->all(), // paragraphs
            'storyImage'      => $this->img($s['about_story_image'] ?? null),
            'philosophyTitle' => (string) ($s['about_philosophy_title'] ?? 'Our Philosophy'),
            'philosophyBody'  => (string) ($s['about_philosophy_body'] ?? ''),
            'values'          => $lines($s['about_values'] ?? '')->all(),
            'stats'           => $lines($s['about_stats'] ?? '')->map(function ($l) {
                $p = explode('|', $l, 2);
                return ['num' => trim($p[0] ?? ''), 'label' => trim($p[1] ?? '')];
            })->all(),
        ];
    }

    /** Blog detail (BOLETO design). */
    public function blogShow(Request $request, \App\Models\BlogPost $post)
    {
        $post->incrementViews();
        return $this->serve('blog', array_merge($this->catalog(), [
            'blogPost'  => $this->blogDetail($post),
            'blogPosts' => $this->blogList(3, $post->id), // "recent posts" rail
        ]));
    }

    /** Read a design file and inject window.BOLETO_DATA overrides. */
    private function serve(string $page, array $data)
    {
        $file = $page . '.html';
        abort_unless(in_array($file, self::PAGES, true), 404);

        $path = resource_path('design/' . $file);
        abort_unless(is_file($path), 404);

        $html = file_get_contents($path);

        $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        // Neutralise "</script>" (and any "</…") inside the JSON so rich HTML
        // (e.g. blog content) can't break out of the inline <script> tag.
        $json = str_replace('</', '<\/', $json);
        $override = '<script>Object.assign(window.BOLETO_DATA, ' . $json . ');</script>';

        // Inject right after the default data IIFE so window.BOLETO_DATA already exists.
        // Use a callback so $-sequences in the JSON (e.g. prices like "$299") are
        // NOT interpreted as regex backreferences.
        $html = preg_replace_callback('/(\}\)\(\);\s*<\/script>)/s', fn ($m) => $m[1] . "\n  " . $override, $html, 1);

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
            'sidebarBanners' => $this->sidebarBanners(),
        ];
    }

    /** Active sidebar promo cards (managed in admin → Banners). */
    private function sidebarBanners(): array
    {
        return SidebarBanner::where('is_active', true)
            ->whereIn('placement', ['sidebar', 'both'])
            ->orderBy('position')->orderBy('id')
            ->get()
            ->map(fn ($b) => [
                'kicker'   => (string) $b->kicker,
                'title'    => (string) $b->title,
                'subtitle' => (string) $b->subtitle,
                'cta'      => (string) ($b->cta_text ?: 'Learn More'),
                'link'     => (string) $b->link,
                'image'    => $this->img($b->image),
            ])->all();
    }

    /** Published blog posts for the listing / recent rail. */
    private function blogList(?int $limit = null, ?int $excludeId = null): array
    {
        return BlogPost::whereNotNull('published_at')
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->with(['author:id,name', 'categories:id,name'])
            ->latest('published_at')
            ->when($limit, fn ($q) => $q->take($limit))
            ->get()
            ->map(fn ($p) => [
                'title'    => $p->title,
                'slug'     => $p->slug,
                'excerpt'  => (string) ($p->excerpt ?: Str::limit(strip_tags((string) $p->content), 140)),
                'image'    => $this->img($p->thumbnail),
                'author'   => $p->author->name ?? 'Boleto',
                'date'     => optional($p->published_at)->format('M d, Y'),
                'category' => $p->categories->first()->name ?? 'Blog',
                'views'    => (int) $p->views,
            ])->all();
    }

    /** Single blog post for the detail page. */
    private function blogDetail(BlogPost $post): array
    {
        $post->loadMissing(['author:id,name', 'categories:id,name', 'tags:id,name']);
        return [
            'title'      => $post->title,
            'slug'       => $post->slug,
            'content'    => (string) $post->content,
            'image'      => $this->img($post->thumbnail),
            'author'     => $post->author->name ?? 'Boleto',
            'date'       => optional($post->published_at)->format('M d, Y'),
            'categories' => $post->categories->pluck('name')->all(),
            'tags'       => $post->tags->pluck('name')->all(),
            'views'      => (int) $post->views,
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
            ['label' => 'Movies', 'href' => '/movies', 'drop' => $drop($movies, '/movie', 'm')],
            ['label' => 'Events', 'href' => '/events', 'drop' => $drop($events, '/event', 'e')],
            ['label' => 'Sports', 'href' => '/sports', 'drop' => $drop($sports, '/event', 'e')],
            ['label' => 'Blog', 'href' => '/blog'],
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
        $movie = Movie::where('slug', $slug)->with('cast', 'gallery')->first()
            ?: Movie::with('cast', 'gallery')->get()->first(fn ($m) => Str::slug($m->title) === $slug);
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

        $gallery = $movie->gallery->map(fn ($g) => $this->img($g->image))->filter()->values()->all();

        $out = [];
        if ($movie->synopsis) $out['synopsis'] = (string) $movie->synopsis;
        if ($cast) $out['cast'] = $cast;
        if ($crew) $out['crew'] = $crew;
        if ($gallery) $out['gallery'] = $gallery;
        if ($movie->trailer_embed_url) $out['trailer'] = $movie->trailer_embed_url;
        return $out;
    }

    /**
     * Per-item detail for the event page (mirrors movieDetail()). The event page
     * serves BOTH events and sports, so this resolves the slug to either and
     * overrides the static window.BOLETO_DATA.eventInfo with real data: the
     * countdown targets the actual date/time, the intro is its description, and
     * speakers / stats come from the DB (events only). Sections with no data are
     * returned empty so the page can hide them instead of showing placeholders.
     */
    private function eventDetail(?string $slug): array
    {
        $eventBase = fn () => Event::with(['speakers', 'stats', 'city']);
        $sportBase = fn () => Sport::with(['city']);

        // Resolve to an event first, then a sport. No slug (direct /event visit)
        // -> first upcoming event, matching the client-side getEvent() fallback.
        $isSport = false;
        if ($slug) {
            $item = $eventBase()->where('slug', $slug)->first()
                ?: $eventBase()->get()->first(fn ($e) => Str::slug($e->title) === $slug);
            if (!$item) {
                $item = $sportBase()->where('slug', $slug)->first()
                    ?: $sportBase()->get()->first(fn ($s) => Str::slug($s->title) === $slug);
                $isSport = (bool) $item;
            }
        } else {
            $item = $eventBase()->orderBy('event_date')->first();
        }
        if (!$item) return [];

        $rawDate = $isSport ? $item->sport_date : $item->event_date;
        $date  = $rawDate instanceof Carbon ? $rawDate : ($rawDate ? Carbon::parse($rawDate) : null);
        $start = $item->start_time ? Carbon::parse($item->start_time) : null;
        $countdown = $date
            ? $date->copy()->setTimeFrom($start ?: Carbon::createFromTime(9, 0))->format('Y-m-d\TH:i:s')
            : null;

        // Intro paragraphs from the description (split on blank lines). For a
        // sport, fall back to "Home vs Away".
        $description = (string) $item->description;
        if ($isSport && trim($description) === '' && $item->team_home && $item->team_away) {
            $description = $item->team_home . ' vs ' . $item->team_away;
        }
        $intro = collect(preg_split('/\R{2,}/', trim($description)))
            ->map(fn ($p) => trim($p))->filter()->values()->all();

        // Speakers + stats are an events-only concept.
        $speakers = $isSport ? [] : $item->speakers->map(fn ($s) => [
            'name'  => $s->name,
            'role'  => $s->designation ?: 'Speaker',
            'photo' => $this->img($s->photo),
            'about' => (string) $s->about,
        ])->all();

        $statIcons = ['ticket', 'cal', 'mic', 'star', 'users'];
        $stats = $isSport ? [] : $item->stats->values()->map(fn ($s, $i) => [
            'ico' => $statIcons[$i % count($statIcons)],
            'n'   => (string) $s->value,
            'l'   => (string) $s->label,
        ])->all();

        $faq = Faq::where('is_active', true)->orderBy('order')->take(6)->get()
            ->map(fn ($f) => ['q' => $f->question, 'a' => $f->answer])->all();

        $sponsors = Partner::where('is_active', true)->orderBy('name')->pluck('name')->all();

        $email = optional(Setting::where('key', 'contact_email')->first())->value
            ?: (config('mail.from.address') ?: 'hello@boleto.com');

        $venue = $isSport
            ? ($item->venue ?: ($item->city->name ?? ''))
            : ($item->address ?: ($item->city->name ?? ''));

        return ['eventInfo' => [
            'countdownTo' => $countdown ?: '',
            'dateLabel'   => $date ? $date->format('D, d M Y') : 'Date to be announced',
            'timeLabel'   => $start ? $start->format('g:i A') . ' onwards' : 'Doors open soon',
            'time'        => $start ? $start->format('g:i A') : 'TBA',
            'venue'       => $venue,
            'trailer'     => (string) ($item->trailer_embed_url ?: ''),
            'organizer'   => (string) ($isSport ? '' : $item->organizer),
            'email'       => $email,
            'ready'       => $isSport ? 'Are you ready for kick-off?' : 'Are you ready to attend?',
            'intro'       => $intro ?: ['More details will be announced soon.'],
            'galleryCount' => 0, // no per-item gallery in the DB — hide the gallery strip
            'speakers'    => $speakers,
            'stats'       => $stats,
            'faq'         => $faq,
            'sponsorTabs' => $sponsors ? ['Partners & Sponsors'] : [],
            'sponsors'    => $sponsors,
        ]];
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
                    'detail' => $this->img($e->detail_image),
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
                    'detail' => $this->img($s->detail_image),
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
