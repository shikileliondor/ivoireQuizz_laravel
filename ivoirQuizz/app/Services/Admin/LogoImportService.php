<?php

namespace App\Services\Admin;

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
     * « difficulties » donne, si besoin, une difficulté propre à une marque (clé en minuscules).
     *
     * @param  array<int, UploadedFile>  $files
     * @param  array{question_text: string, difficulty: string, difficulties?: array<string, string>, image_source: ?string, is_active: bool}  $options
     * @return array{created: list<string>, skipped: list<string>}
     */
    public function import(int $categoryId, array $files, array $options): array
    {
        $questions = Question::query()
            ->where('category_id', $categoryId)
            ->with(['answers' => fn ($query) => $query->where('is_correct', true)])
            ->get();
        $existing = $questions->map(fn (Question $question): ?string => $question->answers->first()?->answer_text)->filter()->values();

        // Marque créée sans image (catalogue) : le fichier complète sa question au lieu d'être ignoré.
        $placeholders = $questions
            ->filter(fn (Question $question): bool => blank($question->image) && $question->answers->isNotEmpty())
            ->keyBy(fn (Question $question): string => mb_strtolower($question->answers->first()->answer_text))
            ->all();

        $taken = $existing->mapWithKeys(fn (string $brand): array => [mb_strtolower($brand) => true])->all();
        $batch = [];
        $skipped = [];

        foreach ($files as $file) {
            $brand = self::brandName($file);
            $key = mb_strtolower($brand);
            $placeholder = $placeholders[$key] ?? null;
            unset($placeholders[$key]);

            if ($brand === '' || (isset($taken[$key]) && $placeholder === null)) {
                $skipped[] = $brand !== '' ? $brand : $file->getClientOriginalName();

                continue;
            }

            $taken[$key] = true;
            $batch[] = ['brand' => $brand, 'file' => $file, 'question' => $placeholder];
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
                foreach ($batch as ['brand' => $brand, 'file' => $file, 'question' => $placeholder]) {
                    LogoImage::shrink($file);
                    $stored[] = $path = PublicImage::store($file, 'questions', 'logos');

                    if ($placeholder !== null) {
                        $placeholder->forceFill([
                            'type' => 'image',
                            'image' => $path,
                            'image_source' => $options['image_source'] ?? $placeholder->image_source,
                            'is_active' => $options['is_active'],
                        ])->save();

                        continue;
                    }

                    $answers = collect([$brand])
                        ->concat($this->wrongAnswers($pool, $brand))
                        ->map(fn (string $text): array => ['text' => $text])
                        ->all();

                    $this->questions->save(new Question, [
                        'category_id' => $categoryId,
                        'level_id' => null,
                        'type' => 'image',
                        'question_text' => $options['question_text'],
                        'difficulty' => $options['difficulties'][mb_strtolower($brand)] ?? $options['difficulty'],
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
