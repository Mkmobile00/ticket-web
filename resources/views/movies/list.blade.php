@extends('layouts.frontend')

@section('content')
<!-- ==========Banner-Section========== -->
    <section class="banner-section">
        <div class="banner-bg bg_img bg-fixed" data-background="{{ asset('assets/images/banner/banner02.jpg') }}"></div>
        <div class="container">
            <div class="banner-content">
                <h1 class="title bold">get <span class="color-theme">movie</span> tickets</h1>
                <p>Buy movie tickets in advance, find movie times watch trailer, read movie reviews and much more</p>
            </div>
        </div>
    </section>
    <!-- ==========Banner-Section========== -->

    <!-- ==========Ticket-Search========== -->
    <section class="search-ticket-section padding-top pt-lg-0">
        <div class="container">
            <div class="search-tab bg_img" data-background="{{ asset('assets/images/ticket/ticket-bg01.jpg') }}">
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
                            <div class="tab-item active">
                                <div class="movie-area mb-10">
                                    @forelse ($movies as $movie)
                                        <x-movie-list-card :movie="$movie" />
                                    @empty
                                        <p class="text-center">No movies found.</p>
                                    @endforelse
                                </div>
                            </div>
                            <div class="tab-item">
                                <div class="row mb-10 justify-content-center">
                                    @forelse ($movies as $movie)
                                        <div class="col-sm-6 col-lg-4">
                                            <x-movie-card :movie="$movie" />
                                        </div>
                                    @empty
                                        <div class="col-12 text-center"><p>No movies found.</p></div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
<div class="pagination-area text-center">{{ $movies->onEachSide(1)->links() }}</div>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Movie-Section========== -->
@endsection


