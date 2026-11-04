<?php

namespace App\Jobs;

use App\Models\DeviceToken;
use App\Models\PushNotification;
use App\Services\Notifications\FcmService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

/**
 * Envoie un lot de notifications (500 joueurs au plus) à tous les appareils de chaque joueur.
 * Rattaché à un envoi du back-office ($campaignId), il en met à jour la progression.
 */
class SendPushBatchJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 120;

    /**
     * @param  list<array{user_id: int, title: string, body: string, data?: array}>  $messages
     */
    public function __construct(
        public array $messages,
        public ?int $campaignId = null,
        public ?string $platform = null,
    ) {}

    public function handle(FcmService $fcm): void
    {
        $campaign = $this->campaignId ? PushNotification::query()->find($this->campaignId) : null;
        if ($this->campaignId !== null && ($campaign === null || $campaign->status === 'canceled')) {
            return;
        }

        $byUser = collect($this->messages)->keyBy('user_id');
        $devices = DeviceToken::query()
            ->whereIn('user_id', $byUser->keys())
            ->when($this->platform, fn ($query) => $query->where('platform', $this->platform))
            ->get(['user_id', 'token']);

        $result = $fcm->sendMessages($devices->map(fn (DeviceToken $device) => [
            'token' => $device->token,
            'title' => $byUser[$device->user_id]['title'],
            'body' => $byUser[$device->user_id]['body'],
            'data' => $byUser[$device->user_id]['data'] ?? [],
        ]));

        $campaign?->recordBatch($devices->count(), $result);
    }

    /** Lot abandonné après ses tentatives : il compte quand même, pour que l'envoi se termine. */
    public function failed(Throwable $exception): void
    {
        if ($this->campaignId !== null) {
            PushNotification::query()->find($this->campaignId)
                ?->recordBatch(0, ['sent' => 0, 'failed' => 0, 'removed' => 0], $exception->getMessage());
        }
    }
}
