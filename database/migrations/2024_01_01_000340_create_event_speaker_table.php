<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_speaker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->onDelete('cascade');
            $table->foreignId('event_speaker_id')->constrained('event_speakers')->onDelete('cascade');
            $table->integer('order')->default(0);
            $table->unique(['event_id', 'event_speaker_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_speaker');
    }
};
