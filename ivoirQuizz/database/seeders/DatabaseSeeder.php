<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            LeaguesSeeder::class,
            CategoriesSeeder::class,
            RegionsSeeder::class,
            CitiesSeeder::class,
            ChestsSeeder::class,
            CollectiblesSeeder::class,
        ]);
    }
}
