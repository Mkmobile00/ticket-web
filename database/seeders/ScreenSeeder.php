<?php

namespace Database\Seeders;

use App\Models\Screen;
use App\Models\Cinema;
use Illuminate\Database\Seeder;

class ScreenSeeder extends Seeder
{
    public function run(): void
    {
        $cinemas = Cinema::all();

        $screens = [];
        foreach ($cinemas as $cinema) {
            for ($i = 1; $i <= 2; $i++) {
                Screen::create([
                    'cinema_id' => $cinema->id,
                    'name' => "Screen $i",
                    'total_seats' => 100,
                    'seat_layout' => [
                        'rows' => ['A', 'B', 'C', 'D', 'E'],
                        'seats_per_row' => [20, 20, 20, 20, 20],
                    ],
                ]);
            }
        }
    }
}
