<?php

namespace Database\Seeders;

use App\Models\SportCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SportCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = ['Cricket', 'Football', 'Basketball', 'Tennis', 'Rugby', 'Ice Hockey'];

        foreach ($categories as $category) {
            SportCategory::create([
                'name' => $category,
                'slug' => Str::slug($category),
            ]);
        }
    }
}
