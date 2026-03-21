<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

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
}
