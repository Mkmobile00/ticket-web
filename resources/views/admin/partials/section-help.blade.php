@php
    // Plain-language explainer for each admin section + how the pieces connect.
    $helps = [
        'movie'        => ['🎬', 'Movies',         'The films you show — title, poster, languages, genres. A movie becomes bookable once it has Showtimes.'],
        'cinema'       => ['🏢', 'Cinemas',        'Your venues (theatres), one per location in a city. Each cinema holds one or more Screens.'],
        'screen'       => ['🖥️', 'Screens',        'A hall inside a cinema. Set its seat layout (rows × seats here) — this is exactly the seat map customers see.'],
        'showtime'     => ['🕐', 'Showtimes',      'One movie playing on one screen at a date & time. THIS is what customers actually book.'],
        'ticket-class' => ['🎟️', 'Ticket Classes', 'Price tiers for a single showtime (e.g. Classic ₹190, IMAX ₹600). You pick the showtime and which seat rows get this price.'],
        'event'        => ['🎤', 'Events',         'Conferences, concerts & shows. Add the event, then give it ticket types (price tiers). Customers buy quantities — no seat map.'],
        'sport'        => ['🏟️', 'Sports',         'Matches & games. Add the fixture, then its ticket types (price tiers). Customers buy quantities — no seat map.'],
    ];

    // The dependency chain — each step needs the one before it.
    $flow = [
        ['cinema', '🏢 Cinema'],
        ['screen', '🖥️ Screen'],
        ['showtime', '🕐 Showtime'],
        ['ticket-class', '🎟️ Ticket Class'],
    ];

    $help = $helps[$resource] ?? null;
    $inFlow = collect($flow)->contains(fn ($s) => $s[0] === $resource);
@endphp

@if ($help)
<div class="card mb-3" style="border-left:4px solid #ff5046;">
    <div class="card-body py-3">
        <div style="font-weight:600;font-size:1rem;">{{ $help[0] }} What is &ldquo;{{ $help[1] }}&rdquo;?</div>
        <div class="text-muted mt-1" style="font-size:.9rem;">{{ $help[2] }}</div>

        @if ($inFlow)
            <div class="d-flex flex-wrap align-items-center mt-3" style="gap:6px;font-size:.8rem;">
                <span class="text-muted me-1">How it connects:</span>
                @foreach ($flow as $step)
                    <span class="badge {{ $resource === $step[0] ? 'bg-danger' : 'bg-light text-dark border' }}" style="font-weight:500;">{{ $step[1] }}</span>
                    @if (! $loop->last)<i class="bi bi-arrow-right text-muted"></i>@endif
                @endforeach
                <span class="text-muted ms-2">(🎬 Movie is attached at the Showtime step)</span>
            </div>
        @endif
    </div>
</div>
@endif
