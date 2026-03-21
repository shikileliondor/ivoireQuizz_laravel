<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SessionAnswer extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'session_answers';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'session_id',
        'question_id',
        'selected_option_id',
        'is_correct',
        'response_time_seconds',
        'points_earned',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_correct' => 'boolean',
        'response_time_seconds' => 'integer',
        'points_earned' => 'integer',
    ];

    /**
     * Get the game session associated with this answer.
     */
    public function session(): BelongsTo
    {
        return $this->belongsTo(GameSession::class, 'session_id');
    }

    /**
     * Get the question associated with this answer.
     */
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    /**
     * Get the selected option for this answer.
     */
    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'selected_option_id');
    }

    /**
     * Calculate earned points from correctness and response time.
     */
    public static function calculatePoints(bool $isCorrect, int $responseTimeSeconds): int
    {
        if (! $isCorrect) {
            return 0;
        }

        if ($responseTimeSeconds < 5) {
            return 150;
        }

        if ($responseTimeSeconds <= 10) {
            return 125;
        }

        return 100;
    }
}
