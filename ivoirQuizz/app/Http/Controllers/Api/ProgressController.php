<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Friendship;
use App\Models\GameSession;
use App\Models\UserStreak;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProgressController extends Controller
{
    public function achievements(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessions = GameSession::where('user_id', $user->id)->whereNotNull('completed_at');
        $games = (clone $sessions)->count();
        $perfect = (clone $sessions)->whereColumn('correct_answers', 'total_questions')->where('total_questions', '>', 0)->exists();
        $streak = (int) (UserStreak::where('user_id', $user->id)->value('current_streak') ?? 0);
        $mastered = (clone $sessions)->whereNotNull('category_id')->select('category_id')
            ->groupBy('category_id')->havingRaw('AVG(accuracy) >= 80')->get()->count();
        $challengeWins = DB::table('friend_challenges')->where('status', 'completed')
            ->where(fn ($q) => $q->where('challenger_id', $user->id)->orWhere('opponent_id', $user->id))
            ->where(fn ($q) => $q->whereRaw('(challenger_id = ? AND challenger_score > opponent_score)', [$user->id])
                ->orWhereRaw('(opponent_id = ? AND opponent_score > challenger_score)', [$user->id]))->count();

        $badges = [
            ['id' => 'first_quiz', 'name' => 'Premier quiz', 'icon' => 'sports_esports', 'unlocked' => $games >= 1, 'progress' => min($games, 1), 'target' => 1],
            ['id' => 'quiz_veteran', 'name' => 'Quiz passionné', 'icon' => 'military_tech', 'unlocked' => $games >= 10, 'progress' => min($games, 10), 'target' => 10],
            ['id' => 'perfect_score', 'name' => 'Sans faute', 'icon' => 'workspace_premium', 'unlocked' => $perfect, 'progress' => $perfect ? 1 : 0, 'target' => 1],
            ['id' => 'streak_3', 'name' => 'Série de 3 jours', 'icon' => 'local_fire_department', 'unlocked' => $streak >= 3, 'progress' => min($streak, 3), 'target' => 3],
            ['id' => 'category_master', 'name' => 'Expert catégorie', 'icon' => 'menu_book', 'unlocked' => $mastered >= 3, 'progress' => min($mastered, 3), 'target' => 3],
            ['id' => 'challenge_winner', 'name' => 'Vainqueur', 'icon' => 'emoji_events', 'unlocked' => $challengeWins >= 1, 'progress' => min($challengeWins, 1), 'target' => 1],
        ];

        return response()->json(['success' => true, 'data' => [
            'badges' => $badges,
            'objectives' => [
                ['id' => 'daily_quiz', 'name' => 'Jouer au quiz du jour', 'progress' => (clone $sessions)->whereDate('completed_at', today())->where('mode', 'daily_challenge')->count(), 'target' => 1],
                ['id' => 'weekly_games', 'name' => 'Terminer 5 parties', 'progress' => (clone $sessions)->whereBetween('completed_at', [now()->startOfWeek(), now()->endOfWeek()])->count(), 'target' => 5],
                ['id' => 'friend_challenge', 'name' => 'Terminer un défi entre amis', 'progress' => DB::table('friend_challenges')->where('status', 'completed')->where(fn ($q) => $q->where('challenger_id', $user->id)->orWhere('opponent_id', $user->id))->count(), 'target' => 1],
            ],
        ]]);
    }

    public function invite(Request $request): JsonResponse
    {
        $code = $request->user()->friend_code;
        return response()->json(['success' => true, 'data' => [
            'friend_code' => $code,
            'message' => "Rejoins-moi sur IvoireQuiz ! Ajoute-moi avec mon code ami : {$code}",
        ]]);
    }
}
