<?php

namespace Database\Seeders;

use App\Models\BlogTag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogTagSeeder extends Seeder
{
    public function run(): void
    {
        $tags = ['Tickets', 'Movies', 'Events', 'Entertainment', 'Reviews', 'Trending', 'Coming Soon', 'Deals'];

        foreach ($tags as $tag) {
            BlogTag::create([
                'name' => $tag,
                'slug' => Str::slug($tag),
            ]);
        }
    }
}
