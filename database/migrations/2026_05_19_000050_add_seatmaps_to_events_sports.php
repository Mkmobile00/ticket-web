<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Give Events & Sports the same seat-map capability as Movies:
 *  - a seating layout (rows × seats) on the event/sport (their "screen")
 *  - seat_rows on each ticket type, mapping a price tier to seat rows
 *    (exactly like ticket_classes.seat_rows for movies)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->json('seat_layout')->nullable()->after('status');
        });
        Schema::table('sports', function (Blueprint $table) {
            $table->json('seat_layout')->nullable()->after('status');
        });
        Schema::table('event_tickets', function (Blueprint $table) {
            $table->json('seat_rows')->nullable()->after('quantity_sold');
        });
        Schema::table('sport_tickets', function (Blueprint $table) {
            $table->json('seat_rows')->nullable()->after('quantity_sold');
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $t) => $t->dropColumn('seat_layout'));
        Schema::table('sports', fn (Blueprint $t) => $t->dropColumn('seat_layout'));
        Schema::table('event_tickets', fn (Blueprint $t) => $t->dropColumn('seat_rows'));
        Schema::table('sport_tickets', fn (Blueprint $t) => $t->dropColumn('seat_rows'));
    }
};
