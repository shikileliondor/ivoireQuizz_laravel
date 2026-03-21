<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->constrained()
                  ->onDelete('cascade');
            $table->foreignId('category_id')
                  ->nullable()
                  ->constrained()
                  ->onDelete('set null');
            $table->enum('mode', ['category', 'mixed']);
            $table->integer('score')->default(0);
            $table->integer('bonus_score')->default(0);
            $table->integer('total_score')->default(0);
            $table->tinyInteger('correct_answers')->default(0);
            $table->integer('duration_seconds')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void {
        Schema::dropIfExists('game_sessions');
    }
};