<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventSpeaker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        $categories = EventCategory::all();
        $speakers = EventSpeaker::all();

        $events = [
            ['title' => 'Digital Marketing Conference 2020', 'description' => 'A comprehensive conference on digital marketing strategies and tools.'],
            ['title' => 'Tech Summit 2025', 'description' => 'Explore the latest innovations in technology and AI.'],
            ['title' => 'Business Growth Workshop', 'description' => 'Learn strategies to grow your business exponentially.'],
            ['title' => 'Creative Design Expo', 'description' => 'Showcase of cutting-edge design and creativity.'],
            ['title' => 'Startup Pitch Night', 'description' => 'Meet investors and pitch your startup idea.'],
            ['title' => 'Leadership Summit', 'description' => 'Develop leadership skills and network with executives.'],
            ['title' => 'Innovation Awards', 'description' => 'Celebrate the most innovative companies and products.'],
            ['title' => 'Future Forum', 'description' => 'Discuss the future of technology and business.'],
        ];

        foreach ($events as $index => $eventData) {
            $event = Event::create([
                'title' => $eventData['title'],
                'slug' => Str::slug($eventData['title']),
                'description' => $eventData['description'],
                'banner_image' => 'assets/images/event/event' . str_pad((($index % 12) + 1), 2, '0', STR_PAD_LEFT) . '.jpg',
                'event_date' => now()->addDays($index * 2)->toDateString(),
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'address' => '17 South Sherman Street, Astoria, NY 11106',
                'organizer' => 'Boleto Events',
                'status' => 'upcoming',
            ]);

            // Attach random categories and speakers
            $event->categories()->attach($categories->random(2)->pluck('id'));
            
            $speakerIds = $speakers->random(3)->pluck('id');
            foreach ($speakerIds as $key => $speakerId) {
                $event->speakers()->attach($speakerId, ['order' => $key + 1]);
            }

            // Create event tickets
            $ticketTypes = ['Regular', 'VIP', 'Premium'];
            foreach ($ticketTypes as $type) {
                $event->tickets()->create([
                    'type' => $type,
                    'price' => $type === 'Regular' ? 49.99 : ($type === 'VIP' ? 99.99 : 149.99),
                    'quantity_total' => 500,
                    'quantity_sold' => 0,
                ]);
            }

            // Create event stats
            $event->stats()->createMany([
                ['label' => 'Speakers', 'value' => '70+', 'order' => 1],
                ['label' => 'Days', 'value' => '3', 'order' => 2],
                ['label' => 'Workshops', 'value' => '100+', 'order' => 3],
            ]);
        }
    }
}
