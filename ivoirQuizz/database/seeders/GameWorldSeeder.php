<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Chest;
use App\Models\City;
use App\Models\Collectible;
use App\Models\League;
use App\Models\Region;
use App\Services\Game\GameCacheService;
use App\Services\Questions\CampaignImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Monde du jeu : ligues, catégories, régions, villes et leurs images, campagnes des villes, coffres, objets.
 * Ne fait que créer ce qui manque : une ligne existante (même archivée) n'est jamais modifiée,
 * pour ne pas défaire ce qui a été changé dans l'admin. Une campagne n'est importée que pour
 * une ville encore sans niveaux ; pour la mettre à jour ensuite : php artisan questions:import.
 */
class GameWorldSeeder extends Seeder
{
    private const LEAGUES = ['Bronze', 'Argent', 'Or', 'Platine', 'Diamant'];

    /** Régions et, pour chacune, ses villes ; seules les villes listées dans CAMPAIGNS sont actives. */
    private const REGIONS = [
        'Abidjan' => ['Yopougon', 'Cocody', 'Plateau', 'Treichville', 'Marcory', 'Abobo', 'Koumassi', 'Port-Bouët'],
        'Yamoussoukro' => [],
        'Bouaké' => [],
        'Poro' => ['Korhogo'],
        'Man' => [],
        'San Pedro' => [],
        'Sud-Comoé' => ['Aboisso'],
    ];

    /** Ville => région ; banque dans docs/campaigns/{ville}_questions.json, image dans seeders/images/{ville}.jpg. */
    private const CAMPAIGNS = ['yopougon' => 'abidjan', 'korhogo' => 'poro', 'aboisso' => 'sud-comoe'];

    private const CHESTS = [
        ['name' => 'Coffre Bronze', 'type' => 'bronze', 'min_xp' => 10, 'max_xp' => 30, 'min_coins' => 20, 'max_coins' => 80, 'min_gems' => 0, 'max_gems' => 1],
        ['name' => 'Coffre Argent', 'type' => 'silver', 'min_xp' => 30, 'max_xp' => 80, 'min_coins' => 80, 'max_coins' => 180, 'min_gems' => 1, 'max_gems' => 3],
        ['name' => 'Coffre Or', 'type' => 'gold', 'min_xp' => 80, 'max_xp' => 150, 'min_coins' => 180, 'max_coins' => 350, 'min_gems' => 3, 'max_gems' => 8],
    ];

    private const COLLECTIBLES = [
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

    public function run(CampaignImporter $campaigns, GameCacheService $cache): void
    {
        foreach (self::LEAGUES as $index => $name) {
            League::query()->firstOrCreate(['slug' => Str::slug($name)], ['name' => $name, 'rank_order' => $index + 1, 'is_active' => true]);
        }

        foreach (QuickPlayQuestionsSeeder::CATEGORY_NAMES as $slug => $name) {
            Category::query()->firstOrCreate(['slug' => $slug], ['name' => $name, 'is_active' => true]);
        }

        $order = 0;
        foreach (self::REGIONS as $regionName => $cities) {
            $regionSlug = Str::slug($regionName);
            $region = Region::withTrashed()->firstOrCreate(['slug' => $regionSlug], [
                'name' => $regionName,
                'order' => ++$order,
                'is_active' => in_array($regionSlug, self::CAMPAIGNS, true),
            ]);

            foreach ($cities as $index => $cityName) {
                $citySlug = Str::slug($cityName);
                City::withTrashed()->firstOrCreate(['region_id' => $region->id, 'slug' => $citySlug], [
                    'name' => $cityName,
                    'order' => $index + 1,
                    'is_active' => isset(self::CAMPAIGNS[$citySlug]),
                ]);
            }
        }

        foreach (self::CAMPAIGNS as $citySlug => $regionSlug) {
            $this->cityImage($citySlug);

            $city = City::query()->where('slug', $citySlug)->where('is_active', true)->first();
            if ($city === null || $city->levels()->withTrashed()->exists()) {
                continue;
            }

            $campaigns->import(['key' => $citySlug, 'region_slug' => $regionSlug, 'city_slug' => $citySlug, 'file' => base_path("docs/campaigns/{$citySlug}_questions.json")]);
            $this->command?->info("Campagne importée : {$citySlug}");
        }

        foreach (self::CHESTS as $chest) {
            Chest::query()->firstOrCreate(['type' => $chest['type']], $chest + ['is_active' => true]);
        }

        foreach (self::COLLECTIBLES as $item) {
            Collectible::withTrashed()->firstOrCreate(['slug' => Str::slug($item['name'])], $item + ['is_active' => true]);
        }

        $cache->clearRegionsMapCache();
    }

    /** Copie l'image si elle manque sur le disque public et la donne à la ville qui n'en a pas. */
    private function cityImage(string $slug): void
    {
        $path = "cities/{$slug}.jpg";
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            $contents = @file_get_contents(database_path("seeders/images/{$slug}.jpg"));
            if ($contents === false || ! $disk->put($path, $contents)) {
                throw new RuntimeException("Impossible de copier l'image de ville vers le disque public : {$path}");
            }
        }

        City::query()->where('slug', $slug)->where(fn ($query) => $query->whereNull('image')->orWhere('image', ''))->update(['image' => $path]);
    }
}
