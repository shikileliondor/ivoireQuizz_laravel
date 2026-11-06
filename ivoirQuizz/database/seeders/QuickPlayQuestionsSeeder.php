<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Services\Questions\CampaignImporter;
use App\Services\Questions\QuickPlayImporter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Questions de « Lancement rapide » (quiz du jour, défis) lues dans docs/questions/*_questions.json.
 * Crée ou réactive d'abord les catégories utilisées par les fichiers.
 * Relançable sans risque : une question déjà présente est ignorée.
 * Pour ajouter des questions : déposer un nouveau fichier xxx_questions.json dans docs/questions, puis relancer.
 */
class QuickPlayQuestionsSeeder extends Seeder
{
    public const CATEGORY_NAMES = [
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
    ];

    /** Slugs mal générés par une ancienne version (accents supprimés au lieu d'être translittérés). */
    private const LEGACY_SLUGS = ['geographie' => 'gographie', 'personnalites' => 'personnalits'];

    public function run(CampaignImporter $reader, QuickPlayImporter $importer): void
    {
        $files = glob(base_path('docs/questions/*_questions.json')) ?: [];
        if ($files === []) {
            throw new RuntimeException('Aucun fichier docs/questions/*_questions.json à importer.');
        }

        $banks = array_map(fn (string $file): array => $reader->readBank($file), $files);
        $this->ensureCategories(array_unique(array_merge(...array_map(
            fn (array $bank): array => array_column($bank, 'category'),
            $banks,
        ))));

        foreach ($files as $index => $file) {
            $result = $importer->import($banks[$index]);
            $this->command?->info(basename($file).' : '.$result['created'].' ajoutée(s), '.$result['skipped'].' déjà présente(s).');
        }
    }

    /** @param  array<int, mixed>  $slugs */
    private function ensureCategories(array $slugs): void
    {
        foreach ($slugs as $slug) {
            if (! is_string($slug) || $slug === '' || $slug === Category::LOGOS) {
                continue;
            }

            $category = Category::query()->where('slug', $slug)->first()
                ?? (isset(self::LEGACY_SLUGS[$slug]) ? Category::query()->where('slug', self::LEGACY_SLUGS[$slug])->first() : null);

            if ($category) {
                $category->forceFill(['slug' => $slug, 'is_active' => true])->save();

                continue;
            }

            Category::query()->create([
                'slug' => $slug,
                'name' => self::CATEGORY_NAMES[$slug] ?? Str::headline($slug),
                'is_active' => true,
            ]);
            $this->command?->info("Catégorie créée : {$slug}");
        }
    }
}
