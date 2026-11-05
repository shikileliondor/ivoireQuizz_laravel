<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GetQuizQuestionsRequest;
use App\Models\Question;
use App\Models\GameplaySetting;
use Illuminate\Http\JsonResponse;

class QuizController extends Controller
{
    public function getQuestions(GetQuizQuestionsRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $query = Question::query()->where('is_active', true)->displayable();

        if ($validated['mode'] === 'category') {
            $query->where('category_id', (int) $validated['category_id']);
        } else {
            $query->withoutLogos();
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

        // Une image référencée mais absente du disque rendrait la question injouable.
        $questions = $questions->filter(fn (Question $question): bool => $question->isDisplayable())->values();

        $questions = $questions->map(function (Question $question): Question {
            $question->setAttribute('image_url', $question->imageUrl());
            $question->setAttribute('options', $question->answers->shuffle()->values()->map(
                fn ($answer): array => [
                    'id' => $answer->id,
                    'option_text' => $answer->answer_text,
                    'is_correct' => (bool) $answer->is_correct,
                ],
            ));
            return $question;
        });

        $settings = GameplaySetting::snapshot();
        return response()->json([
            'success' => true,
            'data' => $questions,
            'meta' => [
                'mode' => $validated['mode'],
                'seconds_per_question' => $validated['mode'] === 'chrono' ? (int) $settings['chrono_seconds'] : null,
                'helpers' => [
                    'hint' => ['enabled' => (bool) $settings['hint_enabled'], 'cost' => (int) $settings['hint_cost']],
                    'skip' => ['enabled' => (bool) $settings['skip_enabled'], 'cost' => (int) $settings['skip_cost']],
                    'pause_on_hint' => $validated['mode'] === 'chrono' && (bool) $settings['chrono_pause_on_hint'],
                    'pause_seconds' => $validated['mode'] === 'chrono' ? (int) $settings['chrono_hint_pause_seconds'] : 0,
                ],
            ],
            'message' => $validated['mode'] === 'daily'
                ? 'Questions du jour récupérées.'
                : 'Questions récupérées avec succès.',
        ]);
    }
}
