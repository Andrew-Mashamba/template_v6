<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonitorAiPerformance extends Command
{
    protected $signature = 'ai:monitor 
                          {--tail : Continuously monitor logs}
                          {--errors : Show only errors}
                          {--slow : Show slow queries (>10s)}
                          {--last=60 : Show logs from last N minutes}';
                          
    protected $description = 'Monitor AI system performance and errors';

    public function handle()
    {
        $this->info('🔍 AI Performance Monitor');
        $this->info('=========================');
        
        $logFile = storage_path('logs/ai-performance.log');
        $chatLogFile = storage_path('logs/ai-chat.log');
        
        if ($this->option('tail')) {
            $this->tailLogs($logFile);
        } else {
            $this->analyzeLogs($logFile, $chatLogFile);
        }
    }
    
    private function analyzeLogs($perfLog, $chatLog)
    {
        $minutes = $this->option('last');
        $showErrors = $this->option('errors');
        $showSlow = $this->option('slow');
        
        $this->info("\nAnalyzing logs from last {$minutes} minutes...\n");
        
        // Get current log file (with date)
        $today = Carbon::now()->format('Y-m-d');
        $perfLogFile = str_replace('.log', "-{$today}.log", $perfLog);
        $chatLogFile = str_replace('.log', "-{$today}.log", $chatLog);
        
        // Performance metrics
        $metrics = [
            'total_requests' => 0,
            'successful' => 0,
            'failed' => 0,
            'timeouts' => 0,
            'fallbacks' => 0,
            'slow_queries' => [],
            'errors' => [],
            'avg_response_time' => [],
            'modes' => ['persistent' => 0, 'per-request' => 0]
        ];
        
        // Read performance log
        if (file_exists($perfLogFile)) {
            $lines = $this->readRecentLines($perfLogFile, $minutes);
            
            foreach ($lines as $line) {
                $this->parseLine($line, $metrics);
            }
        }
        
        // Display results
        $this->displayMetrics($metrics);
    }
    
    private function parseLine($line, &$metrics)
    {
        $data = json_decode($line, true);
        if (!$data) return;
        
        $message = $data['message'] ?? '';
        $context = $data['context'] ?? [];
        
        // Count requests
        if (strpos($message, '[LOCAL-CLAUDE-START]') !== false) {
            $metrics['total_requests']++;
            
            $mode = $context['mode'] ?? 'unknown';
            if (isset($metrics['modes'][$mode])) {
                $metrics['modes'][$mode]++;
            }
        }
        
        // Success/Failure
        if (strpos($message, '[LOCAL-CLAUDE-SUCCESS]') !== false) {
            $metrics['successful']++;
            
            if (isset($context['processing_time'])) {
                $metrics['avg_response_time'][] = $context['processing_time'];
                
                // Check for slow queries
                if ($context['processing_time'] > 10) {
                    $metrics['slow_queries'][] = [
                        'time' => $context['processing_time'],
                        'timestamp' => $data['datetime'] ?? 'unknown'
                    ];
                }
            }
        }
        
        if (strpos($message, '[LOCAL-CLAUDE-ERROR]') !== false || 
            strpos($message, '-ERROR]') !== false) {
            $metrics['failed']++;
            $metrics['errors'][] = [
                'message' => $context['error'] ?? $message,
                'timestamp' => $data['datetime'] ?? 'unknown'
            ];
        }
        
        // Timeouts
        if (strpos($message, 'timeout') !== false || 
            strpos($message, 'TIMEOUT') !== false) {
            $metrics['timeouts']++;
        }
        
        // Fallbacks
        if (strpos($message, '[FALLBACK') !== false) {
            $metrics['fallbacks']++;
        }
    }
    
    private function displayMetrics($metrics)
    {
        // Summary
        $this->table(
            ['Metric', 'Value'],
            [
                ['Total Requests', $metrics['total_requests']],
                ['Successful', $metrics['successful']],
                ['Failed', $metrics['failed']],
                ['Timeouts', $metrics['timeouts']],
                ['Fallbacks Used', $metrics['fallbacks']],
                ['Success Rate', $metrics['total_requests'] > 0 ? 
                    round($metrics['successful'] / $metrics['total_requests'] * 100, 1) . '%' : 'N/A']
            ]
        );
        
        // Processing modes
        $this->info("\n📊 Processing Modes:");
        foreach ($metrics['modes'] as $mode => $count) {
            if ($count > 0) {
                $percent = round($count / $metrics['total_requests'] * 100, 1);
                $this->info("  • {$mode}: {$count} ({$percent}%)");
            }
        }
        
        // Response times
        if (!empty($metrics['avg_response_time'])) {
            $avg = round(array_sum($metrics['avg_response_time']) / count($metrics['avg_response_time']), 2);
            $min = round(min($metrics['avg_response_time']), 2);
            $max = round(max($metrics['avg_response_time']), 2);
            
            $this->info("\n⏱️  Response Times:");
            $this->info("  • Average: {$avg}s");
            $this->info("  • Min: {$min}s");
            $this->info("  • Max: {$max}s");
        }
        
        // Slow queries
        if ($this->option('slow') && !empty($metrics['slow_queries'])) {
            $this->warn("\n🐢 Slow Queries (>10s):");
            foreach (array_slice($metrics['slow_queries'], -5) as $query) {
                $this->warn("  • {$query['time']}s at {$query['timestamp']}");
            }
        }
        
        // Errors
        if ($this->option('errors') && !empty($metrics['errors'])) {
            $this->error("\n❌ Recent Errors:");
            foreach (array_slice($metrics['errors'], -5) as $error) {
                $this->error("  • {$error['timestamp']}: " . substr($error['message'], 0, 100));
            }
        }
        
        // Recommendations
        $this->info("\n💡 Recommendations:");
        
        if ($metrics['timeouts'] > $metrics['total_requests'] * 0.1) {
            $this->warn("  ⚠️ High timeout rate detected. Consider:");
            $this->warn("    - Reducing timeout threshold");
            $this->warn("    - Pre-warming Claude process");
            $this->warn("    - Using persistent mode");
        }
        
        if ($metrics['fallbacks'] > 0) {
            $this->warn("  ⚠️ Fallbacks were triggered. Check:");
            $this->warn("    - Claude CLI availability");
            $this->warn("    - System resources");
        }
        
        if (!empty($metrics['avg_response_time']) && array_sum($metrics['avg_response_time']) / count($metrics['avg_response_time']) > 15) {
            $this->warn("  ⚠️ High average response time. Consider:");
            $this->warn("    - Using persistent process mode");
            $this->warn("    - Optimizing context file");
            $this->warn("    - Enabling query queue");
        }
        
        $persistentRate = $metrics['modes']['persistent'] / max(1, $metrics['total_requests']);
        if ($persistentRate < 0.5) {
            $this->info("  💡 Only " . round($persistentRate * 100, 1) . "% of requests use persistent mode");
            $this->info("     Consider enabling persistent mode for better performance");
        }
    }
    
    private function readRecentLines($file, $minutes)
    {
        if (!file_exists($file)) {
            return [];
        }
        
        $lines = [];
        $cutoff = Carbon::now()->subMinutes($minutes);
        
        $handle = fopen($file, 'r');
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                // Parse timestamp from log
                if (preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $matches)) {
                    $timestamp = Carbon::parse($matches[1]);
                    if ($timestamp->gte($cutoff)) {
                        $lines[] = $line;
                    }
                }
            }
            fclose($handle);
        }
        
        return $lines;
    }
    
    private function tailLogs($logFile)
    {
        $this->info("Tailing AI performance logs... (Ctrl+C to stop)\n");
        
        $today = Carbon::now()->format('Y-m-d');
        $logFile = str_replace('.log', "-{$today}.log", $logFile);
        
        if (!file_exists($logFile)) {
            $this->error("Log file not found: {$logFile}");
            return;
        }
        
        $lastPosition = filesize($logFile);
        
        while (true) {
            clearstatcache();
            $currentSize = filesize($logFile);
            
            if ($currentSize > $lastPosition) {
                $handle = fopen($logFile, 'r');
                fseek($handle, $lastPosition);
                
                while (!feof($handle)) {
                    $line = fgets($handle);
                    if ($line) {
                        $this->outputLogLine($line);
                    }
                }
                
                $lastPosition = ftell($handle);
                fclose($handle);
            }
            
            sleep(1);
        }
    }
    
    private function outputLogLine($line)
    {
        $data = json_decode($line, true);
        if (!$data) return;
        
        $level = $data['level_name'] ?? 'INFO';
        $message = $data['message'] ?? '';
        $context = $data['context'] ?? [];
        
        // Format timestamp
        $timestamp = Carbon::parse($data['datetime'] ?? now())->format('H:i:s');
        
        // Color-code by level
        $output = "[{$timestamp}] ";
        
        if (strpos($message, 'ERROR') !== false || $level === 'ERROR') {
            $this->error($output . $message);
            if (!empty($context['error'])) {
                $this->error("  └─ " . $context['error']);
            }
        } elseif (strpos($message, 'WARNING') !== false || $level === 'WARNING') {
            $this->warn($output . $message);
        } elseif (strpos($message, 'SUCCESS') !== false) {
            $this->info("<fg=green>{$output}{$message}</>");
            if (isset($context['processing_time'])) {
                $this->info("  └─ Time: {$context['processing_time']}s");
            }
        } elseif (strpos($message, 'START') !== false) {
            $this->info("<fg=cyan>{$output}{$message}</>");
        } else {
            $this->line($output . $message);
        }
        
        // Show important context
        if ($this->option('verbose')) {
            foreach (['processing_time', 'response_length', 'mode', 'error'] as $key) {
                if (isset($context[$key])) {
                    $this->line("    {$key}: {$context[$key]}");
                }
            }
        }
    }
}