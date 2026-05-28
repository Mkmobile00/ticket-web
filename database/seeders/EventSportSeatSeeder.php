<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Sport;
use Illuminate\Database\Seeder;

/**
 * Gives every Event and Sport a seat map (like a movie screen) and maps their
 * existing ticket types (VIP / General …) to seat-row sections — highest price
 * at the front. Ticket prices/types are left unchanged. Re-runnable.
 *
 *   php artisan db:seed --class=EventSportSeatSeeder
 */
class EventSportSeatSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Event::with('tickets')->get() as $event) {
            $this->assign($event);
        }
        foreach (Sport::with('tickets')->get() as $sport) {
            $this->assign($sport);
        }
        $this->command->info('Assigned seat maps to ' . Event::count() . ' events and ' . Sport::count() . ' sports.');
    }

    /** @param  \App\Models\Event|\App\Models\Sport  $subject */
    private function assign($subject): void
    {
        $tickets = $subject->tickets->sortByDesc('price')->values();
        if ($tickets->isEmpty()) {
            return;
        }

        // Layout: enough rows so each tier gets a band; 20 seats per row.
        $rowsPerTier = 3;
        $perRow = 20;
        $allRows = [];
        $letter = 'A';
        $tierRows = [];

        foreach ($tickets as $ticket) {
            $rows = [];
            for ($i = 0; $i < $rowsPerTier; $i++) {
                $rows[] = $letter;
                $allRows[] = $letter;
                $letter++;
            }
            $tierRows[$ticket->id] = $rows;
        }

        $subject->update([
            'seat_layout' => [
                'rows' => $allRows,
                'seats_per_row' => array_fill(0, count($allRows), $perRow),
            ],
        ]);

        foreach ($tickets as $ticket) {
            $ticket->update([
                'seat_rows' => $tierRows[$ticket->id],
                'quantity_total' => count($tierRows[$ticket->id]) * $perRow,
            ]);
        }
    }
}
