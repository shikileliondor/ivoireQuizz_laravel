<?php

namespace App\Services\Admin;

use App\Models\Answer;
use App\Models\Question;
use App\Services\Game\GameCacheService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminContentService
{
    public function __construct(
        private GameCacheService $cache,
    ) {}

    /**
     * The player map is cached for 6h, so any structural write must invalidate
     * it or the back office would appear to do nothing for the rest of the day.
     */
    public function forgetPlayerMapCache(): void
    {
        $this->cache->clearRegionsMapCache();
    }

    /**
     * Slugs are part of unique keys the seeders rely on; deriving them from the
     * label keeps the back office from having to think about them at all.
     */
    public function uniqueSlug(string $table, string $source, ?int $ignoreId = null, array $scope = []): string
    {
        $base = Str::slug($source) ?: 'item';
        $slug = $base;
        $suffix = 2;

        while ($this->slugTaken($table, $slug, $ignoreId, $scope)) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function slugTaken(string $table, string $slug, ?int $ignoreId, array $scope): bool
    {
        $query = DB::table($table)->where('slug', $slug);

        foreach ($scope as $column => $value) {
            $query->where($column, $value);
        }

        if ($ignoreId !== null) {
            $query->where('id', '!=', $ignoreId);
        }

        if (in_array($table, ['regions', 'chapters', 'levels', 'questions'], true)) {
            $query->whereNull('deleted_at');
        }

        return $query->exists();
    }

    /**
     * A question and its answers are one editorial unit: a question saved
     * without its answers is an unanswerable node in a live level, so both
     * sides move together or not at all.
     *
     * @param  array<int, array{id?: int|null, answer_text: string, is_correct: bool, order?: int}>  $answers
     */
    public function saveQuestionWithAnswers(Question $question, array $attributes, ?array $answers): Question
    {
        return DB::transaction(function () use ($question, $attributes, $answers): Question {
            $question->fill($attributes)->save();

            if ($answers !== null) {
                $this->syncAnswers($question, $answers);
            }

            return $question->fresh(['answers', 'level.chapter', 'category']);
        });
    }

    /**
     * Answers are replaced in place rather than deleted and recreated: the ids
     * are referenced by `game_session_answers`, and dropping them would orphan
     * the history of every player who already answered this question.
     *
     * @param  array<int, array{id?: int|null, answer_text: string, is_correct: bool, order?: int}>  $answers
     */
    private function syncAnswers(Question $question, array $answers): void
    {
        $existing = $question->answers()->get()->keyBy('id');
        $keptIds = [];

        foreach (array_values($answers) as $index => $payload) {
            $order = (int) ($payload['order'] ?? $index);
            $data = [
                'answer_text' => $payload['answer_text'],
                'is_correct' => filter_var($payload['is_correct'], FILTER_VALIDATE_BOOLEAN),
                'order' => $order,
            ];

            $id = $payload['id'] ?? null;
            $current = $id !== null ? $existing->get((int) $id) : $existing->values()->get($index);

            if ($current instanceof Answer) {
                $current->update($data);
                $keptIds[] = $current->id;

                continue;
            }

            $keptIds[] = $question->answers()->create($data)->id;
        }

        $question->answers()->whereNotIn('id', $keptIds)->delete();
    }

    /**
     * @param  class-string<Model>  $modelClass
     * @param  list<int>  $orderedIds
     */
    public function applyOrder(string $modelClass, array $orderedIds): int
    {
        return DB::transaction(function () use ($modelClass, $orderedIds): int {
            $updated = 0;

            foreach ($orderedIds as $position => $id) {
                $updated += $modelClass::query()->whereKey($id)->update(['order' => $position + 1]);
            }

            $this->forgetPlayerMapCache();

            return $updated;
        });
    }
}
