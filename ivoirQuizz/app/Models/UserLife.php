<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLife extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'lives' => 'integer',
        'max_lives' => 'integer',
        'next_life_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
