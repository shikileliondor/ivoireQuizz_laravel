<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'game_sessions';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'mode',
        'score',
        'bonus_score',
        'base_score',
        'time_bonus_score',
        'streak_bonus_score',
        'perfect_bonus_score',
        'total_score',
        'correct_answers',
        'max_streak',
        'duration_seconds',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'score' => 'integer',
        'bonus_score' => 'integer',
        'base_score' => 'integer',
        'time_bonus_score' => 'integer',
        'streak_bonus_score' => 'integer',
        'perfect_bonus_score' => 'integer',
        'total_score' => 'integer',
        'correct_answers' => 'integer',
        'max_streak' => 'integer',
        'duration_seconds' => 'integer',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user owning this game session.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the category used by this game session.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withDefault();
    }

    /**
     * Get all answers for this game session.
     */
    public function answers(): HasMany
    {
        return $this->hasMany(SessionAnswer::class, 'session_id');
    }

    /**
     * Scope a query to only completed game sessions.
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->whereNotNull('completed_at');
    }

    /**
     * Calculate and persist the total score.
     */
    public function calculateTotalScore(): int
    {
        $this->total_score = max(0, (int) $this->score + (int) $this->bonus_score);
        $this->save();

        return $this->total_score;
    }
}
