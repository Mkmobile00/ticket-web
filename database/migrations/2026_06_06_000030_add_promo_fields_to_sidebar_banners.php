<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sidebar_banners', function (Blueprint $table) {
            $table->string('kicker')->nullable()->after('title');    // badge, e.g. "50% OFF"
            $table->string('subtitle')->nullable()->after('kicker'); // note/price line
            $table->string('cta_text')->nullable()->after('subtitle'); // button label
        });

        // Backfill the existing sidebar promo cards so the design keeps its content.
        $defaults = [
            ['kicker' => '50% OFF', 'title' => 'Be Strong! Come & Join Us', 'subtitle' => 'Call: 022-6394911', 'cta_text' => 'Call Now'],
            ['kicker' => 'New Arrival', 'title' => 'Sport Outfit', 'subtitle' => '$299', 'cta_text' => 'Shop Now'],
        ];
        $rows = DB::table('sidebar_banners')->where('placement', 'sidebar')->orderBy('position')->orderBy('id')->get();
        foreach ($rows->values() as $i => $row) {
            if (! isset($defaults[$i])) break;
            DB::table('sidebar_banners')->where('id', $row->id)->update($defaults[$i]);
        }
    }

    public function down(): void
    {
        Schema::table('sidebar_banners', function (Blueprint $table) {
            $table->dropColumn(['kicker', 'subtitle', 'cta_text']);
        });
    }
};
