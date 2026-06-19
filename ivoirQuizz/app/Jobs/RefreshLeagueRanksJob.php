<?php

namespace App\Jobs;

use App\Models\LeagueSeason;
use App\Services\Game\LeagueService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshLeagueRanksJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public ?int $leagueSeasonId = null,
    ) {}

    public function handle(LeagueService $leagues): void
    {
        if ($this->leagueSeasonId) {
            $season = LeagueSeason::query()->find($this->leagueSeasonId);
            if ($season) {
                $leagues->refreshRanks($season);
            }

            return;
        }

        $leagues->refreshActiveSeasonRanks();
    }
}
