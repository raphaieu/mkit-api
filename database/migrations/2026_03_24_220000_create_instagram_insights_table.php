<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instagram_insights', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instagram_profile_id')->constrained()->cascadeOnDelete();
            $table->timestamp('synced_at');

            // 28-day aggregated totals
            // `accounts_engaged`   = unique accounts that interacted with any content
            // `total_interactions` = sum of likes + comments + shares + saves
            // `reach`              = unique accounts that saw any content
            // `profile_views`      = profile page visits (Business accounts only; 0 for Creator)
            // `follower_count_delta` = net follower gain/loss over the period
            $table->unsignedBigInteger('accounts_engaged_28d')->default(0);
            $table->unsignedBigInteger('total_interactions_28d')->default(0);
            $table->unsignedBigInteger('reach_28d')->default(0);
            $table->unsignedInteger('profile_views_28d')->default(0);
            $table->integer('follower_count_delta_28d')->default(0);

            // Daily time-series for charts — [{date: "YYYY-MM-DD", value: N}, ...]
            $table->json('reach_series')->nullable();
            $table->json('accounts_engaged_series')->nullable();

            // Audience demographics (lifetime — requires ≥100 followers)
            $table->json('audience_gender_age')->nullable();
            $table->json('audience_country')->nullable();
            $table->json('audience_city')->nullable();

            $table->timestamps();

            $table->index('instagram_profile_id');
            $table->index('synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instagram_insights');
    }
};
