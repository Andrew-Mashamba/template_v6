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
        
        // Monthly Loan Loss Provision Cycle - Run on the 5th of each month at 9:00 AM
        // CALCULATE and COMPARE only - Manual ADJUSTMENT required via UI
        $schedule->command('provision:cycle MONTHLY')
                ->monthlyOn(5, '09:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/provision-cycle.log'))
                ->emailOutputTo('andrew.s.mashamba@gmail.com') // Always send report
                ->onSuccess(function () {
                    \Log::info('Monthly provision cycle calculated - awaiting manual adjustment approval');
                })
                ->onFailure(function () {
                    \Log::error('Monthly provision cycle calculation failed');
                });
        
        // Monthly budget close - Run on the last day of each month at 11:30 PM
        $schedule->command('budget:period-close --type=monthly')
                ->monthlyOn(date('t'), '23:30')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/budget-monthly-close.log'));
        
        // Quarterly Loan Loss Provision Cycle - Run on the 5th day of each quarter at 9:30 AM
        // (Jan 5, Apr 5, Jul 5, Oct 5)
        $schedule->command('provision:cycle QUARTERLY')
                ->quarterly()
                ->at('09:30')
                ->when(function () {
                    // Only run if not already run this month (avoid duplicate with monthly)
                    $lastCycle = \DB::table('provision_cycles')
                        ->where('frequency', 'QUARTERLY')
                        ->whereMonth('created_at', now()->month)
                        ->whereYear('created_at', now()->year)
                        ->first();
                    return !$lastCycle;
                })
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/provision-cycle-quarterly.log'));
        
        // Quarterly budget close - Run on the last day of each quarter at 11:45 PM
        $schedule->command('budget:period-close --type=quarterly')
                ->quarterly()
                ->at('23:45')
                ->withoutOverlapping()
                ->appendOutputTo(storage_path('logs/budget-quarterly-close.log'));
        
        // Daily system activities at the start of each day - includes budget monitoring
        $schedule->command('system:daily-activities')
                ->dailyAt('00:05')
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
        
        // Execute standing instructions - Run daily at 6:00 AM
        $schedule->command('standing-instructions:execute')
                ->dailyAt('06:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/standing-instructions.log'));
        
        // Execute standing instructions - Additional run at 2:00 PM for same-day instructions
        $schedule->command('standing-instructions:execute')
                ->dailyAt('14:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/standing-instructions.log'));
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
