@extends('layouts.frontend')

@php
    $movie = $showtime->movie;
    $cinema = $showtime->screen->cinema ?? null;
    $bannerImg = ($movie?->banner_image)
        ? (str_starts_with($movie->banner_image, 'assets/') ? asset($movie->banner_image) : asset('storage/' . $movie->banner_image))
        : asset('assets/images/banner/banner04.jpg');

    $layout = $showtime->screen->seat_layout ?? null;
    if (is_string($layout)) { $layout = json_decode($layout, true); }
    $rows = $layout['rows'] ?? ['A', 'B', 'C', 'D', 'E', 'F', 'G'];
    $seatsPerRow = $layout['seats_per_row'] ?? array_fill(0, count($rows), 12);
@endphp

@section('content')
<!-- ==========Banner-Section========== -->
    <section class="details-banner hero-area bg_img seat-plan-banner" data-background="{{ $bannerImg }}">
        <div class="container">
            <div class="details-banner-wrapper">
                <div class="details-banner-content style-two">
                    <h3 class="title">{{ $movie->title ?? 'Showtime' }}</h3>
                    <div class="tags">
                        @if ($cinema)<a href="#0">{{ $cinema->name }} — {{ $showtime->screen->name }}</a>@endif
                        <a href="#0">{{ $showtime->language->name ?? '' }} - {{ $showtime->format->name ?? '' }}</a>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Banner-Section========== -->

    <!-- ==========Page-Title========== -->
    <section class="page-title bg-one">
        <div class="container">
            <div class="page-title-area">
                <div class="item md-order-1">
                    <a href="{{ route('movies.showtimes', $movie->slug) }}" class="custom-button back-button">
                        <i class="flaticon-double-right-arrows-angles"></i>back
                    </a>
                </div>
                <div class="item date-item">
                    <span class="date">{{ \Carbon\Carbon::parse($showtime->show_date)->format('D, M d Y') }}</span>
                    <span class="ml-3">{{ \Carbon\Carbon::parse($showtime->show_time)->format('H:i') }}</span>
                </div>
                <div class="item">
                    <h5 class="title">{{ $showtime->available_seats ?? '—' }}</h5>
                    <p>Seats Free</p>
                </div>
            </div>
        </div>
    </section>
    <!-- ==========Page-Title========== -->

    @if ($errors->any())
        <div class="container mt-3"><div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div>
    @endif
    @if (session('status'))
        <div class="container mt-3"><div class="alert alert-success">{{ session('status') }}</div></div>
    @endif

    <!-- ==========Seat-Section========== -->
    <div class="seat-plan-section padding-bottom padding-top">
        <div class="container">
            <form method="POST" action="{{ route('showtimes.seats.store', $showtime->id) }}" id="seat-form"
                  data-showtime="{{ $showtime->id }}"
                  data-status-url="{{ route('api.seats.index', $showtime->id) }}"
                  data-lock-url="{{ route('api.seats.lock') }}">
                @csrf
                <div class="screen-area">
                    <h4 class="screen">screen</h4>
                    <div class="screen-thumb">
                        <img src="{{ asset('assets/images/movie/screen-thumb.png') }}" alt="screen">
                    </div>

                    <div class="seat-legend text-center my-3" style="display:flex;gap:18px;justify-content:center;flex-wrap:wrap;font-size:13px;color:#cfd4db;">
                        <span><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#3a4658;vertical-align:middle;margin-right:6px;"></span>Available</span>
                        <span><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#2f9e6f;vertical-align:middle;margin-right:6px;"></span>Selected</span>
                        <span><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#e7b400;vertical-align:middle;margin-right:6px;"></span>Locked (someone choosing)</span>
                        <span><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#c0392b;vertical-align:middle;margin-right:6px;"></span>Booked</span>
                    </div>

                    <div class="my-4 text-center">
                        <label class="text-white mr-2"><strong>Ticket Class:</strong></label>
                        <select name="ticket_class_id" required class="select-bar" style="display:inline-block;width:auto;min-width:240px">
                            @forelse ($showtime->ticketClasses as $tc)
                                <option value="{{ $tc->id }}" data-price="{{ $tc->price }}">
                                    {{ ucfirst($tc->name) }} — ${{ number_format($tc->price, 2) }}
                                </option>
                            @empty
                                <option value="">No ticket classes available</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="screen-wrapper">
                        <ul class="seat-area">
                            @foreach ($rows as $i => $row)
                                <li class="seat-line">
                                    <span>{{ $row }}</span>
                                    <ul class="seat--area">
                                        <li class="front-seat">
                                            <ul>
                                                @for ($n = 1; $n <= ($seatsPerRow[$i] ?? 12); $n++)
                                                    @php $seatId = $row . '-' . $n; @endphp
                                                    <li class="single-seat seat-free">
                                                        <label style="cursor:pointer;display:block">
                                                            <input type="checkbox" name="seats[]" value="{{ $seatId }}" class="seat-checkbox" data-seat="{{ $seatId }}" style="display:none">
                                                            <img src="{{ asset('assets/images/movie/seat01-free.png') }}" alt="seat" class="seat-img">
                                                            <span class="sit-num">{{ $row }}{{ $n }}</span>
                                                        </label>
                                                    </li>
                                                @endfor
                                            </ul>
                                        </li>
                                    </ul>
                                    <span>{{ $row }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>

                <div class="proceed-book bg_img" data-background="{{ asset('assets/images/movie/movie-bg-proceed.jpg') }}">
                    <div class="proceed-to-book">
                        <div class="book-item">
                            <span>Selected Seats</span>
                            <h3 class="title" id="selected-seats-display">—</h3>
                        </div>
                        <div class="book-item">
                            <span>total price</span>
                            <h3 class="title">$<span id="total-price-display">0.00</span></h3>
                        </div>
                        <div class="book-item">
                            @auth
                                <button type="submit" class="custom-button" id="proceed-btn" disabled>proceed</button>
                            @else
                                <a href="{{ route('login') }}" class="custom-button">login to book</a>
                            @endauth
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- ==========Seat-Section========== -->

    @push('scripts')
    <script>
        (function () {
            const form = document.getElementById('seat-form');
            if (!form) return;
            const MAX_SEATS = 10;
            const statusUrl = form.dataset.statusUrl;
            const checkboxes = [...form.querySelectorAll('.seat-checkbox')];
            const seatById = {};
            checkboxes.forEach(cb => { seatById[cb.value.toUpperCase()] = cb; });
            const display = document.getElementById('selected-seats-display');
            const priceDisplay = document.getElementById('total-price-display');
            const proceedBtn = document.getElementById('proceed-btn');
            const select = form.querySelector('select[name="ticket_class_id"]');

            // Map status -> seat <li> tint.
            const TINT = { booked: '#c0392b', locked: '#e7b400', mine: '#2f9e6f', available: '' };

            function getPrice() {
                const opt = select?.options[select.selectedIndex];
                return parseFloat(opt?.dataset.price || 0) || 0;
            }
            function selectedSeats() {
                return checkboxes.filter(c => c.checked).map(c => c.value);
            }
            function update() {
                const selected = selectedSeats();
                display.textContent = selected.length ? selected.join(', ') : '—';
                priceDisplay.textContent = (selected.length * getPrice()).toFixed(2);
                if (proceedBtn) proceedBtn.disabled = selected.length === 0;
            }

            // Apply live availability from the API: disable booked/locked seats.
            async function refreshStatus() {
                if (!statusUrl) return;
                try {
                    const res = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                    if (!res.ok) return;
                    const data = await res.json();
                    (data.rows || []).forEach(row => row.seats.forEach(seat => {
                        const cb = seatById[seat.id];
                        if (!cb) return;
                        const li = cb.closest('li.single-seat');
                        const taken = seat.status === 'booked' || seat.status === 'locked';
                        // Never override a seat the user is actively selecting.
                        if (cb.checked && seat.status !== 'booked') return;
                        cb.disabled = taken;
                        li.style.pointerEvents = taken ? 'none' : '';
                        li.style.filter = taken ? 'grayscale(1)' : '';
                        li.style.background = TINT[seat.status] || '';
                        li.style.borderRadius = '6px';
                        li.title = seat.id + ' — ' + seat.status;
                        if (seat.status === 'booked' && cb.checked) { cb.checked = false; }
                    }));
                    update();
                } catch (e) { /* network hiccup — keep last state */ }
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', () => {
                    if (cb.checked && selectedSeats().length > MAX_SEATS) {
                        cb.checked = false;
                        alert('You can select a maximum of ' + MAX_SEATS + ' seats.');
                        return;
                    }
                    const li = cb.closest('li.single-seat');
                    li.classList.toggle('selected', cb.checked);
                    li.style.background = cb.checked ? '#2f9e6f' : '';
                    li.style.opacity = cb.checked ? '0.85' : '1';
                    update();
                });
            });
            select?.addEventListener('change', update);

            // Initial paint + poll every 10s (BookMyShow real-time seat map).
            refreshStatus();
            setInterval(refreshStatus, 10000);
        })();
    </script>
    @endpush
@endsection
