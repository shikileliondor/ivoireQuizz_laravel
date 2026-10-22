<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\City;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    /**
     * Return global leaderboard and current user rank.
     */
    public function global(Request $request): JsonResponse
    {
        $leaderboard = User::query()
            ->select(['id', 'name', 'avatar_id', 'total_score', 'games_played'])
            ->orderByDesc('total_score')
            ->paginate(20);

        $currentUserRank = User::query()
            ->where('total_score', '>', $request->user()->total_score)
            ->count() + 1;

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $leaderboard,
                'current_user_rank' => $currentUserRank,
            ],
            'message' => 'Classement global récupéré.',
        ]);
    }

    /**
     * Return friends leaderboard including current user.
     */
    public function friends(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $friendships = Friendship::query()
            ->accepted()
            ->where(function ($query) use ($userId): void {
                $query->where('requester_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->get(['requester_id', 'receiver_id']);

        $friendIds = $friendships
            ->flatMap(fn (Friendship $friendship) => [$friendship->requester_id, $friendship->receiver_id])
            ->unique()
            ->push($userId)
            ->unique()
            ->values();

        $users = User::query()
            ->whereIn('id', $friendIds)
            ->orderByDesc('total_score')
            ->get(['id', 'name', 'avatar_id', 'total_score', 'games_played'])
            ->values();

        $rankedUsers = $users->values()->map(function (User $user, int $index) {
            return [
                'rank' => $index + 1,
                'id' => $user->id,
                'name' => $user->name,
                'avatar_id' => $user->avatar_id,
                'total_score' => $user->total_score,
                'games_played' => $user->games_played,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $rankedUsers,
            ],
            'message' => 'Classement des amis récupéré.',
        ]);
    }

    /**
     * Return the campaign leaderboard for one city using each player's best score per level.
     */
    public function city(Request $request, City $city): JsonResponse
    {
        $campaignScores = DB::table('user_level_progress as progress')
            ->join('levels', 'levels.id', '=', 'progress.level_id')
            ->where('levels.city_id', $city->id)
            ->where('progress.is_completed', true)
            ->select('progress.user_id', DB::raw('SUM(progress.best_score) as campaign_score'))
            ->groupBy('progress.user_id');

        $leaderboard = User::query()
            ->joinSub(clone $campaignScores, 'campaign_scores', 'campaign_scores.user_id', '=', 'users.id')
            ->select(['users.id', 'users.name', 'users.avatar_id', 'users.games_played', 'campaign_scores.campaign_score'])
            ->orderByDesc('campaign_scores.campaign_score')
            ->paginate(20);

        $currentScore = (clone $campaignScores)
            ->where('progress.user_id', $request->user()->id)
            ->value('campaign_score') ?? 0;

        $currentUserRank = DB::query()
            ->fromSub(clone $campaignScores, 'campaign_scores')
            ->where('campaign_score', '>', $currentScore)
            ->count() + 1;

        return response()->json([
            'success' => true,
            'data' => [
                'city' => ['id' => $city->id, 'name' => $city->name],
                'leaderboard' => $leaderboard,
                'campaign_score' => (int) $currentScore,
                'current_user_rank' => $currentUserRank,
            ],
            'message' => 'Classement de la campagne récupéré.',
        ]);
    }
}
