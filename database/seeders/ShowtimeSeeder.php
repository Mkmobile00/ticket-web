<?php

namespace Database\Seeders;

use App\Models\Showtime;
use App\Models\Movie;
use App\Models\Screen;
use App\Models\Language;
use App\Models\Format;
use Illuminate\Database\Seeder;

class ShowtimeSeeder extends Seeder
{
    public function run(): void
    {
        $movies = Movie::all();
        $screens = Screen::all();
        $languages = Language::all();
        $formats = Format::all();

        foreach ($movies as $movie) {
            for ($day = 0; $day < 7; $day++) {
                for ($i = 0; $i < 3; $i++) {
                    Showtime::create([
                        'movie_id' => $movie->id,
                        'screen_id' => $screens->random()->id,
                        'language_id' => $languages->random()->id,
                        'format_id' => $formats->random()->id,
                        'show_date' => now()->addDays($day)->toDateString(),
                        'show_time' => ['10:00:00', '14:00:00', '19:00:00'][$i],
                        'available_seats' => 100,
                        'status' => 'active',
                    ]);
                }
            }
        }
    }
}
