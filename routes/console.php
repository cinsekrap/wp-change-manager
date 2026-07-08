<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Host cron runs `php artisan schedule:run` every minute; all recurring jobs
// are defined here rather than as individual host cron tasks.
Schedule::command('requests:notify-scheduled-today')->dailyAt('07:00')->timezone('Europe/London');
Schedule::command('uploads:clean')->dailyAt('03:00');
Schedule::command('uploads:purge-completed')->dailyAt('03:30');
