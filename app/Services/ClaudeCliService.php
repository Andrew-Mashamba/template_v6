<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class ClaudeCliService
{
    private $timeout = 300; // 5 minutes
    private $maxRetries = 3;
    private $retryDelay = 1000000; // 1 second in microseconds
    
    /**
     * Send a message to Claude CLI
     * Clean communication layer - no database queries or context building here
     */
    public function sendMessage(string $message, array $options = []): array
    {
        $startTime = microtime(true);
        
        // LOG POINT 18: ClaudeCliService Entry
        Log::info('=== [CLAUDE-CLI-SERVICE] START ===', [
            'message_preview' => substr($message, 0, 100),
            'message_length' => strlen($message),
            'options_keys' => array_keys($options),
            'session_id' => $options['session_id'] ?? 'unknown',
            'timestamp' => now()->toISOString()
        ]);
        
        try {
            // Set max execution time
            set_time_limit($this->timeout);
            
            // Log the request
            $this->logRequest($message, $options);
            
            // Execute Claude CLI command
            $response = $this->executeClaudeCommand($message, $options);
            
            // Process and validate response
            $result = $this->processResponse($response, $message);
            
            // LOG POINT 19: ClaudeCliService Response
            Log::channel('daily')->info('🔷 [PROMPT-CHAIN] ClaudeCliService Response', [
                'success' => $result['success'],
                'response_length' => strlen($result['message'] ?? ''),
                'execution_time' => round(microtime(true) - $startTime, 3),
                'session_id' => $options['session_id'] ?? 'unknown',
                'step' => 19,
                'location' => 'ClaudeCliService::sendMessage::response'
            ]);
            
            // Log successful execution
            $this->logResponse($result, microtime(true) - $startTime);
            
            return $result;
            
        } catch (Exception $e) {
            return $this->handleError($e, $message);
        }
    }
    
    /**
     * Execute the Claude CLI command
     */
    private function executeClaudeCommand(string $message, array $options = []): ?string
    {
        $attempt = 0;
        $lastError = null;
        
        while ($attempt < $this->maxRetries) {
            $attempt++;
            
            try {
                // Escape the message for shell execution
                $escapedMessage = escapeshellarg($message);
                
                // Build the command
                $command = $this->buildCommand($escapedMessage, $options);
                
                Log::debug('[CLAUDE-CLI-SERVICE] Executing command', [
                    'attempt' => $attempt,
                    'command_length' => strlen($command),
                    'command_preview' => substr($command, 0, 200)
                ]);
                
                // Execute command
                $output = shell_exec($command);
                
                Log::debug('[CLAUDE-CLI-SERVICE] Command executed', [
                    'attempt' => $attempt,
                    'has_output' => $output !== null,
                    'output_length' => $output !== null ? strlen($output) : 0,
                    'output_preview' => $output !== null ? substr($output, 0, 100) : null
                ]);
                
                if ($output !== null && !empty(trim($output))) {
                    return $output;
                }
                
                $lastError = "Empty response from Claude CLI";
                
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                Log::warning('Claude CLI execution attempt failed', [
                    'attempt' => $attempt,
                    'error' => $lastError
                ]);
            }
            
            if ($attempt < $this->maxRetries) {
                usleep($this->retryDelay);
            }
        }
        
        throw new Exception("Failed after {$this->maxRetries} attempts: " . $lastError);
    }
    
    /**
     * Build the Claude CLI command
     */
    private function buildCommand(string $escapedMessage, array $options = []): string
    {
        $baseCommand = "claude " . $escapedMessage;
        
        // Add any additional flags from options
        if (isset($options['flags'])) {
            foreach ($options['flags'] as $flag => $value) {
                if ($value === true) {
                    $baseCommand .= " --{$flag}";
                } else {
                    $baseCommand .= " --{$flag}=" . escapeshellarg($value);
                }
            }
        }
        
        // Redirect stderr to stdout to capture any errors
        $baseCommand .= " 2>&1";
        
        return $baseCommand;
    }
    
    /**
     * Process the response from Claude CLI
     */
    private function processResponse(?string $output, string $originalMessage): array
    {
        if ($output === null) {
            return [
                'success' => false,
                'message' => 'No response from Claude CLI',
                'error' => 'NO_OUTPUT'
            ];
        }
        
        $output = trim($output);
        
        // Check for common errors
        if ($this->isErrorResponse($output)) {
            return $this->parseErrorResponse($output);
        }
        
        // Successful response
        return [
            'success' => true,
            'message' => $output,
            'metadata' => [
                'source' => 'claude_cli',
                'has_project_context' => true,
                'timestamp' => now()->toIso8601String()
            ]
        ];
    }
    
    /**
     * Check if the response is an error
     */
    private function isErrorResponse(string $output): bool
    {
        $errorIndicators = [
            'command not found',
            'error:',
            'Error:',
            'ERROR:',
            'fatal:',
            'Fatal:',
            'permission denied',
            'not installed'
        ];
        
        foreach ($errorIndicators as $indicator) {
            if (stripos($output, $indicator) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Parse error response
     */
    private function parseErrorResponse(string $output): array
    {
        $errorCode = 'UNKNOWN_ERROR';
        $message = $output;
        
        if (stripos($output, 'command not found') !== false) {
            $errorCode = 'NOT_INSTALLED';
            $message = 'Claude CLI is not installed. Please install it first.';
        } elseif (stripos($output, 'permission denied') !== false) {
            $errorCode = 'PERMISSION_DENIED';
            $message = 'Permission denied when executing Claude CLI.';
        }
        
        return [
            'success' => false,
            'message' => $message,
            'error' => $errorCode,
            'raw_output' => $output
        ];
    }
    
    /**
     * Handle errors
     */
    private function handleError(Exception $e, string $message): array
    {
        Log::error('Claude CLI Service Error', [
            'error' => $e->getMessage(),
            'message' => $message,
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'message' => 'Error communicating with Claude CLI: ' . $e->getMessage(),
            'error' => 'EXCEPTION'
        ];
    }
    
    /**
     * Log the request
     */
    private function logRequest(string $message, array $options = []): void
    {
        if (config('app.debug')) {
            Log::debug('Claude CLI Request', [
                'message' => substr($message, 0, 500),
                'options' => $options
            ]);
        }
    }
    
    /**
     * Log the response
     */
    private function logResponse(array $result, float $executionTime): void
    {
        if (config('app.debug')) {
            Log::debug('Claude CLI Response', [
                'success' => $result['success'],
                'execution_time' => round($executionTime, 3) . 's',
                'response_length' => strlen($result['message'] ?? '')
            ]);
        }
    }
    
    /**
     * Check if Claude CLI is available
     */
    public function isAvailable(): bool
    {
        $cacheKey = 'claude_cli_available';
        
        // Check cache first
        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }
        
        // Check if Claude CLI is installed
        $output = shell_exec('which claude 2>&1');
        $isAvailable = !empty(trim($output));
        
        // Cache the result for 5 minutes
        Cache::put($cacheKey, $isAvailable, 300);
        
        return $isAvailable;
    }
    
    /**
     * Test Claude CLI connection
     */
    public function testConnection(): array
    {
        return $this->sendMessage("Hello, confirm you're connected to the SACCOS Core System.");
    }
    
    /**
     * Get Claude CLI version
     */
    public function getVersion(): ?string
    {
        $output = shell_exec('claude --version 2>&1');
        return $output ? trim($output) : null;
    }
}