<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('email', 191)->unique();
            $table->string('password')->nullable();
            $table->string('google_id')->nullable()->unique();
            $table->string('friend_code', 6)->unique();
            $table->string('avatar')->nullable();
            $table->tinyInteger('avatar_id')->default(1);
            $table->integer('current_level')->default(1);
            $table->integer('xp_total')->default(0);
            $table->integer('total_score')->default(0);
            $table->integer('coins')->default(0);
            $table->integer('gems')->default(0);
            $table->integer('games_played')->default(0);
            $table->integer('games_won')->default(0);
            $table->foreignId('current_region_id')->nullable()->index();
            $table->foreignId('current_city_id')->nullable()->index();
            $table->foreignId('current_game_level_id')->nullable()->index();
            $table->timestamp('last_login_at')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
