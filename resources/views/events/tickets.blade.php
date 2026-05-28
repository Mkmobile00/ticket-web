@extends('layouts.frontend')

@php
    $bannerImg = $event->banner_image
        ? (str_starts_with($event->banner_image, 'assets/') ? asset($event->banner_image) : asset('storage/' . $event->banner_image))
        : asset('assets/images/banner/banner07.jpg');
@endphp

@section('content')
    <section class="details-banner event-details-banner hero-area bg_img seat-plan-banner" data-background="{{ $bannerImg }}">
        <div class="container">
            <div class="details-banner-wrapper">
                <div class="details-banner-content style-two">
                    <h3 class="title">{{ $event->title }}</h3>
                    <div class="tags"><span>{{ $event->address ?? $event->organizer }}</span></div>
                </div>
            </div>
        </div>
    </section>

    <section class="page-title bg-one"><div class="container"><div class="page-title-area">
        <div class="item md-order-1">
            <a href="{{ route('events.show', $event->slug) }}" class="custom-button back-button"><i class="flaticon-double-right-arrows-angles"></i>back</a>
        </div>
        <div class="item date-item">
            <span class="date">{{ \Carbon\Carbon::parse($event->event_date)->format('D, M d Y') }}</span>
            @if ($event->start_time)<span class="ml-3">{{ \Carbon\Carbon::parse($event->start_time)->format('H:i') }}</span>@endif
        </div>
    </div></div></section>

    <div class="event-facility padding-bottom padding-top">
        <div class="container">
            <div class="section-header-3">
                <span class="cate">choose your tickets</span>
                <h2 class="title">{{ $event->title }}</h2>
            </div>

            @if ($errors->any())
                <div class="alert alert-danger">@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div>
            @endif

            <form method="POST" action="{{ route('events.tickets.store', $event->slug) }}" id="ticket-form">
                @csrf
                <div class="row justify-content-center mb-30-none">
                    @forelse ($event->tickets as $ticket)
                        @php $available = $ticket->quantity_total - $ticket->quantity_sold; @endphp
                        <div class="col-md-6 col-lg-4 col-sm-10">
                            <div class="ticket--item" style="padding-bottom:24px;">
                                <div class="ticket-content">
                                    <span class="ticket-title">{{ $ticket->type }}</span>
                                    <h2 class="amount" data-price="{{ $ticket->price }}"><sup>$</sup>{{ number_format($ticket->price, 0) }}</h2>
                                    <ul>
                                        <li>{{ $available > 0 ? $available . ' tickets available' : 'Sold out' }}</li>
                                    </ul>
                                    <div class="d-flex align-items-center justify-content-center" style="gap:10px;margin-top:10px;">
                                        <label class="text-white m-0">Qty</label>
                                        <input type="number" name="qty[{{ $ticket->id }}]" class="qty-input"
                                               min="0" max="{{ min($available, 20) }}" value="0"
                                               {{ $available < 1 ? 'disabled' : '' }}
                                               style="width:80px;text-align:center;border-radius:6px;padding:6px;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-white">No tickets available for this event yet.</div>
                    @endforelse
                </div>

                @if ($event->tickets->count())
                    <div class="text-center mt-4" style="color:#fff;">
                        <h4>Total: $<span id="ticket-total">0.00</span></h4>
                        @auth
                            <button type="submit" class="custom-button mt-2" id="proceed-btn" disabled>Proceed to Checkout</button>
                        @else
                            <a href="{{ route('login') }}" class="custom-button mt-2">Login to Book</a>
                        @endauth
                    </div>
                @endif
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        (function () {
            const form = document.getElementById('ticket-form');
            if (!form) return;
            const inputs = [...form.querySelectorAll('.qty-input')];
            const totalEl = document.getElementById('ticket-total');
            const btn = document.getElementById('proceed-btn');
            function recalc() {
                let total = 0, count = 0;
                inputs.forEach(i => {
                    const price = parseFloat(i.closest('.ticket-content').querySelector('.amount').dataset.price) || 0;
                    const q = parseInt(i.value, 10) || 0;
                    total += price * q; count += q;
                });
                totalEl.textContent = total.toFixed(2);
                if (btn) btn.disabled = count === 0;
            }
            inputs.forEach(i => i.addEventListener('input', recalc));
            recalc();
        })();
    </script>
    @endpush
@endsection
