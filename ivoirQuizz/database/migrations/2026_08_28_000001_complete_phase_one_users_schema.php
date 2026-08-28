<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 50)->nullable()->unique()->after('name');
            $table->string('phone', 30)->nullable()->index()->after('email');
            $table->timestamp('email_verified_at')->nullable()->after('email');
            $table->string('city', 100)->nullable()->after('avatar');
            $table->text('bio')->nullable()->after('city');
            $table->date('last_activity_date')->nullable()->index()->after('last_login_at');
            $table->string('status', 20)->default('active')->index()->after('last_activity_date');
            $table->softDeletes();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email', 191)->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique(['username']);
            $table->dropIndex(['phone']);
            $table->dropIndex(['last_activity_date']);
            $table->dropIndex(['status']);
            $table->dropColumn([
                'username',
                'phone',
                'email_verified_at',
                'city',
                'bio',
                'last_activity_date',
                'status',
                'deleted_at',
            ]);
        });
    }
};
