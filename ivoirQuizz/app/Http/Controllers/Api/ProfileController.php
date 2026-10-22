<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    /**
     * Update authenticated user profile.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'avatar_id' => ['required', 'integer', 'min:1', 'max:12'],
        ]);

        $request->user()->update($validated);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()->fresh(),
            ],
            'message' => 'Profil mis à jour avec succès.',
        ]);
    }

    /**
     * Return aggregated statistics for authenticated user.
     */
    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();

        $sessions = GameSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->get(['score', 'total_score', 'correct_answers', 'total_questions']);

        $totalGames = $sessions->count();
        $globalCorrectAnswers = $sessions->sum('correct_answers');
        $totalQuestions = $sessions->sum('total_questions');
        $successRate = $totalQuestions > 0 ? round(($globalCorrectAnswers / $totalQuestions) * 100, 2) : 0;
        $bestScore = (int) $sessions->max('score');
        $cumulativeScore = (int) $sessions->sum('total_score');
        $byCategory = DB::table('game_sessions')
            ->join('categories', 'categories.id', '=', 'game_sessions.category_id')
            ->where('game_sessions.user_id', $user->id)
            ->where('game_sessions.status', 'completed')
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc(DB::raw('COUNT(game_sessions.id)'))
            ->get([
                'categories.id',
                'categories.name',
                DB::raw('COUNT(game_sessions.id) as games_played'),
                DB::raw('SUM(game_sessions.correct_answers) as correct_answers'),
                DB::raw('SUM(game_sessions.total_questions) as total_questions'),
                DB::raw('MAX(game_sessions.total_score) as best_score'),
                DB::raw('ROUND(AVG(game_sessions.accuracy), 2) as success_rate'),
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'total_games_played' => $totalGames,
                'best_score' => $bestScore,
                'success_rate' => $successRate,
                'total_score' => $cumulativeScore,
                'by_category' => $byCategory,
            ],
            'message' => 'Statistiques du profil récupérées.',
        ]);
    }
}
