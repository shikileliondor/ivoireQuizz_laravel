<?php

use App\Services\Game\GameCacheService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Les étapes des campagnes étaient importées avec 0 pièce : une victoire ne rapportait rien.
 * Seules les étapes encore à 0 sont corrigées ; une valeur choisie dans l'admin est gardée.
 */
return new class extends Migration
{
    private const COINS_BY_ORDER = [1 => 30, 2 => 40, 3 => 50, 4 => 60];

    private const BOSS_COINS = 100;

    public function up(): void
    {
        DB::table('levels')->where('coins_reward', 0)->where('is_boss', true)->update(['coins_reward' => self::BOSS_COINS]);

        foreach (self::COINS_BY_ORDER as $order => $coins) {
            DB::table('levels')->where('coins_reward', 0)->where('is_boss', false)->where('order', $order)->update(['coins_reward' => $coins]);
        }

        DB::table('levels')->where('coins_reward', 0)->where('is_boss', false)->where('order', '>', 4)->update(['coins_reward' => self::COINS_BY_ORDER[4]]);

        // La carte met les récompenses en cache.
        app(GameCacheService::class)->clearRegionsMapCache();
    }

    public function down(): void
    {
        // Rien à annuler : remettre 0 pièce recréerait le bug.
    }
};
