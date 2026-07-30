<?php

namespace App\Notifications\Auth;

use App\Support\Mail\SpotOnAuthMail;
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

        return SpotOnAuthMail::make(
            subject: 'Benvenuto in SpotOn - verifica la tua email',
            preheader: 'Conferma il tuo indirizzo email e attiva il tuo account SpotOn.',
            eyebrow: 'Un ultimo passaggio',
            title: 'Conferma la tua email',
            greeting: 'Ciao '.$notifiable->display_name.',',
            intro: 'Grazie per esserti registrato a SpotOn. Conferma il tuo indirizzo email per attivare il tuo account.',
            lines: [],
            image: 'verify-email.png',
            imageAlt: 'Illustrazione verifica email SpotOn',
            actionLabel: 'Verifica email',
            actionUrl: $this->verificationUrl($notifiable),
            notice: "Il link scade tra {$expiresIn} minuti. Se non hai creato tu questo account, puoi ignorare questa email.",
        );
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
