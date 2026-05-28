<?php

namespace Database\Seeders;

use App\Models\EventCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            'Screening', 'Meetings', 'Performances', 'Workshops',
            'Exhibitions', 'Music Shows', 'Comedy Shows', 'Award Shows'
        ];

        foreach ($categories as $category) {
            EventCategory::create([
                'name' => $category,
                'slug' => Str::slug($category),
            ]);
        }
    }
}
