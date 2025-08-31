<?php

namespace App\Http\Livewire\Terminal;

use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class TerminalConsole extends Component
{
    public $command = '';
    public $output = '';
    public $isRunning = false;
    public $processId = null;
    public $currentDirectory;
    public $commandHistory = [];
    public $historyIndex = -1;
    public $autoScroll = true;
    public $maxOutputLines = 1000;
    public $theme = 'dark'; // dark, light
    public $fontSize = '14px';
    
    // Claude-specific properties
    public $claudeMode = false;
    public $claudeBridgeActive = false;
    public $claudeProcessId = null;
    
    protected $listeners = [
        'executeCommand' => 'executeCommand',
        'stopProcess' => 'stopProcess',
        'clearOutput' => 'clearOutput',
        'startClaudeBridge' => 'startClaudeBridge',
        'stopClaudeBridge' => 'stopClaudeBridge',
        'viewClaudeLogs' => 'viewClaudeLogs',
        'checkClaudeRequests' => 'checkClaudeRequests'
    ];

    public function mount()
    {
        $this->currentDirectory = base_path();
        $this->loadCommandHistory();
        $this->checkClaudeBridgeStatus();
    }

    public function render()
    {
        return view('livewire.terminal.terminal-console');
    }

    /**
     * Execute a command in the terminal
     */
    public function executeCommand($command = null)
    {
        if ($command) {
            $this->command = $command;
        }

        if (empty(trim($this->command))) {
            $this->appendOutput("\n\033[1;33m⚠ No command entered\033[0m\n");
            return;
        }

        $this->isRunning = true;
        $this->addToHistory($this->command);
        
        // Add command to output
        $this->appendOutput("\n\033[1;32m$ " . $this->command . "\033[0m\n");
        
        try {
            // Execute the command using Symfony Process
            $parsedCommand = $this->parseCommand($this->command);
            $this->appendOutput("\n\033[1;33m🔍 Parsed command: " . json_encode($parsedCommand) . "\033[0m\n");
            
            $process = new Process($parsedCommand);
            $process->setWorkingDirectory($this->currentDirectory);
            $process->setTimeout(300); // 5 minutes timeout
            
            // Set environment variables to include PHP path
            $env = $_ENV;
            $currentPath = $env['PATH'] ?? getenv('PATH') ?? '/usr/local/bin:/usr/bin:/bin';
            $env['PATH'] = '/opt/homebrew/opt/php@8.2/bin:' . $currentPath;
            $process->setEnv($env);
            
            $process->run(function ($type, $buffer) {
                $this->appendOutput($buffer);
            });

            if ($process->isSuccessful()) {
                $this->appendOutput("\n\033[1;32m✓ Command completed successfully\033[0m\n");
            } else {
                $this->appendOutput("\n\033[1;31m✗ Command failed with exit code: " . $process->getExitCode() . "\033[0m\n");
                $this->appendOutput("\033[1;31mError: " . $process->getErrorOutput() . "\033[0m\n");
            }

        } catch (\Exception $e) {
            $this->appendOutput("\n\033[1;31m✗ Error: " . $e->getMessage() . "\033[0m\n");
            Log::error('Terminal command error', [
                'command' => $this->command,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
        }

        $this->isRunning = false;
        $this->command = '';
        $this->historyIndex = -1;
    }

    /**
     * Execute command asynchronously (for long-running processes)
     */
    public function executeAsyncCommand($command = null)
    {
        if ($command) {
            $this->command = $command;
        }

        if (empty(trim($this->command))) {
            $this->appendOutput("\n\033[1;33m⚠ No command entered\033[0m\n");
            return;
        }

        $this->isRunning = true;
        $this->addToHistory($this->command);
        
        $this->appendOutput("\n\033[1;32m$ " . $this->command . "\033[0m\n");
        
        try {
            // Start async process using Symfony Process
            $process = new Process($this->parseCommand($this->command));
            $process->setWorkingDirectory($this->currentDirectory);
            $process->setTimeout(0); // No timeout for background processes
            
            // Set environment variables to include PHP path
            $env = $_ENV;
            $currentPath = $env['PATH'] ?? getenv('PATH') ?? '/usr/local/bin:/usr/bin:/bin';
            $env['PATH'] = '/opt/homebrew/opt/php@8.2/bin:' . $currentPath;
            $process->setEnv($env);
            
            // Start the process in background
            $process->start(function ($type, $buffer) {
                $this->appendOutput($buffer);
            });

            $this->processId = $process->getPid();
            
            // Store process info for monitoring
            Cache::put("terminal_process_{$this->processId}", [
                'command' => $this->command,
                'started_at' => now(),
                'user_id' => Auth::id(),
                'status' => 'running'
            ], 3600);

        } catch (\Exception $e) {
            $this->appendOutput("\n\033[1;31m✗ Error: " . $e->getMessage() . "\033[0m\n");
            $this->isRunning = false;
        }
    }

    /**
     * Stop a running process
     */
    public function stopProcess($processId = null)
    {
        $pid = $processId ?? $this->processId;
        
        if ($pid) {
            try {
                $killProcess = new Process(['kill', '-9', $pid]);
                $killProcess->run();
                $this->appendOutput("\n\033[1;33m⚠ Process {$pid} stopped\033[0m\n");
                
                // Update cache
                Cache::forget("terminal_process_{$pid}");
                
            } catch (\Exception $e) {
                $this->appendOutput("\n\033[1;31m✗ Error stopping process: " . $e->getMessage() . "\033[0m\n");
            }
        }
        
        $this->isRunning = false;
        $this->processId = null;
    }

    /**
     * Clear terminal output
     */
    public function clearOutput()
    {
        $this->output = '';
        $this->emit('terminalCleared');
    }

    /**
     * Start Claude Bridge
     */
    public function startClaudeBridge()
    {
        $this->claudeMode = true;
        $this->appendOutput("\n\033[1;36m🤖 Starting Claude Code Bridge...\033[0m\n");
        
        try {
            // Check if already running
            $markerFile = storage_path('app/claude-bridge/claude-monitor.active');
            if (file_exists($markerFile) && (time() - filemtime($markerFile) < 10)) {
                $this->appendOutput("\n\033[1;33m⚠ Claude Bridge is already running\033[0m\n");
                $this->claudeBridgeActive = true;
                return;
            }
            
                         // Start the Claude bridge in background using nohup
             $logFile = storage_path('logs/claude_bridge.log');
             $command = sprintf(
                 'nohup php %s > %s 2>&1 & echo $!',
                 base_path('zona_ai/claude_code_bridge_proper.php'),
                 $logFile
             );
             
             $process = new Process(['sh', '-c', $command]);
             $process->setWorkingDirectory(base_path());
             $process->run();
             
             if ($process->isSuccessful()) {
                 $pid = trim($process->getOutput());
                $this->claudeProcessId = $pid;
                $this->claudeBridgeActive = true;
                
                $this->appendOutput("\n\033[1;32m✓ Claude Bridge started (PID: {$pid})\033[0m\n");
                $this->appendOutput("\n\033[1;33m📋 Claude Bridge is now monitoring for requests...\033[0m\n");
                $this->appendOutput("\033[1;36mLog file: {$logFile}\033[0m\n");
                
                // Store Claude process info
                Cache::put("claude_bridge_process", [
                    'pid' => $pid,
                    'started_at' => now(),
                    'user_id' => Auth::id(),
                    'status' => 'running',
                    'log_file' => $logFile
                ], 3600);
            } else {
                throw new \Exception("Failed to start process: " . $result->errorOutput());
            }

        } catch (\Exception $e) {
            $this->appendOutput("\n\033[1;31m✗ Error starting Claude Bridge: " . $e->getMessage() . "\033[0m\n");
            $this->claudeBridgeActive = false;
        }
    }

    /**
     * Stop Claude Bridge
     */
    public function stopClaudeBridge()
    {
                 if ($this->claudeProcessId) {
             try {
                 $killProcess = new Process(['pkill', '-f', 'claude_code_bridge_proper']);
                 $killProcess->run();
                 $this->appendOutput("\n\033[1;33m⚠ Claude Bridge stopped\033[0m\n");
                
                Cache::forget("claude_bridge_process");
                
            } catch (\Exception $e) {
                $this->appendOutput("\n\033[1;31m✗ Error stopping Claude Bridge: " . $e->getMessage() . "\033[0m\n");
            }
        }
        
        $this->claudeBridgeActive = false;
        $this->claudeProcessId = null;
        $this->claudeMode = false;
    }

    /**
     * Check Claude Bridge status
     */
    public function checkClaudeBridgeStatus()
    {
        $claudeInfo = Cache::get("claude_bridge_process");
        if ($claudeInfo && $claudeInfo['status'] === 'running') {
            $this->claudeBridgeActive = true;
            $this->claudeProcessId = $claudeInfo['pid'];
            $this->claudeMode = true;
        }
    }

    /**
     * Test Claude Bridge connection
     */
    public function testClaudeBridge()
    {
        $this->appendOutput("\n\033[1;36m🧪 Testing Claude Bridge connection...\033[0m\n");
        
        try {
            $process = new Process(['php', 'artisan', 'claude:test-local', 'Hello Claude!']);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(60); // 1 minute timeout
            
            $process->run(function ($type, $buffer) {
                $this->appendOutput($buffer);
            });

            if ($process->isSuccessful()) {
                $this->appendOutput("\n\033[1;32m✓ Claude Bridge test successful\033[0m\n");
            } else {
                $this->appendOutput("\n\033[1;31m✗ Claude Bridge test failed\033[0m\n");
                $this->appendOutput("\033[1;31mError: " . $process->getErrorOutput() . "\033[0m\n");
            }

        } catch (\Exception $e) {
            $this->appendOutput("\n\033[1;31m✗ Error testing Claude Bridge: " . $e->getMessage() . "\033[0m\n");
        }
    }

    /**
     * View Claude Bridge logs
     */
    public function viewClaudeLogs()
    {
        $this->appendOutput("\n\033[1;36m📜 Claude Bridge Logs:\033[0m\n");
        
        $claudeInfo = Cache::get("claude_bridge_process");
        if ($claudeInfo && isset($claudeInfo['log_file'])) {
            $logFile = $claudeInfo['log_file'];
                         if (file_exists($logFile)) {
                 // Use tail command to get last 50 lines
                 $process = new Process(['tail', '-n', '50', $logFile]);
                 $process->run();
                 if ($process->isSuccessful()) {
                     $this->appendOutput($process->getOutput() . "\n");
                 } else {
                     $this->appendOutput("\033[1;31m✗ Could not read log file\033[0m\n");
                 }
            } else {
                $this->appendOutput("\033[1;33m⚠ Log file not found\033[0m\n");
            }
        } else {
            $this->appendOutput("\033[1;33m⚠ No Claude Bridge process information found\033[0m\n");
        }
    }
    
    /**
     * Check for pending Claude requests
     */
    public function checkClaudeRequests()
    {
        $this->appendOutput("\n\033[1;36m🔍 Checking for pending Claude requests...\033[0m\n");
        
        try {
            $process = new Process(['php', 'artisan', 'claude:check', '--detailed']);
            $process->setWorkingDirectory(base_path());
            $process->setTimeout(30); // 30 seconds timeout
            
            $process->run(function ($type, $buffer) {
                $this->appendOutput($buffer);
            });
        } catch (\Exception $e) {
            $this->appendOutput("\n\033[1;31m✗ Error checking requests: " . $e->getMessage() . "\033[0m\n");
        }
    }
    
    /**
     * Navigate command history
     */
    public function navigateHistory($direction)
    {
        if ($direction === 'up' && $this->historyIndex < count($this->commandHistory) - 1) {
            $this->historyIndex++;
            $this->command = $this->commandHistory[count($this->commandHistory) - 1 - $this->historyIndex];
        } elseif ($direction === 'down' && $this->historyIndex > 0) {
            $this->historyIndex--;
            $this->command = $this->commandHistory[count($this->commandHistory) - 1 - $this->historyIndex];
        } elseif ($direction === 'down' && $this->historyIndex === 0) {
            $this->historyIndex = -1;
            $this->command = '';
        }
    }

    /**
     * Change directory
     */
    public function changeDirectory($path)
    {
        $newPath = $path === '..' ? dirname($this->currentDirectory) : $this->currentDirectory . '/' . $path;
        
        if (is_dir($newPath)) {
            $this->currentDirectory = $newPath;
            $this->appendOutput("\n\033[1;34m📁 Changed to: {$this->currentDirectory}\033[0m\n");
        } else {
            $this->appendOutput("\n\033[1;31m✗ Directory not found: {$path}\033[0m\n");
        }
    }

    /**
     * List directory contents
     */
    public function listDirectory()
    {
        $this->appendOutput("\n\033[1;34m📁 Directory: {$this->currentDirectory}\033[0m\n");
        
        try {
            $items = scandir($this->currentDirectory);
            $output = [];
            
            foreach ($items as $item) {
                if ($item !== '.' && $item !== '..') {
                    $path = $this->currentDirectory . '/' . $item;
                    $type = is_dir($path) ? '📁' : '📄';
                    $output[] = "{$type} {$item}";
                }
            }
            
            $this->appendOutput(implode("\n", $output) . "\n");
            
        } catch (\Exception $e) {
            $this->appendOutput("\n\033[1;31m✗ Error listing directory: " . $e->getMessage() . "\033[0m\n");
        }
    }

    /**
     * Append output with line limit
     */
    private function appendOutput($text)
    {
        $this->output .= $text;
        
        // Limit output lines
        $lines = explode("\n", $this->output);
        if (count($lines) > $this->maxOutputLines) {
            $lines = array_slice($lines, -$this->maxOutputLines);
            $this->output = implode("\n", $lines);
        }
        
        $this->emit('outputUpdated');
    }

    /**
     * Add command to history
     */
    private function addToHistory($command)
    {
        if (!in_array($command, $this->commandHistory)) {
            $this->commandHistory[] = $command;
            
            // Keep only last 50 commands
            if (count($this->commandHistory) > 50) {
                $this->commandHistory = array_slice($this->commandHistory, -50);
            }
            
            $this->saveCommandHistory();
        }
    }

    /**
     * Load command history
     */
    private function loadCommandHistory()
    {
        $history = Cache::get("terminal_history_" . Auth::id(), []);
        $this->commandHistory = $history;
    }

    /**
     * Save command history
     */
    private function saveCommandHistory()
    {
        Cache::put("terminal_history_" . Auth::id(), $this->commandHistory, 86400); // 24 hours
    }

    /**
     * Get system information
     */
    public function getSystemInfo()
    {
        $this->appendOutput("\n\033[1;36m💻 System Information\033[0m\n");
        $this->appendOutput("OS: " . PHP_OS . "\n");
        $this->appendOutput("PHP Version: " . PHP_VERSION . "\n");
        $this->appendOutput("Laravel Version: " . app()->version() . "\n");
        $this->appendOutput("Current Directory: {$this->currentDirectory}\n");
        $this->appendOutput("User: " . Auth::user()->name . "\n");
    }

    /**
     * Parse command string into array for Symfony Process
     */
    private function parseCommand($command)
    {
        // Trim whitespace
        $command = trim($command);
        
        // If command is empty, return empty array
        if (empty($command)) {
            return [];
        }
        
        // Handle Claude CLI commands specifically
        if ($command === 'claude') {
            $this->appendOutput("\n\033[1;33m💡 Claude CLI Usage Tips:\033[0m\n");
            $this->appendOutput("• \033[1;36mclaude \"Your question here\"\033[0m - Ask Claude a question\n");
            $this->appendOutput("• \033[1;36mclaude --help\033[0m - Show Claude CLI help\n");
            $this->appendOutput("• \033[1;36mclaude -p \"Your question\"\033[0m - Print response and exit\n");
            $this->appendOutput("• \033[1;36mclaude -c\033[0m - Continue last conversation\n");
            $this->appendOutput("\n\033[1;33m💡 For SACCOS AI, use:\033[0m\n");
            $this->appendOutput("• \033[1;36mphp artisan claude:test-local \"Your question\"\033[0m\n");
            $this->appendOutput("• Or use the Claude Bridge Quick Actions above\n");
            return ['echo', 'Claude CLI requires a prompt. See tips above.'];
        }
        
        // Handle Claude CLI typos
        if ($command === 'claud') {
            $this->appendOutput("\n\033[1;33m💡 Did you mean 'claude'?\033[0m\n");
            $this->appendOutput("• \033[1;36mclaude \"Your question here\"\033[0m - Ask Claude a question\n");
            $this->appendOutput("• \033[1;36mclaude setup-token\033[0m - Set up authentication (if needed)\n");
            $this->appendOutput("\n\033[1;33m💡 For SACCOS AI, use:\033[0m\n");
            $this->appendOutput("• \033[1;36mphp artisan claude:test-local \"Your question\"\033[0m\n");
            return ['echo', 'Did you mean "claude"? See tips above.'];
        }
        
        // Handle Claude CLI commands with arguments
        if (strpos($command, 'claude ') === 0) {
            // Check if it's a setup command
            if (strpos($command, 'claude setup-token') === 0) {
                $this->appendOutput("\n\033[1;33m🔐 Claude CLI Authentication Setup\033[0m\n");
                $this->appendOutput("This will open a browser to authenticate with Claude.\n");
                $this->appendOutput("Follow the prompts to set up your API key.\n");
                return explode(' ', $command);
            }
            
            // For other Claude commands, use direct execution
            return explode(' ', $command);
        }
        
        // Handle PHP commands specifically
        if (strpos($command, 'php artisan') === 0) {
            $phpPath = $this->getPhpPath();
            $artisanCommand = substr($command, 4); // Remove 'php ' from the beginning
            $parts = explode(' ', $artisanCommand);
            return array_merge([$phpPath, 'artisan'], $parts);
        }
        
        // Handle PHP commands
        if (strpos($command, 'php ') === 0) {
            $phpPath = $this->getPhpPath();
            $phpCommand = substr($command, 4); // Remove 'php ' from the beginning
            $parts = explode(' ', $phpCommand);
            return array_merge([$phpPath], $parts);
        }
        
        // For commands with quotes or complex arguments, use shell
        if (strpos($command, '"') !== false || strpos($command, "'") !== false || strpos($command, ' ') !== false) {
            // Use shell for complex commands, but handle Claude CLI specially
            if (strpos($command, 'claude') === 0) {
                // For Claude CLI, use direct execution
                return explode(' ', $command);
            }
            // Use shell for complex commands
            return ['sh', '-c', $command];
        } else {
            // For simple single-word commands
            return [$command];
        }
    }

    /**
     * Get the full path to PHP executable
     */
    private function getPhpPath()
    {
        // Try to find PHP in common locations
        $possiblePaths = [
            '/opt/homebrew/opt/php@8.2/bin/php',
            '/usr/bin/php',
            '/usr/local/bin/php',
            '/opt/homebrew/bin/php',
            exec('which php')
        ];

        foreach ($possiblePaths as $path) {
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        return 'php'; // Fallback
    }
}
