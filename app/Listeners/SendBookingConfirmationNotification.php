<?php

namespace App\Listeners;

use App\Events\BookingConfirmed;
use App\Mail\BookingConfirmedMail;
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
        $booking = $event->booking->loadMissing(['user', 'showtime.movie', 'showtime.screen.cinema', 'seats']);

        // 1) Email (works today via the "log" mailer).
        try {
            if ($booking->user?->email) {
                Mail::to($booking->user->email)->send(new BookingConfirmedMail($booking));
            }
        } catch (\Throwable $e) {
            Log::warning('Booking email failed', ['booking' => $booking->id, 'error' => $e->getMessage()]);
        }

        // 2) SMS stub — replace with Twilio/Sparrow SMS in production.
        $seats = $booking->seats->map(fn ($s) => $s->seat_row . $s->seat_number)->implode(', ');
        Log::info('SMS (stub) booking confirmation', [
            'to' => $booking->user?->phone,
            'text' => "Booking #{$booking->id} confirmed. Seats: {$seats}. Show your QR at entry.",
        ]);
    }
}
