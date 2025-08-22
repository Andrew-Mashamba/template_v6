<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DailySystemActivitiesService;
use Illuminate\Support\Facades\Log;

class RunDailySystemActivities extends Command
{
    protected $signature = 'system:daily-activities';
    protected $description = 'Run all daily system activities including dividend calculations';

    protected $activitiesService;

    public function __construct(DailySystemActivitiesService $activitiesService)
    {
        parent::__construct();
        $this->activitiesService = $activitiesService;
    }

    public function handle()
    {
        $this->info('Starting end-of-day system activities...');

        try {
            $result = $this->activitiesService->executeDailyActivities();

            if ($result['success']) {
                $this->info('All end-of-day activities completed successfully for ' . $result['processed_date']);
                $this->displayActivityResults($result['activities']);
            } else {
                $this->error('Failed to complete end-of-day activities for ' . $result['processed_date'] . ': ' . $result['error']);
                Log::error('End-of-day activities failed', [
                    'date' => $result['processed_date'],
                    'error' => $result['error']
                ]);
            }

        } catch (\Exception $e) {
            $this->error('An error occurred: ' . $e->getMessage());
            Log::error('End-of-day activities exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    protected function displayActivityResults($activities)
    {
        $this->table(
            ['Activity', 'Status', 'Date'],
            collect($activities)->map(function ($result, $activity) {
                return [
                    'Activity' => ucwords(str_replace('_', ' ', $activity)),
                    'Status' => $result['status'],
                    'Date' => $result['date']
                ];
            })
        );
    }
} 