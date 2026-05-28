<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('banner_image');
            $table->string('team_home');
            $table->string('team_away');
            $table->date('sport_date');
            $table->time('start_time');
            $table->string('venue');
            $table->foreignId('city_id')->constrained('cities')->onDelete('cascade');
            $table->enum('status', ['upcoming', 'live', 'completed'])->default('upcoming');
            $table->timestamps();
            
            $table->index('slug');
            $table->index('sport_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports');
    }
};
