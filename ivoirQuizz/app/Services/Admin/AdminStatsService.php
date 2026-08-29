<?php

namespace App\Services\Admin;

use App\Models\Chapter;
use App\Models\GameSession;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Region;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminStatsService
{
    public function dashboard(): array
    {
        return [
            'content' => $this->content(),
            'players' => $this->players(),
            'gameplay' => $this->gameplay(),
            'moderation' => $this->moderation(),
        ];
    }

    private function content(): array
    {
        $incompleteLevels = Level::query()
            ->where('is_active', true)
            ->whereIn('node_type', ['level', 'review', 'boss'])
            ->missingQuestions()
            ->count();

        return [
            'regions' => Region::query()->count(),
            'chapters' => Chapter::query()->count(),
            'levels' => Level::query()->count(),
            'questions' => Question::query()->count(),
            'active_questions' => Question::query()->where('is_active', true)->count(),
            // Levels the player cannot start because they draw more questions
            // than they own: the single blocking content metric.
            'incomplete_levels' => $incompleteLevels,
        ];
    }

    private function players(): array
    {
        $today = Carbon::today();

        return [
            'total' => User::query()->where('role', 'player')->count(),
            'new_this_week' => User::query()->where('role', 'player')->where('created_at', '>=', $today->copy()->subDays(7))->count(),
            'active_today' => User::query()->where('last_login_at', '>=', $today)->count(),
            'active_this_week' => User::query()->where('last_login_at', '>=', $today->copy()->subDays(7))->count(),
        ];
    }

    private function gameplay(): array
    {
        $today = Carbon::today();

        $sessions = GameSession::query()
            ->where('created_at', '>=', $today->copy()->subDays(7))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $finished = ($sessions['completed'] ?? 0) + ($sessions['failed'] ?? 0);

        return [
            'sessions_today' => GameSession::query()->where('created_at', '>=', $today)->count(),
            'sessions_this_week' => (int) $sessions->sum(),
            'completed_this_week' => (int) ($sessions['completed'] ?? 0),
            'failed_this_week' => (int) ($sessions['failed'] ?? 0),
            'abandoned_this_week' => (int) ($sessions['abandoned'] ?? 0),
            'success_rate' => $finished > 0 ? round((($sessions['completed'] ?? 0) / $finished) * 100, 1) : null,
        ];
    }

    private function moderation(): array
    {
        return [
            'pending_reports' => QuestionReport::query()->where('status', 'pending')->count(),
            'reports_this_week' => QuestionReport::query()->where('created_at', '>=', Carbon::today()->subDays(7))->count(),
        ];
    }

    /**
     * Difficulty audit. A question everybody fails is usually badly worded, and
     * one everybody passes teaches nothing — both need the editor's attention.
     *
     * @return array{hardest: array, easiest: array}
     */
    public function questionBalance(int $minAnswers = 20, int $limit = 20): array
    {
        $base = fn () => Question::query()
            ->select('questions.*')
            ->join('game_session_answers', 'game_session_answers.question_id', '=', 'questions.id')
            ->where('questions.is_active', true)
            ->groupBy('questions.id')
            ->havingRaw('count(game_session_answers.id) >= ?', [$minAnswers])
            ->selectRaw('count(game_session_answers.id) as times_answered')
            ->selectRaw('sum(case when game_session_answers.is_correct = 1 then 1 else 0 end) as times_correct')
            ->selectRaw('(sum(case when game_session_answers.is_correct = 1 then 1 else 0 end) / count(game_session_answers.id)) * 100 as success_rate');

        return [
            'hardest' => $base()->orderBy('success_rate')->limit($limit)->get(),
            'easiest' => $base()->orderByDesc('success_rate')->limit($limit)->get(),
        ];
    }

    /**
     * Where players stop. A level with a high abandon rate is a difficulty
     * spike or a broken question set, not a player problem.
     */
    public function levelFunnel(int $limit = 30): array
    {
        return DB::table('game_sessions')
            ->join('levels', 'levels.id', '=', 'game_sessions.level_id')
            ->whereNotNull('game_sessions.level_id')
            ->groupBy('levels.id', 'levels.title')
            ->select('levels.id', 'levels.title')
            ->selectRaw('count(*) as attempts')
            ->selectRaw("sum(case when game_sessions.status = 'completed' then 1 else 0 end) as completed")
            ->selectRaw("sum(case when game_sessions.status = 'abandoned' then 1 else 0 end) as abandoned")
            ->selectRaw('round(avg(game_sessions.accuracy), 1) as avg_accuracy')
            ->havingRaw('count(*) >= 5')
            ->orderByDesc('abandoned')
            ->limit($limit)
            ->get()
            ->all();
    }
}
