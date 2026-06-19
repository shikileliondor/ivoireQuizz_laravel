<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        'avatar',
        'avatar_id',
        'current_level',
        'xp_total',
        'total_score',
        'coins',
        'gems',
        'games_played',
        'games_won',
        'current_region_id',
        'current_city_id',
        'current_game_level_id',
        'last_login_at',
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
        'current_level' => 'integer',
        'xp_total' => 'integer',
        'total_score' => 'integer',
        'coins' => 'integer',
        'gems' => 'integer',
        'games_played' => 'integer',
        'games_won' => 'integer',
        'last_login_at' => 'datetime',
    ];

    /**
     * Get all game sessions for the user.
     */
    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }


    public function userLives(): HasOne
    {
        return $this->hasOne(UserLife::class);
    }

    public function userStreaks(): HasOne
    {
        return $this->hasOne(UserStreak::class);
    }

    public function rewardTransactions(): HasMany
    {
        return $this->hasMany(RewardTransaction::class);
    }

    public function userCollectibles(): HasMany
    {
        return $this->hasMany(UserCollectible::class);
    }

    public function userChests(): HasMany
    {
        return $this->hasMany(UserChest::class);
    }

    public function userPassports(): HasMany
    {
        return $this->hasMany(UserPassport::class);
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
