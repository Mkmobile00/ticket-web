<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CitySeeder extends Seeder
{
    public function run(): void
    {
        $cities = ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix'];

        foreach ($cities as $city) {
            City::create([
                'name' => $city,
                'slug' => Str::slug($city),
            ]);
        }
    }
}
