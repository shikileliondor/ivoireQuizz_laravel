<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Friendship;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    /** Return the all-time total of each player's best validated campaign scores. */
    public function global(Request $request): JsonResponse
    {
        $campaignScores = DB::table('user_level_progress as progress')
            ->join('levels', 'levels.id', '=', 'progress.level_id')
            ->where('progress.is_completed', true)
            ->select('progress.user_id', 'levels.city_id')
            ->selectRaw('SUM(progress.best_score) as campaign_score')
            ->groupBy('progress.user_id', 'levels.city_id');

        $totals = DB::query()
            ->fromSub($campaignScores, 'campaign_scores')
            ->select('user_id')
            ->selectRaw('SUM(campaign_score) as total_score')
            ->groupBy('user_id');

        $answerStats = $this->allTimeCorrectAnswerStats();
        $rows = User::query()
            ->leftJoinSub($totals, 'campaign_totals', 'campaign_totals.user_id', '=', 'users.id')
            ->leftJoinSub($answerStats, 'answer_stats', 'answer_stats.user_id', '=', 'users.id')
            ->select(['users.id', 'users.name', 'users.avatar_id', 'users.games_played'])
            ->selectRaw('COALESCE(campaign_totals.total_score, 0) as score')
            ->selectRaw('COALESCE(answer_stats.correct_answers, 0) as correct_answers')
            ->selectRaw('COALESCE(answer_stats.response_time, 0) as response_time');

        $currentUserRank = $this->rankFor(
            $rows,
            $request->user()->id,
            'COALESCE(campaign_totals.total_score, 0)',
            'COALESCE(answer_stats.correct_answers, 0)',
            'COALESCE(answer_stats.response_time, 0)',
        );
        $leaderboard = (clone $rows)
            ->orderByDesc('score')
            ->orderByDesc('correct_answers')
            ->orderBy('response_time')
            ->orderBy('users.id')
            ->paginate(100);

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $leaderboard,
                'current_user_rank' => $currentUserRank['rank'],
                'current_score' => $currentUserRank['score'],
            ],
            'message' => 'Classement général récupéré.',
        ]);
    }

    /** Points earned this Monday-to-Sunday week, with each logical question counted once. */
    public function weekly(Request $request): JsonResponse
    {
        $weekStart = CarbonImmutable::now()->startOfWeek(CarbonImmutable::MONDAY);
        $nextWeek = $weekStart->addWeek();
        $questionKey = 'COALESCE(questions.source_key, CAST(questions.id AS CHAR))';

        $bestPerQuestion = DB::table('game_session_answers as answers')
            ->join('game_sessions as sessions', 'sessions.id', '=', 'answers.game_session_id')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->whereIn('sessions.status', ['completed', 'failed'])
            ->whereNotNull('sessions.finished_at')
            ->where('sessions.finished_at', '>=', $weekStart)
            ->where('sessions.finished_at', '<', $nextWeek)
            ->where('answers.points_earned', '>', 0)
            ->select('sessions.user_id')
            ->selectRaw("{$questionKey} as question_key")
            ->selectRaw('MAX(answers.points_earned) as question_points')
            ->selectRaw('MIN(answers.response_time) as response_time')
            ->groupBy('sessions.user_id')
            ->groupByRaw($questionKey);

        $weeklyScores = DB::query()
            ->fromSub($bestPerQuestion, 'unique_weekly_answers')
            ->select('user_id')
            ->selectRaw('SUM(question_points) as weekly_points')
            ->selectRaw('COUNT(*) as correct_answers')
            ->selectRaw('SUM(COALESCE(response_time, 0)) as response_time')
            ->groupBy('user_id');

        $rows = User::query()
            ->leftJoinSub($weeklyScores, 'weekly_scores', 'weekly_scores.user_id', '=', 'users.id')
            ->select(['users.id', 'users.name', 'users.avatar_id', 'users.games_played'])
            ->selectRaw('COALESCE(weekly_scores.weekly_points, 0) as score')
            ->selectRaw('COALESCE(weekly_scores.correct_answers, 0) as correct_answers')
            ->selectRaw('COALESCE(weekly_scores.response_time, 0) as response_time');

        $currentUserRank = $this->rankFor(
            $rows,
            $request->user()->id,
            'COALESCE(weekly_scores.weekly_points, 0)',
            'COALESCE(weekly_scores.correct_answers, 0)',
            'COALESCE(weekly_scores.response_time, 0)',
        );
        $leaderboard = (clone $rows)
            ->orderByDesc('score')
            ->orderByDesc('correct_answers')
            ->orderBy('response_time')
            ->orderBy('users.id')
            ->paginate(100);

        return response()->json([
            'success' => true,
            'data' => [
                'leaderboard' => $leaderboard,
                'current_user_rank' => $currentUserRank['rank'],
                'weekly_points' => $currentUserRank['score'],
                'week_start' => $weekStart->toDateString(),
                'week_end' => $nextWeek->subDay()->toDateString(),
            ],
            'message' => 'Classement hebdomadaire récupéré.',
        ]);
    }

    /** Return friends leaderboard including the current user. */
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

        $rankedUsers = $users->map(function (User $user, int $index): array {
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
            'data' => ['leaderboard' => $rankedUsers],
            'message' => 'Classement des amis récupéré.',
        ]);
    }

    /** Return a campaign leaderboard using each player's best score per completed level. */
    public function city(Request $request, City $city): JsonResponse
    {
        $campaignScores = DB::table('user_level_progress as progress')
            ->join('levels', 'levels.id', '=', 'progress.level_id')
            ->where('levels.city_id', $city->id)
            ->where('progress.is_completed', true)
            ->select('progress.user_id')
            ->selectRaw('SUM(progress.best_score) as campaign_score')
            ->groupBy('progress.user_id');

        $leaderboard = User::query()
            ->joinSub(clone $campaignScores, 'campaign_scores', 'campaign_scores.user_id', '=', 'users.id')
            ->select(['users.id', 'users.name', 'users.avatar_id', 'users.games_played', 'campaign_scores.campaign_score'])
            ->orderByDesc('campaign_scores.campaign_score')
            ->orderBy('users.id')
            ->paginate(100);

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

    private function allTimeCorrectAnswerStats(): QueryBuilder
    {
        $questionKey = 'COALESCE(questions.source_key, CAST(questions.id AS CHAR))';
        $bestPerQuestion = DB::table('game_session_answers as answers')
            ->join('game_sessions as sessions', 'sessions.id', '=', 'answers.game_session_id')
            ->join('questions', 'questions.id', '=', 'answers.question_id')
            ->whereIn('sessions.status', ['completed', 'failed'])
            ->where('answers.is_correct', true)
            ->select('sessions.user_id')
            ->selectRaw("{$questionKey} as question_key")
            ->selectRaw('MIN(answers.response_time) as response_time')
            ->groupBy('sessions.user_id')
            ->groupByRaw($questionKey);

        return DB::query()
            ->fromSub($bestPerQuestion, 'unique_correct_answers')
            ->select('user_id')
            ->selectRaw('COUNT(*) as correct_answers')
            ->selectRaw('SUM(COALESCE(response_time, 0)) as response_time')
            ->groupBy('user_id');
    }

    /** @return array{rank: int, score: int} */
    private function rankFor(
        EloquentBuilder $rows,
        int $userId,
        string $scoreExpression,
        string $correctAnswersExpression,
        string $responseTimeExpression,
    ): array
    {
        $current = (clone $rows)->where('users.id', $userId)->first();
        if (! $current) {
            return ['rank' => 0, 'score' => 0];
        }

        $ahead = (clone $rows)->where(function (EloquentBuilder $query) use ($current, $scoreExpression, $correctAnswersExpression, $responseTimeExpression, $userId): void {
            $query->whereRaw("{$scoreExpression} > ?", [$current->score])
                ->orWhere(function (EloquentBuilder $query) use ($current, $scoreExpression, $correctAnswersExpression, $responseTimeExpression, $userId): void {
                    $query->whereRaw("{$scoreExpression} = ?", [$current->score])
                        ->where(function (EloquentBuilder $query) use ($current, $correctAnswersExpression, $responseTimeExpression, $userId): void {
                            $query->whereRaw("{$correctAnswersExpression} > ?", [$current->correct_answers])
                                ->orWhere(function (EloquentBuilder $query) use ($current, $correctAnswersExpression, $responseTimeExpression, $userId): void {
                                    $query->whereRaw("{$correctAnswersExpression} = ?", [$current->correct_answers])
                                        ->where(function (EloquentBuilder $query) use ($current, $responseTimeExpression, $userId): void {
                                            $query->whereRaw("{$responseTimeExpression} < ?", [$current->response_time])
                                                ->orWhere(function (EloquentBuilder $query) use ($current, $responseTimeExpression, $userId): void {
                                                    $query->whereRaw("{$responseTimeExpression} = ?", [$current->response_time])
                                                        ->where('users.id', '<', $userId);
                                                });
                                        });
                                });
                        });
                });
        });

        return [
            'rank' => $ahead->count() + 1,
            'score' => (int) $current->score,
        ];
    }
}
