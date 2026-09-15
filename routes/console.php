<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Console routes and the schedule
|--------------------------------------------------------------------------
|
| Laravel 11 removed the console kernel; closure commands and the schedule
| both live here now. Commands in app/Console/Commands are discovered
| automatically and do not need registering.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
 | The mill drops its sheet overnight. This picks up anything waiting in
 | storage/app/imports/inbox, stages it and runs the preview — stopping short
 | of applying, because a human approves the changes in the morning. A mill
 | occasionally sends a truncated file, and an unattended apply would publish
 | an empty warehouse to the storefront.
 */
Schedule::command('tbm:import-inbox')
    ->dailyAt('06:15')
    ->withoutOverlapping()
    ->onOneServer();

Schedule::command('auth:clear-resets')->everyFifteenMinutes();
