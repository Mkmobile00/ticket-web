@extends('emails.layout')
@section('heading', 'Booking Cancelled')
@section('sub', 'Booking #' . $booking->id)
@section('body')
    @php
        $seats = $booking->seats->map(fn ($s) => $s->seat_row . $s->seat_number)->implode(', ');
        $subject = $booking->showtime?->movie?->title ?? $booking->bookable?->title ?? 'Booking';
    @endphp
    <p>Hi {{ $booking->user->name ?? 'there' }},</p>
    <p>Your booking has been cancelled and the seats released.</p>
    <table style="width:100%;border-collapse:collapse;font-size:14px;margin:14px 0;">
        <tr><td style="padding:6px 0;color:#666;">Booking</td><td style="padding:6px 0;text-align:right;font-weight:600;">#{{ $booking->id }} — {{ $subject }}</td></tr>
        @if ($seats)<tr><td style="padding:6px 0;color:#666;">Seats</td><td style="padding:6px 0;text-align:right;">{{ $seats }}</td></tr>@endif
        <tr><td style="padding:6px 0;color:#666;">Amount</td><td style="padding:6px 0;text-align:right;font-weight:700;">Rs {{ number_format($booking->total_amount, 2) }}</td></tr>
    </table>
    <p style="font-size:13px;color:#888;">Any eligible refund will be processed to your original payment method within 5–7 business days.</p>
@endsection
