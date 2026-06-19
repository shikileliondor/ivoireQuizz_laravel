<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeagueMember extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'xp_earned' => 'integer',
        'rank' => 'integer',
        'promoted' => 'boolean',
        'demoted' => 'boolean',
    ];

    public function leagueSeason(): BelongsTo
    {
        return $this->belongsTo(LeagueSeason::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
