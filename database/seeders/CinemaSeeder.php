<?php

namespace Database\Seeders;

use App\Models\Cinema;
use App\Models\City;
use Illuminate\Database\Seeder;

class CinemaSeeder extends Seeder
{
    public function run(): void
    {
        $cities = City::all();
        $cinemas = [
            ['name' => 'City Walk Cinema', 'address' => '123 Main St, New York, NY 10001'],
            ['name' => 'Downtown Multiplex', 'address' => '456 Oak Ave, Los Angeles, CA 90001'],
            ['name' => 'Grand Theater', 'address' => '789 Elm St, Chicago, IL 60601'],
            ['name' => 'Star Cinema', 'address' => '321 Pine Ave, Houston, TX 77001'],
            ['name' => 'Ultra Screens', 'address' => '654 Maple Dr, Phoenix, AZ 85001'],
            ['name' => 'Platinum Halls', 'address' => '987 Cedar Lane, New York, NY 10002'],
            ['name' => 'Premier Picture House', 'address' => '147 Birch St, Los Angeles, CA 90002'],
            ['name' => 'Luxury Cinema', 'address' => '258 Spruce Ave, Chicago, IL 60602'],
        ];

        foreach ($cinemas as $cinema) {
            Cinema::create([
                'name' => $cinema['name'],
                'city_id' => $cities->random()->id,
                'address' => $cinema['address'],
                'latitude' => fake()->latitude(),
                'longitude' => fake()->longitude(),
                'is_active' => true,
            ]);
        }
    }
}
