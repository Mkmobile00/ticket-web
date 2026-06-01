@extends('layouts.frontend')

@push('styles')
<style>
    /* =====================================================================
       Mobile & Tablet polish (≤991px). Desktop untouched.
       Poster-first cards, tidy spacing, clean type — BigMovies-style.
       ===================================================================== */
    @media (max-width: 991.98px) {
        /* --- Card shell (movies / events / sports share these classes) --- */
        .movie-grid, .event-grid, .sports-grid {
            background: #0f1733;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 8px 22px rgba(0, 0, 0, .30);
            margin-bottom: 18px;
            transition: transform .18s ease, box-shadow .18s ease;
        }
        .movie-grid:active, .event-grid:active, .sports-grid:active { transform: scale(.985); }

        /* Poster: consistent 2:3, fills the card, no inner radius */
        .movie-grid .movie-thumb, .event-grid .movie-thumb, .sports-grid .movie-thumb { margin: 0; position: relative; }
        .movie-grid .movie-thumb img, .event-grid .movie-thumb img, .sports-grid .movie-thumb img {
            width: 100%; aspect-ratio: 2 / 3; object-fit: cover; display: block;
        }

        /* Date badge (events/sports) — compact pill, top-left */
        .event-grid .event-date, .sports-grid .event-date {
            position: absolute; top: 8px; left: 8px; padding: 4px 8px;
            background: rgba(13, 17, 34, .82); border-radius: 8px; line-height: 1.1;
        }
        .event-grid .event-date .date-title, .sports-grid .event-date .date-title { font-size: 11px; }

        /* Content area */
        .movie-content {
            padding: 10px 12px 12px;
            background: linear-gradient(180deg, #131b3a 0%, #0f1733 100%) !important;
        }
        .movie-content .title { margin: 0 0 6px; font-size: 14px; line-height: 1.3; }
        .movie-content .title a {
            display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical;
            overflow: hidden; color: #fff;
        }
        .movie-rating-percent { display: flex; gap: 12px; padding: 0; margin: 0; }
        .movie-rating-percent li { display: flex; align-items: center; gap: 5px; font-size: 12px; color: #cfd4db; }
        .movie-rating-percent .thumb img { width: 15px; height: 15px; }

        /* --- Section headers --- */
        .section-header-1 { display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; }
        .section-header-1 .title { font-size: 20px; margin: 0; }
        .section-header-1 .view-all { font-size: 13px; }

        /* --- Section spacing --- */
        .movie-section.padding-top { padding-top: 36px; }
        .movie-section.padding-bottom { padding-bottom: 36px; }
        .article-section.padding-bottom { padding-bottom: 26px; }

        /* --- Tighter gutters for the 2-up grid --- */
        .movie-section .row.mb-30-none { margin-left: -7px; margin-right: -7px; }
        .movie-section .row.mb-30-none > [class*="col-"] { padding-left: 7px; padding-right: 7px; }

        /* --- "What are you looking for" — Movie / Event / Sports on ONE line --- */
        .ticket-tab-menu {
            display: flex !important;
            flex-wrap: nowrap;
            justify-content: center;
            gap: 8px;
            margin-top: 8px;
        }
        .ticket-tab-menu li {
            flex: 1 1 0;
            min-width: 0;
            display: flex !important;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 5px;
            margin: 0 !important;
            padding: 10px 4px !important;
            text-align: center;
            border-radius: 30px;
        }
        .ticket-tab-menu li .tab-thumb { margin: 0 !important; }
        .ticket-tab-menu li .tab-thumb img { width: 26px; height: 26px; }
        .ticket-tab-menu li span { font-size: 12px; line-height: 1; white-space: nowrap; }

        /* --- City / Date / Cinema filters on ONE line (search box full width above) --- */
        .ticket-search-form { display: flex !important; flex-wrap: wrap; gap: 10px; }
        .ticket-search-form .form-group.large { flex: 1 1 100%; margin: 0 !important; }
        .ticket-search-form .form-group:not(.large) {
            flex: 1 1 0; min-width: 0; margin: 0 !important;
            display: flex !important; flex-direction: column; align-items: flex-start;
            gap: 3px; padding: 9px 10px; height: auto;
        }
        .ticket-search-form .form-group:not(.large) .thumb { position: static; margin: 0; }
        .ticket-search-form .form-group:not(.large) .thumb img { width: 16px; height: 16px; }
        .ticket-search-form .form-group:not(.large) .type { font-size: 11px; margin: 0; }
        .ticket-search-form .form-group:not(.large) .nice-select,
        .ticket-search-form .form-group:not(.large) .select-bar {
            width: 100% !important; min-width: 0 !important; font-size: 12px;
            padding-left: 0; padding-right: 16px; height: auto; line-height: 1.4;
            background-color: transparent;
        }
        .ticket-search-form .form-group:not(.large) .nice-select .current { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: block; }
    }

    /* --- Phone-only (≤575px): compact hero + search --- */
    @media (max-width: 575.98px) {
        .banner-section { padding: 72px 0 0 !important; min-height: auto !important; }
        .banner-section .banner-content .title { font-size: 29px !important; line-height: 1.18; }
        .banner-section .banner-content p { font-size: 13px; margin: 8px 0 0; }

        /* A small, comfortable gap between the hero slider and the search panel */
        .search-ticket-section { padding-top: 22px !important; }

        .search-tab { padding: 22px 16px !important; }
        .search-ticket-header .title { font-size: 19px; }
        .search-ticket-header .category { font-size: 12px; }

        .section-header-1 .title { font-size: 18px; }
        .movie-content .title { font-size: 13px; }
    }

    /* --- Tablet (576–991px): roomier hero, 3-up cards via col-md-4 --- */
    @media (min-width: 576px) and (max-width: 991.98px) {
        .banner-section .banner-content .title { font-size: 44px !important; }
        .section-header-1 .title { font-size: 22px; }
    }
</style>
@endpush

@section('content')
<!-- ==========Banner-Section========== -->
    <section class="banner-section">
        <div class="banner-bg bg_img bg-fixed" data-background="{{ \App\Models\Setting::image('hero_bg', 'assets/images/banner/banner01.jpg') }}"></div>
        <div class="container">
            @php
                $heroWords = collect(explode(',', \App\Models\Setting::getValue('hero_words', 'Movie,Event,Sport')))
                    ->map(fn ($w) => trim($w))->filter()->values();
            @endphp
            <div class="banner-content">
                <h1 class="title  cd-headline clip"><span class="d-block">{{ \App\Models\Setting::getValue('hero_line_1', 'book your') }}</span> {{ \App\Models\Setting::getValue('hero_line_2', 'tickets for') }}
                    <span class="color-theme cd-words-wrapper p-0 m-0">
                        @foreach ($heroWords as $i => $word)
                            <b class="{{ $i === 0 ? 'is-visible' : '' }}">{{ $word }}</b>
                        @endforeach
                    </span>
                </h1>
                <p>{{ \App\Models\Setting::getValue('hero_subtitle', 'Safe, secure, reliable ticketing. Your ticket to live entertainment!') }}</p>
            </div>
        </div>
    </section>
    <!-- ==========Banner-Section========== -->

    <!-- ==========Ticket-Search========== -->
    <section class="search-ticket-section padding-top pt-lg-0">
        <div class="container">
            <div class="search-tab bg_img" data-background="{{ \App\Models\Setting::image('search_bg', 'assets/images/ticket/ticket-bg01.jpg') }}">
                <div class="row align-items-center mb--20">
                    <div class="col-lg-6 mb-20">
                        <div class="search-ticket-header">
                            <h6 class="category">welcome to Boleto </h6>
                            <h3 class="title">what are you looking for</h3>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-20">
                        <ul class="tab-menu ticket-tab-menu">
                            <li class="active">
                                <div class="tab-thumb">
                                    <img src="{{ asset('assets/images/ticket/ticket-tab01.png') }}" alt="ticket">
                                </div>
                                <span>movie</span>
                            </li>
                            <li>
                                <div class="tab-thumb">
                                    <img src="{{ asset('assets/images/ticket/ticket-tab02.png') }}" alt="ticket">
                                </div>
                                <span>events</span>
                            </li>
                            <li>
                                <div class="tab-thumb">
                                    <img src="{{ asset('assets/images/ticket/ticket-tab03.png') }}" alt="ticket">
                                </div>
                                <span>sports</span>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="tab-area">
                    <!-- ===== MOVIES TAB ===== -->
                    <div class="tab-item active">
                        <form class="ticket-search-form smart-search-form" method="GET" action="{{ route('movies.index') }}"
                              data-type="movies"
                              data-search-url="{{ route('api.search.movies') }}"
                              data-availability-url="{{ url('/api/availability/movie') }}"
                              data-detail-url="{{ url('/movies') }}">
                            <input type="hidden" name="selected_slug" class="selected-slug">
                            <div class="form-group large" style="position:relative;">
                                <input type="text" name="search" class="search-input" autocomplete="off" placeholder="Search for Movies" value="{{ request('search') }}">
                                <button type="submit"><i class="fas fa-search"></i></button>
                                <div class="autocomplete-results" style="display:none;"></div>
                            </div>
                            <div class="form-group">
                                <div class="thumb"><img src="{{ asset('assets/images/ticket/city.png') }}" alt="city"></div>
                                <span class="type">city</span>
                                <select class="select-bar" name="city">
                                    <option value="">All cities</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="thumb"><img src="{{ asset('assets/images/ticket/date.png') }}" alt="date"></div>
                                <span class="type">date</span>
                                <select class="select-bar" name="date">
                                    <option value="">Any date</option>
                                    @foreach ($dates as $d)
                                        <option value="{{ $d['value'] }}">{{ $d['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="thumb"><img src="{{ asset('assets/images/ticket/cinema.png') }}" alt="cinema"></div>
                                <span class="type">cinema</span>
                                <select class="select-bar" name="cinema">
                                    <option value="">All cinemas</option>
                                    @foreach ($cinemas as $c)
                                        <option value="{{ $c->id }}">{{ $c->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    <!-- ===== EVENTS TAB ===== -->
                    <div class="tab-item">
                        <form class="ticket-search-form smart-search-form" method="GET" action="{{ route('events.index') }}"
                              data-type="events"
                              data-search-url="{{ route('api.search.events') }}"
                              data-availability-url="{{ url('/api/availability/event') }}"
                              data-detail-url="{{ url('/events') }}">
                            <input type="hidden" name="selected_slug" class="selected-slug">
                            <div class="form-group large" style="position:relative;">
                                <input type="text" name="search" class="search-input" autocomplete="off" placeholder="Search for Events" value="{{ request('search') }}">
                                <button type="submit"><i class="fas fa-search"></i></button>
                                <div class="autocomplete-results" style="display:none;"></div>
                            </div>
                            <div class="form-group">
                                <div class="thumb"><img src="{{ asset('assets/images/ticket/city.png') }}" alt="city"></div>
                                <span class="type">city</span>
                                <select class="select-bar" name="city">
                                    <option value="">All cities</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="thumb"><img src="{{ asset('assets/images/ticket/date.png') }}" alt="date"></div>
                                <span class="type">date</span>
                                <select class="select-bar" name="date">
                                    <option value="">Any date</option>
                                    @foreach ($dates as $d)
                                        <option value="{{ $d['value'] }}">{{ $d['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="thumb"><img src="{{ asset('assets/images/ticket/cinema.png') }}" alt="event"></div>
                                <span class="type">category</span>
                                <select class="select-bar" name="category">
                                    <option value="">All categories</option>
                                    @foreach ($eventCategories as $cat)
                                        <option value="{{ $cat->slug ?? $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>

                    <!-- ===== SPORTS TAB ===== -->
                    <div class="tab-item">
                        <form class="ticket-search-form smart-search-form" method="GET" action="{{ route('sports.index') }}"
                              data-type="sports"
                              data-search-url="{{ route('api.search.sports') }}"
                              data-availability-url="{{ url('/api/availability/sport') }}"
                              data-detail-url="{{ url('/sports') }}">
                            <input type="hidden" name="selected_slug" class="selected-slug">
                            <div class="form-group large" style="position:relative;">
                                <input type="text" name="search" class="search-input" autocomplete="off" placeholder="Search for Sports" value="{{ request('search') }}">
                                <button type="submit"><i class="fas fa-search"></i></button>
                                <div class="autocomplete-results" style="display:none;"></div>
                            </div>
                            <div class="form-group">
                                <div class="thumb"><img src="{{ asset('assets/images/ticket/city.png') }}" alt="city"></div>
                                <span class="type">city</span>
                                <select class="select-bar" name="city">
                                    <option value="">All cities</option>
                                    @foreach ($cities as $city)
                                        <option value="{{ $city->id }}">{{ $city->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="thumb"><img src="{{ asset('assets/images/ticket/date.png') }}" alt="date"></div>
                                <span class="type">date</span>
                                <select class="select-bar" name="date">
                                    <option value="">Any date</option>
                                    @foreach ($dates as $d)
                                        <option value="{{ $d['value'] }}">{{ $d['label'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="form-group">
                                <div class="thumb"><img src="{{ asset('assets/images/ticket/cinema.png') }}" alt="sport"></div>
                                <span class="type">category</span>
                                <select class="select-bar" name="category">
                                    <option value="">All categories</option>
                                    @foreach ($sportCategories as $cat)
                                        <option value="{{ $cat->slug ?? $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>    
    <!-- ==========Ticket-Search========== -->

    <!-- ==========Movie-Main-Section========== -->
    <section class="movie-section padding-top padding-bottom bg-two">
        <div class="container">
            <div class="row flex-wrap-reverse justify-content-center">
                <div class="col-lg-3 col-sm-10 mt-50 mt-lg-0 d-none d-lg-block">
                    <div class="widget-1 widget-facility">
                        <div class="widget-1-body">
                            <ul>
                                <li>
                                    <a href="#0">
                                        <span class="img"><img src="{{ asset('assets/images/sidebar/icons/sidebar01.png') }}" alt="sidebar"></span>
                                        <span class="cate">{{ \App\Models\Setting::getValue('badge_1', '24X7 Care') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#0">
                                        <span class="img"><img src="{{ asset('assets/images/sidebar/icons/sidebar02.png') }}" alt="sidebar"></span>
                                        <span class="cate">{{ \App\Models\Setting::getValue('badge_2', '100% Assurance') }}</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#0">
                                        <span class="img"><img src="{{ asset('assets/images/sidebar/icons/sidebar03.png') }}" alt="sidebar"></span>
                                        <span class="cate">{{ \App\Models\Setting::getValue('badge_3', 'Our Promise') }}</span>
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                    @if ($sidebarBanners->isNotEmpty())
                        @php $b = $sidebarBanners->first(); @endphp
                        @php $bimg = str_starts_with($b->image, 'http') ? $b->image : (str_starts_with($b->image, 'assets/') ? asset($b->image) : asset('storage/' . ltrim($b->image, '/'))); @endphp
                        <div class="widget-1 widget-banner">
                            <div class="widget-1-body">
                                <a href="{{ $b->link ?: '#0' }}">
                                    <img src="{{ $bimg }}" alt="{{ $b->title }}">
                                </a>
                            </div>
                        </div>
                    @endif
                    <div class="widget-1 widget-trending-search">
                        <h3 class="title">Trending Searches</h3>
                        <div class="widget-1-body">
                            <ul>
                                @foreach (collect($trending)->flatten(1) as $t)
                                    <li>
                                        <h6 class="sub-title">
                                            <a href="{{ $t['url'] }}">{{ Str::limit($t['title'], 32) }}</a>
                                        </h6>
                                        <p>{{ $t['tag'] }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    @foreach ($sidebarBanners->slice(1) as $b)
                        @php $bimg = str_starts_with($b->image, 'http') ? $b->image : (str_starts_with($b->image, 'assets/') ? asset($b->image) : asset('storage/' . ltrim($b->image, '/'))); @endphp
                        <div class="widget-1 widget-banner">
                            <div class="widget-1-body">
                                <a href="{{ $b->link ?: '#0' }}">
                                    <img src="{{ $bimg }}" alt="{{ $b->title }}">
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="col-lg-9">
                    <div class="article-section padding-bottom">
                        <div class="section-header-1">
                            <h2 class="title">movies</h2>
                            <a class="view-all" href="{{ route('movies.index') }}">View All</a>
                        </div>
                        <div class="row mb-30-none justify-content-center">
                            @foreach ($movies as $movie)
                                <div class="col-6 col-md-4">
                                    <x-movie-card :movie="$movie" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="article-section padding-bottom">
                        <div class="section-header-1">
                            <h2 class="title">events</h2>
                            <a class="view-all" href="{{ route('events.index') }}">View All</a>
                        </div>
                        <div class="row mb-30-none justify-content-center">
                            @foreach ($events as $event)
                                <div class="col-6 col-md-4">
                                    <x-event-card :event="$event" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="article-section">
                        <div class="section-header-1">
                            <h2 class="title">sports</h2>
                            <a class="view-all" href="{{ route('sports.index') }}">View All</a>
                        </div>
                        <div class="row mb-30-none justify-content-center">
                            @foreach ($sports as $sport)
                                <div class="col-6 col-md-4">
                                    <x-sport-card :sport="$sport" />
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Movie-Main-Section========== -->

    @push('styles')
    <style>
        .autocomplete-results {
            position:absolute; top:100%; left:0; right:0; z-index:100;
            background:#1f2329; border:1px solid #2a2f36; border-radius:6px;
            margin-top:6px; max-height:340px; overflow-y:auto;
            box-shadow:0 8px 24px rgba(0,0,0,.4);
        }
        .autocomplete-results .ac-item {
            display:flex; align-items:center; gap:12px; padding:10px 14px;
            cursor:pointer; color:#cfd2d6; border-bottom:1px solid #2a2f36;
            transition:background .15s;
        }
        .autocomplete-results .ac-item:last-child { border-bottom:none; }
        .autocomplete-results .ac-item:hover,
        .autocomplete-results .ac-item.highlight { background:#262b32; color:#fff; }
        .autocomplete-results .ac-thumb {
            width:36px; height:48px; background:#0f1115; border-radius:3px; flex-shrink:0;
            background-size:cover; background-position:center;
        }
        .autocomplete-results .ac-title { font-size:14px; font-weight:500; }
        .autocomplete-results .ac-meta { font-size:11px; color:#7d828c; }
        .autocomplete-results .ac-empty { padding:14px; color:#7d828c; font-size:13px; text-align:center; }
        .smart-search-form .selected-chip {
            display:inline-block; background:#ff5046; color:#fff;
            padding:2px 10px; border-radius:10px; font-size:11px; margin-left:8px;
        }
        .smart-search-form select:disabled { opacity:.4; cursor:not-allowed; }
    </style>
    @endpush

    @push('scripts')
    <script>
    (function () {
        function debounce(fn, wait) {
            let t; return function (...args) { clearTimeout(t); t = setTimeout(() => fn.apply(this, args), wait); };
        }

        function escapeHtml(str) {
            return String(str).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[c]);
        }

        function rebuildSelect(select, items, valueKey, labelKey, placeholder) {
            if (!select) return;
            const prevValue = select.value;
            select.innerHTML = '';
            const firstOpt = document.createElement('option');
            firstOpt.value = '';
            firstOpt.textContent = placeholder;
            select.appendChild(firstOpt);
            items.forEach(it => {
                const opt = document.createElement('option');
                opt.value = it[valueKey];
                opt.textContent = it[labelKey];
                select.appendChild(opt);
            });
            // Try preserve previous selection if still in list
            if ([...select.options].some(o => o.value === prevValue)) select.value = prevValue;
            // Refresh nice-select if used (the template wraps selects)
            if (window.jQuery && jQuery(select).next('.nice-select').length) {
                jQuery(select).niceSelect && jQuery(select).niceSelect('update');
            }
            select.disabled = items.length === 0;
        }

        document.querySelectorAll('.smart-search-form').forEach(form => {
            const type = form.dataset.type;
            const searchUrl = form.dataset.searchUrl;
            const availabilityUrl = form.dataset.availabilityUrl;
            const detailUrl = form.dataset.detailUrl;
            const input = form.querySelector('.search-input');
            const slugInput = form.querySelector('.selected-slug');
            const dropdown = form.querySelector('.autocomplete-results');
            const citySelect = form.querySelector('select[name="city"]');
            const dateSelect = form.querySelector('select[name="date"]');
            const cinemaSelect = form.querySelector('select[name="cinema"]');
            const categorySelect = form.querySelector('select[name="category"]');
            const submitBtn = form.querySelector('button[type="submit"]');

            // Cache original options so we can restore when search is cleared
            const originals = {
                city: citySelect ? citySelect.innerHTML : '',
                date: dateSelect ? dateSelect.innerHTML : '',
                cinema: cinemaSelect ? cinemaSelect.innerHTML : '',
                category: categorySelect ? categorySelect.innerHTML : '',
            };

            function restoreOriginals() {
                slugInput.value = '';
                if (citySelect) { citySelect.innerHTML = originals.city; citySelect.disabled = false; }
                if (dateSelect) { dateSelect.innerHTML = originals.date; dateSelect.disabled = false; }
                if (cinemaSelect) { cinemaSelect.innerHTML = originals.cinema; cinemaSelect.disabled = false; }
                if (categorySelect) { categorySelect.innerHTML = originals.category; categorySelect.disabled = false; }
                if (window.jQuery) {
                    jQuery(form).find('select').each(function () {
                        if (jQuery(this).next('.nice-select').length) jQuery(this).niceSelect('update');
                    });
                }
            }

            const fetchSearch = debounce(async function (q) {
                if (!q || q.length < 1) { dropdown.style.display = 'none'; return; }
                try {
                    const r = await fetch(`${searchUrl}?q=${encodeURIComponent(q)}`, { headers: { 'Accept': 'application/json' } });
                    const items = await r.json();
                    dropdown.innerHTML = '';
                    if (!items.length) {
                        dropdown.innerHTML = '<div class="ac-empty">No matches found</div>';
                    } else {
                        items.forEach(it => {
                            const div = document.createElement('div');
                            div.className = 'ac-item';
                            const thumbUrl = it.poster_image || it.banner_image || '';
                            const meta = it.event_date ? new Date(it.event_date).toDateString()
                                       : it.sport_date ? new Date(it.sport_date).toDateString()
                                       : it.venue || '';
                            const thumbStyle = thumbUrl
                                ? `style="background-image:url('${thumbUrl.startsWith('assets/') ? '/' + thumbUrl : '/storage/' + thumbUrl}')"`
                                : '';
                            div.innerHTML = `<div class="ac-thumb" ${thumbStyle}></div>
                                <div><div class="ac-title">${escapeHtml(it.title)}</div>
                                <div class="ac-meta">${escapeHtml(meta || '')}</div></div>`;
                            div.addEventListener('mousedown', (e) => {
                                e.preventDefault();
                                selectItem(it);
                            });
                            dropdown.appendChild(div);
                        });
                    }
                    dropdown.style.display = 'block';
                } catch (e) {
                    console.error('search error', e);
                }
            }, 250);

            async function selectItem(item) {
                input.value = item.title;
                slugInput.value = item.slug;
                dropdown.style.display = 'none';
                try {
                    const r = await fetch(`${availabilityUrl}/${item.slug}`, { headers: { 'Accept': 'application/json' } });
                    const av = await r.json();
                    if (citySelect) rebuildSelect(citySelect, av.cities || [], 'id', 'name', av.cities?.length ? 'All available cities' : 'No cities');
                    if (dateSelect) rebuildSelect(dateSelect, av.dates || [], 'value', 'label', av.dates?.length ? 'All available dates' : 'No dates');
                    if (cinemaSelect) rebuildSelect(cinemaSelect, av.cinemas || [], 'id', 'name', av.cinemas?.length ? 'All available cinemas' : 'No cinemas');
                    if (categorySelect) rebuildSelect(categorySelect, av.categories || [], 'slug', 'name', av.categories?.length ? 'All categories' : 'No categories');
                } catch (e) {
                    console.error('availability error', e);
                }
            }

            input.addEventListener('input', e => {
                if (slugInput.value) restoreOriginals();
                fetchSearch(e.target.value.trim());
            });
            input.addEventListener('focus', e => {
                if (e.target.value.trim()) fetchSearch(e.target.value.trim());
            });
            input.addEventListener('blur', () => setTimeout(() => dropdown.style.display = 'none', 150));

            // If a specific entity was selected, redirect to its detail page on submit
            form.addEventListener('submit', function (e) {
                if (slugInput.value) {
                    e.preventDefault();
                    const params = new URLSearchParams();
                    if (citySelect && citySelect.value) params.set('city', citySelect.value);
                    if (dateSelect && dateSelect.value) params.set('date', dateSelect.value);
                    if (cinemaSelect && cinemaSelect.value) params.set('cinema', cinemaSelect.value);
                    if (categorySelect && categorySelect.value) params.set('category', categorySelect.value);
                    const qs = params.toString();
                    window.location.href = `${detailUrl}/${slugInput.value}` + (qs ? `?${qs}` : '');
                }
            });
        });
    })();
    </script>
    @endpush
@endsection

