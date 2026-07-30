<?php

namespace App\Notifications\Auth;

use App\Support\Mail\SpotOnAuthMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct()
    {
        $this->onQueue('emails');
        $this->afterCommit();
    }

    public function backoff(): array
    {
        return [10, 60, 300];
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return SpotOnAuthMail::make(
            subject: 'La password del tuo account SpotOn e stata modificata',
            preheader: 'Conferma di sicurezza: la password del tuo account e stata aggiornata.',
            eyebrow: 'Sicurezza account',
            title: 'Password aggiornata',
            greeting: 'Ciao '.$notifiable->display_name.',',
            intro: 'La password del tuo account SpotOn e stata modificata correttamente.',
            lines: ['Tutte le altre sessioni sono state disconnesse per proteggere il tuo account.'],
            image: 'password-changed.png',
            imageAlt: 'Illustrazione password SpotOn protetta',
            notice: 'Se non sei stato tu, avvia subito il recupero password o contatta il supporto. Questa email non contiene password, token o dati sensibili.',
        );
    }
}
