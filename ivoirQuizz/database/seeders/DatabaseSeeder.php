<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** php artisan db:seed --force */
    public function run(): void
    {
        $this->call(QuickPlayQuestionsSeeder::class);
    }
}
