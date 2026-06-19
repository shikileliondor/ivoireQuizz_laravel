<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'order' => 'integer',
        'required_xp' => 'integer',
        'questions_count' => 'integer',
        'passing_score' => 'integer',
        'xp_reward' => 'integer',
        'coins_reward' => 'integer',
        'gems_reward' => 'integer',
        'is_boss' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(Question::class);
    }

    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    public function userLevelProgress(): HasMany
    {
        return $this->hasMany(UserLevelProgress::class);
    }
}
