<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'about_hero_title'       => 'About Boleto',
            'about_hero_subtitle'    => 'Your one gateway to movies, live events and sports — book in seconds, enjoy the moment.',
            'about_story_title'      => 'Get to know us',
            'about_story_body'       => "Boleto makes booking tickets effortless. From blockbuster movies to sold-out concerts and big-match sports, we bring everything you love into one simple, secure place.\n\nWith real-time seat selection, instant e-tickets and trusted payments, millions of fans rely on Boleto to be there for the moments that matter.",
            'about_story_image'      => 'assets/images/about/about01.png',
            'about_philosophy_title' => 'Our Philosophy',
            'about_philosophy_body'  => 'We believe going out should be simple and joyful. Every feature we build is guided by a few principles we never compromise on.',
            'about_values'           => "Honesty & Fairness\nClarity & Transparency\nFocus on Customers",
            'about_stats'            => "30M+|Tickets Booked\n11|Cities Covered\n650+|Screens & Venues\n4.8|Average Rating",
        ];

        foreach ($defaults as $key => $value) {
            // Only insert if missing, so re-running never overwrites admin edits.
            $exists = DB::table('settings')->where('key', $key)->exists();
            if (! $exists) {
                DB::table('settings')->insert([
                    'key'        => $key,
                    'value'      => $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'about_hero_title', 'about_hero_subtitle', 'about_story_title', 'about_story_body',
            'about_story_image', 'about_philosophy_title', 'about_philosophy_body', 'about_values', 'about_stats',
        ])->delete();
    }
};
