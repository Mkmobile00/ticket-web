<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sport_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained('sports')->onDelete('cascade');
            $table->string('type');
            $table->decimal('price', 10, 2);
            $table->integer('quantity_total');
            $table->integer('quantity_sold')->default(0);
            $table->timestamps();
            
            $table->index('sport_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sport_tickets');
    }
};
