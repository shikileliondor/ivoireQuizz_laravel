<?php

namespace App\Enums;

final class GameConstants
{
    public const MODE_LEVEL = 'level';
    public const MODE_BOSS = 'boss';
    public const MODE_DAILY_CHALLENGE = 'daily_challenge';
    public const MODE_MIXED = 'mixed';

    public const STATUS_STARTED = 'started';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_ABANDONED = 'abandoned';
    public const STATUS_FAILED = 'failed';

    public const CHEST_AVAILABLE = 'available';
    public const CHEST_OPENED = 'opened';

    public const REWARD_XP = 'xp';
    public const REWARD_POINT = 'point';
    public const REWARD_COIN = 'coin';
    public const REWARD_GEM = 'gem';
    public const REWARD_LIFE = 'life';

    public const DIFFICULTY_EASY = 'easy';
    public const DIFFICULTY_MEDIUM = 'medium';
    public const DIFFICULTY_HARD = 'hard';
    public const DIFFICULTY_EXPERT = 'expert';

    public const MAX_LIVES = 5;
    public const LIFE_REGENERATION_MINUTES = 30;
    public const MAX_SESSION_HOURS = 6;

    public const MODES = [self::MODE_LEVEL, self::MODE_BOSS, self::MODE_DAILY_CHALLENGE, self::MODE_MIXED];
}
