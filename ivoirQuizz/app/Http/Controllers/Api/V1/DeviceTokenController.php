<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Appareils du joueur qui reçoivent les notifications push (jetons FCM). */
class DeviceTokenController extends Controller
{
    use ApiResponse;

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'token' => ['required', 'string', 'max:512'],
            'platform' => ['nullable', 'in:android,ios'],
        ]);

        // Un appareil appartient au dernier compte qui s'y est connecté.
        DeviceToken::query()->updateOrCreate(
            ['token' => $data['token']],
            [
                'user_id' => $request->user()->id,
                'platform' => $data['platform'] ?? 'android',
                'last_used_at' => now(),
            ],
        );

        return $this->success('Appareil enregistré.');
    }

    public function destroy(Request $request): JsonResponse
    {
        $data = $request->validate(['token' => ['required', 'string', 'max:512']]);
        $request->user()->deviceTokens()->where('token', $data['token'])->delete();

        return $this->success('Appareil retiré.');
    }
}
