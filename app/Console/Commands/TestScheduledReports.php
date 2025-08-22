<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ProcessScheduledReports;

class TestScheduledReports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reports:test-scheduled {--force : Force process all scheduled reports}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the scheduled reports processing system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Scheduled Reports System...');
        
        if ($this->option('force')) {
            $this->warn('⚠️  Force mode enabled - will process all scheduled reports regardless of timing');
        }
        
        $this->info('📋 Processing scheduled reports...');
        
        try {
            // Dispatch the job
            ProcessScheduledReports::dispatch();
            
            $this->info('✅ Scheduled reports job dispatched successfully!');
            $this->info('📧 Check your email and the logs for results.');
            $this->info('📁 Log file: storage/logs/scheduled-reports.log');
            
        } catch (\Exception $e) {
            $this->error('❌ Error processing scheduled reports: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
}
