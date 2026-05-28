<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Make booking_seats polymorphic so seats can belong to a Showtime (movies),
 * an Event, or a Sport — one unified seat system. The double-booking guard
 * becomes a unique index on (seatable_type, seatable_id, seat_row, seat_number).
 *
 * Existing movie rows are backfilled with seatable = Showtime so the movie flow
 * keeps working unchanged.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->nullableMorphs('seatable'); // seatable_type, seatable_id (+ index)
        });

        // Backfill existing movie seats.
        DB::table('booking_seats')->whereNotNull('showtime_id')->update([
            'seatable_type' => 'App\\Models\\Showtime',
            'seatable_id' => DB::raw('showtime_id'),
        ]);

        Schema::table('booking_seats', function (Blueprint $table) {
            $table->unique(['seatable_type', 'seatable_id', 'seat_row', 'seat_number'], 'uniq_seat_per_seatable');
        });
    }

    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropUnique('uniq_seat_per_seatable');
            $table->dropMorphs('seatable');
        });
    }
};
