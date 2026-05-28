@props(['post'])
@php
    $publishedAt = $post->published_at ? ($post->published_at instanceof \Carbon\Carbon ? $post->published_at : \Carbon\Carbon::parse($post->published_at)) : $post->created_at;
@endphp
<div class="blog-item">
    <div class="blog-thumb">
        <a href="{{ route('blog.show', $post->slug) }}">
            <img src="{{ image_url($post->thumbnail, 'assets/images/blog/blog01.jpg') }}" alt="{{ $post->title }}">
        </a>
    </div>
    <div class="blog-content">
        <div class="blog-meta">
            <span class="left">
                <i class="fas fa-calendar-alt"></i> {{ $publishedAt?->format('d M Y') }}
            </span>
            <span class="right">
                <i class="far fa-eye"></i> {{ number_format($post->views ?? 0) }}
            </span>
        </div>
        <h5 class="title">
            <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
        </h5>
    </div>
</div>
