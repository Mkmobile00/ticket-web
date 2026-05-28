<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->foreignId('showtime_id')->nullable()->after('ticket_class_id')
                ->constrained('showtimes')->cascadeOnDelete();

            // The double-booking backstop: no two booking_seats rows (across ALL
            // bookings, pending or confirmed) may claim the same physical seat for
            // the same showtime. Cancelled bookings have their seat rows deleted so
            // the slot frees up. The Cache::lock layer guards the checkout race;
            // this unique index is the final DB-level correctness guarantee.
            $table->unique(['showtime_id', 'seat_row', 'seat_number'], 'uniq_seat_per_showtime');
        });
    }

    public function down(): void
    {
        Schema::table('booking_seats', function (Blueprint $table) {
            $table->dropUnique('uniq_seat_per_showtime');
            $table->dropConstrainedForeignId('showtime_id');
        });
    }
};
