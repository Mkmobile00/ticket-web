<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Per-entity SEO overrides. When set in admin these win over the values
 * auto-derived from the record (title/synopsis). Left blank => auto SEO.
 */
return new class extends Migration
{
    private array $tables = ['movies', 'events', 'sports', 'blog_posts'];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) continue;
            Schema::table($table, function (Blueprint $t) use ($table) {
                if (! Schema::hasColumn($table, 'meta_title'))       $t->string('meta_title', 70)->nullable();
                if (! Schema::hasColumn($table, 'meta_description')) $t->text('meta_description')->nullable();
                if (! Schema::hasColumn($table, 'meta_keywords'))    $t->string('meta_keywords', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table)) continue;
            Schema::table($table, function (Blueprint $t) use ($table) {
                foreach (['meta_title', 'meta_description', 'meta_keywords'] as $col) {
                    if (Schema::hasColumn($table, $col)) $t->dropColumn($col);
                }
            });
        }
    }
};
