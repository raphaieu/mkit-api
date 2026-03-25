<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('instagram_id')->unique();
            $table->string('username');
            $table->string('full_name')->nullable();
            $table->text('biography')->nullable();
            $table->string('profile_picture_url')->nullable();
            $table->unsignedInteger('followers_count')->default(0);
            $table->unsignedInteger('following_count')->default(0);
            $table->unsignedInteger('media_count')->default(0);
            $table->text('access_token');
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->index('user_id');
            $table->index('username');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_profiles');
    }
};
