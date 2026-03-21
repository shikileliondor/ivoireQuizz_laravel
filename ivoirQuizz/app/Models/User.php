<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'google_id',
        'friend_code',
        'avatar_id',
        'total_score',
        'games_played',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'google_id',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'avatar_id' => 'integer',
        'total_score' => 'integer',
        'games_played' => 'integer',
    ];

    /**
     * Get all game sessions for the user.
     */
    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    /**
     * Get all session answers through the user's game sessions.
     */
    public function sessionAnswers(): HasManyThrough
    {
        return $this->hasManyThrough(SessionAnswer::class, GameSession::class, 'user_id', 'session_id');
    }

    /**
     * Get friendship requests sent by the user.
     */
    public function friendsAsRequester(): HasMany
    {
        return $this->hasMany(Friendship::class, 'requester_id');
    }

    /**
     * Get friendship requests received by the user.
     */
    public function friendsAsReceiver(): HasMany
    {
        return $this->hasMany(Friendship::class, 'receiver_id');
    }

    /**
     * Get all accepted friendships with related users loaded.
     */
    public function getFriendsAttribute()
    {
        $requesterFriends = $this->friendsAsRequester()
            ->accepted()
            ->with('receiver')
            ->get();

        $receiverFriends = $this->friendsAsReceiver()
            ->accepted()
            ->with('requester')
            ->get();

        return $requesterFriends->merge($receiverFriends);
    }

    /**
     * Generate a unique friend code.
     */
    public static function generateFriendCode(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

        do {
            $code = '';

            for ($i = 0; $i < 6; $i++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
        } while (self::where('friend_code', $code)->exists());

        return $code;
    }
}
