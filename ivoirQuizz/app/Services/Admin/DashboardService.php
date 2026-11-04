<?php

namespace App\Services\Admin;

use App\Enums\UserStatus;
use App\Models\City;
use App\Models\GameSession;
use App\Models\Level;
use App\Models\Question;
use App\Models\QuestionReport;
use App\Models\Region;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardService
{
    public const CHART_DAYS = 14;

    public function stats(): array
    {
        $today = Carbon::today();
        $weekAgo = $today->copy()->subDays(6);

        $weekSessions = GameSession::query()->where('created_at', '>=', $weekAgo);
        $weekFinished = (clone $weekSessions)->whereIn('status', ['completed', 'failed']);

        return [
            'players' => User::query()->count(),
            'new_players_week' => User::query()->where('created_at', '>=', $weekAgo)->count(),
            'active_today' => User::query()->whereDate('last_activity_date', $today)->count(),
            'suspended' => User::query()->where('status', '!=', UserStatus::Active->value)->count(),
            'sessions_today' => GameSession::query()->where('created_at', '>=', $today)->count(),
            'sessions_week' => (clone $weekSessions)->count(),
            'success_rate_week' => $this->percentage(
                (clone $weekSessions)->where('status', 'completed')->count(),
                (clone $weekFinished)->count(),
            ),
            'accuracy_week' => round((float) (clone $weekFinished)->avg('accuracy'), 1),
            'questions_active' => Question::query()->where('is_active', true)->count(),
            'questions_total' => Question::query()->count(),
            'regions' => Region::query()->count(),
            'cities' => City::query()->count(),
            'levels' => Level::query()->count(),
            'pending_reports' => QuestionReport::query()->where('status', 'pending')->count(),
        ];
    }

    /**
     * Parties et inscriptions par jour sur les derniers jours, jours vides inclus.
     *
     * @return Collection<int, array{date: Carbon, sessions: int, signups: int}>
     */
    public function dailyActivity(): Collection
    {
        $start = Carbon::today()->subDays(self::CHART_DAYS - 1);

        $sessions = GameSession::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $signups = User::query()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, COUNT(*) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        return collect(range(0, self::CHART_DAYS - 1))->map(function (int $offset) use ($start, $sessions, $signups): array {
            $date = $start->copy()->addDays($offset);
            $key = $date->toDateString();

            return [
                'date' => $date,
                'sessions' => (int) ($sessions[$key] ?? 0),
                'signups' => (int) ($signups[$key] ?? 0),
            ];
        });
    }

    public function latestPlayers(int $limit = 6): Collection
    {
        return User::query()->latest()->limit($limit)->get();
    }

    public function topPlayers(int $limit = 6): Collection
    {
        return User::query()
            ->where('status', UserStatus::Active->value)
            ->orderByDesc('xp_total')
            ->limit($limit)
            ->get();
    }

    public function pendingReports(int $limit = 6): Collection
    {
        return QuestionReport::query()
            ->with(['user', 'question'])
            ->where('status', 'pending')
            ->latest()
            ->limit($limit)
            ->get();
    }

    private function percentage(int $part, int $total): float
    {
        return $total === 0 ? 0.0 : round($part * 100 / $total, 1);
    }
}
