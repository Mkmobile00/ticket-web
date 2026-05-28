<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Showtime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Frees inventory held by pending bookings that were never paid, after a grace
 * window (default 15 min — longer than the 5-min seat lock):
 *   - Movie bookings: delete booking_seats (frees the unique-seat slot) and
 *     restore the showtime's available_seats.
 *   - Event/Sport bookings: decrement each ticket type's quantity_sold by the
 *     amount this booking was holding.
 *
 * Scheduled every minute (see routes/console.php).
 */
class ReleaseAbandonedBookings extends Command
{
    protected $signature = 'bookings:release-abandoned {--minutes=15}';

    protected $description = 'Cancel unpaid pending bookings past the grace window and free their seats/ticket inventory.';

    public function handle(): int
    {
        $cutoff = now()->subMinutes((int) $this->option('minutes'));

        $stale = Booking::where('status', 'pending')
            ->where('created_at', '<', $cutoff)
            ->with(['seats', 'items.ticketable'])
            ->get();

        $seatsFreed = 0;

        foreach ($stale as $booking) {
            DB::transaction(function () use ($booking, &$seatsFreed) {
                // Seat-based (movies, events, sports): delete seats — frees the
                // uniq_seat_per_seatable slot — and restore the showtime counter.
                $count = $booking->seats->count();
                if ($count > 0) {
                    if ($booking->showtime_id) {
                        Showtime::where('id', $booking->showtime_id)->increment('available_seats', $count);
                    }
                    $booking->seats()->delete();
                    $seatsFreed += $count;
                }

                // Legacy quantity-based items, if any.
                foreach ($booking->items as $item) {
                    $item->ticketable?->decrement('quantity_sold', $item->quantity);
                }
                $booking->items()->delete();

                $booking->update(['status' => 'cancelled']);
            });
        }

        $this->info("Cancelled {$stale->count()} abandoned booking(s); freed {$seatsFreed} seat(s).");

        return self::SUCCESS;
    }
}
