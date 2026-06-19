<?php

namespace App\Services\Game;

use App\Models\League;
use App\Models\LeagueMember;
use App\Models\LeagueSeason;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
            $this->refreshRanks($season);
        });
    }

    public function refreshRanks(LeagueSeason $season): void
    {
        $members = $season->members()->orderByDesc('xp_earned')->orderBy('updated_at')->get();
        foreach ($members as $index => $member) { $member->update(['rank' => $index + 1]); }
    }
}
