<?php

namespace Tests\Concerns;

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

/**
 * Firebase factice : clé de compte de service bidon et jeton Google préchargé (aucune signature),
 * réponses FCM simulées, lecture des messages envoyés par appareil.
 */
trait FakesFcm
{
    private string $fcmCredentialsPath;

    protected function setUpFakeFcm(): void
    {
        $this->fcmCredentialsPath = tempnam(sys_get_temp_dir(), 'fcm');
        file_put_contents($this->fcmCredentialsPath, json_encode([
            'project_id' => 'notifquizz-test',
            'client_email' => 'fcm@notifquizz-test.iam.gserviceaccount.com',
            'private_key' => 'fake-key',
        ]));
        config(['services.fcm.credentials' => $this->fcmCredentialsPath]);
        // Sans expiration : les tests avancent l'horloge de plusieurs heures.
        Cache::forever('fcm_access_token', 'google-access-token');
        Http::fake(['fcm.googleapis.com/*' => Http::response(['name' => 'projects/notifquizz-test/messages/1'])]);
    }

    protected function tearDownFakeFcm(): void
    {
        @unlink($this->fcmCredentialsPath);
    }

    protected function playerWithDevice(string $token, array $attributes = []): User
    {
        $player = User::factory()->create($attributes);
        DeviceToken::query()->create(['user_id' => $player->id, 'token' => $token, 'platform' => 'android']);

        return $player;
    }

    /** Message FCM reçu par cet appareil (le dernier), ou null. */
    protected function messageFor(string $token): ?array
    {
        return Http::recorded(fn (Request $request) => ($request['message']['token'] ?? null) === $token)
            ->map(fn (array $pair) => $pair[0]['message'])
            ->last();
    }

    protected function messagesFor(string $token): int
    {
        return Http::recorded(fn (Request $request) => ($request['message']['token'] ?? null) === $token)->count();
    }
}
