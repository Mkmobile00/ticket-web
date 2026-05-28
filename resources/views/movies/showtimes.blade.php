@extends('layouts.frontend')

@section('content')
<!-- ==========Window-Warning-Section========== -->
    <section class="window-warning inActive">
        <div class="lay"></div>
        <div class="warning-item">
            <h6 class="subtitle">Welcome! </h6>
            <h4 class="title">Select Your Seats</h4>
            <div class="thumb">
                <img src="{{ asset('assets/images/movie/seat-plan.png') }}" alt="movie">
            </div>
            <a href="movie-seat-plan.html" class="custom-button seatPlanButton">Seat Plans<i class="fas fa-angle-right"></i></a>
        </div>
    </section>
    <!-- ==========Window-Warning-Section========== -->

    @php
        $bannerImg = $movie->banner_image
            ? (str_starts_with($movie->banner_image, 'assets/') ? asset($movie->banner_image) : asset('storage/' . $movie->banner_image))
            : asset('assets/images/banner/banner03.jpg');
    @endphp
    <!-- ==========Banner-Section========== -->
    <section class="details-banner hero-area bg_img" data-background="{{ $bannerImg }}">
        <div class="container">
            <div class="details-banner-wrapper">
                <div class="details-banner-content">
                    <h3 class="title">{{ $movie->title }}</h3>
                    <div class="tags">
                        @foreach ($movie->languages as $lang)
                            <a href="#0">{{ $lang->name }}</a>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Banner-Section========== -->

    <!-- ==========Book-Section========== -->
    <section class="book-section bg-one">
        <div class="container">
            <form class="ticket-search-form two">
                <div class="form-group">
                    <div class="thumb">
                        <img src="{{ asset('assets/images/ticket/city.png') }}" alt="ticket">
                    </div>
                    <span class="type">city</span>
                    <select class="select-bar" onchange="if(this.value){window.location.href=this.value;}">
                        <option value="{{ route('city.clear') }}">All cities</option>
                        @foreach (($allCities ?? []) as $c)
                            <option value="{{ route('city.set', $c->slug) }}" @selected(optional($selectedCity ?? null)->id === $c->id)>{{ $c->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <div class="thumb">
                        <img src="{{ asset('assets/images/ticket/date.png') }}" alt="ticket">
                    </div>
                    <span class="type">date</span>
                    <select class="select-bar">
                        <option value="26-12-19">23/10/2020</option>
                        <option value="26-12-19">24/10/2020</option>
                        <option value="26-12-19">25/10/2020</option>
                        <option value="26-12-19">26/10/2020</option>
                    </select>
                </div>
                <div class="form-group">
                    <div class="thumb">
                        <img src="{{ asset('assets/images/ticket/cinema.png') }}" alt="ticket">
                    </div>
                    <span class="type">cinema</span>
                    <select class="select-bar">
                        <option value="Awaken">Awaken</option>
                        <option value="Venus">Venus</option>
                        <option value="wanted">wanted</option>
                        <option value="joker">joker</option>
                        <option value="fid">fid</option>
                        <option value="kidio">kidio</option>
                        <option value="mottus">mottus</option>
                    </select>
                </div>
                <div class="form-group">
                    <div class="thumb">
                        <img src="{{ asset('assets/images/ticket/exp.png') }}" alt="ticket">
                    </div>
                    <span class="type">Experience</span>
                    <select class="select-bar">
                        <option value="English-2D">English-2D</option>
                        <option value="English-3D">English-3D</option>
                        <option value="Hindi-2D">Hindi-2D</option>
                        <option value="Hindi-3D">Hindi-3D</option>
                        <option value="Telegu-2D">Telegu-2D</option>
                        <option value="Telegu-3D">Telegu-3D</option>
                    </select>
                </div>
            </form>
        </div>
    </section>
    <!-- ==========Book-Section========== -->

    <!-- ==========Movie-Section========== -->
    <div class="ticket-plan-section padding-bottom padding-top">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9 mb-5 mb-lg-0">
                    <ul class="seat-plan-wrapper bg-five">
                        @forelse ($cinemas as $row)
                            <li>
                                <div class="movie-name">
                                    <div class="icons">
                                        <i class="far fa-heart"></i>
                                        <i class="fas fa-heart"></i>
                                    </div>
                                    <a href="#0" class="name">{{ $row['cinema']->name }}</a>
                                    <div class="location-icon">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                </div>
                                <div class="movie-schedule">
                                    @foreach ($row['times']->take(8) as $st)
                                        <a href="{{ route('showtimes.seats', $st->id) }}" class="item" title="{{ $st->show_date->format('D d M') }} · {{ $st->language->name ?? '' }} · {{ $st->format->name ?? '' }}">
                                            {{ \Carbon\Carbon::parse($st->show_time)->format('H:i') }}
                                        </a>
                                    @endforeach
                                </div>
                            </li>
                        @empty
                            <li><div class="p-4 text-center w-100">
                                @if ($selectedCity ?? null)
                                    No upcoming showtimes for <strong>{{ $movie->title }}</strong> in <strong>{{ $selectedCity->name }}</strong>.
                                    <a href="{{ route('city.clear') }}">Show all cities</a>.
                                @else
                                    No upcoming showtimes for this movie.
                                @endif
                            </div></li>
                        @endforelse
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-10">
                    <div class="widget-1 widget-banner">
                        <div class="widget-1-body">
                            <a href="#0">
                                <img src="{{ asset('assets/images/sidebar/banner/banner03.jpg') }}" alt="banner">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ==========Movie-Section========== -->
@endsection

