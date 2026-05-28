<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('showtime_id')->constrained('showtimes')->onDelete('cascade');
            $table->string('name'); // Silver, Gold, Platinum
            $table->decimal('price', 10, 2);
            $table->json('seat_rows')->nullable(); // ["A", "B", "C"]
            $table->timestamps();
            
            $table->index('showtime_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_classes');
    }
};
