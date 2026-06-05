<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            // Detail image shown in the sport page intro (banner_image is the hero).
            $table->string('detail_image')->nullable()->after('banner_image');
        });
    }

    public function down(): void
    {
        Schema::table('sports', function (Blueprint $table) {
            $table->dropColumn('detail_image');
        });
    }
};
