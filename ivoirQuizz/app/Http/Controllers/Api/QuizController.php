<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GetQuizQuestionsRequest;
use App\Models\Question;
use Illuminate\Http\JsonResponse;

class QuizController extends Controller
{
    public function getQuestions(GetQuizQuestionsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = Question::query()->where('is_active', true);

        if ($validated['mode'] === 'category') {
            $query->where('category_id', (int) $validated['category_id']);
        }
        if (! empty($validated['difficulty'])) {
            $query->where('difficulty', $validated['difficulty']);
        }

        if ($validated['mode'] === 'daily') {
            // Same ten questions for every player throughout the local calendar day.
            $questions = $query->with('answers:id,question_id,answer_text,is_correct,order')
                ->orderBy('id')->get()->shuffle((int) now()->format('Ymd'))->take(10)->values();
        } else {
            $questions = $query->with('answers:id,question_id,answer_text,is_correct,order')
                ->inRandomOrder()->limit(10)->get();
        }

        $questions = $questions->map(function (Question $question): Question {
            $question->setAttribute('options', $question->answers->shuffle()->values()->map(
                fn ($answer): array => [
                    'id' => $answer->id,
                    'option_text' => $answer->answer_text,
                    'is_correct' => (bool) $answer->is_correct,
                ],
            ));
            return $question;
        });

        return response()->json([
            'success' => true,
            'data' => $questions,
            'message' => $validated['mode'] === 'daily'
                ? 'Questions du jour récupérées.'
                : 'Questions récupérées avec succès.',
        ]);
    }
}
