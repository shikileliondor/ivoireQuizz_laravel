<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table): void {
            $table->string('source_key', 128)->nullable();
            $table->unsignedSmallInteger('source_version')->default(1);
            $table->char('content_hash', 64)->nullable();
            $table->unique(['source_key', 'source_version'], 'questions_source_version_unique');
        });

        Schema::table('answers', function (Blueprint $table): void {
            $table->string('source_key', 64)->nullable();
            $table->unique(['question_id', 'source_key'], 'answers_question_source_unique');
        });
    }

    public function down(): void
    {
        Schema::table('answers', function (Blueprint $table): void {
            $table->dropUnique('answers_question_source_unique');
            $table->dropColumn('source_key');
        });

        Schema::table('questions', function (Blueprint $table): void {
            $table->dropUnique('questions_source_version_unique');
            $table->dropColumn(['source_key', 'source_version', 'content_hash']);
        });
    }
};
