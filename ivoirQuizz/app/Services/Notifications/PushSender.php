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

    /** Notifications d'action à envoyer une fois la réponse HTTP partie. */
    private array $afterResponse = [];

    public function __construct(private readonly FcmService $fcm) {}

    /**
     * Notification déclenchée par une action (ami, défi, admin…), si ce type est activé.
     * Elle part juste après la réponse HTTP, sans attendre le passage du Cron : le joueur est prévenu tout de suite.
     * Ne lève jamais d'exception : l'action du joueur ne doit pas échouer à cause d'un envoi.
     */
    public function notify(User $user, string $type, array $vars = [], array $data = [], string $segment = 'default'): void
    {
        try {
            $automation = PushAutomation::for($type);
            if (! $automation->enabled || $user->status !== UserStatus::Active) {
                return;
            }

            $message = $this->message($automation, $user, $segment, $vars, $data);
            $this->log($automation, [$message]);
        } catch (Throwable $exception) {
            Log::warning('Notification push non envoyée', ['type' => $type, 'user_id' => $user->id, 'error' => $exception->getMessage()]);

            return;
        }

        if ($this->afterResponse === []) {
            app()->terminating(fn () => $this->flushAfterResponse());
        }
        $this->afterResponse[] = Arr::except($message, 'ref');
    }

    /**
     * Laravel ne vide jamais ses callbacks de fin de requête : la liste est vidée ici,
     * pour qu'un second appel (requête suivante d'un test, Octane) n'envoie rien en double.
     */
    private function flushAfterResponse(): void
    {
        $messages = $this->afterResponse;
        $this->afterResponse = [];

        foreach ($messages as $message) {
            try {
                SendPushBatchJob::dispatchSync([$message]);
            } catch (Throwable $exception) {
                Log::warning('Notification push non envoyée', ['user_id' => $message['user_id'], 'error' => $exception->getMessage()]);
            }
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
        foreach (array_chunk($messages, self::CHUNK) as $chunk) {
            $this->log($automation, $chunk);
            SendPushBatchJob::dispatch(array_map(fn (array $message) => Arr::except($message, 'ref'), $chunk));
        }

        return count($messages);
    }

    private function log(PushAutomation $automation, array $messages): void
    {
        $now = now();
        PushLog::query()->insert(array_map(fn (array $message) => [
            'user_id' => $message['user_id'],
            'type' => $automation->key,
            'ref' => $message['ref'],
            'is_reminder' => $automation->isReminder(),
            'created_at' => $now,
        ], $messages));
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
