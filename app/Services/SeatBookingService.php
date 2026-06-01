<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Showtime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * One reservation pipeline for every seat map (Showtime / Event / Sport):
 *   1. derive each seat's price tier from its row
 *   2. atomically lock the seats (5-min hold) — all-or-rollback
 *   3. persist a pending booking + booking_seats inside a transaction; the
 *      unique (seatable, row, number) index is the final double-booking guard
 *   4. (movies) decrement the showtime's available_seats counter
 */
class SeatBookingService
{
    public function __construct(private SeatLockService $locks) {}

    /**
     * @param  string[]  $seatIds  e.g. ['A-1','A-2']
     * @throws ValidationException
     */
    public function reserve(Model $seatable, array $seatIds, int $userId, string $owner): Booking
    {
        $seatIds = array_values(array_unique(array_map('strtoupper', $seatIds)));

        // Row -> tier (name/price) lookup from the seatable's price tiers.
        $rowTier = [];
        foreach ($seatable->seatTiers() as $tier) {
            foreach ($tier['rows'] as $row) {
                $rowTier[strtoupper($row)] = $tier;
            }
        }

        // Validate every chosen seat maps to a known priced row.
        foreach ($seatIds as $seat) {
            $row = strtoupper(explode('-', $seat)[0]);
            if (! isset($rowTier[$row])) {
                throw ValidationException::withMessages(['seats' => "Seat {$seat} is not available for selection."]);
            }
        }

        // Reject seats that aren't real bookable positions (aisle / blocked / out of range).
        $bookable = method_exists($seatable, 'bookableSeatIds') ? $seatable->bookableSeatIds() : [];
        if (! empty($bookable)) {
            foreach ($seatIds as $seat) {
                if (! isset($bookable[$seat])) {
                    throw ValidationException::withMessages(['seats' => "Seat {$seat} is not available for selection."]);
                }
            }
        }

        $context = $seatable->seatContext();

        // 1) Atomic lock — all or nothing.
        $lock = $this->locks->lock($context, $seatIds, $owner);
        if (! $lock['ok']) {
            throw ValidationException::withMessages(['seats' => "Seat {$lock['conflict']} was just taken. Please pick another."]);
        }

        $isShowtime = $seatable instanceof Showtime;
        $bookable = $isShowtime ? $seatable->movie : $seatable;

        try {
            return DB::transaction(function () use ($seatable, $seatIds, $userId, $rowTier, $isShowtime, $bookable) {
                $total = 0;
                foreach ($seatIds as $seat) {
                    $total += $rowTier[strtoupper(explode('-', $seat)[0])]['price'];
                }
                // Add VAT so total_amount is the actual (VAT-inclusive) charge.
                $total = round($total * (1 + (float) config('app.vat_rate')), 2);

                $booking = Booking::create([
                    'user_id' => $userId,
                    'bookable_type' => $bookable->getMorphClass(),
                    'bookable_id' => $bookable->getKey(),
                    'showtime_id' => $isShowtime ? $seatable->getKey() : null,
                    'total_amount' => $total,
                    'status' => 'pending',
                    'booked_at' => now(),
                ]);

                foreach ($seatIds as $seat) {
                    [$row, $num] = explode('-', $seat);
                    $tier = $rowTier[strtoupper($row)];
                    $booking->seats()->create([
                        'seatable_type' => $seatable->getMorphClass(),
                        'seatable_id' => $seatable->getKey(),
                        'showtime_id' => $isShowtime ? $seatable->getKey() : null,
                        'ticket_class_id' => $isShowtime ? $tier['id'] : null,
                        'seat_row' => strtoupper($row),
                        'seat_number' => (int) $num,
                        'price' => $tier['price'],
                        'tier_label' => $tier['name'],
                    ]);
                }

                if ($isShowtime) {
                    Showtime::where('id', $seatable->getKey())
                        ->where('available_seats', '>=', count($seatIds))
                        ->decrement('available_seats', count($seatIds));
                }

                return $booking;
            });
        } catch (QueryException $e) {
            $this->locks->release($context, $seatIds, $owner);
            if ((int) ($e->errorInfo[1] ?? 0) === 1062) {
                throw ValidationException::withMessages(['seats' => 'One of those seats was just booked. Please choose again.']);
            }
            throw $e;
        }
    }
}
