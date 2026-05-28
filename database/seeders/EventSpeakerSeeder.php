<?php

namespace Database\Seeders;

use App\Models\EventSpeaker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSpeakerSeeder extends Seeder
{
    public function run(): void
    {
        $speakers = [
            ['name' => 'John Smith', 'designation' => 'Digital Marketing Expert'],
            ['name' => 'Sarah Johnson', 'designation' => 'Business Strategist'],
            ['name' => 'Michael Chen', 'designation' => 'Tech Innovator'],
            ['name' => 'Emily Brown', 'designation' => 'Author & Speaker'],
            ['name' => 'David Wilson', 'designation' => 'Industry Leader'],
            ['name' => 'Lisa Anderson', 'designation' => 'Entrepreneur'],
            ['name' => 'Robert Taylor', 'designation' => 'Consultant'],
            ['name' => 'Jennifer White', 'designation' => 'Motivational Speaker'],
        ];

        foreach ($speakers as $index => $speaker) {
            EventSpeaker::create([
                'name' => $speaker['name'],
                'slug' => Str::slug($speaker['name']),
                'designation' => $speaker['designation'],
                'about' => 'A passionate professional with years of experience in their field.',
                'photo' => 'assets/images/speaker/speaker' . str_pad((($index % 5) + 1), 2, '0', STR_PAD_LEFT) . '.jpg',
            ]);
        }
    }
}
