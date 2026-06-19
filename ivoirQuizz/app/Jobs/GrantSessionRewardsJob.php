<?php

namespace App\Jobs;

use App\Models\GameSession;
use App\Services\Game\FinishGameSessionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GrantSessionRewardsJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $gameSessionId,
    ) {}

    public function handle(FinishGameSessionService $sessions): void
    {
        $session = GameSession::query()->find($this->gameSessionId);

        if (! $session) {
            return;
        }

        $sessions->finish($session);
    }
}
