<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_profile_id')->constrained()->cascadeOnDelete();
            $table->string('instagram_media_id')->unique();
            $table->string('media_type');
            $table->string('media_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->string('permalink')->nullable();
            $table->text('caption')->nullable();
            $table->unsignedInteger('like_count')->default(0);
            $table->unsignedInteger('comments_count')->default(0);
            $table->timestamp('timestamp')->nullable();
            $table->timestamps();

            $table->index('instagram_profile_id');
            $table->index('timestamp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_posts');
    }
};
