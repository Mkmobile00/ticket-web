@php
    $current = request()->route()?->getName();
    $is = fn($names) => in_array($current, (array) $names, true) ? 'active' : '';
    $isAny = fn($prefixes) => collect((array) $prefixes)->contains(fn($p) => str_starts_with((string) $current, $p)) ? 'active' : '';
@endphp
<style>
    /* ---- City selector ---- */
    .city-selector .city-trigger i.fa-map-marker-alt{ color:#ff5046; margin-right:6px; }
    .city-selector .city-panel{
        width:340px !important; padding:14px !important;
        background:#141a2b !important; border:1px solid #2b3450 !important;
        border-radius:14px !important; box-shadow:0 22px 50px rgba(0,0,0,.5) !important;
        left:0 !important;
    }
    .city-selector .city-panel > li{ padding:0 !important; border:0 !important; }
    .city-selector .city-panel > li::before{ display:none !important; }
    .city-head{
        color:#8b95b5 !important; font-size:11px !important; font-weight:700;
        text-transform:uppercase; letter-spacing:.08em; padding:0 4px 10px !important;
    }
    .city-grid{
        display:grid; grid-template-columns:1fr 1fr; gap:8px;
        max-height:300px; overflow:auto; padding:2px;
    }
    .city-grid::-webkit-scrollbar{ width:5px; }
    .city-grid::-webkit-scrollbar-thumb{ background:#3a4566; border-radius:3px; }
    .city-chip{
        display:flex !important; align-items:center; justify-content:space-between; gap:6px;
        padding:10px 12px !important; border-radius:9px; background:#1e2742 !important;
        color:#cfd6ea !important; font-size:13.5px; text-transform:capitalize;
        border:1px solid transparent; transition:all .15s ease;
    }
    .city-chip:hover{ background:#283457 !important; transform:translateY(-2px); border-color:#3a4566; color:#fff !important; }
    .city-chip.active{
        background:linear-gradient(135deg,#ff5046,#ff8a3d) !important; color:#fff !important;
        font-weight:600; box-shadow:0 6px 16px rgba(255,80,70,.4);
    }
    .city-chip.active i.fa-check{ font-size:11px; }
    .city-clear{
        display:block !important; text-align:center; margin-top:12px !important;
        padding-top:10px !important; border-top:1px solid #2b3450;
        color:#8b95b5 !important; font-size:12.5px;
    }
    .city-clear:hover{ color:#ff7a70 !important; }
    @media(max-width:991px){ .city-selector .city-panel{ width:100% !important; } }
</style>
<!-- ==========Header-Section========== -->
<header class="header-section">
    <div class="container">
        <div class="header-wrapper">
            <div class="logo">
                <a href="{{ route('home') }}">
                    <img src="{{ asset('assets/images/logo/logo.png') }}" alt="logo">
                </a>
            </div>
            <ul class="menu">
                <li class="city-selector">
                    <a href="#0" class="city-trigger" onclick="window.openCityModal&&window.openCityModal();return false;">
                        <i class="fas fa-map-marker-alt"></i>
                        <span>{{ ($selectedCity ?? null)?->name ?? 'Select City' }}</span>
                        <i class="fas fa-angle-down" style="font-size:.75em;margin-left:5px;"></i>
                    </a>
                    <ul class="submenu city-panel">
                        <li class="city-head">Select your city</li>
                        <li class="city-grid-wrap">
                            <span class="city-grid">
                                @foreach (($allCities ?? []) as $c)
                                    <a href="{{ route('city.set', $c->slug) }}"
                                       class="city-chip {{ ($selectedCity ?? null)?->id === $c->id ? 'active' : '' }}">
                                        <span>{{ $c->name }}</span>
                                        @if (($selectedCity ?? null)?->id === $c->id)<i class="fas fa-check"></i>@endif
                                    </a>
                                @endforeach
                            </span>
                        </li>
                        @if ($selectedCity ?? null)
                            <li class="city-clear-wrap"><a href="{{ route('city.clear') }}" class="city-clear"><i class="fas fa-times"></i> Clear selection</a></li>
                        @endif
                    </ul>
                </li>
                <li>
                    <a href="{{ route('home') }}" class="{{ $is('home') }}">Home</a>
                </li>
                <li>
                    <a href="#0" class="{{ $isAny('movies.') }}">movies</a>
                    <ul class="submenu">
                        <li><a href="{{ route('movies.index') }}">Movie Grid</a></li>
                        <li><a href="{{ route('movies.index', ['view' => 'list']) }}">Movie List</a></li>
                        <li><a href="{{ route('popcorn') }}">Movie Food</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#0" class="{{ $isAny(['events.', 'speakers.']) }}">events</a>
                    <ul class="submenu">
                        <li><a href="{{ route('events.index') }}">Events</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#0" class="{{ $isAny('sports.') }}">sports</a>
                    <ul class="submenu">
                        <li><a href="{{ route('sports.index') }}">Sports</a></li>
                    </ul>
                </li>
                <li>
                    <a href="#0" class="{{ $isAny(['about', 'apps', 'login', 'register', 'account']) }}">pages</a>
                    <ul class="submenu">
                        <li><a href="{{ route('about') }}">About Us</a></li>
                        <li><a href="{{ route('apps') }}">Apps Download</a></li>
                        @guest
                            <li><a href="{{ route('login') }}">Sign In</a></li>
                            <li><a href="{{ route('register') }}">Sign Up</a></li>
                        @endguest
                        @auth
                            <li><a href="{{ route('account.dashboard') }}">My Account</a></li>
                            <li><a href="{{ route('account.bookings.index') }}">My Bookings</a></li>
                            <li><a href="{{ route('account.profile.edit') }}">Profile</a></li>
                            @if (auth()->user()->is_admin)
                                <li><a href="{{ url('/admin') }}">Admin Panel</a></li>
                            @endif
                            <li>
                                <a href="#0" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">Sign Out</a>
                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                            </li>
                        @endauth
                    </ul>
                </li>
                <li>
                    <a href="#0" class="{{ $isAny('blog.') }}">blog</a>
                    <ul class="submenu">
                        <li><a href="{{ route('blog.index') }}">Blog</a></li>
                    </ul>
                </li>
                <li>
                    <a href="{{ route('contact') }}" class="{{ $is('contact') }}">contact</a>
                </li>
                @guest
                    <li class="header-button pr-0">
                        <a href="{{ route('register') }}">join us</a>
                    </li>
                @endguest
                @auth
                    <li>
                        <a href="#0" title="{{ auth()->user()->name }}">
                            <i class="fas fa-user-circle"></i>
                            {{ \Illuminate\Support\Str::of(auth()->user()->name)->explode(' ')->first() }}
                            <i class="fas fa-angle-down" style="font-size:.8em;"></i>
                        </a>
                        <ul class="submenu">
                            <li><a href="{{ route('account.dashboard') }}">My Account</a></li>
                            <li><a href="{{ route('account.bookings.index') }}">My Bookings</a></li>
                            <li><a href="{{ route('account.profile.edit') }}">Profile</a></li>
                            @if (auth()->user()->is_admin)
                                <li><a href="{{ url('/admin') }}">Admin Panel</a></li>
                            @endif
                            <li>
                                <a href="#0" onclick="event.preventDefault(); document.getElementById('logout-form-pill').submit();">Sign Out</a>
                                <form id="logout-form-pill" action="{{ route('logout') }}" method="POST" class="d-none">@csrf</form>
                            </li>
                        </ul>
                    </li>
                @endauth
            </ul>
            <div class="header-bar d-lg-none">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </div>
</header>
<!-- ==========Header-Section========== -->
