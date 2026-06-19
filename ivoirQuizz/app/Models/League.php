<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class League extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'rank_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function seasons(): HasMany
    {
        return $this->hasMany(LeagueSeason::class);
    }
}
