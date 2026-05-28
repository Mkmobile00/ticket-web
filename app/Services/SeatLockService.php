<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Atomic seat locking — the Laravel-native equivalent of the BookMyShow
 * "Redis SET NX EX 300" rule.
 *
 * We use Laravel's atomic locks (Cache::lock), which are backed by the
 * `cache_locks` table when CACHE_STORE=database (and would transparently use
 * Redis if the cache store were Redis). Each lock is an all-or-nothing claim on
 * a set of seats for one showtime, held for a 5-minute TTL and owned by the
 * acquiring session/user so only they can release or confirm it.
 *
 * Lock key pattern:  seat:{showtimeId}:{ROW}-{NUMBER}
 *
 * Example:
 *   $svc->lock(12, ['A-1','A-2'], 'sess_abc');
 *   // => ['ok'=>true,'expiresAt'=>'2026-05-19T12:05:00Z','lockedSeats'=>['A-1','A-2']]
 */
class SeatLockService
{
    /** Lock lifetime in seconds (5 minutes — matches the guide's TTL). */
    public const TTL = 300;

    /** Build the canonical cache key for one seat of one showtime. */
    public function key(int $showtimeId, string $seatId): string
    {
        return "seat:{$showtimeId}:" . strtoupper($seatId);
    }

    /**
     * Atomically lock every seat in $seatIds for $showtimeId, owned by $owner.
     * All-or-rollback: if any seat is already held by someone else, every lock
     * acquired in this call is released and the method reports failure.
     *
     * @param  string[]  $seatIds  e.g. ['A-1','A-2'] (ROW-NUMBER)
     * @return array{ok:bool, lockedSeats?:array, conflict?:string, expiresAt?:string}
     */
    public function lock(int $showtimeId, array $seatIds, string $owner): array
    {
        $seatIds = array_values(array_unique(array_map('strtoupper', $seatIds)));
        $acquired = [];

        foreach ($seatIds as $seatId) {
            $lock = Cache::lock($this->key($showtimeId, $seatId), self::TTL, $owner);

            if ($lock->get()) {
                $acquired[] = $seatId;
                continue;
            }

            // get() can return false for a seat we ALREADY own: re-acquiring in the
            // same second is a no-op UPDATE (0 affected rows in MySQL). Treat a lock
            // already held by this same owner as successfully held (idempotent).
            if ($this->currentOwnerOf($showtimeId, $seatId) === $owner) {
                $acquired[] = $seatId;
                continue;
            }

            // Conflict: someone else holds this seat. Roll back everything.
            $this->release($showtimeId, $acquired, $owner);

            return ['ok' => false, 'conflict' => $seatId];
        }

        return [
            'ok' => true,
            'lockedSeats' => $acquired,
            'expiresAt' => now()->addSeconds(self::TTL)->toIso8601String(),
        ];
    }

    /**
     * Release the given seats, but only if owned by $owner (safe restore via
     * the stored owner token — you cannot release someone else's lock).
     *
     * @param  string[]  $seatIds
     */
    public function release(int $showtimeId, array $seatIds, string $owner): void
    {
        foreach ($seatIds as $seatId) {
            // Restoring with the owner token lets us call release() without
            // having the original lock object instance.
            Cache::restoreLock($this->key($showtimeId, strtoupper($seatId)), $owner)->release();
        }
    }

    /**
     * Re-acquire (extend) locks for another full TTL — used while the user is
     * still on the checkout page. Returns false if any seat was lost to expiry
     * and could not be re-grabbed (someone else took it).
     *
     * @param  string[]  $seatIds
     */
    public function extend(int $showtimeId, array $seatIds, string $owner): bool
    {
        $result = $this->lock($showtimeId, $seatIds, $owner);
        return $result['ok'];
    }

    /** True if the seat currently has an active (unexpired) lock held by anyone. */
    public function isLocked(int $showtimeId, string $seatId): bool
    {
        return ! $this->probeFree($showtimeId, strtoupper($seatId));
    }

    /**
     * Return the set of seat IDs currently locked for a showtime by scanning
     * the cache_locks table directly (DB cache store). Falls back to an empty
     * set on non-database stores; callers should treat absence as "available".
     *
     * @return array<string,bool>  map of SEATID => owner-token (for "is it mine")
     */
    public function lockedSeatMap(int $showtimeId): array
    {
        $prefix = "seat:{$showtimeId}:";

        // The database cache lock store prefixes keys; match on our pattern.
        // We read raw rows so we can also expose the owner token per seat.
        $rows = DB::table('cache_locks')
            ->where('key', 'like', '%' . $prefix . '%')
            ->where('expiration', '>=', now()->getTimestamp())
            ->get(['key', 'owner']);

        $map = [];
        foreach ($rows as $row) {
            $pos = strpos($row->key, $prefix);
            if ($pos === false) {
                continue;
            }
            $seatId = substr($row->key, $pos + strlen($prefix));
            $map[$seatId] = $row->owner;
        }

        return $map;
    }

    /** Internal: cheap free-probe without holding the lock. */
    private function probeFree(int $showtimeId, string $seatId): bool
    {
        $row = DB::table('cache_locks')
            ->where('key', 'like', '%' . $this->key($showtimeId, $seatId))
            ->where('expiration', '>=', now()->getTimestamp())
            ->first();

        return $row === null;
    }

    /** The owner token currently holding this seat, or null if free/expired. */
    private function currentOwnerOf(int $showtimeId, string $seatId): ?string
    {
        $row = DB::table('cache_locks')
            ->where('key', 'like', '%' . $this->key($showtimeId, $seatId))
            ->where('expiration', '>=', now()->getTimestamp())
            ->first(['owner']);

        return $row?->owner;
    }
}
