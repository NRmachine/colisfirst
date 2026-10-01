<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;

class AccountVerification extends VerifyEmail implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct()
    {
        $this->afterCommit();
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)->subject('Confirmez votre inscription à ColisFirst')
            ->greeting('Bonjour '.$notifiable->name.',')
            ->line('Votre demande de création de compte ColisFirst a bien été reçue.')
            ->line('Confirmez votre adresse e-mail en cliquant sur le bouton ci-dessous. Votre compte reste soumis à la validation de l’administration.')
            ->action('Confirmer mon adresse e-mail', $this->verificationUrl($notifiable))
            ->line('Ce lien expire dans '.config('auth.verification.expire', 60).' minutes. Si vous n’êtes pas à l’origine de cette inscription, ignorez ce message.')
            ->salutation('L’équipe ColisFirst');
    }
}
