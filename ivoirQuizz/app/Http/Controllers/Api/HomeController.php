<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\GameSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Return all home screen data in a single payload.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $categories = Category::where('is_active', true)
            ->withCount(['questions' => function ($q) {
                $q->where('is_active', true);
            }])
            ->get();

        $lastSession = GameSession::where('user_id', $user->id)
            ->with('category:id,name')
            ->latest()
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'friend_code' => $user->friend_code,
                    'avatar_id' => $user->avatar_id,
                    'total_score' => $user->total_score,
                    'games_played' => $user->games_played,
                ],
                'categories' => $categories,
                'last_session' => $lastSession,
            ],
        ]);
    }
}
