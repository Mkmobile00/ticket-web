<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['key' => 'site_name', 'value' => 'Boleto'],
            ['key' => 'site_logo', 'value' => 'assets/images/logo/logo.png'],
            ['key' => 'site_email', 'value' => 'hello@boleto.com'],
            ['key' => 'site_phone', 'value' => '+1 (234) 567-8900'],
            ['key' => 'site_address', 'value' => '123 Main Street, New York, NY 10001'],
            ['key' => 'facebook_url', 'value' => 'https://facebook.com/boleto'],
            ['key' => 'twitter_url', 'value' => 'https://twitter.com/boleto'],
            ['key' => 'instagram_url', 'value' => 'https://instagram.com/boleto'],
            ['key' => 'linkedin_url', 'value' => 'https://linkedin.com/company/boleto'],
        ];

        foreach ($settings as $setting) {
            Setting::updateOrCreate(
                ['key' => $setting['key']],
                ['value' => $setting['value']]
            );
        }
    }
}
