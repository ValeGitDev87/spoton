<?php

namespace App\Notifications\Auth;

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
        return (new MailMessage)
            ->subject('La password del tuo account SpotOn e stata modificata')
            ->greeting('Ciao '.$notifiable->display_name)
            ->line('La password del tuo account SpotOn e stata modificata correttamente.')
            ->line('Se non sei stato tu, accedi subito al recupero password o contatta il supporto.')
            ->line('Questa email non contiene password, token o dati sensibili.');
    }
}
