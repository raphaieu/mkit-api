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
        Schema::table('portfolio_posts', function (Blueprint $table) {
            $table->string('collaboration_type')->nullable()->after('partner_name');
            $table->string('reach')->nullable()->after('collaboration_type');
            $table->string('engagement_rate_text')->nullable()->after('reach');
            $table->string('deliverables')->nullable()->after('engagement_rate_text');
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_posts', function (Blueprint $table) {
            $table->dropColumn(['collaboration_type', 'reach', 'engagement_rate_text', 'deliverables']);
        });
    }
};
