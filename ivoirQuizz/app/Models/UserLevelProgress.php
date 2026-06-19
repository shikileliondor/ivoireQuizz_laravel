<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLevelProgress extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'best_score' => 'integer',
        'best_accuracy' => 'decimal:2',
        'stars' => 'integer',
        'attempts' => 'integer',
        'is_unlocked' => 'boolean',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }
}
