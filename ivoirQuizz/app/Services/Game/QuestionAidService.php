<?php

namespace App\Services\Game;

use App\Enums\GameConstants;
use App\Exceptions\Game\GameActionConflictException;
use App\Exceptions\Game\InvalidGameSessionException;
use App\Models\GameQuestionAction;
use App\Models\GameSession;
use App\Models\GameSessionAnswer;
use App\Models\GameplaySetting;
use App\Models\Question;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class QuestionAidService
{
    public function __construct(private readonly WalletService $wallet) {}

    public function hint(User $user, GameSession $session, Question $question): array
    {
        return DB::transaction(function () use ($user, $session, $question): array {
            [$session, $config] = $this->assertAvailable($user, $session, $question, 'hint');
            if (blank($question->hint)) throw new GameActionConflictException('Aucun indice disponible pour cette question.');

            $cost = (int) $config['hint_cost'];
            $action = GameQuestionAction::query()->create([
                'game_session_id' => $session->id, 'question_id' => $question->id, 'user_id' => $user->id,
                'type' => 'hint', 'cost' => $cost,
                'paused_until' => $session->mode === GameConstants::MODE_CHRONO && $config['chrono_pause_on_hint']
                    ? now()->addSeconds((int) $config['chrono_hint_pause_seconds']) : null,
            ]);
            $transaction = $this->wallet->debitLocked($user, $cost, 'hint_purchase', $action);

            return ['hint' => $question->hint, 'cost' => $cost, 'coins_balance' => $transaction->balance_after,
                'timer_paused_until' => $action->paused_until?->toISOString()];
        });
    }

    public function skip(User $user, GameSession $session, Question $question): array
    {
        return DB::transaction(function () use ($user, $session, $question): array {
            [$session, $config] = $this->assertAvailable($user, $session, $question, 'skip');
            $cost = (int) $config['skip_cost'];
            $action = GameQuestionAction::query()->create([
                'game_session_id' => $session->id, 'question_id' => $question->id, 'user_id' => $user->id,
                'type' => 'skip', 'cost' => $cost,
            ]);
            $transaction = $this->wallet->debitLocked($user, $cost, 'question_skip', $action);
            GameSessionAnswer::query()->create([
                'game_session_id' => $session->id, 'question_id' => $question->id, 'answer_id' => null,
                'is_correct' => false, 'status' => 'skipped', 'response_time' => 0, 'points_earned' => 0, 'xp_earned' => 0,
            ]);

            return ['status' => 'skipped', 'cost' => $cost, 'coins_balance' => $transaction->balance_after];
        });
    }

    private function assertAvailable(User $user, GameSession $session, Question $question, string $type): array
    {
        $session = GameSession::query()->lockForUpdate()->findOrFail($session->id);
        if ($session->user_id !== $user->id || $session->status !== GameConstants::STATUS_STARTED) {
            throw new InvalidGameSessionException('Cette session n’est pas active.');
        }
        if ($question->level_id !== null && $session->level_id !== null && $question->level_id !== $session->level_id) {
            throw new InvalidGameSessionException('Cette question n’appartient pas à la session.');
        }
        if ($session->questions()->exists() && ! $session->questions()->whereKey($question->id)->exists()) {
            throw new InvalidGameSessionException('Cette question n’appartient pas au tirage de la session.');
        }
        if (GameSessionAnswer::query()->where('game_session_id', $session->id)->where('question_id', $question->id)->exists()) {
            throw new GameActionConflictException('Cette question est déjà terminée.');
        }
        if (GameQuestionAction::query()->where('game_session_id', $session->id)->where('question_id', $question->id)->where('type', $type)->exists()) {
            throw new GameActionConflictException('Cette aide a déjà été utilisée.');
        }
        $config = $session->gameplay_config ?: GameplaySetting::snapshot();
        if (! ($config[$type.'_enabled'] ?? false)) throw new GameActionConflictException('Cette aide est désactivée.');
        return [$session, $config];
    }
}
