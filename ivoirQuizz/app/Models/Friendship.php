<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Friendship extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'requester_id',
        'receiver_id',
        'status',
    ];

    /**
     * Get the requesting user.
     */
    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requester_id');
    }

    /**
     * Get the receiving user.
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Scope a query to pending friendships.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to accepted friendships.
     */
    public function scopeAccepted(Builder $query): Builder
    {
        return $query->where('status', 'accepted');
    }

    /**
     * Determine if friendship is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Determine if friendship is accepted.
     */
    public function isAccepted(): bool
    {
        return $this->status === 'accepted';
    }
}
