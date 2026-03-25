<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Remove duplicate rows, keeping only the most recent per profile.
        DB::statement('
            DELETE i1 FROM instagram_insights i1
            INNER JOIN instagram_insights i2
                ON i1.instagram_profile_id = i2.instagram_profile_id
                AND i1.synced_at < i2.synced_at
        ');

        Schema::table('instagram_insights', function (Blueprint $table) {
            $table->unique('instagram_profile_id');
        });
    }

    public function down(): void
    {
        Schema::table('instagram_insights', function (Blueprint $table) {
            $table->dropUnique(['instagram_profile_id']);
        });
    }
};
