<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

/**
 * Manages a persistent Claude process to maintain context between messages
 */
class ClaudeProcessManager
{
    private static $instance = null;
    private $process = null;
    private $pipes = [];
    private $sessionId = null;
    private $conversationHistory = [];
    private $isReady = false;
    private $outputBuffer = '';
    private $responseCallbacks = [];
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        // Private constructor for singleton
        $this->sessionId = 'claude_session_' . uniqid();
        $this->startProcess();
    }
    
    /**
     * Start or restart the Claude process
     */
    public function startProcess(): bool
    {
        $startTime = microtime(true);
        
        Log::info('[START-PROCESS] Beginning Claude process startup', [
            'session_id' => $this->sessionId,
            'timestamp' => date('Y-m-d H:i:s'),
            'existing_process' => $this->process !== null
        ]);
        
        try {
            // Close existing process if any
            if ($this->process) {
                Log::debug('[START-PROCESS] Closing existing process', [
                    'session_id' => $this->sessionId
                ]);
                $this->closeProcess();
            }
            
            // Get Claude path and config
            $claudePath = $this->getClaudePath();
            if (!$claudePath) {
                Log::error('[START-PROCESS] Claude CLI not found', [
                    'session_id' => $this->sessionId
                ]);
                throw new Exception('Claude CLI not found');
            }
            
            Log::debug('[START-PROCESS] Claude path found', [
                'session_id' => $this->sessionId,
                'path' => $claudePath
            ]);
            
            // Build command with persistent session
            $allowedTools = $this->getAllowedToolsFlags();
            $mcpConfig = escapeshellarg(base_path('.mcp.json'));
            $contextFile = base_path('zona_ai/context.md');
            
            // Start Claude in interactive mode with context
            $command = "{$claudePath} {$allowedTools} --mcp-config {$mcpConfig}";
            
            // Setup process with pipes
            $descriptors = [
                0 => ['pipe', 'r'], // stdin
                1 => ['pipe', 'w'], // stdout  
                2 => ['pipe', 'w']  // stderr
            ];
            
            Log::debug('[START-PROCESS] Starting proc_open', [
                'session_id' => $this->sessionId,
                'command_length' => strlen($command)
            ]);
            
            $this->process = @proc_open($command, $descriptors, $this->pipes, base_path());
            
            if (!is_resource($this->process)) {
                $error = error_get_last();
                Log::error('[START-PROCESS] proc_open failed', [
                    'session_id' => $this->sessionId,
                    'error' => $error['message'] ?? 'Unknown error'
                ]);
                throw new Exception('Failed to start Claude process: ' . ($error['message'] ?? 'Unknown error'));
            }
            
            Log::debug('[START-PROCESS] Process started successfully', [
                'session_id' => $this->sessionId
            ]);
            
            // Set non-blocking mode for streaming
            stream_set_blocking($this->pipes[1], false);
            stream_set_blocking($this->pipes[2], false);
            
            // Send initial context
            $this->sendInitialContext($contextFile);
            
            $this->isReady = true;
            
            $totalTime = microtime(true) - $startTime;
            
            Log::info('[START-PROCESS-SUCCESS] Claude process started successfully', [
                'session_id' => $this->sessionId,
                'pid' => $this->getProcessPid(),
                'startup_time' => round($totalTime, 2),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return true;
            
        } catch (Exception $e) {
            $totalTime = microtime(true) - $startTime;
            
            Log::error('[START-PROCESS-ERROR] Failed to start Claude process', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'startup_time' => round($totalTime, 2),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            $this->isReady = false;
            $this->process = null;
            $this->pipes = [];
            
            return false;
        }
    }
    
    /**
     * Send initial context to Claude
     */
    private function sendInitialContext(string $contextFile): void
    {
        Log::debug('[INIT-CONTEXT] Sending initial context', [
            'session_id' => $this->sessionId,
            'context_file' => $contextFile,
            'file_exists' => file_exists($contextFile)
        ]);
        
        $initialMessage = "You are a persistent AI assistant for the SACCOS Core System.\n";
        $initialMessage .= "Session ID: {$this->sessionId}\n";
        $initialMessage .= "Please read the context file at: {$contextFile}\n";
        $initialMessage .= "Maintain conversation history across messages in this session.\n";
        $initialMessage .= "Ready to assist with database queries and system operations.\n\n";
        
        $writeResult = @fwrite($this->pipes[0], $initialMessage);
        if ($writeResult === false) {
            Log::error('[INIT-CONTEXT] Failed to write initial context', [
                'session_id' => $this->sessionId
            ]);
        } else {
            Log::debug('[INIT-CONTEXT] Initial context written', [
                'session_id' => $this->sessionId,
                'bytes_written' => $writeResult
            ]);
        }
        
        @fflush($this->pipes[0]);
        
        // Read initial response
        usleep(500000); // Wait 500ms for initialization
        $initialResponse = $this->readAvailableOutput();
        
        if (!empty($initialResponse)) {
            Log::debug('[INIT-CONTEXT] Received initial response', [
                'session_id' => $this->sessionId,
                'response_length' => strlen($initialResponse),
                'response_preview' => substr($initialResponse, 0, 100)
            ]);
        }
    }
    
    /**
     * Send a message to the persistent Claude process
     */
    public function sendMessage(string $message, array $options = []): array
    {
        $messageStart = microtime(true);
        
        Log::info('[SEND-MESSAGE-START] Beginning message processing', [
            'session_id' => $this->sessionId,
            'message_length' => strlen($message),
            'message_preview' => substr($message, 0, 100),
            'has_streaming' => isset($options['enable_streaming']) && $options['enable_streaming'],
            'stream_to_session' => $options['stream_to_session'] ?? null,
            'has_options' => !empty($options),
            'is_ready' => $this->isReady,
            'has_process' => $this->process !== null
        ]);
        
        // Set max execution time for this operation
        set_time_limit(120);
        
        if (!$this->isReady || !$this->process) {
            Log::warning('[SEND-MESSAGE] Process not ready, starting new process', [
                'session_id' => $this->sessionId
            ]);
            $this->startProcess();
        }
        
        try {
            // Check if process is still running
            if ($this->process) {
                $status = proc_get_status($this->process);
                if (!$status['running']) {
                    Log::error('[SEND-MESSAGE] Process dead, restarting', [
                        'session_id' => $this->sessionId,
                        'exit_code' => $status['exitcode'] ?? 'unknown'
                    ]);
                    $this->startProcess();
                }
            }
            
            // Add to conversation history
            $this->conversationHistory[] = [
                'role' => 'user',
                'content' => $message,
                'timestamp' => microtime(true)
            ];
            
            Log::debug('[SEND-MESSAGE] Added to history', [
                'session_id' => $this->sessionId,
                'history_count' => count($this->conversationHistory)
            ]);
            
            // Prepare message with session context
            $enhancedMessage = $this->buildSessionMessage($message, $options);
            
            Log::debug('[SEND-MESSAGE] Sending to Claude', [
                'session_id' => $this->sessionId,
                'enhanced_length' => strlen($enhancedMessage)
            ]);
            
            // Send to Claude with error handling
            $writeResult = @fwrite($this->pipes[0], $enhancedMessage . "\n");
            if ($writeResult === false) {
                Log::error('[SEND-MESSAGE] Failed to write to pipe', [
                    'session_id' => $this->sessionId
                ]);
                throw new Exception('Failed to send message to Claude process');
            }
            
            $flushResult = @fflush($this->pipes[0]);
            if ($flushResult === false) {
                Log::warning('[SEND-MESSAGE] Failed to flush pipe', [
                    'session_id' => $this->sessionId
                ]);
            }
            
            // Stream response
            Log::debug('[SEND-MESSAGE] Starting response stream', [
                'session_id' => $this->sessionId
            ]);
            
            $response = $this->streamResponse($options['stream_callback'] ?? null);
            
            // Add to history
            $this->conversationHistory[] = [
                'role' => 'assistant',
                'content' => $response,
                'timestamp' => microtime(true)
            ];
            
            $totalTime = microtime(true) - $messageStart;
            
            Log::info('[SEND-MESSAGE-SUCCESS] Message processed successfully', [
                'session_id' => $this->sessionId,
                'total_time' => round($totalTime, 2),
                'response_length' => strlen($response),
                'conversation_count' => count($this->conversationHistory)
            ]);
            
            return [
                'success' => true,
                'message' => $response,
                'session_id' => $this->sessionId,
                'conversation_count' => count($this->conversationHistory),
                'processing_time' => round($totalTime, 2)
            ];
            
        } catch (Exception $e) {
            $totalTime = microtime(true) - $messageStart;
            
            Log::error('[SEND-MESSAGE-ERROR] Failed to process message', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'total_time' => round($totalTime, 2)
            ]);
            
            // Try recovery strategies
            if (strpos($e->getMessage(), 'timeout') !== false) {
                Log::warning('[SEND-MESSAGE] Attempting timeout recovery', [
                    'session_id' => $this->sessionId
                ]);
                
                // No fallback - return error
                Log::error('[SEND-MESSAGE] Timeout without fallback', [
                    'session_id' => $this->sessionId
                ]);
            }
            
            // Try to restart process for next time
            try {
                $this->startProcess();
            } catch (Exception $restartError) {
                Log::error('[SEND-MESSAGE] Failed to restart process', [
                    'session_id' => $this->sessionId,
                    'error' => $restartError->getMessage()
                ]);
            }
            
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'processing_time' => round($totalTime, 2)
            ];
        }
    }
    
    /**
     * Stream response from Claude with callback
     */
    private function streamResponse(?callable $callback = null): string
    {
        Log::info('[STREAM-START] Beginning response stream', [
            'session_id' => $this->sessionId,
            'has_callback' => $callback !== null,
            'timestamp' => microtime(true)
        ]);
        
        // Set max execution time for this operation
        set_time_limit(120); // 2 minutes max
        
        $response = '';
        $startTime = microtime(true);
        $timeout = 30; // Reduced to 30 seconds timeout
        $lastOutputTime = microtime(true);
        $silenceThreshold = 1.5; // Reduced to 1.5 seconds of silence
        $checkInterval = 0;
        
        while (true) {
            $checkInterval++;
            $elapsedTime = microtime(true) - $startTime;
            
            // Log every 10 checks
            if ($checkInterval % 10 === 0) {
                Log::debug('[STREAM-PROGRESS] Stream check', [
                    'session_id' => $this->sessionId,
                    'elapsed_time' => round($elapsedTime, 2),
                    'response_length' => strlen($response),
                    'checks_made' => $checkInterval
                ]);
            }
            
            // Check timeout with detailed logging
            if ($elapsedTime > $timeout) {
                Log::error('[STREAM-TIMEOUT] Response timeout reached', [
                    'session_id' => $this->sessionId,
                    'timeout' => $timeout,
                    'elapsed_time' => round($elapsedTime, 2),
                    'response_so_far' => substr($response, 0, 500),
                    'response_length' => strlen($response)
                ]);
                
                // If we have partial response, return it
                if (!empty($response)) {
                    Log::warning('[STREAM-PARTIAL] Returning partial response due to timeout', [
                        'session_id' => $this->sessionId,
                        'response_length' => strlen($response)
                    ]);
                    return $response;
                }
                
                // No fallback - throw timeout exception
                throw new Exception('Response timeout - Claude CLI not responding');
            }
            
            // Read available output
            try {
                $chunk = $this->readAvailableOutput();
                
                if (!empty($chunk)) {
                    Log::debug('[STREAM-CHUNK] Received chunk', [
                        'session_id' => $this->sessionId,
                        'chunk_size' => strlen($chunk),
                        'total_response' => strlen($response) + strlen($chunk)
                    ]);
                    $response .= $chunk;
                    $lastOutputTime = microtime(true);
                    
                    // Call streaming callback if provided
                    if ($callback) {
                        try {
                            $callback($chunk);
                        } catch (Exception $e) {
                            Log::error('[STREAM-CALLBACK-ERROR] Callback failed', [
                                'session_id' => $this->sessionId,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                }
            } catch (Exception $e) {
                Log::error('[STREAM-READ-ERROR] Failed to read output', [
                    'session_id' => $this->sessionId,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Check if response is complete
            $silenceTime = microtime(true) - $lastOutputTime;
            if (!empty($response) && ($silenceTime > $silenceThreshold)) {
                Log::info('[STREAM-COMPLETE] Response complete after silence', [
                    'session_id' => $this->sessionId,
                    'silence_time' => round($silenceTime, 2),
                    'total_time' => round(microtime(true) - $startTime, 2),
                    'response_length' => strlen($response)
                ]);
                break;
            }
            
            // Small delay to prevent CPU spinning
            usleep(50000); // 50ms
        }
        
        Log::info('[STREAM-END] Stream completed', [
            'session_id' => $this->sessionId,
            'total_time' => round(microtime(true) - $startTime, 2),
            'response_length' => strlen($response),
            'checks_made' => $checkInterval
        ]);
        
        return $response;
    }
    
    /**
     * Read available output from Claude
     */
    private function readAvailableOutput(): string
    {
        $output = '';
        $readStart = microtime(true);
        
        try {
            if (isset($this->pipes[1])) {
                // Check if stream is still valid
                if (!is_resource($this->pipes[1])) {
                    Log::error('[READ-OUTPUT] Stdout pipe is not a valid resource', [
                        'session_id' => $this->sessionId
                    ]);
                    throw new Exception('Stdout pipe closed');
                }
                
                $chunk = @stream_get_contents($this->pipes[1]);
                if ($chunk !== false) {
                    if (!empty($chunk)) {
                        Log::debug('[READ-OUTPUT] Read chunk from stdout', [
                            'session_id' => $this->sessionId,
                            'chunk_size' => strlen($chunk),
                            'read_time' => round(microtime(true) - $readStart, 3)
                        ]);
                    }
                    $output .= $chunk;
                } else {
                    Log::warning('[READ-OUTPUT] Failed to read from stdout', [
                        'session_id' => $this->sessionId
                    ]);
                }
            } else {
                Log::error('[READ-OUTPUT] Stdout pipe not set', [
                    'session_id' => $this->sessionId
                ]);
            }
            
            // Also check stderr for any errors
            if (isset($this->pipes[2])) {
                if (is_resource($this->pipes[2])) {
                    $error = @stream_get_contents($this->pipes[2]);
                    if ($error !== false && !empty($error)) {
                        // Only log non-trivial errors
                        if (strlen($error) > 2 && !preg_match('/^\s+$/', $error)) {
                            Log::warning('[READ-OUTPUT] Claude stderr output', [
                                'session_id' => $this->sessionId,
                                'error' => substr($error, 0, 500),
                                'error_length' => strlen($error)
                            ]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error('[READ-OUTPUT-ERROR] Error reading output', [
                'session_id' => $this->sessionId,
                'error' => $e->getMessage(),
                'read_time' => round(microtime(true) - $readStart, 3)
            ]);
        }
        
        return $output;
    }
    
    /**
     * Build message with session context
     */
    private function buildSessionMessage(string $message, array $options): string
    {
        $enhanced = "\n[MESSAGE #{" . count($this->conversationHistory) . "}]\n";
        
        // Add user info if available
        if (!empty($options['user_name'])) {
            $enhanced .= "User: {$options['user_name']}\n";
        }
        
        // Reference previous context
        if (count($this->conversationHistory) > 0) {
            $enhanced .= "This is a continuation of our conversation.\n";
        }
        
        $enhanced .= "\n{$message}\n";
        
        return $enhanced;
    }
    
    /**
     * Get conversation history
     */
    public function getHistory(): array
    {
        return $this->conversationHistory;
    }
    
    /**
     * Clear conversation history but keep process running
     */
    public function clearHistory(): void
    {
        $this->conversationHistory = [];
        
        // Send clear context message to Claude
        if ($this->isReady && $this->process) {
            fwrite($this->pipes[0], "\n[CLEAR CONTEXT]\nStarting fresh conversation. Previous context cleared.\n\n");
            fflush($this->pipes[0]);
        }
    }
    
    /**
     * Get process PID for monitoring
     */
    private function getProcessPid(): ?int
    {
        if ($this->process) {
            $status = proc_get_status($this->process);
            return $status['pid'] ?? null;
        }
        return null;
    }
    
    
    /**
     * Close the Claude process
     */
    public function closeProcess(): void
    {
        if ($this->process) {
            // Close pipes
            if (isset($this->pipes[0])) fclose($this->pipes[0]);
            if (isset($this->pipes[1])) fclose($this->pipes[1]);
            if (isset($this->pipes[2])) fclose($this->pipes[2]);
            
            // Terminate process
            proc_terminate($this->process);
            proc_close($this->process);
            
            $this->process = null;
            $this->pipes = [];
            $this->isReady = false;
            
            Log::info('Claude process closed', [
                'session_id' => $this->sessionId
            ]);
        }
    }
    
    /**
     * Get Claude CLI path
     */
    private function getClaudePath(): ?string
    {
        return Cache::remember('claude_cli_path', 3600, function() {
            $path = trim(shell_exec('which claude 2>&1'));
            return (!empty($path) && strpos($path, 'claude') !== false) ? $path : null;
        });
    }
    
    /**
     * Get allowed tools flags
     */
    private function getAllowedToolsFlags(): string
    {
        return Cache::rememberForever('claude_allowed_tools', function() {
            $allowedTools = [
                '"Bash(psql*)"',
                '"Read(**)"',
                '"Write(public/reports/**)"',
                '"Bash(ls*)"',
                '"Bash(echo*)"',
                '"mcp__ide__getDiagnostics"',
                '"mcp__ide__executeCode"'
            ];
            
            $flags = '';
            foreach ($allowedTools as $tool) {
                $flags .= ' --allowed-tools ' . $tool;
            }
            return $flags;
        });
    }
    
    /**
     * Destructor - ensure process is closed
     */
    public function __destruct()
    {
        $this->closeProcess();
    }
    
    /**
     * Get process status
     */
    public function getStatus(): array
    {
        $status = [
            'session_id' => $this->sessionId,
            'is_ready' => $this->isReady,
            'pid' => $this->getProcessPid(),
            'conversation_count' => count($this->conversationHistory),
            'uptime' => $this->isReady ? (microtime(true) - ($this->conversationHistory[0]['timestamp'] ?? microtime(true))) : 0
        ];
        
        if ($this->process) {
            $procStatus = proc_get_status($this->process);
            $status['running'] = $procStatus['running'] ?? false;
        }
        
        return $status;
    }
}