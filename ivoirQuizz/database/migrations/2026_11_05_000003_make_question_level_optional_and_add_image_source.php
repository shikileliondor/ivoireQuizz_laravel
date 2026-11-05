<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Une question sans niveau n'apparaît pas sur la carte : elle se joue
     * seulement dans le lancement rapide, via sa catégorie (ex. les logos).
     */
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropForeign(['level_id']);
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->foreignId('level_id')->nullable()->change();
            $table->foreign('level_id')->references('id')->on('levels')->cascadeOnDelete();
            $table->string('image_source')->nullable()->after('image');
        });
    }

    /**
     * Échoue tant qu'il reste des questions sans niveau : à rattacher ou supprimer d'abord.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->dropForeign(['level_id']);
            $table->dropColumn('image_source');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->foreignId('level_id')->nullable(false)->change();
            $table->foreign('level_id')->references('id')->on('levels')->cascadeOnDelete();
        });
    }
};
