<?php

namespace App\Providers;

use App\Contracts\PushGateway;
use App\Listeners\SendWelcomeEmail;
use App\Services\Push\ExpoPushGateway;
use App\Services\Push\LogPushGateway;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PushGateway::class, fn () => config('services.push.driver') === 'expo'
            ? new ExpoPushGateway
            : new LogPushGateway);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Verified::class, SendWelcomeEmail::class);
    }
}
