@extends('layouts.frontend')

@php
    $sport = $booking->bookable;
    $bannerImg = ($sport && $sport->banner_image)
        ? (str_starts_with($sport->banner_image, 'assets/') ? asset($sport->banner_image) : asset('storage/' . $sport->banner_image))
        : asset('assets/images/banner/banner10.jpg');
    $title = $sport && $sport->team_home && $sport->team_away ? $sport->team_home . ' vs ' . $sport->team_away : ($sport->title ?? 'Match');
@endphp

@section('content')
    <section class="details-banner event-details-banner hero-area bg_img seat-plan-banner style-two" data-background="{{ $bannerImg }}">
        <div class="container"><div class="details-banner-wrapper"><div class="details-banner-content style-two">
            <h3 class="title">{{ $title }}</h3>
            <div class="tags"><span>Checkout — Booking #{{ $booking->id }}</span></div>
        </div></div></div>
    </section>

    @include('checkout.partials.payment', ['booking' => $booking, 'subject' => $sport, 'kind' => 'sport'])
@endsection
