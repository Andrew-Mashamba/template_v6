<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;

class ViewAiChatLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ai:logs 
                            {--tail=20 : Number of lines to show}
                            {--follow : Follow the log in real-time}
                            {--filter= : Filter logs by keyword}
                            {--today : Show only today\'s logs}
                            {--clear : Clear the log file}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'View AI Chat logs with formatting';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $logFile = storage_path('logs/ai-chat-' . now()->format('Y-m-d') . '.log');
        
        // Check if log file exists
        if (!file_exists($logFile)) {
            $this->error("No AI chat logs found for today.");
            $this->info("Log file will be created at: " . $logFile);
            return 1;
        }
        
        // Clear logs if requested
        if ($this->option('clear')) {
            if ($this->confirm('Are you sure you want to clear the AI chat logs?')) {
                file_put_contents($logFile, '');
                $this->info('AI chat logs cleared successfully.');
            }
            return 0;
        }
        
        // Follow logs in real-time
        if ($this->option('follow')) {
            $this->info("Following AI chat logs (Press Ctrl+C to stop)...\n");
            passthru("tail -f " . escapeshellarg($logFile));
            return 0;
        }
        
        // Read logs
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Filter if requested
        if ($filter = $this->option('filter')) {
            $lines = array_filter($lines, function($line) use ($filter) {
                return stripos($line, $filter) !== false;
            });
        }
        
        // Get last N lines
        $tail = (int) $this->option('tail');
        $lines = array_slice($lines, -$tail);
        
        // Display formatted logs
        $this->displayFormattedLogs($lines);
        
        // Show summary
        $this->showLogSummary($logFile);
        
        return 0;
    }
    
    /**
     * Display logs with color formatting
     */
    private function displayFormattedLogs($lines)
    {
        foreach ($lines as $line) {
            // Parse and format the log line
            if (preg_match('/\[([\d-\s:]+)\]\s+(\w+):\s+(.*)/', $line, $matches)) {
                $timestamp = $matches[1];
                $level = $matches[2];
                $message = $matches[3];
                
                // Format timestamp
                $formattedTime = Carbon::parse($timestamp)->format('H:i:s');
                
                // Color code based on log type
                if (strpos($message, '[CLAUDE-PROMPT]') !== false) {
                    $this->line("<fg=gray>$formattedTime</> <fg=cyan>📝 PROMPT:</> " . $this->extractMessage($message));
                } elseif (strpos($message, '[CLAUDE-REQUEST]') !== false) {
                    $this->line("<fg=gray>$formattedTime</> <fg=yellow>📤 REQUEST:</> " . $this->extractMessage($message));
                } elseif (strpos($message, '[CLAUDE-RESPONSE]') !== false) {
                    $this->line("<fg=gray>$formattedTime</> <fg=green>📥 RESPONSE:</> " . $this->extractMessage($message));
                } elseif (strpos($message, '[VALIDATION-FAILED]') !== false) {
                    $this->line("<fg=gray>$formattedTime</> <fg=red>⚠️  VALIDATION FAILED:</> " . $this->extractMessage($message));
                } elseif (strpos($message, '[VALIDATION-PASSED]') !== false) {
                    $this->line("<fg=gray>$formattedTime</> <fg=green>✅ VALIDATION PASSED</>");
                } elseif (strpos($message, '[FINAL-HTML]') !== false) {
                    $this->line("<fg=gray>$formattedTime</> <fg=blue>🎯 FINAL HTML:</> " . $this->extractMessage($message));
                } elseif (strpos($message, '[CONTENT-PROCESSING]') !== false) {
                    $this->line("<fg=gray>$formattedTime</> <fg=magenta>🔄 PROCESSING:</> " . $this->extractMessage($message));
                } else {
                    $this->line("<fg=gray>$formattedTime</> $message");
                }
            } else {
                // Display raw line if pattern doesn't match
                $this->line($line);
            }
        }
    }
    
    /**
     * Extract message content from log entry
     */
    private function extractMessage($message)
    {
        // Extract key information from the JSON context
        if (preg_match('/\{.*\}/', $message, $matches)) {
            $json = $matches[0];
            $data = json_decode($json, true);
            
            if ($data) {
                $output = [];
                
                // Extract relevant fields
                if (isset($data['prompt'])) {
                    $prompt = substr($data['prompt'], 0, 100);
                    if (strlen($data['prompt']) > 100) $prompt .= '...';
                    $output[] = "Prompt: \"$prompt\"";
                }
                
                if (isset($data['response_length'])) {
                    $output[] = "Length: " . $data['response_length'];
                }
                
                if (isset($data['is_html'])) {
                    $output[] = "HTML: " . ($data['is_html'] ? 'Yes' : 'No');
                }
                
                if (isset($data['starts_with_html'])) {
                    $output[] = "Valid Start: " . $data['starts_with_html'];
                }
                
                if (isset($data['errors'])) {
                    $output[] = "Errors: " . implode(', ', $data['errors']);
                }
                
                if (isset($data['has_compact_classes'])) {
                    $output[] = "Compact: " . ($data['has_compact_classes'] ? 'Yes' : 'No');
                }
                
                return implode(' | ', $output);
            }
        }
        
        // Return first part of message if no JSON
        $parts = explode('{', $message);
        return trim($parts[0]);
    }
    
    /**
     * Show log file summary
     */
    private function showLogSummary($logFile)
    {
        $this->newLine();
        $this->info('📊 Log Summary:');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Log File', basename($logFile)],
                ['File Size', $this->formatBytes(filesize($logFile))],
                ['Total Lines', number_format(count(file($logFile)))],
                ['Last Modified', Carbon::createFromTimestamp(filemtime($logFile))->diffForHumans()],
            ]
        );
        
        // Count log types
        $content = file_get_contents($logFile);
        $counts = [
            'Prompts' => substr_count($content, '[CLAUDE-PROMPT]'),
            'Requests' => substr_count($content, '[CLAUDE-REQUEST]'),
            'Responses' => substr_count($content, '[CLAUDE-RESPONSE]'),
            'Validations Failed' => substr_count($content, '[VALIDATION-FAILED]'),
            'Validations Passed' => substr_count($content, '[VALIDATION-PASSED]'),
        ];
        
        $this->info('📈 Log Type Counts:');
        $this->table(
            ['Type', 'Count'],
            array_map(function($type, $count) {
                return [$type, number_format($count)];
            }, array_keys($counts), array_values($counts))
        );
    }
    
    /**
     * Format bytes to human readable
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}