<?php

namespace Database\Seeders;

use App\Models\Sport;
use App\Models\SportCategory;
use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SportSeeder extends Seeder
{
    public function run(): void
    {
        $categories = SportCategory::all();
        $cities = City::all();

        $sports = [
            ['title' => 'World Cup Final', 'teams' => ['Brazil', 'Argentina']],
            ['title' => 'NBA Championship', 'teams' => ['Lakers', 'Celtics']],
            ['title' => 'Cricket T20 League', 'teams' => ['Mumbai Indians', 'Chennai Super Kings']],
            ['title' => 'Tennis Grand Slam', 'teams' => ['Novak Djokovic', 'Rafael Nadal']],
            ['title' => 'Rugby World Cup', 'teams' => ['New Zealand', 'South Africa']],
            ['title' => 'Ice Hockey Championship', 'teams' => ['Toronto Maple Leafs', 'Boston Bruins']],
        ];

        foreach ($sports as $index => $sportData) {
            $sport = Sport::create([
                'title' => $sportData['title'],
                'slug' => Str::slug($sportData['title']),
                'description' => 'An exciting match between two great teams.',
                'banner_image' => 'assets/images/sports/sports' . str_pad((($index % 12) + 1), 2, '0', STR_PAD_LEFT) . '.jpg',
                'team_home' => $sportData['teams'][0],
                'team_away' => $sportData['teams'][1],
                'sport_date' => now()->addDays($index * 3)->toDateString(),
                'start_time' => '19:00:00',
                'venue' => 'Grand Stadium',
                'city_id' => $cities->random()->id,
                'status' => 'upcoming',
            ]);

            // Attach random categories
            $sport->categories()->attach($categories->random(1)->pluck('id'));

            // Create sport tickets
            $ticketTypes = ['Standard', 'Premium', 'VIP'];
            foreach ($ticketTypes as $type) {
                $sport->tickets()->create([
                    'type' => $type,
                    'price' => $type === 'Standard' ? 29.99 : ($type === 'Premium' ? 79.99 : 149.99),
                    'quantity_total' => 1000,
                    'quantity_sold' => 0,
                ]);
            }
        }
    }
}
