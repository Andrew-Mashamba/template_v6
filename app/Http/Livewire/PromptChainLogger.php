<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class PromptChainLogger extends Component
{
    public $logs = [];
    public $sessionId = '';
    public $autoRefresh = true;
    public $refreshInterval = 2000; // 2 seconds
    public $searchTerm = '';
    public $filterStep = '';
    public $showOnlyErrors = false;

    protected $listeners = ['refreshLogs'];

    public function mount()
    {
        $this->loadLogs();
    }

    public function loadLogs()
    {
        try {
            // Get today's log file
            $logPath = storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log');
            
            if (File::exists($logPath)) {
                // Read the log file
                $content = File::get($logPath);
                
                // Parse prompt chain logs
                $this->logs = $this->parsePromptChainLogs($content);
                
                // Apply filters
                $this->applyFilters();
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error loading logs: ' . $e->getMessage());
        }
    }

    private function parsePromptChainLogs($content)
    {
        $logs = [];
        $lines = explode("\n", $content);
        
        foreach ($lines as $line) {
            // Look for PROMPT-CHAIN logs
            if (strpos($line, '[PROMPT-CHAIN') !== false) {
                // Extract timestamp
                preg_match('/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]/', $line, $timestampMatch);
                $timestamp = $timestampMatch[1] ?? '';
                
                // Extract log level
                preg_match('/local\.(INFO|WARNING|ERROR|DEBUG):/', $line, $levelMatch);
                $level = $levelMatch[1] ?? 'INFO';
                
                // Extract the emoji and title
                preg_match('/([🔵🟣🔴🟡🟢🟠🟡🔶🔷🔸🔹⚠️❌]) \[PROMPT-CHAIN[^\]]*\] ([^{]+)/', $line, $titleMatch);
                $emoji = $titleMatch[1] ?? '';
                $title = trim($titleMatch[2] ?? '');
                
                // Extract JSON data
                preg_match('/(\{.*\})/', $line, $jsonMatch);
                $data = [];
                if (isset($jsonMatch[1])) {
                    $jsonData = json_decode($jsonMatch[1], true);
                    if ($jsonData) {
                        $data = $jsonData;
                    }
                }
                
                // Create log entry
                $logs[] = [
                    'timestamp' => $timestamp,
                    'level' => $level,
                    'emoji' => $emoji,
                    'title' => $title,
                    'step' => $data['step'] ?? 0,
                    'session_id' => $data['session_id'] ?? '',
                    'location' => $data['location'] ?? '',
                    'data' => $data,
                    'raw' => $line
                ];
            }
        }
        
        // Sort by timestamp descending (newest first)
        usort($logs, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });
        
        return $logs;
    }

    private function applyFilters()
    {
        $filtered = $this->logs;
        
        // Filter by session ID
        if ($this->sessionId) {
            $filtered = array_filter($filtered, function($log) {
                return $log['session_id'] === $this->sessionId;
            });
        }
        
        // Filter by search term
        if ($this->searchTerm) {
            $filtered = array_filter($filtered, function($log) {
                return stripos($log['title'], $this->searchTerm) !== false ||
                       stripos($log['location'], $this->searchTerm) !== false ||
                       stripos(json_encode($log['data']), $this->searchTerm) !== false;
            });
        }
        
        // Filter by step
        if ($this->filterStep !== '') {
            $filtered = array_filter($filtered, function($log) {
                return $log['step'] == $this->filterStep;
            });
        }
        
        // Show only errors
        if ($this->showOnlyErrors) {
            $filtered = array_filter($filtered, function($log) {
                return $log['level'] === 'ERROR' || $log['level'] === 'WARNING';
            });
        }
        
        $this->logs = array_values($filtered);
    }

    public function refreshLogs()
    {
        $this->loadLogs();
    }

    public function clearFilters()
    {
        $this->sessionId = '';
        $this->searchTerm = '';
        $this->filterStep = '';
        $this->showOnlyErrors = false;
        $this->loadLogs();
    }

    public function toggleAutoRefresh()
    {
        $this->autoRefresh = !$this->autoRefresh;
    }

    public function getPromptFlow($sessionId)
    {
        // Get all logs for a specific session to show the complete flow
        $sessionLogs = array_filter($this->logs, function($log) use ($sessionId) {
            return $log['session_id'] === $sessionId;
        });
        
        // Sort by step number
        usort($sessionLogs, function($a, $b) {
            return $a['step'] - $b['step'];
        });
        
        return $sessionLogs;
    }

    public function render()
    {
        return view('livewire.prompt-chain-logger');
    }
}