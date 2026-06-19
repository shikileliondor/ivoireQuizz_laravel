<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GameSessionAnswer extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'is_correct' => 'boolean',
        'response_time' => 'integer',
        'points_earned' => 'integer',
        'xp_earned' => 'integer',
    ];

    public function gameSession(): BelongsTo
    {
        return $this->belongsTo(GameSession::class);
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }

    public function answer(): BelongsTo
    {
        return $this->belongsTo(Answer::class);
    }
}
