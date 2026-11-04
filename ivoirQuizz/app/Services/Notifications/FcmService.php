<?php

namespace App\Services\Notifications;

use App\Models\DeviceToken;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

/**
 * Notifications push via l'API HTTP v1 de Firebase Cloud Messaging, sans
 * package (compatible hébergement mutualisé) : le jeton d'accès Google est
 * obtenu en signant un JWT avec openssl, les envois passent par le client HTTP
 * de Laravel. La clé du compte de service est lue dans
 * config('services.fcm.credentials').
 */
class FcmService
{
    /** Requêtes envoyées en parallèle par lot, pour tenir dans le temps d'une requête HTTP. */
    private const BATCH_SIZE = 100;

    private ?array $credentials = null;

    /** Lève une exception si la clé du compte de service est absente ou illisible. */
    public function ensureConfigured(): void
    {
        $this->credentials();
    }

    /**
     * Envoie une notification par appareil (chacune peut avoir son propre texte), par lots parallèles.
     * Lève une exception si la configuration FCM est invalide ; les échecs par appareil sont comptés.
     *
     * @param  iterable<array{token: string, title: string, body: string, data?: array}>  $messages
     * @return array{sent: int, failed: int, removed: int}
     */
    public function sendMessages(iterable $messages): array
    {
        $result = ['sent' => 0, 'failed' => 0, 'removed' => 0];
        $messages = collect($messages)->filter(fn (array $message) => ! empty($message['token']))->unique('token')->values();
        if ($messages->isEmpty()) {
            return $result;
        }

        $accessToken = $this->accessToken();
        $url = 'https://fcm.googleapis.com/v1/projects/'.$this->credentials()['project_id'].'/messages:send';

        foreach ($messages->chunk(self::BATCH_SIZE) as $batch) {
            $batch = $batch->values();
            $responses = Http::pool(fn (Pool $pool) => $batch
                ->map(fn (array $message, int $index) => $pool->as((string) $index)
                    ->withToken($accessToken)
                    ->timeout(10)
                    ->post($url, ['message' => $this->payload($message)]))
                ->all());

            foreach ($batch as $index => $message) {
                $outcome = $this->handleResponse($message['token'], $responses[(string) $index] ?? null);
                $result[$outcome]++;
            }
        }

        return $result;
    }

    /** @param  array{token: string, title: string, body: string, data?: array}  $message */
    private function payload(array $message): array
    {
        $payload = [
            'token' => $message['token'],
            'notification' => ['title' => $message['title'], 'body' => $message['body']],
        ];
        if (! empty($message['data'])) {
            // FCM n'accepte que des valeurs texte dans « data ».
            $payload['data'] = array_map('strval', $message['data']);
        }

        return $payload;
    }

    /**
     * @return 'sent'|'failed'|'removed'
     */
    private function handleResponse(string $token, mixed $response): string
    {
        if (! $response instanceof Response) {
            Log::warning('FCM : appareil injoignable', ['error' => $response instanceof Throwable ? $response->getMessage() : 'aucune réponse']);

            return 'failed';
        }

        // Application désinstallée ou jeton périmé : on oublie cet appareil.
        if ($response->status() === 404 || $response->json('error.details.0.errorCode') === 'UNREGISTERED') {
            DeviceToken::query()->where('token', $token)->delete();

            return 'removed';
        }

        if ($response->failed()) {
            Log::warning('FCM : envoi refusé', ['status' => $response->status(), 'body' => $response->body()]);

            return 'failed';
        }

        return 'sent';
    }

    /** Jeton d'accès Google (valable 1 h), gardé en cache 55 minutes. */
    private function accessToken(): string
    {
        return Cache::remember('fcm_access_token', 3300, function (): string {
            $credentials = $this->credentials();
            $now = time();
            $header = $this->base64url(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
            $claims = $this->base64url(json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ]));

            if (! openssl_sign("$header.$claims", $signature, $credentials['private_key'], OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('FCM : signature impossible avec la clé du compte de service.');
            }

            $response = Http::asForm()->timeout(10)->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => "$header.$claims.".$this->base64url($signature),
            ]);
            if ($response->failed() || ! is_string($response->json('access_token'))) {
                throw new RuntimeException('FCM : Google a refusé la clé — '.$response->body());
            }

            return $response->json('access_token');
        });
    }

    private function credentials(): array
    {
        if ($this->credentials !== null) {
            return $this->credentials;
        }

        $path = (string) config('services.fcm.credentials');
        if (! is_file($path)) {
            $path = base_path($path);
        }
        if (! is_file($path)) {
            throw new RuntimeException("FCM : clé du compte de service introuvable ($path).");
        }

        $credentials = json_decode((string) file_get_contents($path), true);
        if (! is_array($credentials) || empty($credentials['private_key']) || empty($credentials['client_email'])) {
            throw new RuntimeException('FCM : clé du compte de service illisible.');
        }

        return $this->credentials = $credentials;
    }

    private function base64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
