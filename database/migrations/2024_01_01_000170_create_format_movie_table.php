<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('format_movie', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies')->onDelete('cascade');
            $table->foreignId('format_id')->constrained('formats')->onDelete('cascade');
            $table->unique(['movie_id', 'format_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('format_movie');
    }
};
