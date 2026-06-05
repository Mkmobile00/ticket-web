<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** YouTube trailer link for events and sports (shown on their detail pages). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->string('trailer_url')->nullable()->after('banner_image');
        });
        Schema::table('sports', function (Blueprint $table) {
            $table->string('trailer_url')->nullable()->after('banner_image');
        });
    }

    public function down(): void
    {
        Schema::table('events', fn (Blueprint $t) => $t->dropColumn('trailer_url'));
        Schema::table('sports', fn (Blueprint $t) => $t->dropColumn('trailer_url'));
    }
};
