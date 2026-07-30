<?php

namespace App\Support\Mail;

use Illuminate\Notifications\Messages\MailMessage;

final class SpotOnAuthMail
{
    /**
     * @param  array<int, string>  $lines
     */
    public static function make(
        string $subject,
        string $preheader,
        string $eyebrow,
        string $title,
        string $greeting,
        string $intro,
        array $lines,
        string $image,
        string $imageAlt,
        ?string $actionLabel = null,
        ?string $actionUrl = null,
        ?string $notice = null,
        bool $showBrand = true,
    ): MailMessage {
        $data = [
            'preheader' => $preheader,
            'eyebrow' => $eyebrow,
            'title' => $title,
            'greeting' => $greeting,
            'intro' => $intro,
            'lines' => $lines,
            'imageUrl' => asset('images/emails/'.$image),
            'imageAlt' => $imageAlt,
            'actionLabel' => $actionLabel,
            'actionUrl' => $actionUrl,
            'notice' => $notice,
            'showBrand' => $showBrand,
            'supportEmail' => config('spoton.privacy.contact_email', config('mail.from.address')),
        ];

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.auth.notification', $data)
            ->text('emails.auth.notification-text', $data);
    }
}
