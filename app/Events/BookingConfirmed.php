<?php

namespace App\Events;

use App\Models\Booking;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired once a booking is paid and confirmed — the Laravel equivalent of
 * publishing to the Kafka "booking.confirmed" topic. Listeners fan out
 * notifications (email/SMS) from here.
 */
class BookingConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Booking $booking) {}
}
