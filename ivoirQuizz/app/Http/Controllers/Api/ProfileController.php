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

        $stats = GameSession::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) as total_games_played')
            ->selectRaw('COALESCE(SUM(correct_answers), 0) as correct_answers')
            ->selectRaw('COALESCE(SUM(total_questions), 0) as total_questions')
            ->selectRaw('COALESCE(MAX(score), 0) as best_score')
            ->selectRaw('COALESCE(SUM(total_score), 0) as total_score')
            ->first();

        $totalQuestions = (int) $stats->total_questions;
        $successRate = $totalQuestions > 0
            ? round(((int) $stats->correct_answers / $totalQuestions) * 100, 2)
            : 0;
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
                'total_games_played' => (int) $stats->total_games_played,
                'best_score' => (int) $stats->best_score,
                'success_rate' => $successRate,
                'total_score' => (int) $stats->total_score,
                'by_category' => $byCategory,
            ],
            'message' => 'Statistiques du profil récupérées.',
        ]);
    }
}
