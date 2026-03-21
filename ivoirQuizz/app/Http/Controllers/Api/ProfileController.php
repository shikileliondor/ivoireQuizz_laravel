<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
            ->with('category:id,name')
            ->get();

        $categoryStats = $sessions
            ->groupBy('category_id')
            ->map(function ($categorySessions, $categoryId) {
                $count = $categorySessions->count();
                $totalCorrect = $categorySessions->sum('correct_answers');
                $category = $categorySessions->first()?->category;

                return [
                    'category_id' => (int) $categoryId,
                    'category_name' => $category?->name,
                    'sessions_played' => $count,
                    'average_score' => round((float) $categorySessions->avg('total_score'), 2),
                    'best_score' => (int) $categorySessions->max('total_score'),
                    'success_rate' => $count > 0
                        ? round(($totalCorrect / ($count * 10)) * 100, 2)
                        : 0,
                ];
            })
            ->values();

        $totalGames = $sessions->count();
        $globalCorrectAnswers = $sessions->sum('correct_answers');

        return response()->json([
            'success' => true,
            'data' => [
                'global' => [
                    'total_games_played' => $totalGames,
                    'best_session_score' => (int) $sessions->max('total_score'),
                    'global_success_rate' => $totalGames > 0
                        ? round(($globalCorrectAnswers / ($totalGames * 10)) * 100, 2)
                        : 0,
                    'total_cumulative_score' => (int) $sessions->sum('total_score'),
                ],
                'by_category' => $categoryStats,
            ],
            'message' => 'Statistiques du profil récupérées.',
        ]);
    }
}
