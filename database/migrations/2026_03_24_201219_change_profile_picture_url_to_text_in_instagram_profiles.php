<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Instagram CDN URLs can exceed VARCHAR(255).
        // Same applies to media_url, thumbnail_url and permalink in instagram_posts.
        Schema::table('instagram_profiles', function (Blueprint $table) {
            $table->text('profile_picture_url')->nullable()->change();
        });

        Schema::table('instagram_posts', function (Blueprint $table) {
            $table->text('media_url')->nullable()->change();
            $table->text('thumbnail_url')->nullable()->change();
            $table->text('permalink')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('instagram_profiles', function (Blueprint $table) {
            $table->string('profile_picture_url')->nullable()->change();
        });

        Schema::table('instagram_posts', function (Blueprint $table) {
            $table->string('media_url')->nullable()->change();
            $table->string('thumbnail_url')->nullable()->change();
            $table->string('permalink')->nullable()->change();
        });
    }
};
