@php
    $st = $booking->showtime;
    $movie = $st?->movie;
    $cinema = $st?->screen?->cinema;
    $seats = $booking->seats->map(fn ($s) => $s->seat_row . $s->seat_number)->implode(', ');
@endphp
<!DOCTYPE html>
<html>
<head><meta charset="utf-8"><title>Booking Confirmed</title></head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f4f5f7;margin:0;padding:24px;color:#222;">
    <div style="max-width:560px;margin:0 auto;background:#fff;border-radius:10px;overflow:hidden;border:1px solid #e5e7eb;">
        <div style="background:linear-gradient(135deg,#ff5046,#ff8a3d);padding:22px 28px;color:#fff;">
            <h2 style="margin:0;font-size:20px;">🎟️ Booking Confirmed</h2>
            <p style="margin:6px 0 0;opacity:.9;">Booking #{{ $booking->id }}</p>
        </div>
        <div style="padding:24px 28px;">
            <p>Hi {{ $booking->user->name ?? 'there' }}, your tickets are confirmed.</p>
            <table style="width:100%;border-collapse:collapse;font-size:14px;">
                <tr><td style="padding:6px 0;color:#666;">Movie</td><td style="padding:6px 0;text-align:right;font-weight:600;">{{ $movie->title ?? '—' }}</td></tr>
                <tr><td style="padding:6px 0;color:#666;">Cinema</td><td style="padding:6px 0;text-align:right;">{{ $cinema->name ?? '—' }} {{ $st?->screen?->name ? '— ' . $st->screen->name : '' }}</td></tr>
                <tr><td style="padding:6px 0;color:#666;">Date &amp; Time</td><td style="padding:6px 0;text-align:right;">{{ $st ? \Carbon\Carbon::parse($st->show_date)->format('D, M d Y') . ' ' . \Carbon\Carbon::parse($st->show_time)->format('H:i') : '—' }}</td></tr>
                <tr><td style="padding:6px 0;color:#666;">Seats</td><td style="padding:6px 0;text-align:right;font-weight:600;">{{ $seats }}</td></tr>
                <tr><td style="padding:6px 0;color:#666;">Total Paid</td><td style="padding:6px 0;text-align:right;font-weight:700;">${{ number_format($booking->total_amount, 2) }}</td></tr>
            </table>
            <div style="margin:22px 0;text-align:center;">
                <p style="font-size:13px;color:#666;margin-bottom:8px;">Show this QR code at entry:</p>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($booking->qr_code) }}" alt="Ticket QR" width="180" height="180" style="border:1px solid #eee;border-radius:8px;">
                <p style="font-size:11px;color:#999;margin-top:6px;">{{ $booking->qr_code }}</p>
            </div>
            <p style="font-size:12px;color:#888;">Thank you for booking with {{ config('app.name', 'Boleto') }}.</p>
        </div>
    </div>
</body>
</html>
