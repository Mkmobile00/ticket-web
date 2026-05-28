<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_seats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->onDelete('cascade');
            $table->foreignId('ticket_class_id')->constrained('ticket_classes')->onDelete('cascade');
            $table->char('seat_row');
            $table->integer('seat_number');
            $table->timestamps();
            
            $table->index('booking_id');
            $table->unique(['booking_id', 'seat_row', 'seat_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_seats');
    }
};
