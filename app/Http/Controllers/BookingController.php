<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Showtime;
use App\Services\SeatLockService;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(private SeatLockService $locks) {}

    public function seats(Showtime $showtime)
    {
        $showtime->load(['movie', 'screen.cinema', 'ticketClasses', 'language', 'format']);
        return view('bookings.seats', compact('showtime'));
    }

    /**
     * Reserve seats for a showtime. This now enforces the BookMyShow "key rule":
     *   1. Atomically lock the chosen seats (5-min TTL) so concurrent users can't
     *      grab the same seat during checkout.
     *   2. Persist a pending booking + booking_seats inside a DB transaction. The
     *      unique (showtime_id, seat_row, seat_number) index is the final guard —
     *      if a seat slipped through, the insert fails and we roll everything back.
     *   3. Decrement the showtime's available_seats counter.
     * The locks are released later on confirmation (or by the cleanup command if
     * the user abandons checkout).
     */
    public function storeSeats(Request $request, Showtime $showtime)
    {
        $data = $request->validate([
            'seats' => 'required|array|min:1|max:10',
            'seats.*' => 'string|regex:/^[A-Za-z]{1,2}-\d{1,3}$/',
            'ticket_class_id' => 'required|exists:ticket_classes,id',
        ]);

        $ticketClass = $showtime->ticketClasses()->findOrFail($data['ticket_class_id']);
        $owner = 'sess:' . $request->session()->getId();
        $seatIds = array_map('strtoupper', $data['seats']);

        // 1) Atomic lock — all-or-nothing.
        $lock = $this->locks->lock($showtime->id, $seatIds, $owner);
        if (! $lock['ok']) {
            throw ValidationException::withMessages([
                'seats' => "Seat {$lock['conflict']} was just taken. Please pick another.",
            ]);
        }

        try {
            $booking = DB::transaction(function () use ($showtime, $data, $ticketClass, $seatIds) {
                $booking = Booking::create([
                    'user_id' => auth()->id(),
                    'bookable_type' => $showtime->movie->getMorphClass(),
                    'bookable_id' => $showtime->movie_id,
                    'showtime_id' => $showtime->id,
                    'total_amount' => $ticketClass->price * count($seatIds),
                    'status' => 'pending',
                    'booked_at' => now(),
                ]);

                foreach ($seatIds as $seat) {
                    [$row, $num] = explode('-', $seat);
                    $booking->seats()->create([
                        'showtime_id' => $showtime->id,
                        'seat_row' => $row,
                        'seat_number' => (int) $num,
                        'ticket_class_id' => $ticketClass->id,
                    ]);
                }

                // Keep the live availability counter honest.
                Showtime::where('id', $showtime->id)
                    ->where('available_seats', '>=', count($seatIds))
                    ->decrement('available_seats', count($seatIds));

                return $booking;
            });
        } catch (QueryException $e) {
            // Unique violation on (showtime_id, seat_row, seat_number) -> double-book race lost.
            $this->locks->release($showtime->id, $seatIds, $owner);

            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw ValidationException::withMessages([
                    'seats' => 'One of those seats was just booked by someone else. Please choose again.',
                ]);
            }
            throw $e;
        }

        return redirect()->route('checkout.movie', $booking);
    }
}
