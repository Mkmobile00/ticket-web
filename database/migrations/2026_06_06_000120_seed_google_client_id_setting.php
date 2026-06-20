<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Google OAuth Web Client ID (from Google Cloud / Firebase). When set,
        // the web shows a Google sign-in button and the API/web verify the token
        // audience against it. Leave empty to hide Google sign-in.
        if (! DB::table('settings')->where('key', 'google_client_id')->exists()) {
            DB::table('settings')->insert([
                'key' => 'google_client_id', 'value' => '',
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('settings')->where('key', 'google_client_id')->delete();
    }
};
