<?php

namespace Database\Seeders;

use App\Models\PromoCode;
use Illuminate\Database\Seeder;

class PromoCodeSeeder extends Seeder
{
    public function run(): void
    {
        $codes = [
            ['code' => 'SAVE10', 'type' => 'percentage', 'value' => 10, 'usage_limit' => 100],
            ['code' => 'SAVE20', 'type' => 'percentage', 'value' => 20, 'usage_limit' => 50],
            ['code' => 'FLAT5', 'type' => 'fixed', 'value' => 5, 'usage_limit' => 200],
            ['code' => 'WELCOME', 'type' => 'percentage', 'value' => 15, 'usage_limit' => 300],
            ['code' => 'SUMMER', 'type' => 'fixed', 'value' => 10, 'usage_limit' => 150],
        ];

        foreach ($codes as $code) {
            PromoCode::create([
                'code' => $code['code'],
                'discount_type' => $code['type'],
                'discount_value' => $code['value'],
                'valid_from' => now(),
                'valid_to' => now()->addMonths(3),
                'usage_limit' => $code['usage_limit'],
                'usage_count' => 0,
                'is_active' => true,
            ]);
        }
    }
}
