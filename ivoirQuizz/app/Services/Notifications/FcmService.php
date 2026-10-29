<?php

namespace App\Services\Notifications;

use App\Models\DeviceToken;
use App\Models\User;
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
    private ?array $credentials = null;

    /**
     * Envoie une notification à tous les appareils de l'utilisateur.
     * Ne lève jamais d'exception : un échec d'envoi ne doit pas faire échouer
     * l'action du joueur, il est seulement journalisé.
     */
    public function sendToUser(User $user, string $title, string $body, array $data = []): void
    {
        try {
            $tokens = $user->deviceTokens()->pluck('token');
            if ($tokens->isEmpty()) {
                return;
            }

            $accessToken = $this->accessToken();
            foreach ($tokens as $token) {
                $this->sendToToken($accessToken, $token, $title, $body, $data);
            }
        } catch (Throwable $exception) {
            Log::warning('FCM : notification non envoyée', [
                'user_id' => $user->id,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private function sendToToken(string $accessToken, string $token, string $title, string $body, array $data): void
    {
        $message = [
            'token' => $token,
            'notification' => ['title' => $title, 'body' => $body],
        ];
        if ($data !== []) {
            // FCM n'accepte que des valeurs texte dans « data ».
            $message['data'] = array_map('strval', $data);
        }

        $response = Http::withToken($accessToken)->timeout(5)->post(
            'https://fcm.googleapis.com/v1/projects/'.$this->credentials()['project_id'].'/messages:send',
            ['message' => $message],
        );

        // Application désinstallée ou jeton périmé : on oublie cet appareil.
        if ($response->status() === 404 || $response->json('error.details.0.errorCode') === 'UNREGISTERED') {
            DeviceToken::query()->where('token', $token)->delete();
        } elseif ($response->failed()) {
            Log::warning('FCM : envoi refusé', ['status' => $response->status(), 'body' => $response->body()]);
        }
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
