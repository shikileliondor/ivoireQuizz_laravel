<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $token) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = rtrim((string) config('app.frontend_url'), '/')
            .'/reset-password?token='.urlencode($this->token)
            .'&email='.urlencode((string) $notifiable->getEmailForPasswordReset());

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe IvoirQuizz')
            ->line('Vous recevez ce message car une réinitialisation de mot de passe a été demandée.')
            ->action('Réinitialiser mon mot de passe', $url)
            ->line('Ce lien expirera dans '.config('auth.passwords.users.expire').' minutes.')
            ->line('Ignorez ce message si vous n’êtes pas à l’origine de cette demande.');
    }
}
