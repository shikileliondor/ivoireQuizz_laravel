<?php

namespace App\Services\Game;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\LeagueSeason;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class LeagueService
{
    public function getCurrentSeasonForUser(User $user): LeagueSeason
    {
        return DB::transaction(function () use ($user): LeagueSeason {
            $now = Carbon::now();
            $league = League::query()->firstOrCreate(['slug' => 'bronze'], ['name' => 'Bronze', 'rank_order' => 1, 'is_active' => true]);
            $season = LeagueSeason::query()->where('status', 'active')->where('starts_at', '<=', $now)->where('ends_at', '>=', $now)->first()
                ?? LeagueSeason::query()->create(['league_id' => $league->id, 'starts_at' => $now->copy()->startOfWeek(), 'ends_at' => $now->copy()->endOfWeek(), 'status' => 'active']);
            LeagueMember::query()->firstOrCreate(['league_season_id' => $season->id, 'user_id' => $user->id], ['xp_earned' => 0]);
            return $season;
        });
    }

    public function addXp(User $user, int $xp): void
    {
        if ($xp <= 0) { return; }
        DB::transaction(function () use ($user, $xp): void {
            $season = $this->getCurrentSeasonForUser($user);
            LeagueMember::query()->where('league_season_id', $season->id)->where('user_id', $user->id)->lockForUpdate()->increment('xp_earned', $xp);
            $this->incrementWeeklyXpInRedis($season, $user, $xp);
            $this->refreshRanks($season);
        });
    }

    public function incrementWeeklyXpInRedis(LeagueSeason $season, User $user, int $xp): float
    {
        if ($xp <= 0) { return 0.0; }

        return (float) Redis::zincrby($this->rankingRedisKey($season), $xp, (string) $user->id);
    }

    public function getTopPlayersFromRedis(LeagueSeason $season, int $limit = 50): array
    {
        $limit = max(1, $limit);
        $rawPlayers = Redis::zrevrange($this->rankingRedisKey($season), 0, $limit - 1, ['withscores' => true]);

        $rank = 1;
        $players = [];
        foreach ($rawPlayers as $userId => $score) {
            $players[] = [
                'user_id' => (int) $userId,
                'xp_earned' => (int) $score,
                'rank' => $rank++,
            ];
        }

        return $players;
    }

    public function refreshRanks(LeagueSeason $season): void
    {
        $members = $season->members()->orderByDesc('xp_earned')->orderBy('updated_at')->get();
        foreach ($members as $index => $member) { $member->update(['rank' => $index + 1]); }
        $this->syncRankingToRedis($season);
    }

    public function refreshActiveSeasonRanks(): int
    {
        return LeagueSeason::query()
            ->where('status', 'active')
            ->get()
            ->sum(function (LeagueSeason $season): int {
                $this->refreshRanks($season);

                return 1;
            });
    }

    public function rankingRedisKey(LeagueSeason $season): string
    {
        return "league:{$season->league_id}:season:{$season->id}:ranking";
    }

    private function syncRankingToRedis(LeagueSeason $season): void
    {
        $key = $this->rankingRedisKey($season);
        Redis::del($key);

        $season->members()
            ->where('xp_earned', '>', 0)
            ->chunkById(500, function ($members) use ($key): void {
                foreach ($members as $member) {
                    Redis::zadd($key, (int) $member->xp_earned, (string) $member->user_id);
                }
            });
    }
}
