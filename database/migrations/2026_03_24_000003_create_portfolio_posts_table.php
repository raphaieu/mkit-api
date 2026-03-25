<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('partner_name')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_posts');
    }
};
