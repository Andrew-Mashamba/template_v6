<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Manages a queue of queries for efficient processing
 */
class ClaudeQueryQueue
{
    private static $instance = null;
    private $queue = [];
    private $isProcessing = false;
    private $processManager;
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct()
    {
        $this->processManager = ClaudeProcessManager::getInstance();
    }
    
    /**
     * Add a query to the queue
     */
    public function addQuery(string $message, array $options = []): string
    {
        $queryId = uniqid('query_', true);
        
        Log::info('[QUEUE-ADD] Adding query to queue', [
            'query_id' => $queryId,
            'message_length' => strlen($message),
            'has_options' => !empty($options),
            'queue_length' => count($this->queue),
            'is_processing' => $this->isProcessing
        ]);
        
        $query = [
            'id' => $queryId,
            'message' => $message,
            'options' => $options,
            'status' => 'pending',
            'response' => null,
            'error' => null,
            'created_at' => microtime(true),
            'stream_callback' => $options['stream_callback'] ?? null
        ];
        
        // Store in cache for persistence
        Cache::put("claude_query_{$queryId}", $query, 300); // 5 minutes TTL
        
        // Add to queue
        $this->queue[] = $queryId;
        
        Log::debug('[QUEUE-ADD] Query added', [
            'query_id' => $queryId,
            'new_queue_length' => count($this->queue)
        ]);
        
        // Process queue if not already processing
        if (!$this->isProcessing) {
            Log::debug('[QUEUE-ADD] Starting queue processing', [
                'query_id' => $queryId
            ]);
            $this->processQueue();
        } else {
            Log::debug('[QUEUE-ADD] Queue already processing, query will be processed soon', [
                'query_id' => $queryId
            ]);
        }
        
        return $queryId;
    }
    
    /**
     * Process the queue
     */
    private function processQueue(): void
    {
        if ($this->isProcessing || empty($this->queue)) {
            Log::debug('[QUEUE-PROCESS] Skipping processing', [
                'is_processing' => $this->isProcessing,
                'queue_empty' => empty($this->queue)
            ]);
            return;
        }
        
        $this->isProcessing = true;
        $processStartTime = microtime(true);
        $processedCount = 0;
        
        Log::info('[QUEUE-PROCESS-START] Beginning queue processing', [
            'queue_length' => count($this->queue),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        try {
            while (!empty($this->queue)) {
                $queryId = array_shift($this->queue);
                $queryStartTime = microtime(true);
                
                Log::debug('[QUEUE-PROCESS] Processing query', [
                    'query_id' => $queryId,
                    'remaining_in_queue' => count($this->queue)
                ]);
                
                $query = Cache::get("claude_query_{$queryId}");
                
                if (!$query) {
                    Log::warning('[QUEUE-PROCESS] Query not found in cache', [
                        'query_id' => $queryId
                    ]);
                    continue;
                }
                
                // Update status
                $query['status'] = 'processing';
                Cache::put("claude_query_{$queryId}", $query, 300);
                
                // Process with streaming support
                $streamCallback = null;
                if (isset($query['stream_callback'])) {
                    $streamCallback = function($chunk) use ($queryId, &$query) {
                        // Update partial response in cache
                        $query['response'] = ($query['response'] ?? '') . $chunk;
                        Cache::put("claude_query_{$queryId}", $query, 300);
                        
                        // Call original callback if provided
                        if (is_callable($query['stream_callback'])) {
                            call_user_func($query['stream_callback'], $chunk, $queryId);
                        }
                    };
                }
                
                // Send to process manager
                Log::debug('[QUEUE-PROCESS] Sending to process manager', [
                    'query_id' => $queryId
                ]);
                
                $response = $this->processManager->sendMessage(
                    $query['message'],
                    array_merge($query['options'], ['stream_callback' => $streamCallback])
                );
                
                // Update query result
                $query['status'] = $response['success'] ? 'completed' : 'failed';
                $query['response'] = $response['message'] ?? null;
                $query['error'] = $response['error'] ?? null;
                $query['completed_at'] = microtime(true);
                $query['processing_time'] = $query['completed_at'] - $query['created_at'];
                
                Cache::put("claude_query_{$queryId}", $query, 300);
                
                $queryTime = microtime(true) - $queryStartTime;
                $processedCount++;
                
                Log::info('[QUEUE-PROCESS-QUERY] Query processed', [
                    'query_id' => $queryId,
                    'status' => $query['status'],
                    'query_time' => round($queryTime, 2),
                    'total_processing_time' => round($query['processing_time'], 2),
                    'response_length' => strlen($query['response'] ?? ''),
                    'error' => $query['error']
                ]);
            }
            
            $totalProcessTime = microtime(true) - $processStartTime;
            
            Log::info('[QUEUE-PROCESS-COMPLETE] Queue processing completed', [
                'queries_processed' => $processedCount,
                'total_time' => round($totalProcessTime, 2),
                'avg_time_per_query' => $processedCount > 0 ? round($totalProcessTime / $processedCount, 2) : 0
            ]);
        } catch (Exception $e) {
            Log::error('[QUEUE-PROCESS-ERROR] Queue processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'queries_processed' => $processedCount
            ]);
        } finally {
            $this->isProcessing = false;
            Log::debug('[QUEUE-PROCESS-END] Queue processing finished', [
                'is_processing' => $this->isProcessing,
                'queue_length' => count($this->queue)
            ]);
        }
    }
    
    /**
     * Get query result
     */
    public function getQueryResult(string $queryId): ?array
    {
        return Cache::get("claude_query_{$queryId}");
    }
    
    /**
     * Get query status
     */
    public function getQueryStatus(string $queryId): ?string
    {
        $query = Cache::get("claude_query_{$queryId}");
        return $query['status'] ?? null;
    }
    
    /**
     * Wait for query completion
     */
    public function waitForQuery(string $queryId, int $timeout = 60): ?array
    {
        $startTime = microtime(true);
        
        while (microtime(true) - $startTime < $timeout) {
            $query = $this->getQueryResult($queryId);
            
            if ($query && in_array($query['status'], ['completed', 'failed'])) {
                return $query;
            }
            
            usleep(100000); // 100ms
        }
        
        return null;
    }
    
    /**
     * Get queue stats
     */
    public function getStats(): array
    {
        return [
            'queue_length' => count($this->queue),
            'is_processing' => $this->isProcessing,
            'process_status' => $this->processManager->getStatus()
        ];
    }
}