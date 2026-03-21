<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('session_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')
                  ->constrained('game_sessions')
                  ->onDelete('cascade');
            $table->foreignId('question_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('selected_option_id')
                  ->nullable()
                  ->constrained('options')
                  ->onDelete('set null');
            $table->boolean('is_correct')->default(false);
            $table->tinyInteger('response_time_seconds')->default(0);
            $table->integer('points_earned')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('session_answers');
    }
};