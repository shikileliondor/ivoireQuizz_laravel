<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendPushRequest;
use App\Models\DeviceToken;
use App\Models\PushNotification;
use App\Services\Admin\PushNotificationService;
use App\Services\Notifications\ReminderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Throwable;

class PushNotificationController extends Controller
{
    public function index(Request $request, ReminderService $reminders): View
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
            'scheduler' => $reminders->status(),
        ]);
    }

    public function store(SendPushRequest $request, PushNotificationService $push): RedirectResponse
    {
        // Avec QUEUE_CONNECTION=sync, les lots partent pendant cette requête : on laisse de la marge.
        @set_time_limit(120);

        try {
            $notification = $push->create([
                ...$request->safe()->only(['audience', 'days', 'platform', 'title', 'body']),
                'user_id' => $request->targetUser()?->id,
                'scheduled_at' => $request->input('when') === 'later' ? Carbon::parse($request->validated('send_at')) : null,
            ], $request->user());
        } catch (Throwable $exception) {
            report($exception);

            return back()->withInput()->with('error', 'Envoi impossible : '.$exception->getMessage());
        }

        if ($notification === null) {
            return back()->withInput()->with('error', 'Aucun appareil enregistré ne correspond à ces destinataires : rien n’a été envoyé.');
        }

        $redirect = redirect()->route('admin.notifications.index');

        return match ($notification->status) {
            'scheduled' => $redirect->with('success', 'Notification programmée pour le '.$notification->scheduled_at->format('d/m/Y à H:i').'.'),
            'sent' => $redirect->with('success', $this->summary($notification)),
            'failed' => $redirect->with('error', 'Envoi échoué : '.$notification->error),
            default => $redirect->with('success', 'Envoi lancé : il part au prochain passage du planificateur (5 min max). La progression s’affiche dans l’historique.'),
        };
    }

    public function cancel(PushNotification $notification): RedirectResponse
    {
        if (! $notification->isCancelable()) {
            return back()->with('error', 'Cet envoi est terminé : il ne peut plus être annulé.');
        }

        $notification->update(['status' => 'canceled', 'finished_at' => now()]);

        return back()->with('success', 'Envoi annulé. Les lots déjà partis ne peuvent pas être rappelés.');
    }

    private function summary(PushNotification $notification): string
    {
        $message = "Notification envoyée à {$notification->devices_count} appareil(s) : {$notification->sent_count} reçue(s)";
        if ($notification->failed_count > 0) {
            $message .= ", {$notification->failed_count} échec(s)";
        }
        if ($notification->removed_count > 0) {
            $message .= ", {$notification->removed_count} appareil(s) obsolète(s) retiré(s)";
        }

        return $message.'.';
    }
}
