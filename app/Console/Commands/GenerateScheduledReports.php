<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Services\FinancialReportingService;
use App\Mail\ScheduledReportMail;
use Carbon\Carbon;
use Exception;

class GenerateScheduledReports extends Command
{
    protected $signature = 'reports:generate-scheduled {--force : Force generation of all scheduled reports}';
    protected $description = 'Generate and send scheduled reports';

    protected $financialReportingService;

    public function __construct(FinancialReportingService $financialReportingService)
    {
        parent::__construct();
        $this->financialReportingService = $financialReportingService;
    }

    public function handle()
    {
        $this->info('Starting scheduled report generation...');

        try {
            $scheduledReports = $this->getScheduledReports();
            
            if (empty($scheduledReports)) {
                $this->info('No scheduled reports found.');
                return 0;
            }

            $this->info("Found " . count($scheduledReports) . " scheduled reports to process.");

            foreach ($scheduledReports as $report) {
                $this->processScheduledReport($report);
            }

            $this->info('Scheduled report generation completed successfully.');
            return 0;

        } catch (Exception $e) {
            Log::error('Error in scheduled report generation: ' . $e->getMessage());
            $this->error('Error generating scheduled reports: ' . $e->getMessage());
            return 1;
        }
    }

    private function getScheduledReports()
    {
        $query = DB::table('scheduled_reports')
            ->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now());

        if (!$this->option('force')) {
            $query->where(function($q) {
                $q->where('frequency', 'once')
                  ->orWhere(function($q2) {
                      $q2->where('frequency', 'daily')
                         ->whereDate('scheduled_at', '<=', now()->subDay());
                  })
                  ->orWhere(function($q2) {
                      $q2->where('frequency', 'weekly')
                         ->whereDate('scheduled_at', '<=', now()->subWeek());
                  })
                  ->orWhere(function($q2) {
                      $q2->where('frequency', 'monthly')
                         ->whereDate('scheduled_at', '<=', now()->subMonth());
                  });
            });
        }

        return $query->get();
    }

    private function processScheduledReport($report)
    {
        $this->info("Processing report: {$report->report_type}");

        try {
            // Update status to processing
            DB::table('scheduled_reports')
                ->where('id', $report->id)
                ->update([
                    'status' => 'processing',
                    'updated_at' => now()
                ]);

            // Generate report data
            $reportConfig = json_decode($report->report_config, true);
            $reportData = $this->financialReportingService->generateFinancialData(
                $reportConfig['report_type'] ?? 'statement_of_financial_position',
                Carbon::parse($reportConfig['period']['start'] ?? now()->startOfMonth()),
                Carbon::parse($reportConfig['period']['end'] ?? now()->endOfMonth())
            );

            // Generate report file
            $reportFile = $this->generateReportFile($reportData, $reportConfig);

            // Send email if recipients are specified
            if (!empty($report->email_recipients)) {
                $this->sendReportEmail($report, $reportFile, $reportData);
            }

            // Update status to completed
            DB::table('scheduled_reports')
                ->where('id', $report->id)
                ->update([
                    'status' => 'completed',
                    'generated_at' => now(),
                    'file_path' => $reportFile,
                    'updated_at' => now()
                ]);

            // Schedule next occurrence if recurring
            if ($report->frequency !== 'once') {
                $this->scheduleNextOccurrence($report);
            }

            $this->info("Successfully processed report: {$report->report_type}");

        } catch (Exception $e) {
            Log::error("Error processing scheduled report {$report->id}: " . $e->getMessage());
            
            // Update status to failed
            DB::table('scheduled_reports')
                ->where('id', $report->id)
                ->update([
                    'status' => 'failed',
                    'error_message' => $e->getMessage(),
                    'updated_at' => now()
                ]);

            $this->error("Failed to process report: {$report->report_type} - {$e->getMessage()}");
        }
    }

    private function generateReportFile($reportData, $config)
    {
        $format = $config['format'] ?? 'pdf';
        $reportType = $config['report_type'] ?? 'statement_of_financial_position';
        $timestamp = now()->format('Y-m-d_H-i-s');
        
        $filename = "scheduled_report_{$reportType}_{$timestamp}.{$format}";
        $filepath = storage_path("app/reports/scheduled/{$filename}");

        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        switch ($format) {
            case 'pdf':
                $this->generatePDFReport($reportData, $filepath, $config);
                break;
            case 'excel':
                $this->generateExcelReport($reportData, $filepath, $config);
                break;
            case 'csv':
                $this->generateCSVReport($reportData, $filepath, $config);
                break;
            default:
                throw new Exception("Unsupported format: {$format}");
        }

        return $filepath;
    }

    private function generatePDFReport($reportData, $filepath, $config)
    {
        $pdf = \PDF::loadView('reports.pdf.' . $config['report_type'], [
            'data' => $reportData,
            'config' => $config
        ]);

        $pdf->save($filepath);
    }

    private function generateExcelReport($reportData, $filepath, $config)
    {
        // Implementation for Excel generation
        // This would use Maatwebsite/Excel package
    }

    private function generateCSVReport($reportData, $filepath, $config)
    {
        // Implementation for CSV generation
    }

    private function sendReportEmail($report, $reportFile, $reportData)
    {
        $recipients = json_decode($report->email_recipients, true);
        
        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)
                    ->send(new ScheduledReportMail($report, $reportFile, $reportData));
                
                $this->info("Report sent to: {$recipient}");
                
            } catch (Exception $e) {
                Log::error("Failed to send report email to {$recipient}: " . $e->getMessage());
                $this->error("Failed to send email to: {$recipient}");
            }
        }
    }

    private function scheduleNextOccurrence($report)
    {
        $nextScheduledAt = $this->calculateNextScheduledDate($report);
        
        DB::table('scheduled_reports')->insert([
            'report_type' => $report->report_type,
            'report_config' => $report->report_config,
            'user_id' => $report->user_id,
            'status' => 'scheduled',
            'frequency' => $report->frequency,
            'scheduled_at' => $nextScheduledAt,
            'email_recipients' => $report->email_recipients,
            'email_subject' => $report->email_subject,
            'email_message' => $report->email_message,
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }

    private function calculateNextScheduledDate($report)
    {
        $lastScheduled = Carbon::parse($report->scheduled_at);
        
        switch ($report->frequency) {
            case 'daily':
                return $lastScheduled->addDay();
            case 'weekly':
                return $lastScheduled->addWeek();
            case 'monthly':
                return $lastScheduled->addMonth();
            default:
                return $lastScheduled->addDay();
        }
    }
}
