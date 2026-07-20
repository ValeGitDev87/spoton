<?php

namespace App\Notifications\Auth;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;

class VerifyEmailNotification extends Notification implements ShouldQueue
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
        $expiresIn = (int) config('services.spoton_auth.email_verification_expire_minutes', 60);

        return (new MailMessage)
            ->subject('Benvenuto in SpotOn - verifica la tua email')
            ->greeting('Ciao '.$notifiable->display_name)
            ->line('Grazie per esserti registrato a SpotOn.')
            ->line('Conferma il tuo indirizzo email per attivare il tuo account.')
            ->action('Verifica email', $this->verificationUrl($notifiable))
            ->line("Il link scade tra {$expiresIn} minuti.")
            ->line('Se non hai creato tu questo account, puoi ignorare questa email.');
    }

    private function verificationUrl(object $notifiable): string
    {
        $expiresIn = (int) config('services.spoton_auth.email_verification_expire_minutes', 60);

        return URL::temporarySignedRoute(
            'verification.verify',
            Carbon::now()->addMinutes($expiresIn),
            [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ],
        );
    }
}
