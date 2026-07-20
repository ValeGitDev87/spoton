<?php

namespace App\Listeners;

use App\Notifications\Auth\WelcomeNotification;
use Illuminate\Auth\Events\Verified;

class SendWelcomeEmail
{
    public function handle(Verified $event): void
    {
        $user = $event->user;

        if ($user->welcome_email_sent_at) {
            return;
        }

        $updated = $user->newQuery()
            ->whereKey($user->getKey())
            ->whereNull('welcome_email_sent_at')
            ->update(['welcome_email_sent_at' => now()]);

        if ($updated === 1) {
            $user->refresh()->notify(new WelcomeNotification);
        }
    }
}
