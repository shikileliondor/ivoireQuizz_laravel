<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_sessions', function (Blueprint $table): void {
            if (! Schema::hasColumn('game_sessions', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            }
            if (! Schema::hasColumn('game_sessions', 'duration_seconds')) {
                $table->unsignedInteger('duration_seconds')->default(0)->after('total_questions');
            }
            if (! Schema::hasColumn('game_sessions', 'bonus_score')) {
                $table->unsignedInteger('bonus_score')->default(0)->after('score');
            }
            if (! Schema::hasColumn('game_sessions', 'total_score')) {
                $table->unsignedInteger('total_score')->default(0)->after('bonus_score');
            }
            if (! Schema::hasColumn('game_sessions', 'completed_at')) {
                $table->timestamp('completed_at')->nullable()->after('finished_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', function (Blueprint $table): void {
            $table->dropForeign(['category_id']);
            $table->dropColumn(['category_id', 'duration_seconds', 'bonus_score', 'total_score', 'completed_at']);
        });
    }
};
