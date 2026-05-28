<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            // Movie bookings are tied to a specific showtime (screen + date + time).
            // Nullable because event/sport bookings are not showtime-based.
            $table->foreignId('showtime_id')->nullable()->after('bookable_id')
                ->constrained('showtimes')->nullOnDelete();
            // Signed token encoded into the ticket QR; populated on confirmation.
            $table->string('qr_code')->nullable()->after('transaction_id');
            $table->index('showtime_id');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropConstrainedForeignId('showtime_id');
            $table->dropColumn('qr_code');
        });
    }
};
