<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'sms_twilio_sid'    => '',
            'sms_twilio_token'  => '',
            'sms_twilio_from'   => '',
            'sms_msg91_authkey' => '',
            'sms_msg91_sender'  => '',
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
            'sms_twilio_sid', 'sms_twilio_token', 'sms_twilio_from', 'sms_msg91_authkey', 'sms_msg91_sender',
        ])->delete();
    }
};
