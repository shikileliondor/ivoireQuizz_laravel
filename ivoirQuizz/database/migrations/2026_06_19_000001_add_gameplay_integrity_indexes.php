<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('game_session_answers', function (Blueprint $table) {
            $table->unique(['game_session_id', 'question_id'], 'gsa_session_question_unique');
        });

        Schema::table('reward_transactions', function (Blueprint $table) {
            $table->index(['user_id', 'type', 'source_type', 'source_id'], 'reward_user_source_index');
        });
    }

    public function down(): void
    {
        Schema::table('reward_transactions', function (Blueprint $table) {
            $table->dropIndex('reward_user_source_index');
        });

        Schema::table('game_session_answers', function (Blueprint $table) {
            $table->dropUnique('gsa_session_question_unique');
        });
    }
};
