<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('friend_challenges', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 8)->unique();
            $table->foreignId('challenger_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('opponent_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->json('question_ids');
            $table->unsignedInteger('challenger_score')->nullable();
            $table->unsignedInteger('opponent_score')->nullable();
            $table->unsignedSmallInteger('challenger_correct')->nullable();
            $table->unsignedSmallInteger('opponent_correct')->nullable();
            $table->json('challenger_answers')->nullable();
            $table->json('opponent_answers')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamps();
            $table->index(['challenger_id', 'status']);
            $table->index(['opponent_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('friend_challenges');
    }
};
