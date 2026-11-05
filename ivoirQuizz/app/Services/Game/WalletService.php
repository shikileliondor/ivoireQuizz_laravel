<?php

namespace App\Services\Game;

use App\Exceptions\Game\InsufficientCoinsException;
use App\Models\CoinTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class WalletService
{
    public function debitLocked(User $user, int $amount, string $type, ?Model $reference = null, ?string $idempotencyKey = null): CoinTransaction
    {
        $user = User::query()->lockForUpdate()->findOrFail($user->id);
        if ($amount < 0 || $user->coins < $amount) {
            throw new InsufficientCoinsException('Solde de pièces insuffisant.');
        }

        $before = (int) $user->coins;
        $user->update(['coins' => $before - $amount]);

        return CoinTransaction::query()->create([
            'user_id' => $user->id,
            'type' => $type,
            'amount' => -$amount,
            'balance_before' => $before,
            'balance_after' => $before - $amount,
            'reference_type' => $reference?->getMorphClass(),
            'reference_id' => $reference?->getKey(),
            'idempotency_key' => $idempotencyKey,
        ]);
    }
}
