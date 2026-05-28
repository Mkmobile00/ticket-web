<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->string('gateway');                 // khalti, esewa, mock, card
            $table->string('gateway_ref')->nullable(); // pidx / transaction reference from gateway
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['initiated', 'pending', 'completed', 'failed', 'refunded'])
                ->default('initiated');
            $table->json('meta')->nullable();          // raw gateway payload for audit
            $table->timestamps();

            $table->index('booking_id');
            $table->index(['gateway', 'gateway_ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
