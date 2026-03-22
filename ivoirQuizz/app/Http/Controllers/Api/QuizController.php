<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\GetQuizQuestionsRequest;
use App\Models\Question;
use Illuminate\Http\JsonResponse;

class QuizController extends Controller
{
    /**
     * Return quiz questions for category or mixed mode.
     */
    public function getQuestions(GetQuizQuestionsRequest $request): JsonResponse
{
    $validated = $request->validated();

    $query = Question::query()->active();

    if ($validated['mode'] === 'category') {
        $query->byCategory((int) $validated['category_id']);
    }

    $questions = $query
        ->with(['options:id,question_id,option_text,is_correct'])
        ->inRandomOrder()
        ->limit(10)
        ->get();

    $questions = $questions->map(function (Question $question) {
        $question->setRelation(
            'options',
            $question->options->shuffle()->values()
        );
        return $question;
    });

    return response()->json([
        'success' => true,
        'data' => $questions,
        'message' => 'Questions récupérées avec succès.',
    ]);
}
    }
