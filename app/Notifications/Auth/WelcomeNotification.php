<?php

namespace App\Notifications\Auth;

use App\Support\Mail\SpotOnAuthMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
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
            subject: 'Il tuo account SpotOn e attivo',
            preheader: 'Benvenuto in SpotOn: il tuo account e pronto.',
            eyebrow: 'Account attivato',
            title: 'Ora sei ufficialmente dei nostri',
            greeting: 'Benvenuto, '.$notifiable->display_name.'!',
            intro: 'La tua email e stata verificata correttamente. Ora puoi pubblicare, riconoscere incontri reali e sbloccare nuove conversazioni.',
            lines: ['Esplora i luoghi, scopri cosa succede intorno a te e partecipa alla community con rispetto e curiosita.'],
            image: 'welcome.png',
            imageAlt: 'Benvenuto nella community SpotOn',
            actionLabel: 'Vai su SpotOn',
            actionUrl: rtrim((string) config('app.url'), '/'),
            notice: 'Non condividere mai password, codici o dati sensibili nelle conversazioni.',
            showBrand: false,
        );
    }
}
