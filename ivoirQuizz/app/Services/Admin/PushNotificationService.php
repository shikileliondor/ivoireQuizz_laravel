<?php

namespace App\Services\Admin;

use App\Enums\UserStatus;
use App\Models\DeviceToken;
use App\Models\PushNotification;
use App\Models\User;
use App\Services\Notifications\FcmService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PushNotificationService
{
    public const AUDIENCES = ['me', 'all', 'active', 'inactive', 'user'];

    public const PLATFORMS = ['android', 'ios'];

    public function __construct(private readonly FcmService $fcm) {}

    /**
     * Appareils visés : seuls les comptes actifs reçoivent les envois groupés.
     *
     * @param  array{audience: string, days?: int|null, user_id?: int|null, platform?: string|null}  $filters
     */
    public function devices(array $filters, User $admin): Builder
    {
        $days = max(1, (int) ($filters['days'] ?? 7));
        $since = Carbon::today()->subDays($days);

        return DeviceToken::query()
            ->when(! empty($filters['platform']), fn (Builder $query) => $query->where('platform', $filters['platform']))
            ->whereHas('user', function (Builder $users) use ($filters, $admin, $since): void {
                match ($filters['audience']) {
                    'me' => $users->whereKey($admin->id),
                    'user' => $users->whereKey($filters['user_id'] ?? 0),
                    'active' => $users->where('status', UserStatus::Active->value)->whereDate('last_activity_date', '>=', $since),
                    'inactive' => $users->where('status', UserStatus::Active->value)->where(fn (Builder $inner) => $inner
                        ->whereNull('last_activity_date')
                        ->orWhereDate('last_activity_date', '<', $since)),
                    default => $users->where('status', UserStatus::Active->value),
                };
            });
    }

    /**
     * Envoie et enregistre la notification ; retourne null si aucun appareil ne correspond (rien n'est envoyé).
     *
     * @param  array{audience: string, days?: int|null, user_id?: int|null, platform?: string|null, title: string, body: string}  $data
     */
    public function send(array $data, User $admin): ?PushNotification
    {
        $filters = array_filter([
            'days' => in_array($data['audience'], ['active', 'inactive'], true) ? (int) ($data['days'] ?? 7) : null,
            'user_id' => $data['audience'] === 'user' ? $data['user_id'] : null,
            'platform' => $data['platform'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        $tokens = $this->devices(['audience' => $data['audience'], ...$filters], $admin)->pluck('token');
        if ($tokens->isEmpty()) {
            return null;
        }

        $result = $this->fcm->sendToTokens($tokens, $data['title'], $data['body'], ['type' => 'admin_broadcast']);

        return PushNotification::query()->create([
            'admin_id' => $admin->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'filters' => $filters ?: null,
            'devices_count' => $tokens->count(),
            'sent_count' => $result['sent'],
            'failed_count' => $result['failed'],
            'removed_count' => $result['removed'],
        ]);
    }
}
