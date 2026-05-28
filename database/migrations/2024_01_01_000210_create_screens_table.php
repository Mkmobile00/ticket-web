<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('screens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cinema_id')->constrained('cinemas')->onDelete('cascade');
            $table->string('name');
            $table->integer('total_seats');
            $table->json('seat_layout')->nullable(); // {"rows": ["A","B","C"], "seats_per_row": [20,20,20]}
            $table->timestamps();
            
            $table->index('cinema_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('screens');
    }
};
