<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Réglages modifiés depuis l'admin ; les valeurs par défaut vivent dans App\Services\Notifications\PushCatalog.
        Schema::create('push_automations', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 40)->unique();
            $table->boolean('enabled')->default(true);
            $table->json('settings')->nullable();
            $table->json('templates')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();
        });

        // Journal des notifications automatiques : plafond quotidien, anti-doublon et statistiques.
        Schema::create('push_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 40);
            $table->string('ref', 64)->nullable();
            $table->boolean('is_reminder')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['type', 'ref']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_logs');
        Schema::dropIfExists('push_automations');
    }
};
