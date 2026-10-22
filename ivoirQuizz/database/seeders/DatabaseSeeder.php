<?php

namespace Database\Seeders;

use Database\Seeders\Campaigns\CampaignSeeder;
use Database\Seeders\Reference\CategoriesSeeder;
use Database\Seeders\Reference\ChestsSeeder;
use Database\Seeders\Reference\CitiesSeeder;
use Database\Seeders\Reference\CollectiblesSeeder;
use Database\Seeders\Reference\LeaguesSeeder;
use Database\Seeders\Reference\RegionsSeeder;
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
            CampaignSeeder::class,
            ChestsSeeder::class,
            CollectiblesSeeder::class,
        ]);
    }
}
