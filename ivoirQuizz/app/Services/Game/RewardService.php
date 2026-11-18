<?php

namespace App\Services\Game;

use App\Enums\GameConstants;
use App\Exceptions\Game\RewardException;
use App\Models\GameplaySetting;
use App\Models\RewardTransaction;
use App\Models\User;
use App\Services\User\PlayerLevelService;
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

    /** Une partie plus courte ne rapporte pas de pièces : sinon, une question réussie suffirait. */
    public const WIN_MIN_QUESTIONS = 5;

    /** Sources des pièces de victoire hors campagne, comptées dans la limite du jour. */
    public const WIN_COIN_SOURCES = ['quick_game', 'friend_challenge'];

    /**
     * Pièces d'une victoire en lancement rapide ou en défi, dans la limite quotidienne réglée dans l'admin :
     * le résultat est envoyé par l'app, la limite évite qu'une partie rejouée en boucle vide l'économie.
     *
     * @return int pièces réellement données
     */
    public function addWinCoins(User $user, string $setting, string $sourceType, int $sourceId): int
    {
        $amount = (int) GameplaySetting::valueOf($setting);
        $wonToday = RewardTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', GameConstants::REWARD_COIN)
            ->whereIn('source_type', self::WIN_COIN_SOURCES)
            ->where('created_at', '>=', now()->startOfDay())
            ->count();

        if ($amount <= 0 || $wonToday >= (int) GameplaySetting::valueOf('win_coins_daily_limit')) {
            return 0;
        }

        $this->addCoins($user, $amount, $sourceType, $sourceId, 'Win coins');

        return $amount;
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
            $lockedUser->increment($column, $amount);
            if ($column === 'xp_total') {
                $this->levels->sync($lockedUser->refresh());
            }
            $this->record($lockedUser, $type, $amount, $sourceType, $sourceId, $description);
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
