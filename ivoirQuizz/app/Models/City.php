<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'order' => 'integer',
        'required_xp' => 'integer',
        'is_active' => 'boolean',
    ];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(Level::class);
    }

    public function collectibles(): HasMany
    {
        return $this->hasMany(Collectible::class);
    }

    public function userCityProgress(): HasMany
    {
        return $this->hasMany(UserCityProgress::class);
    }
}
