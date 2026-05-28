<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Models\Showtime;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Frees seats held by pending bookings that were never paid. The Cache::lock
 * expires after 5 minutes; this gives the user a 15-minute grace window before
 * the pending booking is cancelled, its booking_seats deleted (which releases
 * the unique-seat slot) and the showtime's available_seats restored.
 *
 * Schedule it every minute (see routes/console.php).
 */
class ReleaseAbandonedBookings extends Command
{
    protected $signature = 'bookings:release-abandoned {--minutes=15}';

    protected $description = 'Cancel unpaid pending bookings past the grace window and free their seats.';

    public function handle(): int
    {
        $cutoff = now()->subMinutes((int) $this->option('minutes'));

        $stale = Booking::where('status', 'pending')
            ->whereNotNull('showtime_id')
            ->where('created_at', '<', $cutoff)
            ->with('seats')
            ->get();

        $released = 0;
        foreach ($stale as $booking) {
            DB::transaction(function () use ($booking, &$released) {
                $count = $booking->seats->count();

                if ($booking->showtime_id && $count > 0) {
                    Showtime::where('id', $booking->showtime_id)->increment('available_seats', $count);
                }

                $booking->seats()->delete();        // frees the uniq_seat_per_showtime slot
                $booking->update(['status' => 'cancelled']);
                $released += $count;
            });
        }

        $this->info("Cancelled {$stale->count()} abandoned booking(s), freed {$released} seat(s).");

        return self::SUCCESS;
    }
}
