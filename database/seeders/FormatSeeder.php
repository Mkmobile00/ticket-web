<?php

namespace Database\Seeders;

use App\Models\Format;
use Illuminate\Database\Seeder;

class FormatSeeder extends Seeder
{
    public function run(): void
    {
        $formats = ['2D', '3D', 'IMAX', '4DX'];

        foreach ($formats as $format) {
            Format::create(['name' => $format]);
        }
    }
}
