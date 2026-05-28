<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movie_cast', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movie_id')->constrained('movies')->onDelete('cascade');
            $table->foreignId('cast_member_id')->constrained('cast_members')->onDelete('cascade');
            $table->string('character_name');
            $table->enum('role', ['actor', 'director', 'producer', 'crew'])->default('actor');
            $table->integer('order')->default(0);
            $table->unique(['movie_id', 'cast_member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movie_cast');
    }
};
