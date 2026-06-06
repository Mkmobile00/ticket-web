<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'social_facebook'  => 'https://facebook.com',
            'social_twitter'   => 'https://twitter.com',
            'social_instagram' => 'https://instagram.com',
            'social_youtube'   => 'https://youtube.com',
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
            'social_facebook', 'social_twitter', 'social_instagram', 'social_youtube',
        ])->delete();
    }
};
