<?php

namespace Database\Seeders;

use App\Models\TicketClass;
use App\Models\Showtime;
use Illuminate\Database\Seeder;

class TicketClassSeeder extends Seeder
{
    public function run(): void
    {
        $showtimes = Showtime::all();

        $classes = [
            ['name' => 'Silver', 'price' => 8.99, 'rows' => ['A', 'B']],
            ['name' => 'Gold', 'price' => 12.99, 'rows' => ['C', 'D']],
            ['name' => 'Platinum', 'price' => 16.99, 'rows' => ['E']],
        ];

        foreach ($showtimes as $showtime) {
            foreach ($classes as $class) {
                TicketClass::create([
                    'showtime_id' => $showtime->id,
                    'name' => $class['name'],
                    'price' => $class['price'],
                    'seat_rows' => $class['rows'],
                ]);
            }
        }
    }
}
