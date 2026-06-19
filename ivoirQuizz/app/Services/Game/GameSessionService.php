<?php

namespace App\Services\Game;

use App\Enums\GameConstants;
use App\Exceptions\Game\InvalidGameSessionException;
use App\Exceptions\Game\LevelLockedException;
use App\Exceptions\Game\NotEnoughLivesException;
use App\Models\GameSession;
use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class GameSessionService
{
    public function __construct(private LifeService $lifeService, private ProgressionService $progressionService) {}

    public function start(User $user, Level $level, string $mode = GameConstants::MODE_LEVEL): GameSession
    {
        if (! in_array($mode, GameConstants::MODES, true)) { throw new InvalidArgumentException('Unsupported game mode.'); }
        if (! $this->lifeService->canPlay($user)) { throw new NotEnoughLivesException('Not enough lives to start a game.'); }
        if (! $this->progressionService->isLevelUnlocked($user, $level)) { Log::warning('Locked level start attempt', ['user_id' => $user->id, 'level_id' => $level->id]); throw new LevelLockedException('This level is locked.'); }

        return DB::transaction(function () use ($user, $level, $mode): GameSession {
            $level->loadMissing('city.region');
            $total = $level->questions()->where('is_active', true)->count();
            return GameSession::query()->create([
                'user_id' => $user->id,
                'region_id' => $level->city->region_id,
                'city_id' => $level->city_id,
                'level_id' => $level->id,
                'mode' => $level->is_boss ? GameConstants::MODE_BOSS : $mode,
                'status' => GameConstants::STATUS_STARTED,
                'total_questions' => $total,
                'started_at' => now(),
            ]);
        });
    }

    public function getQuestionsForSession(GameSession $session): Collection
    {
        $this->assertPlayable($session);
        return $session->level->questions()->where('is_active', true)->with(['answers' => fn ($q) => $q->orderBy('order')])->inRandomOrder()->limit($session->total_questions)->get();
    }

    public function abandon(GameSession $session): GameSession
    {
        $this->assertPlayable($session);
        $session->update(['status' => GameConstants::STATUS_ABANDONED, 'finished_at' => now()]);
        return $session->refresh();
    }

    private function assertPlayable(GameSession $session): void
    {
        if ($session->status !== GameConstants::STATUS_STARTED || ! $session->level_id || $session->started_at?->lt(now()->subHours(GameConstants::MAX_SESSION_HOURS))) {
            Log::warning('Invalid game session access', ['session_id' => $session->id, 'status' => $session->status]);
            throw new InvalidGameSessionException('The game session is not playable.');
        }
    }
}
