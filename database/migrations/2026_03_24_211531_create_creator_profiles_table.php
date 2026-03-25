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
        Schema::create('creator_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // Contact
            $table->string('contact_email')->nullable();
            $table->string('contact_whatsapp')->nullable();

            // Display
            $table->string('city')->nullable();
            $table->enum('theme', ['gold', 'rose', 'ocean', 'sage'])->default('gold');
            $table->json('niches')->nullable();  // ["Beleza","Lifestyle","Moda"]
            $table->json('badges')->nullable();  // ["UGC Creator","Micro"]

            // Social links
            $table->string('instagram_url')->nullable();
            $table->string('tiktok_url')->nullable();
            $table->string('youtube_url')->nullable();
            $table->string('pinterest_url')->nullable();
            $table->string('twitter_url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('creator_profiles');
    }
};
