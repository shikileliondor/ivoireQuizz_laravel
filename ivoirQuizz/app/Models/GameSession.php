<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GameSession extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'score' => 'integer',
        'points_earned' => 'integer',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
        'gems_earned' => 'integer',
        'correct_answers' => 'integer',
        'wrong_answers' => 'integer',
        'total_questions' => 'integer',
        'accuracy' => 'decimal:2',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
    public function city(): BelongsTo { return $this->belongsTo(City::class); }
    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
    public function gameSessionAnswers(): HasMany { return $this->hasMany(GameSessionAnswer::class); }
}
