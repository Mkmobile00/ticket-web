<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') &middot; {{ config('app.name', 'Boleto') }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.bootstrap5.min.css">
    <style>
        body { background:#f5f6fa; min-height:100vh; }
        .admin-sidebar { width:240px; height:100vh; overflow-y:auto; background:#1f2329; color:#cfd2d6; position:fixed; top:0; left:0; padding:0; scrollbar-width:thin; scrollbar-color:#3a4049 #1f2329; }
        .admin-sidebar::-webkit-scrollbar { width:6px; }
        .admin-sidebar::-webkit-scrollbar-track { background:#1f2329; }
        .admin-sidebar::-webkit-scrollbar-thumb { background:#3a4049; border-radius:3px; }
        .admin-sidebar::-webkit-scrollbar-thumb:hover { background:#4a5159; }
        .admin-sidebar .brand { padding:18px 22px; font-size:1.25rem; font-weight:700; color:#fff; border-bottom:1px solid #2a2f36; }
        .admin-sidebar .nav-link { color:#cfd2d6; padding:9px 22px; border-left:3px solid transparent; }
        .admin-sidebar .nav-link:hover { color:#fff; background:#262b32; }
        .admin-sidebar .nav-link.active { color:#fff; background:#262b32; border-left-color:#ff5046; }
        .admin-sidebar .nav-section { padding:14px 22px 6px; font-size:.72rem; text-transform:uppercase; color:#6f7681; letter-spacing:.05em; }
        .admin-content { margin-left:240px; padding:24px 30px; }
        .admin-topbar { background:#fff; padding:12px 24px; border-bottom:1px solid #e5e7eb; margin:-24px -30px 24px; display:flex; justify-content:space-between; align-items:center; }
        .stat-card { border:none; border-radius:8px; box-shadow:0 1px 3px rgba(0,0,0,.05); }
        .stat-card .num { font-size:1.6rem; font-weight:700; }
        table.table-admin th { background:#f8f9fb; font-size:.8rem; text-transform:uppercase; color:#6c757d; }
        @media (max-width: 768px) {
            .admin-sidebar { width:100%; height:auto; max-height:none; position:relative; overflow-y:visible; }
            .admin-content { margin-left:0; }
        }
        /* Keep searchable dropdowns from collapsing to a tiny width in filter bars. */
        .ts-wrapper { min-width: 160px; }
        .ts-wrapper .ts-control { min-height: 38px; }
    </style>
    @stack('styles')
</head>
<body>
    @php
        $sectionGroups = [
            'Overview' => [
                ['Dashboard', 'admin.dashboard', 'speedometer2'],
            ],
            'Movies' => [
                ['Movies', 'admin.movies.index', 'film'],
                ['Cinemas', 'admin.cinemas.index', 'building'],
                ['Screens', 'admin.screens.index', 'tv'],
                ['Showtimes', 'admin.showtimes.index', 'calendar3'],
                ['Ticket Classes', 'admin.ticket-classes.index', 'tags'],
                ['Languages', 'admin.languages.index', 'translate'],
                ['Formats', 'admin.formats.index', 'aspect-ratio'],
                ['Genres', 'admin.genres.index', 'bookmark-star'],
                ['Cast & Crew', 'admin.cast-members.index', 'people-fill'],
                ['Promo Codes', 'admin.promo-codes.index', 'percent'],
                ['Popcorn', 'admin.popcorn-items.index', 'cup-straw'],
            ],
            'Events' => [
                ['Events', 'admin.events.index', 'megaphone'],
                ['Event Categories', 'admin.event-categories.index', 'collection'],
                ['Speakers', 'admin.speakers.index', 'person-bounding-box'],
            ],
            'Sports' => [
                ['Sports', 'admin.sports.index', 'trophy'],
                ['Sport Categories', 'admin.sport-categories.index', 'list-ul'],
            ],
            'Blog' => [
                ['Blog Posts', 'admin.blog-posts.index', 'journal-text'],
                ['Categories', 'admin.blog-categories.index', 'tag'],
                ['Tags', 'admin.blog-tags.index', 'hash'],
                ['Comments', 'admin.blog-comments.index', 'chat-dots'],
            ],
            'Site' => [
                ['Users', 'admin.users.index', 'people'],
                ['Bookings', 'admin.bookings.index', 'ticket-perforated'],
                ['Cities', 'admin.cities.index', 'geo-alt'],
                ['Banners', 'admin.banners.index', 'image'],
                ['Menus', 'admin.menus.index', 'list-nested'],
                ['Offers', 'admin.offers.index', 'percent'],
                ['FAQs', 'admin.faqs.index', 'question-circle'],
                ['Partners', 'admin.partners.index', 'handshake'],
                ['Contact Messages', 'admin.contact-messages.index', 'envelope'],
                ['Newsletter', 'admin.newsletter.index', 'mailbox'],
                ['Push Notifications', 'admin.notifications.index', 'bell'],
                ['Settings', 'admin.settings.index', 'gear'],
            ],
            'Media' => [
                ['File Manager', 'admin.filemanager', 'folder2-open'],
            ],
        ];
        $current = request()->route()?->getName();
    @endphp
    <aside class="admin-sidebar">
        <div class="brand">
            <i class="bi bi-ticket-perforated-fill"></i> {{ config('app.name', 'Boleto') }}
        </div>
        <nav class="nav flex-column">
            @foreach ($sectionGroups as $groupName => $items)
                <div class="nav-section">{{ $groupName }}</div>
                @foreach ($items as [$label, $route, $icon])
                    <a class="nav-link {{ $current === $route ? 'active' : '' }}" href="{{ route($route) }}">
                        <i class="bi bi-{{ $icon }} me-2"></i>{{ $label }}
                    </a>
                @endforeach
            @endforeach
        </nav>
    </aside>

    <div class="admin-content">
        <div class="admin-topbar">
            <div>
                <strong>@yield('page-title', 'Dashboard')</strong>
                @hasSection('breadcrumb') <span class="text-muted">/ @yield('breadcrumb')</span> @endif
            </div>
            <div class="d-flex align-items-center gap-3">
                <a class="text-decoration-none text-muted" href="{{ route('home') }}" target="_blank"><i class="bi bi-box-arrow-up-right"></i> View site</a>
                <span class="text-muted">{{ auth()->user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" class="m-0">@csrf
                    <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-box-arrow-right"></i> Logout</button>
                </form>
            </div>
        </div>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="m-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        @yield('content')
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
    <script>
        // Make every admin dropdown searchable. Skips the "add new" pickers
        // (which have their own toggle JS) and any <select data-no-ts>.
        document.addEventListener('DOMContentLoaded', function () {
            if (typeof TomSelect === 'undefined') return;
            document.querySelectorAll('select:not(.picker):not([data-no-ts]):not([multiple])').forEach(function (el) {
                if (el.tomselect) return;
                new TomSelect(el, {
                    allowEmptyOption: true,
                    maxOptions: 2000,
                    create: false,
                    placeholder: el.querySelector('option')?.textContent || 'Select…',
                });
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
