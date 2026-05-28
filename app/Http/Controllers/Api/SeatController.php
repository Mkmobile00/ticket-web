<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Showtime;
use App\Models\Sport;
use App\Services\SeatLockService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Polymorphic seat availability + atomic locking API for any "seatable"
 * (Showtime / Event / Sport). One engine, three subjects.
 *
 *   GET    /api/seats/{type}/{id}   -> layout + per-seat status (+ tier/price)
 *   POST   /api/seats/lock          -> atomically lock seats (5-min TTL)
 *   DELETE /api/seats/lock          -> release my locks
 *   POST   /api/seats/extend        -> refresh TTL while on checkout
 *
 * {type} ∈ showtime | event | sport
 */
class SeatController extends Controller
{
    private const MAP = [
        'showtime' => Showtime::class,
        'event' => Event::class,
        'sport' => Sport::class,
    ];

    public function __construct(private SeatLockService $locks) {}

    private function owner(Request $request): string
    {
        return 'sess:' . $request->session()->getId();
    }

    /** Resolve a seatable model from "{type}/{id}", eager-loading what we need. */
    private function resolve(string $type, int|string $id): ?Model
    {
        $class = self::MAP[$type] ?? null;
        if (! $class) {
            return null;
        }
        $with = $type === 'showtime' ? ['screen', 'ticketClasses'] : ['tickets'];
        return $class::with($with)->find($id);
    }

    /** Seats already taken in the DB (pending/confirmed) for a seatable. */
    private function bookedMap(Model $seatable)
    {
        return \App\Models\BookingSeat::where('seatable_type', $seatable->getMorphClass())
            ->where('seatable_id', $seatable->getKey())
            ->whereHas('booking', fn ($q) => $q->whereIn('status', ['pending', 'confirmed', 'completed']))
            ->get(['seat_row', 'seat_number'])
            ->map(fn ($s) => strtoupper($s->seat_row . '-' . $s->seat_number))
            ->flip();
    }

    /** Build [ROW => ['name'=>..,'price'=>..]] lookup from a seatable's tiers. */
    private function rowTierMap(Model $seatable): array
    {
        $map = [];
        foreach ($seatable->seatTiers() as $tier) {
            foreach ($tier['rows'] as $row) {
                $map[strtoupper($row)] = ['name' => $tier['name'], 'price' => $tier['price']];
            }
        }
        return $map;
    }

    /** GET /api/seats/{type}/{id} */
    public function status(Request $request, string $type, int $id)
    {
        $seatable = $this->resolve($type, $id);
        abort_unless($seatable, 404);

        $layout = $seatable->seatLayoutArray();
        $rows = $layout['rows'] ?: ['A', 'B', 'C', 'D', 'E'];
        $perRow = $layout['seats_per_row'] ?: array_fill(0, count($rows), 20);

        $owner = $this->owner($request);
        $booked = $this->bookedMap($seatable);
        $lockMap = $this->locks->lockedSeatMap($seatable->seatContext());
        $tierByRow = $this->rowTierMap($seatable);

        $counts = ['available' => 0, 'locked' => 0, 'booked' => 0];
        $grid = [];
        foreach ($rows as $i => $row) {
            $row = strtoupper($row);
            $tier = $tierByRow[$row] ?? null;
            $seats = [];
            for ($n = 1; $n <= ($perRow[$i] ?? 0); $n++) {
                $sid = $row . '-' . $n;
                if ($booked->has($sid)) {
                    $status = 'booked';
                    $counts['booked']++;
                } elseif (isset($lockMap[$sid])) {
                    $status = $lockMap[$sid] === $owner ? 'mine' : 'locked';
                    $counts['locked']++;
                } else {
                    $status = 'available';
                    $counts['available']++;
                }
                $seats[] = [
                    'id' => $sid,
                    'status' => $status,
                    'tier' => $tier['name'] ?? null,
                    'price' => $tier['price'] ?? null,
                ];
            }
            $grid[] = ['row' => $row, 'tier' => $tier['name'] ?? null, 'seats' => $seats];
        }

        return response()->json([
            'context' => $seatable->seatContext(),
            'rows' => $grid,
            'tiers' => $seatable->seatTiers()->values(),
            'counts' => $counts,
            'lock_ttl' => SeatLockService::TTL,
        ]);
    }

    private function validateContext(Request $request): array
    {
        return $request->validate([
            'context' => 'required|regex:/^(showtime|event|sport):\d+$/',
            'seats' => 'required|array|min:1|max:10',
            'seats.*' => 'string|regex:/^[A-Za-z]{1,2}-\d{1,3}$/',
        ]);
    }

    /** POST /api/seats/lock  Body: { context, seats:[] } */
    public function lock(Request $request)
    {
        $data = $this->validateContext($request);
        $result = $this->locks->lock($data['context'], $data['seats'], $this->owner($request));

        if (! $result['ok']) {
            return response()->json([
                'ok' => false,
                'message' => "Seat {$result['conflict']} was just taken.",
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

    /** DELETE /api/seats/lock */
    public function release(Request $request)
    {
        $data = $this->validateContext($request);
        $this->locks->release($data['context'], $data['seats'], $this->owner($request));
        return response()->json(['ok' => true]);
    }

    /** POST /api/seats/extend */
    public function extend(Request $request)
    {
        $data = $this->validateContext($request);
        $ok = $this->locks->extend($data['context'], $data['seats'], $this->owner($request));
        return response()->json([
            'ok' => $ok,
            'expiresAt' => $ok ? now()->addSeconds(SeatLockService::TTL)->toIso8601String() : null,
        ], $ok ? 200 : 409);
    }
}
