<?php

namespace App\Services\Game;

use App\Enums\GameConstants;
use App\Exceptions\Game\InvalidGameSessionException;
use App\Models\GameSession;
use App\Models\RewardTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinishGameSessionService
{
    public function __construct(
        private RewardService $rewards,
        private LifeService $lives,
        private ProgressionService $progression,
        private StreakService $streaks,
        private LeagueService $leagues,
        private ChestService $chests,
        private CollectionService $collections,
        private PassportService $passports,
    ) {}

    public function finish(GameSession $session): GameSession
    {
        return DB::transaction(function () use ($session): GameSession {
            $session = GameSession::query()->with(['user', 'level.city.region', 'gameSessionAnswers'])->lockForUpdate()->findOrFail($session->id);
            if ($session->status !== GameConstants::STATUS_STARTED || $session->started_at?->lt(now()->subHours(GameConstants::MAX_SESSION_HOURS))) { Log::warning('Invalid finish attempt', ['session_id' => $session->id, 'status' => $session->status]); throw new InvalidGameSessionException('This session cannot be finished.'); }
            $answers = $session->gameSessionAnswers;
            if ($answers->count() !== (int) $session->total_questions || $answers->count() > (int) $session->total_questions) { Log::warning('Suspicious finish answer count', ['session_id' => $session->id, 'answers' => $answers->count(), 'expected' => $session->total_questions]); throw new InvalidGameSessionException('All expected questions must be answered before finishing.'); }

            $correct = $answers->where('is_correct', true)->count(); $wrong = $answers->count() - $correct;
            $accuracy = $session->total_questions > 0 ? round($correct / $session->total_questions * 100, 2) : 0;
            $passed = $accuracy >= $session->level->passing_score;
            $xp = $passed ? (int) $session->level->xp_reward + (int) $answers->sum('xp_earned') : 0;
            $coins = $passed ? (int) $session->level->coins_reward : 0;
            $gems = $passed ? (int) $session->level->gems_reward : 0;
            $status = $passed ? GameConstants::STATUS_COMPLETED : GameConstants::STATUS_FAILED;

            $session->update(['status' => $status, 'correct_answers' => $correct, 'wrong_answers' => $wrong, 'accuracy' => $accuracy, 'xp_earned' => $xp, 'coins_earned' => $coins, 'gems_earned' => $gems, 'finished_at' => now()]);
            $user = $session->user; $source = 'game_session';
            $this->rewards->addPoints($user, (int) $session->score, $source, $session->id, 'Game score');
            if ($passed) {
                if ($xp) { $this->rewards->addXp($user, $xp, $source, $session->id, 'Game XP'); }
                if ($coins) { $this->rewards->addCoins($user, $coins, $source, $session->id, 'Game coins'); }
                if ($gems) { $this->rewards->addGems($user, $gems, $source, $session->id, 'Game gems'); }
                $this->progression->completeLevel($user, $session->level, (int) $session->score, $accuracy);
                $this->progression->unlockNextAfterLevel($user, $session->level);
                if ($session->level->is_boss) { $this->progression->completeRegionIfBoss($user, $session->level); $this->passports->stampRegion($user, $session->level->city->region); }
                $this->collections->unlockRandom($user, $session->region_id, $session->city_id, $source, $session->id);
                if ($session->level->is_boss || $accuracy >= 90) { $this->chests->grantChest($user, $session->level->is_boss ? 'gold' : 'bronze', $source, $session->id); }
                if (! RewardTransaction::query()->where('user_id', $user->id)->where('type', 'xp')->where('source_type', 'league_game_session')->where('source_id', $session->id)->exists()) { $this->leagues->addXp($user, $xp); RewardTransaction::query()->create(['user_id' => $user->id, 'type' => 'xp', 'amount' => $xp, 'source_type' => 'league_game_session', 'source_id' => $session->id, 'description' => 'League XP marker']); }
            } else { $this->lives->loseLife($user); }
            $this->streaks->updateAfterGame($user);
            $user->increment('games_played'); if ($passed) { $user->increment('games_won'); }
            return $session->refresh();
        });
    }
}
