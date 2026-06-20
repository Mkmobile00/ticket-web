<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'sms_driver'        => 'log',  // log | sparrow | twilio | msg91 | textbelt
            'sms_sparrow_token' => '',     // Sparrow SMS API token
            'sms_sparrow_from'  => '',     // Sparrow approved Sender ID / identity
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
        DB::table('settings')->whereIn('key', ['sms_driver', 'sms_sparrow_token', 'sms_sparrow_from'])->delete();
    }
};
