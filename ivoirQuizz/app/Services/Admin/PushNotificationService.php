<?php

namespace App\Services\Admin;

use App\Enums\UserStatus;
use App\Jobs\SendPushBatchJob;
use App\Models\DeviceToken;
use App\Models\PushNotification;
use App\Models\User;
use App\Services\Notifications\FcmService;
use App\Services\Notifications\PushSender;
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
     * Enregistre la notification puis la met en file d'attente, ou la programme si scheduled_at est donné.
     * Retourne null si aucun appareil ne correspond (rien n'est enregistré).
     * L'envoi test (« me ») part tout de suite, pour un retour immédiat.
     *
     * @param  array{audience: string, days?: int|null, user_id?: int|null, platform?: string|null, title: string, body: string, scheduled_at?: \DateTimeInterface|null}  $data
     */
    public function create(array $data, User $admin): ?PushNotification
    {
        $filters = array_filter([
            'days' => in_array($data['audience'], ['active', 'inactive'], true) ? (int) ($data['days'] ?? 7) : null,
            'user_id' => $data['audience'] === 'user' ? $data['user_id'] : null,
            'platform' => $data['platform'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');

        if (! $this->devices(['audience' => $data['audience'], ...$filters], $admin)->exists()) {
            return null;
        }
        $this->fcm->ensureConfigured();

        $scheduledAt = $data['scheduled_at'] ?? null;
        $notification = PushNotification::query()->create([
            'admin_id' => $admin->id,
            'title' => $data['title'],
            'body' => $data['body'],
            'audience' => $data['audience'],
            'filters' => $filters ?: null,
            'status' => $scheduledAt ? 'scheduled' : 'queued',
            'scheduled_at' => $scheduledAt,
        ]);

        if (! $scheduledAt) {
            $this->dispatch($notification, sync: $data['audience'] === 'me');
        }

        return $notification->refresh();
    }

    /** Découpe les destinataires en lots de joueurs et les met en file d'attente. */
    public function dispatch(PushNotification $notification, bool $sync = false): void
    {
        $userIds = $this->devices(
            ['audience' => $notification->audience, ...($notification->filters ?? [])],
            $notification->admin ?? new User,
        )->distinct()->pluck('user_id');
        $chunks = $userIds->chunk(PushSender::CHUNK);

        // Le nombre de lots est connu avant le premier envoi : le dernier lot terminé clôt l'envoi.
        $notification->update([
            'status' => $chunks->isEmpty() ? 'sent' : 'sending',
            'started_at' => now(),
            'finished_at' => $chunks->isEmpty() ? now() : null,
            'batches_total' => $chunks->count(),
        ]);

        foreach ($chunks as $chunk) {
            $job = new SendPushBatchJob(
                $chunk->map(fn ($userId) => [
                    'user_id' => (int) $userId,
                    'title' => $notification->title,
                    'body' => $notification->body,
                    'data' => ['type' => 'admin_broadcast'],
                ])->values()->all(),
                $notification->id,
                $notification->filters['platform'] ?? null,
            );
            $sync ? dispatch_sync($job) : dispatch($job);
        }
    }
}
