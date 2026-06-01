@extends('admin.layouts.admin')
@section('title', 'Where it plays — ' . $movie->title)
@section('page-title', 'Movie schedule')

@php
    use Illuminate\Support\Carbon;
    $base = route('admin.movies.playing', $movie->id);
    $cinemaUrl   = fn ($c) => $base . '?cinema_id=' . $c;
    $screenUrl   = fn ($s) => $base . '?cinema_id=' . $cinemaId . '&screen_id=' . $s;
    $showtimeUrl = fn ($st) => $base . '?cinema_id=' . $cinemaId . '&screen_id=' . $screenId . '&showtime_id=' . $st;

    // Which level is currently open?
    $level = $showtimeId ? 4 : ($screenId ? 3 : ($cinemaId ? 2 : 1));
    $fmtShow = fn ($s) => Carbon::parse($s->show_date)->format('D, d M Y') . ' · ' . Carbon::parse($s->show_time)->format('h:i A');
@endphp

@section('content')

{{-- Breadcrumb / step trail --}}
<nav class="mb-3">
    <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="{{ route('admin.movies.index') }}">Movies</a></li>
        <li class="breadcrumb-item {{ $level === 1 ? 'active' : '' }}">
            @if ($level === 1) {{ $movie->title }}
            @else <a href="{{ $base }}">{{ $movie->title }}</a> @endif
        </li>
        @if ($cinema)
            <li class="breadcrumb-item {{ $level === 2 ? 'active' : '' }}">
                @if ($level === 2) {{ $cinema->name }}
                @else <a href="{{ $cinemaUrl($cinema->id) }}">{{ $cinema->name }}</a> @endif
            </li>
        @endif
        @if ($screen)
            <li class="breadcrumb-item {{ $level === 3 ? 'active' : '' }}">
                @if ($level === 3) {{ $screen->name }}
                @else <a href="{{ $screenUrl($screen->id) }}">{{ $screen->name }}</a> @endif
            </li>
        @endif
        @if ($showtime)
            <li class="breadcrumb-item active">{{ $fmtShow($showtime) }}</li>
        @endif
    </ol>
</nav>

<div class="card mb-3">
    <div class="card-body d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 class="mb-1">{{ $movie->title }}</h5>
            <div class="text-muted small">
                @switch($level)
                    @case(1) Step 1 of 4 — pick a <strong>cinema</strong> to drill down. @break
                    @case(2) Step 2 of 4 — pick a <strong>screen</strong> in {{ $cinema->name }}. @break
                    @case(3) Step 3 of 4 — pick a <strong>showtime</strong> on {{ $screen->name }}. @break
                    @case(4) Step 4 of 4 — <strong>ticket classes</strong> for this showtime. @break
                @endswitch
            </div>
        </div>
        <a href="{{ route('admin.movies.edit', $movie->id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-pencil"></i> Edit movie
        </a>
    </div>
</div>

{{-- ===== Level 1: Cinemas ===== --}}
@if ($level === 1)
    <h6 class="text-muted text-uppercase small mb-2">Cinemas playing this movie</h6>
    @forelse ($cinemas as $row)
        <a href="{{ $cinemaUrl($row['model']->id) }}" class="card card-body mb-2 text-decoration-none d-flex flex-row justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">{{ $row['model']->name }}</div>
                <div class="text-muted small">{{ $row['model']->city?->name }}{{ $row['model']->address ? ' · ' . $row['model']->address : '' }}</div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary">{{ $row['count'] }} showtime{{ $row['count'] === 1 ? '' : 's' }}</span>
                <i class="bi bi-chevron-right text-muted"></i>
            </div>
        </a>
    @empty
        <div class="alert alert-warning mb-0">This movie has no showtimes scheduled yet.
            <a href="{{ route('admin.showtimes.create') }}">Add a showtime</a>.</div>
    @endforelse
@endif

{{-- ===== Level 2: Screens ===== --}}
@if ($level === 2)
    <h6 class="text-muted text-uppercase small mb-2">Screens at {{ $cinema->name }}</h6>
    @forelse ($screens as $row)
        <a href="{{ $screenUrl($row['model']->id) }}" class="card card-body mb-2 text-decoration-none d-flex flex-row justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">{{ $row['model']->name }}</div>
                <div class="text-muted small">{{ $row['model']->total_seats }} seats</div>
            </div>
            <div class="d-flex align-items-center gap-3">
                <span class="badge bg-primary">{{ $row['count'] }} showtime{{ $row['count'] === 1 ? '' : 's' }}</span>
                <i class="bi bi-chevron-right text-muted"></i>
            </div>
        </a>
    @empty
        <div class="alert alert-warning mb-0">No screens for this movie at {{ $cinema->name }}.</div>
    @endforelse
@endif

{{-- ===== Level 3: Showtimes ===== --}}
@if ($level === 3)
    <h6 class="text-muted text-uppercase small mb-2">Showtimes on {{ $screen->name }} — {{ $cinema->name }}</h6>
    @forelse ($shows as $s)
        <a href="{{ $showtimeUrl($s->id) }}" class="card card-body mb-2 text-decoration-none d-flex flex-row justify-content-between align-items-center">
            <div>
                <div class="fw-semibold">{{ $fmtShow($s) }}</div>
                <div class="text-muted small">
                    {{ $s->language->name ?? '' }}{{ $s->format ? ' · ' . $s->format->name : '' }}
                    · {{ $s->ticketClasses()->count() }} ticket class(es)
                    @if (!is_null($s->available_seats)) · {{ $s->available_seats }} seats free @endif
                </div>
            </div>
            <i class="bi bi-chevron-right text-muted"></i>
        </a>
    @empty
        <div class="alert alert-warning mb-0">No showtimes on this screen for this movie.</div>
    @endforelse
@endif

{{-- ===== Level 4: Ticket classes ===== --}}
@if ($level === 4)
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="text-muted text-uppercase small mb-0">Ticket classes — {{ $fmtShow($showtime) }}</h6>
        <a href="{{ route('admin.showtimes.pricing', $showtime) }}" class="btn btn-sm btn-success">
            <i class="bi bi-currency-dollar"></i> Edit seat prices
        </a>
    </div>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-admin mb-0">
                <thead><tr><th>Tier</th><th>Price</th><th>Seat rows</th></tr></thead>
                <tbody>
                    @forelse ($ticketClasses as $tc)
                        <tr>
                            <td class="fw-semibold">{{ $tc->name }}</td>
                            <td>Rs {{ number_format($tc->price, 2) }}</td>
                            <td>
                                @php $rows = (array) $tc->seat_rows; @endphp
                                @if ($rows)
                                    @foreach ($rows as $r)<span class="badge bg-secondary me-1">{{ $r }}</span>@endforeach
                                @else <span class="text-muted">—</span> @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-4">
                            No ticket classes for this showtime yet.
                            <a href="{{ route('admin.showtimes.pricing', $showtime) }}">Set prices</a>.
                        </td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

@endsection
