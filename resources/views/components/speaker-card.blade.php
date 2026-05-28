@props(['speaker'])
<div class="speaker-item">
    <div class="speaker-thumb">
        <a href="{{ route('speakers.show', $speaker->slug ?? $speaker->id) }}">
            <img src="{{ image_url($speaker->photo, 'assets/images/speaker/speaker01.jpg') }}" alt="{{ $speaker->name }}">
        </a>
        <ul class="social-icons">
            @if ($speaker->facebook_url)<li><a href="{{ $speaker->facebook_url }}"><i class="fab fa-facebook-f"></i></a></li>@endif
            @if ($speaker->twitter_url)<li><a href="{{ $speaker->twitter_url }}"><i class="fab fa-twitter"></i></a></li>@endif
            @if ($speaker->linkedin_url)<li><a href="{{ $speaker->linkedin_url }}"><i class="fab fa-linkedin-in"></i></a></li>@endif
        </ul>
    </div>
    <div class="speaker-content">
        <h5 class="title">
            <a href="{{ route('speakers.show', $speaker->slug ?? $speaker->id) }}">{{ $speaker->name }}</a>
        </h5>
        <span class="info">{{ $speaker->designation }}</span>
    </div>
</div>
