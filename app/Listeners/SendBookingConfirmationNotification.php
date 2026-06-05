<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Mail\BookingConfirmedMail;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Notification fan-out for confirmed bookings (BookMyShow Phase 2.5).
 * Sends the confirmation email and logs an SMS stub. With MAIL_MAILER=log
 * the email is written to storage/logs, so the flow is fully testable now;
 * swap to SendGrid/SES + Twilio by changing config + the sms() body.
 */
class SendBookingConfirmationNotification
{
    public function handle(BookingConfirmed $event): void
    {
        $booking = $event->booking->loadMissing([
            'user', 'showtime.movie', 'showtime.screen.cinema', 'seats', 'items', 'bookable',
        ]);

        // 1) Email (works today via the "log" mailer).
        try {
            if ($booking->user?->email) {
                Mail::to($booking->user->email)->send(new BookingConfirmedMail($booking));
            }
        } catch (\Throwable $e) {
            Log::warning('Booking email failed', ['booking' => $booking->id, 'error' => $e->getMessage()]);
        }

        // 2) SMS stub — replace with Twilio/Sparrow SMS in production.
        $detail = $booking->showtime
            ? 'Seats: ' . $booking->seats->map(fn ($s) => $s->seat_row . $s->seat_number)->implode(', ')
            : $booking->items->map(fn ($i) => $i->quantity . '× ' . $i->label)->implode(', ');
        Log::info('SMS (stub) booking confirmation', [
            'to' => $booking->user?->phone,
            'text' => "Booking #{$booking->id} confirmed. {$detail}. Show your QR at entry.",
        ]);

        // 3) Push notification (FCM) — no-op until a service account is configured.
        try {
            if ($booking->user) {
                $movie = $booking->showtime?->movie;
                $what = $movie?->title ?? ($booking->bookable?->title ?? 'your booking');
                $poster = $this->posterUrl($movie?->poster_image ?? $booking->bookable?->banner_image ?? null);
                app(FcmService::class)->sendToUser(
                    $booking->user,
                    'Booking confirmed 🎟️',
                    "Your tickets for {$what} are confirmed. {$detail}.",
                    ['type' => 'booking', 'booking_id' => $booking->id],
                    $poster,
                );
            }
        } catch (\Throwable $e) {
            Log::warning('Booking push failed', ['booking' => $booking->id, 'error' => $e->getMessage()]);
        }
    }

    /** Resolve a stored image path to an absolute URL FCM/the device can fetch. */
    private function posterUrl(?string $path): ?string
    {
        if (! $path) return null;
        if (str_starts_with($path, 'http')) return $path;
        return str_starts_with($path, 'assets/')
            ? asset($path)
            : asset('storage/' . ltrim($path, '/'));
    }
}
