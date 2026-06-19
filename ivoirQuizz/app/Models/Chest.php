<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Chest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'min_xp' => 'integer',
        'max_xp' => 'integer',
        'min_coins' => 'integer',
        'max_coins' => 'integer',
        'min_gems' => 'integer',
        'max_gems' => 'integer',
        'is_active' => 'boolean',
    ];

    public function userChests(): HasMany
    {
        return $this->hasMany(UserChest::class);
    }
}
