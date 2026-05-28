@props(['post'])
@php
    $img = image_url($post->thumbnail, 'assets/images/blog/blog01.jpg');
    $publishedAt = $post->published_at ?? $post->created_at;
@endphp
<div class="post-item">
    <div class="post-thumb">
        <a href="{{ route('blog.show', $post->slug) }}">
            <img src="{{ $img }}" alt="{{ $post->title }}">
        </a>
    </div>
    <div class="post-content">
        <div class="post-header">
            <h4 class="title">
                <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
            </h4>
            <div class="meta-post">
                <a href="#0" class="mr-4"><i class="flaticon-conversation"></i>{{ $post->approvedComments?->count() ?? $post->comments_count ?? 0 }} Comments</a>
                <a href="#0"><i class="flaticon-view"></i>{{ number_format($post->views ?? 0) }} View</a>
            </div>
            <p>{{ Str::limit(strip_tags($post->excerpt ?? $post->content ?? ''), 200) }}</p>
        </div>
        <div class="entry-content">
            <div class="left">
                <span class="date">{{ $publishedAt?->format('M d, Y') }} BY </span>
                @if ($post->author)
                    <div class="authors">
                        <div class="thumb">
                            <a href="#0"><img src="{{ asset('assets/images/blog/author.jpg') }}" alt="author"></a>
                        </div>
                        <h6 class="title"><a href="#0">{{ $post->author->name }}</a></h6>
                    </div>
                @endif
            </div>
            <a href="{{ route('blog.show', $post->slug) }}" class="buttons">Read More <i class="flaticon-right"></i></a>
        </div>
    </div>
</div>
