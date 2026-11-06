<?php

namespace App\Services\Admin;

use App\Models\Answer;
use App\Models\Question;
use App\Support\LogoImage;
use App\Support\PublicImage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class LogoImportService
{
    /** Bonne réponse + trois mauvaises, comme une question saisie à la main. */
    private const WRONG_ANSWERS = 3;

    public function __construct(private readonly QuestionAdminService $questions) {}

    /**
     * Crée une question par fichier. Le nom du fichier donne la bonne réponse ;
     * les mauvaises réponses sont tirées parmi les autres marques de la catégorie.
     * Une marque déjà présente (question non archivée) est ignorée.
     *
     * @param  array<int, UploadedFile>  $files
     * @param  array{question_text: string, difficulty: string, image_source: ?string, is_active: bool}  $options
     * @return array{created: list<string>, skipped: list<string>}
     */
    public function import(int $categoryId, array $files, array $options): array
    {
        $existing = Answer::query()
            ->where('is_correct', true)
            ->whereHas('question', fn ($query) => $query->where('category_id', $categoryId))
            ->pluck('answer_text');

        $taken = $existing->mapWithKeys(fn (string $brand): array => [mb_strtolower($brand) => true])->all();
        $batch = [];
        $skipped = [];

        foreach ($files as $file) {
            $brand = self::brandName($file);
            $key = mb_strtolower($brand);

            if ($brand === '' || isset($taken[$key])) {
                $skipped[] = $brand !== '' ? $brand : $file->getClientOriginalName();

                continue;
            }

            $taken[$key] = true;
            $batch[] = ['brand' => $brand, 'file' => $file];
        }

        $pool = $existing->concat(array_column($batch, 'brand'))->unique(fn (string $brand): string => mb_strtolower($brand))->values();
        if ($batch !== [] && $pool->count() < 2) {
            throw ValidationException::withMessages([
                'logos' => 'Il faut au moins deux marques dans la catégorie Logos pour proposer de mauvaises réponses : ajoutez un autre fichier.',
            ]);
        }

        $stored = [];

        try {
            DB::transaction(function () use ($batch, $pool, $categoryId, $options, &$stored): void {
                foreach ($batch as ['brand' => $brand, 'file' => $file]) {
                    LogoImage::shrink($file);
                    $stored[] = $path = PublicImage::store($file, 'questions', 'logos');

                    $answers = collect([$brand])
                        ->concat($this->wrongAnswers($pool, $brand))
                        ->map(fn (string $text): array => ['text' => $text])
                        ->all();

                    $this->questions->save(new Question, [
                        'category_id' => $categoryId,
                        'level_id' => null,
                        'type' => 'image',
                        'question_text' => $options['question_text'],
                        'difficulty' => $options['difficulty'],
                        'image' => $path,
                        'image_source' => $options['image_source'],
                        'is_active' => $options['is_active'],
                    ], $answers, 0);
                }
            });
        } catch (Throwable $exception) {
            Storage::disk('public')->delete($stored);

            throw $exception;
        }

        return ['created' => array_column($batch, 'brand'), 'skipped' => $skipped];
    }

    /**
     * Nom de la marque tiré du fichier : « Moov_Africa.png » donne « Moov Africa ».
     * La casse est gardée telle quelle (MTN reste MTN) et les tirets aussi (Coca-Cola).
     */
    public static function brandName(UploadedFile $file): string
    {
        $name = str_replace('_', ' ', pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        return mb_substr(trim((string) preg_replace('/\s+/u', ' ', $name)), 0, 255);
    }

    /**
     * @param  Collection<int, string>  $pool
     * @return Collection<int, string>
     */
    private function wrongAnswers(Collection $pool, string $brand): Collection
    {
        return $pool
            ->reject(fn (string $other): bool => mb_strtolower($other) === mb_strtolower($brand))
            ->shuffle()
            ->take(self::WRONG_ANSWERS)
            ->values();
    }
}
