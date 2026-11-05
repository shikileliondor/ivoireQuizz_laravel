<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('gameplay_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
        });

        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->integer('amount');
            $table->unsignedInteger('balance_before');
            $table->unsignedInteger('balance_after');
            $table->nullableMorphs('reference');
            $table->uuid('idempotency_key')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'idempotency_key']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('game_question_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['hint', 'skip']);
            $table->unsignedInteger('cost');
            $table->timestamp('paused_until')->nullable();
            $table->timestamps();
            $table->unique(['game_session_id', 'question_id', 'type']);
        });

        Schema::create('game_session_question', function (Blueprint $table) {
            $table->foreignId('game_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('position');
            $table->primary(['game_session_id', 'question_id']);
            $table->unique(['game_session_id', 'position']);
        });

        Schema::table('questions', fn (Blueprint $table) => $table->text('hint')->nullable()->after('explanation'));
        Schema::table('game_session_answers', fn (Blueprint $table) => $table->string('status', 20)->default('answered')->after('is_correct'));
        Schema::table('game_sessions', function (Blueprint $table) {
            $table->unsignedSmallInteger('seconds_per_question')->nullable()->after('total_questions');
            $table->json('gameplay_config')->nullable()->after('seconds_per_question');
        });
    }

    public function down(): void
    {
        Schema::table('game_sessions', fn (Blueprint $table) => $table->dropColumn(['seconds_per_question', 'gameplay_config']));
        Schema::table('game_session_answers', fn (Blueprint $table) => $table->dropColumn('status'));
        Schema::table('questions', fn (Blueprint $table) => $table->dropColumn('hint'));
        Schema::dropIfExists('game_session_question');
        Schema::dropIfExists('game_question_actions');
        Schema::dropIfExists('coin_transactions');
        Schema::dropIfExists('gameplay_settings');
    }
};
