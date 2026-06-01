<?php

namespace App\Support;

/**
 * Normalizes a submitted seat_layout into the stored shape:
 *   ['grid' => [['label'=>'A','cells'=>'sxb..'], ...], 'rows' => [...], 'seats_per_row' => [...]]
 *
 * Accepts the new visual editor (seat_layout[grid] as a JSON string) and
 * falls back to the legacy rows[] / seats_per_row[] inputs.
 */
class SeatLayout
{
    public static function normalize(array $input): array
    {
        // New format: grid JSON string from the visual editor.
        $gridRaw = $input['grid'] ?? null;
        if (is_string($gridRaw) && $gridRaw !== '') {
            $decoded = json_decode($gridRaw, true);
            if (is_array($decoded)) {
                $grid = [];
                $rows = [];
                $counts = [];
                foreach ($decoded as $i => $row) {
                    $label = strtoupper(trim((string) ($row['label'] ?? '')));
                    if ($label === '') {
                        $label = chr(65 + $i);
                    }
                    $cells = strtolower(preg_replace('/[^sxbSXB]/', 'x', (string) ($row['cells'] ?? '')));
                    if ($cells === '') {
                        continue;
                    }
                    $grid[] = ['label' => $label, 'cells' => $cells];
                    $rows[] = $label;
                    $counts[] = substr_count($cells, 's');
                }
                if (! empty($grid)) {
                    return ['grid' => $grid, 'rows' => $rows, 'seats_per_row' => $counts];
                }
            }
        }

        // Legacy: rows[] + seats_per_row[].
        $rows = array_values(array_map(fn ($r) => strtoupper(trim((string) $r)), (array) ($input['rows'] ?? [])));
        $countsIn = array_values(array_map(fn ($c) => max(1, (int) $c), (array) ($input['seats_per_row'] ?? [])));
        $pairs = [];
        foreach ($rows as $i => $r) {
            if ($r === '') {
                continue;
            }
            $pairs[$r] = $countsIn[$i] ?? 1;
        }

        return ['rows' => array_keys($pairs), 'seats_per_row' => array_values($pairs)];
    }
}
