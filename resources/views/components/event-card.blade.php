@props(['event'])
@php
    $date = $event->event_date instanceof \Carbon\Carbon ? $event->event_date : \Carbon\Carbon::parse($event->event_date);
@endphp
<div class="event-grid">
    <div class="movie-thumb c-thumb">
        <a href="{{ route('events.show', $event->slug) }}">
            <img src="{{ image_url($event->banner_image, 'assets/images/event/event01.jpg') }}" alt="{{ $event->title }}">
        </a>
        <div class="event-date">
            <h6 class="date-title">{{ $date->format('d') }}</h6>
            <span>{{ $date->format('M') }}</span>
        </div>
    </div>
    <div class="movie-content bg-one">
        <h5 class="title m-0">
            <a href="{{ route('events.show', $event->slug) }}">{{ $event->title }}</a>
        </h5>
        <div class="movie-rating-percent">
            <span>{{ $event->address }}</span>
        </div>
    </div>
</div>
