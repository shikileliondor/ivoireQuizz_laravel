<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('push_notifications', function (Blueprint $table): void {
            // Les envois existants ont été faits immédiatement : ils sont « sent ».
            $table->string('status', 20)->default('sent')->index()->after('filters');
            $table->timestamp('scheduled_at')->nullable()->index()->after('status');
            $table->timestamp('started_at')->nullable()->after('scheduled_at');
            $table->timestamp('finished_at')->nullable()->after('started_at');
            $table->unsignedInteger('batches_total')->default(0)->after('finished_at');
            $table->unsignedInteger('batches_done')->default(0)->after('batches_total');
            $table->string('error', 500)->nullable()->after('removed_count');
        });
    }

    public function down(): void
    {
        Schema::table('push_notifications', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropIndex(['scheduled_at']);
            $table->dropColumn(['status', 'scheduled_at', 'started_at', 'finished_at', 'batches_total', 'batches_done', 'error']);
        });
    }
};
