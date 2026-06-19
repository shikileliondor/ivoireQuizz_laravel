<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'expert'])->default('easy');
            $table->integer('order')->default(0);
            $table->integer('required_xp')->default(0);
            $table->integer('questions_count')->default(10);
            $table->integer('passing_score')->default(70);
            $table->integer('xp_reward')->default(50);
            $table->integer('coins_reward')->default(0);
            $table->integer('gems_reward')->default(0);
            $table->boolean('is_boss')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('city_id');
            $table->unique(['city_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('levels');
    }
};
