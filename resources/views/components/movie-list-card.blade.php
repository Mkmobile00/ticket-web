@props(['movie'])
@php
    $img = image_url($movie->poster_image, 'assets/images/movie/movie01.jpg');
    $duration = $movie->duration_minutes ? floor($movie->duration_minutes / 60) . 'hrs ' . ($movie->duration_minutes % 60) . ' min' : '';
@endphp
<div class="movie-list">
    <div class="movie-thumb c-thumb">
        <a href="{{ route('movies.show', $movie->slug) }}" class="w-100 bg_img h-100" data-background="{{ $img }}">
            <img class="d-sm-none" src="{{ $img }}" alt="{{ $movie->title }}">
        </a>
    </div>
    <div class="movie-content bg-one">
        <h5 class="title">
            <a href="{{ route('movies.show', $movie->slug) }}">{{ $movie->title }}</a>
        </h5>
        <p class="duration">{{ $duration }}</p>
        @if ($movie->relationLoaded('genres') && $movie->genres->count())
            <div class="movie-tags">
                @foreach ($movie->genres as $genre)
                    <a href="#0">{{ $genre->name }}</a>
                @endforeach
            </div>
        @endif
        @if ($movie->release_date)
            <div class="release">
                <span>Release Date : </span> <a href="#0"> {{ $movie->release_date->format('F j, Y') }}</a>
            </div>
        @endif
        <ul class="movie-rating-percent">
            <li>
                <div class="thumb">
                    <img src="{{ asset('assets/images/movie/tomato.png') }}" alt="rating">
                </div>
                <span class="content">{{ (int) $movie->rating_tomato }}%</span>
            </li>
            <li>
                <div class="thumb">
                    <img src="{{ asset('assets/images/movie/cake.png') }}" alt="rating">
                </div>
                <span class="content">{{ (int) $movie->rating_audience }}%</span>
            </li>
        </ul>
        <div class="book-area">
            <div class="book-ticket">
                <div class="react-item">
                    <a href="#0">
                        <div class="thumb">
                            <img src="{{ asset('assets/images/icons/heart.png') }}" alt="icons">
                        </div>
                    </a>
                </div>
                <div class="react-item mr-auto">
                    <a href="{{ route('movies.showtimes', $movie->slug) }}">
                        <div class="thumb">
                            <img src="{{ asset('assets/images/icons/book.png') }}" alt="icons">
                        </div>
                        <span>book ticket</span>
                    </a>
                </div>
                @if ($movie->trailer_url)
                    <div class="react-item">
                        <a href="{{ $movie->trailer_url }}" class="popup-video">
                            <div class="thumb">
                                <img src="{{ asset('assets/images/icons/play-button.png') }}" alt="icons">
                            </div>
                            <span>watch trailer</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
