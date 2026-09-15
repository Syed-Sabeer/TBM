<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        /*
         | The mill drops its sheet overnight. This picks up anything waiting
         | in storage/app/imports/inbox, stages it and runs the preview —
         | stopping short of applying, because a human approves the changes
         | in the morning. See ImportInboxCommand.
         */
        $schedule->command('tbm:import-inbox')
            ->dailyAt('06:15')
            ->withoutOverlapping()
            ->onOneServer();

        $schedule->command('auth:clear-resets')->everyFifteenMinutes();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
