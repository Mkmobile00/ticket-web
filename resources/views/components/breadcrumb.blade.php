@props(['title' => '', 'crumbs' => [], 'background' => 'assets/images/banner/banner07.jpg'])
<section class="main-page-header speaker-banner bg_img" data-background="{{ asset($background) }}">
    <div class="container">
        <div class="speaker-banner-content">
            <h2 class="title">{{ $title }}</h2>
            <ul class="breadcrumb">
                <li><a href="{{ route('home') }}">Home</a></li>
                @foreach ($crumbs as $label => $url)
                    @if (is_string($url) && $url)
                        <li><a href="{{ $url }}">{{ $label }}</a></li>
                    @else
                        <li>{{ is_string($label) ? $label : $url }}</li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>
</section>
