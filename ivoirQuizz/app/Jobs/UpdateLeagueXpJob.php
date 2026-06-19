<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\Game\LeagueService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class UpdateLeagueXpJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $userId,
        public int $xp,
    ) {}

    public function handle(LeagueService $leagues): void
    {
        $user = User::query()->find($this->userId);

        if (! $user || $this->xp <= 0) {
            return;
        }

        $leagues->addXp($user, $this->xp);
    }
}
