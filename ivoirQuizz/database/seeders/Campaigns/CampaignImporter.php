<?php

namespace Database\Seeders\Campaigns;

use App\Models\Answer;
use App\Models\Category;
use App\Models\City;
use App\Models\Level;
use App\Models\Question;
use App\Models\Region;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class CampaignImporter
{
    private const POINTS_BY_LEVEL = [10, 15, 20, 25, 30];

    private const DIFFICULTIES_BY_LEVEL = ['easy', 'medium', 'medium', 'hard', 'expert'];

    private const LEVELS = [
        ['title' => 'Découverte', 'slug' => 'decouverte', 'is_boss' => false],
        ['title' => 'Explorateur', 'slug' => 'explorateur', 'is_boss' => false],
        ['title' => 'Connaisseur', 'slug' => 'connaisseur', 'is_boss' => false],
        ['title' => 'Expert', 'slug' => 'expert', 'is_boss' => false],
        ['title' => 'Maître', 'slug' => 'maitre', 'is_boss' => true],
    ];

    public function import(array $campaign): void
    {
        $file = $campaign['file'] ?? null;
        if (! is_string($file) || ! is_file($file) || ! is_readable($file)) {
            throw new RuntimeException('Le fichier JSON de la campagne est introuvable ou illisible.');
        }

        try {
            $questions = json_decode(file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidArgumentException('Le JSON de la campagne est invalide : '.$exception->getMessage(), previous: $exception);
        }

        if (! is_array($questions)) {
            throw new InvalidArgumentException('La banque de campagne doit être un tableau JSON.');
        }

        $categorySlugs = Category::query()->where('is_active', true)->pluck('slug')->all();
        $this->validateBank($questions, $campaign['key'], $categorySlugs);

        DB::transaction(function () use ($campaign, $questions): void {
            $region = Region::query()->where('slug', $campaign['region_slug'])->where('is_active', true)->first();
            if (! $region) {
                throw new RuntimeException("La région {$campaign['region_slug']} doit être créée par RegionsSeeder avant l’import.");
            }

            $city = City::query()
                ->where('region_id', $region->id)
                ->where('slug', $campaign['city_slug'])
                ->where('is_active', true)
                ->first();
            if (! $city) {
                throw new RuntimeException("La ville {$campaign['city_slug']} doit être créée par CitiesSeeder avant l’import.");
            }

            $levels = $this->syncLevels($city);
            $categoryIds = Category::query()
                ->whereIn('slug', array_unique(array_column($questions, 'category')))
                ->pluck('id', 'slug');
            $importedQuestionIds = [];

            foreach ($questions as $questionData) {
                $levelNumber = intdiv($questionData['number'] - 1, 10) + 1;
                $question = $this->syncQuestion(
                    $questionData,
                    $levels[$levelNumber],
                    (int) $categoryIds[$questionData['category']],
                    self::DIFFICULTIES_BY_LEVEL[$levelNumber - 1],
                );
                $importedQuestionIds[] = $question->id;
            }

            // Campaign levels own their question bank. Retire removed content without deleting history.
            Question::query()
                ->whereIn('level_id', array_map(fn (Level $level): int => $level->id, $levels))
                ->where('is_active', true)
                ->whereNotIn('id', $importedQuestionIds)
                ->update(['is_active' => false, 'updated_at' => now()]);
        });
    }

    /** @param array<int, string> $availableCategorySlugs */
    public function validateBank(array $questions, string $campaignKey, array $availableCategorySlugs): void
    {
        if (! preg_match('/^[a-z0-9-]+$/', $campaignKey)) {
            throw new InvalidArgumentException('La clé de campagne doit être un slug simple.');
        }

        if (count($questions) !== 50 || array_is_list($questions) === false) {
            throw new InvalidArgumentException('La banque doit contenir exactement 50 questions dans une liste JSON.');
        }

        $questionKeys = [];
        $questionTexts = [];
        $availableCategories = array_fill_keys($availableCategorySlugs, true);

        foreach ($questions as $index => $question) {
            $expectedNumber = $index + 1;
            if (! is_array($question)
                || ! isset($question['number'])
                || ! is_int($question['number'])
                || $question['number'] !== $expectedNumber) {
                throw new InvalidArgumentException("La question {$expectedNumber} a un numéro absent, dupliqué ou hors séquence.");
            }

            $sourceKey = $question['source_key'] ?? null;
            if (! is_string($sourceKey)
                || ! preg_match('/^'.preg_quote($campaignKey, '/').'\.q\.[a-z0-9_-]+$/', $sourceKey)
                || isset($questionKeys[$sourceKey])) {
                throw new InvalidArgumentException("La question {$expectedNumber} a une clé source invalide ou dupliquée.");
            }
            $questionKeys[$sourceKey] = true;

            foreach (['question', 'explanation'] as $field) {
                if (! isset($question[$field]) || ! is_string($question[$field]) || trim($question[$field]) === '') {
                    throw new InvalidArgumentException("La question {$expectedNumber} doit avoir un texte et une explication non vides.");
                }
            }

            $normalizedText = mb_strtolower(trim($question['question']), 'UTF-8');
            if (isset($questionTexts[$normalizedText])) {
                throw new InvalidArgumentException("Le texte de la question {$expectedNumber} est dupliqué dans la campagne.");
            }
            $questionTexts[$normalizedText] = true;

            $category = $question['category'] ?? null;
            if (! is_string($category) || ! isset($availableCategories[$category])) {
                throw new InvalidArgumentException("La question {$expectedNumber} référence une catégorie inconnue ou inactive.");
            }

            $levelNumber = intdiv($expectedNumber - 1, 10) + 1;
            if (! isset($question['points'])
                || ! is_int($question['points'])
                || $question['points'] !== self::POINTS_BY_LEVEL[$levelNumber - 1]) {
                throw new InvalidArgumentException("La question {$expectedNumber} n’a pas le barème attendu pour son niveau.");
            }

            $options = $question['options'] ?? null;
            if (! is_array($options) || ! in_array(count($options), [2, 4], true) || ! array_is_list($options)) {
                throw new InvalidArgumentException("La question {$expectedNumber} doit contenir deux ou quatre options.");
            }

            $optionKeys = [];
            $optionTexts = [];
            $correctCount = 0;
            foreach ($options as $option) {
                if (! is_array($option)) {
                    throw new InvalidArgumentException("La question {$expectedNumber} contient une option invalide.");
                }

                $optionKey = $option['source_key'] ?? null;
                $optionText = $option['text'] ?? null;
                $isCorrect = $option['correct'] ?? null;
                if (! is_string($optionKey)
                    || ! preg_match('/^[a-z0-9][a-z0-9_-]*$/', $optionKey)
                    || isset($optionKeys[$optionKey])
                    || ! is_string($optionText)
                    || trim($optionText) === ''
                    || ! is_bool($isCorrect)) {
                    throw new InvalidArgumentException("La question {$expectedNumber} contient une option mal formée ou une clé dupliquée.");
                }

                $optionKeys[$optionKey] = true;
                $normalizedOption = mb_strtolower(trim($optionText), 'UTF-8');
                if (isset($optionTexts[$normalizedOption])) {
                    throw new InvalidArgumentException("La question {$expectedNumber} contient des options textuellement dupliquées.");
                }
                $optionTexts[$normalizedOption] = true;
                $correctCount += $isCorrect ? 1 : 0;
            }

            if ($correctCount !== 1) {
                throw new InvalidArgumentException("La question {$expectedNumber} doit avoir exactement une bonne réponse.");
            }
        }
    }

    /** @return array<int, Level> */
    private function syncLevels(City $city): array
    {
        $levels = [];
        foreach (self::LEVELS as $index => $definition) {
            $level = Level::withTrashed()->updateOrCreate(
                ['city_id' => $city->id, 'slug' => $definition['slug']],
                [
                    'title' => $definition['title'],
                    'difficulty' => self::DIFFICULTIES_BY_LEVEL[$index],
                    'order' => $index + 1,
                    'required_xp' => 0,
                    'questions_count' => 10,
                    'passing_score' => 70,
                    'xp_reward' => 50,
                    'coins_reward' => 0,
                    'gems_reward' => 0,
                    'is_boss' => $definition['is_boss'],
                    'is_active' => true,
                ],
            );
            if ($level->trashed()) {
                $level->restore();
            }
            $levels[$index + 1] = $level;
        }

        return $levels;
    }

    private function syncQuestion(array $data, Level $level, int $categoryId, string $difficulty): Question
    {
        $hash = $this->contentHash($data, $difficulty);
        $latest = Question::withTrashed()
            ->where('source_key', $data['source_key'])
            ->orderByDesc('source_version')
            ->lockForUpdate()
            ->first();

        if (! $latest) {
            $legacyRows = Question::withTrashed()
                ->where('level_id', $level->id)
                ->whereNull('source_key')
                ->where('question_text', trim($data['question']))
                ->lockForUpdate()
                ->get();

            if ($legacyRows->count() > 1) {
                throw new RuntimeException("Question legacy ambiguë pour la clé {$data['source_key']}.");
            }

            $legacy = $legacyRows->first();
            if ($legacy) {
                if ($legacy->trashed()) {
                    $legacy->restore();
                }

                if ($this->legacyMatchesData($legacy, $data, $difficulty)) {
                    $legacy->fill([
                        'source_key' => $data['source_key'],
                        'source_version' => 1,
                        'content_hash' => $hash,
                        'is_active' => true,
                    ])->save();
                    $this->assignLegacyOptionKeys($legacy, $data['options']);

                    return $legacy;
                }

                $legacy->forceFill(['is_active' => false])->save();
            }
        }

        $matchingVersion = Question::withTrashed()
            ->where('source_key', $data['source_key'])
            ->where('content_hash', $hash)
            ->orderByDesc('source_version')
            ->first();

        if ($matchingVersion) {
            if ($matchingVersion->trashed()) {
                $matchingVersion->restore();
            }
            if (! $matchingVersion->is_active) {
                $matchingVersion->forceFill(['is_active' => true])->save();
            }
            Question::withTrashed()
                ->where('source_key', $data['source_key'])
                ->where('id', '<>', $matchingVersion->id)
                ->where('is_active', true)
                ->update(['is_active' => false, 'updated_at' => now()]);

            return $matchingVersion;
        }

        $nextVersion = ((int) Question::withTrashed()->where('source_key', $data['source_key'])->max('source_version')) + 1;
        if ($latest && $latest->is_active) {
            $latest->forceFill(['is_active' => false])->save();
        }

        $question = Question::query()->create([
            'level_id' => $level->id,
            'category_id' => $categoryId,
            'source_key' => $data['source_key'],
            'source_version' => max(1, $nextVersion),
            'content_hash' => $hash,
            'question_text' => trim($data['question']),
            'type' => 'text',
            'difficulty' => $difficulty,
            'explanation' => trim($data['explanation']),
            'points' => $data['points'],
            'xp_reward' => 5,
            'time_limit' => 20,
            'is_active' => true,
        ]);

        foreach ($data['options'] as $index => $option) {
            Answer::query()->create([
                'question_id' => $question->id,
                'source_key' => $option['source_key'],
                'answer_text' => trim($option['text']),
                'is_correct' => $option['correct'],
                'order' => $index + 1,
            ]);
        }

        Question::withTrashed()
            ->where('source_key', $data['source_key'])
            ->where('id', '<>', $question->id)
            ->where('is_active', true)
            ->update(['is_active' => false, 'updated_at' => now()]);

        return $question;
    }

    private function contentHash(array $data, string $difficulty): string
    {
        $content = [
            'category' => $data['category'],
            'question' => trim($data['question']),
            'explanation' => trim($data['explanation']),
            'difficulty' => $difficulty,
            'points' => $data['points'],
            'xp_reward' => 5,
            'time_limit' => 20,
            'options' => array_map(fn (array $option, int $index): array => [
                'source_key' => $option['source_key'],
                'text' => trim($option['text']),
                'correct' => $option['correct'],
                'order' => $index + 1,
            ], $data['options'], array_keys($data['options'])),
        ];

        return hash('sha256', json_encode($content, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));
    }

    private function legacyMatchesData(Question $question, array $data, string $difficulty): bool
    {
        $question->loadMissing(['category', 'answers']);
        $answers = $question->answers->sortBy('order')->values();
        if ($answers->count() !== count($data['options'])) {
            return false;
        }

        if ($question->category?->slug !== $data['category']
            || trim($question->question_text) !== trim($data['question'])
            || trim((string) $question->explanation) !== trim($data['explanation'])
            || $question->difficulty !== $difficulty
            || (int) $question->points !== (int) $data['points']
            || (int) $question->xp_reward !== 5
            || (int) $question->time_limit !== 20) {
            return false;
        }

        foreach ($data['options'] as $index => $option) {
            $answer = $answers[$index];
            if (trim($answer->answer_text) !== trim($option['text'])
                || (bool) $answer->is_correct !== $option['correct']) {
                return false;
            }
        }

        return true;
    }

    private function assignLegacyOptionKeys(Question $question, array $options): void
    {
        $answers = $question->answers()->orderBy('order')->get();
        if ($answers->count() !== count($options)) {
            throw new RuntimeException("Les options legacy de la question {$question->source_key} sont ambiguës.");
        }

        foreach ($options as $index => $option) {
            $answer = $answers[$index];
            if ($answer->answer_text !== trim($option['text']) || (bool) $answer->is_correct !== $option['correct']) {
                throw new RuntimeException("Une option legacy ne correspond pas à la question {$question->source_key}.");
            }
            $answer->forceFill(['source_key' => $option['source_key']])->save();
        }
    }
}
