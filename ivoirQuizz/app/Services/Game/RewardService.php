<?php

namespace App\Services\Game;

use App\Enums\GameConstants;
use App\Exceptions\Game\RewardException;
use App\Models\GameplaySetting;
use App\Models\RewardTransaction;
use App\Models\User;
use App\Services\User\PlayerLevelService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class RewardService
{
    public function __construct(private PlayerLevelService $levels) {}

    public function addXp(User $user, int $amount, ?string $sourceType = null, ?int $sourceId = null, ?string $description = null): void
    {
        $this->apply($user, GameConstants::REWARD_XP, $amount, 'xp_total', $sourceType, $sourceId, $description);
    }

    public function addPoints(User $user, int $amount, ?string $sourceType = null, ?int $sourceId = null, ?string $description = null): void
    {
        $this->apply($user, GameConstants::REWARD_POINT, $amount, 'total_score', $sourceType, $sourceId, $description);
    }

    public function addCoins(User $user, int $amount, ?string $sourceType = null, ?int $sourceId = null, ?string $description = null): void
    {
        $this->apply($user, GameConstants::REWARD_COIN, $amount, 'coins', $sourceType, $sourceId, $description);
    }

    /** Taux de bonnes réponses d'une partie gagnée hors campagne, comme le seuil des étapes. */
    public const WIN_ACCURACY = 70;

    /** Une partie plus courte n'a ni bonus de victoire ni bonus sans-faute : sinon, une question réussie suffirait. */
    public const WIN_MIN_QUESTIONS = 5;

    /** Pièces des parties hors campagne, comptées dans la limite du jour. */
    public const CAPPED_COIN_SOURCES = ['quick_game', 'friend_challenge'];

    /** Pièces d'une partie terminée : une par bonne réponse, plus le bonus de victoire et le bonus sans-faute. */
    public function gameCoins(int $correct, int $total, int $winBonus): int
    {
        $coins = max(0, $correct) * (int) GameplaySetting::valueOf('coins_per_correct') + max(0, $winBonus);
        if ($total >= self::WIN_MIN_QUESTIONS && $correct === $total) {
            $coins += (int) GameplaySetting::valueOf('perfect_game_coins');
        }

        return $coins;
    }

    /**
     * Pièces d'une partie en lancement rapide ou en défi, dans la limite quotidienne réglée dans l'admin :
     * le résultat est envoyé par l'app, la limite évite qu'une partie rejouée en boucle vide l'économie.
     *
     * @return int pièces réellement données
     */
    public function addCappedGameCoins(User $user, int $amount, string $sourceType, int $sourceId): int
    {
        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId): int {
            User::query()->lockForUpdate()->findOrFail($user->id);
            $paidToday = $this->coinsToday($user)->whereIn('source_type', self::CAPPED_COIN_SOURCES)->count();
            if ($amount <= 0 || $paidToday >= (int) GameplaySetting::valueOf('win_coins_daily_limit')) {
                return 0;
            }
            $this->addCoins($user, $amount, $sourceType, $sourceId, 'Game coins');

            return $amount;
        });
    }

    /** Bonus versé une seule fois par jour pour cette source (quiz du jour). */
    public function addDailyCoins(User $user, string $setting, string $sourceType, ?int $sourceId = null): int
    {
        return $this->addOncePerDay($user, (int) GameplaySetting::valueOf($setting), $sourceType, $sourceId);
    }

    /** Première partie du jour : bonus par jour de série, plafonné. */
    public function addStreakCoins(User $user, int $streakDays): int
    {
        $amount = min(
            (int) GameplaySetting::valueOf('streak_coins_max'),
            max(1, $streakDays) * (int) GameplaySetting::valueOf('streak_coins_per_day'),
        );

        return $this->addOncePerDay($user, $amount, 'daily_streak', null);
    }

    private function addOncePerDay(User $user, int $amount, string $sourceType, ?int $sourceId): int
    {
        return DB::transaction(function () use ($user, $amount, $sourceType, $sourceId): int {
            User::query()->lockForUpdate()->findOrFail($user->id);
            if ($amount <= 0 || $this->coinsToday($user)->where('source_type', $sourceType)->exists()) {
                return 0;
            }
            $this->addCoins($user, $amount, $sourceType, $sourceId, 'Daily coins');

            return $amount;
        });
    }

    /** @return Builder<RewardTransaction> */
    private function coinsToday(User $user): Builder
    {
        return RewardTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', GameConstants::REWARD_COIN)
            ->where('created_at', '>=', now()->startOfDay());
    }

    public function addGems(User $user, int $amount, ?string $sourceType = null, ?int $sourceId = null, ?string $description = null): void
    {
        $this->apply($user, GameConstants::REWARD_GEM, $amount, 'gems', $sourceType, $sourceId, $description);
    }

    public function addLife(User $user, int $amount, ?string $sourceType = null, ?int $sourceId = null, ?string $description = null): void
    {
        $this->applyLife($user, abs($amount), $sourceType, $sourceId, $description);
    }

    public function removeLife(User $user, int $amount = 1, ?string $sourceType = null, ?int $sourceId = null, ?string $description = null): void
    {
        $this->applyLife($user, -abs($amount), $sourceType, $sourceId, $description);
    }

    private function apply(User $user, string $type, int $amount, string $column, ?string $sourceType, ?int $sourceId, ?string $description): void
    {
        if ($amount < 0) {
            throw new RewardException('Reward amount cannot be negative for add operations.');
        }

        DB::transaction(function () use ($user, $type, $amount, $column, $sourceType, $sourceId, $description): void {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $levelBefore = (int) $lockedUser->current_level;
            $lockedUser->increment($column, $amount);
            if ($column === 'xp_total') {
                $this->levels->sync($lockedUser->refresh());
            }
            $this->record($lockedUser, $type, $amount, $sourceType, $sourceId, $description);

            // Montée de niveau du joueur : des pièces par niveau gagné.
            $levelsGained = $column === 'xp_total' ? (int) $lockedUser->current_level - $levelBefore : 0;
            $levelUpCoins = $levelsGained * (int) GameplaySetting::valueOf('level_up_coins');
            if ($levelUpCoins > 0) {
                $this->apply($lockedUser, GameConstants::REWARD_COIN, $levelUpCoins, 'coins', 'player_level', (int) $lockedUser->current_level, 'Level up coins');
            }
            $user->refresh();
        });
    }

    private function applyLife(User $user, int $amount, ?string $sourceType, ?int $sourceId, ?string $description): void
    {
        DB::transaction(function () use ($user, $amount, $sourceType, $sourceId, $description): void {
            if ($amount >= 0) {
                app(LifeService::class)->addLife($user, $amount);
            } else {
                app(LifeService::class)->loseLife($user, abs($amount));
            }

            $this->record($user, GameConstants::REWARD_LIFE, $amount, $sourceType, $sourceId, $description);
        });
    }

    private function record(User $user, string $type, int $amount, ?string $sourceType, ?int $sourceId, ?string $description): void
    {
        RewardTransaction::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => $amount,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'description' => $description,
        ]);
    }
}
