<?php

namespace Database\Seeders;

use App\Models\Language;
use Illuminate\Database\Seeder;

class LanguageSeeder extends Seeder
{
    public function run(): void
    {
        $languages = [
            ['name' => 'English', 'code' => 'en'],
            ['name' => 'Hindi', 'code' => 'hi'],
            ['name' => 'Tamil', 'code' => 'ta'],
            ['name' => 'Telugu', 'code' => 'te'],
        ];

        foreach ($languages as $lang) {
            Language::create($lang);
        }
    }
}
