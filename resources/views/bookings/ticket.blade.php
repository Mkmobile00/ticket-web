@extends('layouts.frontend')

@php
    $st = $booking->showtime;
    $movie = $st?->movie;
    $cinema = $st?->screen?->cinema;
    $seats = $booking->seats->map(fn ($s) => $s->seat_row . $s->seat_number)->implode(', ');
@endphp

@section('content')
<section class="page-title bg-one"><div class="container"><div class="page-title-area">
    <div class="item md-order-1"><a href="{{ route('account.bookings.index') }}" class="custom-button back-button"><i class="flaticon-double-right-arrows-angles"></i>my bookings</a></div>
    <div class="item"><h5 class="title">E-Ticket</h5><p>Booking #{{ $booking->id }}</p></div>
</div></div></section>

<div class="padding-top padding-bottom">
    <div class="container">
        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div style="background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 10px 40px rgba(0,0,0,.25);color:#1f2329;">
                    <div style="background:linear-gradient(135deg,#ff5046,#ff8a3d);color:#fff;padding:24px 28px;display:flex;justify-content:space-between;align-items:center;">
                        <div>
                            <h3 style="margin:0;font-weight:800;">{{ $movie->title ?? 'Ticket' }}</h3>
                            <span style="opacity:.9;">{{ $cinema->name ?? '' }} {{ $st?->screen?->name ? '— ' . $st->screen->name : '' }}</span>
                        </div>
                        <span style="background:rgba(255,255,255,.2);padding:6px 14px;border-radius:20px;font-weight:700;">CONFIRMED</span>
                    </div>

                    <div style="display:flex;flex-wrap:wrap;gap:24px;padding:28px;">
                        <div style="flex:1 1 280px;">
                            <table style="width:100%;border-collapse:collapse;font-size:15px;">
                                <tr><td style="padding:8px 0;color:#777;">Date</td><td style="padding:8px 0;text-align:right;font-weight:600;">{{ $st ? \Carbon\Carbon::parse($st->show_date)->format('D, M d Y') : '—' }}</td></tr>
                                <tr><td style="padding:8px 0;color:#777;">Time</td><td style="padding:8px 0;text-align:right;font-weight:600;">{{ $st ? \Carbon\Carbon::parse($st->show_time)->format('H:i') : '—' }}</td></tr>
                                <tr><td style="padding:8px 0;color:#777;">Seats</td><td style="padding:8px 0;text-align:right;font-weight:700;color:#ff5046;">{{ $seats }}</td></tr>
                                <tr><td style="padding:8px 0;color:#777;">Tickets</td><td style="padding:8px 0;text-align:right;">{{ $booking->seats->count() }}</td></tr>
                                <tr><td style="padding:8px 0;color:#777;">Paid via</td><td style="padding:8px 0;text-align:right;text-transform:capitalize;">{{ $booking->payment_method }}</td></tr>
                                <tr><td style="padding:8px 0;color:#777;border-top:1px dashed #ddd;">Total</td><td style="padding:8px 0;text-align:right;font-weight:800;border-top:1px dashed #ddd;">${{ number_format($booking->total_amount, 2) }}</td></tr>
                            </table>
                        </div>
                        <div style="flex:0 0 200px;text-align:center;border-left:1px dashed #ddd;padding-left:24px;">
                            <p style="font-size:13px;color:#777;margin-bottom:10px;">Scan at entry</p>
                            <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($booking->qr_code) }}" alt="Ticket QR" width="180" height="180" style="border-radius:10px;border:1px solid #eee;">
                            <p style="font-size:11px;color:#aaa;margin-top:8px;word-break:break-all;">{{ $booking->qr_code }}</p>
                        </div>
                    </div>

                    <div style="background:#f7f8fa;padding:16px 28px;text-align:center;font-size:13px;color:#888;">
                        A copy has been sent to {{ $booking->user->email ?? 'your email' }}.
                        <a href="javascript:window.print()" style="color:#ff5046;margin-left:8px;">Print ticket</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
