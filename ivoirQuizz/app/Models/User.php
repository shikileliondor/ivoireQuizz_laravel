<?php

namespace App\Models;

use App\Enums\UserStatus;
use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use App\Support\Pseudo;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'pseudo',
        'username',
        'email',
        'phone',
        'password',
        'google_id',
        'friend_code',
        'avatar',
        'city',
        'bio',
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
        'last_activity_date',
        'status',
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
        'email_verified_at' => 'datetime',
        'last_activity_date' => 'date',
        'status' => UserStatus::class,
        'is_admin' => 'boolean',
    ];

    /**
     * Tout compte créé sans pseudo en reçoit un : premier mot du nom, sinon « Joueur » + id.
     * Uniquement à la création : un pseudo existant n'est jamais recalculé.
     */
    protected static function booted(): void
    {
        static::created(function (User $user): void {
            if (blank($user->pseudo)) {
                $user->forceFill([
                    'pseudo' => Pseudo::fromName($user->name) ?? Pseudo::fallback((int) $user->id),
                ])->saveQuietly();
            }
        });
    }

    /**
     * Nom montré aux autres joueurs (notifications d'amis, défis).
     */
    public function publicName(): string
    {
        return $this->pseudo ?: (Pseudo::fromName($this->name) ?? (string) $this->name);
    }

    /**
     * Get all game sessions for the user.
     */
    public function gameSessions(): HasMany
    {
        return $this->hasMany(GameSession::class);
    }

    /**
     * Appareils qui reçoivent les notifications push du joueur.
     */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
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

    public function coinTransactions(): HasMany
    {
        return $this->hasMany(CoinTransaction::class);
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

    public function questionReports(): HasMany
    {
        return $this->hasMany(QuestionReport::class);
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

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification);
    }
}
