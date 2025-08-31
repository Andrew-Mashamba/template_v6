<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class HybridAiService
{
    private $claudeCliService;
    private $contextService;
    private $queryRequestService;
    private $conversationMemory = [];
    private $maxMemorySize = 20;
    private $maxRetries = 3;
    
    public function __construct()
    {
        $this->claudeCliService = new ClaudeCliService();
        $this->contextService = new ContextEnhancementService();
        $this->queryRequestService = new QueryRequestService();
    }
    
    /**
     * Process message with proper separation of concerns
     * Following patterns from AiAgentService
     */
    public function processMessage(string $message, array $options = []): array
    {
        // Set unlimited execution time for AI processing
        set_time_limit(0);
        
        // LOG POINT 7: HybridAI Service Entry
        Log::channel('daily')->info('🔵 [PROMPT-CHAIN] HybridAI Service Processing', [
            'message' => $message,
            'session_id' => $options['session_id'] ?? 'unknown',
            'step' => 7,
            'location' => 'HybridAiService::processMessage'
        ]);
        
        try {
            // Validate input
            $this->validateInput($message);
            
            // Get or create session ID
            $sessionId = $options['session_id'] ?? $this->getSessionId();
            $options['session_id'] = $sessionId;
            
            // Check if Claude CLI is available
            if (!$this->claudeCliService->isAvailable()) {
                Log::channel('daily')->warning('⚠️ [PROMPT-CHAIN] Claude CLI Unavailable', [
                    'session_id' => $sessionId,
                    'step' => 8,
                    'location' => 'HybridAiService::processMessage::claudeCheck'
                ]);
                return $this->handleClaudeUnavailable();
            }
            
            // LOG POINT 8: Building context
            Log::channel('daily')->info('🟣 [PROMPT-CHAIN] Building Context', [
                'session_id' => $sessionId,
                'message_preview' => substr($message, 0, 100),
                'step' => 8,
                'location' => 'HybridAiService::processMessage::buildContext'
            ]);
            
            // Build context using the context service
            $context = $this->contextService->buildContext($message, $options);
            
            // LOG POINT 9: Context built
            Log::channel('daily')->info('🟪 [PROMPT-CHAIN] Context Built', [
                'session_id' => $sessionId,
                'context_keys' => array_keys($context),
                'enhanced_message_length' => isset($context['enhanced_message']) ? strlen($context['enhanced_message']) : 0,
                'metadata_keys' => isset($context['metadata']) ? array_keys($context['metadata']) : [],
                'step' => 9,
                'location' => 'HybridAiService::processMessage::contextBuilt'
            ]);
            
            // Get the enhanced message with permissions and context
            $enhancedMessage = $context['enhanced_message'];
            
            // Send to Claude CLI with retry logic for permission issues
            $response = $this->sendWithPermissionHandling($enhancedMessage, $message, $options);
            
            // Process successful response
            if ($response['success']) {
                // Add to conversation history
                $this->contextService->addToConversationHistory(
                    $sessionId, 
                    $message, 
                    $response['message']
                );
                
                // Add to local memory
                $this->addToMemory($message, $response['message']);
                
                // Log successful interaction
                $this->logSuccessfulInteraction($message, $response, $context);
            }
            
            return $response;
            
        } catch (Exception $e) {
            return $this->handleException($e, $message);
        }
    }
    
    /**
     * Send message with permission issue handling
     */
    private function sendWithPermissionHandling(string $enhancedMessage, string $originalMessage, array $options): array
    {
        $attempt = 0;
        $lastResponse = null;
        
        while ($attempt < $this->maxRetries) {
            $attempt++;
            
            // Send to Claude CLI
            $response = $this->claudeCliService->sendMessage($enhancedMessage, $options);
            $lastResponse = $response;
            
            // Check if successful
            if (!$response['success']) {
                return $response;
            }
            
            // Check for permission issues in the response
            if ($this->queryRequestService->hasPermissionIssue($response['message'])) {
                Log::channel('daily')->info('🔄 [PROMPT-CHAIN] Permission issue detected, auto-executing queries', [
                    'attempt' => $attempt,
                    'message' => substr($originalMessage, 0, 100),
                    'session_id' => $options['session_id'] ?? 'unknown'
                ]);
                
                // Extract query request from Claude's response
                $queryRequest = $this->queryRequestService->extractQueryRequest($response['message']);
                
                if ($queryRequest) {
                    // Auto-execute the requested queries
                    $queryResults = $this->queryRequestService->executeQueries($queryRequest);
                    
                    Log::channel('daily')->info('✅ [PROMPT-CHAIN] Queries auto-executed', [
                        'success' => $queryResults['success'],
                        'result_count' => count($queryResults['results']),
                        'session_id' => $options['session_id'] ?? 'unknown'
                    ]);
                    
                    if ($queryResults['success']) {
                        // Build new message with query results
                        $enhancedMessage = $this->queryRequestService->buildEnhancedMessageWithResults(
                            $originalMessage,
                            $queryResults
                        );
                        
                        // Add full database permissions context
                        $enhancedMessage = "[DATABASE ACCESS GRANTED - AUTO-EXECUTED]\n" .
                                         "You have FULL database access. Queries were automatically executed.\n\n" .
                                         $enhancedMessage;
                        
                        Log::channel('daily')->info('🔁 [PROMPT-CHAIN] Retrying with query results', [
                            'attempt' => $attempt,
                            'query_count' => count($queryResults['results']),
                            'session_id' => $options['session_id'] ?? 'unknown'
                        ]);
                        
                        // Continue loop to retry with results
                        continue;
                    } else {
                        Log::channel('daily')->error('❌ [PROMPT-CHAIN] Failed to execute queries', [
                            'errors' => $queryResults['errors'],
                            'session_id' => $options['session_id'] ?? 'unknown'
                        ]);
                    }
                }
            }
            
            // No permission issues, return the response
            return $response;
        }
        
        // Max retries reached
        Log::warning('Max retries reached for permission handling', [
            'message' => substr($originalMessage, 0, 100),
            'attempts' => $attempt
        ]);
        
        return $lastResponse ?? [
            'success' => false,
            'message' => 'Failed to process request after multiple attempts',
            'error' => 'MAX_RETRIES'
        ];
    }
    
    /**
     * Validate input message
     */
    private function validateInput(string $message): void
    {
        if (empty(trim($message))) {
            throw new Exception('Message cannot be empty');
        }
        
        if (strlen($message) > 10000) {
            throw new Exception('Message exceeds maximum length of 10000 characters');
        }
    }
    
    /**
     * Get or generate session ID
     */
    private function getSessionId(): string
    {
        return session()->getId() ?? uniqid('session_', true);
    }
    
    /**
     * Handle Claude CLI unavailable
     */
    private function handleClaudeUnavailable(): array
    {
        Log::warning('Claude CLI is not available');
        
        return [
            'success' => false,
            'message' => 'Claude CLI is not installed or not available. Please install Claude CLI first.',
            'error' => 'CLAUDE_UNAVAILABLE'
        ];
    }
    
    /**
     * Add to conversation memory
     */
    private function addToMemory(string $message, string $response): void
    {
        $this->conversationMemory[] = [
            'message' => $message,
            'response' => $response,
            'timestamp' => now()->toIso8601String()
        ];
        
        // Keep memory size limited
        if (count($this->conversationMemory) > $this->maxMemorySize) {
            array_shift($this->conversationMemory);
        }
    }
    
    /**
     * Get conversation memory
     */
    public function getMemory(): array
    {
        return $this->conversationMemory;
    }
    
    /**
     * Clear conversation memory
     */
    public function clearMemory(): void
    {
        $this->conversationMemory = [];
    }
    
    /**
     * Log successful interaction
     */
    private function logSuccessfulInteraction(string $message, array $response, array $context): void
    {
        Log::info('HybridAI: Successful interaction', [
            'message' => substr($message, 0, 100),
            'response_length' => strlen($response['message'] ?? ''),
            'session_id' => $context['session_id'] ?? null,
            'has_context' => !empty($context),
            'source' => $response['metadata']['source'] ?? 'claude_cli'
        ]);
    }
    
    /**
     * Handle exceptions
     */
    private function handleException(Exception $e, string $message): array
    {
        Log::error('HybridAI: Exception occurred', [
            'error' => $e->getMessage(),
            'message' => substr($message, 0, 100),
            'trace' => $e->getTraceAsString()
        ]);
        
        return [
            'success' => false,
            'message' => 'An error occurred while processing your request. Please try again.',
            'error' => 'EXCEPTION',
            'debug' => config('app.debug') ? $e->getMessage() : null
        ];
    }
    
    /**
     * Get conversation history for current session
     */
    public function getConversationHistory(string $sessionId = null, int $limit = 10): array
    {
        $sessionId = $sessionId ?? $this->getSessionId();
        return $this->contextService->getConversationHistory($sessionId, $limit);
    }
    
    /**
     * Clear conversation history
     */
    public function clearConversationHistory(string $sessionId = null): void
    {
        $sessionId = $sessionId ?? $this->getSessionId();
        $this->contextService->clearConversationHistory($sessionId);
    }
    
    /**
     * Test the connection and context
     */
    public function testConnection(): array
    {
        return $this->processMessage(
            "Hello! Can you confirm you're connected to the SACCOS Core System and tell me how many users are registered?",
            ['skip_history' => true]
        );
    }
    
    /**
     * Get service status
     */
    public function getStatus(): array
    {
        return [
            'claude_cli_available' => $this->claudeCliService->isAvailable(),
            'claude_version' => $this->claudeCliService->getVersion(),
            'memory_size' => count($this->conversationMemory),
            'session_id' => $this->getSessionId(),
            'timestamp' => now()->toIso8601String()
        ];
    }
}