<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('elongaciones:send-reminders')
    ->dailyAt(config('elongacion-alerts.schedule_time', '09:00'))
    ->timezone(config('elongacion-alerts.timezone', 'America/Mexico_City'))
    ->name('elongaciones-send-reminders')
    ->withoutOverlapping(30);

Schedule::command('notifications:send-activities')
    ->dailyAt(config('elongacion-alerts.schedule_time', '09:00'))
    ->timezone(config('elongacion-alerts.timezone', 'America/Mexico_City'))
    ->name('notifications-send-activities')
    ->withoutOverlapping(30);
