<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('note')->nullable();
            $table->string('color')->default('#0fb39a'); // badge background
            $table->integer('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        $now = now();
        $seed = [
            ['name' => 'Amazon Pay Cashback Offer', 'note' => 'Win Cashback Upto Rs 300*', 'color' => '#ff9900'],
            ['name' => 'PayPal Offer', 'note' => 'Transact first time with PayPal and get 100% cashback up to Rs. 500', 'color' => '#1f6fb0'],
            ['name' => 'HDFC Bank Offer', 'note' => 'Get 15% discount up to INR 100* and INR 50* off on F&B. T&C apply', 'color' => '#e3262e'],
        ];
        foreach ($seed as $i => $row) {
            DB::table('offers')->insert($row + ['position' => $i, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('offers');
    }
};
