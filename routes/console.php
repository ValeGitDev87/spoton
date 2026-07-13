<?php

use App\Jobs\ExpirePostsJob;
use App\Jobs\CloseStalePresenceSessionsJob;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new ExpirePostsJob)->everyMinute()->withoutOverlapping();
Schedule::job(new CloseStalePresenceSessionsJob)->everyMinute()->withoutOverlapping();
