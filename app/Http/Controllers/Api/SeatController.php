<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Showtime;
use App\Services\SeatLockService;
use Illuminate\Http\Request;

/**
 * Seat availability + atomic seat-locking API (BookMyShow Phase 2.1 / 2.2),
 * implemented in Laravel.
 *
 *   GET    /api/showtimes/{showtime}/seats   -> layout + per-seat status
 *   POST   /api/bookings/lock                -> atomically lock seats (5-min TTL)
 *   DELETE /api/bookings/lock                -> release my locks
 *   POST   /api/bookings/extend-lock         -> refresh TTL while on checkout
 */
class SeatController extends Controller
{
    public function __construct(private SeatLockService $locks) {}

    /**
     * The lock "owner" token. Tied to the session so a user's own locks are
     * recognised as theirs across requests/tabs in the same browser session.
     */
    private function owner(Request $request): string
    {
        return 'sess:' . $request->session()->getId();
    }

    /** Build [rows[], seats_per_row[]] from a screen's stored seat_layout JSON. */
    private function layout(Showtime $showtime): array
    {
        $layout = $showtime->screen->seat_layout ?? null;
        if (is_string($layout)) {
            $layout = json_decode($layout, true);
        }
        $rows = $layout['rows'] ?? ['A', 'B', 'C', 'D', 'E'];
        $perRow = $layout['seats_per_row'] ?? array_fill(0, count($rows), 20);

        return [$rows, $perRow];
    }

    /**
     * GET /api/showtimes/{showtime}/seats
     *
     * Response:
     * {
     *   "showtime_id": 12,
     *   "rows": [
     *     { "row": "A", "seats": [ {"id":"A-1","status":"available"}, ... ] }, ...
     *   ],
     *   "counts": {"available":78,"locked":2,"booked":20}
     * }
     * status ∈ available | locked | mine | booked
     */
    public function index(Request $request, Showtime $showtime)
    {
        [$rows, $perRow] = $this->layout($showtime);
        $owner = $this->owner($request);

        // Confirmed/pending seats = permanently/temporarily booked in DB.
        $booked = $showtime->bookedSeats()
            ->whereHas('booking', fn ($q) => $q->whereIn('status', ['pending', 'confirmed', 'completed']))
            ->get(['seat_row', 'seat_number'])
            ->map(fn ($s) => strtoupper($s->seat_row . '-' . $s->seat_number))
            ->flip();

        // Active cache locks (someone is mid-checkout).
        $lockMap = $this->locks->lockedSeatMap($showtime->id);

        $counts = ['available' => 0, 'locked' => 0, 'booked' => 0];
        $grid = [];

        foreach ($rows as $i => $row) {
            $seats = [];
            for ($n = 1; $n <= ($perRow[$i] ?? 0); $n++) {
                $id = strtoupper($row . '-' . $n);

                if ($booked->has($id)) {
                    $status = 'booked';
                    $counts['booked']++;
                } elseif (isset($lockMap[$id])) {
                    $status = $lockMap[$id] === $owner ? 'mine' : 'locked';
                    $counts['locked']++;
                } else {
                    $status = 'available';
                    $counts['available']++;
                }

                $seats[] = ['id' => $id, 'status' => $status];
            }
            $grid[] = ['row' => $row, 'seats' => $seats];
        }

        return response()->json([
            'showtime_id' => $showtime->id,
            'rows' => $grid,
            'counts' => $counts,
            'lock_ttl' => SeatLockService::TTL,
        ]);
    }

    /**
     * POST /api/bookings/lock
     * Body: { showtime_id, seats: ["A-1","A-2"] }
     */
    public function lock(Request $request)
    {
        $data = $request->validate([
            'showtime_id' => 'required|integer|exists:showtimes,id',
            'seats' => 'required|array|min:1|max:10',
            'seats.*' => 'string|regex:/^[A-Za-z]{1,2}-\d{1,3}$/',
        ]);

        $showtime = Showtime::findOrFail($data['showtime_id']);
        $owner = $this->owner($request);

        // Reject seats already confirmed/pending in the DB before touching locks.
        $already = $showtime->bookedSeats()
            ->whereHas('booking', fn ($q) => $q->whereIn('status', ['pending', 'confirmed', 'completed']))
            ->get()
            ->map(fn ($s) => strtoupper($s->seat_row . '-' . $s->seat_number))
            ->flip();

        foreach ($data['seats'] as $seat) {
            if ($already->has(strtoupper($seat))) {
                return response()->json([
                    'ok' => false,
                    'message' => "Seat {$seat} is already booked.",
                    'conflict' => strtoupper($seat),
                ], 409);
            }
        }

        $result = $this->locks->lock($showtime->id, $data['seats'], $owner);

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => "Seat {$result['conflict']} was just taken by someone else.",
                'conflict' => $result['conflict'],
            ], 409);
        }

        return response()->json([
            'ok' => true,
            'lockedSeats' => $result['lockedSeats'],
            'expiresAt' => $result['expiresAt'],
            'ttl' => SeatLockService::TTL,
        ]);
    }

    /** DELETE /api/bookings/lock  Body: { showtime_id, seats:[] } */
    public function release(Request $request)
    {
        $data = $request->validate([
            'showtime_id' => 'required|integer',
            'seats' => 'required|array',
            'seats.*' => 'string',
        ]);

        $this->locks->release($data['showtime_id'], $data['seats'], $this->owner($request));

        return response()->json(['ok' => true]);
    }

    /** POST /api/bookings/extend-lock  Body: { showtime_id, seats:[] } */
    public function extend(Request $request)
    {
        $data = $request->validate([
            'showtime_id' => 'required|integer',
            'seats' => 'required|array',
            'seats.*' => 'string',
        ]);

        $ok = $this->locks->extend($data['showtime_id'], $data['seats'], $this->owner($request));

        return response()->json([
            'ok' => $ok,
            'expiresAt' => $ok ? now()->addSeconds(SeatLockService::TTL)->toIso8601String() : null,
        ], $ok ? 200 : 409);
    }
}
