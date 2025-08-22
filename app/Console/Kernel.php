<?php

namespace App\Console;

use App\Jobs\EndOfDay;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CleanupLogs::class,
        Commands\SyncMenuActions::class,
        Commands\SyncUserRoles::class,
        Commands\SyncRolePermissions::class,
        Commands\SyncRoles::class,
        Commands\TestNotificationService::class,
        Commands\TestMenuLoading::class,
        Commands\ViewSystemLogs::class,
        Commands\SystemDailyActivitiesCommand::class,
        Commands\ProcessEmailQueue::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //$schedule->command('stethoscope:monitor')->everyMinute()->withoutOverlapping();
        //$schedule->command(new EndOfDay())->dailyAt('16:56');
        // PPE Depreciation is now handled in monthly activities
        //$schedule->job(new \App\Jobs\CalculatePpeDepreciation)->monthlyOn(Carbon::now()->endOfMonth()->day);
        //$schedule->job(new CalculatePpeDepreciation)->monthly();
        // Run monthly billing on the 1st of each month at 00:01
        $schedule->command('bills:generate-monthly')
            ->monthlyOn(1, '00:01')
            ->withoutOverlapping()
            ->runInBackground();
        // Clean up all logs daily at midnight
        $schedule->command('logs:cleanup')->daily();
        // Run daily system activities at the end of each day
        $schedule->command('system:daily-activities')
            ->dailyAt('23:55')
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/daily-activities.log'));
        // Run monthly activities at 2:00 AM on the first day of every month
        $schedule->command('sacco:run-monthly-activities')
                ->monthlyOn(1, '02:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/monthly-activities.log'));
        // Run quarterly activities at 3:00 AM on the first day of every quarter (Jan 1, Apr 1, Jul 1, Oct 1)
        $schedule->command('sacco:run-quarterly-activities')
                ->quarterly()
                ->at('03:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/quarterly-activities.log'));
        // Run yearly activities at 4:00 AM on January 1st
        $schedule->command('sacco:run-yearly-activities')
                ->yearly()
                ->at('04:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/yearly-activities.log'));
        // Run year-end closing activities at 5:00 AM on December 31st
        $schedule->command('sacco:run-year-end-closing')
                ->yearly()
                ->at('05:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/year-end-closing.log'));
        
        // Process scheduled reports every 5 minutes
        $schedule->job(new \App\Jobs\ProcessScheduledReports)
                ->everyFiveMinutes()
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/scheduled-reports.log'));
        
        // Sync emails if enabled
        if (config('email-servers.sync.enabled')) {
            $interval = config('email-servers.sync.interval', 5);
            $schedule->command('emails:sync --all')
                ->everyMinutes($interval)
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/email-sync.log'));
        }
        
        // Archive old emails daily at 2 AM
        $schedule->call(function () {
            $emailService = new \App\Services\EmailService();
            $archivedCount = $emailService->backupOldEmails(90);
            \Log::channel('email')->info("Archived {$archivedCount} old emails");
        })->dailyAt('02:00');
        
        // Process snoozed emails every 5 minutes
        $schedule->command('emails:process-snoozes')
            ->everyFiveMinutes()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Process scheduled emails every minute
        $schedule->command('emails:send-scheduled')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Process undo queue every minute (more frequent than needed but Laravel's minimum)
        $schedule->command('emails:process-undo-queue')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground();
            
        // Process queued emails every minute to send emails after undo window expires
        $schedule->command('email:process-queue')
            ->everyMinute()
            ->withoutOverlapping()
            ->runInBackground()
            ->appendOutputTo(storage_path('logs/email-queue.log'));
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
