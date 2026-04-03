<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table): void {
            $table->integer('base_score')->default(0)->after('bonus_score');
            $table->integer('time_bonus_score')->default(0)->after('base_score');
            $table->integer('streak_bonus_score')->default(0)->after('time_bonus_score');
            $table->integer('perfect_bonus_score')->default(0)->after('streak_bonus_score');
            $table->tinyInteger('max_streak')->default(0)->after('correct_answers');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table): void {
            $table->dropColumn([
                'base_score',
                'time_bonus_score',
                'streak_bonus_score',
                'perfect_bonus_score',
                'max_streak',
            ]);
        });
    }
};
