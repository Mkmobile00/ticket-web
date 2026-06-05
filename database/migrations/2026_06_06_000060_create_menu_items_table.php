<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->string('location')->default('header'); // header | footer
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('label');
            $table->string('url')->default('#'); // chosen from a fixed list of site sections
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['location', 'parent_id', 'position']);
        });

        // Seed the current header + footer menus so nothing disappears.
        $now = now();
        $header = [
            ['Home', '/'], ['Movies', '/movies'], ['Events', '/events'],
            ['Sports', '/sports'], ['Blog', '/blog'], ['My Bookings', '/account'], ['Contact', '/contact'],
        ];
        foreach ($header as $i => [$label, $url]) {
            DB::table('menu_items')->insert(['location' => 'header', 'parent_id' => null, 'label' => $label, 'url' => $url, 'position' => $i, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
        $footer = [
            ['About Us', '/about'], ['Movies', '/movies'], ['Events', '/events'], ['Blog', '/blog'], ['Contact', '/contact'],
        ];
        foreach ($footer as $i => [$label, $url]) {
            DB::table('menu_items')->insert(['location' => 'footer', 'parent_id' => null, 'label' => $label, 'url' => $url, 'position' => $i, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
