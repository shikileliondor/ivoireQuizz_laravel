<?php

namespace App\Services\Admin;

use App\Models\Question;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class QuestionAdminService
{
    /**
     * Archive la question et efface son image du serveur : un logo retiré à la demande
     * d'une marque ne doit plus être accessible, même par l'URL déjà envoyée à l'app.
     * Le fichier est gardé s'il sert encore à une autre question.
     */
    public function archive(Question $question): void
    {
        $image = $question->image;

        DB::transaction(function () use ($question): void {
            $question->forceFill(['image' => null])->save();
            $question->delete();
        });

        if ($image !== null && $image !== '' && ! str_starts_with($image, 'http')
            && ! Question::query()->where('image', $image)->exists()) {
            Storage::disk('public')->delete($image);
        }
    }

    /**
     * Enregistre la question et synchronise ses réponses en une transaction.
     * Les réponses existantes sont modifiées en place pour garder le lien avec l'historique des parties.
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int|string, array{id?: int|string|null, text: string}>  $answers
     */
    public function save(Question $question, array $attributes, array $answers, int|string $correctKey): Question
    {
        return DB::transaction(function () use ($question, $attributes, $answers, $correctKey): Question {
            $question->fill($attributes)->save();

            $existing = $question->answers()->get()->keyBy('id');
            $keptIds = [];
            $order = 0;

            foreach ($answers as $key => $row) {
                $values = [
                    'answer_text' => trim($row['text']),
                    'is_correct' => (string) $key === (string) $correctKey,
                    'order' => ++$order,
                ];

                $answer = $existing->get((int) ($row['id'] ?? 0));

                if ($answer !== null) {
                    $answer->update($values);
                } else {
                    $answer = $question->answers()->create($values);
                }

                $keptIds[] = $answer->id;
            }

            $question->answers()->whereNotIn('id', $keptIds)->delete();

            return $question;
        });
    }
}
