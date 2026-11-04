<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendPushRequest;
use App\Models\DeviceToken;
use App\Models\PushNotification;
use App\Services\Admin\PushNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Throwable;

class PushNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $activeDevices = DeviceToken::query()->whereHas('user', fn ($users) => $users->where('status', UserStatus::Active->value));

        return view('admin.notifications.index', [
            'stats' => [
                'devices' => (clone $activeDevices)->count(),
                'players' => (clone $activeDevices)->distinct()->count('user_id'),
                'android' => (clone $activeDevices)->where('platform', 'android')->count(),
                'ios' => (clone $activeDevices)->where('platform', 'ios')->count(),
                'mine' => $request->user()->deviceTokens()->count(),
            ],
            'history' => PushNotification::query()->with('admin')->latest()->paginate(15),
            'prefillUser' => $request->query('user'),
        ]);
    }

    public function store(SendPushRequest $request, PushNotificationService $push): RedirectResponse
    {
        // Les envois partent par lots parallèles ; on laisse de la marge pour les grosses audiences.
        @set_time_limit(120);

        try {
            $notification = $push->send([
                ...$request->safe()->only(['audience', 'days', 'platform', 'title', 'body']),
                'user_id' => $request->targetUser()?->id,
            ], $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Envoi impossible : '.$exception->getMessage());
        }

        if ($notification === null) {
            return back()->withInput()->with('error', 'Aucun appareil enregistré ne correspond à ces destinataires : rien n’a été envoyé.');
        }

        $message = "Notification envoyée à {$notification->devices_count} appareil(s) : {$notification->sent_count} reçue(s)";
        if ($notification->failed_count > 0) {
            $message .= ", {$notification->failed_count} échec(s)";
        }
        if ($notification->removed_count > 0) {
            $message .= ", {$notification->removed_count} appareil(s) obsolète(s) retiré(s)";
        }

        return redirect()->route('admin.notifications.index')->with('success', $message.'.');
    }
}
