<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Base Tables
        $this->call(UserSeeder::class);
        $this->call(CitySeeder::class);
        $this->call(LanguageSeeder::class);
        $this->call(GenreSeeder::class);
        $this->call(FormatSeeder::class);
        $this->call(CastMemberSeeder::class);

        // Movie Infrastructure
        $this->call(CinemaSeeder::class);
        $this->call(ScreenSeeder::class);
        $this->call(MovieSeeder::class);
        $this->call(ShowtimeSeeder::class);
        $this->call(TicketClassSeeder::class);
        $this->call(PopcornItemSeeder::class);

        // Events
        $this->call(EventCategorySeeder::class);
        $this->call(EventSpeakerSeeder::class);
        $this->call(EventSeeder::class);

        // Sports
        $this->call(SportCategorySeeder::class);
        $this->call(SportSeeder::class);

        // Blog
        $this->call(BlogCategorySeeder::class);
        $this->call(BlogTagSeeder::class);
        $this->call(BlogPostSeeder::class);

        // Settings & Promo
        $this->call(SettingSeeder::class);
        $this->call(PromoCodeSeeder::class);
    }
}
