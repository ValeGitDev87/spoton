<?php

namespace App\Notifications\Auth;

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
        return (new MailMessage)
            ->subject('Il tuo account SpotOn e attivo')
            ->greeting('Benvenuto in SpotOn, '.$notifiable->display_name)
            ->line('La tua email e stata verificata correttamente.')
            ->line('Ora puoi usare SpotOn per pubblicare, riconoscere incontri reali e sbloccare conversazioni in modo sicuro.')
            ->action('Vai su SpotOn', rtrim((string) config('app.url'), '/'))
            ->line('Non condividere mai password, codici o dati sensibili nelle conversazioni.');
    }
}
