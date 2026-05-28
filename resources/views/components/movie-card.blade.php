@props(['movie'])
<div class="movie-grid">
    <div class="movie-thumb c-thumb">
        <a href="{{ route('movies.show', $movie->slug) }}">
            <img src="{{ image_url($movie->poster_image, 'assets/images/movie/movie01.jpg') }}" alt="{{ $movie->title }}">
        </a>
    </div>
    <div class="movie-content bg-one">
        <h5 class="title m-0">
            <a href="{{ route('movies.show', $movie->slug) }}">{{ $movie->title }}</a>
        </h5>
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
    </div>
</div>
