<?php

namespace Database\Seeders;

use App\Models\Movie;
use App\Models\Genre;
use App\Models\Language;
use App\Models\Format;
use App\Models\CastMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MovieSeeder extends Seeder
{
    public function run(): void
    {
        $movies = [
            ['title' => 'Venus', 'synopsis' => 'A thrilling horror movie about an unknown planet.'],
            ['title' => 'The Quantum Realm', 'synopsis' => 'A sci-fi adventure exploring alternate dimensions.'],
            ['title' => 'City of Shadows', 'synopsis' => 'A dark thriller in the heart of the city.'],
            ['title' => 'Love in Paris', 'synopsis' => 'A romantic drama set in the City of Light.'],
            ['title' => 'Action Heroes', 'synopsis' => 'An action-packed adventure with explosions and stunts.'],
            ['title' => 'Midnight Comedy', 'synopsis' => 'A hilarious comedy about a midnight encounter.'],
            ['title' => 'The Last Warrior', 'synopsis' => 'An epic battle for the fate of humanity.'],
            ['title' => 'Silent Mystery', 'synopsis' => 'A mystery thriller that keeps you guessing.'],
            ['title' => 'Space Odyssey', 'synopsis' => 'A journey through the vast universe.'],
            ['title' => 'Heart Strings', 'synopsis' => 'An emotional drama about family bonds.'],
            ['title' => 'The Invisible Man', 'synopsis' => 'A modern take on the classic story.'],
            ['title' => 'Summer Dreams', 'synopsis' => 'A feel-good comedy about young love.'],
        ];

        $genres = Genre::all();
        $languages = Language::all();
        $formats = Format::all();
        $castMembers = CastMember::all();

        foreach ($movies as $index => $movieData) {
            $movie = Movie::create([
                'title' => $movieData['title'],
                'slug' => Str::slug($movieData['title']),
                'synopsis' => $movieData['synopsis'],
                'poster_image' => 'assets/images/movie/movie' . str_pad((($index % 12) + 1), 2, '0', STR_PAD_LEFT) . '.jpg',
                'banner_image' => 'assets/images/banner/banner0' . ((($index % 7) + 1)) . '.jpg',
                'trailer_url' => 'https://www.youtube.com/embed/KGeBMAgc46E',
                'release_date' => now()->addDays($index),
                'duration_minutes' => 120 + ($index * 5),
                'rating_tomato' => 75 + rand(-15, 15),
                'rating_audience' => 80 + rand(-10, 10),
                'user_rating' => rand(3, 5),
                'status' => 'now_showing',
            ]);

            // Attach random languages, genres, formats
            $movie->languages()->attach($languages->random(2)->pluck('id'));
            $movie->genres()->attach($genres->random(2)->pluck('id'));
            $movie->formats()->attach($formats->random(2)->pluck('id'));

            // Attach cast members
            $castIds = $castMembers->random(4)->pluck('id');
            foreach ($castIds as $castId) {
                $movie->cast()->attach($castId, [
                    'character_name' => fake()->name(),
                    'role' => ['actor', 'director'][rand(0, 1)],
                    'order' => rand(1, 4),
                ]);
            }
        }
    }
}
