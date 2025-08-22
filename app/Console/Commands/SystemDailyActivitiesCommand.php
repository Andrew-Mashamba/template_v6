<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DailySystemActivitiesService;

class SystemDailyActivitiesCommand extends Command
{
    protected $signature = 'system:daily-activities';
    protected $description = 'Execute daily system activities';

    protected $dailyActivitiesService;

    public function __construct(DailySystemActivitiesService $dailyActivitiesService)
    {
        parent::__construct();
        $this->dailyActivitiesService = $dailyActivitiesService;
    }

    public function handle()
    {
        $this->info('Starting daily system activities...');
        
        $result = $this->dailyActivitiesService->executeDailyActivities();
        
        if ($result['status'] === 'success') {
            $this->info('Daily activities completed successfully for ' . $result['date']);
        } else {
            $this->error('Daily activities failed: ' . $result['message']);
        }
    }
} 