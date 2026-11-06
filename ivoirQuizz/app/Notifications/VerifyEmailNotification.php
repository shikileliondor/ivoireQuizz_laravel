<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $path = URL::temporarySignedRoute(
            'site.email.verify',
            now()->addMinutes(config('auth_links.verification_expire', 60)),
            ['id' => $notifiable->getKey(), 'hash' => sha1($notifiable->getEmailForVerification())],
            false,
        );
        $url = rtrim((string) config('auth_links.url'), '/').$path;

        return (new MailMessage)
            ->subject('Vérifiez votre adresse email IvoirQuizz')
            ->greeting('Bienvenue sur IvoirQuizz !')
            ->line('Votre compte est déjà accessible. Confirmez simplement votre adresse email pour la sécuriser.')
            ->action('Vérifier mon adresse', $url)
            ->line('Ce lien expirera dans '.config('auth_links.verification_expire', 60).' minutes.')
            ->line('Ignorez ce message si vous n’êtes pas à l’origine de cette inscription.');
    }
}
