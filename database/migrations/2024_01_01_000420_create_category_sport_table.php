<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_sport', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained('sports')->onDelete('cascade');
            $table->foreignId('sport_category_id')->constrained('sport_categories')->onDelete('cascade');
            $table->unique(['sport_id', 'sport_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_sport');
    }
};
