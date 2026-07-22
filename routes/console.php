<?php

use App\Jobs\CloseStalePresenceSessionsJob;
use App\Jobs\ExpirePostsJob;
use App\Jobs\PurgeLocationDataJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpirePostsJob)->everyMinute()->withoutOverlapping();
Schedule::job(new CloseStalePresenceSessionsJob)->everyMinute()->withoutOverlapping();
Schedule::job(new PurgeLocationDataJob)->dailyAt('03:15')->withoutOverlapping();
Schedule::command('auth:clear-resets')->hourly();
