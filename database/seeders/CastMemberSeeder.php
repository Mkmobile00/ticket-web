<?php

namespace Database\Seeders;

use App\Models\CastMember;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CastMemberSeeder extends Seeder
{
    public function run(): void
    {
        $actors = [
            'Tom Hanks', 'Leonardo DiCaprio', 'Brad Pitt', 'Morgan Freeman',
            'Al Pacino', 'Jack Nicholson', 'Christian Bale', 'Denzel Washington',
            'Will Smith', 'Johnny Depp', 'Matt Damon', 'Ryan Gosling'
        ];

        foreach ($actors as $index => $actor) {
            CastMember::create([
                'name' => $actor,
                'slug' => Str::slug($actor),
                'bio' => 'Award-winning actor with extensive filmography.',
                'photo' => 'assets/images/cast/cast' . str_pad((($index % 8) + 1), 2, '0', STR_PAD_LEFT) . '.jpg',
            ]);
        }
    }
}
