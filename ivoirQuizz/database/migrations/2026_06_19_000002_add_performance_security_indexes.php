<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('league_members', function (Blueprint $table) {
            $table->index(['league_season_id', 'xp_earned'], 'league_members_season_xp_index');
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->index(['level_id', 'is_active'], 'questions_level_active_index');
        });

        Schema::table('regions', function (Blueprint $table) {
            $table->index(['id', 'order'], 'regions_id_order_index');
        });

        Schema::table('cities', function (Blueprint $table) {
            $table->index(['region_id', 'order'], 'cities_region_order_index');
        });

        Schema::table('levels', function (Blueprint $table) {
            $table->index(['city_id', 'order'], 'levels_city_order_index');
        });

        Schema::table('user_chests', function (Blueprint $table) {
            $table->index(['user_id', 'status'], 'user_chests_user_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('user_chests', function (Blueprint $table) {
            $table->dropIndex('user_chests_user_status_index');
        });
        Schema::table('levels', function (Blueprint $table) {
            $table->dropIndex('levels_city_order_index');
        });
        Schema::table('cities', function (Blueprint $table) {
            $table->dropIndex('cities_region_order_index');
        });
        Schema::table('regions', function (Blueprint $table) {
            $table->dropIndex('regions_id_order_index');
        });
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_level_active_index');
        });
        Schema::table('league_members', function (Blueprint $table) {
            $table->dropIndex('league_members_season_xp_index');
        });
    }
};
