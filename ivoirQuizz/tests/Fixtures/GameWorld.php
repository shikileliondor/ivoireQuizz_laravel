<?php

namespace Tests\Fixtures;

use App\Models\Category;
use App\Models\Chest;
use App\Models\City;
use App\Models\Collectible;
use App\Models\League;
use App\Models\Region;
use App\Services\Questions\CampaignImporter;
use Illuminate\Support\Str;

/**
 * Monde de jeu minimal pour les tests : ligues, coffres, catégories, régions, villes, objets
 * et une banque de 50 questions générées par ville jouable. N'existe que dans les tests.
 */
final class GameWorld
{
    /** Villes jouables => région. */
    public const CAMPAIGNS = ['korhogo' => 'poro', 'aboisso' => 'sud-comoe', 'yopougon' => 'abidjan'];

    public const CATEGORIES = [
        'histoire' => 'Histoire',
        'geographie' => 'Géographie',
        'gastronomie' => 'Gastronomie',
        'sport' => 'Sport',
        'culture' => 'Culture',
        'monuments' => 'Monuments',
        'personnalites' => 'Personnalités',
        'agriculture' => 'Agriculture',
        'economie' => 'Économie',
        'energie' => 'Énergie',
        'artisanat' => 'Artisanat',
        'education' => 'Éducation',
        'musique' => 'Musique',
        'patrimoine' => 'Patrimoine',
        'infrastructures' => 'Infrastructures',
        Category::LOGOS => 'Logos',
    ];

    private const POINTS_BY_LEVEL = [10, 15, 20, 25, 30];

    public static function build(): void
    {
        self::leagues();
        self::categories();
        self::regions();
        self::cities();

        foreach (self::CAMPAIGNS as $city => $region) {
            app(CampaignImporter::class)->importBank(self::campaign($city), self::bank($city));
        }

        self::chests();
        self::collectibles();
    }

    /** @return array{key: string, region_slug: string, city_slug: string} */
    public static function campaign(string $city): array
    {
        return ['key' => $city, 'region_slug' => self::CAMPAIGNS[$city], 'city_slug' => $city];
    }

    /** Banque valide de 50 questions (10 par niveau, 4 options, une seule bonne réponse). */
    public static function bank(string $city): array
    {
        $categories = array_values(array_diff(array_keys(self::CATEGORIES), [Category::LOGOS]));
        $bank = [];

        for ($number = 1; $number <= 50; $number++) {
            $options = [];
            foreach (['A', 'B', 'C', 'D'] as $index => $letter) {
                $options[] = [
                    'text' => "Réponse {$letter} de la question {$number}",
                    'correct' => $index === $number % 4,
                    'source_key' => "opt_{$city}_{$number}_".strtolower($letter),
                ];
            }

            $bank[] = [
                'number' => $number,
                'category' => $categories[$number % count($categories)],
                'question' => 'Question '.$number.' sur '.Str::title($city).' ?',
                'explanation' => "Explication de la question {$number}.",
                'options' => $options,
                'points' => self::POINTS_BY_LEVEL[intdiv($number - 1, 10)],
                'source_key' => sprintf('%s.q.%03d', $city, $number),
            ];
        }

        return $bank;
    }

    public static function leagues(): void
    {
        foreach (['Bronze', 'Argent', 'Or', 'Platine', 'Diamant'] as $index => $name) {
            League::query()->updateOrCreate(
                ['slug' => Str::slug($name)],
                ['name' => $name, 'rank_order' => $index + 1, 'is_active' => true],
            );
        }
    }

    public static function chests(): void
    {
        foreach ([
            ['name' => 'Coffre Bronze', 'type' => 'bronze', 'min_xp' => 10, 'max_xp' => 30, 'min_coins' => 20, 'max_coins' => 80, 'min_gems' => 0, 'max_gems' => 1],
            ['name' => 'Coffre Argent', 'type' => 'silver', 'min_xp' => 30, 'max_xp' => 80, 'min_coins' => 80, 'max_coins' => 180, 'min_gems' => 1, 'max_gems' => 3],
            ['name' => 'Coffre Or', 'type' => 'gold', 'min_xp' => 80, 'max_xp' => 150, 'min_coins' => 180, 'max_coins' => 350, 'min_gems' => 3, 'max_gems' => 8],
        ] as $chest) {
            Chest::query()->updateOrCreate(['type' => $chest['type']], $chest + ['is_active' => true]);
        }
    }

    public static function categories(): void
    {
        foreach (self::CATEGORIES as $slug => $name) {
            Category::query()->updateOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true]);
        }
    }

    /** Sept régions, seules Abidjan, Poro et Sud-Comoé sont visibles. */
    public static function regions(): void
    {
        $visible = array_values(self::CAMPAIGNS);

        foreach (['Abidjan', 'Yamoussoukro', 'Bouaké', 'Poro', 'Man', 'San Pedro', 'Sud-Comoé'] as $index => $name) {
            $slug = Str::slug($name);
            Region::query()->updateOrCreate(
                ['slug' => $slug],
                ['name' => $name, 'order' => $index + 1, 'is_active' => in_array($slug, $visible, true)],
            );
        }
    }

    /** Une ville active par région visible ; les autres communes d'Abidjan existent mais sont inactives. */
    public static function cities(): void
    {
        $abidjan = Region::query()->where('slug', 'abidjan')->firstOrFail();
        foreach (['Cocody', 'Plateau', 'Yopougon', 'Treichville', 'Marcory', 'Abobo', 'Koumassi', 'Port-Bouët'] as $index => $name) {
            $slug = Str::slug($name);
            City::query()->updateOrCreate(
                ['region_id' => $abidjan->id, 'slug' => $slug],
                ['name' => $name, 'order' => $slug === 'yopougon' ? 1 : $index + 1, 'is_active' => $slug === 'yopougon'],
            );
        }

        foreach (['korhogo' => 'Korhogo', 'aboisso' => 'Aboisso'] as $slug => $name) {
            $region = Region::query()->where('slug', self::CAMPAIGNS[$slug])->firstOrFail();
            City::query()->updateOrCreate(
                ['region_id' => $region->id, 'slug' => $slug],
                ['name' => $name, 'order' => 1, 'is_active' => true],
            );
        }
    }

    public static function collectibles(): void
    {
        foreach ([
            ['type' => 'personality', 'name' => 'Félix Houphouët-Boigny', 'rarity' => 'legendary'],
            ['type' => 'personality', 'name' => 'Didier Drogba', 'rarity' => 'epic'],
            ['type' => 'personality', 'name' => 'Alpha Blondy', 'rarity' => 'rare'],
            ['type' => 'monument', 'name' => 'Basilique Notre-Dame de la Paix', 'rarity' => 'legendary'],
            ['type' => 'monument', 'name' => 'Pont Henri Konan Bédié', 'rarity' => 'epic'],
            ['type' => 'monument', 'name' => 'Mosquée de Kong', 'rarity' => 'rare'],
        ] as $item) {
            Collectible::query()->updateOrCreate(['slug' => Str::slug($item['name'])], $item + ['is_active' => true]);
        }
    }
}
