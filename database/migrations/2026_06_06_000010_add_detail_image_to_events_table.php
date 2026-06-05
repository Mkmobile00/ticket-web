<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // Separate "detail" image shown in the event page intro section
            // (the banner_image is the hero background).
            $table->string('detail_image')->nullable()->after('banner_image');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn('detail_image');
        });
    }
};
