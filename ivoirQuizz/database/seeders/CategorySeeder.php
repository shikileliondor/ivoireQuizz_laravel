<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Histoire & Politique',
                'description' => "Découvrez l'histoire riche et la vie politique de la Côte d'Ivoire, de l'indépendance à nos jours.",
                'icon' => 'history',
                'is_active' => true,
            ],
            [
                'name' => 'Géographie',
                'description' => "Explorez les régions, villes, fleuves et richesses naturelles de la Côte d'Ivoire.",
                'icon' => 'map',
                'is_active' => true,
            ],
            [
                'name' => 'Gastronomie & Traditions',
                'description' => "Plongez dans la culture culinaire et les traditions ancestrales des peuples ivoiriens.",
                'icon' => 'restaurant',
                'is_active' => true,
            ],
            [
                'name' => 'morel',
                'description' => "test morel",
                'icon' => 'restaurant',
                'is_active' => true,
            ],
            [
                'name' => 'azerty',
                'description' => "popo",
                'icon' => 'restaeent',
                'is_active' => true,
            ],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                [
                    'description' => $category['description'],
                    'icon' => $category['icon'],
                    'is_active' => $category['is_active'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
