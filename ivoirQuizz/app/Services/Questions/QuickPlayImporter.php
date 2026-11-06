<?php

namespace App\Services\Questions;

use App\Http\Requests\Admin\LevelRequest;
use App\Http\Requests\Admin\QuestionRequest;
use App\Models\Category;
use App\Models\Question;
use App\Services\Admin\QuestionAdminService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Importe des questions de « Lancement rapide » (sans ville), au format du livrable B de
 * docs/prompt-generation-contenu.md. Ces questions servent aussi au quiz du jour et aux défis.
 * Une question dont le texte existe déjà hors campagne (même archivée) est ignorée :
 * réimporter un fichier ne crée pas de doublon et ne ressuscite pas une question retirée.
 */
class QuickPlayImporter
{
    public function __construct(private readonly QuestionAdminService $questions) {}

    /** @return array{created: int, skipped: int} */
    public function import(array $bank, bool $dryRun = false): array
    {
        $categoryIds = Category::query()->where('is_active', true)->pluck('id', 'slug')->all();
        $this->validate($bank, $categoryIds);

        return DB::transaction(function () use ($bank, $categoryIds, $dryRun): array {
            $created = 0;
            $skipped = 0;

            foreach ($bank as $data) {
                $text = trim($data['question']);
                $exists = Question::withTrashed()->whereNull('level_id')->where('question_text', $text)->exists();

                if ($exists) {
                    $skipped++;

                    continue;
                }

                if (! $dryRun) {
                    $this->questions->save(new Question, [
                        'level_id' => null,
                        'category_id' => $categoryIds[$data['category']],
                        'question_text' => $text,
                        'type' => 'text',
                        'difficulty' => $data['difficulty'],
                        'explanation' => trim($data['explanation']),
                        'hint' => isset($data['hint']) ? trim($data['hint']) : null,
                        'points' => $data['points'],
                        'xp_reward' => 5,
                        'time_limit' => $data['time_limit'] ?? 20,
                        'is_active' => true,
                    ], array_map(fn (array $option): array => ['text' => $option['text']], $data['options']),
                        array_search(true, array_column($data['options'], 'correct'), true));
                }

                $created++;
            }

            return ['created' => $created, 'skipped' => $skipped];
        });
    }

    /** @param  array<string, int>  $categoryIds */
    public function validate(array $bank, array $categoryIds): void
    {
        if ($bank === [] || ! array_is_list($bank)) {
            throw new InvalidArgumentException('Le fichier doit contenir une liste JSON de questions.');
        }

        $texts = [];
        foreach ($bank as $index => $question) {
            $number = $index + 1;
            $fail = fn (string $reason) => throw new InvalidArgumentException("Question {$number} : {$reason}.");

            if (! is_array($question)) {
                $fail('format invalide');
            }

            $category = $question['category'] ?? null;
            if (! is_string($category) || ! isset($categoryIds[$category]) || $category === Category::LOGOS) {
                $fail('catégorie « '.(is_string($category) ? $category : '?').' » inconnue, inactive ou réservée aux logos');
            }

            if (($question['type'] ?? 'text') !== 'text') {
                $fail('seules les questions texte sont importables, ajouter les questions avec image depuis l’admin');
            }

            if (! in_array($question['difficulty'] ?? null, LevelRequest::DIFFICULTIES, true)) {
                $fail('difficulté invalide (easy, medium, hard ou expert)');
            }

            foreach (['question' => 1000, 'explanation' => 2000] as $field => $max) {
                if (! is_string($question[$field] ?? null) || trim($question[$field]) === '' || mb_strlen($question[$field]) > $max) {
                    $fail("« {$field} » vide ou trop long");
                }
            }

            if (isset($question['hint']) && (! is_string($question['hint']) || mb_strlen($question['hint']) > 1000)) {
                $fail('indice invalide');
            }

            if (! is_int($question['points'] ?? null) || $question['points'] < 0 || $question['points'] > 1000) {
                $fail('points invalides');
            }

            $timeLimit = $question['time_limit'] ?? 20;
            if (! is_int($timeLimit) || $timeLimit < 5 || $timeLimit > 300) {
                $fail('temps limite invalide (5 à 300 s)');
            }

            $options = $question['options'] ?? null;
            if (! is_array($options) || ! array_is_list($options) || count($options) < 2 || count($options) > QuestionRequest::MAX_ANSWERS) {
                $fail('il faut entre 2 et '.QuestionRequest::MAX_ANSWERS.' réponses');
            }

            $optionTexts = [];
            $correct = 0;
            foreach ($options as $option) {
                $optionText = is_array($option) ? ($option['text'] ?? null) : null;
                if (! is_string($optionText) || trim($optionText) === '' || mb_strlen($optionText) > 255 || ! is_bool($option['correct'] ?? null)) {
                    $fail('réponse mal formée');
                }

                $key = mb_strtolower(trim($optionText));
                if (isset($optionTexts[$key])) {
                    $fail('réponses en double');
                }
                $optionTexts[$key] = true;
                $correct += $option['correct'] ? 1 : 0;
            }

            if ($correct !== 1) {
                $fail('il faut exactement une bonne réponse');
            }

            $key = mb_strtolower(trim($question['question']));
            if (isset($texts[$key])) {
                $fail('texte déjà utilisé par une autre question du fichier');
            }
            $texts[$key] = true;
        }
    }
}
