<?php

namespace App\Support;

use DateTimeZone;

class SightingDateValidation
{
    public static function todayFor(mixed $timezone): string
    {
        $timezone = is_string($timezone)
            && in_array($timezone, DateTimeZone::listIdentifiers(), true)
                ? $timezone
                : config('app.timezone');

        return now($timezone)->toDateString();
    }
}
