<?php

namespace App\Providers;

use App\Contracts\PushGateway;
use App\Listeners\SendWelcomeEmail;
use App\Models\Post;
use App\Models\User;
use App\Services\Push\ExpoPushGateway;
use App\Services\Push\LogPushGateway;
use Illuminate\Auth\Events\Verified;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
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
        Relation::enforceMorphMap([
            'post' => Post::class,
            'user' => User::class,
        ]);

        $key = fn (Request $request): string => (string) ($request->user()?->id ?? $request->ip());

        RateLimiter::for('posts-create', fn (Request $request) => Limit::perHour(10)->by($key($request)));
        RateLimiter::for('comments', fn (Request $request) => Limit::perMinute(30)->by($key($request)));
        RateLimiter::for('messages', fn (Request $request) => Limit::perMinute(60)->by($key($request)));
        RateLimiter::for('engagements', fn (Request $request) => Limit::perMinute(60)->by($key($request)));
        RateLimiter::for('challenges', fn (Request $request) => Limit::perHour(10)->by($key($request)));
        RateLimiter::for('challenge-answers', fn (Request $request) => Limit::perHour(5)->by($key($request)));
        RateLimiter::for('counterproposals', fn (Request $request) => Limit::perHour(5)->by($key($request)));
        RateLimiter::for('reports', fn (Request $request) => Limit::perHour(10)->by($key($request)));

        Event::listen(Verified::class, SendWelcomeEmail::class);
    }
}
