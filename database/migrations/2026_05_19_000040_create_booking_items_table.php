<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Quantity-based line items for a booking — used by Events and Sports, which
 * sell N tickets of a given type (VIP, General…) rather than specific seats.
 * Polymorphic so it points at either an EventTicket or a SportTicket.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->morphs('ticketable'); // event_ticket / sport_ticket
            $table->string('label');      // e.g. "VIP", cached for display
            $table->decimal('unit_price', 10, 2);
            $table->unsignedInteger('quantity');
            $table->timestamps();

            $table->index('booking_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_items');
    }
};
