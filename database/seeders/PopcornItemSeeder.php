<?php

namespace Database\Seeders;

use App\Models\PopcornItem;
use Illuminate\Database\Seeder;

class PopcornItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['name' => 'Butter Popcorn', 'price' => 5.99, 'description' => 'Classic buttered popcorn'],
            ['name' => 'Cheese Popcorn', 'price' => 6.99, 'description' => 'Savory cheese flavored popcorn'],
            ['name' => 'Caramel Popcorn', 'price' => 6.99, 'description' => 'Sweet caramel coated popcorn'],
            ['name' => 'Mix Combo', 'price' => 7.99, 'description' => 'Mix of butter and caramel popcorn'],
            ['name' => 'Nachos', 'price' => 4.99, 'description' => 'Cheesy nachos with jalapeños'],
            ['name' => 'Soft Drink', 'price' => 2.99, 'description' => 'Large cold soft drink'],
            ['name' => 'Hot Dog', 'price' => 4.99, 'description' => 'Grilled hot dog with toppings'],
            ['name' => 'Candy Pack', 'price' => 3.99, 'description' => 'Assorted movie candy'],
        ];

        foreach ($items as $item) {
            PopcornItem::create(array_merge($item, ['is_active' => true]));
        }
    }
}
