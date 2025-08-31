<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Services\DirectClaudeCliService;
use Illuminate\Support\Facades\Log;

class StreamController extends Controller
{
    protected $claudeCliService;

    public function __construct()
    {
        // Initialize DirectClaudeCliService - this is the ONLY service we use
        try {
            $this->claudeCliService = new DirectClaudeCliService();
            Log::info('[STREAM-CONTROLLER] DirectClaudeCliService initialized successfully');
        } catch (\Exception $e) {
            Log::error('[STREAM-CONTROLLER] Failed to initialize DirectClaudeCliService', [
                'error' => $e->getMessage()
            ]);
            $this->claudeCliService = null;
        }
    }

    /**
     * Process AI message and initiate streaming response
     */
    public function process(Request $request)
    {
        Log::info('=== STREAM CONTROLLER: PROCESS START ===', [
            'method' => 'POST /ai/process',
            'ip' => $request->ip(),
            'user_id' => auth()->id(),
            'timestamp' => now()->toISOString()
        ]);
        
        try {
            $request->validate([
                'message' => 'required|string|max:10000',
                'sessionId' => 'required|string'
            ]);

            $message = $request->input('message');
            $sessionId = $request->input('sessionId');
            
            Log::info('STREAM CONTROLLER: Request validated', [
                'message' => $message,
                'sessionId' => $sessionId,
                'message_length' => strlen($message)
            ]);

            // Clear any previous stream data
            $streamKey = "claude_stream_{$sessionId}";
            $completeKey = "{$streamKey}_complete";
            
            Log::info('STREAM CONTROLLER: Clearing cache keys', [
                'stream_key' => $streamKey,
                'complete_key' => $completeKey,
                'previous_stream_exists' => Cache::has($streamKey),
                'previous_complete_exists' => Cache::has($completeKey)
            ]);
            
            Cache::forget($streamKey);
            Cache::forget($completeKey);

            // Process message synchronously
            Log::info('STREAM CONTROLLER: Processing message', [
                'sessionId' => $sessionId
            ]);
            
            // Process the message
            try {
                $this->processMessageAsync($message, $sessionId);
            } catch (\Exception $e) {
                Log::error('STREAM CONTROLLER: Processing failed', [
                    'error' => $e->getMessage(),
                    'sessionId' => $sessionId
                ]);
                
                // Write error to stream
                Cache::put($streamKey, '<div class="text-red-600">Error: ' . $e->getMessage() . '</div>', 120);
                Cache::put($completeKey, true, 120);
            }

            return response()->json([
                'success' => true,
                'sessionId' => $sessionId,
                'message' => 'Processing started'
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('STREAM CONTROLLER: Validation Error', [
                'errors' => $e->errors(),
                'message' => $request->input('message')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
            
        } catch (\Exception $e) {
            Log::error('=== STREAM CONTROLLER: PROCESS ERROR ===', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'message' => $request->input('message'),
                'sessionId' => $request->input('sessionId')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to process message: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process message asynchronously and write chunks to cache
     */
    private function processMessageAsync($message, $sessionId)
    {
        Log::info('=== PROCESS MESSAGE ASYNC START ===', [
            'sessionId' => $sessionId,
            'message_length' => strlen($message),
            'timestamp' => now()->toISOString()
        ]);
        
        $streamKey = "claude_stream_{$sessionId}";
        $completeKey = "{$streamKey}_complete";

        // Use ONLY local Claude CLI directly - no API, no fallbacks
        try {
            if (!$this->claudeCliService || !$this->claudeCliService->isAvailable()) {
                throw new \Exception('Local Claude CLI not available');
            }
            
            // Configure for streaming
            $context = [
                'enable_streaming' => true,
                'stream_to_session' => $sessionId,
                'session_id' => $sessionId,
                'format' => 'html'
            ];
            
            Log::info('[STREAM-PROCESS] Using Direct Claude CLI (the ONLY option)', [
                'session_id' => $sessionId,
                'message_preview' => substr($message, 0, 100)
            ]);
            
            // Send message to Claude CLI (which is YOU)
            $response = $this->claudeCliService->sendMessage($message, $context);
            
            Log::info('[STREAM-PROCESS] DirectClaudeCliService response received', [
                'session_id' => $sessionId,
                'success' => $response['success'] ?? false,
                'has_message' => isset($response['message']),
                'message_length' => isset($response['message']) ? strlen($response['message']) : 0,
                'error' => $response['error'] ?? null
            ]);
            
            if ($response['success']) {
                // Always write the response to cache for streaming
                // Format response as HTML if needed
                $htmlResponse = $this->formatAsHtml($response['message']);
                
                // Check if streaming happened, if not write the full response
                $existingStream = Cache::get($streamKey, '');
                Log::info('[STREAM-PROCESS] Cache check', [
                    'session_id' => $sessionId,
                    'existing_stream_length' => strlen($existingStream),
                    'cache_driver' => config('cache.default')
                ]);
                
                if (empty($existingStream)) {
                    // No streaming occurred, write full response
                    Cache::put($streamKey, $htmlResponse, 120);
                    Log::info('[STREAM-PROCESS] Writing full response to cache', [
                        'session_id' => $sessionId,
                        'response_length' => strlen($htmlResponse),
                        'cache_key' => $streamKey
                    ]);
                } else {
                    Log::info('[STREAM-PROCESS] Streaming already in cache', [
                        'session_id' => $sessionId,
                        'streamed_length' => strlen($existingStream)
                    ]);
                }
                
                // Mark as complete
                Cache::put($completeKey, true, 120);
                
                Log::info('[STREAM-PROCESS] DirectClaudeCliService SUCCESS', [
                    'session_id' => $sessionId,
                    'success' => true,
                    'response_preview' => substr($htmlResponse, 0, 100)
                ]);
                
                return;
            } else {
                Log::warning('[STREAM-PROCESS] DirectClaudeCliService failed', [
                    'session_id' => $sessionId,
                    'error' => $response['error'] ?? 'Unknown error',
                    'message' => $response['message'] ?? null
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('[STREAM-PROCESS] Direct Claude CLI failed', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
        
        // NO FALLBACKS - Only local Claude CLI is used
        
        // If we get here, local Claude CLI is not available
        Log::error('[STREAM-PROCESS] Direct Claude CLI not available', [
            'session_id' => $sessionId,
            'claude_cli_available' => $this->claudeCliService ? $this->claudeCliService->isAvailable() : false,
            'cache_driver' => config('cache.default'),
            'timestamp' => now()->toISOString()
        ]);
        
        $errorMessage = '<div class="bg-red-50 border border-red-200 rounded-lg p-4">';
        $errorMessage .= '<div class="text-red-800 font-semibold mb-2">Local Claude CLI Not Available</div>';
        $errorMessage .= '<div class="text-red-600 text-sm space-y-2">';
        $errorMessage .= '<p>The local Claude CLI is not accessible. Please ensure:</p>';
        $errorMessage .= '<ul class="list-disc list-inside mt-2 space-y-1">';
        $errorMessage .= '<li>tmux is installed: <code class="bg-gray-100 px-1 py-0.5 rounded">brew install tmux</code></li>';
        $errorMessage .= '<li>Claude CLI is available at: <code class="bg-gray-100 px-1 py-0.5 rounded">' . shell_exec('which claude 2>&1') . '</code></li>';
        $errorMessage .= '<li>The Claude CLI session is active</li>';
        $errorMessage .= '</ul>';
        $errorMessage .= '<p class="mt-3 text-xs text-gray-600">Debug: Session ' . $sessionId . ' at ' . now()->toISOString() . '</p>';
        $errorMessage .= '</div>';
        $errorMessage .= '</div>';
        
        Cache::put($streamKey, $errorMessage, 120);
        Cache::put($completeKey, true, 120);
        
        Log::info('[STREAM-PROCESS] Error message written to cache', [
            'session_id' => $sessionId,
            'stream_key' => $streamKey,
            'complete_key' => $completeKey
        ]);
    }
    
    /**
     * Format response as HTML
     */
    private function formatAsHtml($response)
    {
        // Check if already HTML
        if (preg_match('/<[^>]+>/', $response)) {
            return $response;
        }
        
        // Convert to HTML
        return '<div><p>' . nl2br(htmlspecialchars($response)) . '</p></div>';
    }

    /**
     * Stream Claude responses via Server-Sent Events
     */
    public function stream(Request $request, $sessionId)
    {
        Log::info('=== STREAM CONTROLLER: SSE START ===', [
            'method' => 'GET /ai/stream',
            'sessionId' => $sessionId,
            'user_id' => auth()->id(),
            'is_authenticated' => auth()->check(),
            'has_token_param' => $request->has('_token'),
            'ip' => $request->ip(),
            'timestamp' => now()->toISOString()
        ]);
        
        // Check authentication (skip for test routes)
        if (!auth()->check() && !$request->is('test-ai/*')) {
            Log::warning('STREAM SSE: Unauthenticated access attempt', [
                'sessionId' => $sessionId,
                'ip' => $request->ip()
            ]);
            
            // Return 401 for unauthenticated requests
            return response('Unauthorized', 401);
        }
        
        return new StreamedResponse(function() use ($sessionId) {
            // Set headers for SSE
            header('Content-Type: text/event-stream');
            header('Cache-Control: no-cache');
            header('Connection: keep-alive');
            header('X-Accel-Buffering: no'); // Disable Nginx buffering
            
            Log::info('STREAM SSE: Headers sent', [
                'sessionId' => $sessionId
            ]);
            
            $lastPosition = 0;
            $streamKey = "claude_stream_{$sessionId}";
            $completeKey = "{$streamKey}_complete";
            $startTime = time();
            $timeout = 60; // 60 seconds max connection time
            $checkCount = 0;
            
            Log::info('STREAM SSE: Starting stream loop', [
                'stream_key' => $streamKey,
                'complete_key' => $completeKey,
                'timeout' => $timeout
            ]);
            
            while (true) {
                $checkCount++;
                
                // Check timeout
                if (time() - $startTime > $timeout) {
                    Log::warning('STREAM SSE: Timeout reached', [
                        'sessionId' => $sessionId,
                        'elapsed' => time() - $startTime,
                        'checks_made' => $checkCount
                    ]);
                    
                    echo "event: timeout\n";
                    echo "data: Connection timeout\n\n";
                    ob_flush();
                    flush();
                    break;
                }
                
                // Get current stream content
                $content = Cache::get($streamKey, '');
                
                // Check for new content
                if (strlen($content) > $lastPosition) {
                    $newChunk = substr($content, $lastPosition);
                    $lastPosition = strlen($content);
                    
                    Log::info('STREAM SSE: New chunk available', [
                        'sessionId' => $sessionId,
                        'chunk_size' => strlen($newChunk),
                        'total_position' => $lastPosition,
                        'check_count' => $checkCount
                    ]);
                    
                    // Send SSE event
                    echo "event: message\n";
                    echo "data: " . json_encode([
                        'chunk' => $newChunk,
                        'position' => $lastPosition,
                        'timestamp' => microtime(true)
                    ]) . "\n\n";
                    
                    ob_flush();
                    flush();
                } else if ($checkCount % 100 === 0) {
                    // Log every 100 checks (5 seconds)
                    Log::debug('STREAM SSE: Still waiting', [
                        'sessionId' => $sessionId,
                        'checks' => $checkCount,
                        'elapsed' => time() - $startTime,
                        'content_length' => strlen($content)
                    ]);
                }
                
                // Check if streaming is complete
                if (Cache::has($completeKey)) {
                    Log::info('STREAM SSE: Complete flag detected', [
                        'sessionId' => $sessionId,
                        'final_length' => strlen($content),
                        'checks_made' => $checkCount,
                        'elapsed' => time() - $startTime
                    ]);
                    
                    echo "event: complete\n";
                    echo "data: Stream complete\n\n";
                    
                    // Clean up
                    Cache::forget($streamKey);
                    Cache::forget($completeKey);
                    
                    ob_flush();
                    flush();
                    break;
                }
                
                // Small delay to prevent CPU spinning
                usleep(50000); // 50ms
            }
            
            Log::info('=== STREAM CONTROLLER: SSE END ===', [
                'sessionId' => $sessionId,
                'total_checks' => $checkCount,
                'total_time' => time() - $startTime
            ]);
            
        }, 200, [
            'X-Accel-Buffering' => 'no',
            'Cache-Control' => 'no-cache',
            'Content-Type' => 'text/event-stream',
        ]);
    }
    
    /**
     * Mark stream as complete
     */
    public function complete(Request $request, $sessionId)
    {
        $completeKey = "claude_stream_{$sessionId}_complete";
        Cache::put($completeKey, true, 60);
        
        return response()->json(['success' => true]);
    }
    
}