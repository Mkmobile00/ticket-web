<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $defaults = [
            'contact_heading' => 'Get in touch',
            'contact_intro'   => "Questions, feedback or partnership ideas? We'd love to hear from you.",
            'contact_address' => '17 South Sherman Street, Astoria, NY 11106',
            'contact_phone'   => '022-6394911',
            'contact_email'   => 'hello@boleto.com',
            'contact_hours'   => 'Mon – Sun, 9:00 AM – 9:00 PM',
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
            'contact_heading', 'contact_intro', 'contact_address', 'contact_phone', 'contact_email', 'contact_hours',
        ])->delete();
    }
};
