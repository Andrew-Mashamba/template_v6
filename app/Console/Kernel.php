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
        
        // Monthly budget close - Run on the last day of each month at 11:30 PM
        $schedule->command('budget:period-close --type=monthly')
                ->monthlyOn(date('t'), '23:30')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/budget-monthly-close.log'));
        
        // Quarterly budget close - Run on the last day of each quarter at 11:45 PM
        $schedule->command('budget:period-close --type=quarterly')
                ->quarterly()
                ->at('23:45')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/budget-quarterly-close.log'));
        
        // Daily system activities at the end of each day - includes budget monitoring
        $schedule->command('system:daily-activities')
                ->dailyAt('23:55')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/daily-activities.log'));
        
        // Monthly system activities on the last day of each month
        $schedule->command('sacco:run-monthly-activities')
                ->monthlyOn(date('t'), '23:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/monthly-activities.log'));
        
        // Quarterly system activities
        $schedule->command('sacco:run-quarterly-activities')
                ->quarterly()
                ->at('23:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/quarterly-activities.log'));
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
