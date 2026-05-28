@props(['sport'])
@php
    $date = $sport->sport_date instanceof \Carbon\Carbon ? $sport->sport_date : \Carbon\Carbon::parse($sport->sport_date);
@endphp
<div class="sports-grid">
    <div class="movie-thumb c-thumb">
        <a href="{{ route('sports.show', $sport->slug) }}">
            <img src="{{ image_url($sport->banner_image, 'assets/images/sports/sports01.jpg') }}" alt="{{ $sport->title }}">
        </a>
        <div class="event-date">
            <h6 class="date-title">{{ $date->format('d') }}</h6>
            <span>{{ $date->format('M') }}</span>
        </div>
    </div>
    <div class="movie-content bg-one">
        <h5 class="title m-0">
            <a href="{{ route('sports.show', $sport->slug) }}">{{ $sport->title }}</a>
        </h5>
        <div class="movie-rating-percent">
            <span>{{ $sport->venue }}</span>
        </div>
    </div>
</div>
