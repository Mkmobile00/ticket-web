<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-seat pricing so the seat map works for any seatable. Movies still link a
 * ticket_class, but events/sports price each seat by its tier directly, so:
 *  - ticket_class_id becomes nullable
 *  - each seat carries its own price + tier_label (the authoritative price)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropForeign(['ticket_class_id']);
        });
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->foreignId('ticket_class_id')->nullable()->change();
            $table->decimal('price', 10, 2)->nullable()->after('ticket_class_id');
            $table->string('tier_label')->nullable()->after('price');
        });
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->foreign('ticket_class_id')->references('id')->on('ticket_classes')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropColumn(['price', 'tier_label']);
        });
    }
};
