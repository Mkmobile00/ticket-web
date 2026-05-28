<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_event', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('event_category_id')->constrained('event_categories')->onDelete('cascade');
            $table->unique(['event_id', 'event_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_event');
    }
};
