<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('showtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies')->onDelete('cascade');
            $table->foreignId('screen_id')->constrained('screens')->onDelete('cascade');
            $table->foreignId('language_id')->constrained('languages')->onDelete('cascade');
            $table->foreignId('format_id')->constrained('formats')->onDelete('cascade');
            $table->date('show_date');
            $table->time('show_time');
            $table->integer('available_seats');
            $table->enum('status', ['active', 'cancelled'])->default('active');
            $table->timestamps();
            
            $table->index(['movie_id', 'show_date']);
            $table->index('screen_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('showtimes');
    }
};
