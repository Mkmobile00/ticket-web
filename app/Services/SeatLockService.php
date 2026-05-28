<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Atomic seat locking — the Laravel-native equivalent of BookMyShow's
 * "Redis SET NX EX 300" rule, using Laravel atomic locks (cache_locks table).
 *
 * Polymorphic: a "context" string identifies what the seats belong to, so the
 * same engine serves movies, events and sports:
 *     showtime:12   event:3   sport:5
 *
 * Lock key pattern:  seat:{context}:{ROW}-{NUMBER}
 */
class SeatLockService
{
    /** Lock lifetime in seconds (5 minutes). */
    public const TTL = 300;

    public function key(string $context, string $seatId): string
    {
        return "seat:{$context}:" . strtoupper($seatId);
    }

    /**
     * Atomically lock every seat for $context, owned by $owner. All-or-rollback.
     *
     * @param  string[]  $seatIds  e.g. ['A-1','A-2']
     * @return array{ok:bool, lockedSeats?:array, conflict?:string, expiresAt?:string}
     */
    public function lock(string $context, array $seatIds, string $owner): array
    {
        $seatIds = array_values(array_unique(array_map('strtoupper', $seatIds)));
        $acquired = [];

        foreach ($seatIds as $seatId) {
            $lock = Cache::lock($this->key($context, $seatId), self::TTL, $owner);

            if ($lock->get()) {
                $acquired[] = $seatId;
                continue;
            }

            // Same-owner re-lock is idempotent (MySQL no-op UPDATE returns 0 rows).
            if ($this->currentOwnerOf($context, $seatId) === $owner) {
                $acquired[] = $seatId;
                continue;
            }

            $this->release($context, $acquired, $owner);
            return ['ok' => false, 'conflict' => $seatId];
        }

        return [
            'ok' => true,
            'lockedSeats' => $acquired,
            'expiresAt' => now()->addSeconds(self::TTL)->toIso8601String(),
        ];
    }

    /** @param string[] $seatIds */
    public function release(string $context, array $seatIds, string $owner): void
    {
        foreach ($seatIds as $seatId) {
            Cache::restoreLock($this->key($context, strtoupper($seatId)), $owner)->release();
        }
    }

    /** @param string[] $seatIds */
    public function extend(string $context, array $seatIds, string $owner): bool
    {
        return $this->lock($context, $seatIds, $owner)['ok'];
    }

    public function isLocked(string $context, string $seatId): bool
    {
        return ! $this->probeFree($context, strtoupper($seatId));
    }

    /**
     * Map of SEATID => owner-token for all active locks in this context.
     *
     * @return array<string,string>
     */
    public function lockedSeatMap(string $context): array
    {
        $prefix = "seat:{$context}:";

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

    private function probeFree(string $context, string $seatId): bool
    {
        return DB::table('cache_locks')
            ->where('key', 'like', '%' . $this->key($context, $seatId))
            ->where('expiration', '>=', now()->getTimestamp())
            ->first() === null;
    }

    private function currentOwnerOf(string $context, string $seatId): ?string
    {
        $row = DB::table('cache_locks')
            ->where('key', 'like', '%' . $this->key($context, $seatId))
            ->where('expiration', '>=', now()->getTimestamp())
            ->first(['owner']);

        return $row?->owner;
    }
}
