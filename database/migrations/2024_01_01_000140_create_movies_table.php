<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('synopsis');
            $table->string('poster_image');
            $table->string('banner_image');
            $table->string('trailer_url')->nullable();
            $table->date('release_date');
            $table->integer('duration_minutes');
            $table->decimal('rating_tomato', 5, 2)->nullable();
            $table->decimal('rating_audience', 5, 2)->nullable();
            $table->decimal('user_rating', 3, 2)->default(0);
            $table->enum('status', ['coming_soon', 'now_showing', 'archived'])->default('now_showing');
            $table->timestamps();
            
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
