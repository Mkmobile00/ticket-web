@extends('layouts.frontend')

@php
    $movie = $booking->bookable;
    $firstSeat = $booking->seats->first();
    $showtime = $firstSeat?->ticketClass?->showtime;
    $cinema = $showtime?->screen?->cinema;
    $bannerImg = $movie?->banner_image
        ? (str_starts_with($movie->banner_image, 'assets/') ? asset($movie->banner_image) : asset('storage/' . $movie->banner_image))
        : asset('assets/images/banner/banner04.jpg');
    $vat = round($booking->total_amount * 0.05, 2);
    $payable = $booking->total_amount + $vat;
@endphp

@section('content')
<!-- ==========Banner-Section========== -->
    <section class="details-banner hero-area bg_img seat-plan-banner" data-background="{{ $bannerImg }}">
        <div class="container">
            <div class="details-banner-wrapper">
                <div class="details-banner-content style-two">
                    <h3 class="title">{{ $movie->title ?? 'Booking #' . $booking->id }}</h3>
                    <div class="tags">
                        @if ($cinema)<a href="#0">{{ $cinema->name }}</a>@endif
                        @if ($showtime)<a href="#0">{{ $showtime->language->name ?? '' }} - {{ $showtime->format->name ?? '' }}</a>@endif
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
                    <a href="{{ url()->previous() }}" class="custom-button back-button">
                        <i class="flaticon-double-right-arrows-angles"></i>back
                    </a>
                </div>
                <div class="item date-item">
                    @if ($showtime)
                        <span class="date">{{ \Carbon\Carbon::parse($showtime->show_date)->format('D, M d Y') }}</span>
                        <span class="ml-3">{{ \Carbon\Carbon::parse($showtime->show_time)->format('H:i') }}</span>
                    @endif
                </div>
                <div class="item">
                    <h5 class="title">{{ $booking->seats->count() }}</h5>
                    <p>Tickets</p>
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

    <!-- ==========Checkout-Section========== -->
    <div class="movie-facility padding-bottom padding-top">
        <div class="container">
            <div class="row">
                <div class="col-lg-8">
                    <div class="checkout-widget checkout-contact">
                        <h5 class="title">Booked by</h5>
                        <p class="text-light"><i class="fas fa-user"></i> {{ auth()->user()->name }} &middot; {{ auth()->user()->email }}</p>
                    </div>

                    <div class="checkout-widget checkout-contact">
                        <h5 class="title">Promo Code</h5>
                        <form class="checkout-contact-form" method="POST" action="#">
                            @csrf
                            <div class="form-group">
                                <input type="text" name="promo_code" placeholder="Please enter promo code">
                            </div>
                            <div class="form-group">
                                <input type="submit" value="Verify" class="custom-button" disabled>
                            </div>
                        </form>
                    </div>

                    <div class="checkout-widget checkout-card mb-0">
                        <h5 class="title">Payment Option</h5>
                        <form method="POST" action="{{ route('checkout.confirm', $booking->id) }}" class="payment-card-form">
                            @csrf
                            <ul class="payment-option">
                                <li class="active">
                                    <label style="cursor:pointer">
                                        <input type="radio" name="payment_method" value="esewa" checked style="display:none">
                                        <img src="{{ asset('assets/images/payment/card.png') }}" alt="eSewa">
                                        <span>eSewa <small>(sandbox)</small></span>
                                    </label>
                                </li>
                                <li>
                                    <label style="cursor:pointer">
                                        <input type="radio" name="payment_method" value="khalti" style="display:none">
                                        <img src="{{ asset('assets/images/payment/paypal.png') }}" alt="Khalti">
                                        <span>Khalti</span>
                                    </label>
                                </li>
                                <li>
                                    <label style="cursor:pointer">
                                        <input type="radio" name="payment_method" value="credit_card" style="display:none">
                                        <img src="{{ asset('assets/images/payment/card.png') }}" alt="Card">
                                        <span>Card <small>(test)</small></span>
                                    </label>
                                </li>
                            </ul>
                            <div class="alert" style="background:#eef6ff;border:1px solid #cfe2ff;color:#234;border-radius:8px;padding:12px 14px;font-size:13px;">
                                <strong>Test payments only.</strong>
                                <ul style="margin:8px 0 0;padding-left:18px;">
                                    <li><strong>eSewa</strong> opens the official sandbox — log in with <code>9806800001</code>, password <code>Nepal@123</code>, MPIN <code>1122</code>, OTP <code>123456</code>.</li>
                                    <li><strong>Khalti</strong> uses the live mock unless you add a free test key in <code>.env</code>.</li>
                                    <li><strong>Card (test)</strong> auto-completes instantly for local testing.</li>
                                </ul>
                            </div>
                            <div class="form-group">
                                <input type="submit" class="custom-button" value="confirm booking">
                            </div>
                        </form>
                        <p class="notice">By clicking "Confirm Booking" you agree to the <a href="#0">terms and conditions</a></p>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="booking-summery bg-one">
                        <h4 class="title">booking summary</h4>
                        <ul>
                            <li>
                                <h6 class="subtitle">{{ $movie->title ?? '—' }}</h6>
                                @if ($showtime)
                                    <span class="info">{{ $showtime->language->name ?? '' }} - {{ $showtime->format->name ?? '' }}</span>
                                @endif
                            </li>
                            @if ($cinema && $showtime)
                                <li>
                                    <h6 class="subtitle"><span>{{ $cinema->name }}</span><span>{{ $booking->seats->count() }}</span></h6>
                                    <div class="info"><span>{{ \Carbon\Carbon::parse($showtime->show_date)->format('d M D') }}, {{ \Carbon\Carbon::parse($showtime->show_time)->format('H:i') }}</span> <span>Tickets</span></div>
                                </li>
                            @endif
                            <li>
                                <h6 class="subtitle"><span>Seats</span><span>{{ $booking->seats->pluck('seat_row')->zip($booking->seats->pluck('seat_number'))->map(fn($p) => $p[0] . $p[1])->join(', ') }}</span></h6>
                            </li>
                            <li>
                                <h6 class="subtitle mb-0"><span>Tickets Price</span><span>${{ number_format($booking->total_amount, 2) }}</span></h6>
                            </li>
                        </ul>
                        <ul>
                            <li>
                                <span class="info"><span>Subtotal</span><span>${{ number_format($booking->total_amount, 2) }}</span></span>
                                <span class="info"><span>VAT (5%)</span><span>${{ number_format($vat, 2) }}</span></span>
                            </li>
                        </ul>
                    </div>
                    <div class="proceed-area text-center">
                        <h6 class="subtitle"><span>Amount Payable</span><span>${{ number_format($payable, 2) }}</span></h6>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- ==========Checkout-Section========== -->
@endsection
