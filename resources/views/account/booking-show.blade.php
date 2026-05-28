@extends('layouts.frontend')
@section('title', 'Booking #' . $booking->id)

@php
    $movie = $booking->bookable;
    $firstSeat = $booking->seats->first();
    $showtime = $firstSeat?->ticketClass?->showtime;
    $cinema = $showtime?->screen?->cinema;
    $vat = round($booking->total_amount * 0.05, 2);
    $payable = $booking->total_amount + $vat;
@endphp

@section('content')
<x-breadcrumb title="Booking #{{ $booking->id }}" :crumbs="['Account' => route('account.dashboard'), 'Bookings' => route('account.bookings.index'), '#' . $booking->id => '']" />

<section class="padding-top padding-bottom" style="min-height:60vh;">
    <div class="container">
        @include('account._nav')

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row">
            <div class="col-lg-8">
                <div style="background:#1c1d28;padding:24px;border-radius:6px;color:#fff;">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <h3 class="m-0">{{ $movie->title ?? 'Booking' }}</h3>
                        @php $cls = match($booking->status){ 'confirmed'=>'success','pending'=>'warning','cancelled'=>'danger','refunded'=>'secondary', default=>'dark' }; @endphp
                        <span class="badge bg-{{ $cls }} fs-6">{{ strtoupper($booking->status) }}</span>
                    </div>
                    <dl class="row mb-0">
                        <dt class="col-sm-4">Booking ID</dt><dd class="col-sm-8">#{{ $booking->id }}</dd>
                        <dt class="col-sm-4">Booked at</dt><dd class="col-sm-8">{{ $booking->booked_at?->format('d M Y, H:i') }}</dd>
                        @if ($cinema)
                            <dt class="col-sm-4">Cinema</dt><dd class="col-sm-8">{{ $cinema->name }} &middot; {{ $cinema->address }}</dd>
                        @endif
                        @if ($showtime)
                            <dt class="col-sm-4">Show</dt><dd class="col-sm-8">{{ \Carbon\Carbon::parse($showtime->show_date)->format('D, d M Y') }} at {{ \Carbon\Carbon::parse($showtime->show_time)->format('H:i') }}</dd>
                            <dt class="col-sm-4">Language / Format</dt><dd class="col-sm-8">{{ $showtime->language->name ?? '' }} / {{ $showtime->format->name ?? '' }}</dd>
                        @endif
                        <dt class="col-sm-4">Seats</dt>
                        <dd class="col-sm-8">
                            @foreach ($booking->seats as $s)
                                <span class="badge bg-secondary me-1">{{ $s->seat_row }}{{ $s->seat_number }} ({{ $s->ticketClass->name ?? '' }})</span>
                            @endforeach
                        </dd>
                        @if ($booking->payment_method)
                            <dt class="col-sm-4">Payment</dt><dd class="col-sm-8">{{ ucfirst($booking->payment_method) }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
            <div class="col-lg-4">
                <div style="background:#1c1d28;padding:24px;border-radius:6px;color:#fff;">
                    <h5>Payment Summary</h5>
                    <hr style="border-color:#2a2d3a;">
                    <div class="d-flex justify-content-between mb-2"><span>Tickets</span><span>Rs {{ number_format($booking->total_amount, 2) }}</span></div>
                    <div class="d-flex justify-content-between mb-2"><span>VAT (5%)</span><span>Rs {{ number_format($vat, 2) }}</span></div>
                    @if ($booking->discount_amount > 0)
                        <div class="d-flex justify-content-between mb-2"><span>Discount</span><span>-Rs {{ number_format($booking->discount_amount, 2) }}</span></div>
                    @endif
                    <hr style="border-color:#2a2d3a;">
                    <div class="d-flex justify-content-between mb-3" style="font-size:1.2rem;font-weight:700;"><span>Total</span><span>Rs {{ number_format($payable - ($booking->discount_amount ?? 0), 2) }}</span></div>

                    @if (! in_array($booking->status, ['cancelled', 'refunded']))
                        <form method="POST" action="{{ route('account.bookings.cancel', $booking->id) }}" onsubmit="return confirm('Cancel this booking? This cannot be undone.')">
                            @csrf
                            <button class="btn btn-danger w-100"><i class="fas fa-times"></i> Cancel Booking</button>
                        </form>
                    @endif
                    <a href="{{ route('account.bookings.index') }}" class="btn btn-outline-light w-100 mt-2">Back to Bookings</a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
