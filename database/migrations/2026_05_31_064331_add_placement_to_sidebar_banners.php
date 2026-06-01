<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sidebar_banners', function (Blueprint $table) {
            // Where the banner shows: 'sidebar' (web home), 'carousel' (mobile), or 'both'.
            $table->string('placement')->default('both')->after('link');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sidebar_banners', function (Blueprint $table) {
            $table->dropColumn('placement');
        });
    }
};
