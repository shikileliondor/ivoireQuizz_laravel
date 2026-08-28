<?php

namespace App\Services\User;

use App\Models\User;
use InvalidArgumentException;

class PlayerLevelService
{
    /**
     * @return array<int, int>
     */
    public function thresholds(): array
    {
        $thresholds = config('progression.levels', [1 => 0]);
        ksort($thresholds, SORT_NUMERIC);

        if ($thresholds === [] || (int) array_key_first($thresholds) !== 1 || (int) reset($thresholds) !== 0) {
            throw new InvalidArgumentException('Player progression must start at level 1 with 0 XP.');
        }

        return array_map('intval', $thresholds);
    }

    public function levelForXp(int $xp): int
    {
        $level = 1;

        foreach ($this->thresholds() as $candidate => $requiredXp) {
            if ($xp < $requiredXp) {
                break;
            }

            $level = (int) $candidate;
        }

        return $level;
    }

    public function sync(User $user): bool
    {
        $level = $this->levelForXp(max(0, (int) $user->xp_total));

        if ((int) $user->current_level === $level) {
            return false;
        }

        $user->forceFill(['current_level' => $level])->save();

        return true;
    }

    /**
     * @return array{level: int, current_xp: int, level_start_xp: int, next_level_xp: int|null, progress_percent: float}
     */
    public function progress(User $user): array
    {
        $xp = max(0, (int) $user->xp_total);
        $level = $this->levelForXp($xp);
        $thresholds = $this->thresholds();
        $start = $thresholds[$level] ?? 0;
        $next = $thresholds[$level + 1] ?? null;
        $percent = $next === null ? 100.0 : round(min(100, (($xp - $start) / max(1, $next - $start)) * 100), 2);

        return [
            'level' => $level,
            'current_xp' => $xp,
            'level_start_xp' => $start,
            'next_level_xp' => $next,
            'progress_percent' => $percent,
        ];
    }
}
