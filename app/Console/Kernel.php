<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Scheduled Reports Generation - Run every hour
        $schedule->command('reports:generate-scheduled')
                ->hourly()
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/scheduled-reports.log'));

        // Daily cleanup of old report files (older than 30 days)
        $schedule->command('reports:cleanup-old-files')
                ->daily()
                ->at('02:00')
                ->appendOutputTo(storage_path('logs/reports-cleanup.log'));

        // Weekly report generation for recurring reports
        $schedule->command('reports:generate-weekly')
                ->weekly()
                ->sundays()
                ->at('06:00')
                ->appendOutputTo(storage_path('logs/weekly-reports.log'));

        // Monthly report generation for recurring reports
        $schedule->command('reports:generate-monthly')
                ->monthlyOn(1, '07:00')
                ->appendOutputTo(storage_path('logs/monthly-reports.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
