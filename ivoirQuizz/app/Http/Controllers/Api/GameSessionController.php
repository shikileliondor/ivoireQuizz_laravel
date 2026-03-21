<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreGameSessionRequest;
use App\Models\GameSession;
use App\Models\Question;
use App\Models\SessionAnswer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class GameSessionController extends Controller
{
    /**
     * Store a completed game session and related answers.
     */
    public function store(StoreGameSessionRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $session = DB::transaction(function () use ($request, $validated) {
                $session = GameSession::create([
                    'user_id' => $request->user()->id,
                    'category_id' => $validated['category_id'] ?? null,
                    'mode' => $validated['mode'],
                    'duration_seconds' => $validated['duration_seconds'],
                    'score' => 0,
                    'bonus_score' => 0,
                    'total_score' => 0,
                    'correct_answers' => 0,
                ]);

                $correctAnswers = 0;
                $bonusScore = 0;

                foreach ($validated['answers'] as $answerData) {
                    $question = Question::query()->with('options')->findOrFail($answerData['question_id']);
                    $selectedOptionId = $answerData['selected_option_id'] ?? null;
                    $responseTime = (int) $answerData['response_time_seconds'];

                    $isCorrect = false;

                    if ($selectedOptionId) {
                        $selectedOption = $question->options->firstWhere('id', $selectedOptionId);
                        $isCorrect = (bool) optional($selectedOption)->is_correct;
                    }

                    $pointsEarned = SessionAnswer::calculatePoints($isCorrect, $responseTime);

                    SessionAnswer::create([
                        'session_id' => $session->id,
                        'question_id' => $question->id,
                        'selected_option_id' => $selectedOptionId,
                        'is_correct' => $isCorrect,
                        'response_time_seconds' => $responseTime,
                        'points_earned' => $pointsEarned,
                    ]);

                    if ($isCorrect) {
                        $correctAnswers++;
                        $bonusScore += max(0, $pointsEarned - 100);
                    }
                }

                $score = $correctAnswers * 100;
                $totalScore = $score + $bonusScore;

                $session->update([
                    'score' => $score,
                    'bonus_score' => $bonusScore,
                    'total_score' => $totalScore,
                    'correct_answers' => $correctAnswers,
                    'completed_at' => now(),
                ]);

                $request->user()->increment('total_score', $totalScore);
                $request->user()->increment('games_played');

                return $session;
            });

            $session->load(['category', 'answers.question.options', 'answers.selectedOption']);

            $session->answers->each(function (SessionAnswer $answer): void {
                $correctOption = $answer->question
                    ?->options
                    ->firstWhere('is_correct', true);

                $answer->setAttribute('correct_option', $correctOption ? [
                    'id' => $correctOption->id,
                    'question_id' => $correctOption->question_id,
                    'option_text' => $correctOption->option_text,
                ] : null);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'session' => $session,
                ],
                'message' => 'Session enregistrée avec succès.',
            ], 201);
        } catch (Throwable $exception) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement de la session.',
                'errors' => [
                    'exception' => $exception->getMessage(),
                ],
            ], 500);
        }
    }

    /**
     * Return latest sessions of authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $sessions = GameSession::query()
            ->where('user_id', $request->user()->id)
            ->with('category')
            ->latest()
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'sessions' => $sessions,
            ],
            'message' => 'Historique des sessions récupéré.',
        ]);
    }

    /**
     * Return a specific session if it belongs to authenticated user.
     */
    public function show(Request $request, int $id): JsonResponse
    {
        $session = GameSession::query()
            ->with(['answers.question.options', 'answers.selectedOption', 'category'])
            ->findOrFail($id);

        if ((int) $session->user_id !== (int) $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Accès interdit à cette session.',
                'errors' => null,
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'session' => $session,
            ],
            'message' => 'Détails de la session récupérés.',
        ]);
    }
}
