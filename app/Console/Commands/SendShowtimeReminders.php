<?php

namespace App\Console\Commands;

use App\Models\Booking;
use App\Services\FcmService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

/**
 * Pushes a "your show starts soon" reminder for confirmed movie bookings whose
 * showtime begins within the next --within minutes. Each booking is reminded
 * once (reminded_at), so it's safe to run frequently from the scheduler.
 */
class SendShowtimeReminders extends Command
{
    protected $signature = 'bookings:remind {--within=60 : Minutes ahead to remind}';

    protected $description = 'Send push reminders for upcoming booked showtimes';

    public function handle(FcmService $fcm): int
    {
        $now = now();
        $deadline = $now->copy()->addMinutes((int) $this->option('within'));
        $sent = 0;

        Booking::query()
            ->where('status', 'confirmed')
            ->whereNull('reminded_at')
            ->whereNotNull('showtime_id')
            ->with(['user', 'showtime.movie', 'seats'])
            ->chunkById(200, function ($bookings) use (&$sent, $now, $deadline, $fcm) {
                foreach ($bookings as $booking) {
                    $st = $booking->showtime;
                    if (! $st || ! $booking->user) {
                        continue;
                    }

                    $start = Carbon::parse(
                        Carbon::parse($st->show_date)->format('Y-m-d') . ' ' . $st->show_time
                    );
                    if ($start->lt($now) || $start->gt($deadline)) {
                        continue;
                    }

                    $movie = $st->movie?->title ?? 'your movie';
                    $seats = $booking->seats->map(fn ($s) => $s->seat_row . $s->seat_number)->implode(', ');
                    $mins = max(1, $now->diffInMinutes($start));

                    $fcm->sendToUser(
                        $booking->user,
                        'Showtime reminder ⏰',
                        "{$movie} starts in about {$mins} min." . ($seats ? " Seats {$seats}." : '') . ' See you there!',
                        ['type' => 'reminder', 'booking_id' => $booking->id],
                    );
                    $booking->forceFill(['reminded_at' => now()])->save();
                    $sent++;
                }
            });

        $this->info("Sent {$sent} showtime reminder(s).");
        return self::SUCCESS;
    }
}
