<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = ['Action', 'Horror', 'Comedy', 'Drama', 'Thriller', 'Romance', 'Animation', 'Sci-Fi'];

        foreach ($genres as $genre) {
            Genre::create([
                'name' => $genre,
                'slug' => Str::slug($genre),
            ]);
        }
    }
}
