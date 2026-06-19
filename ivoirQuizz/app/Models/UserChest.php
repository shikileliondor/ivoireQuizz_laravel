<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserChest extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'source_id' => 'integer',
        'opened_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function chest(): BelongsTo
    {
        return $this->belongsTo(Chest::class);
    }
}
