<?php

namespace App\Models\Concerns;

use App\Models\BookingSeat;

/**
 * Shared behaviour for anything that has a seat map: Showtime (movies),
 * Event, and Sport. Each using model also implements:
 *   - seatLayoutArray(): ['rows'=>string[], 'seats_per_row'=>int[]]
 *   - seatTiers(): collection of ['name','price','rows'] price tiers
 */
trait Seatable
{
    /** Lock/context key, e.g. "event:3", "sport:5", "showtime:12". */
    public function seatContext(): string
    {
        return strtolower(class_basename($this)) . ':' . $this->getKey();
    }

    /** All seats booked against this seatable (across bookings). */
    public function bookedSeats()
    {
        return $this->morphMany(BookingSeat::class, 'seatable');
    }
}
