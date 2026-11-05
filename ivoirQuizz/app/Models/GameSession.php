<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'seconds_per_question' => 'integer',
        'gameplay_config' => 'array',
        'accuracy' => 'decimal:2',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function region(): BelongsTo { return $this->belongsTo(Region::class); }
    public function city(): BelongsTo { return $this->belongsTo(City::class); }
    public function level(): BelongsTo { return $this->belongsTo(Level::class); }
    public function category(): BelongsTo { return $this->belongsTo(Category::class); }
    public function gameSessionAnswers(): HasMany { return $this->hasMany(GameSessionAnswer::class); }
    public function questionActions(): HasMany { return $this->hasMany(GameQuestionAction::class); }
    public function questions(): BelongsToMany { return $this->belongsToMany(Question::class)->withPivot('position')->orderByPivot('position'); }
}
