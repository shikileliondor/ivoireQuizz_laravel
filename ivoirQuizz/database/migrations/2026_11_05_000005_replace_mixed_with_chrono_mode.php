<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('game_sessions', fn (Blueprint $table) => $table->string('mode', 30)->default('level')->change());
    }

    public function down(): void
    {
        DB::table('game_sessions')->where('mode', 'chrono')->update(['mode' => 'mixed']);
    }
};
