<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blog_post_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('blog_post_id')->constrained('blog_posts')->onDelete('cascade');
            $table->string('image');
            $table->integer('order')->default(0);
            $table->timestamps();
            
            $table->index('blog_post_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blog_post_images');
    }
};
