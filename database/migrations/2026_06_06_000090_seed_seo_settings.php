<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'seo_site_name'    => 'Boleto',
            'seo_title_suffix' => 'Boleto — Book Movies, Events & Sports',
            'seo_description'  => 'Boleto — book tickets for the latest movies, live events and sports in seconds. Real-time seats, instant e-tickets and secure payments.',
            'seo_keywords'     => 'movie tickets, event tickets, sports tickets, book tickets online, cinema, concerts, boleto',
            'seo_og_image'     => 'assets/images/banner/banner01.jpg',
        ];

        foreach ($defaults as $key => $value) {
            if (! DB::table('settings')->where('key', $key)->exists()) {
                DB::table('settings')->insert([
                    'key' => $key, 'value' => $value, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('settings')->whereIn('key', [
            'seo_site_name', 'seo_title_suffix', 'seo_description', 'seo_keywords', 'seo_og_image',
        ])->delete();
    }
};
