@extends('layouts.frontend')

@php
    $event = $booking->bookable;
    $bannerImg = ($event && $event->banner_image)
        ? (str_starts_with($event->banner_image, 'assets/') ? asset($event->banner_image) : asset('storage/' . $event->banner_image))
        : asset('assets/images/banner/banner07.jpg');
@endphp

@section('content')
    <section class="details-banner event-details-banner hero-area bg_img seat-plan-banner" data-background="{{ $bannerImg }}">
        <div class="container"><div class="details-banner-wrapper"><div class="details-banner-content style-two">
            <h3 class="title">{{ $event->title ?? 'Event' }}</h3>
            <div class="tags"><span>Checkout — Booking #{{ $booking->id }}</span></div>
        </div></div></div>
    </section>

    @include('checkout.partials.payment', ['booking' => $booking, 'subject' => $event, 'kind' => 'event'])
@endsection
