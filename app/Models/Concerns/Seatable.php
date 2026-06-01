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

    /**
     * Normalized seat grid for rendering: a list of rows, each
     *   ['label' => 'A', 'cells' => [ ['type'=>'seat','number'=>1,'id'=>'A-1'], ['type'=>'aisle'], ['type'=>'blocked'], ... ]]
     *
     * Uses the rich `grid` layout when present (cells string of s|x|b),
     * otherwise falls back to a full block from rows + seats_per_row.
     */
    public function seatGrid(): array
    {
        $layout = $this->seatLayoutArray();
        $out = [];

        if (! empty($layout['grid']) && is_array($layout['grid'])) {
            foreach ($layout['grid'] as $row) {
                $label = strtoupper(trim((string) ($row['label'] ?? '')));
                $cellsStr = (string) ($row['cells'] ?? '');
                if ($label === '') {
                    continue;
                }
                $cells = [];
                $n = 0;
                foreach (str_split($cellsStr) as $ch) {
                    if ($ch === 's' || $ch === 'S') {
                        $n++;
                        $cells[] = ['type' => 'seat', 'number' => $n, 'id' => $label . '-' . $n];
                    } elseif ($ch === 'b' || $ch === 'B') {
                        $cells[] = ['type' => 'blocked'];
                    } else {
                        $cells[] = ['type' => 'aisle'];
                    }
                }
                $out[] = ['label' => $label, 'cells' => $cells];
            }
            return $out;
        }

        // Legacy: a full rectangle of seats.
        $rows = $layout['rows'] ?: ['A', 'B', 'C', 'D', 'E'];
        $perRow = $layout['seats_per_row'] ?: array_fill(0, count($rows), 20);
        foreach ($rows as $i => $label) {
            $label = strtoupper((string) $label);
            $cells = [];
            for ($n = 1; $n <= ($perRow[$i] ?? 0); $n++) {
                $cells[] = ['type' => 'seat', 'number' => $n, 'id' => $label . '-' . $n];
            }
            $out[] = ['label' => $label, 'cells' => $cells];
        }
        return $out;
    }

    /** Flat set of valid (bookable) seat ids, e.g. ['A-1' => true, ...]. */
    public function bookableSeatIds(): array
    {
        $ids = [];
        foreach ($this->seatGrid() as $row) {
            foreach ($row['cells'] as $cell) {
                if (($cell['type'] ?? null) === 'seat') {
                    $ids[strtoupper($cell['id'])] = true;
                }
            }
        }
        return $ids;
    }
}
