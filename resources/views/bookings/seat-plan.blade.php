@extends('layouts.frontend')

@php
    $statusUrl = route('api.seats.status', ['type' => $kind, 'id' => $seatable->getKey()]);
@endphp

@section('content')
    <section class="details-banner hero-area bg_img seat-plan-banner" data-background="{{ $bannerImg }}">
        <div class="container"><div class="details-banner-wrapper"><div class="details-banner-content style-two">
            <h3 class="title">{{ $title }}</h3>
            <div class="tags">
                @if ($subtitle)<a href="#0">{{ $subtitle }}</a>@endif
                <a href="#0">{{ $dateLine }}</a>
            </div>
        </div></div></div>
    </section>

    <section class="page-title bg-one"><div class="container"><div class="page-title-area">
        <div class="item md-order-1"><a href="javascript:history.back()" class="custom-button back-button"><i class="flaticon-double-right-arrows-angles"></i>back</a></div>
        <div class="item date-item"><span class="date">{{ $dateLine }}</span></div>
        <div class="item"><h5 class="title" id="seats-free">—</h5><p>Seats Free</p></div>
    </div></div></section>

    <div class="seat-plan-section padding-bottom padding-top">
        <div class="container">
            @if ($errors->any())
                <div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            @endif

            <form method="POST" action="{{ $storeUrl }}" id="seat-form" data-status-url="{{ $statusUrl }}">
                @csrf
                <div class="screen-area">
                    <h4 class="screen">screen / stage</h4>
                    <div class="screen-thumb"><img src="{{ asset('assets/images/movie/screen-thumb.png') }}" alt="screen"></div>

                    <div class="seat-legend text-center my-3" style="display:flex;gap:18px;justify-content:center;flex-wrap:wrap;font-size:13px;color:#cfd4db;">
                        <span><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#3a4658;vertical-align:middle;margin-right:6px;"></span>Available</span>
                        <span><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#2f9e6f;vertical-align:middle;margin-right:6px;"></span>Selected</span>
                        <span><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#e7b400;vertical-align:middle;margin-right:6px;"></span>Held by others</span>
                        <span><span style="display:inline-block;width:14px;height:14px;border-radius:3px;background:#c0392b;vertical-align:middle;margin-right:6px;"></span>Booked</span>
                    </div>

                    {{-- Tier price legend (filled from the API) --}}
                    <div id="tier-legend" class="text-center mb-3" style="color:#9aa3af;font-size:13px;"></div>

                    {{-- Seat grid is rendered here from the status API --}}
                    <div class="screen-wrapper">
                        <ul class="seat-area" id="seat-area"></ul>
                        <p id="seat-loading" class="text-center text-muted">Loading seats…</p>
                    </div>
                </div>

                <div class="proceed-book bg_img" data-background="{{ asset('assets/images/movie/movie-bg-proceed.jpg') }}">
                    <div class="proceed-to-book">
                        <div class="book-item"><span>Selected Seats</span><h3 class="title" id="selected-seats-display">—</h3></div>
                        <div class="book-item"><span>Hold expires in</span><h3 class="title" id="hold-timer">5:00</h3></div>
                        <div class="book-item"><span>Total Price</span><h3 class="title">Rs <span id="total-price-display">0.00</span></h3></div>
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

    @push('styles')
    <style>
        /* Original seat-icon look: the theme seat image as a mask, filled with the
           status colour, so each seat is a crisp seat shape (not a flat box). */
        /* Let wide seat grids scroll horizontally inside the card instead of breaking the page. */
        .screen-wrapper{overflow-x:auto;-webkit-overflow-scrolling:touch;padding-bottom:8px;}
        #seat-area{list-style:none;padding:0;margin:0 auto;max-width:940px;min-width:max-content;}
        #seat-area .seat-line{display:flex;align-items:center;justify-content:center;gap:14px;margin:6px 0;}
        #seat-area .seat-line > .rl{flex:0 0 24px;width:24px;text-align:center;color:#9aa3af;font-size:13px;font-weight:600;}
        #seat-area .seats{display:flex;flex-wrap:nowrap;gap:7px;justify-content:center;width:auto !important;flex:0 1 auto;}
        #seat-area .single-seat{position:relative;cursor:pointer;width:34px;height:32px;padding:0;}
        #seat-area .single-seat .seat-shape{
            display:block;width:34px;height:32px;
            -webkit-mask:url('{{ asset('assets/images/movie/seat01-free.png') }}') center/contain no-repeat;
                    mask:url('{{ asset('assets/images/movie/seat01-free.png') }}') center/contain no-repeat;
            background:#46597a;transition:background .15s ease, transform .1s ease;
        }
        #seat-area .single-seat .sit-num{position:absolute;top:46%;left:50%;transform:translate(-50%,-50%);font-size:9px;color:#fff;pointer-events:none;white-space:nowrap;}
        #seat-area .single-seat:hover .seat-shape{transform:translateY(-2px);}
        #seat-area .single-seat.is-mine   .seat-shape{background:#2f9e6f;}
        #seat-area .single-seat.is-locked .seat-shape{background:#e7b400;}
        #seat-area .single-seat.is-booked .seat-shape{background:#c0392b;}
        #seat-area .single-seat.is-blocked .seat-shape{background:#9aa3af;opacity:.7;}
        #seat-area .single-seat.is-aisle{background:transparent;cursor:default;}
        #seat-area .single-seat.is-aisle:hover .seat-shape{transform:none;}
        #seat-area .single-seat.taken{cursor:not-allowed;}
        #seat-area .single-seat.taken:hover .seat-shape{transform:none;}
        @media(max-width:600px){ #seat-area .single-seat,#seat-area .single-seat .seat-shape{width:30px;height:28px;} #seat-area .single-seat .sit-num{font-size:8px;} #seat-area .seats{gap:4px;} }
    </style>
    @endpush
    @push('scripts')
    <script>
    (function () {
        const form = document.getElementById('seat-form');
        if (!form) return;
        const statusUrl = form.dataset.statusUrl;
        const area = document.getElementById('seat-area');
        const tierLegend = document.getElementById('tier-legend');
        const display = document.getElementById('selected-seats-display');
        const priceEl = document.getElementById('total-price-display');
        const freeEl = document.getElementById('seats-free');
        const btn = document.getElementById('proceed-btn');
        const MAX = 10;
        const priceBySeat = {};
        const cellById = {};
        const selected = new Set();
        let built = false;

        function paint(cell, status) {
            cell.classList.remove('is-mine', 'is-locked', 'is-booked', 'taken');
            if (status === 'booked') cell.classList.add('is-booked', 'taken');
            else if (status === 'locked') cell.classList.add('is-locked', 'taken');
            else if (status === 'mine') cell.classList.add('is-mine');
        }
        function recalc() {
            let total = 0;
            selected.forEach(id => total += (priceBySeat[id] || 0));
            display.textContent = selected.size ? [...selected].join(', ') : '—';
            priceEl.textContent = total.toFixed(2);
            if (btn) btn.disabled = selected.size === 0;
            form.querySelectorAll('input[name="seats[]"]').forEach(i => i.remove());
            selected.forEach(id => {
                const inp = document.createElement('input');
                inp.type = 'hidden'; inp.name = 'seats[]'; inp.value = id;
                form.appendChild(inp);
            });
        }
        function toggle(id) {
            const cell = cellById[id];
            if (selected.has(id)) { selected.delete(id); cell.classList.remove('is-mine'); }
            else {
                if (selected.size >= MAX) { alert('You can select up to ' + MAX + ' seats.'); return; }
                selected.add(id); cell.classList.add('is-mine');
            }
            recalc();
        }

        function build(data) {
            tierLegend.innerHTML = (data.tiers || []).map(t =>
                `<span style="margin:0 12px;"><strong style="color:#fff;">${t.name}</strong> Rs ${Number(t.price).toFixed(0)}</span>`).join('');
            area.innerHTML = '';
            (data.rows || []).forEach(row => {
                const li = document.createElement('li'); li.className = 'seat-line';
                const lblL = document.createElement('span'); lblL.className = 'rl'; lblL.textContent = row.row;
                const seats = document.createElement('div'); seats.className = 'seats';
                row.seats.forEach(seat => {
                    const type = seat.type || 'seat';
                    if (type !== 'seat') {
                        // Aisle and blocked both render as empty walking space (no box).
                        const sp = document.createElement('li');
                        sp.className = 'single-seat is-aisle';
                        seats.appendChild(sp);
                        return;
                    }
                    priceBySeat[seat.id] = seat.price || 0;
                    const label = seat.id.replace('-', ''); // e.g. "A1"
                    const cell = document.createElement('li');
                    cell.className = 'single-seat';
                    cell.dataset.id = seat.id;
                    cell.title = seat.id + (seat.tier ? ' · ' + seat.tier + ' $' + seat.price : '') + ' — ' + seat.status;
                    cell.innerHTML = '<span class="seat-shape"></span><span class="sit-num">' + label + '</span>';
                    cellById[seat.id] = cell;
                    if (seat.status === 'mine') selected.add(seat.id);
                    paint(cell, seat.status);
                    cell.addEventListener('click', () => { if (!cell.classList.contains('taken')) toggle(seat.id); });
                    seats.appendChild(cell);
                });
                const lblR = document.createElement('span'); lblR.className = 'rl'; lblR.textContent = row.row;
                li.appendChild(lblL); li.appendChild(seats); li.appendChild(lblR);
                area.appendChild(li);
            });
            document.getElementById('seat-loading')?.remove();
            built = true;
            recalc();
        }

        async function refresh() {
            try {
                const res = await fetch(statusUrl, { headers: { 'Accept':'application/json' } });
                if (!res.ok) return;
                const data = await res.json();
                if (freeEl) freeEl.textContent = (data.counts?.available ?? 0);
                if (!built) { build(data); return; }
                (data.rows||[]).forEach(r => r.seats.forEach(seat => {
                    if ((seat.type || 'seat') !== 'seat') return;
                    const cell = cellById[seat.id]; if (!cell) return;
                    if (selected.has(seat.id) && seat.status !== 'booked') { paint(cell, 'mine'); return; }
                    if (seat.status === 'booked') selected.delete(seat.id);
                    paint(cell, seat.status);
                }));
                recalc();
            } catch (e) {}
        }

        // 5-minute visual hold countdown.
        let left = 300; const t = document.getElementById('hold-timer');
        setInterval(() => {
            if (left > 0) left--;
            const m = Math.floor(left/60), s = left%60;
            if (t) t.textContent = m + ':' + (s<10?'0':'') + s;
        }, 1000);

        refresh();
        setInterval(refresh, 10000);
    })();
    </script>
    @endpush
@endsection
