<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->text('question_text');
            $table->enum('type', ['text', 'image', 'audio'])->default('text');
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'expert'])->default('easy');
            $table->string('image')->nullable();
            $table->string('audio')->nullable();
            $table->text('explanation')->nullable();
            $table->integer('points')->default(10);
            $table->integer('xp_reward')->default(5);
            $table->integer('time_limit')->default(20);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['level_id', 'category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
