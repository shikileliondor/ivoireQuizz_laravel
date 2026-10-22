<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreGameSessionRequest;
use App\Models\GameSession;
use App\Models\GameSessionAnswer;
use App\Services\Game\StreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameSessionController extends Controller
{
    public function store(StoreGameSessionRequest $request, StreakService $streaks): JsonResponse
    {
        $input = $request->validated();
        $user = $request->user();
        $questionIds = collect($input['answers'])->pluck('question_id')->map(fn ($id): int => (int) $id);
        $questionsById = \App\Models\Question::with('answers')
            ->whereIn('id', $questionIds)->get()->keyBy('id');

        $session = DB::transaction(function () use ($input, $user, $questionsById, $streaks): GameSession {
            $session = GameSession::create([
                'user_id' => $user->id,
                'category_id' => $input['category_id'] ?? null,
                'mode' => $input['mode'] === 'category' ? 'level' : ($input['mode'] === 'daily' ? 'daily_challenge' : 'mixed'),
                'status' => 'completed',
                'total_questions' => count($input['answers']),
                'duration_seconds' => $input['duration_seconds'],
                'started_at' => now()->subSeconds($input['duration_seconds']),
                'finished_at' => now(),
                'completed_at' => now(),
            ]);

            $correct = 0;
            $score = 0;
            foreach ($input['answers'] as $answerData) {
                $question = $questionsById->get((int) $answerData['question_id']);
                abort_if(! $question, 422, 'Question invalide.');
                $selected = ! empty($answerData['selected_option_id'])
                    ? $question->answers->firstWhere('id', (int) $answerData['selected_option_id'])
                    : null;
                $isCorrect = (bool) ($selected?->is_correct);
                $points = $isCorrect ? (int) $question->points : 0;
                GameSessionAnswer::create([
                    'game_session_id' => $session->id,
                    'question_id' => $question->id,
                    'answer_id' => $selected?->id,
                    'is_correct' => $isCorrect,
                    'response_time' => (int) $answerData['response_time_seconds'],
                    'points_earned' => $points,
                    'xp_earned' => $isCorrect ? (int) ($question->xp_reward ?? 0) : 0,
                ]);
                if ($isCorrect) {
                    $correct++;
                    $score += $points;
                }
            }

            $session->update([
                'score' => $score,
                'points_earned' => $score,
                'bonus_score' => 0,
                'total_score' => $score,
                'correct_answers' => $correct,
                'wrong_answers' => count($input['answers']) - $correct,
                'accuracy' => round($correct / max(1, count($input['answers'])) * 100, 2),
            ]);
            $user->increment('total_score', $score);
            $user->increment('games_played');
            $streaks->updateAfterGame($user);
            return $session->fresh();
        });

        $session->load(['category', 'gameSessionAnswers.question.answers', 'gameSessionAnswers.answer']);
        $answers = $session->gameSessionAnswers->map(function (GameSessionAnswer $row): array {
            $correct = $row->question?->answers->firstWhere('is_correct', true);
            return [
                'question_id' => $row->question_id,
                'selected_option_id' => $row->answer_id,
                'is_correct' => $row->is_correct,
                'response_time_seconds' => $row->response_time,
                'question' => [
                    'question_text' => $row->question?->question_text,
                    'explanation' => $row->question?->explanation,
                ],
                'selected_option' => $row->answer ? ['option_text' => $row->answer->answer_text] : null,
                'correct_option' => $correct ? ['id' => $correct->id, 'option_text' => $correct->answer_text] : null,
                'points_earned' => $row->points_earned,
            ];
        })->values();

        return response()->json(['success' => true, 'data' => [
            'id' => $session->id,
            'category' => $session->category,
            'mode' => $input['mode'],
            'score' => $session->score,
            'bonus_score' => $session->bonus_score,
            'total_score' => $session->total_score,
            'correct_answers' => $session->correct_answers,
            'total_questions' => $session->total_questions,
            'duration_seconds' => $session->duration_seconds,
            'completed_at' => $session->completed_at?->toISOString(),
            'answers' => $answers,
        ], 'message' => 'Session enregistrée avec succès.'], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $sessions = GameSession::where('user_id', $request->user()->id)
            ->with('category:id,name')->latest()->limit(50)->get();
        return response()->json(['success' => true, 'data' => $sessions]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $session = GameSession::where('user_id', $request->user()->id)
            ->with(['category', 'gameSessionAnswers.question.answers', 'gameSessionAnswers.answer'])
            ->findOrFail($id);
        return response()->json(['success' => true, 'data' => $session]);
    }
}
