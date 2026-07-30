<?php

namespace App\Notifications\Auth;

use App\Support\Mail\SpotOnAuthMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

class ResetPasswordNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(private readonly string $token)
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
        $expiresIn = (int) config('services.spoton_auth.password_reset_expire_minutes', 30);

        return SpotOnAuthMail::make(
            subject: 'SpotOn - reimposta la password',
            preheader: 'Usa il link sicuro per scegliere una nuova password.',
            eyebrow: 'Recupero account',
            title: 'Reimposta la tua password',
            greeting: 'Ciao '.$notifiable->display_name.',',
            intro: 'Abbiamo ricevuto una richiesta di recupero password per il tuo account SpotOn.',
            lines: ['Premi il pulsante qui sotto per scegliere una nuova password e tornare ad accedere al tuo account.'],
            image: 'reset-password.png',
            imageAlt: 'Illustrazione recupero password SpotOn',
            actionLabel: 'Reimposta password',
            actionUrl: $this->resetUrl($notifiable),
            notice: "Il link scade tra {$expiresIn} minuti ed e utilizzabile una sola volta. Se non hai richiesto tu il recupero, ignora questa email.",
        );
    }

    private function resetUrl(object $notifiable): string
    {
        return URL::query((string) config('services.spoton_auth.password_reset_url'), [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
