<?php

namespace App\Services\Notifications;

use App\Enums\UserStatus;
use App\Jobs\SendPushBatchJob;
use App\Models\PushAutomation;
use App\Models\PushLog;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Prépare les notifications automatiques (textes de l'admin + variables), les journalise
 * et les met en file d'attente par lots.
 */
class PushSender
{
    /** Joueurs par job : un lot reste court, même sur un hébergement mutualisé. */
    public const CHUNK = 500;

    public function __construct(private readonly FcmService $fcm) {}

    /**
     * Notification déclenchée par une action (ami, défi, admin…), si ce type est activé.
     * Ne lève jamais d'exception : l'action du joueur ne doit pas échouer à cause d'un envoi.
     */
    public function notify(User $user, string $type, array $vars = [], array $data = [], string $segment = 'default'): void
    {
        try {
            $automation = PushAutomation::for($type);
            if (! $automation->enabled || $user->status !== UserStatus::Active) {
                return;
            }

            $this->queue($automation, [$this->message($automation, $user, $segment, $vars, $data)]);
        } catch (Throwable $exception) {
            Log::warning('Notification push non envoyée', ['type' => $type, 'user_id' => $user->id, 'error' => $exception->getMessage()]);
        }
    }

    /**
     * @return array{user_id: int, title: string, body: string, data: array, ref: ?string}
     */
    public function message(PushAutomation $automation, User $user, string $segment = 'default', array $vars = [], array $data = [], ?string $ref = null): array
    {
        [$title, $body] = $automation->render(
            $segment,
            ['prenom' => self::firstName($user), ...$vars],
            crc32($user->id.'|'.today()->toDateString()),
        );

        return ['user_id' => $user->id, 'title' => $title, 'body' => $body, 'data' => [...$automation->data(), ...$data], 'ref' => $ref];
    }

    /**
     * Journalise puis met en file d'attente, par lots.
     *
     * @param  list<array{user_id: int, title: string, body: string, data: array, ref: ?string}>  $messages
     */
    public function queue(PushAutomation $automation, array $messages): int
    {
        $now = now();
        foreach (array_chunk($messages, self::CHUNK) as $chunk) {
            PushLog::query()->insert(array_map(fn (array $message) => [
                'user_id' => $message['user_id'],
                'type' => $automation->key,
                'ref' => $message['ref'],
                'is_reminder' => $automation->isReminder(),
                'created_at' => $now,
            ], $chunk));

            SendPushBatchJob::dispatch(array_map(fn (array $message) => Arr::except($message, 'ref'), $chunk));
        }

        return count($messages);
    }

    /**
     * Envoie tout de suite un exemple aux appareils de l'admin, sans le journaliser.
     *
     * @return array{sent: int, failed: int, removed: int}
     */
    public function sendTest(PushAutomation $automation, User $admin, string $segment): array
    {
        $message = $this->message($automation, $admin, $segment, $automation->definition()['sample']);

        return $this->fcm->sendMessages($admin->deviceTokens()->pluck('token')->map(fn (string $token) => [
            'token' => $token,
            ...Arr::only($message, ['title', 'body', 'data']),
        ]));
    }

    private static function firstName(User $user): string
    {
        return Str::of((string) $user->name)->trim()->explode(' ')->first() ?: (string) $user->username;
    }
}
