<?php

namespace Database\Seeders;

use App\Http\Requests\Admin\LevelRequest;
use App\Models\Answer;
use App\Models\Category;
use App\Models\Question;
use App\Services\Admin\LogoImportService;
use App\Support\LogoImage;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use RuntimeException;

/**
 * Questions de la catégorie Logos lues dans docs/logos : un fichier PNG ou WebP par marque,
 * nommé d'après elle (« Moov_Africa.png » donne la bonne réponse « Moov Africa »).
 * Mêmes règles et même traitement que l'import en lot de l'admin : les mauvaises réponses
 * sont tirées parmi les autres marques. La difficulté vient du catalogue des marques, sinon « easy ».
 * Relançable sans risque : une marque déjà présente est ignorée, une marque archivée
 * (logo retiré à la demande d'une marque) n'est jamais réimportée.
 */
class LogosSeeder extends Seeder
{
    private const CATALOGUE = 'docs/questions/catalogue_100_marques_a_preparer.json';

    private const DEFAULT_DIFFICULTY = 'easy';

    /** Dossier lu, docs/logos par défaut. */
    public ?string $directory = null;

    public function run(LogoImportService $importer): void
    {
        $directory = $this->directory ?? base_path('docs/logos');
        $files = [];

        foreach (glob($directory.'/*') ?: [] as $path) {
            $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
            if (! is_file($path) || $extension === 'md') {
                continue;
            }
            if (! in_array($extension, LogoImage::MIMES, true)) {
                $this->command?->warn('Ignoré, PNG ou WebP uniquement : '.basename($path));

                continue;
            }
            $files[] = $path;
        }

        if ($files === []) {
            $this->command?->info('Aucun logo dans docs/logos : rien à importer.');

            return;
        }

        $copies = [];

        try {
            // Le traitement réduit les grands logos en réécrivant le fichier : on travaille sur des copies.
            $uploads = array_map(function (string $path) use (&$copies): UploadedFile {
                $copies[] = $copy = (string) tempnam(sys_get_temp_dir(), 'logo');
                copy($path, $copy);

                return new UploadedFile($copy, basename($path), null, null, true);
            }, $files);

            $this->validate($uploads);
            $this->import($importer, $uploads);
        } finally {
            array_map(fn (string $copy): bool => @unlink($copy), $copies);
        }
    }

    /** @param  list<UploadedFile>  $uploads */
    private function import(LogoImportService $importer, array $uploads): void
    {
        $category = Category::query()->firstOrCreate(
            ['slug' => Category::LOGOS],
            ['name' => 'Logos', 'is_active' => true],
        );

        $archived = Answer::query()
            ->where('is_correct', true)
            ->whereHas('question', fn ($query) => $query->onlyTrashed()->where('category_id', $category->id))
            ->pluck('answer_text')
            ->mapWithKeys(fn (string $brand): array => [mb_strtolower($brand) => true])
            ->all();

        $retired = [];
        $uploads = array_values(array_filter($uploads, function (UploadedFile $upload) use ($archived, &$retired): bool {
            $brand = LogoImportService::brandName($upload);
            if (isset($archived[mb_strtolower($brand)])) {
                $retired[] = $brand;

                return false;
            }

            return true;
        }));

        $result = $importer->import($category->id, $uploads, [
            'question_text' => Question::LOGO_QUESTION_TEXT,
            'difficulty' => self::DEFAULT_DIFFICULTY,
            'difficulties' => $this->catalogueDifficulties(),
            'image_source' => null,
            'is_active' => true,
        ]);

        $this->command?->info('Logos : '.count($result['created']).' ajouté(s), '.count($result['skipped']).' déjà présent(s).');
        if ($retired !== []) {
            $this->command?->warn('Logos archivés, non réimportés (retirer le fichier de docs/logos) : '.implode(', ', $retired));
        }
    }

    /**
     * Mêmes règles que l'import admin ; un seul fichier refusé arrête tout, avant toute écriture.
     *
     * @param  list<UploadedFile>  $uploads
     */
    private function validate(array $uploads): void
    {
        $errors = [];

        foreach ($uploads as $upload) {
            $validator = Validator::make(
                ['logo' => $upload],
                ['logo' => LogoImage::rules()],
                [],
                ['logo' => '« '.$upload->getClientOriginalName().' »'],
            );
            array_push($errors, ...$validator->errors()->all());
        }

        if ($errors !== []) {
            throw new RuntimeException("Logos refusés dans docs/logos :\n- ".implode("\n- ", $errors));
        }
    }

    /** @return array<string, string> difficulté proposée par le catalogue, par marque en minuscules */
    private function catalogueDifficulties(): array
    {
        $path = base_path(self::CATALOGUE);
        $entries = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;
        $difficulties = [];

        foreach (is_array($entries) ? $entries : [] as $entry) {
            $brand = $entry['marque'] ?? null;
            $difficulty = $entry['difficulte_proposee'] ?? null;
            if (is_string($brand) && in_array($difficulty, LevelRequest::DIFFICULTIES, true)) {
                $difficulties[mb_strtolower($brand)] = $difficulty;
            }
        }

        return $difficulties;
    }
}
