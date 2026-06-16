<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreGameSessionRequest;
use App\Models\GameSession;
use App\Models\Question;
use App\Models\SessionAnswer;
use App\Services\QuizScoreCalculator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class GameSessionController extends Controller
{
    /**
     * Store a completed game session and related answers.
     */
    public function store(StoreGameSessionRequest $request, QuizScoreCalculator $quizScoreCalculator): JsonResponse
    {
        $validated = $request->validated();

        try {
            $session = DB::transaction(function () use ($request, $validated, $quizScoreCalculator) {
                $session = GameSession::create([
                    'user_id' => $request->user()->id,
                    'category_id' => $validated['category_id'] ?? null,
                    'mode' => $validated['mode'],
                    'duration_seconds' => $validated['duration_seconds'],
                    'score' => 0,
                    'bonus_score' => 0,
                    'base_score' => 0,
                    'time_bonus_score' => 0,
                    'streak_bonus_score' => 0,
                    'perfect_bonus_score' => 0,
                    'total_score' => 0,
                    'correct_answers' => 0,
                    'max_streak' => 0,
                ]);

                $answersForScoring = [];
                $resolvedAnswers = [];

                foreach ($validated['answers'] as $answerData) {
                    $question = Question::query()->with('options')->findOrFail($answerData['question_id']);
                    $selectedOptionId = $answerData['selected_option_id'] ?? null;
                    $responseTime = (int) $answerData['response_time_seconds'];

                    $isCorrect = false;

                    if ($selectedOptionId) {
                        $selectedOption = $question->options->firstWhere('id', $selectedOptionId);
                        $isCorrect = (bool) optional($selectedOption)->is_correct;
                    }

                    $answersForScoring[] = [
                        'is_correct' => $isCorrect,
                        'response_time_seconds' => $responseTime,
                    ];

                    $resolvedAnswers[] = [
                        'question_id' => $question->id,
                        'selected_option_id' => $selectedOptionId,
                        'is_correct' => $isCorrect,
                        'response_time_seconds' => $responseTime,
                    ];
                }

                $scoreDetails = $quizScoreCalculator->calculate($answersForScoring);

                foreach ($resolvedAnswers as $index => $resolvedAnswer) {
                    $answerBreakdown = $scoreDetails['per_answer'][$index];

                    SessionAnswer::create([
                        'session_id' => $session->id,
                        'question_id' => $resolvedAnswer['question_id'],
                        'selected_option_id' => $resolvedAnswer['selected_option_id'],
                        'is_correct' => $resolvedAnswer['is_correct'],
                        'response_time_seconds' => $resolvedAnswer['response_time_seconds'],
                        'points_earned' => $answerBreakdown['points_earned'],
                    ]);
                }

                $session->update([
                    'score' => $scoreDetails['score'],
                    'bonus_score' => $scoreDetails['bonus_score'],
                    'base_score' => $scoreDetails['base_score'],
                    'time_bonus_score' => $scoreDetails['time_bonus_score'],
                    'streak_bonus_score' => $scoreDetails['streak_bonus_score'],
                    'perfect_bonus_score' => $scoreDetails['perfect_bonus_score'],
                    'total_score' => $scoreDetails['total_score'],
                    'correct_answers' => $scoreDetails['correct_answers'],
                    'max_streak' => $scoreDetails['max_streak'],
                    'completed_at' => now(),
                ]);

                $request->user()->increment('total_score', $scoreDetails['total_score']);
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

            $session->setAttribute('score_breakdown', [
                'base_score' => $session->base_score,
                'time_bonus_score' => $session->time_bonus_score,
                'streak_bonus_score' => $session->streak_bonus_score,
                'perfect_bonus_score' => $session->perfect_bonus_score,
                'bonus_score' => $session->bonus_score,
                'total_score' => $session->total_score,
            ]);

            return response()->json([
                'success' => true,
                'data' => $session,
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
            'data' => $sessions,
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

        $session->setAttribute('score_breakdown', [
            'base_score' => $session->base_score,
            'time_bonus_score' => $session->time_bonus_score,
            'streak_bonus_score' => $session->streak_bonus_score,
            'perfect_bonus_score' => $session->perfect_bonus_score,
            'bonus_score' => $session->bonus_score,
            'total_score' => $session->total_score,
        ]);

        return response()->json([
            'success' => true,
            'data' => $session,
            'message' => 'Détails de la session récupérés.',
        ]);
    }
}
