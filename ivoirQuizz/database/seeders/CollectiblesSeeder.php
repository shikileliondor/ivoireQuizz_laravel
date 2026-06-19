<?php

namespace Database\Seeders;

use App\Models\Collectible;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CollectiblesSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['type' => 'personality', 'name' => 'Félix Houphouët-Boigny', 'rarity' => 'legendary'],
            ['type' => 'personality', 'name' => 'Didier Drogba', 'rarity' => 'epic'],
            ['type' => 'personality', 'name' => 'Alpha Blondy', 'rarity' => 'rare'],
            ['type' => 'personality', 'name' => 'Bernard Dadié', 'rarity' => 'rare'],
            ['type' => 'personality', 'name' => 'Cheick Cissé', 'rarity' => 'rare'],
            ['type' => 'monument', 'name' => 'Basilique Notre-Dame de la Paix', 'rarity' => 'legendary'],
            ['type' => 'monument', 'name' => 'Pont Henri Konan Bédié', 'rarity' => 'epic'],
            ['type' => 'monument', 'name' => 'Stade Alassane Ouattara d’Ebimpé', 'rarity' => 'epic'],
            ['type' => 'monument', 'name' => 'Parc National du Banco', 'rarity' => 'rare'],
            ['type' => 'monument', 'name' => 'Mosquée de Kong', 'rarity' => 'rare'],
        ];

        foreach ($items as $item) {
            Collectible::updateOrCreate(
                ['slug' => Str::slug($item['name'])],
                $item + ['is_active' => true]
            );
        }
    }
}
