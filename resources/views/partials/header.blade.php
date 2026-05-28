@php
    $current = request()->route()?->getName();
    $is = fn($names) => in_array($current, (array) $names, true) ? 'active' : '';
    $isAny = fn($prefixes) => collect((array) $prefixes)->contains(fn($p) => str_starts_with((string) $current, $p)) ? 'active' : '';
@endphp
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
                    <a href="#0">
                        <i class="flaticon-pin"></i>
                        {{ ($selectedCity ?? null)?->name ?? 'Select City' }}
                        <i class="flaticon-down-arrow" style="font-size:.7em;margin-left:4px;"></i>
                    </a>
                    <ul class="submenu" style="max-height:340px;overflow:auto;">
                        @foreach (($allCities ?? []) as $c)
                            <li>
                                <a href="{{ route('city.set', $c->slug) }}"
                                   style="{{ ($selectedCity ?? null)?->id === $c->id ? 'color:#ff5046;font-weight:600;' : '' }}">
                                    {{ $c->name }}
                                </a>
                            </li>
                        @endforeach
                        @if ($selectedCity ?? null)
                            <li><a href="{{ route('city.clear') }}" style="color:#888;">✕ Clear city</a></li>
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
                    <li class="header-button pr-0">
                        <a href="{{ route('account.dashboard') }}">{{ Str::limit(auth()->user()->name, 12) }}</a>
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
