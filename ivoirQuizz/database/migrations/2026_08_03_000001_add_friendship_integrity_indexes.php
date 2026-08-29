<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Two simultaneous requests between the same pair would otherwise create
        // duplicate rows, and the pair would never be able to accept cleanly.
        DB::table('friendships as f1')
            ->join('friendships as f2', function ($join): void {
                $join->on('f1.requester_id', '=', 'f2.requester_id')
                    ->on('f1.receiver_id', '=', 'f2.receiver_id')
                    ->whereColumn('f1.id', '>', 'f2.id');
            })
            ->delete();

        Schema::table('friendships', function (Blueprint $table) {
            $table->unique(['requester_id', 'receiver_id'], 'friendships_pair_unique');
            $table->index(['receiver_id', 'status'], 'friendships_receiver_status_index');
            $table->index(['requester_id', 'status'], 'friendships_requester_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('friendships', function (Blueprint $table) {
            $table->dropUnique('friendships_pair_unique');
            $table->dropIndex('friendships_receiver_status_index');
            $table->dropIndex('friendships_requester_status_index');
        });
    }
};
