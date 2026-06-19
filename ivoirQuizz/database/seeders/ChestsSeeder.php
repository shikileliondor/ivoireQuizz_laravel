<?php

namespace Database\Seeders;

use App\Models\Chest;
use Illuminate\Database\Seeder;

class ChestsSeeder extends Seeder
{
    public function run(): void
    {
        $chests = [
            ['name' => 'Coffre Bronze', 'type' => 'bronze', 'min_xp' => 10, 'max_xp' => 30, 'min_coins' => 20, 'max_coins' => 80, 'min_gems' => 0, 'max_gems' => 1],
            ['name' => 'Coffre Argent', 'type' => 'silver', 'min_xp' => 30, 'max_xp' => 80, 'min_coins' => 80, 'max_coins' => 180, 'min_gems' => 1, 'max_gems' => 3],
            ['name' => 'Coffre Or', 'type' => 'gold', 'min_xp' => 80, 'max_xp' => 150, 'min_coins' => 180, 'max_coins' => 350, 'min_gems' => 3, 'max_gems' => 8],
        ];

        foreach ($chests as $chest) {
            Chest::updateOrCreate(['type' => $chest['type']], $chest + ['is_active' => true]);
        }
    }
}
