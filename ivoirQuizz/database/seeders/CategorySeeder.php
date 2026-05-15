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
                'description' => "Explorez les régions, villes, fleuves, districts, reliefs et richesses naturelles de la Côte d'Ivoire.",
                'icon' => 'map',
                'is_active' => true,
            ],
            [
                'name' => 'Gastronomie & Traditions',
                'description' => "Plongez dans les plats ivoiriens, les coutumes, les fêtes traditionnelles et les pratiques culturelles des peuples de Côte d'Ivoire.",
                'icon' => 'restaurant',
                'is_active' => true,
            ],
            [
                'name' => 'Culture & Arts',
                'description' => "Testez vos connaissances sur les artistes, la danse, le cinéma, la littérature, les festivals et les expressions culturelles ivoiriennes.",
                'icon' => 'palette',
                'is_active' => true,
            ],
            [
                'name' => 'Sport ivoirien',
                'description' => "Revivez les grands moments du sport ivoirien : football, athlétisme, champions, clubs, compétitions et exploits internationaux.",
                'icon' => 'sports_soccer',
                'is_active' => true,
            ],
            [
                'name' => 'Économie & Développement',
                'description' => "Découvrez les secteurs économiques, les ressources naturelles, les infrastructures, l’agriculture, l’industrie et le développement de la Côte d’Ivoire.",
                'icon' => 'trending_up',
                'is_active' => true,
            ],
            [
                'name' => 'Langues & Peuples',
                'description' => "Explorez les peuples, langues, groupes ethniques, royaumes, chefferies et identités culturelles qui composent la richesse ivoirienne.",
                'icon' => 'groups',
                'is_active' => true,
            ],
            [
                'name' => 'Personnalités ivoiriennes',
                'description' => "Testez vos connaissances sur les figures marquantes de la politique, du sport, de la culture, de l’entrepreneuriat et de l’histoire ivoirienne.",
                'icon' => 'person',
                'is_active' => true,
            ],
            [
                'name' => 'Musique ivoirienne',
                'description' => "Plongez dans l’univers musical ivoirien : zouglou, coupé-décalé, tradi-moderne, artistes, chansons cultes et mouvements populaires.",
                'icon' => 'music_note',
                'is_active' => true,
            ],
            [
                'name' => 'Villes & Régions',
                'description' => "Connaissez-vous les villes, régions, districts, capitales régionales, communes et particularités locales de la Côte d’Ivoire ?",
                'icon' => 'location_city',
                'is_active' => true,
            ],
            [
                'name' => 'Patrimoine & Tourisme',
                'description' => "Découvrez les sites touristiques, monuments, parcs nationaux, plages, lieux historiques et richesses patrimoniales ivoiriennes.",
                'icon' => 'travel_explore',
                'is_active' => true,
            ],
            [
                'name' => 'Éducation & Société',
                'description' => "Questions sur l’école, les institutions, la société ivoirienne, les habitudes de vie, les réalités sociales et le quotidien en Côte d’Ivoire.",
                'icon' => 'school',
                'is_active' => true,
            ],
            [
                'name' => 'Christianisme en Côte d’Ivoire',
                'description' => "Testez vos connaissances sur l’histoire du christianisme en Côte d’Ivoire, les églises, les fêtes chrétiennes, les figures religieuses et son influence dans la société ivoirienne.",
                'icon' => 'church',
                'is_active' => true,
            ],
            [
    'name' => 'Foursquare en Côte d’Ivoire',
    'description' => "Découvrez l’Église Foursquare en Côte d’Ivoire, son histoire, ses missions, ses pratiques de culte, ses grandes assemblées et son rôle dans le développement du christianisme évangélique dans le pays.",
    'icon' => 'church',
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