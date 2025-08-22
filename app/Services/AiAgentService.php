<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class AiAgentService
{
    private $providers = [
        'claude' => [
            'url' => 'https://api.anthropic.com/v1/messages',
            'model' => 'claude-3-5-sonnet-20241022',
            'api_key' => null,
            'api_version' => '2023-06-01'
        ],
        'groq' => [
            'url' => 'https://api.groq.com/openai/v1/chat/completions',
            'model' => 'llama3-8b-8192',
            'api_key' => null
        ],
        'custom_ollama' => [
            'url' => 'http://204.74.232.34:11434/api/generate',
            'model' => 'llama3:8b-instruct-q4_0',
            'api_key' => null, // No API key needed for self-hosted
            'type' => 'ollama'
        ]
    ];

    private $memory = [];
    private $maxRetries = 3;
    private $timeout = 300; // 5 minutes for complex AI processing with full context
    private $reasoningEnabled = true; // Enable reasoning by default
    private $validTableNames = []; // Store valid table names from table index
    private $errorPatterns = []; // Track error patterns for prompt improvement
    
    // Simplified context caching
    private $contextCacheEnabled = true; // Enable simple context caching
    private $contextCacheTtl = 3600; // Cache TTL in seconds (1 hour)
    
    // Context chunking settings - Optimized for Llama 3 8B (8K context)
    private $maxTokensPerChunk = 7500; // Use most of the 8K context per chunk
    private $maxTokensPerRequest = 8100; // Near full 8K context utilization
    private $tokensPerChar = 0.25; // Rough estimation: 1 token ≈ 4 characters
    
    // RAG-based chunking settings - Optimized for full 8K context
    private $chunkOverlap = 300; // More overlap for better context continuity
    private $maxChunkSize = 6000; // Larger chunks to utilize full context
    private $minChunkSize = 1500; // Higher minimum for more substantial chunks
    private $semanticBoundaries = ['\n\n', '. ', '; ', '! ', '? ']; // Natural break points

    public function __construct()
    {
        $this->loadApiKeys();
        $this->loadErrorPatterns();
    }
    
    /**
     * Load error patterns from cache for prompt improvement
     */
    private function loadErrorPatterns()
    {
        $this->errorPatterns = Cache::get('ai_error_patterns', []);
    }
    
    /**
     * Track SQL generation errors for prompt improvement
     */
    private function trackError($errorType, $context)
    {
        $pattern = [
            'type' => $errorType,
            'context' => $context,
            'timestamp' => now(),
            'count' => 1
        ];
        
        // Find existing pattern
        $found = false;
        foreach ($this->errorPatterns as &$existing) {
            if ($existing['type'] === $errorType && 
                isset($existing['context']['tables']) && 
                $existing['context']['tables'] === $context['tables']) {
                $existing['count']++;
                $existing['timestamp'] = now();
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $this->errorPatterns[] = $pattern;
        }
        
        // Keep only recent patterns (last 50)
        if (count($this->errorPatterns) > 50) {
            $this->errorPatterns = array_slice($this->errorPatterns, -50);
        }
        
        Cache::put('ai_error_patterns', $this->errorPatterns, 60 * 60 * 24 * 7);
    }
    
    /**
     * Smart context management - send context only when needed
     */
    private function shouldSendContext($sessionId)
    {
        // Check if this session already has FULL context
        $contextCacheKey = "ai_full_context_sent_{$sessionId}";
        
        // Always send FULL context on first data question in session
        if (!Cache::has($contextCacheKey)) {
            Log::info('[AI Smart Context - First Data Question]', [
                'session_id' => $sessionId,
                'reason' => 'first_data_question_needs_full_context',
                'action' => 'sending_full_87kb_context'
            ]);
            return true;
        }
        
        // Full context already sent and is fresh
        Log::info('[AI Smart Context - Using Conversation History]', [
            'session_id' => $sessionId,
            'reason' => 'full_context_already_sent',
            'action' => 'using_conversation_memory_only'
        ]);
        return false;
    }
    
    /**
     * Mark that FULL context has been sent to this session
     */
    private function updateContextCache($sessionId)
    {
        $contextCacheKey = "ai_full_context_sent_{$sessionId}";
        
        // Mark FULL context as sent for this session (1 hour cache)
        Cache::put($contextCacheKey, now(), $this->contextCacheTtl);
        
        Log::info('[AI Full Context Marked as Sent]', [
            'session_id' => $sessionId,
            'cache_ttl_hours' => $this->contextCacheTtl / 3600,
            'next_full_context_refresh' => now()->addSeconds($this->contextCacheTtl)->format('H:i:s'),
            'context_size' => '87KB_full_table_descriptions'
        ]);
    }
    
    /**
     * Simplified: no question counting
     */
    private function incrementQuestionCount($sessionId)
    {
        // Simplified: no question counting
            return;
        }
        
    /**
     * Clear context cache and conversation history
     */
    public function clearContextCache($sessionId)
    {
        $contextCacheKey = "ai_full_context_sent_{$sessionId}";
        $conversationKey = "ai_conversation_history_{$sessionId}";
        
        Cache::forget($contextCacheKey);
        Cache::forget($conversationKey);
        
        Log::info('[AI Context and Conversation Cleared]', [
            'session_id' => $sessionId,
            'reason' => 'new_chat_session'
        ]);
    }
    
    /**
     * Get conversation history for context
     */
    private function getConversationHistory($sessionId)
    {
        $conversationKey = "ai_conversation_history_{$sessionId}";
        $history = Cache::get($conversationKey, []);
        
        if (empty($history)) {
            return '';
        }
        
        // Format recent conversation (last 3 exchanges)
        $recentHistory = array_slice($history, -3);
        $formattedHistory = '';
        
        foreach ($recentHistory as $exchange) {
            $formattedHistory .= "User: {$exchange['question']}\n";
            $formattedHistory .= "Assistant: {$exchange['summary']}\n\n";
        }
        
        return $formattedHistory;
    }

    /**
     * Add to conversation history
     */
    private function addToConversationHistory($sessionId, $question, $response)
    {
        $conversationKey = "ai_conversation_history_{$sessionId}";
        $history = Cache::get($conversationKey, []);
        
        // Add new exchange with summary
        $history[] = [
            'question' => $question,
            'summary' => $this->summarizeResponse($response),
            'timestamp' => now()
        ];
        
        // Keep only last 10 exchanges
        if (count($history) > 10) {
            $history = array_slice($history, -10);
        }
        
        Cache::put($conversationKey, $history, $this->contextCacheTtl);
    }

    /**
     * Summarize response for conversation history
     */
    private function summarizeResponse($response)
    {
        if (is_array($response) && isset($response['response'])) {
            $text = $response['response'];
        } else {
            $text = is_string($response) ? $response : json_encode($response);
        }
        
        // Keep first 200 characters as summary
        return strlen($text) > 200 ? substr($text, 0, 200) . '...' : $text;
    }
    
    /**
     * Simplified: no configuration needed for simple caching
     */
    public function configureContextCaching($enabled = true, $refreshInterval = 50, $cacheTtl = 3600)
    {
        // Simplified: no configuration needed
        return;
    }
    
    /**
     * Simplified: no cache statistics
     */
    public function getContextCacheStats($sessionId)
    {
        return [
            'enabled' => false,
            'message' => 'Simplified caching - no statistics available'
        ];
    }
    
    /**
     * Force context refresh for a session (when AI requests it)
     * @param string $sessionId
     * @param string $reason
     */
    public function forceContextRefresh($sessionId, $reason = 'ai_requested')
    {
        $this->clearContextCache($sessionId);
        
        Log::info('[AI Context Force Refresh]', [
            'session_id' => $sessionId,
            'reason' => $reason,
            'triggered_by' => 'context_request_mechanism'
        ]);
        
        return true;
    }

    /**
     * Load API keys from environment variables
     */
    private function loadApiKeys()
    {
        // Load API keys for all providers
        $this->providers['claude']['api_key'] = config('services.claude.api_key', env('CLAUDE_API_KEY'));
        $this->providers['groq']['api_key'] = config('services.groq.api_key', env('GROQ_API_KEY'));
        $this->providers['custom_ollama']['api_key'] = config('services.custom_ollama.api_key', env('CUSTOM_OLLAMA_API_KEY', 'not_required'));
    }

    /**
     * Main method to process AI requests with reasoning capabilities
     * This allows the LLM to think, ask questions, and iterate through data analysis
     */
    public function processRequest($query, $context = [], $options = [])
    {
        // Set unlimited execution time for AI processing
        set_time_limit(0);
        
        try {
            // Validate input
            try {
                $this->validateInput($query);
            } catch (Exception $e) {
                Log::error('[AI Validation Failure]', [
                    'query' => $query,
                    'error' => $e->getMessage(),
                    'context' => $context,
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }

            // CRITICAL: Ensure the AI agent only uses tables from the table index
            $this->enforceTableIndexRestrictions();
            
            // Get session ID from context for context caching
            $sessionId = $context['session_id'] ?? 'default_session';
            
            // Check if context refresh is forced
            if (isset($context['force_context_refresh']) && $context['force_context_refresh']) {
                $this->forceContextRefresh($sessionId, 'forced_refresh');
                $options['should_send_context'] = true;
            } else {
                // Increment question count for this session
                $this->incrementQuestionCount($sessionId);
                $options['should_send_context'] = $this->shouldSendContext($sessionId);
            }
            
            // Add context caching information to options
            $options['session_id'] = $sessionId;

            // All questions now get full database context - no simple question routing
            Log::info('[AI All Questions Route to Database Context]', [
                'question' => $query,
                'route' => 'four_chunk_sequential_approach',
                'reason' => 'simple_question_detection_disabled'
            ]);
            $result = $this->processQuestionWithTableChunks($query, $context, $options);
            
            // Update context cache if context was sent
            if ($options['should_send_context']) {
                $this->updateContextCache($sessionId);
            }
            
            // Add to conversation history for future context
            $this->addToConversationHistory($sessionId, $query, $result);
            
            return $result;

        } catch (Exception $e) {
            $this->logRequest($query, $e->getMessage(), 'error');
            Log::error('[AI AgentService Fatal]', [
                'query' => $query,
                'error' => $e->getMessage(),
                'context' => $context,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Enable or disable reasoning mode
     */
    public function setReasoningMode($enabled)
    {
        $this->reasoningEnabled = (bool) $enabled;
        Log::info('[AI Reasoning Mode Changed]', [
            'enabled' => $this->reasoningEnabled
        ]);
        return $this;
    }

    /**
     * Check if reasoning mode is enabled
     */
    public function isReasoningEnabled()
    {
        return $this->reasoningEnabled;
    }

    /**
     * Process questions using LLM reasoning capabilities
     * Allows the LLM to think, ask questions, and iterate through data analysis
     */
    public function processQuestionWithReasoning($question, $context = [], $options = [])
    {
        $startTime = microtime(true);
        
        try {
            Log::info('[AI Processing Started]', [
                'question' => $question,
                'question_length' => strlen($question),
                'context_keys' => array_keys($context),
                'options' => $options,
                'start_time' => date('H:i:s')
            ]);
            
            // Use reasoning loop approach
            return $this->executeReasoningLoop($question, $this->getTableInfoForReasoning(), $context, $options);
            
        } catch (Exception $e) {
            $totalTime = round((microtime(true) - $startTime) * 1000, 2);
            Log::error('[AI Processing Failed]', [
                    'question' => $question,
                'error' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'total_time_ms' => $totalTime,
                'failed_at' => date('H:i:s')
            ]);
            
            // Final fallback - simple error response
            return [
                'response' => "I'm sorry, I encountered an error while processing your question. Please try rephrasing your question or contact support if the issue persists.",
                'original_query' => $question,
                'sql_queries' => [],
                'sql_results' => [],
                'reasoning_steps' => [],
                'timestamp' => now(),
                'method' => 'error_fallback',
                'relevant_tables' => [],
                'error' => $e->getMessage()
            ];
        }
    }

    /*
     * DISABLED: Simple question detection - all questions now get full database context
     * 
    private function isSimpleQuestion($query)
    {
        $query = strtolower(trim($query));
        
        // Greetings and social interactions
        $simplePatterns = [
            'hello', 'hi', 'hey', 'good morning', 'good afternoon', 'good evening',
            'how are you', 'how do you do', 'what\'s up', 'whats up',
            'thank you', 'thanks', 'bye', 'goodbye', 'see you',
            'what can you do', 'what are you', 'who are you',
            'help me', 'what is this system', 'how does this work',
            'test', 'testing', 'are you working', 'can you hear me'
        ];
        
        foreach ($simplePatterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                return true;
            }
        }
        
        // Check for questions that clearly need database access
        $databasePatterns = [
            'how many', 'count', 'list', 'show me', 'find', 'search',
            'account', 'client', 'member', 'loan', 'transaction', 'balance',
            'liability', 'asset', 'equity', 'revenue', 'expense',
            'deposit', 'withdrawal', 'payment', 'branch', 'user',
            // Financial reporting and analysis terms
            'generate', 'create', 'prepare', 'produce', 'build',
            'balance sheet', 'income statement', 'profit and loss', 'cash flow',
            'financial report', 'financial statement', 'statement', 'report',
            'analysis', 'analytics', 'summary', 'total', 'sum',
            'portfolio', 'outstanding', 'aging', 'maturity',
            // SACCO specific terms
            'sacco', 'cooperative', 'shares', 'dividend', 'interest',
            'arrears', 'provision', 'reserve', 'capital'
        ];
        
        foreach ($databasePatterns as $pattern) {
            if (strpos($query, $pattern) !== false) {
                return false; // Definitely needs database context
            }
        }
        
        return strlen($query) < 50; // Short questions are likely simple
    }
    */

    /**
     * DISABLED: Handle simple questions without database context
     * All questions now get full database context for better accuracy  
     */
    private function handleSimpleQuestion($query, $context, $options)
    {
        $sessionId = $options['session_id'] ?? 'default_session';
        
        // Build simple system prompt for general conversation
        $systemPrompt = "You are ZONA, an AI assistant for a SACCO (Savings and Credit Cooperative Organization) Management System. 
        
You are helpful, friendly, and professional. For simple greetings and general questions, respond naturally without needing specific database information.

If asked about your capabilities, explain that you can help with:
- Financial data analysis and reporting
- Member account information
- Loan and savings management
- Transaction history and patterns
- System navigation and guidance

Keep responses concise and helpful.";

        $messages = [
            ['role' => 'system', 'content' => $systemPrompt],
            ['role' => 'user', 'content' => $query]
        ];

        try {
            $provider = $this->selectProvider($options);
            $response = $this->executeWithFallback($messages, $context, $provider);
            
            Log::info('[AI Simple Question Response]', [
                'question' => $query,
                'provider' => $provider,
                'response_length' => strlen($response['choices'][0]['message']['content'] ?? ''),
                'processing_route' => 'simple_no_context'
            ]);
            
            return [
                'response' => $response['choices'][0]['message']['content'] ?? 'I apologize, but I was unable to generate a response.',
                'original_query' => $query,
                'sql_queries' => [],
                'sql_results' => [],
                'reasoning_steps' => [],
                'timestamp' => now(),
                'method' => 'simple_response',
                'relevant_tables' => [],
                'context_sent' => false
            ];
            
        } catch (Exception $e) {
            Log::error('[AI Simple Question Failed]', [
                'question' => $query,
                'error' => $e->getMessage()
            ]);
            
            return [
                'response' => "Hello! I'm ZONA, your SACCO management assistant. I'm here to help you with financial data, member accounts, loans, and system guidance. How can I assist you today?",
                'original_query' => $query,
                'sql_queries' => [],
                'sql_results' => [],
                'reasoning_steps' => [],
                'timestamp' => now(),
                'method' => 'simple_fallback',
                'relevant_tables' => [],
                'context_sent' => false
            ];
        }
    }

    /**
     * Get table information for reasoning using chunked approach
     */
    private function getTableInfoForReasoning()
    {
        $tableIndex = $this->getCompleteTableIndex();
        
        // Use chunking system to prepare table info
        $chunks = $this->chunkTableIndex($tableIndex);
        
        // Convert chunks to comprehensive table info
        $allTableInfo = [];
        foreach ($chunks as $chunk) {
            foreach ($chunk as $tableName => $tableData) {
                $info = "Table: {$tableName}\n";
                $info .= "Description: {$tableData['description']}\n";
                $info .= "Keywords: " . implode(', ', $tableData['keywords']) . "\n";
                $info .= "Fields: " . implode(', ', $tableData['fields']) . "\n";
                $info .= "Sample Queries: " . implode(', ', $tableData['sample_queries']) . "\n";
                $info .= "Data Patterns: {$tableData['data_patterns']}\n";
                
                $allTableInfo[$tableName] = $info;
            }
        }
        
        return $allTableInfo;
    }

    /**
     * Execute the reasoning loop where LLM thinks, asks questions, and iterates
     */
    private function executeReasoningLoop($question, $tableInfo, $context, $options, $initialHistory = [])
    {
        $history = $initialHistory;
        $sqlQueries = [];
        $sqlResults = [];
        $reasoningSteps = [];
        $consecutiveThinkSteps = 0;
        $maxConsecutiveThinks = 2; // Simplified - force action sooner
        $loopLimit = 7; // Slightly increased to allow completion
        $startTime = microtime(true);
        
        Log::info('[AI Reasoning Loop Started]', [
            'question' => $question,
            'loop_limit' => $loopLimit,
            'tables_available' => array_keys($tableInfo),
            'context_keys' => array_keys($context)
        ]);

        for ($step = 1; $step <= $loopLimit; $step++) {
            $stepStartTime = microtime(true);
            
            try {
                Log::info('[AI Reasoning Step Start]', [
                    'step' => $step,
                    'step_number' => $step,
                    'total_steps_so_far' => count($reasoningSteps),
                    'history_count' => count($history)
                ]);
                
                // Build reasoning prompt
                $promptStartTime = microtime(true);
                $prompt = $this->buildReasoningPrompt($question, $tableInfo, $history, $options);
                $promptTime = round((microtime(true) - $promptStartTime) * 1000, 2);
                
                Log::debug('[AI Reasoning Prompt Built]', [
                    'step' => $step,
                    'prompt_length' => strlen($prompt),
                    'prompt_time_ms' => $promptTime
                ]);
                
                // Get LLM response
                $llmStartTime = microtime(true);
                $provider = $this->selectProvider($options);
                Log::info('[AI Reasoning LLM Call]', [
                    'step' => $step,
                    'provider' => $provider,
                    'llm_call_start' => date('H:i:s')
                ]);
                
                $response = $this->executeWithFallback($prompt, $context, $provider);
                $llmTime = round((microtime(true) - $llmStartTime) * 1000, 2);
                
                $content = trim($response['choices'][0]['message']['content'] ?? '');
                
                Log::debug('[AI Reasoning LLM Response]', [
                    'step' => $step,
                    'provider' => $provider,
                    'llm_time_ms' => $llmTime,
                    'response_length' => strlen($content),
                    'llm_call_end' => date('H:i:s')
                ]);
                
                if (empty($content)) {
                    Log::warning('[AI Reasoning - Empty Response]', [
                        'step' => $step,
                        'provider' => $provider
                    ]);
                    continue;
                }
                
                // Parse the response
                $parseStartTime = microtime(true);
                $parsedStep = $this->parseReasoningStep($content, $step);
                $parseTime = round((microtime(true) - $parseStartTime) * 1000, 2);
                
                $reasoningSteps[] = $parsedStep;
                $history[] = $content;
                
                Log::debug('[AI Reasoning Step Parsed]', [
                    'step' => $step,
                    'action' => $parsedStep['action'],
                    'content_length' => strlen($parsedStep['content']),
                    'parse_time_ms' => $parseTime,
                    'total_steps_now' => count($reasoningSteps)
                ]);
                
                // Handle different action types
                Log::info('[AI Reasoning Action Handler]', [
                    'step' => $step,
                    'action' => $parsedStep['action'],
                    'action_start_time' => date('H:i:s')
                ]);
                
                switch ($parsedStep['action']) {
                    case 'THINK':
                        $consecutiveThinkSteps++;
                        Log::info('[AI Reasoning - THINK Action]', [
                            'step' => $step,
                            'thinking_content' => $parsedStep['content'],
                            'consecutive_thinks' => $consecutiveThinkSteps,
                            'max_consecutive_thinks' => $maxConsecutiveThinks,
                            'action_duration_ms' => round((microtime(true) - $stepStartTime) * 1000, 2)
                        ]);
                        
                        // Force SQL generation if too many consecutive THINK steps
                        if ($consecutiveThinkSteps >= $maxConsecutiveThinks) {
                            Log::warning('[AI Reasoning - Forcing SQL Generation]', [
                                'step' => $step,
                                'consecutive_thinks' => $consecutiveThinkSteps,
                                'reason' => 'Too many consecutive THINK steps'
                            ]);
                            
                            // Add a forced SQL generation prompt
                            $forcedPrompt = $this->buildReasoningPrompt($question, $tableInfo, $history, $options);
                            $forcedPrompt .= "\n\nIMPORTANT: You have been thinking too much. Generate a SQL query NOW to get actual data. ";
                            $forcedPrompt .= "For the question '{$question}', generate a simple SELECT query. ";
                            $forcedPrompt .= "If asking about liability accounts, use: SELECT * FROM accounts WHERE major_category_code = '2000'";
                            $forcedPrompt .= "\n\nRespond with ONLY the SQL query, no explanations.";
                            
                            $forcedResponse = $this->executeWithFallback($forcedPrompt, $context, $provider);
                            $forcedContent = trim($forcedResponse['choices'][0]['message']['content'] ?? '');
                            
                            if (!empty($forcedContent)) {
                                $forcedParsed = $this->parseReasoningStep($forcedContent, $step);
                                if ($forcedParsed['action'] === 'SQL') {
                                    $parsedStep = $forcedParsed;
                                    $consecutiveThinkSteps = 0; // Reset counter
                                    // Continue to SQL case
                                } else {
                                    // Continue to next step in the outer loop
                                    continue 2;
                                }
                            } else {
                                // Continue to next step in the outer loop
                                continue 2;
                            }
                        } else {
                            // Continue to next step in the outer loop
                            continue 2;
                        }
                        
                    case 'SQL':
                        $consecutiveThinkSteps = 0; // Reset consecutive think counter
                        Log::info('[AI Reasoning - SQL Action Start]', [
                            'step' => $step,
                            'sql_query' => $parsedStep['content'],
                            'sql_length' => strlen($parsedStep['content'])
                        ]);
                        
                        // Execute SQL query
                        $sql = $parsedStep['content'];
                        $sqlQueries[] = $sql;
                        
                        // Validate SQL before execution
                        if (empty($sql) || !preg_match('/^SELECT\s+/i', $sql)) {
                            $errorResponse = "DATA: SQL Error - Invalid SQL query: " . $sql;
                            $history[] = $errorResponse;
                            
                            Log::error('[AI Reasoning SQL Invalid]', [
                                'step' => $step,
                                'sql' => $sql,
                                'sql_length' => strlen($sql),
                                'error' => 'Invalid SQL format',
                                'validation_failed' => true
                            ]);
                            break;
                        }
                        
                        Log::info('[AI Reasoning SQL Validation Passed]', [
                            'step' => $step,
                            'sql' => $sql,
                            'sql_clean' => true
                        ]);
                        
                        try {
                            $sqlStartTime = microtime(true);
                            $result = $this->executeSqlQuery($sql);
                            $sqlTime = round((microtime(true) - $sqlStartTime) * 1000, 2);
                            
                            $sqlResults[] = [
                                'query' => $sql,
                                'result' => $result
                            ];
                            
                            // Add data to history for next iteration
                            $dataResponse = "DATA: " . json_encode($result);
                            $history[] = $dataResponse;
                            
                            Log::info('[AI Reasoning SQL Executed Successfully]', [
                                'step' => $step,
                                'sql' => $sql,
                                'sql_execution_time_ms' => $sqlTime,
                                'result_success' => $result['success'] ?? false,
                                'result_count' => $result['count'] ?? 0,
                                'result_data_preview' => isset($result['data']) ? 'Data returned: ' . count($result['data']) . ' rows' : 'No data',
                                'history_updated' => true
                            ]);
                            
                            // If we got a valid result, encourage AI to provide answer on next step
                            if (($result['success'] ?? false) && ($result['count'] ?? 0) > 0) {
                                $encouragement = "GUIDANCE: Your SQL query was successful and returned " . ($result['count'] ?? 0) . " rows of data. ";
                                $encouragement .= "You now have the information needed to answer the user's question. ";
                                $encouragement .= "Please provide your ANSWER in the next step instead of running more queries.";
                                $history[] = $encouragement;
                                
                                Log::info('[AI Reasoning - Encouraging Answer]', [
                                    'step' => $step,
                                    'reason' => 'successful_query_result_obtained'
                                ]);
                            }
                            
                            // If query returned 0 results, suggest alternative approaches
                            if (($result['count'] ?? 0) === 0) {
                                Log::info('[AI Reasoning - Zero Results Detected]', [
                                    'step' => $step,
                                    'sql' => $sql,
                                    'suggestion' => 'Consider trying without WHERE clauses or checking different status values'
                                ]);
                                
                                // Add suggestion to history for next iteration
                                $suggestion = "SUGGESTION: Query returned 0 results. Try: 1) Remove WHERE clauses, 2) Check different status values, 3) Use simpler queries";
                                $history[] = $suggestion;
                            }
                            
                        } catch (Exception $e) {
                            $errorResponse = "DATA: SQL Error - " . $e->getMessage();
                            $history[] = $errorResponse;
                            
                            Log::error('[AI Reasoning SQL Execution Failed]', [
                                'step' => $step,
                                'sql' => $sql,
                                'sql_execution_time_ms' => round((microtime(true) - $sqlStartTime) * 1000, 2),
                                'error_message' => $e->getMessage(),
                                'error_trace' => $e->getTraceAsString(),
                                'history_updated_with_error' => true
                            ]);
                        }
                        break;
                        

                        
                    case 'ANSWER':
                        $consecutiveThinkSteps = 0; // Reset consecutive think counter
                        Log::info('[AI Reasoning - ANSWER Action]', [
                            'step' => $step,
                            'answer_content' => $parsedStep['content'],
                            'answer_length' => strlen($parsedStep['content']),
                            'total_reasoning_steps' => count($reasoningSteps),
                            'total_sql_queries' => count($sqlQueries),
                            'total_sql_results' => count($sqlResults)
                        ]);
                        
                        // Final answer reached
                        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
                        Log::info('[AI Reasoning Complete Successfully]', [
                            'step' => $step,
                            'total_steps' => count($reasoningSteps),
                            'total_time_ms' => $totalTime,
                            'average_time_per_step_ms' => $totalTime / count($reasoningSteps),
                            'final_answer_provided' => true
                        ]);
                        
                        return [
                            'response' => $parsedStep['content'],
                            'sql_queries' => $sqlQueries,
                            'sql_results' => $sqlResults,
                            'reasoning_steps' => $reasoningSteps
                        ];
                        
                    default:
                        Log::warning('[AI Reasoning - Unknown Action]', [
                            'step' => $step,
                            'action' => $parsedStep['action'],
                            'content' => $parsedStep['content'],
                            'content_length' => strlen($parsedStep['content'])
                        ]);
                        break;
                }
                
                $stepTime = round((microtime(true) - $stepStartTime) * 1000, 2);
                Log::info('[AI Reasoning Step Complete]', [
                    'step' => $step,
                    'step_duration_ms' => $stepTime,
                    'action' => $parsedStep['action'],
                    'total_steps_completed' => count($reasoningSteps),
                    'step_end_time' => date('H:i:s')
                ]);
                
            } catch (Exception $e) {
                $stepTime = round((microtime(true) - $stepStartTime) * 1000, 2);
                Log::error('[AI Reasoning Step Failed]', [
                    'step' => $step,
                    'step_duration_ms' => $stepTime,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString(),
                    'step_failed_at' => date('H:i:s'),
                    'total_steps_completed' => count($reasoningSteps)
                ]);
                
                // Add error to history and continue
                $history[] = "ERROR: " . $e->getMessage();
            }
        }
        
        // If we reach here, we hit the loop limit
        $totalTime = round((microtime(true) - $startTime) * 1000, 2);
        Log::warning('[AI Reasoning - Loop Limit Reached]', [
            'question' => $question,
            'total_steps' => count($reasoningSteps),
            'total_time_ms' => $totalTime,
            'average_time_per_step_ms' => $totalTime / count($reasoningSteps),
            'loop_limit_hit' => true,
            'final_step' => $loopLimit
        ]);
        
        // Analyze what went wrong
        $analysisStartTime = microtime(true);
        $analysis = $this->analyzeReasoningFailure($reasoningSteps, $sqlQueries, $sqlResults);
        $analysisTime = round((microtime(true) - $analysisStartTime) * 1000, 2);
        
        Log::info('[AI Reasoning Failure Analysis]', [
            'analysis_time_ms' => $analysisTime,
            'failure_message' => $analysis['message'],
            'failure_issues' => $analysis['issues'],
            'failure_suggestions' => $analysis['suggestions']
        ]);
        
        return [
            'response' => "I was unable to reach a complete answer after {$loopLimit} reasoning steps. " . $analysis['message'],
            'sql_queries' => $sqlQueries,
            'sql_results' => $sqlResults,
            'reasoning_steps' => $reasoningSteps,
            'failure_analysis' => $analysis
        ];
    }

    /**
     * Build the reasoning prompt for the LLM
     */
    private function buildReasoningPrompt(string $question, array $schemas, array $history = [], array $options = []): string
    {
        // Get valid table names from the table index
        $tableIndex = $this->getCompleteTableIndex();
        $validTableNames = array_keys($tableIndex);
        $availableTableNames = array_keys($schemas);
        
        // Check if we should include full context or use cached context
        $shouldSendContext = $options['should_send_context'] ?? true;
        
        $tableInfoText = '';
        if ($shouldSendContext) {
            // Send FULL table context with rich descriptions
            foreach ($schemas as $table => $info) {
                $tableInfoText .= "{$info}\n\n";
            }
            
            Log::info('[AI Context Sent - FULL Table Info]', [
                'session_id' => $options['session_id'] ?? 'unknown',
                'tables_count' => count($schemas),
                'context_length' => strlen($tableInfoText),
                'reason' => 'first_data_question_in_session',
                'context_type' => 'full_87kb_descriptions'
            ]);
        } else {
            // Use conversation history only - add previous conversation context
            $sessionId = $options['session_id'] ?? 'default_session';
            $conversationHistory = $this->getConversationHistory($sessionId);
            
            $tableInfoText = "CONVERSATION MEMORY: You previously received full database context in this session.\n\n";
            $tableInfoText .= "AVAILABLE TABLES: " . implode(', ', $availableTableNames) . "\n\n";
            
            if (!empty($conversationHistory)) {
                $tableInfoText .= "RECENT CONVERSATION CONTEXT:\n";
                $tableInfoText .= $conversationHistory . "\n\n";
            }
            
            Log::info('[AI Context Using - Conversation Memory]', [
                'session_id' => $options['session_id'] ?? 'unknown',
                'tables_count' => count($schemas),
                'context_length' => strlen($tableInfoText),
                'reason' => 'full_context_already_sent',
                'context_type' => 'conversation_history_only'
            ]);
        }

        $historyText = '';
        if (!empty($history)) {
            $historyText = "\nPREVIOUS STEPS:\n";
            foreach ($history as $index => $step) {
                // Handle both string and array history items
                if (is_string($step)) {
                    // CRITICAL FIX: Don't truncate SQL result data - only truncate other content
                    if (strpos($step, 'DATA: ') === 0) {
                        // This is SQL result data - include it fully so AI can see actual member names
                        $historyText .= "Step " . ($index + 1) . ": " . $step . "\n";
                    } else {
                        // This is other content (THINK, explanations) - truncate to prevent prompt bloat
                    $historyText .= "Step " . ($index + 1) . ": " . substr($step, 0, 100) . (strlen($step) > 100 ? '...' : '') . "\n";
                    }
                } elseif (is_array($step) && isset($step['action']) && isset($step['content'])) {
                    $historyText .= "Step " . ($index + 1) . ": {$step['action']} - {$step['content']}\n";
                } else {
                    $historyText .= "Step " . ($index + 1) . ": " . json_encode($step) . "\n";
                }
            }
        }

        return "You are a reasoning assistant that can analyze data step-by-step by querying a PostgreSQL database for a SACCO Management System.

USER QUESTION: \"{$question}\"

🚨 CRITICAL TABLE RESTRICTIONS 🚨:
- You can ONLY use tables from the following list: " . implode(', ', $availableTableNames) . "
- DO NOT reference, suggest, or query any tables not in this list
- ❌ FORBIDDEN: Do NOT invent table names like 'balance_sheet', 'financial_data', 'summary_table'
- ❌ FORBIDDEN: Do NOT assume tables exist - use ONLY the exact names provided
- ✅ REQUIRED: Every FROM clause must use a table name from the list above
- If you need data from a table not listed, you cannot access it
- All valid tables in the system: " . implode(', ', $validTableNames) . "

AVAILABLE TABLE INFORMATION:
{$tableInfoText}

CRITICAL INSTRUCTIONS:
1. Use THINK only for initial analysis (max 1-2 times)
2. Use SQL immediately when you know what data to retrieve
3. Use ANSWER when you have sufficient data
4. If a query returns 0 results, try: 
   - Removing WHERE clauses
   - Checking different status values
   - Using simpler queries first
5. Be direct - don't overthink or repeat yourself
6. Focus on getting data, not endless analysis
7. IMPORTANT: Only use tables from the AVAILABLE TABLE INFORMATION above
8. IMPORTANT: If you cannot answer the question with the available tables, say so clearly
9. IMPORTANT: For 'asset accounts' questions, use accounts table with major_category_code = '1000'
10. IMPORTANT: Prefer SQL actions over THINK actions
11. CONTEXT REQUEST: If you feel you're missing essential table context or schema information needed to answer the question, respond with 'CONTEXT-REQUEST' and we will provide full context
12. 🚨 CRITICAL DATA INTEGRITY: When providing ANSWER, use ONLY the exact data from SQL query results shown in the conversation history. NEVER invent, assume, or fabricate names, numbers, or other data. For financial systems, data accuracy is legally required.
13. 🚨 FORBIDDEN: Do NOT generate fake names like 'John Smith', 'Jane Doe', etc. Use only actual member names from the database query results.

{$historyText}

Based on the question and available schemas, provide your next step. Choose one of:
- THINK: [brief reasoning about what data you need - use sparingly]
- SQL: [your SQL query to retrieve the data - ONLY from available tables]
- ANSWER: [your final answer based on the data]
- CONTEXT-REQUEST: [if you need more table context/schema information]

🚨 CRITICAL FORMAT REQUIREMENTS:
1. You MUST start your response with exactly one of: THINK:, SQL:, ANSWER:, or CONTEXT-REQUEST:
2. For SQL responses, provide EXECUTABLE PostgreSQL queries, not descriptions
3. Example: 'SQL: SELECT account_name, balance FROM accounts WHERE status = 'ACTIVE''
4. DO NOT write descriptions like 'Select all active accounts...' - write actual SQL code
5. FORBIDDEN: Explanatory text without the proper prefix
6. 🚨 SQL VALIDATION: Before writing SQL, verify EVERY table name in FROM/JOIN clauses exists in the available tables list above

Remember: Move quickly from thinking to action. Get data first, then analyze. Only use the tables provided.";
    }

    /**
     * Parse a reasoning step from LLM response
     */
    private function parseReasoningStep($content, $stepNumber)
    {
        $content = trim($content);
        
        // Extract action and content
        if (preg_match('/^THINK:\s*(.+)$/is', $content, $matches)) {
            return [
                'step' => $stepNumber,
                'action' => 'THINK',
                'content' => trim($matches[1])
            ];
        }
        
        if (preg_match('/^SQL:\s*(.+)$/is', $content, $matches)) {
            $sqlContent = trim($matches[1]);
            
            // Extract SQL query from the content (handle cases where LLM adds extra text)
            $sqlQuery = $this->extractSqlFromContent($sqlContent);
            
            return [
                'step' => $stepNumber,
                'action' => 'SQL',
                'content' => $sqlQuery
            ];
        }
        
        if (preg_match('/^ANSWER:\s*(.+)$/is', $content, $matches)) {
            return [
                'step' => $stepNumber,
                'action' => 'ANSWER',
                'content' => trim($matches[1])
            ];
        }
        

        
        // If no clear action found, treat as THINK
        Log::warning('[AI Reasoning - Unclear Action]', [
            'step' => $stepNumber,
            'content' => $content
        ]);
        
        return [
            'step' => $stepNumber,
            'action' => 'THINK',
            'content' => $content
        ];
    }

    /**
     * Extract SQL query from content that may contain additional text
     */
    private function extractSqlFromContent($content)
    {
        Log::info('[AI SQL Extraction Start]', [
            'original_content_length' => strlen($content),
            'original_content_preview' => substr($content, 0, 200) . (strlen($content) > 200 ? '...' : '')
        ]);
        
        // Remove common LLM explanations and extract just the SQL
        $cleanedContent = $content;
        $cleanedContent = preg_replace('/\*\*.*?\*\*/s', '', $cleanedContent); // Remove **bold text**
        $cleanedContent = preg_replace('/`.*?`/s', '', $cleanedContent); // Remove `code blocks`
        
        Log::info('[AI SQL Extraction Cleaned]', [
            'cleaned_content_length' => strlen($cleanedContent),
            'cleaned_content_preview' => substr($cleanedContent, 0, 200) . (strlen($cleanedContent) > 200 ? '...' : ''),
            'removed_markdown' => $cleanedContent !== $content
        ]);
        
        // Look for SQL patterns - improved for AI's current output format
        $sqlPatterns = [
            // Pattern 1: Multiple SQL queries in code blocks (improved for comments)
            '/```(?:sql)?\s*((?:--.*?\n|\/\*.*?\*\/|\s)*SELECT\s+.*?)\s*```/is',
            // Pattern 2: Single SQL in code blocks
            '/```(?:sql)?\s*(SELECT\s+.*?)\s*```/is',
            // Pattern 3: SQL after "SQL:" followed by code block (common AI format)
            '/SQL:\s*```(?:sql)?\s*((?:WITH\s+.*?\s*)?SELECT\s+.*?)\s*```/is',
            // Pattern 4: SQL after "SQL:" or similar markers
            '/(?:SQL|Query):\s*((?:WITH\s+.*?\s*)?SELECT\s+.*?)(?:;|$)/is',
            // Pattern 5: WITH clause followed by SELECT in code blocks
            '/```(?:sql)?\s*(WITH\s+.*?SELECT\s+.*?)\s*```/is',
            // Pattern 6: Direct SELECT statement with semicolon
            '/((?:WITH\s+.*?\s*)?SELECT\s+.*?;)/is',
            // Pattern 7: SELECT statement with proper SQL keywords (stricter)
            '/((?:WITH\s+.*?\s*)?SELECT\s+(?:\*|[\w\s,\(\)]+)\s+FROM\s+\w+.*?)(?:\n\n|$)/is',
            // Pattern 8: Fallback - SELECT with FROM keyword (stricter than before)
            '/((?:WITH\s+.*?\s*)?SELECT\s+(?:\*|[\w\s,\(\)]+)\s+FROM\s+\w+.*?)/is'
        ];
        
        foreach ($sqlPatterns as $index => $pattern) {
            Log::info('[AI SQL Extraction Pattern Test]', [
                'pattern_index' => $index + 1,
                'pattern' => $pattern
            ]);
            
            // CRITICAL FIX: Use original content for markdown patterns, cleaned content for others
            $searchContent = ($index < 3) ? $content : $cleanedContent;
            
            if (preg_match($pattern, $searchContent, $matches)) {
                $sql = trim($matches[1]);
                
                Log::info('[AI SQL Extraction Pattern Match]', [
                    'pattern_index' => $index + 1,
                    'raw_match' => substr($sql, 0, 200) . (strlen($sql) > 200 ? '...' : ''),
                    'match_length' => strlen($sql)
                ]);
                
                // Handle multiple SQL queries in the match
                $extractedQueries = $this->extractMultipleSqlQueries($sql);
                
                if (!empty($extractedQueries)) {
                    Log::info('[AI SQL Extraction Success - Multiple Queries]', [
                        'pattern_used' => $index + 1,
                        'queries_found' => count($extractedQueries),
                        'queries' => $extractedQueries
                    ]);
                    // Return the first valid query for single query processing
                    return $extractedQueries[0];
                } else {
                    Log::warning('[AI SQL Extraction No Valid Queries Found]', [
                        'pattern_used' => $index + 1,
                        'extracted_content' => substr($sql, 0, 200) . (strlen($sql) > 200 ? '...' : ''),
                        'no_valid_select_queries' => true
                    ]);
                }
            } else {
                Log::info('[AI SQL Extraction Pattern No Match]', [
                    'pattern_index' => $index + 1,
                    'pattern_failed' => true
                ]);
            }
        }
        
        // If no SQL found, return the original content
        Log::warning('[AI SQL Extraction Failed]', [
            'content' => $content,
            'cleaned_content' => $cleanedContent,
            'no_patterns_matched' => true,
            'returning_original_content' => true
        ]);
        
        return $content;
    }

    /**
     * Extract multiple SQL queries from content that may contain comments and multiple SELECT statements
     * @param string $content
     * @return array
     */
    private function extractMultipleSqlQueries($content)
    {
        $queries = [];
        
        // Remove SQL comments while preserving structure
        $cleanContent = $content;
        $cleanContent = preg_replace('/--.*$/m', '', $cleanContent); // Remove single-line comments
        $cleanContent = preg_replace('/\/\*.*?\*\//s', '', $cleanContent); // Remove multi-line comments
        
        // Split by common separators and find SELECT statements
        $lines = explode("\n", $cleanContent);
        $currentQuery = '';
        $inQuery = false;
        
        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            
            // Skip empty lines
            if (empty($trimmedLine)) {
                if ($inQuery && !empty($currentQuery)) {
                    // End of current query
                    $query = $this->cleanSqlQuery($currentQuery);
                    if ($this->isValidSelectQuery($query)) {
                        $queries[] = $query;
                    }
                    $currentQuery = '';
                    $inQuery = false;
                }
                continue;
            }
            
            // Check if line starts a new SQL query (SELECT or WITH clause)
            if (preg_match('/^(SELECT|WITH)\s+/i', $trimmedLine)) {
                // Save previous query if exists
                if ($inQuery && !empty($currentQuery)) {
                    $query = $this->cleanSqlQuery($currentQuery);
                    if ($this->isValidSelectQuery($query)) {
                        $queries[] = $query;
                    }
                }
                
                $currentQuery = $trimmedLine;
                $inQuery = true;
            } elseif ($inQuery) {
                // Continue building current query
                $currentQuery .= ' ' . $trimmedLine;
                
                // Check if query ends with semicolon
                if (substr($trimmedLine, -1) === ';') {
                    $query = $this->cleanSqlQuery($currentQuery);
                    if ($this->isValidSelectQuery($query)) {
                        $queries[] = $query;
                    }
                    $currentQuery = '';
                    $inQuery = false;
                }
            }
        }
        
        // Handle any remaining query
        if ($inQuery && !empty($currentQuery)) {
            $query = $this->cleanSqlQuery($currentQuery);
            if ($this->isValidSelectQuery($query)) {
                $queries[] = $query;
            }
        }
        
        // If no queries found using line-by-line approach, try regex approach
        if (empty($queries)) {
            preg_match_all('/(WITH\s+.*?SELECT\s+.*?|SELECT\s+.*?)(?:;|$)/is', $cleanContent, $matches);
            foreach ($matches[0] as $match) {
                $query = $this->cleanSqlQuery($match);
                if ($this->isValidSelectQuery($query)) {
                    $queries[] = $query;
                }
            }
        }
        
        Log::info('[AI Multiple SQL Extraction]', [
            'input_length' => strlen($content),
            'queries_found' => count($queries),
            'queries' => $queries
        ]);
        
        return $queries;
    }

    /**
     * Clean and normalize SQL query
     * @param string $sql
     * @return string
     */
    private function cleanSqlQuery($sql)
    {
        $sql = trim($sql);
        $sql = preg_replace('/\s+/', ' ', $sql); // Normalize whitespace
        $sql = rtrim($sql, ';'); // Remove trailing semicolon
        return trim($sql);
    }

    /**
     * Check if a query is a valid SELECT statement
     * @param string $sql
     * @return bool
     */
    private function isValidSelectQuery($sql)
    {
        return !empty($sql) && preg_match('/^(SELECT|WITH)\s+/i', $sql);
    }

    /**
     * Get reasoning statistics from a reasoning session
     */
    public function getReasoningStats($reasoningSteps)
    {
        $stats = [
            'total_steps' => count($reasoningSteps),
            'think_steps' => 0,
            'sql_steps' => 0,
            'answer_steps' => 0,
            'unknown_steps' => 0,
            'average_thinking_time' => 0,
            'complexity_score' => 0
        ];
        
        foreach ($reasoningSteps as $step) {
            switch ($step['action']) {
                case 'THINK':
                    $stats['think_steps']++;
                    break;
                case 'SQL':
                    $stats['sql_steps']++;
                    break;
                case 'ANSWER':
                    $stats['answer_steps']++;
                    break;
                default:
                    $stats['unknown_steps']++;
                    break;
            }
        }
        
        // Calculate complexity score based on number of SQL queries and thinking steps
        $stats['complexity_score'] = ($stats['sql_steps'] * 2) + $stats['think_steps'];
        
        return $stats;
    }

    /**
     * Analyze reasoning efficiency
     */
    public function analyzeReasoningEfficiency($reasoningSteps, $finalAnswer)
    {
        $analysis = [
            'efficiency_score' => 0,
            'reasoning_quality' => 'unknown',
            'suggestions' => []
        ];
        
        $stats = $this->getReasoningStats($reasoningSteps);
        
        // Calculate efficiency score (0-100)
        if ($stats['total_steps'] > 0) {
            // Higher score for fewer steps to reach answer
            $efficiency = max(0, 100 - ($stats['total_steps'] * 10));
            
            // Bonus for having answer step
            if ($stats['answer_steps'] > 0) {
                $efficiency += 20;
            }
            
            // Penalty for too many thinking steps without progress
            if ($stats['think_steps'] > $stats['sql_steps'] * 2) {
                $efficiency -= 15;
            }
            
            $analysis['efficiency_score'] = min(100, max(0, $efficiency));
        }
        
        // Assess reasoning quality
        if ($analysis['efficiency_score'] >= 80) {
            $analysis['reasoning_quality'] = 'excellent';
        } elseif ($analysis['efficiency_score'] >= 60) {
            $analysis['reasoning_quality'] = 'good';
        } elseif ($analysis['efficiency_score'] >= 40) {
            $analysis['reasoning_quality'] = 'fair';
        } else {
            $analysis['reasoning_quality'] = 'poor';
        }
        
        // Generate suggestions
        if ($stats['think_steps'] > $stats['sql_steps'] * 3) {
            $analysis['suggestions'][] = 'Consider being more direct with SQL queries instead of excessive thinking';
        }
        
        if ($stats['sql_steps'] > 5) {
            $analysis['suggestions'][] = 'Consider combining multiple queries into fewer, more comprehensive ones';
        }
        
        if ($stats['answer_steps'] === 0) {
            $analysis['suggestions'][] = 'The reasoning process did not reach a final answer';
        }
        
        return $analysis;
    }

    /**
     * Get reasoning session summary
     */
    public function getReasoningSummary($reasoningSteps, $sqlQueries, $sqlResults, $finalAnswer)
    {
        $stats = $this->getReasoningStats($reasoningSteps);
        $efficiency = $this->analyzeReasoningEfficiency($reasoningSteps, $finalAnswer);
        
        $summary = [
            'session_overview' => [
                'total_steps' => $stats['total_steps'],
                'reasoning_method' => 'iterative_analysis',
                'final_answer_provided' => !empty($finalAnswer),
                'efficiency_score' => $efficiency['efficiency_score'],
                'reasoning_quality' => $efficiency['reasoning_quality']
            ],
            'step_breakdown' => [
                'thinking_phases' => $stats['think_steps'],
                'data_queries' => $stats['sql_steps'],
                'conclusion_phases' => $stats['answer_steps']
            ],
            'data_analysis' => [
                'queries_executed' => count($sqlQueries),
                'successful_queries' => count(array_filter($sqlResults, fn($r) => $r['result']['success'] ?? false)),
                'total_records_retrieved' => array_sum(array_map(fn($r) => $r['result']['count'] ?? 0, $sqlResults))
            ],
            'performance_metrics' => [
                'complexity_score' => $stats['complexity_score'],
                'query_efficiency' => $stats['sql_steps'] > 0 ? $stats['complexity_score'] / $stats['sql_steps'] : 0
            ],
            'improvement_suggestions' => $efficiency['suggestions']
        ];
        
        return $summary;
    }

    /**
     * Analyze why reasoning failed
     */
    private function analyzeReasoningFailure($reasoningSteps, $sqlQueries, $sqlResults)
    {
        $analysis = [
            'message' => '',
            'issues' => [],
            'suggestions' => []
        ];
        
        if (empty($reasoningSteps)) {
            $analysis['message'] = 'No reasoning steps were completed.';
            $analysis['issues'][] = 'No progress made in reasoning';
            $analysis['suggestions'][] = 'Check if the question requires data analysis';
            return $analysis;
        }
        
        $lastStep = end($reasoningSteps);
        $stats = $this->getReasoningStats($reasoningSteps);
        
        // Check for common failure patterns
        if ($stats['sql_steps'] === 0) {
            $analysis['issues'][] = 'No SQL queries were generated';
            $analysis['suggestions'][] = 'The question may not require database queries';
        }
        
        if ($stats['answer_steps'] === 0) {
            $analysis['issues'][] = 'No final answer was provided';
            $analysis['suggestions'][] = 'The reasoning process was incomplete';
        }
        
        // Check for SQL errors
        $sqlErrors = 0;
        foreach ($sqlResults as $result) {
            if (isset($result['result']['success']) && !$result['result']['success']) {
                $sqlErrors++;
            }
        }
        
        if ($sqlErrors > 0) {
            $analysis['issues'][] = "{$sqlErrors} SQL queries failed to execute";
            $analysis['suggestions'][] = 'Check table and column names in the database schema';
        }
        
        // Check for excessive thinking without progress
        if ($stats['think_steps'] > $stats['sql_steps'] * 3) {
            $analysis['issues'][] = 'Too much thinking without data gathering';
            $analysis['suggestions'][] = 'Consider being more direct with SQL queries';
        }
        
        // Generate message based on last step
        if ($lastStep['action'] === 'THINK') {
            $analysis['message'] = "The reasoning process was still thinking about the problem. Last thought: " . 
                                 substr($lastStep['content'], 0, 100) . "...";
        } elseif ($lastStep['action'] === 'SQL') {
            $analysis['message'] = "The last step attempted to execute a SQL query but the process didn't complete. " .
                                 "Last query: " . substr($lastStep['content'], 0, 80) . "...";
        } else {
            $analysis['message'] = "The reasoning process didn't reach a conclusion. Last step: " . 
                                 substr($lastStep['content'], 0, 100) . "...";
        }
        
        return $analysis;
    }
    
    /**
     * Execute SQL query safely
     */
    private function executeSqlQuery($sql)
    {
        try {
            // Validate SQL query
            $sql = trim($sql);
            if (empty($sql)) {
                throw new Exception('Empty SQL query');
            }
            
            // Only allow SELECT queries and WITH clauses (CTEs)
            if (!preg_match('/^(SELECT|WITH)\s+/i', $sql)) {
                throw new Exception('Only SELECT queries and WITH clauses are allowed');
            }
            
            // CRITICAL: Validate SQL query against table index before execution
            $validTableNames = $this->validTableNames ?? array_keys($this->getCompleteTableIndex());
            if (!$this->validateSqlQueryTables($sql, $validTableNames)) {
                Log::error('[AI SQL Execution Blocked - Invalid Table]', [
                    'sql' => $sql,
                    'reason' => 'table_not_in_index',
                    'valid_tables' => $validTableNames
                ]);
                
                return [
                    'success' => false,
                    'error' => 'Query references tables not in the allowed table index',
                    'data' => []
                ];
            }
            
            // Check for dangerous keywords
            $dangerousKeywords = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'CREATE', 'ALTER', 'TRUNCATE'];
            foreach ($dangerousKeywords as $keyword) {
                if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $sql)) {
                    throw new Exception("Dangerous SQL keyword detected: {$keyword}");
                }
            }
            
            // Add safety measures for reasoning loop
            // Limit result set size to prevent memory issues
            if (!preg_match('/\bLIMIT\s+\d+/i', $sql)) {
                // Add LIMIT 100 if not present
                $sql .= (preg_match('/\bORDER BY\b/i', $sql) ? ' LIMIT 100' : ' LIMIT 100');
            }
            
            // Add timeout protection
            DB::statement('SET statement_timeout = 30000'); // 30 seconds
            
            // Execute query
            $results = DB::select($sql);
            
            // Reset timeout
            DB::statement('SET statement_timeout = 0');
            
            Log::info('[AI SQL Execution Success]', [
                'sql' => $sql,
                'result_count' => count($results)
            ]);
            
            return [
                'success' => true,
                'data' => $results,
                'count' => count($results)
            ];
            
        } catch (Exception $e) {
            // Reset timeout on error
            try {
                DB::statement('SET statement_timeout = 0');
            } catch (Exception $resetError) {
                // Ignore reset errors
            }
            
            Log::error('[AI SQL Execution Failed]', [
                'sql' => $sql,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Validate input query
     */
    private function validateInput($query)
    {
        if (empty($query) || !is_string($query)) {
            throw new Exception('Invalid query provided');
        }

        // Check for SQL injection attempts
        if ($this->containsSqlInjection($query)) {
            throw new Exception('Query contains potentially harmful content');
        }

        // Check for security violations
        if ($this->containsSecurityViolations($query)) {
            throw new Exception('Query violates security policies');
        }
    }

    /**
     * Check for SQL injection attempts
     */
    private function containsSqlInjection($query)
    {
        // SQL INJECTION VALIDATION TEMPORARILY DISABLED
        return false;
        
        $sqlPatterns = [
            '/\b(union|select|insert|update|delete|drop|create|alter)\b/i',
            '/[\'";]/',
            '/--/',
            '/\/\*.*\*\//'
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for security violations
     */
    private function containsSecurityViolations($query)
    {
        // SECURITY VALIDATION TEMPORARILY DISABLED
        return false;
        
        // Whitelist legitimate queries
        $whitelist = [
            'system users',
            'how many users',
            'user count',
            'total users',
            'user management',
            'system information',
            'system status'
        ];
        
        $queryLower = strtolower($query);
        foreach ($whitelist as $whitelisted) {
            if (strpos($queryLower, $whitelisted) !== false) {
                return false; // Skip security checks for whitelisted content
            }
        }
        
        $securityPatterns = [
            '/\b(password|secret|key|token)\b/i',
            '/\b(delete|drop|truncate)\b/i',
            '/\b(system|exec|shell)\b/i'
        ];

        foreach ($securityPatterns as $pattern) {
            if (preg_match($pattern, $query)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Select the best provider based on availability and performance
     */
    private function selectProvider($options = [])
    {
        $preferredProvider = $options['provider'] ?? null;
        
        if ($preferredProvider && isset($this->providers[$preferredProvider])) {
            return $preferredProvider;
        }

        // Check provider health and select best one
        $healthyProviders = $this->getHealthyProviders();
        
        if (empty($healthyProviders)) {
            throw new Exception('No AI providers available');
        }

        // CRITICAL FIX: Check for recent Claude overload errors
        $claudeRecentlyFailed = Cache::get('claude_overload_error', false);
        $groqContextExceeded = Cache::get('groq_context_exceeded', false);
        
        // Smart provider selection based on recent failures
        if (!$claudeRecentlyFailed && isset($healthyProviders['claude'])) {
            // Claude is healthy and no recent overload issues
            Log::info('[AI Provider Selection]', ['selected_provider' => 'claude', 'reason' => 'primary_provider_healthy']);
            return 'claude';
        } elseif (!$groqContextExceeded && isset($healthyProviders['groq'])) {
            // Claude is overloaded, try Groq if no context issues
            Log::info('[AI Provider Selection]', ['selected_provider' => 'groq', 'reason' => 'claude_overloaded_fallback']);
            return 'groq';
        } elseif (isset($healthyProviders['custom_ollama'])) {
            // Both Claude and Groq have issues, use Ollama
            Log::info('[AI Provider Selection]', ['selected_provider' => 'custom_ollama', 'reason' => 'primary_providers_unavailable']);
            return 'custom_ollama';
        } elseif (isset($healthyProviders['groq'])) {
            // Groq with context reduction as last resort
            Log::info('[AI Provider Selection]', ['selected_provider' => 'groq', 'reason' => 'final_fallback_with_context_reduction']);
            return 'groq';
        } elseif (isset($healthyProviders['claude'])) {
            // Claude even if overloaded as final fallback
            Log::info('[AI Provider Selection]', ['selected_provider' => 'claude', 'reason' => 'final_fallback_despite_overload']);
            return 'claude';
        }

        // Return the first healthy provider if all else fails
        $fallbackProvider = array_key_first($healthyProviders);
        Log::warning('[AI Provider Selection]', ['selected_provider' => $fallbackProvider, 'reason' => 'emergency_fallback']);
        return $fallbackProvider;
    }

    /**
     * Get healthy providers
     */
    private function getHealthyProviders()
    {
        $healthyProviders = [];

        foreach ($this->providers as $name => $config) {
            if ($this->isProviderHealthy($name)) {
                $healthyProviders[$name] = $config;
            }
        }

        return $healthyProviders;
    }

    /**
     * Check if provider is healthy
     */
    private function isProviderHealthy($providerName)
    {
        $cacheKey = "ai_provider_health_{$providerName}";
        
        // Check cache first
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Perform health check
        $isHealthy = $this->performHealthCheck($providerName);
        
        // Cache result for 5 minutes
        Cache::put($cacheKey, $isHealthy, 300);

        return $isHealthy;
    }

    /**
     * Perform health check on provider
     */
    private function performHealthCheck($providerName)
    {
        try {
            $config = $this->providers[$providerName];
            
            // Custom Ollama doesn't need an API key (self-hosted)
            if (empty($config['api_key']) && $providerName !== 'custom_ollama') {
                return false;
            }

            // Simple health check - can be enhanced
            // For custom_ollama, we could add a simple ping test to the endpoint
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Execute request with fallback logic
     */
    private function executeWithFallback($query, $context, $primaryProvider)
    {
        $providers = array_keys($this->providers);
        $attemptedProviders = [];
        $lastError = null;
        $queryLength = is_string($query) ? strlen($query) : strlen(json_encode($query));

        foreach ($providers as $provider) {
            try {
                $attemptedProviders[] = $provider;
                
                Log::info('[AI Provider Selection]', [
                    'selected_provider' => $provider,
                    'reason' => $provider === $primaryProvider ? 'primary_provider_reliable' : 'fallback_attempt',
                    'query_length' => $queryLength
                ]);
                
                $currentQuery = $query;
                
                // CRITICAL FIX: For Groq, reduce context if query is too long (>50K chars)
                if ($provider === 'groq' && $queryLength > 50000) {
                    Log::info('[AI Groq Context Reduction]', [
                        'original_length' => $queryLength,
                        'action' => 'reducing_context_for_groq'
                    ]);
                    
                    // Create reduced prompt for Groq
                    $currentQuery = $this->createReducedPromptForGroq($query);
                    $newLength = is_string($currentQuery) ? strlen($currentQuery) : strlen(json_encode($currentQuery));
                    
                    Log::info('[AI Groq Context Reduction Complete]', [
                        'new_length' => $newLength,
                        'reduction_ratio' => round((1 - $newLength / $queryLength) * 100, 1) . '%'
                    ]);
                }
                
                $response = $this->callProvider($provider, $currentQuery, $context);
                
                if ($response && !empty($response['choices'][0]['message']['content'])) {
                    // CRITICAL FIX: Clear error cache on successful response
                    if ($provider === 'claude') {
                        Cache::forget('claude_overload_error');
                        Log::info('[AI Provider Recovery]', [
                            'provider' => 'claude',
                            'action' => 'cleared_overload_error_cache',
                            'reason' => 'successful_response'
                        ]);
                    } elseif ($provider === 'groq') {
                        Cache::forget('groq_context_exceeded');
                        Log::info('[AI Provider Recovery]', [
                            'provider' => 'groq',
                            'action' => 'cleared_context_exceeded_cache',
                            'reason' => 'successful_response'
                        ]);
                    }
                    
                    // Add provider info to response
                    $response['provider'] = $provider;
                    return $response;
                }
            } catch (Exception $e) {
                $lastError = $e;
                $errorMessage = $e->getMessage();
                
                // Check for specific error types
                $isOverloadError = strpos($errorMessage, 'Overloaded') !== false || strpos($errorMessage, '529') !== false;
                $isContextLengthError = strpos($errorMessage, 'context_length_exceeded') !== false || strpos($errorMessage, 'reduce the length') !== false;
                
                // CRITICAL FIX: Track provider-specific errors for smart fallback
                if ($provider === 'claude' && $isOverloadError) {
                    // Cache Claude overload error for 10 minutes
                    Cache::put('claude_overload_error', true, 600);
                    Log::warning('[AI Provider Error Tracking]', [
                        'provider' => $provider,
                        'error_type' => 'overload',
                        'cached_for_minutes' => 10,
                        'action' => 'will_prioritize_groq_for_next_requests'
                    ]);
                }
                
                if ($provider === 'groq' && $isContextLengthError) {
                    // Cache Groq context length error for 5 minutes
                    Cache::put('groq_context_exceeded', true, 300);
                    Log::warning('[AI Provider Error Tracking]', [
                        'provider' => $provider,
                        'error_type' => 'context_length_exceeded',
                        'cached_for_minutes' => 5,
                        'action' => 'will_use_context_reduction_or_avoid_groq'
                    ]);
                }
                
                Log::error('[AI Provider Failure]', [
                    'provider' => $provider,
                    'query_length' => is_string($currentQuery ?? $query) ? strlen($currentQuery ?? $query) : strlen(json_encode($currentQuery ?? $query)),
                    'error' => $errorMessage,
                    'error_type' => $isOverloadError ? 'server_overload' : ($isContextLengthError ? 'context_length_exceeded' : 'unknown'),
                    'trace' => $e->getTraceAsString(),
                ]);
                
                // Continue to next provider
                continue;
            }
        }

        Log::critical('[AI All Providers Failed]', [
            'query_length' => $queryLength,
            'attempted' => $attemptedProviders,
            'last_error' => $lastError ? $lastError->getMessage() : null,
        ]);
        throw new Exception('All AI providers failed. Attempted: ' . implode(', ', $attemptedProviders) . ($lastError ? ('. Last error: ' . $lastError->getMessage()) : ''));
    }

    /**
     * Create reduced prompt for Groq to fit within context limits
     */
    private function createReducedPromptForGroq($originalQuery)
    {
        // If it's already a string, extract key parts
        if (is_string($originalQuery)) {
            // Extract question and basic instructions
            $lines = explode("\n", $originalQuery);
            $question = '';
            $basicInstructions = '';
            
            foreach ($lines as $line) {
                if (strpos($line, 'USER QUESTION:') !== false) {
                    $question = trim(str_replace(['USER QUESTION:', '"'], '', $line));
                    break;
                }
            }
            
            // Create minimal prompt for Groq
            return "You are a database assistant for a SACCO Management System.

USER QUESTION: \"$question\"

You can query these tables: clients (members), accounts (member accounts), loans (loan records), transactions (financial transactions), employees (staff), branches (locations), users (system users).

Available actions:
- THINK: [brief reasoning]
- SQL: [your SQL query]
- ANSWER: [final answer based on data]

Generate a SQL query to answer the question. Use only standard PostgreSQL syntax.

Respond with one action:";
        }
        
        // If it's an array (messages), create simplified version
        if (is_array($originalQuery)) {
            $simplifiedMessages = [];
            
            foreach ($originalQuery as $message) {
                if ($message['role'] === 'system') {
                    // Simplified system message
                    $simplifiedMessages[] = [
                        'role' => 'system',
                        'content' => 'You are a database assistant for a SACCO Management System. Help users query financial data using SQL.'
                    ];
                } elseif ($message['role'] === 'user') {
                    // Keep user message but truncate if needed
                    $content = $message['content'];
                    if (strlen($content) > 2000) {
                        $content = substr($content, 0, 2000) . '...';
                    }
                    $simplifiedMessages[] = [
                        'role' => 'user',
                        'content' => $content
                    ];
                }
            }
            
            return $simplifiedMessages;
        }
        
        return $originalQuery;
    }

    /**
     * Call specific AI provider
     */
    private function callProvider($provider, $query, $context)
    {
        $config = $this->providers[$provider];
        
        // Skip API key check for custom_ollama (self-hosted, no key needed)
        if (empty($config['api_key']) && $provider !== 'custom_ollama') {
            Log::error('[AI Provider Config Error]', ['provider' => $provider, 'reason' => 'No API key configured']);
            throw new Exception("No API key configured for {$provider}");
        }

        // Check if $query is already a message array (new approach) or a string (old approach)
        if (is_array($query) && isset($query[0]['role'])) {
            // $query is already a message array - use it directly
            $messages = $query;
        } else {
            // $query is a string - build messages using the old approach
            $messages = $this->buildMessages($query, $context);
        }
        
        // Handle Custom Ollama's different API format
        if ($provider === 'custom_ollama') {
            return $this->callOllamaProvider($config, $messages);
        }
        
        // Handle Claude's different API format
        if ($provider === 'claude') {
            return $this->callClaudeProvider($config, $messages);
        }
        
        // Standard OpenAI-compatible format for other providers - Optimized for longer responses
        $payload = [
            'model' => $config['model'],
            'messages' => $messages,
            'max_tokens' => 3000, // Increased for more comprehensive responses
            'temperature' => 0.7,
            'stream' => false
        ];

        $response = Http::timeout($this->timeout)
            ->withHeaders([
                'Authorization' => 'Bearer ' . $config['api_key'],
                'Content-Type' => 'application/json'
            ])
            ->post($config['url'], $payload);

        if (!$response->successful()) {
            Log::error('[AI Provider HTTP Error]', [
                'provider' => $provider,
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload
            ]);
            throw new Exception("Provider {$provider} returned error: " . $response->body());
        }

        $json = $response->json();
        // Log::info('[AI Provider Response]', [
        //     'provider' => $provider,
        //     'json' => $json
        // ]);

        return $json;
    }

    /**
     * Call Claude AI provider with Anthropic's specific API format
     */
    private function callClaudeProvider($config, $messages)
    {
        // Convert OpenAI-style messages to Claude format
        $system = '';
        $claudeMessages = [];
        
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system = $message['content'];
            } else {
                $claudeMessages[] = [
                    'role' => $message['role'],
                    'content' => $message['content']
                ];
            }
        }
        
        $payload = [
            'model' => $config['model'],
            'max_tokens' => 3000, // Increased for more comprehensive responses
            'temperature' => 0.7,
            'messages' => $claudeMessages
        ];
        
        // Add system message if present
        if (!empty($system)) {
            $payload['system'] = $system;
        }
        
        $headers = [
            'x-api-key' => $config['api_key'],
            'anthropic-version' => $config['api_version'],
            'Content-Type' => 'application/json'
        ];
        
        $response = Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->post($config['url'], $payload);
            
        if (!$response->successful()) {
            Log::error('[AI Claude Provider HTTP Error]', [
                'provider' => 'claude',
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload
            ]);
            throw new Exception("Claude provider returned error: " . $response->body());
        }
        
        $json = $response->json();
        
        // Convert Claude response format to OpenAI-compatible format
        if (isset($json['content'][0]['text'])) {
            return [
                'choices' => [
                    [
                        'message' => [
                            'content' => $json['content'][0]['text'],
                            'role' => 'assistant'
                        ]
                    ]
                ],
                'usage' => $json['usage'] ?? null,
                'provider' => 'claude'
            ];
        }
        
        throw new Exception("Unexpected Claude API response format");
    }

    /**
     * Call Custom Ollama provider with Ollama's specific API format
     */
    private function callOllamaProvider($config, $messages)
    {
        // Convert OpenAI-style messages to a single prompt for Ollama
        $prompt = '';
        $system = '';
        
        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $system = $message['content'];
            } elseif ($message['role'] === 'user') {
                $prompt .= $message['content'] . "\n";
            } elseif ($message['role'] === 'assistant') {
                $prompt .= "Assistant: " . $message['content'] . "\n";
            }
        }
        
        // Prepend system message if present
        if (!empty($system)) {
            $prompt = $system . "\n\n" . $prompt;
        }
        
        $payload = [
            'model' => $config['model'],
            'prompt' => trim($prompt),
            'stream' => false,
            'options' => [
                'temperature' => 0.7,
                'top_p' => 0.9,
                'num_predict' => 4000, // Ollama uses 'num_predict' instead of 'max_tokens'
                'num_ctx' => 8192,     // Full 8K context window
                'repeat_penalty' => 1.1,
                'top_k' => 40
            ]
        ];
        
        $headers = [
            'Content-Type' => 'application/json'
        ];
        
        $response = Http::timeout($this->timeout)
            ->withHeaders($headers)
            ->post($config['url'], $payload);
            
        if (!$response->successful()) {
            Log::error('[AI Custom Ollama Provider HTTP Error]', [
                'provider' => 'custom_ollama',
                'status' => $response->status(),
                'body' => $response->body(),
                'payload' => $payload
            ]);
            throw new Exception("Custom Ollama provider returned error: " . $response->body());
        }
        
        $json = $response->json();
        
        // Convert Ollama response format to OpenAI-compatible format
        if (isset($json['response'])) {
            return [
                'choices' => [
                    [
                        'message' => [
                            'content' => $json['response'],
                            'role' => 'assistant'
                        ]
                    ]
                ],
                'usage' => [
                    'prompt_tokens' => $json['prompt_eval_count'] ?? 0,
                    'completion_tokens' => $json['eval_count'] ?? 0,
                    'total_tokens' => ($json['prompt_eval_count'] ?? 0) + ($json['eval_count'] ?? 0)
                ],
                'provider' => 'custom_ollama'
            ];
        }
        
        throw new Exception("Unexpected Ollama API response format");
    }

    /**
     * Build messages for AI provider
     */
    private function buildMessages($query, $context)
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $this->getSystemPrompt($context)
            ]
        ];

        // Add memory context
        if (!empty($this->memory)) {
            $memoryContext = $this->formatMemoryContext();
            $messages[] = [
                'role' => 'assistant',
                'content' => "Previous context: {$memoryContext}"
            ];
        }

        // Add user query
        $messages[] = [
            'role' => 'user',
            'content' => $query
        ];

        return $messages;
    }

    /**
     * Get system prompt based on context
     */
    private function getSystemPrompt($context)
    {
        $systemContext = $this->getSystemContext();
        
        $basePrompt = "You are an AI assistant for a SACCO (Savings and Credit Cooperative Organization) Management System. ";
        $basePrompt .= "This is a comprehensive financial services management system for cooperative organizations. ";
        $basePrompt .= "Always provide accurate, helpful, and secure responses based on the current system data. ";
        $basePrompt .= "\n\n";
        
        // Add current system data
        $basePrompt .= "CURRENT SYSTEM DATA:\n";
        foreach ($systemContext['current_data'] as $key => $value) {
            $basePrompt .= "- {$key}: {$value}\n";
        }
        $basePrompt .= "\n";
        
        // Add database schema information
        $basePrompt .= "DATABASE SCHEMA:\n";
        foreach ($systemContext['database_schema'] as $table => $description) {
            $basePrompt .= "- {$table}: {$description}\n";
        }
        $basePrompt .= "\n";
        
        // Add system capabilities
        $basePrompt .= "SYSTEM CAPABILITIES:\n";
        foreach ($systemContext['system_capabilities'] as $capability => $description) {
            $basePrompt .= "- {$capability}: {$description}\n";
        }
        $basePrompt .= "\n";
        
        // Add conversation memory
        if (!empty($this->memory)) {
            $basePrompt .= "CONVERSATION HISTORY:\n";
            $basePrompt .= $this->formatMemoryContext();
            $basePrompt .= "\n\n";
        }
        
        $basePrompt .= "IMPORTANT: Always reference the actual current data when answering questions. ";
        $basePrompt .= "If asked about counts or statistics, use the real numbers from the system. ";
        $basePrompt .= "Maintain context throughout the conversation and remember previous questions. ";
        $basePrompt .= "CONTEXT REQUEST: If you feel you're missing essential database context or schema information needed to answer the question, respond with 'CONTEXT-REQUEST' and we will provide full context. ";

        if (!empty($context['role'])) {
            $basePrompt .= "User role: {$context['role']}. ";
        }

        if (!empty($context['permissions'])) {
            $basePrompt .= "User permissions: " . implode(', ', $context['permissions']) . ". ";
        }

        return $basePrompt;
    }

    /**
     * Format memory context
     */
    private function formatMemoryContext()
    {
        $context = [];
        foreach ($this->memory as $entry) {
            $context[] = "Q: {$entry['query']}";
            if (isset($entry['response'])) {
                $context[] = "A: {$entry['response']}";
            }
        }
        return implode(' | ', array_slice($context, -10)); // Last 10 items (5 Q&A pairs)
    }

    /**
     * Add interaction to memory
     */
    private function addToMemory($query, $context, $response = null)
    {
        $this->memory[] = [
            'query' => $query,
            'context' => $context,
            'response' => $response,
            'timestamp' => now()
        ];

        // Keep only last 10 interactions
        if (count($this->memory) > 10) {
            $this->memory = array_slice($this->memory, -10);
        }
    }

    /**
     * Format response for client
     */
    private function formatResponse($response, $originalQuery, $provider = 'unknown')
    {
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        return [
            'response' => $content,
            'original_query' => $originalQuery,
            'provider' => $response['provider'] ?? $provider,
            'timestamp' => now(),
            'usage' => $response['usage'] ?? null
        ];
    }

    /**
     * Log request for monitoring
     */
    private function logRequest($query, $response, $status)
    {
        $logData = [
            'query' => $query,
            'response' => is_array($response) ? json_encode($response) : $response,
            'status' => $status,
            'timestamp' => now()
        ];

        // Log::info('AI Agent Request', $logData);
    }

    /**
     * Validate SQL query
     */
    private function isValidSqlQuery($sql)
    {
        // Basic SQL validation
        $forbiddenKeywords = ['DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE'];
        
        foreach ($forbiddenKeywords as $keyword) {
            if (stripos($sql, $keyword) !== false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Clear memory
     */
    public function clearMemory()
    {
        $this->memory = [];
    }

    /**
     * Get memory contents
     */
    public function getMemory()
    {
        return $this->memory;
    }

    /**
     * Set provider configuration
     */
    public function setProviderConfig($provider, $config)
    {
        if (isset($this->providers[$provider])) {
            $this->providers[$provider] = array_merge($this->providers[$provider], $config);
        }
    }



    /**
     * Get database schema descriptions
     */
    private function getDatabaseSchema()
    {
        return [
            // Core User Management
            'users' => 'System users and administrators with roles, permissions, and authentication details',
            'user_roles' => 'User role assignments and permissions mapping',
            
            // Organization Structure
            'institutions' => 'Financial institutions and SACCO organizations',
            'branches' => 'Physical branch locations and office details',
            'departments' => 'Organizational departments and functional units',
            'employees' => 'Staff records, employment details, and personnel information',
            'employee_roles' => 'Employee role assignments and job functions',
            'employee_requests' => 'Employee request management and approvals',
            
            // Member Management
            'clients' => 'SACCO members and customers with comprehensive personal and financial information',
            'client_documents' => 'Member document storage and verification',
            'member_categories' => 'Member classification and categorization',
            'groups' => 'Member groups and associations',
            'applicants' => 'Job and loan applicants',
            'interviews' => 'Interview scheduling and results',
            'onboarding' => 'New member onboarding process and status',
            
            // Financial Products
            'accounts' => 'Member savings and loan accounts with balances and status',
            'account_historical_balances' => 'Historical account balance tracking',
            
            // Loan Management
            'loans' => 'Loan applications, disbursements, and repayment tracking',
            'loan_schedules' => 'Loan repayment schedules and installment plans',
            'loan_collaterals' => 'Loan collateral and security documentation',
            'loan_guarantors' => 'Loan guarantor information and commitments',
            'loan_images' => 'Loan-related document images and attachments',
            'loan_sub_products' => 'Loan product types and features',
            'loan_product_charges' => 'Loan charges and fee structures',
            'loan_provision_settings' => 'Loan loss provisioning and risk management',
            'loan_process_progress' => 'Loan application progress tracking',
            'settled_loans' => 'Completed and closed loan records',
            'loans_arrears' => 'Loan arrears and delinquency tracking',
            'loans_summary' => 'Loan portfolio summaries and statistics',
            
                
                // Share Management
                'shares' => 'Member share capital and ownership records',
                'share_transactions' => 'Share purchase, sale, and transfer transactions',
                'share_registers' => 'Share register and ownership documentation',
                'share_ownership' => 'Share ownership details and certificates',
                'share_transfers' => 'Share transfer requests and processing',
                'share_withdrawals' => 'Share withdrawal requests and processing',
                'issued_shares' => 'Issued share capital tracking',
                
                // Transaction Management
                'transactions' => 'Financial transactions and journal entries',
                'transaction_reversals' => 'Transaction reversal and correction records',
                'reconciled_transactions' => 'Reconciled transaction records',
                'bank_transactions' => 'Bank transaction imports and processing',
                'im_bank_transactions' => 'Intermediary bank transaction records',
                'entries' => 'General ledger entries and accounting records',
                'entries_amount' => 'Entry amount details and calculations',
                'general_ledger' => 'General ledger account balances and movements',
                
                // Banking and Payments
                'banks' => 'Bank information and account details',
                'bank_accounts' => 'Bank account management and details',
                'bank_statements_staging_table' => 'Bank statement import staging',
                'reconciliation_staging_table' => 'Bank reconciliation staging data',
                'payment_methods' => 'Payment method definitions and configurations',
                'standing_instructions' => 'Standing order and recurring payment instructions',
                'cheque_books' => 'Cheque book management and tracking',
                
                // Financial Management
                'budget_managements' => 'Budget planning and management',
                'budget_approvers' => 'Budget approval workflow and authorities',
                'main_budget' => 'Main budget categories and allocations',
                'main_budget_pending' => 'Pending budget items and approvals',
                'expenses' => 'Expense tracking and management',
                'expense_approvals' => 'Expense approval workflow',
                'expense_accounts' => 'Expense account categorization',
                'purchases' => 'Purchase orders and procurement tracking',
                'payables' => 'Accounts payable and vendor management',
                'receivables' => 'Accounts receivable and customer management',
                'financial_data' => 'Financial performance data and metrics',
                'financial_position' => 'Financial position statements and reports',
                'financial_ratios' => 'Financial ratio calculations and analysis',
                'loss_reserves' => 'Loss reserve calculations and provisions',
                
                // Investment and Assets
                'investments_list' => 'Investment portfolio and holdings',
                'investment_types' => 'Investment product types and categories',
                'ppes' => 'Property, Plant, and Equipment asset management',
                'inventories' => 'Inventory tracking and management',
                'movable_property_types' => 'Movable property classifications',
                'landed_property_types' => 'Real estate property classifications',
                
                // Insurance and Risk Management
                'insurances' => 'Insurance policy management and tracking',
                'insurancelist' => 'Insurance product catalog and features',
                'product_has_insurance' => 'Product-insurance relationship mapping',
                
                // Charges and Fees
                'charges' => 'Fee and charge definitions',
                'chargeslist' => 'Charge catalog and pricing',
                'product_has_charges' => 'Product-charge relationship mapping',
                'interest_payables' => 'Interest payable tracking and calculations',
                
                // Dividends and Benefits
                'dividends' => 'Dividend declarations and distributions',
                'benefits' => 'Employee benefits and compensation',
                
                // Committee and Governance
                'committees' => 'Management committees and governance structures',
                'committee_memberships' => 'Committee member assignments and roles',
                'committee_members' => 'Committee member details and participation',
                'meetings' => 'Meeting scheduling and management',
                'meeting_attendance' => 'Meeting attendance tracking',
                'meeting_documents' => 'Meeting documentation and minutes',
                'leaderships' => 'Leadership positions and appointments',
                
                // Human Resources
                'jobs' => 'Job postings and recruitment',
                'job_postings' => 'Job advertisement and application tracking',
                'leaves' => 'Employee leave management',
                'leave_management' => 'Leave policy and approval workflow',
                'hires_approvals' => 'Hiring approval workflow',
                
                // System and Configuration
                'menus' => 'System menu structure and navigation',
           
                'roles' => 'System roles and permission groups',
                'permissions' => 'System permissions and access rights',
                'role_menu_actions' => 'Role-based menu access permissions',
          
                'ai_interactions' => 'AI agent interaction history and logs',
                'notification_logs' => 'System notification tracking and delivery',
                'notifications' => 'System notification definitions and templates',
                'audit_logs' => 'System audit trail and security logging',
                'security_audit_logs' => 'Security event logging and monitoring',
                
                // Reports and Analytics
                'reports' => 'Report definitions and configurations',
                'scheduled_reports' => 'Automated report scheduling and delivery',
                'query_responses' => 'Query response tracking',
                'analysis_sessions' => 'Data analysis session tracking',
                'scores' => 'Credit scoring and risk assessment',
                
                // Document Management
                'document_types' => 'Document type classifications',
                'institution_files' => 'Institutional document storage',
                'employeefiles' => 'Employee document management',
                
                // Communication and Callbacks
                'call_back' => 'Callback request management',
                'currencies' => 'Currency definitions and exchange rates',
                'collateral_types' => 'Collateral type classifications',
                'main_collateral_types' => 'Main collateral categories',
                'custom_collaterals' => 'Custom collateral definitions',
                'collaterals' => 'Collateral documentation and tracking',
                'contract_managements' => 'Contract management and tracking',
                'current_loans_stages' => 'Current loan stage tracking',
                'setup_accounts' => 'Account setup and configuration',
                'short_long_term_loans' => 'Loan term classifications',
             
                'project' => 'Project management and tracking',
           
          
                'locked_amounts' => 'Account locked amount tracking',
                'pending_registrations' => 'Pending registration requests',
                'cash_flow_configurations' => 'Cash flow analysis configurations'
        ];
    }


    
    /**
     * Extract SQL queries from AI response
     */
    private function extractSqlQueries($content)
    {
        // Get valid table names from the table index
        $tableIndex = $this->getCompleteTableIndex();
        $validTableNames = array_keys($tableIndex);
        
        // Try to find JSON array in the response
        if (preg_match('/\[.*\]/s', $content, $matches)) {
            try {
                $json = $matches[0];
                $queries = json_decode($json, true);
                if (is_array($queries)) {
                    // Handle array of objects with 'query' key
                    if (isset($queries[0]) && is_array($queries[0]) && isset($queries[0]['query'])) {
                        $filteredQueries = array_filter(array_map(function($item) use ($validTableNames) {
                            if (!is_array($item) || !isset($item['query'])) {
                                return null;
                            }
                            $query = trim($item['query']);
                            return $this->validateSqlQueryTables($query, $validTableNames) ? $query : null;
                        }, $queries));
                        return array_values($filteredQueries);
                    }
                    // Handle array of strings
                    $filteredQueries = array_filter($queries, function($query) use ($validTableNames) {
                        return is_string($query) && !empty(trim($query)) && $this->validateSqlQueryTables($query, $validTableNames);
                    });
                    return array_values($filteredQueries);
                }
            } catch (Exception $e) {
                Log::warning('[AI SQL JSON Parse Failed]', ['content' => $content, 'error' => $e->getMessage()]);
            }
        }
        // Fallback: try to extract individual SQL statements
        $queries = [];
        $lines = explode("\n", $content);
        $currentQuery = '';
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            // Check if line looks like SQL
            if (preg_match('/^(SELECT|WITH|SELECT\s+DISTINCT)/i', $line)) {
                if (!empty($currentQuery)) {
                    $query = trim($currentQuery);
                    if ($this->validateSqlQueryTables($query, $validTableNames)) {
                        $queries[] = $query;
                    }
                }
                $currentQuery = $line;
            } else {
                $currentQuery .= ' ' . $line;
            }
        }
        if (!empty($currentQuery)) {
            $query = trim($currentQuery);
            if ($this->validateSqlQueryTables($query, $validTableNames)) {
                $queries[] = $query;
            }
        }
        return array_filter($queries, function($query) {
            return !empty($query) && preg_match('/^SELECT/i', $query);
        });
    }
    
    /**
     * Validate that a SQL query only references valid tables
     */
    private function validateSqlQueryTables($query, $validTableNames)
    {
        $queryLower = strtolower($query);
        
        // Extract CTE (Common Table Expression) names from WITH clauses
        $cteNames = $this->extractCteNames($query);
        
        // Combine valid database tables with CTE temporary table names
        $allValidNames = array_merge($validTableNames, $cteNames);
        
        // Extract table names from FROM and JOIN clauses
        $tablePatterns = [
            '/\bfrom\s+(\w+)/i',
            '/\bjoin\s+(\w+)/i',
            '/\bupdate\s+(\w+)/i',
            '/\binto\s+(\w+)/i'
        ];
        
        foreach ($tablePatterns as $pattern) {
            if (preg_match_all($pattern, $queryLower, $matches)) {
                foreach ($matches[1] as $tableName) {
                    if (!in_array($tableName, $allValidNames)) {
                        Log::warning('[AI SQL Query Validation - Invalid Table]', [
                            'query' => $query,
                            'invalid_table' => $tableName,
                            'valid_tables' => $validTableNames,
                            'cte_tables' => $cteNames,
                            'filtered_out' => true
                        ]);
                        return false;
                    }
                }
            }
        }
        
        return true;
    }

    /**
     * Extract CTE (Common Table Expression) names from WITH clauses
     */
    private function extractCteNames($query)
    {
        $cteNames = [];
        
        // Pattern to match WITH clause CTE definitions
        // Example: WITH account_balances AS (...), totals AS (...)
        if (preg_match_all('/\bwith\s+(\w+)\s+as\s*\(/i', $query, $matches)) {
            $cteNames = array_merge($cteNames, $matches[1]);
        }
        
        // Pattern to match additional CTEs separated by commas
        // Example: account_balances AS (...), totals AS (...)
        if (preg_match_all('/,\s*(\w+)\s+as\s*\(/i', $query, $matches)) {
            $cteNames = array_merge($cteNames, $matches[1]);
        }
        
        // Convert to lowercase for case-insensitive matching
        $cteNames = array_map('strtolower', $cteNames);
        
        if (!empty($cteNames)) {
            Log::info('[AI SQL CTE Names Extracted]', [
                'query_preview' => substr($query, 0, 200) . '...',
                'cte_names' => $cteNames
            ]);
        }
        
        return $cteNames;
    }
    
    /**
     * Enforce that the AI agent only uses tables from the table index
     * This is a critical security and consistency measure
     */
    private function enforceTableIndexRestrictions()
    {
        $tableIndex = $this->getCompleteTableIndex();
        $validTableNames = array_keys($tableIndex);
        
        Log::info('[AI Table Index Restrictions Enforced]', [
            'valid_tables_count' => count($validTableNames),
            'valid_tables' => $validTableNames,
            'restriction_message' => 'AI agent restricted to table index only'
        ]);
        
        // Store the valid table names in a class property for easy access
        $this->validTableNames = $validTableNames;
        
        return true;
    }
    
    /**
     * Answer question using retrieved data
     */
    public function answerWithData($question, $data, $context = [])
    {
        $systemContext = $this->getSystemContext();
        
        $prompt = "You are an AI assistant for a SACCO Management System. ";
        $prompt .= "Answer the user's question using the provided data.\n\n";
        
        $prompt .= "SYSTEM CONTEXT:\n";
        $prompt .= "- System Type: {$systemContext['system_type']}\n";
        $prompt .= "- Description: {$systemContext['description']}\n\n";
        
        $prompt .= "USER QUESTION: {$question}\n\n";
        
        $prompt .= "RETRIEVED DATA:\n";
        if (is_array($data)) {
            foreach ($data as $index => $result) {
                $prompt .= "Query " . ($index + 1) . " Results:\n";
                if (is_array($result) && isset($result['data'])) {
                    $prompt .= json_encode($result['data'], JSON_PRETTY_PRINT) . "\n";
                } else {
                    $prompt .= json_encode($result, JSON_PRETTY_PRINT) . "\n";
                }
                $prompt .= "\n";
            }
        } else {
            $prompt .= json_encode($data, JSON_PRETTY_PRINT) . "\n\n";
        }
        
        $prompt .= "INSTRUCTIONS:\n";
        $prompt .= "1. Answer the question using ONLY the provided data\n";
        $prompt .= "2. If the data shows 'N/A' or empty results, say so\n";
        $prompt .= "3. Be specific and accurate with numbers\n";
        $prompt .= "4. If asked about counts, provide the exact numbers\n";
        $prompt .= "5. If no relevant data is found, explain what you looked for\n\n";
        
        $prompt .= "ANSWER:";
        
        try {
            $response = $this->executeWithFallback($prompt, $context, 'groq');
            $content = $response['choices'][0]['message']['content'] ?? '';
            
            // Log::info('[AI Answer Generation]', [
            //     'question' => $question,
            //     'data' => $data,
            //     'context' => $context,
            //     'answer' => $content
            // ]);
            
            return $content;
            
        } catch (Exception $e) {
            Log::error('[AI Answer Generation Failed]', [
                'question' => $question,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    /**
     * Process question using SQL-first approach
     */
    public function processQuestionWithSql($question, $context = [], $options = [])
    {
        try {
            // Step 1: Send question + table names and descriptions to LLM
            // Log::info('[AI SQL-First Step 1]', ['question' => $question, 'step' => 'identify_tables']);
            $relevantTables = $this->identifyRelevantTables($question, $context);
            
            // If no relevant tables found, this is not a data question
            if (empty($relevantTables)) {
                // Log::info('[AI SQL-First No Data Question]', [
                //     'question' => $question
                // ]);
                
                // Use traditional approach for non-data questions
                $provider = $this->selectProvider($options);
                $response = $this->executeWithFallback($question, $context, $provider);
                $formattedResponse = $this->formatResponse($response, $question, $provider);
                
                return [
                    'response' => $formattedResponse['response'],
                    'original_query' => $question,
                    'sql_queries' => [],
                    'sql_results' => [],
                    'timestamp' => now(),
                    'method' => 'traditional',
                    'relevant_tables' => []
                ];
            }
            
            // Step 2: Get schemas for identified tables
            // Log::info('[AI SQL-First Step 2]', ['tables' => $relevantTables, 'step' => 'get_schemas']);
            $tableSchemas = $this->getTableSchemas($relevantTables);
            
            // Step 3: Send schemas + question to LLM to get SQL queries
            // Log::info('[AI SQL-First Step 3]', ['step' => 'generate_sql']);
            $sqlQueries = $this->generateSqlQueriesWithSchemas($question, $context, $tableSchemas);
            
            if (empty($sqlQueries)) {
                throw new Exception('No valid SQL queries generated');
            }
            
            // Step 4: Execute SQL queries
            // Log::info('[AI SQL-First Step 4]', ['queries' => $sqlQueries, 'step' => 'execute_sql']);
            $results = [];
            foreach ($sqlQueries as $index => $sql) {
                try {
                    $result = $this->executeSqlQuery($sql);
                    $results[] = [
                        'query' => $sql,
                        'result' => $result
                    ];
                } catch (Exception $e) {
                    Log::warning('[AI SQL Execution Failed]', [
                        'sql' => $sql,
                        'error' => $e->getMessage()
                    ]);
                    
                    // Track error for prompt improvement
                    if (strpos($e->getMessage(), 'column') !== false) {
                        $this->trackError('invalid_column', [
                            'tables' => $relevantTables,
                            'error' => $e->getMessage()
                        ]);
                    } elseif (strpos($e->getMessage(), 'table') !== false) {
                        $this->trackError('invalid_table', [
                            'tables' => $relevantTables,
                            'error' => $e->getMessage()
                        ]);
                    }
                    
                    $results[] = [
                        'query' => $sql,
                        'result' => ['success' => false, 'error' => $e->getMessage()]
                    ];
                }
            }
            
            // Step 5: Send results + question to LLM for final answer
            // Log::info('[AI SQL-First Step 5]', ['results' => $results, 'step' => 'generate_answer']);
            $answer = $this->answerWithData($question, $results, $context);
            
            // Self-learning: Store successful queries
            $successfulQueries = [];
            foreach ($results as $index => $result) {
                if (isset($result['result']['success']) && $result['result']['success'] && 
                    isset($result['result']['data']) && !empty($result['result']['data'])) {
                    $successfulQueries[] = $result['query'];
                }
            }
            
            // Store each successful query for future use
            foreach ($successfulQueries as $sql) {
                $this->storeSuccessfulQuery($question, $sql, $relevantTables);
            }
            
            // Format response
            $formattedResponse = [
                'response' => $answer,
                'original_query' => $question,
                'sql_queries' => $sqlQueries,
                'sql_results' => $results,
                'timestamp' => now(),
                'method' => 'sql_first',
                'relevant_tables' => $relevantTables,
                'table_schemas' => $tableSchemas
            ];
            
            // Add to memory
            try {
                $this->addToMemory($question, $context, $answer);
            } catch (Exception $e) {
                Log::error('[AI Memory Failure]', [
                    'question' => $question,
                    'error' => $e->getMessage()
                ]);
            }
            
            // Log::info('[AI SQL-First Success]', [
            //     'question' => $question,
            //     'answer' => $answer,
            //     'tables_used' => $relevantTables
            // ]);
            
            return $formattedResponse;
            
        } catch (Exception $e) {
            Log::error('[AI SQL-First Failed]', [
                'question' => $question,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Identify relevant tables for a question using RAG approach
     * Best practice: Use semantic similarity and caching for better performance
     */
    public function identifyRelevantTables($question, $context = [])
    {
        // Check cache first for similar questions
        $cacheKey = 'ai_table_identification_' . md5($question);
        $cachedTables = Cache::get($cacheKey);
        if ($cachedTables !== null) {
            Log::info('[AI Table Identification - Cache Hit]', [
                'question' => $question,
                'cached_tables' => $cachedTables
            ]);
            return $cachedTables;
        }
        Log::info('[AI Table Identification Started]', [
            'question' => $question,
            'step' => 'identify_tables'
        ]);

        try {
            // Get the complete table index - this is the ONLY source of valid tables
            $tableIndex = $this->getCompleteTableIndex();
            $validTableNames = array_keys($tableIndex);
            
            // Check if we need chunking for table identification
            $tableInfoText = '';
            foreach ($tableIndex as $table => $info) {
                $tableInfoText .= "Table: {$table}\n";
                $tableInfoText .= "Description: {$info['description']}\n";
                $tableInfoText .= "Keywords: " . implode(', ', $info['keywords']) . "\n";
                $tableInfoText .= "Fields: " . implode(', ', $info['fields']) . "\n";
                $tableInfoText .= "Sample Queries: " . implode(', ', $info['sample_queries']) . "\n";
                $tableInfoText .= "Data Patterns: {$info['data_patterns']}\n";
                $tableInfoText .= "---\n";
            }
            
            $totalTableInfoSize = $this->estimateTokenCount($tableInfoText);
            Log::info('[AI Table Identification Size Check]', [
                'total_table_info_tokens' => $totalTableInfoSize,
                'max_tokens_per_chunk' => $this->maxTokensPerChunk,
                'needs_chunking' => $totalTableInfoSize > $this->maxTokensPerChunk
            ]);
            
            if ($totalTableInfoSize > $this->maxTokensPerChunk) {
                // Use chunked table identification
                return $this->identifyTablesWithChunking($question, $tableIndex, $context, $validTableNames);
            } else {
                // Use normal table identification
                return $this->identifyTablesNormally($question, $tableIndex, $context, $validTableNames);
            }
            
            $prompt .= "\nSTRICT INSTRUCTIONS:\n";
            $prompt .= "1. You MUST ONLY use tables from the VALID TABLES list above\n";
            $prompt .= "2. DO NOT reference, suggest, or use any tables not in the VALID TABLES list\n";
            $prompt .= "3. If a table you think you need is not in the VALID TABLES list, find the closest alternative from the list\n";
            $prompt .= "4. Analyze the question carefully and match it against keywords, sample queries, and data patterns\n";
            $prompt .= "5. Return ONLY table names that are relevant to answer the question\n";
            $prompt .= "6. If the question is NOT about data (e.g., greetings, general questions), return empty array\n";
            $prompt .= "7. Return as JSON array of strings\n";
            $prompt .= "8. Use the keywords, sample queries, and data patterns to find the most relevant tables\n";
            $prompt .= "9. Consider field names when matching query intent to table contents\n";
            $prompt .= "10. IMPORTANT: 'members' refers to 'clients' table\n";
            $prompt .= "11. IMPORTANT: 'savings products' refers to 'accounts' and 'charges' tables\n";
            $prompt .= "12. IMPORTANT: If you cannot find a relevant table in the VALID TABLES list, return empty array\n\n";
            
            $prompt .= "EXAMPLES:\n";
            $prompt .= "Question: 'How many users are in the system?' → [\"users\"]\n";
            $prompt .= "Question: 'How many members do we have?' → [\"clients\"]\n";
            $prompt .= "Question: 'How many savings products do we have?' → [\"accounts\", \"charges\"]\n";
            $prompt .= "Question: 'List all clients and their loans' → [\"clients\", \"loans\"]\n";
            $prompt .= "Question: 'Hello, how are you?' → []\n";
            $prompt .= "Question: 'What is the weather?' → []\n";
            $prompt .= "Question: 'Show me data from invalid_table' → [] (because invalid_table is not in VALID TABLES)\n\n";
            
            $prompt .= "QUESTION: {$question}\n\n";
            $prompt .= "Return relevant table names as JSON array (ONLY from VALID TABLES list):";

            $response = $this->executeWithFallback($prompt, $context, 'groq');
            $content = $response['choices'][0]['message']['content'] ?? '';
            
            $tableNames = $this->extractTableNames($content);
            
            // CRITICAL: Filter out any tables not in the valid table index
            $filteredTableNames = array_filter($tableNames, function($table) use ($validTableNames) {
                $isValid = in_array($table, $validTableNames);
                if (!$isValid) {
                    Log::warning('[AI Table Identification - Invalid Table Filtered]', [
                        'invalid_table' => $table,
                        'valid_tables' => $validTableNames,
                        'reason' => 'table_not_in_index'
                    ]);
                }
                return $isValid;
            });
            
            Log::info('[AI Table Identification Success]', [
                'question' => $question,
                'identified_tables' => $tableNames,
                'filtered_tables' => $filteredTableNames,
                'invalid_tables_filtered' => array_diff($tableNames, $filteredTableNames),
                'validation_applied' => true
            ]);
            
            // Cache the result for 1 hour
            $result = array_values($filteredTableNames);
            Cache::put($cacheKey, $result, 3600);
            
            return $result;
            
        } catch (Exception $e) {
            Log::error('[AI Table Identification Failed]', [
                'question' => $question,
                'error' => $e->getMessage()
            ]);
            
            // Fallback: Use simple keyword matching for common queries
            return $this->fallbackTableIdentification($question);
        }
    }

    /**
     * Fallback table identification using keyword matching
     */
    private function fallbackTableIdentification($question)
    {
        $question = strtolower($question);
        $tableNames = [];
        $tableIndex = $this->getCompleteTableIndex(); // Use complete index for fallback too
        $validTableNames = array_keys($tableIndex);
        
        // Enhanced keyword matching using table index
        foreach ($tableIndex as $table => $info) {
            $matched = false;
            
            // Check keywords
            foreach ($info['keywords'] as $keyword) {
                if (strpos($question, strtolower($keyword)) !== false) {
                    $tableNames[] = $table;
                    $matched = true;
                    break;
                }
            }
            
            // Check sample queries if no keyword match
            if (!$matched) {
                foreach ($info['sample_queries'] as $sampleQuery) {
                    if (strpos($question, strtolower($sampleQuery)) !== false) {
                        $tableNames[] = $table;
                        break;
                    }
                }
            }
        }
        
        // Remove duplicates and ensure only valid tables
        $tableNames = array_unique($tableNames);
        $filteredTableNames = array_filter($tableNames, function($table) use ($validTableNames) {
            return in_array($table, $validTableNames);
        });
        
        Log::info('[Enhanced Fallback Table Identification]', [
            'question' => $question,
            'identified_tables' => $tableNames,
            'filtered_tables' => $filteredTableNames,
            'table_index_used' => true,
            'validation_applied' => true
        ]);
        
        return array_values($filteredTableNames);
    }
    
    /**
     * Extract table names from AI response
     */
    private function extractTableNames($content)
    {
        Log::info('[AI Table Names Extraction]', [
            'raw_content' => $content,
            'content_length' => strlen($content)
        ]);
        
        // Get valid table names from the table index
        $tableIndex = $this->getCompleteTableIndex();
        $validTableNames = array_keys($tableIndex);
        
        // Try to find JSON array in the response
        if (preg_match('/\[.*\]/s', $content, $matches)) {
            try {
                $json = $matches[0];
                $tables = json_decode($json, true);
                if (is_array($tables)) {
                    $filteredTables = array_filter($tables, function($table) use ($validTableNames) {
                        return is_string($table) && !empty(trim($table)) && in_array($table, $validTableNames);
                    });
                    
                    Log::info('[AI Table Names JSON Parse Success]', [
                        'extracted_json' => $json,
                        'parsed_tables' => $tables,
                        'filtered_tables' => $filteredTables,
                        'invalid_tables_filtered' => array_diff($tables, $filteredTables),
                        'validation_applied' => true
                    ]);
                    
                    return array_values($filteredTables);
                }
            } catch (Exception $e) {
                Log::warning('[AI Table Names JSON Parse Failed]', [
                    'content' => $content, 
                    'error' => $e->getMessage(),
                    'extracted_json' => $matches[0] ?? 'none'
                ]);
            }
        }
        
        // Try to extract table names from text if JSON fails - ONLY from valid tables
        $tableNames = [];
        
        foreach ($validTableNames as $table) {
            if (stripos($content, $table) !== false) {
                $tableNames[] = $table;
            }
        }
        
        Log::info('[AI Table Names Text Extraction]', [
            'extracted_tables' => $tableNames,
            'valid_tables_checked' => $validTableNames,
            'validation_applied' => true
        ]);
        
        return $tableNames;
    }
    
    /**
     * Get detailed schema for specific tables
     */
    private function getDetailedSchema($tableNames)
    {
        $detailedSchema = [];
        
        foreach ($tableNames as $tableName) {
            try {
                // Start a fresh transaction
                DB::beginTransaction();
                
                // Get table structure from database
                $columns = DB::select("
                    SELECT column_name, data_type, is_nullable, column_default
                    FROM information_schema.columns 
                    WHERE table_name = ? 
                    ORDER BY ordinal_position
                ", [$tableName]);
                
                // Commit the transaction
                DB::commit();
                
                $detailedSchema[$tableName] = [
                    'description' => $this->getDatabaseSchema()[$tableName] ?? 'No description available',
                    'columns' => $columns
                ];
                
                // Log the schema being sent to AI
                // Log::info('[AI Schema Sent]', [
                //     'table' => $tableName,
                //     'description' => $detailedSchema[$tableName]['description'],
                //     'columns' => array_map(function($col) {
                //         return [
                //             'name' => $col->column_name,
                //             'type' => $col->data_type,
                //             'nullable' => $col->is_nullable,
                //             'default' => $col->column_default
                //         ];
                //     }, $columns)
                // ]);
                
            } catch (Exception $e) {
                // Rollback the transaction
                DB::rollBack();
                
                Log::error('[Schema Fetch Failed]', [
                    'table' => $tableName,
                    'error' => $e->getMessage()
                ]);
                
                // Use basic schema as fallback
                $detailedSchema[$tableName] = [
                    'description' => $this->getDatabaseSchema()[$tableName] ?? 'No description available',
                    'columns' => []
                ];
            }
        }
        
        return $detailedSchema;
    }

    /**
     * Get database schemas for an array of table names
     * Using CREATE TABLE format as per best practices - familiar to LLMs
     * @param array $tableNames
     * @return array
     */
    public function getTableSchemas(array $tableNames)
    {
        $schemas = [];
        foreach ($tableNames as $tableName) {
            try {
                // Get table index for description
                $tableIndex = $this->getCompleteTableIndex();
                $tableDescription = $tableIndex[$tableName]['description'] ?? '';
                
                // Get columns
                $columns = DB::select("
                    SELECT 
                        column_name, 
                        data_type, 
                        character_maximum_length,
                        is_nullable, 
                        column_default
                    FROM information_schema.columns
                    WHERE table_name = ?
                    ORDER BY ordinal_position
                ", [$tableName]);
                
                // Get primary key
                $primaryKey = DB::selectOne("
                    SELECT column_name
                    FROM information_schema.key_column_usage
                    WHERE table_name = ? AND constraint_name LIKE '%_pkey'
                    LIMIT 1
                ", [$tableName]);
                
                // Build CREATE TABLE statement (LLM-friendly format)
                $createStatement = "-- $tableDescription\n";
                $createStatement .= "CREATE TABLE $tableName (\n";
                
                $columnDefs = [];
                foreach ($columns as $col) {
                    $def = "    {$col->column_name} {$col->data_type}";
                    
                    // Add length for varchar
                    if ($col->character_maximum_length) {
                        $def .= "({$col->character_maximum_length})";
                    }
                    
                    // Add NOT NULL
                    if ($col->is_nullable === 'NO') {
                        $def .= " NOT NULL";
                    }
                    
                    // Add DEFAULT
                    if ($col->column_default) {
                        $def .= " DEFAULT {$col->column_default}";
                    }
                    
                    // Add PRIMARY KEY
                    if ($primaryKey && $col->column_name === $primaryKey->column_name) {
                        $def .= " PRIMARY KEY";
                    }
                    
                    $columnDefs[] = $def;
                }
                
                $createStatement .= implode(",\n", $columnDefs);
                $createStatement .= "\n);";
                
                $schemas[$tableName] = $createStatement;
                
            } catch (\Exception $e) {
                Log::warning('[Schema Generation Failed]', [
                    'table' => $tableName,
                    'error' => $e->getMessage()
                ]);
                $schemas[$tableName] = "-- Table schema unavailable\nCREATE TABLE $tableName (\n    -- Schema could not be retrieved\n);";
            }
        }
        return $schemas;
    }

    /**
     * Generate SQL queries from a natural language question with schemas
     */
    private function generateSqlQueriesWithSchemas($question, $context, $tableSchemas)
    {
        $systemContext = $this->getSystemContext();
        
        // Generate table information from index for the relevant tables
        $tableNames = array_keys($tableSchemas);
        $tableInfo = $this->generateTableInfoFromIndex($tableNames);
        
        // Reduced logging to prevent verbosity
        Log::debug('[AI SQL Generation Table Info]', [
            'question' => $question,
            'table_names' => $tableNames,
            'table_count' => count($tableNames)
        ]);
        
        // Get valid table names from the table index
        $tableIndex = $this->getCompleteTableIndex();
        $validTableNames = array_keys($tableIndex);
        $availableTableNames = array_keys($tableInfo);
        
        // Best practice: Clear, simple instructions
        $prompt = "You are a PostgreSQL expert. Generate SQL queries to answer the user's question.\n\n";
        
        // Best practice: Use CREATE TABLE format (familiar to LLMs)
        $prompt .= "DATABASE SCHEMA:\n";
        foreach ($tableSchemas as $tableName => $schema) {
            $prompt .= $schema . "\n\n";
        }
        
        // Add few-shot examples if we have any for this type of query
        $fewShotExamples = $this->getFewShotExamples($question, $tableNames);
        if (!empty($fewShotExamples)) {
            $prompt .= "EXAMPLES:\n";
            foreach ($fewShotExamples as $example) {
                $prompt .= "Question: {$example['question']}\n";
                $prompt .= "SQL: {$example['sql']}\n\n";
            }
        }
        
        $prompt .= "INSTRUCTIONS:\n";
        $prompt .= "1. Generate PostgreSQL queries to answer: {$question}\n";
        $prompt .= "2. Return ONLY a JSON array of SQL strings\n";
        $prompt .= "3. Use ONLY the tables provided above\n";
        $prompt .= "4. If you cannot answer with available tables, return []\n\n";
        
        $prompt .= "OUTPUT:";
        
        try {
            $response = $this->executeWithFallback($prompt, $context, 'groq');
            $content = $response['choices'][0]['message']['content'] ?? '';
            
            // Try to extract JSON array from response
            $queries = $this->extractSqlQueries($content);
            
            Log::info('[AI SQL Generation Result]', [
                'generated_queries' => $queries,
                'raw_response' => $content
            ]);
            
            return $queries;
            
        } catch (Exception $e) {
            Log::error('[AI SQL Generation Failed]', [
                'question' => $question,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get few-shot examples based on question similarity
     * Best practice: Add examples only when needed for specific error patterns
     */
    private function getFewShotExamples($question, $tableNames)
    {
        $examples = [];
        
        // Cache key for stored successful queries
        $cacheKey = 'ai_successful_queries';
        $storedQueries = Cache::get($cacheKey, []);
        
        // Simple keyword matching for now - can be enhanced with embeddings
        $questionLower = strtolower($question);
        
        // Check for complex join patterns
        if (count($tableNames) > 1 && (strpos($questionLower, 'with') !== false || strpos($questionLower, 'and') !== false)) {
            // Only add join example if we've seen successful multi-table queries
            foreach ($storedQueries as $stored) {
                if (isset($stored['table_count']) && $stored['table_count'] > 1) {
                    $examples[] = [
                        'question' => $stored['question'],
                        'sql' => $stored['sql']
                    ];
                    break; // Only need one example
                }
            }
        }
        
        // Check for aggregation patterns
        if (preg_match('/\b(count|sum|average|total|how many)\b/i', $questionLower)) {
            foreach ($storedQueries as $stored) {
                if (isset($stored['has_aggregation']) && $stored['has_aggregation']) {
                    $examples[] = [
                        'question' => $stored['question'],
                        'sql' => $stored['sql']
                    ];
                    break;
                }
            }
        }
        
        // Limit examples to avoid prompt bloat
        return array_slice($examples, 0, 2);
    }

    /**
     * Store successful query for self-learning
     * Best practice: Store successful query patterns for future use
     */
    public function storeSuccessfulQuery($question, $sql, $tableNames)
    {
        $cacheKey = 'ai_successful_queries';
        $storedQueries = Cache::get($cacheKey, []);
        
        // Analyze query characteristics
        $hasAggregation = preg_match('/\b(COUNT|SUM|AVG|MIN|MAX)\s*\(/i', $sql);
        $hasJoin = preg_match('/\bJOIN\b/i', $sql);
        
        // Store with metadata
        $queryData = [
            'question' => $question,
            'sql' => $sql,
            'tables' => $tableNames,
            'table_count' => count($tableNames),
            'has_aggregation' => $hasAggregation,
            'has_join' => $hasJoin,
            'timestamp' => now(),
            'hash' => md5($question . $sql)
        ];
        
        // Avoid duplicates
        $exists = false;
        foreach ($storedQueries as $stored) {
            if ($stored['hash'] === $queryData['hash']) {
                $exists = true;
                break;
            }
        }
        
        if (!$exists) {
            $storedQueries[] = $queryData;
            
            // Keep only recent queries (last 100)
            if (count($storedQueries) > 100) {
                $storedQueries = array_slice($storedQueries, -100);
            }
            
            // Store for 7 days
            Cache::put($cacheKey, $storedQueries, 60 * 60 * 24 * 7);
            
            Log::info('[AI Self-Learning - Query Stored]', [
                'question' => $question,
                'sql' => $sql,
                'total_stored' => count($storedQueries)
            ]);
        }
    }

    /**
     * Get system context with safe database queries
     */
    private function getSystemContext(): array
    {
        $context = [
            'system_type' => 'SACCO Management System',
            'description' => 'This is a comprehensive financial services management system for Savings and Credit Cooperative Organizations (SACCOs).',
            'current_data' => [],
            'database_schema' => $this->getDatabaseSchema(),
            'system_capabilities' => [
                'member_management' => 'Register and manage SACCO members',
                'loan_processing' => 'Process loan applications and disbursements',
                'savings_management' => 'Manage member savings accounts',
                'share_management' => 'Handle share capital and dividends',
                'financial_reporting' => 'Generate financial reports',
                'approval_workflows' => 'Manage approval processes',
                'audit_trails' => 'Track system activities',
                'user_management' => 'Manage system users and permissions',
                'budget_management' => 'Budget planning and control',
                'investment_tracking' => 'Investment portfolio management',
                'insurance_management' => 'Insurance policy tracking',
                'committee_governance' => 'Committee and governance management',
                'hr_management' => 'Human resources and employee management',
                'document_management' => 'Document storage and retrieval',
                'banking_integration' => 'Bank account and transaction integration',
                'risk_management' => 'Risk assessment and mitigation',
                'compliance_tracking' => 'Regulatory compliance monitoring'
            ]
        ];

        // Get current data counts safely
        $tables = ['users', 'clients', 'institutions', 'branches', 'departments', 'loans', 'accounts', 'transactions'];
        
        foreach ($tables as $table) {
            try {
                // Start a fresh transaction for each count
                DB::beginTransaction();
                
                $count = DB::table($table)->count();
                
                // Commit the transaction
                DB::commit();
                
                $context['current_data'][$table] = $count;
                
            } catch (\Exception $e) {
                // Rollback the transaction
                DB::rollBack();
                
                Log::warning('[Data Count Failed]', [
                    'table' => $table,
                    'error' => $e->getMessage()
                ]);
                
                $context['current_data'][$table] = 'N/A';
            }
        }

        return $context;
    }

    /**
     * Generate table information from table index instead of database schema
     * This provides focused, relevant information for the AI agent
     */
    private function generateTableInfoFromIndex($tableNames)
    {
        $tableInfo = [];
        $tableIndex = $this->getCompleteTableIndex();
        
        foreach ($tableNames as $tableName) {
            if (isset($tableIndex[$tableName])) {
                $table = $tableIndex[$tableName];
                
                // Build comprehensive table information
                $info = "Table: {$tableName}\n";
                $info .= "Description: {$table['description']}\n";
                $info .= "Keywords: " . implode(', ', $table['keywords']) . "\n";
                $info .= "Sample Queries: " . implode(', ', $table['sample_queries']) . "\n";
                $info .= "Data Patterns: {$table['data_patterns']}\n";
                
                // Add field information if available
                if (isset($table['fields']) && is_array($table['fields'])) {
                    $info .= "Fields: " . implode(', ', $table['fields']) . "\n";
                }
                
                $tableInfo[$tableName] = $info;
                
                // Reduced logging to prevent verbosity
                Log::debug('[AI Table Index - Table Info Generated]', [
                    'table' => $tableName,
                    'info_length' => strlen($info)
                ]);
                
            } else {
                // Fallback for tables not in index
                Log::warning('[AI Table Index - Table Not Found]', [
                    'table' => $tableName,
                    'available_tables' => array_keys($tableIndex)
                ]);
                
                $tableInfo[$tableName] = "Table: {$tableName}\nDescription: Table not found in index.\n";
            }
        }
        
        return $tableInfo;
    }

    /**
     * Get comprehensive table index with field names, keywords, and sample data
     * This provides rich context for AI table identification
     */
    /**
     * Get actual field names from database schema for a given table
     * @param string $tableName
     * @return array
     */
    private function getTableFields($tableName)
    {
        try {
            $fields = [];
            $columns = DB::select("
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_name = ? 
                ORDER BY ordinal_position
            ", [$tableName]);
            
            foreach ($columns as $column) {
                $fields[] = $column->column_name;
            }
            
            return $fields;
        } catch (Exception $e) {
            Log::warning('[Table Fields Extraction Failed]', [
                'table' => $tableName,
                'error' => $e->getMessage()
            ]);
            
            // Return basic fields as fallback
            return ['id', 'created_at', 'updated_at'];
        }
    }

    /**
     * Get all tables that exist in the database but are not in our curated table index
     * Note: Tables not in the index are intentionally excluded from AI access
     * @return array
     */
    public function getMissingTables()
    {
        try {
            $existingTables = [];
            $tables = DB::select("
                SELECT table_name 
                FROM information_schema.tables 
                WHERE table_schema = 'public' 
                AND table_type = 'BASE TABLE'
                ORDER BY table_name
            ");
            
            foreach ($tables as $table) {
                $existingTables[] = $table->table_name;
            }
            
            $indexedTables = array_keys($this->getTableIndex());
            $missingTables = array_diff($existingTables, $indexedTables);
            
            Log::info('[Missing Tables Check]', [
                'total_database_tables' => count($existingTables),
                'curated_tables' => count($indexedTables),
                'excluded_tables' => count($missingTables),
                'excluded_table_list' => $missingTables
            ]);
            
            return array_values($missingTables);
        } catch (Exception $e) {
            Log::error('[Missing Tables Check Failed]', [
                'error' => $e->getMessage()
            ]);
            
            return [];
        }
    }

    /**
     * Get the curated table index - only tables meant for AI access
     * @return array
     */
    public function getCompleteTableIndex()
    {
        // Return only the curated tables that are meant for AI access
        return $this->getTableIndex();
    }

    /**
     * Get comprehensive table index with field names, keywords, and sample data
     * This provides rich context for AI table identification
     * @return array
     */
    public function getTableIndex()
    {
        // Comprehensive table index auto-integrated from test/generated_table_index_advanced.txt
        return [



            'Expenses' => [
                'description' => 'Expense tracking and management with expense categories, amounts, and approval workflows. Core entity for expense management and cost tracking.',
                'keywords' => ['expense', 'cost', 'expenditure', 'spending', 'expense tracking', 'expense amount', 'expense category'],
                'fields' => $this->getTableFields('Expenses'),
                'sample_queries' => ['expenses', 'expense count', 'spending', 'costs', 'expense amounts', 'expense categories'],
                'data_patterns' => 'Contains expense records with descriptions, amounts, categories, dates, status, approval information, and relationship to employees and departments'
            ],



            'Group_loans' => [
                'description' => 'Tracks loans issued to groups, including group number, loan amount, member participation, and status. Used for managing group lending and collective loan products.',
                'keywords' => ['group loan', 'group lending', 'group number', 'collective loan', 'loan status'],
                'fields' => $this->getTableFields('Group_loans'),
                'sample_queries' => ['group loans', 'loans for group X', 'group loan status', 'group loan members'],
                'data_patterns' => 'Each record links a loan to a group and member, with amount and status. Used for group lending and collective loan management.'
            ],

            'account_historical_balances' => [
                'description' => 'Stores year-end and snapshot balances for accounts, including financial year, account details, and balance breakdowns. Used for financial reporting and audits.',
                'keywords' => ['historical balance', 'account balance', 'year-end balance', 'financial snapshot', 'balance history'],
                'fields' => $this->getTableFields('account_historical_balances'),
                'sample_queries' => ['historical balances', 'year-end balances', 'account balance history', 'balance for year X'],
                'data_patterns' => 'Each record captures a balance snapshot for an account, with year, account details, and balance breakdowns. Used for reporting and audits.'
            ],

            'ai_interactions' => [
                'description' => 'Logs all interactions with the AI agent, including queries, responses, context, and metadata. Used for auditing, analytics, and improving AI performance.',
                'keywords' => ['AI interaction', 'AI log', 'AI query', 'AI response', 'interaction history'],
                'fields' => $this->getTableFields('ai_interactions'),
                'sample_queries' => ['AI interactions', 'AI queries', 'AI responses', 'interaction history'],
                'data_patterns' => 'Each record logs a query/response pair with context and metadata. Used for auditing and analytics.'
            ],

            'applicants' => [
                'description' => 'Tracks individuals or entities applying for membership, loans, or services, including application status and supporting documents. Used for onboarding and workflow management.',
                'keywords' => ['applicant', 'application', 'membership application', 'loan application', 'application status'],
                'fields' => $this->getTableFields('applicants'),
                'sample_queries' => ['applicants', 'pending applications', 'application status', 'applicant documents'],
                'data_patterns' => 'Each record tracks an applicant, application type, status, and supporting documents. Used for onboarding and workflow.'
            ],

            'approval_actions' => [
                'description' => 'Logs actions taken during approval workflows, including approver, status, comments, and timestamps. Used for workflow tracking and compliance.',
                'keywords' => ['approval action', 'workflow action', 'approver', 'approval status', 'approval comment'],
                'fields' => $this->getTableFields('approval_actions'),
                'sample_queries' => ['approval actions', 'actions for approval X', 'approver comments', 'approval workflow history'],
                'data_patterns' => 'Each record logs an action in an approval workflow, with approver, status, comments, and timestamps. Used for workflow tracking and compliance.'
            ],

            'approval_comments' => [
                'description' => 'Stores comments and notes made during approval processes, including approver, comment text, and timestamps. Used for audit trails and workflow transparency.',
                'keywords' => ['approval comment', 'workflow comment', 'approver note', 'approval note'],
                'fields' => $this->getTableFields('approval_comments'),
                'sample_queries' => ['approval comments', 'comments for approval X', 'workflow notes'],
                'data_patterns' => 'Each record stores a comment made during an approval process, with approver and timestamp. Used for audit trails and transparency.'
            ],

            'approval_matrix_configs' => [
                'description' => 'Defines approval matrix configurations, including required approval levels, roles, and thresholds. Used for workflow automation and compliance.',
                'keywords' => ['approval matrix', 'approval configuration', 'workflow matrix', 'approval level', 'approval threshold'],
                'fields' => $this->getTableFields('approval_matrix_configs'),
                'sample_queries' => ['approval matrix configs', 'approval levels', 'workflow matrix', 'approval thresholds'],
                'data_patterns' => 'Each record defines an approval matrix, with levels, roles, and thresholds. Used for workflow automation and compliance.'
            ],

            'approvals' => [
                'description' => 'Tracks approval requests and their status, including requester, approver, approval type, and timestamps. Used for workflow management and compliance.',
                'keywords' => ['approval', 'approval request', 'approval status', 'approver', 'workflow approval'],
                'fields' => $this->getTableFields('approvals'),
                'sample_queries' => ['approvals', 'pending approvals', 'approval status', 'approver history'],
                'data_patterns' => 'Each record tracks an approval request, with requester, approver, type, and status. Used for workflow management and compliance.'
            ],

     

            'asset_url' => [
                'description' => 'Stores URLs or paths to digital assets, such as images, documents, or files, used in the system. Used for asset management and retrieval.',
                'keywords' => ['asset URL', 'file URL', 'document URL', 'image URL', 'digital asset'],
                'fields' => $this->getTableFields('asset_url'),
                'sample_queries' => ['asset URLs', 'file URLs', 'document URLs', 'image URLs'],
                'data_patterns' => 'Each record stores a URL or path to a digital asset. Used for asset management and retrieval.'
            ],

            'assets_list' => [
                'description' => 'Catalogs physical or digital assets owned or managed by the organization, including asset details, value, and status. Used for asset tracking and management.',
                'keywords' => ['asset', 'asset list', 'asset catalog', 'asset value', 'asset status'],
                'fields' => $this->getTableFields('assets_list'),
                'sample_queries' => ['assets', 'asset list', 'asset value', 'asset status'],
                'data_patterns' => 'Each record catalogs an asset, with details, value, and status. Used for asset tracking and management.'
            ],

            'audit_logs' => [
                'description' => 'Logs system events and user actions for auditing and compliance, including event type, user, timestamp, and details. Used for security and compliance.',
                'keywords' => ['audit log', 'system log', 'user action', 'event log', 'compliance log'],
                'fields' => $this->getTableFields('audit_logs'),
                'sample_queries' => ['audit logs', 'system events', 'user actions', 'event history'],
                'data_patterns' => 'Each record logs a system event or user action, with type, user, timestamp, and details. Used for auditing and compliance.'
            ],



            'bank_statements_staging_table' => [
                'description' => 'Temporary storage for uploaded bank statements before processing and reconciliation. Used for data import and validation.',
                'keywords' => ['bank statement', 'staging table', 'statement upload', 'reconciliation', 'data import'],
                'fields' => $this->getTableFields('bank_statements_staging_table'),
                'sample_queries' => ['bank statements staging', 'uploaded statements', 'pending reconciliation'],
                'data_patterns' => 'Each record stores a bank statement upload before processing. Used for data import and reconciliation.'
            ],


            'banks' => [
                'description' => 'Lists banks available for transactions, including bank names, codes, and integration details. Used for linking accounts and processing payments.',
                'keywords' => ['bank', 'bank name', 'bank code', 'financial institution', 'external bank'],
                'fields' => $this->getTableFields('banks'),
                'sample_queries' => ['banks', 'bank list', 'bank codes', 'available banks'],
                'data_patterns' => 'Each record lists a bank, with name, code, and integration details. Used for linking accounts and processing payments.'
            ],

            'benefits' => [
                'description' => 'Tracks member or employee benefits, including benefit type, value, eligibility, and status. Used for HR and member services.',
                'keywords' => ['benefit', 'member benefit', 'employee benefit', 'benefit type', 'benefit status'],
                'fields' => $this->getTableFields('benefits'),
                'sample_queries' => ['benefits', 'member benefits', 'employee benefits', 'benefit status'],
                'data_patterns' => 'Each record tracks a benefit, with type, value, eligibility, and status. Used for HR and member services.'
            ],

  

            'bills' => [
                'description' => 'Billing records and invoice management with amounts, due dates, and payment status. Core entity for billing operations and payment tracking.',
                'keywords' => ['bill', 'invoice', 'billing', 'payment', 'due date', 'bill amount', 'payment status'],
                'fields' => $this->getTableFields('bills'),
                'sample_queries' => ['bills', 'invoices', 'billing', 'bill count', 'payment status', 'due dates', 'bill amounts'],
                'data_patterns' => 'Contains billing records with member references, amounts, due dates, payment status, service types, and relationship to payments and services'
            ],

            'branches' => [
                'description' => 'Physical branch locations and office details with contact information and operational status. Core entity for organizational structure and branch management.',
                'keywords' => ['branch', 'office', 'location', 'physical address', 'branch office', 'branch location', 'branch details'],
                'fields' => $this->getTableFields('branches'),
                'sample_queries' => ['branches', 'branch count', 'office locations', 'branch details', 'branch information', 'branch status'],
                'data_patterns' => 'Contains branch information with branch codes, names, addresses, contact details, operational status, and relationship to institutions and departments'
            ],



            'cash_in_transit_providers' => [
                'description' => 'Lists providers for cash-in-transit services, including provider details, contact information, and service status. Used for cash logistics and security.',
                'keywords' => ['cash in transit', 'CIT provider', 'cash logistics', 'provider', 'security'],
                'fields' => $this->getTableFields('cash_in_transit_providers'),
                'sample_queries' => ['cash in transit providers', 'CIT providers', 'provider details', 'service status'],
                'data_patterns' => 'Each record lists a cash-in-transit provider, with details and status. Used for cash logistics and security.'
            ],

            'cash_movements' => [
                'description' => 'Tracks movements of cash between tills, vaults, and accounts, including source, destination, amount, and approval. Used for cash management and audit.',
                'keywords' => ['cash movement', 'cash transfer', 'cash flow', 'source', 'destination'],
                'fields' => $this->getTableFields('cash_movements'),
                'sample_queries' => ['cash movements', 'cash transfers', 'cash flow', 'movement approval'],
                'data_patterns' => 'Each record tracks a cash movement, with source, destination, amount, and approval. Used for cash management and audit.'
            ],

            'charges' => [
                'description' => 'Fee and charge definitions with charge types, amounts, and pricing configurations. Core entity for fee management and service charges.',
                'keywords' => ['charge', 'fee', 'service charge', 'product charge', 'cost', 'pricing', 'charge amount'],
                'fields' => $this->getTableFields('charges'),
                'sample_queries' => ['charges', 'fees', 'service charges', 'charge count', 'product charges', 'charge amounts'],
                'data_patterns' => 'Contains fee definitions with charge names, types, amounts, descriptions, status, and relationship to products and services'
            ],

            'chargeslist' => [
                'description' => 'Lists available charges and fees, including charge types, amounts, and applicability. Used for product pricing and compliance.',
                'keywords' => ['charge', 'fee', 'charge list', 'pricing', 'applicable charge'],
                'fields' => $this->getTableFields('chargeslist'),
                'sample_queries' => ['charges', 'fees', 'charge types', 'applicable charges'],
                'data_patterns' => 'Each record lists a charge or fee, with type, amount, and applicability. Used for product pricing and compliance.'
            ],

 

            'client_documents' => [
                'description' => 'Member document storage and verification with document types, file paths, and verification status. Core entity for document management and compliance.',
                'keywords' => ['document', 'member document', 'verification', 'id document', 'proof', 'document storage', 'file upload'],
                'fields' => $this->getTableFields('client_documents'),
                'sample_queries' => ['member documents', 'document count', 'verification documents', 'document storage', 'file uploads'],
                'data_patterns' => 'Contains uploaded member documents with file paths, document types, verification status, upload dates, and relationship to members and verification processes'
            ],

            'clients' => [
                'description' => 'SACCO members and customers with comprehensive personal, financial, and demographic information. Primary entity for member management including individual, business, and group memberships.',
                'keywords' => ['client', 'member', 'customer', 'sacco member', 'account holder', 'member registration', 'individual member', 'business member', 'group member', 'member profile'],
                'fields' => $this->getTableFields('clients'),
                'sample_queries' => ['members', 'clients', 'member count', 'registered members', 'active members', 'member details', 'member information', 'client profiles', 'member demographics'],
                'data_patterns' => 'Contains member personal information (name, contact, demographics), financial data (income, expenses), employment details, guarantor information, membership status, and comprehensive address and identification data'
            ],

            'collateral_types' => [
                'description' => 'Defines types of collateral accepted for loans, including type name, description, and eligibility. Used for risk management and loan processing.',
                'keywords' => ['collateral type', 'collateral', 'loan collateral', 'risk management', 'eligibility'],
                'fields' => $this->getTableFields('collateral_types'),
                'sample_queries' => ['collateral types', 'accepted collateral', 'collateral eligibility'],
                'data_patterns' => 'Each record defines a collateral type, with name, description, and eligibility. Used for risk management and loan processing.'
            ],





            'contract_managements' => [
                'description' => 'Tracks contracts and agreements, including contract terms, parties, status, and renewal dates. Used for legal compliance and contract management.',
                'keywords' => ['contract', 'agreement', 'contract management', 'contract status', 'renewal'],
                'fields' => $this->getTableFields('contract_managements'),
                'sample_queries' => ['contracts', 'active contracts', 'contract status', 'renewal dates'],
                'data_patterns' => 'Each record tracks a contract, with terms, parties, status, and renewal dates. Used for legal compliance and management.'
            ],

            'currencies' => [
                'description' => 'Lists supported currencies, including currency codes, names, and exchange rates. Used for multi-currency transactions and reporting.',
                'keywords' => ['currency', 'currency code', 'exchange rate', 'multi-currency', 'supported currency'],
                'fields' => $this->getTableFields('currencies'),
                'sample_queries' => ['currencies', 'currency codes', 'exchange rates', 'supported currencies'],
                'data_patterns' => 'Each record lists a currency, with code, name, and exchange rate. Used for multi-currency transactions and reporting.'
            ],

            'current_loans_stages' => [
                'description' => 'Tracks the current stage of loan applications, including stage name, status, and timestamps. Used for workflow management and reporting.',
                'keywords' => ['loan stage', 'application stage', 'workflow stage', 'loan status'],
                'fields' => $this->getTableFields('current_loans_stages'),
                'sample_queries' => ['loan stages', 'current loan stage', 'application workflow'],
                'data_patterns' => 'Each record tracks a loan application stage, with name, status, and timestamps. Used for workflow management and reporting.'
            ],


            'departments' => [
                'description' => 'Organizational departments and functional units with department codes and descriptions. Core entity for organizational structure and department management.',
                'keywords' => ['department', 'unit', 'division', 'functional area', 'organizational unit', 'department code', 'department name'],
                'fields' => $this->getTableFields('departments'),
                'sample_queries' => ['departments', 'department count', 'organizational units', 'department information', 'department structure'],
                'data_patterns' => 'Contains department information with department codes, names, descriptions, status, and relationship to institutions and employees'
            ],





            'document_types' => [
                'description' => 'Defines types of documents used in the system, including document name, category, and requirements. Used for document management and compliance.',
                'keywords' => ['document type', 'document', 'document category', 'document requirement'],
                'fields' => $this->getTableFields('document_types'),
                'sample_queries' => ['document types', 'required documents', 'document categories'],
                'data_patterns' => 'Each record defines a document type, with name, category, and requirements. Used for document management and compliance.'
            ],

            'employee_requests' => [
                'description' => 'Tracks requests made by employees, including request type, status, and approval. Used for HR and workflow management.',
                'keywords' => ['employee request', 'HR request', 'request type', 'request status', 'approval'],
                'fields' => $this->getTableFields('employee_requests'),
                'sample_queries' => ['employee requests', 'pending requests', 'request status', 'approved requests'],
                'data_patterns' => 'Each record tracks an employee request, with type, status, and approval. Used for HR and workflow management.'
            ],

            'employee_roles' => [
                'description' => 'Defines roles assigned to employees, including role name, permissions, and status. Used for HR and access control.',
                'keywords' => ['employee role', 'role', 'HR role', 'role assignment', 'role permission'],
                'fields' => $this->getTableFields('employee_roles'),
                'sample_queries' => ['employee roles', 'assigned roles', 'role permissions'],
                'data_patterns' => 'Each record defines an employee role, with name, permissions, and status. Used for HR and access control.'
            ],

            'employeefiles' => [
                'description' => 'Stores files and documents related to employees, including file type, path, and status. Used for HR and compliance.',
                'keywords' => ['employee file', 'HR file', 'employee document', 'file type', 'file status'],
                'fields' => $this->getTableFields('employeefiles'),
                'sample_queries' => ['employee files', 'HR documents', 'file status'],
                'data_patterns' => 'Each record stores a file related to an employee, with type, path, and status. Used for HR and compliance.'
            ],

            'employees' => [
                'description' => 'Staff records, employment details, and personnel information with job positions and employment history. Core entity for human resource management.',
                'keywords' => ['employee', 'staff', 'personnel', 'worker', 'employment', 'staff member', 'employee record', 'job position'],
                'fields' => $this->getTableFields('employees'),
                'sample_queries' => ['employees', 'staff count', 'personnel', 'employee details', 'employee information', 'staff records'],
                'data_patterns' => 'Contains employee information with employee numbers, names, positions, hire dates, employment status, and relationship to departments and users'
            ],



            'expenses' => [
                'description' => 'Expense tracking and management with expense categories, amounts, and approval workflows. Core entity for expense management and cost tracking.',
                'keywords' => ['expense', 'cost', 'expenditure', 'spending', 'expense tracking', 'expense amount', 'expense category'],
                'fields' => $this->getTableFields('expenses'),
                'sample_queries' => ['expenses', 'expense count', 'spending', 'costs', 'expense amounts', 'expense categories'],
                'data_patterns' => 'Contains expense records with descriptions, amounts, categories, dates, status, approval information, and relationship to employees and departments'
            ],


            'general_ledger' => [
                'description' => 'Tracks general ledger entries, including account, debit, credit, and transaction details. Used for accounting and financial reporting.',
                'keywords' => ['general ledger', 'ledger entry', 'accounting', 'debit', 'credit', 'transaction'],
                'fields' => $this->getTableFields('general_ledger'),
                'sample_queries' => ['general ledger', 'ledger entries', 'debit and credit', 'accounting transactions'],
                'data_patterns' => 'Each record tracks a general ledger entry, with account, debit, credit, and transaction details. Used for accounting and reporting.'
            ],

    
            'groups' => [
                'description' => 'Member groups and associations with group definitions and member relationships. Core entity for group membership and collective operations.',
                'keywords' => ['group', 'member group', 'association', 'collective', 'group membership', 'group definition'],
                'fields' => $this->getTableFields('groups'),
                'sample_queries' => ['groups', 'group count', 'member groups', 'associations', 'group memberships', 'group definitions'],
                'data_patterns' => 'Contains group definitions with group names, descriptions, creation dates, status, and relationship to members and group loans'
            ],

            'guarantors' => [
                'description' => 'Loan guarantor information and guarantee relationships with guarantor details and guarantee amounts. Core entity for loan guarantee management.',
                'keywords' => ['guarantor', 'guarantee', 'loan guarantee', 'guarantor information', 'guarantee amount', 'guarantor relationship'],
                'fields' => $this->getTableFields('guarantors'),
                'sample_queries' => ['guarantors', 'guarantee', 'loan guarantees', 'guarantor information', 'guarantee amounts'],
                'data_patterns' => 'Contains guarantor information with member references, guarantor details, relationship types, guarantee amounts, status, and relationship to loans'
            ],

   

            'historical_balances' => [
                'description' => 'Stores historical balances for institutions and branches, including balance date, account codes, and balance amounts. Used for financial reporting and trend analysis.',
                'keywords' => ['historical balance', 'institution balance', 'branch balance', 'balance date', 'trend analysis'],
                'fields' => $this->getTableFields('historical_balances'),
                'sample_queries' => ['historical balances', 'balance for date X', 'institution balance history'],
                'data_patterns' => 'Each record stores a historical balance for an institution or branch, with date, account codes, and balance. Used for reporting and analysis.'
            ],


            'institution_files' => [
                'description' => 'Stores files and documents related to the institution, including file type, path, and status. Used for compliance and knowledge management.',
                'keywords' => ['institution file', 'institution document', 'file type', 'file status'],
                'fields' => $this->getTableFields('institution_files'),
                'sample_queries' => ['institution files', 'institution documents', 'file status'],
                'data_patterns' => 'Each record stores a file related to the institution, with type, path, and status. Used for compliance and knowledge management.'
            ],

            'institutions' => [
                'description' => 'Financial institutions and SACCO organizations with organizational details and contact information. Core entity for institution management and configuration.',
                'keywords' => ['institution', 'organization', 'sacco', 'financial institution', 'company', 'institution details'],
                'fields' => $this->getTableFields('institutions'),
                'sample_queries' => ['institutions', 'organization details', 'sacco information', 'institution count', 'institution details'],
                'data_patterns' => 'Contains institution information with institution codes, names, addresses, contact details, status, and relationship to branches and departments'
            ],


            'interviews' => [
                'description' => 'Tracks interviews conducted for applicants, employees, or members, including interview date, participants, and outcome. Used for HR and onboarding.',
                'keywords' => ['interview', 'applicant interview', 'employee interview', 'interview outcome'],
                'fields' => $this->getTableFields('interviews'),
                'sample_queries' => ['interviews', 'interview dates', 'interview outcomes'],
                'data_patterns' => 'Each record tracks an interview, with date, participants, and outcome. Used for HR and onboarding.'
            ],



            'investments_list' => [
                'description' => 'Catalog of investment opportunities or holdings, including investment types, values, and associated accounts. Used for tracking organizational or member investments.',
                'keywords' => ['investment', 'investment list', 'investment type', 'investment value', 'investment account'],
                'fields' => $this->getTableFields('investments_list'),
                'sample_queries' => ['investments', 'investment value', 'investment type', 'investment account details'],
                'data_patterns' => 'Each record includes investment type, value, account linkage, and status. Used for portfolio management and reporting.'
            ],

            'issued_shares' => [
                'description' => 'Tracks shares issued to members, including reference numbers, share products, account linkage, price, and status. Part of the shares product category (product_number=1000). Used for auditing share allocations and member equity.',
                'keywords' => ['issued shares', 'share issuance', 'share allocation', 'share product', 'member shares', 'share account', 'product number 1000'],
                'fields' => $this->getTableFields('issued_shares'),
                'sample_queries' => ['issued shares', 'shares issued to member X', 'share issuance history', 'share allocation status', 'shares product number 1000'],
                'data_patterns' => 'Each record links to a member, share product, and account, with details on number of shares, price, total value, and status. Used for tracking share distribution and compliance. Part of the main shares product category (product_number=1000).'
            ],

            'job_postings' => [
                'description' => 'Tracks job openings and recruitment postings, including job titles, descriptions, requirements, and status. Used for HR and recruitment workflows.',
                'keywords' => ['job posting', 'recruitment', 'job opening', 'job title', 'job status'],
                'fields' => $this->getTableFields('job_postings'),
                'sample_queries' => ['job postings', 'open jobs', 'job requirements', 'job status'],
                'data_patterns' => 'Each record includes job title, description, requirements, and status. Used for managing recruitment and HR processes.'
            ],

       

            'landed_property_types' => [
                'description' => 'Defines types of landed property for collateral or investment, including property categories and characteristics. Used for property management and collateral assessment.',
                'keywords' => ['landed property', 'property type', 'property category', 'real estate', 'collateral property'],
                'fields' => $this->getTableFields('landed_property_types'),
                'sample_queries' => ['property types', 'landed property categories', 'collateral property types'],
                'data_patterns' => 'Each record defines a property type/category, used for classifying collateral or investments.'
            ],

            'leaderships' => [
                'description' => 'Tracks leadership roles and assignments within the organization, including positions, tenure, and responsibilities. Used for governance and compliance.',
                'keywords' => ['leadership', 'leadership role', 'governance', 'position', 'tenure'],
                'fields' => $this->getTableFields('leaderships'),
                'sample_queries' => ['leadership roles', 'current leaders', 'leadership tenure', 'governance structure'],
                'data_patterns' => 'Each record includes leader, position, tenure, and responsibilities. Used for organizational charts and compliance.'
            ],

            'leave_management' => [
                'description' => 'Manages employee leave requests, approvals, and balances, including leave types, dates, and status. Used for HR and payroll.',
                'keywords' => ['leave management', 'employee leave', 'leave request', 'leave approval', 'leave balance'],
                'fields' => $this->getTableFields('leave_management'),
                'sample_queries' => ['leave requests', 'leave approvals', 'leave balances', 'leave status'],
                'data_patterns' => 'Each record tracks employee, leave type, dates, status, and approval. Used for HR and payroll processing.'
            ],

            'leaves' => [
                'description' => 'Records individual leave entries for employees, including leave type, duration, and approval status. Used for detailed leave tracking and reporting.',
                'keywords' => ['leave', 'employee leave', 'leave entry', 'leave type', 'leave status'],
                'fields' => $this->getTableFields('leaves'),
                'sample_queries' => ['leaves', 'leave entries', 'leave status', 'leave type for employee X'],
                'data_patterns' => 'Each record includes employee, leave type, duration, and approval status. Used for HR analytics and compliance.'
            ],





            'loan_guarantors' => [
                'description' => 'Tracks guarantors for loans, including member linkage, guarantee amount, and relationship to the borrower. Used for risk assessment and loan approval.',
                'keywords' => ['loan guarantor', 'guarantor', 'guarantee amount', 'loan guarantee', 'guarantor relationship'],
                'fields' => $this->getTableFields('loan_guarantors'),
                'sample_queries' => ['loan guarantors', 'guarantor for loan X', 'guarantee amount', 'guarantor status'],
                'data_patterns' => 'Each record links a guarantor to a loan, with guarantee amount, relationship, and status. Used for loan risk management.'
            ],

            'loan_images' => [
                'description' => 'Stores images and documents related to loan applications, such as collateral photos or signed agreements. Used for compliance and verification.',
                'keywords' => ['loan image', 'loan document', 'collateral photo', 'agreement image', 'loan verification'],
                'fields' => $this->getTableFields('loan_images'),
                'sample_queries' => ['loan images', 'collateral photos', 'loan documents for loan X'],
                'data_patterns' => 'Each record links an image/document to a loan, with file path, type, and status. Used for compliance and verification.'
            ],

            'loan_process_progress' => [
                'description' => 'Tracks the progress of loan applications through various stages, including approvals, verifications, and disbursements. Used for workflow management and reporting.',
                'keywords' => ['loan process', 'loan progress', 'application stage', 'approval workflow', 'loan status'],
                'fields' => $this->getTableFields('loan_process_progress'),
                'sample_queries' => ['loan process progress', 'application stage for loan X', 'pending approvals', 'workflow status'],
                'data_patterns' => 'Each record tracks a loan through workflow stages, with timestamps, status, and responsible users. Used for process optimization and compliance.'
            ],

            'loan_product_charges' => [
                'description' => 'Defines charges and fees associated with loan products, including charge types, amounts, and applicability. Used for loan pricing and compliance.',
                'keywords' => ['loan charge', 'product charge', 'loan fee', 'charge type', 'loan pricing'],
                'fields' => $this->getTableFields('loan_product_charges'),
                'sample_queries' => ['loan product charges', 'charges for loan product X', 'loan fee structure'],
                'data_patterns' => 'Each record defines a charge for a loan product, with type, amount, and applicability. Used for pricing and compliance.'
            ],

            'loan_provision_settings' => [
                'description' => 'Configures provisioning rules for loan loss reserves, including thresholds, rates, and product linkage. Used for financial risk management and compliance.',
                'keywords' => ['loan provision', 'provisioning', 'loan loss reserve', 'provision rate', 'risk management'],
                'fields' => $this->getTableFields('loan_provision_settings'),
                'sample_queries' => ['loan provision settings', 'provisioning rules', 'loan loss reserve rates'],
                'data_patterns' => 'Each record defines provisioning rules for loan products, with thresholds, rates, and product linkage. Used for risk and compliance.'
            ],

      
            'loan_sub_products' => [
                'description' => 'Loan product types and features with interest rates, terms, and product configurations. Part of the four main SACCO products where loans have product_number=4000. Core entity for loan product management and pricing.',
                'keywords' => ['loan product', 'product type', 'loan type', 'product feature', 'loan category', 'interest rate', 'loan term', 'product number 4000'],
                'fields' => $this->getTableFields('loan_sub_products'),
                'sample_queries' => ['loan products', 'product types', 'loan categories', 'product count', 'interest rates', 'loan terms', 'loan product number 4000'],
                'data_patterns' => 'Contains loan product definitions with product_number=4000, product IDs, names, interest rates, terms, minimum/maximum amounts, collection accounts, and product-specific configurations. Part of the main loans product category.'
            ],

            'loans' => [
                'description' => 'Loan applications, disbursements, and repayment tracking with comprehensive business assessment data. Core entity for lending operations including loan products, collateral, guarantors, and risk assessment.',
                'keywords' => ['loan', 'lending', 'borrowing', 'loan application', 'loan status', 'disbursement', 'loan product', 'loan amount', 'interest rate', 'loan term', 'collateral'],
                'fields' => $this->getTableFields('loans'),
                'sample_queries' => ['loans', 'loan count', 'active loans', 'loan applications', 'disbursed loans', 'loan portfolio', 'loan status', 'loan amounts', 'interest rates'],
                'data_patterns' => 'Contains loan applications with principle amounts, interest rates, terms, business assessment data (inventory, sales, expenses), collateral information, guarantor details, approval stages, and comprehensive risk assessment metrics'
            ],

            'loans_arreas' => [
                'description' => 'Tracks loans in arrears, including overdue amounts, days in arrears, and status. Used for collections and risk management.',
                'keywords' => ['loan arrears', 'overdue loan', 'arrears amount', 'days in arrears', 'collections'],
                'fields' => $this->getTableFields('loans_arreas'),
                'sample_queries' => ['loans in arrears', 'overdue loans', 'arrears amount', 'days in arrears for loan X'],
                'data_patterns' => 'Each record tracks a loan with overdue payments, including amount, days overdue, and status. Used for collections and risk reporting.'
            ],



            'loans_summary' => [
                'description' => 'Aggregates loan portfolio statistics, including total loans, outstanding amounts, and risk metrics. Used for management reporting and analytics.',
                'keywords' => ['loan summary', 'loan portfolio', 'outstanding loans', 'loan statistics', 'risk metrics'],
                'fields' => $this->getTableFields('loans_summary'),
                'sample_queries' => ['loan summary', 'total loans', 'outstanding loan amount', 'portfolio risk metrics'],
                'data_patterns' => 'Each record summarizes loan portfolio data, including totals, risk metrics, and trends. Used for management and board reporting.'
            ],

            'locked_amounts' => [
                'description' => 'Tracks amounts locked in accounts for specific services or compliance, including lock reason, status, and release details. Used for risk management and regulatory compliance.',
                'keywords' => ['locked amount', 'account lock', 'service lock', 'compliance', 'risk management', 'lock status'],
                'fields' => $this->getTableFields('locked_amounts'),
                'sample_queries' => ['locked amounts', 'locked funds for account X', 'active locks', 'lock status'],
                'data_patterns' => 'Each record links to an account, service, and user, with amount, reason, status, and lock/release timestamps. Used for regulatory and operational controls.'
            ],



            'main_budget' => [
                'description' => 'Tracks the main organizational budget, including allocations, expenditures, and balances. Used for financial planning and control.',
                'keywords' => ['budget', 'main budget', 'budget allocation', 'budget expenditure', 'budget balance'],
                'fields' => $this->getTableFields('main_budget'),
                'sample_queries' => ['main budget', 'budget allocation', 'budget expenditure', 'budget balance'],
                'data_patterns' => 'Each record tracks budget allocations, expenditures, and balances for the organization. Used for financial planning and control.'
            ],



            'mandatory_savings_notifications' => [
                'description' => 'Tracks notifications sent to members regarding mandatory savings requirements, including notification type, status, and delivery details. Used for compliance and member engagement.',
                'keywords' => ['mandatory savings', 'savings notification', 'compliance notification', 'member notification'],
                'fields' => $this->getTableFields('mandatory_savings_notifications'),
                'sample_queries' => ['mandatory savings notifications', 'notification status', 'compliance notifications sent'],
                'data_patterns' => 'Each record tracks a notification sent to a member regarding mandatory savings, with type, status, and delivery details. Used for compliance and engagement.'
            ],

            'mandatory_savings_settings' => [
                'description' => 'Configures rules and thresholds for mandatory savings, including minimum amounts, frequency, and enforcement. Used for compliance and product configuration.',
                'keywords' => ['mandatory savings', 'savings settings', 'compliance settings', 'minimum savings', 'savings frequency'],
                'fields' => $this->getTableFields('mandatory_savings_settings'),
                'sample_queries' => ['mandatory savings settings', 'minimum savings amount', 'savings frequency rules'],
                'data_patterns' => 'Each record defines a rule or threshold for mandatory savings, including minimums, frequency, and enforcement. Used for compliance and configuration.'
            ],

            'mandatory_savings_tracking' => [
                'description' => 'Tracks member compliance with mandatory savings requirements, including amounts saved, periods, and status. Used for compliance monitoring and enforcement.',
                'keywords' => ['mandatory savings', 'savings tracking', 'compliance tracking', 'savings period', 'savings status'],
                'fields' => $this->getTableFields('mandatory_savings_tracking'),
                'sample_queries' => ['mandatory savings tracking', 'compliance status', 'savings for member X', 'periodic savings compliance'],
                'data_patterns' => 'Each record tracks a member\'s compliance with mandatory savings, including amounts, periods, and status. Used for monitoring and enforcement.'
            ],

            'meeting_attendance' => [
                'description' => 'Tracks attendance at organizational meetings, including member participation, meeting dates, and status. Used for compliance and engagement reporting.',
                'keywords' => ['meeting attendance', 'attendance tracking', 'meeting participation', 'member attendance'],
                'fields' => $this->getTableFields('meeting_attendance'),
                'sample_queries' => ['meeting attendance', 'attendance for meeting X', 'member participation', 'attendance status'],
                'data_patterns' => 'Each record tracks attendance for a meeting, including member, date, and status. Used for compliance and engagement.'
            ],

            'meeting_documents' => [
                'description' => 'Stores documents and files related to meetings, such as agendas, minutes, and presentations. Used for compliance and knowledge management.',
                'keywords' => ['meeting document', 'meeting file', 'agenda', 'minutes', 'presentation'],
                'fields' => $this->getTableFields('meeting_documents'),
                'sample_queries' => ['meeting documents', 'agenda for meeting X', 'meeting minutes', 'meeting files'],
                'data_patterns' => 'Each record links a document to a meeting, with file path, type, and status. Used for compliance and knowledge management.'
            ],

            'meetings' => [
                'description' => 'Tracks organizational meetings, including meeting dates, agendas, participants, and outcomes. Used for compliance, planning, and engagement.',
                'keywords' => ['meeting', 'organizational meeting', 'meeting agenda', 'meeting date', 'meeting outcome', 'participants'],
                'fields' => $this->getTableFields('meetings'),
                'sample_queries' => ['meetings', 'meeting dates', 'meeting agendas', 'meeting participants', 'meeting outcomes'],
                'data_patterns' => 'Each record includes meeting date, agenda, participants, and outcomes. Used for compliance, planning, and engagement.'
            ],

            'menu_actions' => [
                'description' => 'Defines actions available in system menus, including action names, permissions, and linkage to menu items. Used for access control and UI configuration.',
                'keywords' => ['menu action', 'system action', 'menu permission', 'action definition', 'UI action'],
                'fields' => $this->getTableFields('menu_actions'),
                'sample_queries' => ['menu actions', 'actions for menu X', 'action permissions'],
                'data_patterns' => 'Each record defines an action available in a menu, with name, permission, and linkage to menu items. Used for access control and UI configuration.'
            ],

            'mnos' => [
                'description' => 'Lists mobile network operators (MNOs) for integration with mobile money and SMS services. Used for payment processing and communication.',
                'keywords' => ['MNO', 'mobile network operator', 'mobile money', 'SMS provider', 'telecom'],
                'fields' => $this->getTableFields('mnos'),
                'sample_queries' => ['MNOs', 'mobile network operators', 'available MNOs', 'MNO integration'],
                'data_patterns' => 'Each record defines a mobile network operator, with name, code, and integration details. Used for mobile money and SMS services.'
            ],

            'movable_property_types' => [
                'description' => 'Defines types of movable property for collateral or asset management, including categories and characteristics. Used for asset tracking and collateral assessment.',
                'keywords' => ['movable property', 'property type', 'asset type', 'collateral property', 'movable asset'],
                'fields' => $this->getTableFields('movable_property_types'),
                'sample_queries' => ['movable property types', 'asset categories', 'collateral property types'],
                'data_patterns' => 'Each record defines a movable property type/category, used for classifying assets or collateral.'
            ],

            'notifications' => [
                'description' => 'Tracks system notifications sent to users, including notification type, recipient, status, and delivery details. Used for communication and compliance.',
                'keywords' => ['notification', 'system notification', 'user notification', 'notification status', 'notification type'],
                'fields' => $this->getTableFields('notifications'),
                'sample_queries' => ['notifications', 'sent notifications', 'notification status', 'user notifications'],
                'data_patterns' => 'Each record tracks a notification sent to a user, with type, recipient, status, and delivery details. Used for communication and compliance.'
            ],

            'onboarding' => [
                'description' => 'Tracks onboarding processes for new members or employees, including steps completed, status, and responsible users. Used for workflow management and compliance.',
                'keywords' => ['onboarding', 'onboarding process', 'new member onboarding', 'employee onboarding', 'workflow'],
                'fields' => $this->getTableFields('onboarding'),
                'sample_queries' => ['onboarding', 'onboarding status', 'onboarding steps', 'completed onboarding'],
                'data_patterns' => 'Each record tracks onboarding steps, status, and responsible users for a new member or employee. Used for workflow management and compliance.'
            ],

            'pay_rolls' => [
                'description' => 'Tracks payroll processing for employees, including salary payments, deductions, and payroll periods. Used for HR and financial management.',
                'keywords' => ['payroll', 'salary payment', 'payroll processing', 'employee payroll', 'payroll period'],
                'fields' => $this->getTableFields('pay_rolls'),
                'sample_queries' => ['payroll', 'salary payments', 'payroll periods', 'employee payroll details'],
                'data_patterns' => 'Each record tracks payroll processing for an employee, including salary, deductions, and period. Used for HR and financial management.'
            ],

            'payables' => [
                'description' => 'Tracks amounts owed by the organization to suppliers or creditors, including due dates, amounts, and payment status. Used for accounts payable and cash flow management.',
                'keywords' => ['payable', 'accounts payable', 'supplier payment', 'creditor', 'payment due'],
                'fields' => $this->getTableFields('payables'),
                'sample_queries' => ['payables', 'amounts due', 'supplier payments', 'payment status'],
                'data_patterns' => 'Each record tracks an amount owed to a supplier or creditor, with due date, amount, and payment status. Used for accounts payable and cash flow management.'
            ],

            'payment_methods' => [
                'description' => 'Defines available payment methods for transactions, including method names, types, and configuration. Used for payment processing and configuration.',
                'keywords' => ['payment method', 'transaction method', 'payment type', 'payment configuration'],
                'fields' => $this->getTableFields('payment_methods'),
                'sample_queries' => ['payment methods', 'available payment methods', 'payment method configuration'],
                'data_patterns' => 'Each record defines a payment method, with name, type, and configuration. Used for payment processing and configuration.'
            ],

            'payment_notifications' => [
                'description' => 'Tracks notifications sent to users regarding payments, including payment status, amount, and delivery details. Used for communication and compliance.',
                'keywords' => ['payment notification', 'payment status', 'payment communication', 'user notification'],
                'fields' => $this->getTableFields('payment_notifications'),
                'sample_queries' => ['payment notifications', 'payment status notifications', 'sent payment notifications'],
                'data_patterns' => 'Each record tracks a notification sent to a user regarding a payment, with status, amount, and delivery details. Used for communication and compliance.'
            ],

            'pending_registrations' => [
                'description' => 'Tracks pending registrations for new members, employees, or accounts, including status, required documents, and responsible users. Used for workflow management and compliance.',
                'keywords' => ['pending registration', 'registration status', 'new member registration', 'employee registration'],
                'fields' => $this->getTableFields('pending_registrations'),
                'sample_queries' => ['pending registrations', 'registration status', 'required documents', 'pending member registrations'],
                'data_patterns' => 'Each record tracks a pending registration, with status, required documents, and responsible users. Used for workflow management and compliance.'
            ],

            'permissions' => [
                'description' => 'System permissions and access rights with permission definitions and granular access control. Core entity for permission management and security.',
                'keywords' => ['permission', 'access right', 'system permission', 'permission definition', 'access control'],
                'fields' => $this->getTableFields('permissions'),
                'sample_queries' => ['permissions', 'permission count', 'access rights', 'system permissions', 'permission definitions'],
                'data_patterns' => 'Contains permission definitions with permission names, descriptions, categories, status, and relationship to roles and menu actions'
            ],


            'ppes' => [
                'description' => 'Tracks property, plant, and equipment (PPE) assets, including asset types, values, depreciation, and status. Used for asset management and financial reporting.',
                'keywords' => ['PPE', 'property', 'plant', 'equipment', 'asset', 'depreciation'],
                'fields' => $this->getTableFields('ppes'),
                'sample_queries' => ['PPE assets', 'asset values', 'depreciation schedule', 'asset status'],
                'data_patterns' => 'Each record tracks a PPE asset, with type, value, depreciation, and status. Used for asset management and financial reporting.'
            ],

            'process_code_configs' => [
                'description' => 'Defines process codes and configurations for workflow automation, including code definitions, parameters, and status. Used for workflow and process management.',
                'keywords' => ['process code', 'workflow automation', 'process configuration', 'code definition'],
                'fields' => $this->getTableFields('process_code_configs'),
                'sample_queries' => ['process codes', 'workflow codes', 'process configuration'],
                'data_patterns' => 'Each record defines a process code and configuration, with parameters and status. Used for workflow and process management.'
            ],

            'product_has_charges' => [
                'description' => 'Links products to applicable charges, including charge types, amounts, and applicability. Used for product pricing and compliance.',
                'keywords' => ['product charge', 'product pricing', 'charge type', 'applicable charge'],
                'fields' => $this->getTableFields('product_has_charges'),
                'sample_queries' => ['product charges', 'charges for product X', 'applicable charges'],
                'data_patterns' => 'Each record links a product to a charge, with type, amount, and applicability. Used for product pricing and compliance.'
            ],

            'product_has_insurance' => [
                'description' => 'Links products to applicable insurance policies, including policy details, coverage, and status. Used for risk management and compliance.',
                'keywords' => ['product insurance', 'insurance policy', 'product risk', 'insurance coverage'],
                'fields' => $this->getTableFields('product_has_insurance'),
                'sample_queries' => ['product insurance', 'insurance for product X', 'applicable insurance policies'],
                'data_patterns' => 'Each record links a product to an insurance policy, with details, coverage, and status. Used for risk management and compliance.'
            ],


            'receivables' => [
                'description' => 'Tracks amounts owed to the organization by customers or members, including invoice numbers, due dates, and collection status. Used for accounts receivable and cash flow management.',
                'keywords' => ['receivable', 'accounts receivable', 'customer invoice', 'amount due', 'collection status'],
                'fields' => $this->getTableFields('receivables'),
                'sample_queries' => ['receivables', 'amounts due', 'customer invoices', 'collection status'],
                'data_patterns' => 'Each record tracks an amount owed by a customer or member, with invoice number, due date, and collection status. Used for accounts receivable and cash flow management.'
            ],


            'reports' => [
                'description' => 'Report definitions and configurations with report types, parameters, and scheduling. Core entity for reporting system and analytics.',
                'keywords' => ['report', 'reporting', 'analytics', 'report definition', 'report configuration', 'report type'],
                'fields' => $this->getTableFields('reports'),
                'sample_queries' => ['reports', 'report count', 'reporting', 'analytics', 'report types', 'report definitions'],
                'data_patterns' => 'Contains report definitions with report names, types, descriptions, parameters, status, and relationship to scheduled reports and users'
            ],

            'role_menu_actions' => [
                'description' => 'Links roles to menu actions, defining which actions are permitted for each role. Used for access control and security.',
                'keywords' => ['role menu action', 'role permission', 'menu action', 'access control'],
                'fields' => $this->getTableFields('role_menu_actions'),
                'sample_queries' => ['role menu actions', 'actions for role X', 'permitted actions'],
                'data_patterns' => 'Each record links a role to a menu action, defining permitted actions for that role. Used for access control and security.'
            ],

            'role_permissions' => [
                'description' => 'Links roles to permissions, defining which permissions are granted to each role. Used for access control and security.',
                'keywords' => ['role permission', 'role access', 'permission assignment', 'access control'],
                'fields' => $this->getTableFields('role_permissions'),
                'sample_queries' => ['role permissions', 'permissions for role X', 'granted permissions'],
                'data_patterns' => 'Each record links a role to a permission, defining granted permissions for that role. Used for access control and security.'
            ],

            'roles' => [
                'description' => 'User roles and permissions with role definitions and access control configurations. Core entity for role-based access control and security management.',
                'keywords' => ['role', 'permission', 'access control', 'user role', 'role definition', 'security role'],
                'fields' => $this->getTableFields('roles'),
                'sample_queries' => ['roles', 'role count', 'user roles', 'permissions', 'access control', 'role definitions'],
                'data_patterns' => 'Contains role definitions with role names, descriptions, permissions, status, and relationship to users and menu actions'
            ],

  
            'scheduled_reports' => [
                'description' => 'Tracks scheduled and automated reports, including report type, frequency, status, and delivery details. Used for reporting automation and compliance.',
                'keywords' => ['scheduled report', 'automated report', 'report scheduling', 'report delivery'],
                'fields' => $this->getTableFields('scheduled_reports'),
                'sample_queries' => ['scheduled reports', 'automated reports', 'report status', 'report delivery'],
                'data_patterns' => 'Each record tracks a scheduled or automated report, with type, frequency, status, and delivery details. Used for reporting automation and compliance.'
            ],

            'scores' => [
                'description' => 'Credit scoring and risk assessment with score types, values, and assessment history. Core entity for credit risk evaluation and scoring.',
                'keywords' => ['score', 'credit score', 'risk assessment', 'credit rating', 'scoring', 'credit evaluation'],
                'fields' => $this->getTableFields('scores'),
                'sample_queries' => ['scores', 'credit scores', 'risk assessment', 'credit ratings', 'scoring', 'credit evaluation'],
                'data_patterns' => 'Contains credit scores with member references, score types, values, assessment dates, status, and relationship to loan applications and risk assessment'
            ],

 
            'services' => [
                'description' => 'Defines services offered by the institution, including service name, code, description, limits, and account linkage. Used for product management and billing.',
                'keywords' => ['service', 'institution service', 'service code', 'service limit', 'product management'],
                'fields' => $this->getTableFields('services'),
                'sample_queries' => ['services', 'service codes', 'service limits', 'offered services'],
                'data_patterns' => 'Each record defines a service, with name, code, description, limits, and account linkage. Used for product management and billing.'
            ],

    

            'share_transfers' => [
                'description' => 'Records transfers of shares between members or accounts, including transfer amounts, source, destination, and approval status. Used for share ownership changes and compliance.',
                'keywords' => ['share transfer', 'share ownership transfer', 'transfer between members', 'share movement', 'transfer approval'],
                'fields' => $this->getTableFields('share_transfers'),
                'sample_queries' => ['share transfers', 'transfers between members', 'transfer status', 'approved transfers'],
                'data_patterns' => 'Each record tracks a share transfer, with source, destination, amount, and approval status. Used for share ownership changes and compliance.'
            ],

            'share_withdrawals' => [
                'description' => 'Tracks requests and approvals for share withdrawals by members, including withdrawn shares, value, approval status, and payment details.',
                'keywords' => ['share withdrawal', 'withdrawn shares', 'withdrawal approval', 'share redemption', 'withdrawal status'],
                'fields' => $this->getTableFields('share_withdrawals'),
                'sample_queries' => ['share withdrawals', 'withdrawal requests', 'approved share withdrawals', 'withdrawal status for member X'],
                'data_patterns' => 'Each record includes member, product, withdrawn shares, value, approval status, and payment details. Used for managing and auditing share redemptions.'
            ],

   

            'standing_instructions' => [
                'description' => 'Defines recurring payment instructions and automated transfers, including frequency, amounts, and account linkage. Used for automated payment processing.',
                'keywords' => ['standing instruction', 'recurring payment', 'automated transfer', 'payment instruction', 'scheduled payment'],
                'fields' => $this->getTableFields('standing_instructions'),
                'sample_queries' => ['standing instructions', 'recurring payments', 'automated transfers', 'active instructions'],
                'data_patterns' => 'Each record defines a recurring payment instruction, with frequency, amount, source, destination, and status. Used for automated payment processing.'
            ],

    

            'sub_products' => [
                'description' => 'Defines product variations and subtypes for the four main SACCO products (shares, savings, deposits, loans), including interest rates, terms, and product configurations. Each product has a product_number: shares=1000, savings=2000, deposits=3000, loans=4000. Used for product management and pricing.',
                'keywords' => ['sub product', 'product variation', 'product subtype', 'product configuration', 'interest rate', 'product number', 'shares product', 'savings product', 'deposits product', 'loans product'],
                'fields' => $this->getTableFields('sub_products'),
                'sample_queries' => ['sub products', 'product variations', 'interest rates', 'product configurations', 'shares products', 'savings products', 'deposits products'],
                'data_patterns' => 'Each record defines a product variation with product_number (1000=shares, 2000=savings, 3000=deposits, 4000=loans), interest rate, terms, and configuration. Used for product management and pricing across all four main SACCO product categories.'
            ],

    



            'accounts' => [
                'description' => 'Core accounting table that stores all member accounts and internal SACCO accounts with hierarchical chart of accounts structure. Links members to their product accounts (shares, savings, deposits, loans) and maintains account balances, status, and transaction history. Each account is linked to a product via product_number (shares=1000, savings=2000, deposits=3000, loans=4000). Uses 4-level hierarchical structure: Level 1=Major categories (1000=Assets, 2000=Liabilities, 3000=Equity, 4000=Revenue, 5000=Expenses), Level 2=Categories, Level 3=Sub-categories, Level 4=Individual member accounts. Parent-child relationships maintained via parent_account_number field for balance rollups and financial statement generation.',
                'keywords' => ['account', 'member account', 'product account', 'account balance', 'account status', 'account number', 'product number', 'shares account', 'savings account', 'deposits account', 'loans account', 'chart of accounts', 'account hierarchy', 'parent account', 'child account', 'account level', 'major category', 'category code', 'sub category', 'account rollup', 'financial statement', 'parent_account_number', 'account_level', 'major_category_code', 'category_code', 'sub_category_code'],
                'fields' => $this->getTableFields('accounts'),
                'sample_queries' => [
                    'member accounts for client 12345',
                    'account balance for account number 0101500056005610',
                    'all accounts with product_number 1000 (shares)',
                    'parent account 010150005600 and its child accounts',
                    'accounts at level 3 (sub-categories)',
                    'liability accounts (major_category_code 2000)',
                    'expense accounts with balance > 0',
                    'account hierarchy for assets category',
                    'member accounts linked to savings products',
                    'accounts with status ACTIVE and account_use internal',
                    'accounts with parent_account_number 010150005600',
                    'accounts at account_level 1 (major categories)',
                    'all expense accounts (major_category_code 5000)',
                    'accounts for product_number 2000 (savings)',
                    'accounts for product_number 3000 (deposits)',
                    'accounts for product_number 4000 (loans)'
                ],
                'data_patterns' => 'Account numbers follow hierarchical pattern: {institution_number}{branch_number}{major_category_code}{category_code}{sub_category_code}{member_account_code}. Parent-child relationships via parent_account_number field enable balance rollups and financial statement generation. Account levels (1-4) determine hierarchy depth. Product integration via product_number links to SACCO products (1000=shares, 2000=savings, 3000=deposits, 4000=loans). Account types (asset_accounts, liability_accounts, capital_accounts, income_accounts, expense_accounts) determine which specific account table contains detailed fields. Major categories: 1000=Assets, 2000=Liabilities, 3000=Equity, 4000=Revenue, 5000=Expenses. Used for all financial operations, reporting, chart of accounts management, and member product account tracking.'
            ],

            'sub_roles' => [
                'description' => 'Defines sub-roles and specialized permissions within main roles, including role hierarchy and access control. Used for granular access management.',
                'keywords' => ['sub role', 'role hierarchy', 'specialized permission', 'access control', 'role variation'],
                'fields' => $this->getTableFields('sub_roles'),
                'sample_queries' => ['sub roles', 'role hierarchy', 'specialized permissions', 'access control'],
                'data_patterns' => 'Each record defines a sub-role, with hierarchy, permissions, and access control. Used for granular access management.'
            ],

            'taxes' => [
                'description' => 'Defines tax rates, types, and applicability for transactions and products. Used for tax calculation and compliance.',
                'keywords' => ['tax', 'tax rate', 'tax type', 'tax calculation', 'tax compliance'],
                'fields' => $this->getTableFields('taxes'),
                'sample_queries' => ['taxes', 'tax rates', 'tax types', 'tax calculations'],
                'data_patterns' => 'Each record defines a tax, with rate, type, and applicability. Used for tax calculation and compliance.'
            ],

            'teller_end_of_day_positions' => [
                'description' => 'Tracks end-of-day cash positions for tellers, including balances, variances, and reconciliation status. Used for cash management and compliance.',
                'keywords' => ['teller end of day', 'cash position', 'teller balance', 'end of day reconciliation', 'cash variance'],
                'fields' => $this->getTableFields('teller_end_of_day_positions'),
                'sample_queries' => ['teller end of day positions', 'cash positions', 'end of day balances', 'cash variances'],
                'data_patterns' => 'Each record tracks end-of-day position for a teller, with balance, variance, and reconciliation status. Used for cash management and compliance.'
            ],

            'tellers' => [
                'description' => 'Tracks teller assignments, permissions, and transaction limits, including user linkage and branch assignment. Used for cash operations and access control.',
                'keywords' => ['teller', 'cashier', 'teller assignment', 'transaction limit', 'branch teller'],
                'fields' => $this->getTableFields('tellers'),
                'sample_queries' => ['tellers', 'teller assignments', 'transaction limits', 'branch tellers'],
                'data_patterns' => 'Each record tracks a teller assignment, with user, branch, permissions, and transaction limits. Used for cash operations and access control.'
            ],


            'till_reconciliations' => [
                'description' => 'Tracks reconciliation of till balances and transactions, including variances, explanations, and approval status. Used for cash management and compliance.',
                'keywords' => ['till reconciliation', 'cash reconciliation', 'till balance', 'variance explanation', 'reconciliation status'],
                'fields' => $this->getTableFields('till_reconciliations'),
                'sample_queries' => ['till reconciliations', 'cash reconciliations', 'variance explanations', 'reconciliation status'],
                'data_patterns' => 'Each record tracks till reconciliation, with balance, variance, explanation, and approval status. Used for cash management and compliance.'
            ],

            'till_transactions' => [
                'description' => 'Records transactions processed through tills, including amounts, types, and teller assignments. Used for cash operations and audit trails.',
                'keywords' => ['till transaction', 'cash transaction', 'teller transaction', 'transaction type', 'cash operation'],
                'fields' => $this->getTableFields('till_transactions'),
                'sample_queries' => ['till transactions', 'cash transactions', 'teller transactions', 'transaction types'],
                'data_patterns' => 'Each record tracks a transaction processed through a till, with amount, type, and teller assignment. Used for cash operations and audit trails.'
            ],

            'tills' => [
                'description' => 'Defines cash tills and their configurations, including balances, limits, and assignments. Used for cash management and operations.',
                'keywords' => ['till', 'cash till', 'till balance', 'till limit', 'till assignment'],
                'fields' => $this->getTableFields('tills'),
                'sample_queries' => ['tills', 'cash tills', 'till balances', 'till limits', 'till assignments'],
                'data_patterns' => 'Each record defines a cash till, with balance, limits, and assignment. Used for cash management and operations.'
            ],


            'transaction_reconciliations' => [
                'description' => 'Tracks reconciliation of transactions with external systems or accounts, including status, variances, and resolution. Used for audit and compliance.',
                'keywords' => ['transaction reconciliation', 'external reconciliation', 'reconciliation status', 'transaction variance', 'audit trail'],
                'fields' => $this->getTableFields('transaction_reconciliations'),
                'sample_queries' => ['transaction reconciliations', 'external reconciliations', 'reconciliation status', 'transaction variances'],
                'data_patterns' => 'Each record tracks transaction reconciliation, with status, variance, and resolution. Used for audit and compliance.'
            ],



            'transaction_reversals' => [
                'description' => 'Records reversals of transactions, including reason, approval, and audit trail. Used for error correction and compliance.',
                'keywords' => ['transaction reversal', 'reversal reason', 'error correction', 'audit trail', 'reversal approval'],
                'fields' => $this->getTableFields('transaction_reversals'),
                'sample_queries' => ['transaction reversals', 'reversal reasons', 'error corrections', 'reversal approvals'],
                'data_patterns' => 'Each record tracks a transaction reversal, with reason, approval, and audit trail. Used for error correction and compliance.'
            ],

            'transactions' => [
                'description' => 'Financial transactions and journal entries with comprehensive audit trail and reconciliation tracking. Core entity for all money movements including deposits, withdrawals, transfers, and system-generated transactions.',
                'keywords' => ['transaction', 'financial transaction', 'journal entry', 'money movement', 'payment', 'deposit', 'withdrawal', 'transfer', 'transaction history'],
                'fields' => $this->getTableFields('transactions'),
                'sample_queries' => ['transactions', 'transaction count', 'financial transactions', 'money movements', 'transaction history', 'transaction status', 'transaction amounts'],
                'data_patterns' => 'Contains financial transactions with amounts, account references, balance tracking, status management, audit logs, reconciliation data, external system integration, and comprehensive metadata for tracking and reporting'
            ],

            'unearned_deferred_revenue' => [
                'description' => 'Tracks unearned or deferred revenue, including recognition schedules and accounting treatment. Used for financial reporting and compliance.',
                'keywords' => ['unearned revenue', 'deferred revenue', 'revenue recognition', 'accounting treatment', 'financial reporting'],
                'fields' => $this->getTableFields('unearned_deferred_revenue'),
                'sample_queries' => ['unearned revenue', 'deferred revenue', 'revenue recognition', 'accounting treatment'],
                'data_patterns' => 'Each record tracks unearned or deferred revenue, with recognition schedule and accounting treatment. Used for financial reporting and compliance.'
            ],

  



            'users' => [
                'description' => 'System users and administrators with authentication, roles, permissions, and security profiles. Core entity for staff access control and system administration.',
                'keywords' => ['user', 'admin', 'administrator', 'login', 'authentication', 'system user', 'staff login', 'employee login', 'user account', 'staff member'],
                'fields' => $this->getTableFields('users'),
                'sample_queries' => ['how many users', 'active users', 'user count', 'system administrators', 'staff members', 'user login history', 'user roles'],
                'data_patterns' => 'Contains user accounts with email/password authentication, role assignments, department codes, branch assignments, and security profiles including OTP, password expiry, and access controls'
            ],

            'vaults' => [
                'description' => 'Defines cash vaults and their configurations, including balances, access controls, and security measures. Used for cash management and security.',
                'keywords' => ['vault', 'cash vault', 'vault balance', 'vault security', 'cash storage'],
                'fields' => $this->getTableFields('vaults'),
                'sample_queries' => ['vaults', 'cash vaults', 'vault balances', 'vault security', 'cash storage'],
                'data_patterns' => 'Each record defines a cash vault, with balance, access controls, and security measures. Used for cash management and security.'
            ],

            'vendors' => [
                'description' => 'Tracks vendor information and relationships, including contact details, payment terms, and performance metrics. Used for procurement and accounts payable.',
                'keywords' => ['vendor', 'supplier', 'vendor information', 'payment terms', 'procurement'],
                'fields' => $this->getTableFields('vendors'),
                'sample_queries' => ['vendors', 'suppliers', 'vendor information', 'payment terms', 'procurement'],
                'data_patterns' => 'Each record tracks vendor information, with contact details, payment terms, and performance metrics. Used for procurement and accounts payable.'
            ],

            'wards' => [
                'description' => 'Defines administrative wards or districts, including boundaries and demographic information. Used for geographic organization and reporting.',
                'keywords' => ['ward', 'administrative ward', 'district', 'geographic boundary', 'demographic'],
                'fields' => $this->getTableFields('wards'),
                'sample_queries' => ['wards', 'administrative wards', 'districts', 'geographic boundaries'],
                'data_patterns' => 'Each record defines an administrative ward, with boundaries and demographic information. Used for geographic organization and reporting.'
            ],

            'share_registers' => [
                'description' => 'Tracks each member\'s share account and all share-related activity, including issued, redeemed, and transferred shares, current balances, product type, dividend history, and account status. Part of the shares product category (product_number=1000). Serves as the authoritative ledger for member shareholdings, supporting dividend calculation, share transfers, compliance, and reporting.',
                'keywords' => ['share register', 'share account', 'member shares', 'shareholding', 'share balance', 'share product', 'issued shares', 'redeemed shares', 'transferred shares', 'dividend', 'share status', 'share ledger', 'share capital', 'share transfer', 'share compliance', 'product number 1000'],
                'fields' => $this->getTableFields('share_registers'),
                'sample_queries' => [
                    'Show all share registers for member 12345',
                    'What is the current share balance for account X?',
                    "List all active share registers for product type 'MANDATORY'",
                    'How much dividend has member 12345 received this year?',
                    'Total shares issued and redeemed for branch Y',
                    'Find all share registers with pending dividends',
                    'Share register details for account number 0012345678',
                    'shares product number 1000'
                ],
                'data_patterns' => 'Each record represents a unique share account for a member and product, tracking issued, redeemed, transferred shares, current balance, and value. Includes dividend history, account status, compliance flags, and links to member, product, and branch. Used for all share-related operations, reporting, and compliance. Part of the main shares product category (product_number=1000).'
            ],













        ];
    }

    /**
     * Estimate token count for a given text
     * @param string $text
     * @return int
     */
    private function estimateTokenCount($text)
    {
        return (int) (strlen($text) * $this->tokensPerChar);
    }

    /**
     * Check if a prompt exceeds token limits
     * @param string $prompt
     * @return bool
     */
    private function isPromptTooLarge($prompt)
    {
        $tokenCount = $this->estimateTokenCount($prompt);
        return $tokenCount > $this->maxTokensPerRequest;
    }

    /**
     * Split table information into chunks to fit within token limits
     * @param array $tableInfo
     * @return array
     */
    /**
     * Modern RAG-based semantic chunking for table information
     * @param array $tableInfo
     * @return array
     */
    private function chunkTableInfo($tableInfo)
    {
        // Convert table info to text for semantic chunking
        $fullText = $this->convertTableInfoToText($tableInfo);
        
        // Create semantic chunks with overlap
        $textChunks = $this->createSemanticChunks($fullText);
        
        // Convert chunks back to table info format
        $chunks = [];
        foreach ($textChunks as $chunkText) {
            $chunkTables = $this->extractTablesFromChunk($chunkText, $tableInfo);
            if (!empty($chunkTables)) {
                $chunks[] = $chunkTables;
            }
        }
        
        Log::info('[RAG Chunking]', [
            'original_tables' => count($tableInfo),
            'chunks_created' => count($chunks),
            'avg_chunk_size' => count($chunks) > 0 ? round(count($tableInfo) / count($chunks), 1) : 0
        ]);
        
        return $chunks;
    }



    /**
     * Generate final answer from chunked processing results
     * @param string $question
     * @param array $chunkResults
     * @param array $context
     * @param array $options
     * @return string
     */
    private function generateFinalAnswerFromChunks($question, $chunkResults, $context, $options)
    {
        // Collect all SQL results and reasoning steps
        $allData = [];
        $allReasoning = [];
        
        foreach ($chunkResults as $result) {
            if (!empty($result['sql_results'])) {
                $allData = array_merge($allData, $result['sql_results']);
            }
            if (!empty($result['reasoning_steps'])) {
                $allReasoning = array_merge($allReasoning, $result['reasoning_steps']);
            }
        }
        
        // If we have data, use it to generate answer
        if (!empty($allData)) {
            return $this->answerWithData($question, $allData, $context);
        }
        
        // If we have reasoning steps but no data, summarize the reasoning
        if (!empty($allReasoning)) {
            $reasoningSummary = $this->getReasoningSummary($allReasoning, [], [], '');
            return "Based on my analysis across multiple data sources: " . $reasoningSummary;
        }
        
        // Fallback response
        return "I analyzed the available data but couldn't find specific information to answer your question. Please try rephrasing or ask about different aspects of the data.";
    }



    /**
     * Identify tables using chunked approach for large table indexes
     * @param string $question
     * @param array $tableIndex
     * @param array $context
     * @param array $validTableNames
     * @return array
     */
    private function identifyTablesWithChunking($question, $tableIndex, $context, $validTableNames)
    {
        Log::info('[AI Chunked Table Identification Started]', [
            'question' => $question,
            'total_tables' => count($tableIndex),
            'reason' => 'table_index_too_large'
        ]);
        
        // Split table index into chunks
        $chunks = $this->chunkTableIndex($tableIndex);
        $allIdentifiedTables = [];
        
        foreach ($chunks as $chunkIndex => $chunk) {
            Log::info('[AI Processing Table Chunk]', [
                'chunk_index' => $chunkIndex + 1,
                'total_chunks' => count($chunks),
                'tables_in_chunk' => array_keys($chunk)
            ]);
            
            try {
                $chunkTables = $this->identifyTablesInChunk($question, $chunk, $context, $validTableNames);
                $allIdentifiedTables = array_merge($allIdentifiedTables, $chunkTables);
                
                Log::info('[AI Table Chunk Processed]', [
                    'chunk_index' => $chunkIndex + 1,
                    'tables_found' => $chunkTables
                ]);
                
            } catch (Exception $e) {
                Log::error('[AI Table Chunk Processing Error]', [
                    'chunk_index' => $chunkIndex + 1,
                    'error' => $e->getMessage()
                ]);
                // Continue with next chunk
            }
        }
        
        // Remove duplicates and return
        $uniqueTables = array_unique($allIdentifiedTables);
        
        Log::info('[AI Chunked Table Identification Complete]', [
            'question' => $question,
            'total_identified_tables' => count($allIdentifiedTables),
            'unique_tables' => $uniqueTables,
            'chunks_processed' => count($chunks)
        ]);
        
        return array_values($uniqueTables);
    }

    /**
     * Identify tables using normal approach for smaller table indexes
     * @param string $question
     * @param array $tableIndex
     * @param array $context
     * @param array $validTableNames
     * @return array
     */
    private function identifyTablesNormally($question, $tableIndex, $context, $validTableNames)
    {
        $prompt = "You are a database expert for a SACCO Management System. Given a question, identify which database tables are relevant to answer it.\n\n";
        $prompt .= "CRITICAL RESTRICTION: You can ONLY use tables from the following list. DO NOT reference any other tables:\n";
        $prompt .= "VALID TABLES: " . implode(', ', $validTableNames) . "\n\n";
        
        $prompt .= "COMPREHENSIVE TABLE INDEX:\n";
        foreach ($tableIndex as $table => $info) {
            $prompt .= "Table: {$table}\n";
            $prompt .= "Description: {$info['description']}\n";
            $prompt .= "Keywords: " . implode(', ', $info['keywords']) . "\n";
            $prompt .= "Fields: " . implode(', ', $info['fields']) . "\n";
            $prompt .= "Sample Queries: " . implode(', ', $info['sample_queries']) . "\n";
            $prompt .= "Data Patterns: {$info['data_patterns']}\n";
            $prompt .= "---\n";
        }
        
        $prompt .= "\nSTRICT INSTRUCTIONS:\n";
        $prompt .= "1. You MUST ONLY use tables from the VALID TABLES list above\n";
        $prompt .= "2. DO NOT reference, suggest, or use any tables not in the VALID TABLES list\n";
        $prompt .= "3. If a table you think you need is not in the VALID TABLES list, find the closest alternative from the list\n";
        $prompt .= "4. Analyze the question carefully and match it against keywords, sample queries, and data patterns\n";
        $prompt .= "5. Return ONLY table names that are relevant to answer the question\n";
        $prompt .= "6. If the question is NOT about data (e.g., greetings, general questions), return empty array\n";
        $prompt .= "7. Return as JSON array of strings\n";
        $prompt .= "8. Use the keywords, sample queries, and data patterns to find the most relevant tables\n";
        $prompt .= "9. Consider field names when matching query intent to table contents\n";
        $prompt .= "10. IMPORTANT: 'members' refers to 'clients' table\n";
        $prompt .= "11. IMPORTANT: 'savings products' refers to 'accounts' and 'charges' tables\n";
        $prompt .= "12. IMPORTANT: 'asset accounts' refers to 'accounts' table with major_category_code = '1000'\n";
        $prompt .= "13. IMPORTANT: If you cannot find a relevant table in the VALID TABLES list, return empty array\n\n";
        
        $prompt .= "EXAMPLES:\n";
        $prompt .= "Question: 'How many users are in the system?' → [\"users\"]\n";
        $prompt .= "Question: 'How many members do we have?' → [\"clients\"]\n";
        $prompt .= "Question: 'How many savings products do we have?' → [\"accounts\", \"charges\"]\n";
        $prompt .= "Question: 'How many asset accounts are there?' → [\"accounts\"]\n";
        $prompt .= "Question: 'List all clients and their loans' → [\"clients\", \"loans\"]\n";
        $prompt .= "Question: 'Hello, how are you?' → []\n";
        $prompt .= "Question: 'What is the weather?' → []\n";
        $prompt .= "Question: 'Show me data from invalid_table' → [] (because invalid_table is not in VALID TABLES)\n\n";
        
        $prompt .= "QUESTION: {$question}\n\n";
        $prompt .= "Return relevant table names as JSON array (ONLY from VALID TABLES list):";

        $response = $this->executeWithFallback($prompt, $context, 'groq');
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        $tableNames = $this->extractTableNames($content);
        
        // CRITICAL: Filter out any tables not in the valid table index
        $filteredTableNames = array_filter($tableNames, function($table) use ($validTableNames) {
            $isValid = in_array($table, $validTableNames);
            if (!$isValid) {
                Log::warning('[AI Table Identification - Invalid Table Filtered]', [
                    'invalid_table' => $table,
                    'valid_tables' => $validTableNames,
                    'reason' => 'table_not_in_index'
                ]);
            }
            return $isValid;
        });
        
        Log::info('[AI Table Identification Success]', [
            'question' => $question,
            'identified_tables' => $tableNames,
            'filtered_tables' => $filteredTableNames,
            'invalid_tables_filtered' => array_diff($tableNames, $filteredTableNames),
            'validation_applied' => true
        ]);
        
        return array_values($filteredTableNames);
    }

    /**
     * Identify tables in a specific chunk
     * @param string $question
     * @param array $chunk
     * @param array $context
     * @param array $validTableNames
     * @return array
     */
    private function identifyTablesInChunk($question, $chunk, $context, $validTableNames)
    {
        $prompt = "You are a database expert for a SACCO Management System. Given a question, identify which database tables are relevant to answer it.\n\n";
        $prompt .= "CRITICAL RESTRICTION: You can ONLY use tables from the following list. DO NOT reference any other tables:\n";
        $prompt .= "VALID TABLES: " . implode(', ', array_keys($chunk)) . "\n\n";
        
        $prompt .= "TABLE INDEX (PARTIAL):\n";
        foreach ($chunk as $table => $info) {
            $prompt .= "Table: {$table}\n";
            $prompt .= "Description: {$info['description']}\n";
            $prompt .= "Keywords: " . implode(', ', $info['keywords']) . "\n";
            $prompt .= "Fields: " . implode(', ', $info['fields']) . "\n";
            $prompt .= "Sample Queries: " . implode(', ', $info['sample_queries']) . "\n";
            $prompt .= "Data Patterns: {$info['data_patterns']}\n";
            $prompt .= "---\n";
        }
        
        $prompt .= "\nSTRICT INSTRUCTIONS:\n";
        $prompt .= "1. You MUST ONLY use tables from the VALID TABLES list above\n";
        $prompt .= "2. Analyze the question carefully and match it against keywords, sample queries, and data patterns\n";
        $prompt .= "3. Return ONLY table names that are relevant to answer the question\n";
        $prompt .= "4. If the question is NOT about data (e.g., greetings, general questions), return empty array\n";
        $prompt .= "5. Return as JSON array of strings\n";
        $prompt .= "6. IMPORTANT: 'members' refers to 'clients' table\n";
        $prompt .= "7. IMPORTANT: If you cannot find a relevant table in the VALID TABLES list, return empty array\n\n";
        $prompt .= "8. IMPORTANT: 'members' refers to 'clients' table\n";
        
        
        $prompt .= "QUESTION: {$question}\n\n";
        $prompt .= "Return relevant table names as JSON array (ONLY from VALID TABLES list):";

        $response = $this->executeWithFallback($prompt, $context, 'groq');
        $content = $response['choices'][0]['message']['content'] ?? '';
        
        $tableNames = $this->extractTableNames($content);
        
        // Filter to only valid tables from this chunk
        $filteredTableNames = array_filter($tableNames, function($table) use ($validTableNames) {
            return in_array($table, $validTableNames);
        });
        
        return array_values($filteredTableNames);
    }

    /**
     * Split table index into chunks for processing
     * @param array $tableIndex
     * @return array
     */
    private function chunkTableIndex($tableIndex)
    {
        $tables = array_keys($tableIndex);
        $totalTables = count($tables);
        
        // Create 4 chunks for balanced context distribution
        $chunks = [];
        $tablesPerChunk = ceil($totalTables / 4);
        
        for ($i = 0; $i < 4; $i++) {
            $startIndex = $i * $tablesPerChunk;
            $endIndex = min($startIndex + $tablesPerChunk, $totalTables);
            
            if ($startIndex < $totalTables) {
                $chunkTables = array_slice($tables, $startIndex, $endIndex - $startIndex);
                $chunkData = [];
                
                foreach ($chunkTables as $tableName) {
                    $chunkData[$tableName] = $tableIndex[$tableName];
                }
                
                $chunks[] = $chunkData;
            }
        }
        
        Log::info('[AI Table Index Chunking]', [
            'total_tables' => $totalTables,
            'chunks_created' => count($chunks),
            'tables_per_chunk' => array_map('count', $chunks),
            'max_tables_per_chunk' => $tablesPerChunk
        ]);
        
        return $chunks;
    }

    /**
     * Convert table info array to text for semantic chunking
     * @param array $tableInfo
     * @return string
     */
    private function convertTableInfoToText($tableInfo)
    {
        $text = "";
        
        // Add prominent warning about table names at the top
        $tableNames = array_keys($tableInfo);
        $text .= "🚨 CRITICAL TABLE RESTRICTION 🚨\n";
        $text .= "ONLY USE THESE EXACT TABLE NAMES: " . implode(', ', $tableNames) . "\n";
        $text .= "❌ FORBIDDEN: Do NOT invent table names like 'balance_sheet', 'financial_data', etc.\n";
        $text .= "✅ REQUIRED: Use ONLY the table names listed above\n\n";
        
        foreach ($tableInfo as $tableName => $info) {
            $text .= "Table: {$tableName}\n";
            $text .= "Description: {$info['description']}\n";
            $text .= "Keywords: " . implode(', ', $info['keywords']) . "\n";
            $text .= "Sample Queries: " . implode('; ', $info['sample_queries']) . "\n";
            $text .= "Data Patterns: {$info['data_patterns']}\n";
            $text .= "🚨 EXACT FIELD NAMES (USE ONLY THESE): " . implode(', ', $info['fields']) . "\n";
            
            // Add specific warnings for commonly misused tables
            if ($tableName === 'loan_sub_products') {
                $text .= "⚠️ CRITICAL: This table uses 'sub_product_status' NOT 'status'\n";
            }
            if ($tableName === 'clients') {
                $text .= "⚠️ CRITICAL: This table uses 'first_name', 'middle_name', 'last_name' NOT 'name' or 'member_name'\n";
            }
            if ($tableName === 'accounts') {
                $text .= "⚠️ CRITICAL: This table uses 'status' NOT 'status'\n";
            }
            
            $text .= "\n";
        }
        return $text;
    }

    /**
     * Create semantic chunks with intelligent boundaries and overlap
     * @param string $text
     * @return array
     */
    private function createSemanticChunks($text)
    {
        $chunks = [];
        $currentChunk = "";
        $currentLength = 0;
        
        // Split text into sentences/paragraphs
        $segments = $this->splitIntoSegments($text);
        
        foreach ($segments as $segment) {
            $segmentLength = strlen($segment);
            
            // If adding this segment would exceed chunk size, save current chunk
            if ($currentLength + $segmentLength > $this->maxChunkSize && !empty($currentChunk)) {
                $chunks[] = trim($currentChunk);
                $currentChunk = "";
                $currentLength = 0;
            }
            
            $currentChunk .= $segment . "\n";
            $currentLength += $segmentLength;
        }
        
        // Add the last chunk
        if (!empty($currentChunk)) {
            $chunks[] = trim($currentChunk);
        }
        
        // Add overlap between chunks for context continuity
        $chunksWithOverlap = [];
        for ($i = 0; $i < count($chunks); $i++) {
            $chunk = $chunks[$i];
            
            // Add overlap from previous chunk
            if ($i > 0) {
                $prevChunk = $chunks[$i - 1];
                $overlap = substr($prevChunk, -$this->chunkOverlap);
                $chunk = $overlap . "\n" . $chunk;
            }
            
            // Add overlap to next chunk
            if ($i < count($chunks) - 1) {
                $nextChunk = $chunks[$i + 1];
                $overlap = substr($nextChunk, 0, $this->chunkOverlap);
                $chunk = $chunk . "\n" . $overlap;
            }
            
            $chunksWithOverlap[] = $chunk;
        }
        
        return $chunksWithOverlap;
    }

    /**
     * Split text into semantic segments at natural boundaries
     * @param string $text
     * @return array
     */
    private function splitIntoSegments($text)
    {
        $segments = [];
        $currentSegment = "";
        
        // Split by double newlines first (paragraphs)
        $paragraphs = explode("\n\n", $text);
        
        foreach ($paragraphs as $paragraph) {
            if (strlen($currentSegment . $paragraph) <= $this->maxChunkSize) {
                $currentSegment .= $paragraph . "\n\n";
            } else {
                if (!empty($currentSegment)) {
                    $segments[] = trim($currentSegment);
                }
                $currentSegment = $paragraph . "\n\n";
            }
        }
        
        if (!empty($currentSegment)) {
            $segments[] = trim($currentSegment);
        }
        
        return $segments;
    }

    /**
     * Extract table information from a text chunk
     * @param string $chunkText
     * @param array $originalTableInfo
     * @return array
     */
    private function extractTablesFromChunk($chunkText, $originalTableInfo)
    {
        $extractedTables = [];
        
        foreach ($originalTableInfo as $tableName => $info) {
            // Check if this table is mentioned in the chunk
            if (strpos($chunkText, "Table: {$tableName}") !== false) {
                $extractedTables[$tableName] = $info;
            }
        }
        
        return $extractedTables;
    }

    /**
     * Enhanced relevance-based retrieval for RAG
     * @param string $question
     * @param array $chunks
     * @return array
     */
    private function retrieveRelevantChunks($question, $chunks)
    {
        $relevantChunks = [];
        $questionKeywords = $this->extractKeywords($question);
        
        foreach ($chunks as $chunkIndex => $chunk) {
            $relevanceScore = $this->calculateRelevanceScore($questionKeywords, $chunk);
            
            if ($relevanceScore > 0.1) { // Minimum relevance threshold
                $relevantChunks[] = [
                    'chunk_index' => $chunkIndex,
                    'chunk' => $chunk,
                    'relevance_score' => $relevanceScore
                ];
            }
        }
        
        // Sort by relevance score (highest first)
        usort($relevantChunks, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        // Return top relevant chunks
        return array_slice($relevantChunks, 0, 3);
    }

    /**
     * Extract keywords from question for relevance scoring
     * @param string $question
     * @return array
     */
    private function extractKeywords($question)
    {
        // Simple keyword extraction - can be enhanced with NLP
        $stopWords = ['the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'can', 'what', 'when', 'where', 'why', 'how', 'which', 'who', 'whom', 'whose'];
        
        $words = preg_split('/\s+/', strtolower($question));
        $keywords = array_filter($words, function($word) use ($stopWords) {
            return !in_array($word, $stopWords) && strlen($word) > 2;
        });
        
        return array_values($keywords);
    }

    /**
     * Calculate relevance score between question keywords and chunk content
     * @param array $keywords
     * @param string $chunk
     * @return float
     */
    private function calculateRelevanceScore($keywords, $chunk)
    {
        $chunkLower = strtolower($chunk);
        $score = 0;
        
        foreach ($keywords as $keyword) {
            $count = substr_count($chunkLower, $keyword);
            $score += $count * 0.1; // Weight by frequency
        }
        
        // Bonus for exact phrase matches
        foreach ($keywords as $i => $keyword) {
            if ($i < count($keywords) - 1) {
                $phrase = $keyword . ' ' . $keywords[$i + 1];
                $phraseCount = substr_count($chunkLower, $phrase);
                $score += $phraseCount * 0.2; // Higher weight for phrases
            }
        }
        
        return min($score, 1.0); // Normalize to 0-1
    }



    /**
     * Generate final answer from RAG processing results
     * @param string $question
     * @param array $ragResults
     * @param array $context
     * @param array $options
     * @return string
     */
    private function generateRAGFinalAnswer($question, $ragResults, $context, $options)
    {
        // Sort results by relevance score (highest first)
        usort($ragResults, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        // Collect all data and reasoning from relevant chunks
        $allData = [];
        $allReasoning = [];
        $weightedResponses = [];
        
        foreach ($ragResults as $result) {
            $weight = $result['relevance_score'];
            
            // Weight the data by relevance
            if (!empty($result['sql_results'])) {
                foreach ($result['sql_results'] as $data) {
                    $allData[] = array_merge($data, ['relevance_weight' => $weight]);
                }
            }
            
            if (!empty($result['reasoning_steps'])) {
                foreach ($result['reasoning_steps'] as $step) {
                    $allReasoning[] = array_merge($step, ['relevance_weight' => $weight]);
                }
            }
            
            // Weight the responses
            if (!empty($result['response'])) {
                $weightedResponses[] = [
                    'response' => $result['response'],
                    'weight' => $weight,
                    'chunk_index' => $result['chunk_index']
                ];
            }
        }
        
        // If we have weighted responses, combine them intelligently
        if (!empty($weightedResponses)) {
            return $this->combineWeightedResponses($weightedResponses, $question);
        }
        
        // If we have data, use it to generate answer
        if (!empty($allData)) {
            return $this->answerWithData($question, $allData, $context);
        }
        
        // If we have reasoning steps but no data, summarize the reasoning
        if (!empty($allReasoning)) {
            $reasoningSummary = $this->getReasoningSummary($allReasoning, [], [], '');
            return "Based on my analysis across multiple relevant data sources: " . $reasoningSummary;
        }
        
        // Fallback response
        return "I analyzed the available data but couldn't find specific information to answer your question. Please try rephrasing or ask about different aspects of the data.";
    }

    /**
     * Combine weighted responses from multiple chunks
     * @param array $weightedResponses
     * @param string $question
     * @return string
     */
    private function combineWeightedResponses($weightedResponses, $question)
    {
        // Sort by weight (highest first)
        usort($weightedResponses, function($a, $b) {
            return $b['weight'] <=> $a['weight'];
        });
        
        // If highest weight is significantly higher, use that response
        if (count($weightedResponses) > 1) {
            $highestWeight = $weightedResponses[0]['weight'];
            $secondWeight = $weightedResponses[1]['weight'];
            
            if ($highestWeight > $secondWeight * 1.5) { // 50% higher
                return $weightedResponses[0]['response'];
            }
        }
        
        // Otherwise, combine responses intelligently
        $combinedResponse = "Based on my analysis across multiple data sources:\n\n";
        
        foreach ($weightedResponses as $i => $response) {
            if ($i < 3) { // Limit to top 3 responses
                $combinedResponse .= "• " . trim($response['response']) . "\n\n";
            }
        }
        
        return trim($combinedResponse);
    }

    /**
     * Check for common queries that can be answered directly without reasoning loop
     * Uses AI to understand intent and generate appropriate SQL queries dynamically
     * @param string $question
     * @return array|null
     */
    private function checkForDirectAnswer($question)
    {
        Log::info('[AI Direct Answer Check Started]', [
            'question' => $question,
            'question_length' => strlen($question)
        ]);

        // Step 1: Ask AI to count and extract questions
        $extractedQuestions = $this->extractQuestionsFromUserQuery($question);
        
        if (empty($extractedQuestions)) {
            Log::info('[AI Direct Answer - No Questions Extracted]', [
                'question' => $question,
                'reason' => 'extraction_failed'
            ]);
            return null;
        }

        Log::info('[AI Direct Answer - Questions Extracted]', [
            'original_question' => $question,
            'extracted_questions' => $extractedQuestions,
            'question_count' => count($extractedQuestions)
        ]);

        // Step 2: Process each question with table index chunks
        $results = [];
        $allSqlQueries = [];
        $allSqlResults = [];
        $allRelevantTables = [];

        foreach ($extractedQuestions as $index => $subQuestion) {
            Log::info('[AI Processing Sub-Question]', [
                'question_index' => $index + 1,
                'total_questions' => count($extractedQuestions),
                'sub_question' => $subQuestion
            ]);

            $result = $this->processQuestionWithTableChunks($subQuestion);
            
            if ($result) {
                $results[] = [
                    'question' => $subQuestion,
                    'response' => $result['response'],
                    'sql_queries' => $result['sql_queries'],
                    'sql_results' => $result['sql_results'],
                    'relevant_tables' => $result['relevant_tables']
                ];
                
                $allSqlQueries = array_merge($allSqlQueries, $result['sql_queries']);
                $allSqlResults = array_merge($allSqlResults, $result['sql_results']);
                $allRelevantTables = array_merge($allRelevantTables, $result['relevant_tables']);
            } else {
                Log::warning('[AI Sub-Question Processing Failed]', [
                    'sub_question' => $subQuestion,
                    'question_index' => $index + 1
                ]);
                return null; // If any sub-question fails, fall back to reasoning
            }
        }

        if (empty($results)) {
            Log::info('[AI Direct Answer - No Results Generated]', [
                'question' => $question,
                'reason' => 'no_successful_results'
            ]);
            return null;
        }

        // Step 3: Combine all results
        $combinedResponse = $this->combineQuestionResults($results, $question);
        
        $finalResult = [
            'response' => $combinedResponse,
            'original_query' => $question,
            'sql_queries' => $allSqlQueries,
            'sql_results' => $allSqlResults,
            'reasoning_steps' => [],
            'timestamp' => now(),
            'method' => 'systematic_direct_answer',
            'relevant_tables' => array_unique($allRelevantTables)
        ];

        Log::info('[AI Direct Answer Found]', [
            'question' => $question,
            'direct_answer' => $combinedResponse,
            'processing_time_ms' => round((microtime(true) - LARAVEL_START) * 1000, 2)
        ]);

        return $finalResult;
    }

    /**
     * Step 1: Extract questions from user query using AI
     * @param string $userQuestion
     * @return array
     */
    private function extractQuestionsFromUserQuery($userQuestion)
    {
        $prompt = "Analyze this user question and extract all individual questions that need to be answered.

User Question: {$userQuestion}

Instructions:
1. Count how many distinct questions are being asked
2. Extract each question as a separate, complete question
3. CRITICAL: Always preserve the subject/context from the original question in each split question
4. If there's only one question, return it as an array with one element
5. If there are multiple questions, split them appropriately while maintaining context
6. Never create ambiguous questions that lose the original subject matter

Examples:
- 'How many liability accounts and list their names' → ['How many liability accounts?', 'List the names of liability accounts']
- 'What is the balance and count the users' → ['What is the balance?', 'Count the users']
- 'Show accounts, count members, list loans' → ['Show accounts', 'Count members', 'List loans']
- 'How many asset accounts are there?' → ['How many asset accounts are there?']

CRITICAL CONTEXT PRESERVATION RULES:
1. Always include the subject/entity from the original question in each split question
2. Never use vague pronouns like 'their', 'them', 'it' without specifying what they refer to
3. Each split question must be independently understandable without the original context

BAD Examples (lose context):
- 'How many liability accounts and list their names' → ['How many liability accounts?', 'List their names'] ❌
- 'Count asset accounts and show them' → ['Count asset accounts', 'Show them'] ❌

GOOD Examples (preserve context):
- 'How many liability accounts and list their names' → ['How many liability accounts?', 'List liability account names'] ✅
- 'Count asset accounts and show them' → ['Count asset accounts', 'Show asset accounts'] ✅
- 'How many users and their roles' → ['How many users?', 'List user roles'] ✅

Return ONLY a JSON array of questions:
[\"question1\", \"question2\", \"question3\"]

IMPORTANT: Return a flat array, not a nested array. 
CORRECT: [\"How many liability accounts?\", \"List liability account names\"]
WRONG: [[\"How many liability accounts?\", \"List liability account names\"]]

FINAL REMINDER: Each question in your array must be complete and self-contained. Do NOT use pronouns like 'their', 'them', 'it' - always specify the exact subject.

Do not include any explanations or additional text, just the JSON array.";

        try {
            $provider = $this->selectProvider();
            $response = $this->callProvider($provider, $prompt, []);
            
            if ($response && isset($response['choices'][0]['message']['content'])) {
                $content = $response['choices'][0]['message']['content'];
                
                Log::info('[AI Question Extraction Response]', [
                    'user_question' => $userQuestion,
                    'ai_response' => $content
                ]);
                
                // Extract JSON array from response
                if (preg_match('/\[.*\]/s', $content, $matches)) {
                    $jsonStr = $matches[0];
                    $questions = json_decode($jsonStr, true);
                    
                    if (is_array($questions) && !empty($questions)) {
                        // Handle nested arrays (AI might return [["q1", "q2"]] instead of ["q1", "q2"])
                        $flatQuestions = $this->flattenArray($questions);
                        
                        // Filter out empty strings and ensure all elements are strings
                        $flatQuestions = array_filter($flatQuestions, function($q) {
                            return is_string($q) && !empty(trim($q));
                        });
                        
                        if (!empty($flatQuestions)) {
                            Log::info('[AI Question Extraction Success]', [
                                'user_question' => $userQuestion,
                                'extracted_questions' => $flatQuestions,
                                'question_count' => count($flatQuestions),
                                'was_nested' => $questions !== $flatQuestions
                            ]);
                            return array_values($flatQuestions); // Re-index array
                        }
                    }
                }
                
                Log::warning('[AI Question Extraction - Invalid JSON]', [
                    'user_question' => $userQuestion,
                    'content' => $content
                ]);
            }
        } catch (Exception $e) {
            Log::error('[AI Question Extraction Error]', [
                'user_question' => $userQuestion,
                'error' => $e->getMessage()
            ]);
        }
        
        // Fallback: return original question as single question
        return [$userQuestion];
    }

    /**
     * Flatten a nested array into a single-level array
     * @param array $array
     * @return array
     */
    private function flattenArray($array)
    {
        $result = [];
        foreach ($array as $item) {
            if (is_array($item)) {
                $result = array_merge($result, $this->flattenArray($item));
            } else {
                $result[] = $item;
            }
        }
        return $result;
    }

    /**
     * Process a single question with 4-chunk table index approach
     * @param string $question
     * @param array $context
     * @param array $options
     * @return array|null
     */
    private function processQuestionWithTableChunks($question, $context = [], $options = [])
    {
        try {
        // Get table index
            $tableIndex = $this->getCompleteTableIndex();
        $validTableNames = array_keys($tableIndex);
        
            // Chunk the entire table index into 4 parts
        $chunks = $this->chunkTableIndex($tableIndex);
        
            Log::info('[AI Four-Chunk Context Delivery Started]', [
            'question' => $question,
            'total_tables' => count($tableIndex),
            'chunks_created' => count($chunks),
                'tables_per_chunk' => array_map('count', $chunks),
                'context_keys' => array_keys($context),
                'options' => $options
        ]);
        
            // Process with 4-chunk sequential approach (ignore responses until final question)
        return $this->processQuestionWithSequentialChunks($question, $chunks, $validTableNames);
            
        } catch (Exception $e) {
            Log::error('[AI Four-Chunk Table Process Failed]', [
                'question' => $question,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback to old reasoning approach
            return $this->processQuestionWithReasoning($question, $context, $options);
        }
    }





    /**
     * Combine results from multiple questions using AI to generate HTML
     * @param array $results
     * @param string $originalQuestion
     * @return string
     */
    private function combineQuestionResults($results, $originalQuestion)
    {
        if (count($results) === 1) {
            return $results[0]['response'];
        }
        
        // Collect all SQL queries for the footer
        $allSqlQueries = [];
        foreach ($results as $result) {
            if (isset($result['sql_queries'])) {
                $allSqlQueries = array_merge($allSqlQueries, $result['sql_queries']);
            }
        }
        
        // Use AI to generate a natural, streamlined response
        return $this->generateStreamlinedCombinedResponse($results, $originalQuestion, $allSqlQueries);
    }
    
    /**
     * Generate a streamlined, natural combined response using AI
     * @param array $results
     * @param string $originalQuestion
     * @param array $allSqlQueries
     * @return string
     */
    private function generateStreamlinedCombinedResponse($results, $originalQuestion, $allSqlQueries)
    {
        try {
            // Prepare the context for AI
            $contextData = [];
            foreach ($results as $index => $result) {
                $contextData[] = [
                    'question' => $result['question'],
                    'data' => $result['sql_results'][0]['data'] ?? [],
                    'sql_query' => $result['sql_queries'][0] ?? ''
                ];
            }
            
            $prompt = "You are an AI assistant for a SACCO (Savings and Credit Cooperative Organization) Management System.

Original User Question: {$originalQuestion}

The user's question has been broken down into sub-questions and answered. Here's the data:

";
            
            foreach ($contextData as $index => $data) {
                $prompt .= "Sub-question " . ($index + 1) . ": {$data['question']}\n";
                $prompt .= "SQL Query: {$data['sql_query']}\n";
                $prompt .= "Results: " . json_encode($data['data']) . "\n\n";
            }
            
            $prompt .= "TASK: Generate a single, streamlined, natural HTML response that answers the original question comprehensively WITHOUT showing the sub-question breakdown.

CRITICAL REQUIREMENTS:
1. Answer the original question directly and naturally
2. DO NOT show 'Question 1:', 'Question 2:', etc.
3. DO NOT repeat the same information multiple times
4. Present the information in a logical, flowing manner
5. Use professional HTML with Tailwind CSS styling
6. For questions asking 'how many' AND 'list', show the count prominently and then the list
7. Make the response feel like a single, cohesive answer
8. Use appropriate headings, tables, and styling
9. Include the SQL queries used at the bottom in a collapsible section

EXAMPLE APPROACH for 'How many X and list their names':
- Start with the count: 'There are [N] liability accounts in the system.'
- Then show the list naturally: 'Here are all the liability accounts:'
- Present the data in a clean table or list format
- Add SQL queries at the bottom

🚨 CRITICAL: Generate STATIC HTML only (no template syntax). 
❌ FORBIDDEN: Do NOT use any template syntax like:
- {{ variable }} (Vue.js/Blade)
- {% variable %} (Twig)
- v-for (Vue.js)
- ng-repeat (Angular)
- @foreach (Blade)
- Any template loops or variables

✅ REQUIRED: Use actual data values from the results above.
- Replace {{ account.account_name }} with actual account names from the data
- Replace v-for loops with actual HTML table rows containing real data
- Show the actual count numbers, not template variables

IMPORTANT: This should be ready-to-display HTML with real data values, not a template.

Return ONLY the HTML content (no explanations), starting with a div container.";

$prompt .= "\nIMPORTANT: Double-check that every @if has an @endif and every @foreach has an @endforeach!";

            $provider = $this->selectProvider();
            $response = $this->callProvider($provider, $prompt, []);
            
            if ($response && isset($response['choices'][0]['message']['content'])) {
                $htmlContent = $response['choices'][0]['message']['content'];
                
                // Clean up the HTML response
                $htmlContent = preg_replace('/```html\s*/', '', $htmlContent);
                $htmlContent = preg_replace('/```\s*$/', '', $htmlContent);
                $htmlContent = trim($htmlContent);
                
                Log::info('[AI Streamlined Combined Response Generated]', [
                    'original_question' => $originalQuestion,
                    'sub_questions_count' => count($results),
                    'html_length' => strlen($htmlContent),
                    'sql_queries_count' => count($allSqlQueries)
                ]);
                
                return $htmlContent;
            }
            
        } catch (Exception $e) {
            Log::error('[AI Streamlined Combined Response Generation Failed]', [
                'original_question' => $originalQuestion,
                'error' => $e->getMessage()
            ]);
        }
        
        // Fallback to simple combination if AI generation fails
        return $this->generateFallbackCombinedResponse($results, $originalQuestion, $allSqlQueries);
    }
    
    /**
     * Generate a fallback combined response when AI generation fails
     * @param array $results
     * @param string $originalQuestion
     * @param array $allSqlQueries
     * @return string
     */
    private function generateFallbackCombinedResponse($results, $originalQuestion, $allSqlQueries)
    {
        $combinedHtml = '<div class="container mx-auto p-4">';
        $combinedHtml .= '<h1 class="text-3xl font-bold text-blue-600 mb-6">' . htmlspecialchars($originalQuestion) . '</h1>';
        
        // For count + list questions, combine them naturally
        if (count($results) === 2 && 
            (stripos($results[0]['question'], 'how many') !== false || stripos($results[0]['question'], 'count') !== false) &&
            (stripos($results[1]['question'], 'list') !== false || stripos($results[1]['question'], 'names') !== false)) {
            
            // Extract count from first result
            $countData = $results[0]['sql_results'][0]['data'] ?? [];
            $count = 0;
            if (!empty($countData)) {
                $count = $countData[0]->count ?? count($countData);
            }
            
            // Extract list from second result
            $listData = $results[1]['sql_results'][0]['data'] ?? [];
            
            $combinedHtml .= '<div class="bg-white shadow-md rounded-lg p-6 mb-6">';
            $combinedHtml .= '<div class="mb-4">';
            $combinedHtml .= '<p class="text-lg text-gray-800">There are <span class="font-bold text-blue-600">' . $count . '</span> liability accounts in the system.</p>';
            $combinedHtml .= '</div>';
            
            if (!empty($listData)) {
                $combinedHtml .= '<div class="mt-6">';
                $combinedHtml .= '<h3 class="text-xl font-semibold text-gray-800 mb-4">Account Names:</h3>';
                $combinedHtml .= '<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2">';
                foreach ($listData as $item) {
                    $accountName = $item->account_name ?? 'Unknown';
                    $combinedHtml .= '<div class="bg-gray-50 px-3 py-2 rounded border text-sm">' . htmlspecialchars($accountName) . '</div>';
                }
                $combinedHtml .= '</div>';
                $combinedHtml .= '</div>';
            }
            
            $combinedHtml .= '</div>';
        } else {
            // Default fallback for other combinations
            foreach ($results as $index => $result) {
                $combinedHtml .= '<div class="mb-8">';
                $combinedHtml .= '<h2 class="text-2xl font-semibold text-blue-500 mb-4">Question ' . ($index + 1) . ': ' . htmlspecialchars($result['question']) . '</h2>';
                $combinedHtml .= '<div class="ml-4">' . $result['response'] . '</div>';
                $combinedHtml .= '</div>';
            }
        }
        
        // Add SQL queries section
        if (!empty($allSqlQueries)) {
            $combinedHtml .= '<div class="mt-8 border-t pt-6">';
            $combinedHtml .= '<h2 class="text-2xl font-semibold text-blue-500 mb-4">SQL Queries Used</h2>';
            $combinedHtml .= '<div class="bg-gray-100 rounded-lg p-4">';
            $combinedHtml .= '<ul class="list-decimal list-inside space-y-2">';
            foreach ($allSqlQueries as $index => $query) {
                $combinedHtml .= '<li class="font-mono text-sm bg-white p-2 rounded">' . htmlspecialchars($query) . '</li>';
            }
            $combinedHtml .= '</ul>';
            $combinedHtml .= '</div>';
            $combinedHtml .= '</div>';
        }
        
        $combinedHtml .= '</div>';
        
        Log::info('[AI Fallback Combined Response Generated]', [
            'original_question' => $originalQuestion,
            'sub_questions_count' => count($results),
            'html_length' => strlen($combinedHtml),
            'sql_queries_count' => count($allSqlQueries)
        ]);
        
        return $combinedHtml;
    }
    


    /**
     * Format query result by asking AI to generate detailed HTML response
     * @param array $data
     * @param string $question
     * @param array $sqlQueries
     * @return string
     */
    private function formatQueryResult($data, $question, $sqlQueries = [])
    {
        if (empty($data)) {
            return $this->generateHtmlResponse("No data found.", $question, [], $sqlQueries);
        }
        
        // Send the SQL results to AI for detailed HTML formatting
        return $this->generateHtmlResponse($data, $question, $data, $sqlQueries);
    }
    
    /**
     * Generate detailed HTML response using AI
     * @param mixed $data
     * @param string $originalQuestion
     * @param array $sqlResults
     * @param array $sqlQueries
     * @return string
     */
    private function generateHtmlResponse($data, $originalQuestion, $sqlResults, $sqlQueries = [])
    {
        try {
            // Prepare the SQL results summary with clear data formatting
            $resultsText = "";
            if (!empty($sqlResults)) {
                $resultsText = "SQL Results Data (Use this exact data in your HTML):\n";
                $resultsText .= "Total rows returned: " . count($sqlResults) . "\n\n";
                
                foreach ($sqlResults as $index => $row) {
                    $rowArray = (array) $row;
                    $resultsText .= "Row " . ($index + 1) . ":\n";
                    foreach ($rowArray as $column => $value) {
                        $resultsText .= "  {$column}: {$value}\n";
                    }
                    $resultsText .= "\n";
                }
                
                // Also provide a simple list of values for easy reference
                if (count($sqlResults) > 0) {
                    $firstRow = (array) $sqlResults[0];
                    $firstColumn = array_keys($firstRow)[0];
                    $resultsText .= "All {$firstColumn} values:\n";
                    foreach ($sqlResults as $row) {
                        $rowArray = (array) $row;
                        $resultsText .= "- " . $rowArray[$firstColumn] . "\n";
                    }
                }
            }
            
            // Prepare SQL queries summary
            $queriesText = "";
            if (!empty($sqlQueries)) {
                $queriesText = "\nSQL Queries Used:\n";
                foreach ($sqlQueries as $index => $query) {
                    $queriesText .= ($index + 1) . ". " . $query . "\n";
                }
            }
            
            $prompt = "You are ZONA, an AI assistant generating a Laravel Blade template for a SACCOS Management System.

Original Question: {$originalQuestion}

{$resultsText}
{$queriesText}

MANDATORY: Generate ONLY Laravel Blade template syntax. NO other template systems allowed.

AVAILABLE VARIABLES:
- \$results: Array of SQL result records (each record has ->account_name property)
- \$total_count: Total number of records (" . count($sqlResults) . ")
- \$question: The original question
- \$queries: Array of SQL queries used

BLADE TEMPLATE REQUIREMENTS:
1. Use @foreach(\$results as \$record) for loops
2. Use {{ \$record->account_name }} for data output
3. Use @if(\$total_count > 0) for conditionals
4. Use @endforeach and @endif to close blocks

EXAMPLE BLADE TEMPLATE:
<div class=\"container mx-auto p-4\">
    <h1 class=\"text-3xl font-bold mb-4\">Liability Accounts</h1>
    
    <div class=\"bg-white shadow-md rounded p-4\">
        <h2 class=\"text-2xl font-bold mb-2\">Count</h2>
        <p class=\"text-lg\">There are <strong>{{ \$total_count }}</strong> liability accounts.</p>
    </div>
    
    <div class=\"bg-white shadow-md rounded p-4 mt-4\">
        <h2 class=\"text-2xl font-bold mb-2\">Account Names</h2>
        @if(\$total_count > 0)
            <table class=\"w-full bg-white border-collapse\">
                <thead>
                    <tr>
                        <th class=\"bg-gray-100 text-lg font-bold p-4\">Account Name</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(\$results as \$record)
                        <tr>
                            <td class=\"text-lg p-4\">{{ \$record->account_name }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p>No accounts found.</p>
        @endif
    </div>
    
    <div class=\"bg-white shadow-md rounded p-4 mt-4\">
        <h2 class=\"text-2xl font-bold mb-2\">SQL Queries Used</h2>
        @if(count(\$queries) > 0)
            <pre class=\"bg-gray-100 p-4\">
@foreach(\$queries as \$query){{ \$query }}
@endforeach
            </pre>
        @endif
    </div>
</div>

STRICTLY FORBIDDEN:
- v-for (Vue.js)
- ng-repeat (Angular)
- {{ account.name }} (incorrect variable access)
- <script> tags
- JavaScript arrays
- Any non-Blade syntax

CRITICAL: Replace the example above with proper Blade template for the given question and data.

Generate the complete Blade template now:";

            $provider = $this->selectProvider();
            $response = $this->callProvider($provider, $prompt, []);
            
            if ($response && isset($response['choices'][0]['message']['content'])) {
                $bladeContent = $response['choices'][0]['message']['content'];
                
                // Clean up the response (remove any markdown code blocks if present)
                $bladeContent = preg_replace('/```blade\s*/', '', $bladeContent);
                $bladeContent = preg_replace('/```php\s*/', '', $bladeContent);
                $bladeContent = preg_replace('/```html\s*/', '', $bladeContent);
                $bladeContent = preg_replace('/```\s*$/', '', $bladeContent);
                
                // Remove common explanatory phrases that AI might include
                $bladeContent = preg_replace('/^.*?Here is the.*?template.*?:\s*/is', '', $bladeContent);
                $bladeContent = preg_replace('/^.*?Here is the.*?Blade.*?:\s*/is', '', $bladeContent);
                $bladeContent = preg_replace('/^.*?complete Blade template.*?:\s*/is', '', $bladeContent);
                $bladeContent = preg_replace('/^.*?generated Blade template.*?:\s*/is', '', $bladeContent);
                
                $bladeContent = trim($bladeContent);
                
                // Now render the Blade template with actual data
                return $this->renderBladeTemplate($bladeContent, $originalQuestion, $sqlResults, $sqlQueries);
            }
            
        } catch (Exception $e) {
            Log::error('[AI HTML Response Generation Failed]', [
                'question' => $originalQuestion,
                'error' => $e->getMessage()
            ]);
        }
        
        // Fallback to simple HTML if AI generation fails
        return $this->generateFallbackHtmlResponse($data, $originalQuestion, $sqlQueries);
    }
    
    /**
     * Render a Blade template with actual data
     * @param string $bladeContent
     * @param string $originalQuestion
     * @param array $sqlResults
     * @param array $sqlQueries
     * @param int $attempt
     * @return string
     */
    private function renderBladeTemplate($bladeContent, $originalQuestion, $sqlResults, $sqlQueries, $attempt = 1)
    {
        $maxAttempts = 3;
        
        try {
            // Prepare data for Blade template
            // Keep data as objects (stdClass) as returned by PostgreSQL
            // This allows using $result->column_name syntax in Blade templates
            $templateData = [
                'question' => $originalQuestion,
                'results' => $sqlResults,  // Keep as objects (stdClass)
                'queries' => $sqlQueries,
                'total_count' => count($sqlResults)
            ];
            
            // Create temporary Blade file
            $tempFileName = 'ai_response_' . uniqid() . '.blade.php';
            $tempPath = resource_path('views/temp/' . $tempFileName);
            
            // Ensure temp directory exists
            $tempDir = dirname($tempPath);
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }
            
            // Validate Blade template for common issues
            $this->validateBladeTemplate($bladeContent, $sqlResults);
            
            // Write Blade template to file
            file_put_contents($tempPath, $bladeContent);
            
            // Verify file was created
            if (!file_exists($tempPath)) {
                throw new Exception("Failed to create temporary Blade file: {$tempPath}");
            }
            
            // Render the Blade template
            // Remove .blade.php extension to get just the base name
            $baseName = str_replace('.blade.php', '', $tempFileName);
            $viewName = 'temp.' . $baseName;
            
            Log::info('[AI Blade Template Rendering Attempt]', [
                'question' => $originalQuestion,
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
                'template_file' => $tempFileName,
                'base_name' => $baseName,
                'view_name' => $viewName,
                'temp_path' => $tempPath,
                'file_exists' => file_exists($tempPath),
                'template_data_keys' => array_keys($templateData),
                'blade_content_length' => strlen($bladeContent),
                'data_type' => !empty($sqlResults) ? gettype($sqlResults[0]) : 'empty',
                'sample_data_structure' => !empty($sqlResults) ? get_object_vars($sqlResults[0]) : 'no_data'
            ]);
            
            $renderedHtml = view($viewName, $templateData)->render();
            
            // Clean up temporary file
            if (file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            Log::info('[AI Blade Template Rendered Successfully]', [
                'question' => $originalQuestion,
                'attempt' => $attempt,
                'template_file' => $tempFileName,
                'rendered_html_length' => strlen($renderedHtml),
                'data_count' => count($sqlResults)
            ]);
            
            return $renderedHtml;
            
        } catch (Exception $e) {
            Log::error('[AI Blade Template Rendering Failed]', [
                'question' => $originalQuestion,
                'attempt' => $attempt,
                'max_attempts' => $maxAttempts,
                'error' => $e->getMessage(),
                'blade_content_preview' => substr($bladeContent, 0, 200),
                'data_type' => !empty($sqlResults) ? gettype($sqlResults[0]) : 'empty',
                'sample_data_structure' => !empty($sqlResults) ? get_object_vars($sqlResults[0]) : 'no_data',
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile()
            ]);
            
            // Clean up temporary file if it exists
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }
            
            // If we haven't reached max attempts, ask AI to fix the template
            if ($attempt < $maxAttempts) {
                Log::info('[AI Blade Template Retry Initiated]', [
                    'question' => $originalQuestion,
                    'attempt' => $attempt,
                    'next_attempt' => $attempt + 1,
                    'error_for_ai' => $e->getMessage()
                ]);
                
                // Ask AI to fix the template
                $fixedTemplate = $this->askAiToFixBladeTemplate($bladeContent, $e->getMessage(), $originalQuestion, $sqlResults, $sqlQueries, $attempt);
                
                if ($fixedTemplate) {
                    // Recursively try with the fixed template
                    return $this->renderBladeTemplate($fixedTemplate, $originalQuestion, $sqlResults, $sqlQueries, $attempt + 1);
                } else {
                    Log::warning('[AI Blade Template Fix Failed]', [
                        'question' => $originalQuestion,
                        'attempt' => $attempt,
                        'reason' => 'ai_could_not_generate_fix'
                    ]);
                }
            }
            
            // If max attempts reached or AI couldn't fix, fall back to simple HTML
            Log::warning('[AI Blade Template Max Attempts Reached]', [
                'question' => $originalQuestion,
                'total_attempts' => $attempt,
                'max_attempts' => $maxAttempts,
                'falling_back' => true
            ]);
            
            return $this->generateFallbackHtmlResponse($sqlResults, $originalQuestion, $sqlQueries);
        }
    }
    

    
    /**
     * Generate Blade template using AI
     * @param string $originalQuestion
     * @param array $sqlResults
     * @param array $sqlQueries
     * @return string|null
     */
    private function generateBladeTemplate($originalQuestion, $sqlResults, $sqlQueries)
    {
        try {
            // Prepare data context for AI
            $dataContext = "";
            if (!empty($sqlResults)) {
                            $dataContext = "Available Data Variables:\n";
            $dataContext .= "- \$question: '{$originalQuestion}'\n";
            $dataContext .= "- \$results: Array of " . count($sqlResults) . " records (each record is a stdClass object)\n";
            $dataContext .= "- \$total_count: " . count($sqlResults) . "\n";
            $dataContext .= "- \$queries: Array of SQL queries\n\n";
            
            $dataContext .= "CRITICAL: Data Access Pattern:\n";
            $dataContext .= "Each \$record in \$results is a stdClass object, so use: \$record->column_name\n";
            $dataContext .= "DO NOT use: \$record['column_name'] (this will cause errors)\n\n";
            
            $dataContext .= "Sample Data Structure:\n";
            if (count($sqlResults) > 0) {
                $firstRow = $sqlResults[0];
                $dataContext .= "Each record in \$results contains:\n";
                foreach (get_object_vars($firstRow) as $column => $value) {
                    $dataContext .= "  ->{$column} => '{$value}'\n";
                }
                $dataContext .= "\nCorrect Blade syntax: {{ \$record->{$column} }}\n";
                
                // Special handling for COUNT queries
                if (isset($firstRow->count)) {
                    $dataContext .= "\n🚨 CRITICAL COUNT QUERY DETECTED 🚨\n";
                    $dataContext .= "This is a COUNT query result. The 'count' field contains the actual count value ({$firstRow->count}).\n";
                    $dataContext .= "MANDATORY: Use \$results[0]->count ({$firstRow->count}) for displaying the count.\n";
                    $dataContext .= "DO NOT use \$total_count (1) as it shows number of result rows, not the count value.\n";
                    $dataContext .= "CORRECT: There are {{ \$results[0]->count }} records.\n";
                    $dataContext .= "WRONG: There are {{ \$total_count }} records.\n";
                }
            }
            }
            
            $prompt = "Generate a Laravel Blade template for displaying SACCO financial data.

Question: {$originalQuestion}

{$dataContext}

Generate a complete Blade template using Laravel syntax with Tailwind CSS classes.

REQUIREMENTS:
1. Use proper Laravel Blade syntax: @foreach, @if, @endforeach, etc.
2. Use {{ \$variable }} for outputting data (this is correct in Blade)
3. Use Tailwind CSS classes for styling
4. Make it professional with financial system colors (blues, greens)
5. Structure with proper headings, tables, cards as appropriate
6. Include SQL queries in a collapsible section at the bottom
7. Handle empty data gracefully with @if/@empty directives

EXAMPLE BLADE SYNTAX:
@if(\$total_count > 0)
    <h2 class=\"text-2xl font-bold mb-4\">Found {{ \$total_count }} records</h2>
    <table class=\"table-auto w-full\">
        <thead>
            <tr>
                <th class=\"px-4 py-2\">Account Name</th>
            </tr>
        </thead>
        <tbody>
            @foreach(\$results as \$record)
                <tr>
                    <td class=\"px-4 py-2\">{{ \$record->account_name }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
@else
    <p class=\"text-gray-600\">No data found.</p>
@endif

🚨 CRITICAL: SPECIAL CASE - COUNT QUERIES 🚨
If the data contains a 'count' field, YOU MUST display the actual count value:
✅ CORRECT: <p class=\"text-lg\">There are <strong>{{ \$results[0]->count }}</strong> records.</p>
❌ WRONG: <p class=\"text-lg\">There are <strong>{{ \$total_count }}</strong> records.</p>
The \$total_count shows number of result rows (1), not the count value (39).
ALWAYS use \$results[0]->count for COUNT queries.

CRITICAL SYNTAX RULES:
1. Use @foreach(\$results as \$record) - NOT @foreach(\$results as \$record in \$results)
2. Use {{ \$record->column_name }} - NOT {{ \$record['column_name'] }}
3. Always close with @endforeach
4. Use proper object property access syntax for stdClass objects

CRITICAL: Return ONLY the Blade template code with NO explanations, descriptions, or additional text.
Do NOT include phrases like 'Here is the complete Blade template' or any other explanatory text.
Start directly with the <div> container and end with the closing </div>.
The response should be ready to render immediately without any cleanup.";

            $provider = $this->selectProvider();
            $response = $this->callProvider($provider, $prompt, []);
            
            if ($response && isset($response['choices'][0]['message']['content'])) {
                $bladeContent = $response['choices'][0]['message']['content'];
                
                // Clean up the response (remove any markdown code blocks if present)
                $bladeContent = preg_replace('/```blade\s*/', '', $bladeContent);
                $bladeContent = preg_replace('/```php\s*/', '', $bladeContent);
                $bladeContent = preg_replace('/```\s*$/', '', $bladeContent);
                
                // Remove explanatory text that AI might add
                $bladeContent = preg_replace('/^Here is the.*?template.*?:?\s*/i', '', $bladeContent);
                $bladeContent = preg_replace('/^This is the.*?template.*?:?\s*/i', '', $bladeContent);
                $bladeContent = preg_replace('/^The following.*?template.*?:?\s*/i', '', $bladeContent);
                $bladeContent = preg_replace('/^Below is the.*?template.*?:?\s*/i', '', $bladeContent);
                $bladeContent = preg_replace('/Note that I have used.*?$/i', '', $bladeContent);
                $bladeContent = preg_replace('/```[^`]*Note that.*?$/ims', '```', $bladeContent);
                $bladeContent = preg_replace('/\s*Note that I have.*?data\.\s*/i', '', $bladeContent);
                
                $bladeContent = trim($bladeContent);
                
                // Post-process COUNT queries to ensure correct syntax
                if (!empty($sqlResults) && isset($sqlResults[0]->count)) {
                    // Replace incorrect $total_count usage with $results[0]->count for COUNT queries
                    $bladeContent = preg_replace(
                        '/\{\{\s*\$total_count\s*\}\}/',
                        '{{ $results[0]->count }}',
                        $bladeContent
                    );
                    
                    // Also fix any hardcoded incorrect count values
                    $bladeContent = preg_replace(
                        '/There are <strong>1<\/strong>/',
                        "There are <strong>{{ \$results[0]->count }}</strong>",
                        $bladeContent
                    );
                    
                    Log::info('[AI COUNT Query Display Fixed in Generate Method]', [
                        'actual_count' => $sqlResults[0]->count,
                        'template_length' => strlen($bladeContent)
                    ]);
                }
                
                // Convert array syntax to object syntax for stdClass objects
                $bladeContent = preg_replace(
                    '/\$record\s*\[\s*[\'"]([^\'\"]+)[\'"]\s*\]/',
                    '$record->$1',
                    $bladeContent
                );
                
                // Also handle dynamic property access
                $bladeContent = preg_replace(
                    '/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*[\'"]([^\'\"]+)[\'"]\s*\]/',
                    '$$$1->$2',
                    $bladeContent
                );
                
                Log::info('[AI Array Syntax Converted to Object Syntax]', [
                    'template_length' => strlen($bladeContent)
                ]);
                
                Log::info('[AI Blade Template Generated]', [
                    'question' => $originalQuestion,
                    'template_length' => strlen($bladeContent)
                ]);
                
                return $bladeContent;
            }
            
        } catch (Exception $e) {
            Log::error('[AI Blade Template Generation Failed]', [
                'question' => $originalQuestion,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }
    
    /**
     * Ask AI to fix a Blade template based on the error message
     * @param string $failedTemplate
     * @param string $errorMessage
     * @param string $originalQuestion
     * @param array $sqlResults
     * @param array $sqlQueries
     * @param int $attempt
     * @return string|null
     */
    private function askAiToFixBladeTemplate($failedTemplate, $errorMessage, $originalQuestion, $sqlResults, $sqlQueries, $attempt)
    {
        try {
            Log::info('[AI Blade Template Fix Request]', [
                'question' => $originalQuestion,
                'attempt' => $attempt,
                'error_message' => $errorMessage,
                'failed_template_length' => strlen($failedTemplate)
            ]);
            
            // Prepare data context for AI
            $dataContext = "";
            if (!empty($sqlResults)) {
                $dataContext = "Available Data Variables:\n";
                $dataContext .= "- \$question: '{$originalQuestion}'\n";
                $dataContext .= "- \$results: Array of " . count($sqlResults) . " records (each record is a stdClass object)\n";
                $dataContext .= "- \$total_count: " . count($sqlResults) . "\n";
                $dataContext .= "- \$queries: Array of SQL queries\n\n";
                
                $dataContext .= "CRITICAL: Data Access Pattern:\n";
                $dataContext .= "Each \$record in \$results is a stdClass object, so use: \$record->column_name\n";
                $dataContext .= "DO NOT use: \$record['column_name'] (this will cause errors)\n\n";
                
                $dataContext .= "Sample Data Structure:\n";
                if (count($sqlResults) > 0) {
                    $firstRow = $sqlResults[0];
                    $dataContext .= "Each record in \$results contains:\n";
                    foreach (get_object_vars($firstRow) as $column => $value) {
                        $dataContext .= "  ->{$column} => '{$value}'\n";
                    }
                    $dataContext .= "\nCorrect Blade syntax: {{ \$record->{$column} }}\n";
                }
            }
            
            $prompt = "You are a Laravel Blade template expert. I need you to fix a Blade template that is causing an error.

ORIGINAL QUESTION: {$originalQuestion}

{$dataContext}

FAILED TEMPLATE:
{$failedTemplate}

ERROR MESSAGE:
{$errorMessage}

INSTRUCTIONS:
1. Analyze the error message carefully
2. Identify the specific issue in the template
3. Fix the template while maintaining the same visual structure and styling
4. Ensure you use the correct data access syntax: \$record->column_name for stdClass objects
5. Use proper Laravel Blade syntax: @foreach, @if, @endforeach, etc.
6. Use Tailwind CSS classes for styling
7. Make sure all @foreach loops have matching @endforeach
8. Handle empty data gracefully with @if/@empty directives

COMMON FIXES:
- If error mentions 'property on array': Change \$record['column'] to \$record->column
- If error mentions 'undefined property': Check column names match the data structure
- If error mentions 'invalid foreach': Use @foreach(\$results as \$record) syntax
- If error mentions 'undefined variable': Check variable names match the template data

CRITICAL: Return ONLY the corrected Blade template code with NO explanations, descriptions, or additional text.
Do NOT include phrases like 'Here is the corrected template' or any other explanatory text.
Start directly with the <div> container and end with the closing </div>.
The response should be ready to render immediately without any cleanup.";

            $provider = $this->selectProvider();
            $response = $this->callProvider($provider, $prompt, []);
            
            if ($response && isset($response['choices'][0]['message']['content'])) {
                $fixedTemplate = $response['choices'][0]['message']['content'];
                
                // Clean up the response (remove any markdown code blocks if present)
                $fixedTemplate = preg_replace('/```blade\s*/', '', $fixedTemplate);
                $fixedTemplate = preg_replace('/```php\s*/', '', $fixedTemplate);
                $fixedTemplate = preg_replace('/```html\s*/', '', $fixedTemplate);
                $fixedTemplate = preg_replace('/```\s*$/', '', $fixedTemplate);
                
                // Remove common explanatory phrases that AI might include
                $fixedTemplate = preg_replace('/^.*?Here is the.*?template.*?:\s*/is', '', $fixedTemplate);
                $fixedTemplate = preg_replace('/^.*?Here is the.*?Blade.*?:\s*/is', '', $fixedTemplate);
                $fixedTemplate = preg_replace('/^.*?corrected.*?template.*?:\s*/is', '', $fixedTemplate);
                $fixedTemplate = preg_replace('/^.*?fixed.*?template.*?:\s*/is', '', $fixedTemplate);
                
                $fixedTemplate = trim($fixedTemplate);
                
                // Post-process COUNT queries to ensure correct syntax
                if (!empty($sqlResults) && isset($sqlResults[0]->count)) {
                    // Replace incorrect $total_count usage with $results[0]->count for COUNT queries
                    $fixedTemplate = preg_replace(
                        '/\{\{\s*\$total_count\s*\}\}/',
                        '{{ $results[0]->count }}',
                        $fixedTemplate
                    );
                    
                    // Also fix any hardcoded incorrect count values
                    $fixedTemplate = preg_replace(
                        '/There are <strong>1<\/strong>/',
                        "There are <strong>{{ \$results[0]->count }}</strong>",
                        $fixedTemplate
                    );
                    
                    Log::info('[AI COUNT Query Display Fixed in Fix Method]', [
                        'actual_count' => $sqlResults[0]->count,
                        'template_length' => strlen($fixedTemplate)
                    ]);
                }
                
                // Convert array syntax to object syntax for stdClass objects
                $fixedTemplate = preg_replace(
                    '/\$record\s*\[\s*[\'"]([^\'\"]+)[\'"]\s*\]/',
                    '$record->$1',
                    $fixedTemplate
                );
                
                // Also handle dynamic property access
                $fixedTemplate = preg_replace(
                    '/\$([a-zA-Z_][a-zA-Z0-9_]*)\s*\[\s*[\'"]([^\'\"]+)[\'"]\s*\]/',
                    '$$$1->$2',
                    $fixedTemplate
                );
                
                Log::info('[AI Array Syntax Converted to Object Syntax in Fix Method]', [
                    'template_length' => strlen($fixedTemplate)
                ]);
                
                Log::info('[AI Blade Template Fix Generated]', [
                    'question' => $originalQuestion,
                    'attempt' => $attempt,
                    'fixed_template_length' => strlen($fixedTemplate),
                    'changes_made' => strlen($fixedTemplate) !== strlen($failedTemplate) || $fixedTemplate !== $failedTemplate
                ]);
                
                return $fixedTemplate;
            }
            
        } catch (Exception $e) {
            Log::error('[AI Blade Template Fix Generation Failed]', [
                'question' => $originalQuestion,
                'attempt' => $attempt,
                'error' => $e->getMessage()
            ]);
        }
        
        return null;
    }

    /**
     * Validate Blade template for common syntax issues
     * @param string $bladeContent
     * @param array $sqlResults
     * @throws Exception
     */
    private function validateBladeTemplate($bladeContent, $sqlResults)
    {
        // Check for array access syntax when we have objects
        if (!empty($sqlResults) && is_object($sqlResults[0])) {
            if (preg_match('/\$\w+\[\'?\w+\'?\]/', $bladeContent)) {
                Log::warning('[AI Blade Template Validation - Array Syntax Detected]', [
                    'issue' => 'template_uses_array_syntax_with_objects',
                    'suggestion' => 'Use $record->column_name instead of $record[\'column_name\']',
                    'detected_pattern' => 'Array syntax found in template'
                ]);
                // Don't throw exception anymore since we have post-processing to fix this
                Log::info('[AI Blade Template Validation - Array Syntax Will Be Auto-Fixed]', [
                    'auto_fix' => 'Post-processing will convert array syntax to object syntax'
                ]);
            }
        }
        
        // Check for object access syntax when we have arrays
        if (!empty($sqlResults) && is_array($sqlResults[0])) {
            if (preg_match('/\$\w+->\w+/', $bladeContent)) {
                Log::warning('[AI Blade Template Validation - Object Syntax Detected]', [
                    'issue' => 'template_uses_object_syntax_with_arrays',
                    'suggestion' => 'Use $record[\'column_name\'] instead of $record->column_name'
                ]);
                throw new Exception('Template uses object syntax but data is arrays. Use $record[\'column_name\'] syntax.');
            }
        }
        
        // Check for proper @foreach syntax
        if (!preg_match('/@foreach\s*\(\s*\$results\s+as\s+\$\w+\s*\)/', $bladeContent)) {
            if (strpos($bladeContent, '@foreach') !== false) {
                Log::warning('[AI Blade Template Validation - Invalid @foreach Syntax]', [
                    'issue' => 'invalid_foreach_syntax',
                    'suggestion' => 'Use @foreach($results as $record) syntax'
                ]);
                throw new Exception('Invalid @foreach syntax. Use @foreach($results as $record) format.');
            }
        }
        
        // Check for missing @endforeach
        $foreachCount = substr_count($bladeContent, '@foreach');
        $endforeachCount = substr_count($bladeContent, '@endforeach');
        if ($foreachCount !== $endforeachCount) {
            Log::warning('[AI Blade Template Validation - Mismatched @foreach/@endforeach]', [
                'issue' => 'mismatched_foreach_endforeach',
                'foreach_count' => $foreachCount,
                'endforeach_count' => $endforeachCount
            ]);
            throw new Exception('Mismatched @foreach and @endforeach directives.');
        }
        
        Log::debug('[AI Blade Template Validation Passed]', [
            'template_length' => strlen($bladeContent),
            'data_type' => !empty($sqlResults) ? gettype($sqlResults[0]) : 'empty'
        ]);
    }

    /**
     * Generate fallback HTML response if AI generation fails
     * @param mixed $data
     * @param string $question
     * @param array $sqlQueries
     * @return string
     */
    private function generateFallbackHtmlResponse($data, $question, $sqlQueries = [])
    {

        Log::error('[AI HTML Response Generation Failed] OOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOOO');
        
        $html = '<div class="bg-white p-6 rounded-lg shadow-lg">';
        $html .= '<h2 class="text-2xl font-bold text-gray-800 mb-4">Query Results</h2>';
        
        if (empty($data)) {
            $html .= '<p class="text-gray-600">No data found.</p>';
        } else {
            $html .= '<div class="overflow-x-auto">';
            $html .= '<table class="min-w-full bg-white border border-gray-200">';
            $html .= '<thead class="bg-gray-50">';
            
            // Table headers
            if (!empty($data)) {
                $firstRow = (array) $data[0];
                $html .= '<tr>';
                foreach (array_keys($firstRow) as $header) {
                    $html .= '<th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">' . htmlspecialchars($header) . '</th>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</thead><tbody class="bg-white divide-y divide-gray-200">';
            
            // Table rows
            foreach ($data as $row) {
                $rowArray = (array) $row;
                $html .= '<tr>';
                foreach ($rowArray as $value) {
                    $html .= '<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">' . htmlspecialchars($value) . '</td>';
                }
                $html .= '</tr>';
            }
            
            $html .= '</tbody></table>';
            $html .= '</div>';
        }
        
        // Add SQL queries section
        if (!empty($sqlQueries)) {
            $html .= '<div class="mt-6">';
            $html .= '<h3 class="text-lg font-semibold text-gray-800 mb-2">SQL Queries Used:</h3>';
            $html .= '<div class="bg-gray-100 p-4 rounded-lg">';
            foreach ($sqlQueries as $index => $query) {
                $html .= '<pre class="text-sm text-gray-700 mb-2"><code>' . htmlspecialchars($query) . '</code></pre>';
            }
            $html .= '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        
        return $html;
    }

    /**
     * Extract table names from SQL query
     * @param string $sql
     * @param array $validTableNames
     * @return array
     */
    private function extractTablesFromSql($sql, $validTableNames)
    {
        $tables = [];
        foreach ($validTableNames as $tableName) {
            if (preg_match('/\b' . preg_quote($tableName, '/') . '\b/i', $sql)) {
                $tables[] = $tableName;
            }
        }
        return $tables;
    }

    /**
     * Select relevant tables for a specific question
     * @param string $question
     * @param array $tableIndex
     * @return array
     */
    private function selectRelevantTables($question, $tableIndex)
    {
        $questionLower = strtolower($question);
        $relevantTables = [];
        
        // Define question patterns and their relevant tables
        $patterns = [
            // Account-related questions
            'account' => ['accounts'],
            'liability' => ['accounts'],
            'asset' => ['accounts'],
            'equity' => ['accounts'],
            'revenue' => ['accounts'],
            'expense' => ['accounts'],
            'balance' => ['accounts', 'account_historical_balances'],
            'financial' => ['accounts', 'general_ledger', 'transactions'],
            
            // Member/Client questions
            'member' => ['clients', 'accounts'],
            'client' => ['clients', 'accounts'],
            'customer' => ['clients', 'accounts'],
            
            // Loan questions
            'loan' => ['loans', 'loan_sub_products', 'loan_guarantors'],
            'lending' => ['loans', 'loan_sub_products'],
            'borrow' => ['loans', 'loan_sub_products'],
            
            // Transaction questions
            'transaction' => ['transactions', 'general_ledger'],
            'payment' => ['transactions', 'bills'],
            'deposit' => ['transactions', 'accounts'],
            'withdrawal' => ['transactions', 'accounts'],
            
            // User/Staff questions
            'user' => ['users'],
            'staff' => ['employees', 'users'],
            'employee' => ['employees', 'users'],
            
            // Branch questions
            'branch' => ['branches'],
            'office' => ['branches'],
            
            // Institution questions
            'institution' => ['institutions'],
            'organization' => ['institutions'],
            
            // Share questions
            'share' => ['share_registers', 'issued_shares', 'accounts'],
            'dividend' => ['share_registers', 'issued_shares'],
            
            // Savings questions
            'saving' => ['accounts'],
            'deposit' => ['accounts'],
            
            // Count questions (general)
            'how many' => ['accounts', 'clients', 'loans', 'users', 'employees', 'branches'],
            'count' => ['accounts', 'clients', 'loans', 'users', 'employees', 'branches'],
            'total' => ['accounts', 'clients', 'loans', 'users', 'employees', 'branches'],
            
            // List questions (general)
            'list' => ['accounts', 'clients', 'loans', 'users', 'employees', 'branches'],
            'show' => ['accounts', 'clients', 'loans', 'users', 'employees', 'branches'],
            'display' => ['accounts', 'clients', 'loans', 'users', 'employees', 'branches']
        ];
        
        // Check for specific patterns in the question
        foreach ($patterns as $pattern => $tables) {
            if (strpos($questionLower, $pattern) !== false) {
                foreach ($tables as $table) {
                    if (isset($tableIndex[$table])) {
                        $relevantTables[$table] = $tableIndex[$table];
                    }
                }
            }
        }
        
        // If no specific patterns found, include core tables
        if (empty($relevantTables)) {
            $coreTables = ['accounts', 'clients', 'loans', 'users', 'transactions'];
            foreach ($coreTables as $table) {
                if (isset($tableIndex[$table])) {
                    $relevantTables[$table] = $tableIndex[$table];
                }
            }
        }
        
        // Always include accounts table for financial questions
        if (strpos($questionLower, 'account') !== false || 
            strpos($questionLower, 'liability') !== false || 
            strpos($questionLower, 'asset') !== false ||
            strpos($questionLower, 'financial') !== false) {
            if (isset($tableIndex['accounts'])) {
                $relevantTables['accounts'] = $tableIndex['accounts'];
            }
        }
        
        return $relevantTables;
    }

  

    /**
     * Process question with smart context-on-demand approach
     * @param string $question
     * @param array $chunks
     * @param array $validTableNames
     * @return array|null
     */
    private function processQuestionWithSequentialChunks($question, $chunks, $validTableNames)
    {
        try {
            $provider = $this->selectProvider();
            $totalChunks = count($chunks);
            
            // Check if this is the first question in session or if we should send context
            // For first question or if explicitly requested, send full context
            $shouldSendContext = $options['should_send_context'] ?? true;
            
            if ($shouldSendContext) {
                // First question - send full context
                Log::info('[AI Context-On-Demand: First Question]', [
                'question' => $question,
                'total_chunks' => $totalChunks,
                    'provider' => $provider,
                    'approach' => 'first_question_with_full_context'
                ]);
                
                return $this->sendFullContextAndQuestion($question, $chunks, $provider, $totalChunks, $validTableNames);
            } else {
                // Subsequent question - try without context first
                Log::info('[AI Context-On-Demand: Subsequent Question]', [
                    'question' => $question,
                    'provider' => $provider,
                    'approach' => 'question_only_with_context_request_fallback'
                ]);
                
                return $this->tryQuestionWithoutContext($question, $chunks, $provider, $totalChunks, $validTableNames);
            }
            
            if (empty($content)) {
                Log::warning('[AI Four-Chunk Response Empty]', [
                    'question' => $question,
                'provider' => $provider
            ]);
                return null;
            }
            
            Log::info('[AI Four-Chunk Response Received]', [
                'question' => $question,
                'provider' => $provider,
                'response_length' => strlen($content)
            ]);
            
            // Extract SQL queries from the response
            $extractedSql = $this->extractSqlFromContent($content);
            
            if (empty($extractedSql)) {
                Log::warning('[AI Four-Chunk No SQL Extracted]', [
                    'question' => $question,
                    'response_content' => $content
                ]);
                return null;
            }
            
            // Convert single SQL string to array for processing
            $sqlQueries = is_array($extractedSql) ? $extractedSql : [$extractedSql];
            
            Log::info('[AI Four-Chunk SQL Queries Ready]', [
                'question' => $question,
                'queries_count' => count($sqlQueries),
                'queries' => $sqlQueries
            ]);
            
            // Execute the SQL queries and collect results
            $sqlResults = [];
            $allTableNames = [];
            
            foreach ($sqlQueries as $sql) {
                Log::info('[AI Executing Four-Chunk SQL]', [
                    'sql' => $sql,
                    'question' => $question
                ]);
                
                $result = $this->executeSqlQuery($sql);
                
                if ($result['success']) {
                    $sqlResults[] = $result['data'];
                    
                    // Extract table names from SQL for tracking
                    preg_match_all('/\bfrom\s+(\w+)|\bjoin\s+(\w+)/i', $sql, $matches);
                    $tablesInQuery = array_filter(array_merge($matches[1], $matches[2]));
                    $allTableNames = array_merge($allTableNames, $tablesInQuery);
                    
                    Log::info('[AI Four-Chunk SQL Success]', [
                        'sql' => $sql,
                        'result_count' => count($result['data']),
                        'tables_used' => $tablesInQuery
                    ]);
                } else {
                    Log::error('[AI Four-Chunk SQL Failed]', [
                        'sql' => $sql,
                        'error' => $result['error']
                    ]);
                }
            }
            
            if (empty($sqlResults)) {
                Log::warning('[AI Four-Chunk No SQL Results]', [
                    'question' => $question,
                    'queries_attempted' => count($sqlQueries)
                ]);
                return null;
            }
            
            // Generate response using the SQL results
            $finalResponse = $this->generateResponseFromSqlResults($question, $sqlResults, $sqlQueries);
            
            Log::info('[AI Four-Chunk Process Complete]', [
                'question' => $question,
                'sql_queries_count' => count($sqlQueries),
                'sql_results_count' => count($sqlResults),
                'tables_used' => array_unique($allTableNames),
                'method' => 'four_chunk_sequential_ignore_responses'
            ]);
            
            return [
                'response' => $finalResponse,
                'original_query' => $question,
                'sql_queries' => $sqlQueries,
                'sql_results' => $sqlResults,
                'reasoning_steps' => [],
                'timestamp' => now(),
                'method' => 'four_chunk_sequential',
                'relevant_tables' => array_unique($allTableNames),
                'context_sent' => true,
                'chunks_used' => $totalChunks
            ];
            
        } catch (Exception $e) {
            Log::error('[AI Four-Chunk Process Failed]', [
                'question' => $question,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Send full context (4 chunks) and question - used for first questions
     * @param string $question
     * @param array $chunks
     * @param string $provider
     * @param int $totalChunks
     * @param array $validTableNames
     * @return array|null
     */
    private function sendFullContextAndQuestion($question, $chunks, $provider, $totalChunks, $validTableNames)
    {
        // Build conversation messages with all chunks first, then the question
        $messages = [
            [
                'role' => 'system',
                'content' => "You are an AI assistant for a SACCO (Savings and Credit Cooperative Organization) Management System. This is a comprehensive financial services management system for cooperative organizations. Always provide accurate, helpful, and secure responses based on the current system data."
            ]
        ];
        
        // Add all chunks to conversation
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunk = $chunks[$i];
                $chunkNumber = $i + 1;
                
            Log::info('[AI Adding Context Chunk to Conversation]', [
                    'chunk_number' => $chunkNumber,
                    'total_chunks' => $totalChunks,
                    'tables_in_chunk' => count($chunk)
                ]);
                
                $contextText = $this->convertTableInfoToText($chunk);
            
            $messages[] = [
                'role' => 'user',
                'content' => "Database Context (Chunk {$chunkNumber} of {$totalChunks}):\n\n{$contextText}\n\nIMPORTANT: This is context chunk {$chunkNumber} of {$totalChunks}. Do NOT respond yet - more context is coming."
            ];
            
            $messages[] = [
                'role' => 'assistant',
                'content' => "Context chunk {$chunkNumber} received. Waiting for remaining chunks and user question."
            ];
        }
        
        // Add final question
        $messages[] = [
            'role' => 'user',
            'content' => "Now I have provided all {$totalChunks} chunks of database context. Please use this information to answer my question:\n\nQUESTION: \"{$question}\"\n\nINSTRUCTIONS:\n1. Use the database context from all {$totalChunks} chunks above to understand available tables\n2. Generate one or multiple SQL queries or JOIN queries that will help answer this question\n3. Use ONLY the exact table names and field names provided in the context chunks\n4. Return your queries in the format:\n   SQL: [your SQL query]\n5. CRITICAL TABLE NAME MAPPING:\n   - 'members' = 'clients' table (NEVER use 'members' - always use 'clients')\n   - 'customers' = 'clients' table\n   - 'users' = 'users' table (for system users/staff)\n   - 'employees' = 'employees' table (for staff)\n6. 🚨 ABSOLUTE FIELD NAME ENFORCEMENT:\n   - MANDATORY: Before writing ANY SQL, verify EVERY field name against the context above\n   - FORBIDDEN: Using field names from your training data (like 'status', 'name', 'member_name')\n   - REQUIRED: Use ONLY the exact field names listed in \"Fields:\" for each table\n   - CRITICAL EXAMPLE: loan_sub_products table has 'sub_product_status' NOT 'status'\n   - CRITICAL EXAMPLE: clients table has 'first_name', 'middle_name', 'last_name' NOT 'name'\n   - If ANY field name is not explicitly listed in context, respond: CONTEXT-REQUEST-{{TABLE_NAME}}\n7. FIELD VALIDATION CHECKLIST:\n   - ✓ Is this field name in the \"Fields:\" list for this table?\n   - ✓ Am I using the EXACT field name from context (not a similar one)?\n   - ✓ Have I checked ALL field names in my SQL against the context?\n8. CRITICAL: Always use SINGLE QUOTES for string values, never double quotes\n9. PostgreSQL Rule: Use 'value' not \"value\" for strings\n10. ZERO TOLERANCE: Any field name not in context = CONTEXT-REQUEST-{{TABLE_NAME}}\n\nBased on the {$totalChunks} context chunks provided above, generate the appropriate SQL query/queries to answer: \"{$question}\""
        ];
        
        Log::info('[AI Sending Complete Conversation with All Chunks and Question]', [
            'total_messages' => count($messages),
            'chunks_included' => $totalChunks,
            'question' => $question,
            'approach' => 'full_context_first_question'
        ]);
        
        return $this->executeRequestAndProcessSQL($messages, $provider, $question, $validTableNames);
    }

    /**
     * Execute request and process SQL response - shared logic
     * @param array $messages
     * @param string $provider
     * @param string $question
     * @param array $validTableNames
     * @return array|null
     */
    private function executeRequestAndProcessSQL($messages, $provider, $question, $validTableNames)
    {
        // Send request with fallback
        try {
            $response = $this->executeWithFallback($messages, [], $provider);
            $content = trim($response['choices'][0]['message']['content'] ?? '');
            $actualProvider = $response['provider'] ?? $provider;
        } catch (Exception $e) {
            Log::error('[AI Request Failed]', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'question' => $question
            ]);
            return null;
        }

        if (empty($content)) {
            Log::warning('[AI Response Empty]', [
                'question' => $question,
                'provider' => $actualProvider
            ]);
            return null;
        }

        Log::info('[AI Response Received]', [
            'question' => $question,
            'provider' => $actualProvider,
            'response_length' => strlen($content)
        ]);

        return $this->processQuestionResponse($content, $question, $actualProvider, $validTableNames);
    }

    /**
     * Try question without context first - used for subsequent questions
     * @param string $question
     * @param array $chunks
     * @param string $provider
     * @param int $totalChunks
     * @param array $validTableNames
     * @return array|null
     */
    private function tryQuestionWithoutContext($question, $chunks, $provider, $totalChunks, $validTableNames)
    {
        // Try sending just the question with instructions to request context if needed
        $messages = [
                    [
                        'role' => 'system',
                        'content' => "You are an AI assistant for a SACCO (Savings and Credit Cooperative Organization) Management System. This is a comprehensive financial services management system for cooperative organizations. Always provide accurate, helpful, and secure responses based on the current system data."
                    ],
                    [
                        'role' => 'user',
                'content' => "QUESTION: \"{$question}\"\n\nINSTRUCTIONS:\n1. If you remember the database context from our previous conversation, generate SQL queries to answer this question\n2. Use ONLY the exact table names and field names you remember from the previous context\n3. Return your queries in the format: SQL: [your SQL query]\n4. CRITICAL TABLE NAME MAPPING:\n   - 'members' = 'clients' table (NEVER use 'members' - always use 'clients')\n   - 'customers' = 'clients' table\n   - 'users' = 'users' table (for system users/staff)\n   - 'employees' = 'employees' table (for staff)\n5. 🚨 ABSOLUTE FIELD NAME ENFORCEMENT:\n   - MANDATORY: Before writing ANY SQL, verify EVERY field name against previous context\n   - FORBIDDEN: Using field names from your training data (like 'status', 'name', 'member_name')\n   - REQUIRED: Use ONLY the exact field names you remember from previous context\n   - CRITICAL EXAMPLE: loan_sub_products table has 'sub_product_status' NOT 'status'\n   - CRITICAL EXAMPLE: clients table has 'first_name', 'middle_name', 'last_name' NOT 'name'\n   - If unsure about ANY field name, respond: CONTEXT-REQUEST-{{TABLE_NAME}}\n6. FIELD VALIDATION CHECKLIST:\n   - ✓ Is this field name from the previous context (not training data)?\n   - ✓ Am I using the EXACT field name I remember (not a similar one)?\n   - ✓ Have I verified ALL field names against previous context?\n7. CRITICAL: Always use SINGLE QUOTES for string values, never double quotes\n8. PostgreSQL Rule: Use 'value' not \"value\" for strings\n9. ZERO TOLERANCE: Any uncertain field name = CONTEXT-REQUEST-{{TABLE_NAME}}\n\nIMPORTANT: If you do NOT remember the database context or table structures from our previous conversation, respond with ONLY the word \"CONTEXT-REQUEST\" and nothing else.\n\nAnswer the question: \"{$question}\""
            ]
        ];
        
        Log::info('[AI Trying Question Without Context]', [
            'question' => $question,
            'provider' => $provider,
            'approach' => 'question_only_first_attempt'
        ]);
        
        // Send question without context
        try {
            $response = $this->executeWithFallback($messages, [], $provider);
            $content = trim($response['choices'][0]['message']['content'] ?? '');
            $actualProvider = $response['provider'] ?? $provider;
            
            Log::info('[AI Question-Only Response Received]', [
                'question' => $question,
                'provider' => $actualProvider,
                'response_length' => strlen($content),
                'response_preview' => substr($content, 0, 100) . (strlen($content) > 100 ? '...' : '')
            ]);
            
            // Check if AI requested context (general or specific table)
            if (trim($content) === 'CONTEXT-REQUEST') {
                Log::info('[AI Requested General Context - Sending Full Context]', [
                    'question' => $question,
                    'reason' => 'ai_does_not_remember_context'
                ]);
                
                // AI doesn't remember context, send full context
                return $this->sendFullContextAndQuestion($question, $chunks, $actualProvider, $totalChunks, $validTableNames);
            } elseif (preg_match('/^CONTEXT-REQUEST-([a-zA-Z0-9_]+)$/', trim($content), $matches)) {
                $requestedTable = $matches[1];
                Log::info('[AI Requested Specific Table Context]', [
                    'question' => $question,
                    'requested_table' => $requestedTable,
                    'reason' => 'ai_unsure_about_table_structure'
                ]);
                
                // AI is unsure about specific table, send specific table context
                return $this->sendSpecificTableContextAndRetry($question, $requestedTable, $actualProvider, $validTableNames);
            } else {
                Log::info('[AI Answered Without Context]', [
                    'question' => $question,
                    'provider' => $actualProvider,
                    'reason' => 'ai_remembered_context'
                ]);
                
                // AI answered without needing context - process the SQL
                return $this->processQuestionResponse($content, $question, $actualProvider, $validTableNames);
            }
            
        } catch (Exception $e) {
            Log::error('[AI Question-Only Attempt Failed]', [
                'question' => $question,
                'error' => $e->getMessage(),
                'fallback_to_full_context' => true
            ]);
            
            // If question-only fails, send full context
                         return $this->sendFullContextAndQuestion($question, $chunks, $provider, $totalChunks, $validTableNames);
         }
     }

    /**
     * Send specific table context and retry question - used when AI requests specific table info
     * @param string $question
     * @param string $requestedTable
     * @param string $provider
     * @param array $validTableNames
     * @return array|null
     */
    private function sendSpecificTableContextAndRetry($question, $requestedTable, $provider, $validTableNames)
    {
        try {
            // Get table index and check if requested table exists
            $tableIndex = $this->getTableIndex();
            
            if (!isset($tableIndex[$requestedTable])) {
                Log::warning('[AI Requested Invalid Table]', [
                'question' => $question,
                    'requested_table' => $requestedTable,
                    'available_tables' => array_keys($tableIndex)
                ]);
                
                // Table doesn't exist, send error response
                return [
                    'response' => "I apologize, but the table '{$requestedTable}' is not available in our database. Please check the table name and try again.",
                    'original_query' => $question,
                    'error' => "Table '{$requestedTable}' not found",
                    'method' => 'specific_table_context_error'
                ];
            }
            
            // Build specific table context
            $specificTableContext = [$requestedTable => $tableIndex[$requestedTable]];
            $contextText = $this->convertTableInfoToText($specificTableContext);
            
            Log::info('[AI Sending Specific Table Context]', [
                'question' => $question,
                'requested_table' => $requestedTable,
                'table_fields_count' => count($tableIndex[$requestedTable]['fields'] ?? []),
                'provider' => $provider
            ]);
            
            // Build messages with specific table context
            $messages = [
                [
                    'role' => 'system',
                    'content' => "You are an AI assistant for a SACCO (Savings and Credit Cooperative Organization) Management System. This is a comprehensive financial services management system for cooperative organizations. Always provide accurate, helpful, and secure responses based on the current system data."
                ],
                [
                    'role' => 'user',
                    'content' => "You requested specific context for the '{$requestedTable}' table. Here is the detailed information:\n\n{$contextText}\n\nNow please answer the original question using ONLY the field names provided above for the '{$requestedTable}' table.\n\nORIGINAL QUESTION: \"{$question}\"\n\nINSTRUCTIONS:\n1. 🚨 MANDATORY FIELD VERIFICATION: Use ONLY the field names in \"Fields:\" list above\n2. 🚨 FORBIDDEN: Any field names from your training data (like 'status' instead of 'sub_product_status')\n3. Generate SQL queries using these EXACT field names from the context\n4. Return your queries in the format: SQL: [your SQL query]\n5. CRITICAL FIELD EXAMPLES:\n   - For loan_sub_products: Use 'sub_product_status' NOT 'status'\n   - For clients: Use 'first_name', 'middle_name', 'last_name' NOT 'name'\n6. FIELD VALIDATION CHECKLIST:\n   - ✓ Is this field name in the \"Fields:\" list above?\n   - ✓ Am I using the EXACT field name (not a similar one)?\n   - ✓ Have I verified ALL field names against the context?\n7. CRITICAL: Use SINGLE QUOTES for string values, never double quotes\n8. PostgreSQL Rule: Use 'value' not \"value\" for strings\n9. If you need context for other tables, respond with: CONTEXT-REQUEST-{{TABLE_NAME}}\n\nAnswer the question: \"{$question}\""
                ]
            ];
            
            // Send request and process response
            return $this->executeRequestAndProcessSQL($messages, $provider, $question, $validTableNames);
            
        } catch (Exception $e) {
            Log::error('[AI Specific Table Context Failed]', [
                    'question' => $question,
                'requested_table' => $requestedTable,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'response' => "I apologize, but I encountered an error while retrieving information about the '{$requestedTable}' table. Please try your question again.",
                'original_query' => $question,
                'error' => $e->getMessage(),
                'method' => 'specific_table_context_error'
            ];
        }
    }

    /**
     * Process AI response content and extract/execute SQL
     * @param string $content
     * @param string $question
     * @param string $provider
     * @param array $validTableNames
     * @return array|null
     */
    private function processQuestionResponse($content, $question, $provider, $validTableNames)
    {
        // Check if AI requested specific table context
        if (preg_match('/^CONTEXT-REQUEST-([a-zA-Z0-9_]+)$/', trim($content), $matches)) {
            $requestedTable = $matches[1];
            Log::info('[AI Requested Specific Table Context During Processing]', [
                'question' => $question,
                'requested_table' => $requestedTable,
                'reason' => 'ai_unsure_about_table_structure_during_sql_generation'
            ]);
            
            // AI is unsure about specific table, send specific table context
            return $this->sendSpecificTableContextAndRetry($question, $requestedTable, $provider, $validTableNames);
        }
        
        // Extract SQL queries from the response
        $extractedSql = $this->extractSqlFromContent($content);
        
        if (empty($extractedSql)) {
            Log::warning('[AI No SQL Extracted from Response]', [
                    'question' => $question,
                'response_content' => substr($content, 0, 500) . (strlen($content) > 500 ? '...' : '')
                ]);
                return null;
            }
            
        // Convert single SQL string to array for processing
        $sqlQueries = is_array($extractedSql) ? $extractedSql : [$extractedSql];
        
        Log::info('[AI SQL Queries Extracted]', [
            'question' => $question,
            'sql_count' => count($sqlQueries),
            'queries' => $sqlQueries
        ]);
        
        // Execute the SQL queries and collect results
        $sqlResults = [];
        $allTableNames = [];
        
        foreach ($sqlQueries as $sql) {
            $sql = trim($sql);
            if (empty($sql)) continue;
            
            Log::info('[AI Executing SQL Query]', [
                'question' => $question,
                'sql' => $sql
            ]);
            
            $result = $this->executeSqlQuery($sql, $validTableNames);
            
            if ($result['success']) {
                $sqlResults[] = $result['data'];
                // Simple table name extraction from SQL for logging
                preg_match_all('/FROM\s+(\w+)/i', $sql, $matches);
                $tableNames = $matches[1] ?? [];
                $allTableNames = array_merge($allTableNames, $tableNames);
                
                Log::info('[AI SQL Execution Success]', [
                    'sql' => $sql,
                    'result_count' => is_array($result['data']) ? count($result['data']) : 1,
                    'tables_used' => $tableNames
                ]);
            } else {
                Log::error('[AI SQL Execution Failed]', [
                    'sql' => $sql,
                    'error' => $result['error']
                ]);
                
                // Check if this is a field/column error that we can recover from
                $recoveryResult = $this->attemptSqlErrorRecovery($sql, $result['error'], $question, $provider, $validTableNames);
                if ($recoveryResult !== null) {
                    Log::info('[AI SQL Error Recovery Success]', [
                        'original_sql' => $sql,
                        'original_error' => $result['error'],
                        'recovery_method' => 'table_context_resend'
                    ]);
                    return $recoveryResult; // Return the recovered result immediately
                }
            }
        }
        
        if (empty($sqlResults)) {
            Log::warning('[AI No SQL Results]', [
                'question' => $question,
                'queries_attempted' => count($sqlQueries)
                ]);
                return null;
            }
            
        // Generate response using the SQL results
        $finalResponse = $this->generateResponseFromSqlResults($question, $sqlResults, $sqlQueries);
        
        Log::info('[AI Question Processing Complete]', [
                'question' => $question,
            'sql_queries_count' => count($sqlQueries),
            'sql_results_count' => count($sqlResults),
            'tables_used' => array_unique($allTableNames),
            'provider' => $provider
        ]);
        
        return [
            'response' => $finalResponse,
            'original_query' => $question,
            'sql_queries' => $sqlQueries,
            'sql_results' => $sqlResults,
            'reasoning_steps' => [],
            'timestamp' => now(),
            'method' => 'context_on_demand',
            'relevant_tables' => array_unique($allTableNames),
            'context_sent' => true,
            'provider_used' => $provider
        ];
    }

    /**
     * Attempt to recover from SQL execution errors by resending table context
     * @param string $failedSql
     * @param string $error
     * @param string $question
     * @param string $provider
     * @param array $validTableNames
     * @return array|null
     */
    private function attemptSqlErrorRecovery($failedSql, $error, $question, $provider, $validTableNames)
    {
        try {
            // Check if this is a recoverable error type
            if (!$this->isRecoverableSqlError($error)) {
                Log::info('[AI SQL Error Recovery Skipped]', [
                    'sql' => $failedSql,
                    'error' => $error,
                    'reason' => 'not_recoverable_error_type'
                ]);
                return null;
            }
            
            // Extract table names from the failed SQL
            $tablesInSql = $this->extractTableNamesFromFailedSql($failedSql);
            
            if (empty($tablesInSql)) {
                Log::warning('[AI SQL Error Recovery Failed]', [
                    'sql' => $failedSql,
                    'error' => $error,
                    'reason' => 'could_not_extract_table_names'
                ]);
                return null;
            }
            
            Log::info('[AI SQL Error Recovery Attempt]', [
                'sql' => $failedSql,
                'error' => $error,
                'extracted_tables' => $tablesInSql,
                'recovery_method' => 'resend_table_context'
            ]);
            
            // Get table index for the failed tables
            $tableIndex = $this->getTableIndex();
            $recoveryTableContext = [];
            
            foreach ($tablesInSql as $tableName) {
                if (isset($tableIndex[$tableName])) {
                    $recoveryTableContext[$tableName] = $tableIndex[$tableName];
                }
            }
            
            if (empty($recoveryTableContext)) {
                Log::warning('[AI SQL Error Recovery Failed]', [
                    'sql' => $failedSql,
                    'tables' => $tablesInSql,
                    'reason' => 'tables_not_found_in_index'
                ]);
                return null;
            }
            
            // Build recovery context message
            $contextText = $this->convertTableInfoToText($recoveryTableContext);
            $errorDetails = $this->extractErrorDetails($error);
            $limitedError = $this->limitErrorSize($error);
            $limitedSql = $this->limitSqlSize($failedSql);
            
            $recoveryMessages = [
                [
                    'role' => 'system',
                    'content' => "You are an AI assistant for a SACCO Management System. Your previous SQL query failed and I'm providing you with the exact error message and correct table context to help you fix it."
                ],
                [
                    'role' => 'user',
                    'content' => "🚨 YOUR SQL QUERY FAILED - PLEASE FIX IT\n\n" .
                                "📋 ORIGINAL QUESTION: \"{$question}\"\n\n" .
                                "❌ FAILED SQL QUERY:\n{$limitedSql}\n\n" .
                                "🚨 EXACT ERROR MESSAGE:\n{$limitedError}\n\n" .
                                "💡 ERROR ANALYSIS:\n{$errorDetails}\n\n" .
                                "📊 CORRECT TABLE CONTEXT (USE THESE EXACT FIELD NAMES):\n{$contextText}\n\n" .
                                "🔧 RECOVERY INSTRUCTIONS:\n" .
                                "1. 🚨 ANALYZE the error message above to understand what went wrong\n" .
                                "2. 🚨 COMPARE your failed SQL with the correct field names in the table context\n" .
                                "3. 🚨 USE ONLY the field names from \"🚨 EXACT FIELD NAMES\" lists above\n" .
                                "4. 🚨 FORBIDDEN: Do NOT use field names from your training data\n" .
                                "5. 🚨 REPLACE any incorrect field names with the correct ones from context\n" .
                                "6. Return the corrected SQL in this format: SQL: [your corrected query]\n" .
                                "7. Use SINGLE QUOTES for string values\n" .
                                "8. If still unsure about field names, respond: CONTEXT-REQUEST-{$tablesInSql[0]}\n\n" .
                                "🎯 GENERATE THE CORRECTED SQL QUERY NOW:"
                ]
            ];
            
            // Send recovery request to AI
            $recoveryResponse = $this->executeWithFallback($recoveryMessages, [], $provider);
            $recoveryContent = trim($recoveryResponse['choices'][0]['message']['content'] ?? '');
            
            if (empty($recoveryContent)) {
                Log::warning('[AI SQL Error Recovery Failed]', [
                    'reason' => 'empty_recovery_response'
                ]);
                return null;
            }
            
            Log::info('[AI SQL Error Recovery Response Received]', [
                'recovery_content_preview' => substr($recoveryContent, 0, 200) . (strlen($recoveryContent) > 200 ? '...' : ''),
                'response_length' => strlen($recoveryContent)
            ]);
            
            // Check if AI requested more context
            if (preg_match('/^CONTEXT-REQUEST-([a-zA-Z0-9_]+)$/', trim($recoveryContent), $matches)) {
                $requestedTable = $matches[1];
                Log::info('[AI Requested Additional Context During Recovery]', [
                    'requested_table' => $requestedTable
                ]);
                // Send specific table context
                return $this->sendSpecificTableContextAndRetry($question, $requestedTable, $provider, $validTableNames);
            }
            
            // Extract corrected SQL from recovery response
            $correctedSql = $this->extractSqlFromContent($recoveryContent);
            
            if (empty($correctedSql)) {
                Log::warning('[AI SQL Error Recovery Failed]', [
                    'reason' => 'no_sql_extracted_from_recovery_response',
                    'recovery_content' => $recoveryContent
                ]);
                return null;
            }
            
            // Convert to array if needed
            $correctedQueries = is_array($correctedSql) ? $correctedSql : [$correctedSql];
            
            Log::info('[AI SQL Error Recovery - Executing Corrected Query]', [
                'original_sql' => $failedSql,
                'corrected_sql' => $correctedQueries[0],
                'attempt' => 'recovery_execution'
            ]);
            
            // Execute the corrected SQL
            $correctedResult = $this->executeSqlQuery($correctedQueries[0], $validTableNames);
            
            if ($correctedResult['success']) {
                // Generate response using corrected results
                $finalResponse = $this->generateResponseFromSqlResults($question, [$correctedResult['data']], $correctedQueries);
                
                Log::info('[AI SQL Error Recovery Complete Success]', [
                    'question' => $question,
                    'original_sql' => $failedSql,
                    'corrected_sql' => $correctedQueries[0],
                    'result_count' => is_array($correctedResult['data']) ? count($correctedResult['data']) : 1
                ]);
            
            return [
                    'response' => $finalResponse,
                    'original_query' => $question,
                    'sql_queries' => $correctedQueries,
                    'sql_results' => [$correctedResult['data']],
                    'reasoning_steps' => [],
                    'timestamp' => now(),
                    'method' => 'sql_error_recovery',
                    'relevant_tables' => $tablesInSql,
                    'recovery_info' => [
                        'original_sql' => $failedSql,
                        'original_error' => $error,
                        'corrected_sql' => $correctedQueries[0],
                        'recovery_successful' => true
                    ]
                ];
            } else {
                Log::error('[AI SQL Error Recovery Failed - Corrected Query Also Failed]', [
                    'original_sql' => $failedSql,
                    'original_error' => $error,
                    'corrected_sql' => $correctedQueries[0],
                    'corrected_error' => $correctedResult['error']
                ]);
                return null;
            }
            
        } catch (Exception $e) {
            Log::error('[AI SQL Error Recovery Exception]', [
                'sql' => $failedSql,
                'error' => $error,
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Check if an SQL error is recoverable (field name, table name errors)
     * @param string $error
     * @return bool
     */
    private function isRecoverableSqlError($error)
    {
        $recoverablePatterns = [
            '/column "([^"]+)" does not exist/i',           // PostgreSQL column not found
            '/undefined column/i',                          // Generic column error
            '/unknown column/i',                            // MySQL column not found
            '/no such column/i',                           // SQLite column not found
            '/relation "([^"]+)" does not exist/i',        // PostgreSQL table not found
            '/table "([^"]+)" doesn\'t exist/i',           // MySQL table not found
            '/syntax error.*near/i',                       // Basic syntax errors
            '/invalid input syntax/i',                     // PostgreSQL syntax errors
            '/Query references tables not in the allowed table index/i'  // Custom table validation error
        ];
        
        foreach ($recoverablePatterns as $pattern) {
            if (preg_match($pattern, $error)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Extract table names from failed SQL query
     * @param string $sql
     * @return array
     */
    private function extractTableNamesFromFailedSql($sql)
    {
        $tables = [];
        
        // Extract FROM clauses
        if (preg_match_all('/FROM\s+([a-zA-Z0-9_]+)/i', $sql, $matches)) {
            $tables = array_merge($tables, $matches[1]);
        }
        
        // Extract JOIN clauses
        if (preg_match_all('/JOIN\s+([a-zA-Z0-9_]+)/i', $sql, $matches)) {
            $tables = array_merge($tables, $matches[1]);
        }
        
        // Extract UPDATE clauses
        if (preg_match_all('/UPDATE\s+([a-zA-Z0-9_]+)/i', $sql, $matches)) {
            $tables = array_merge($tables, $matches[1]);
        }
        
        // Extract INSERT INTO clauses
        if (preg_match_all('/INSERT\s+INTO\s+([a-zA-Z0-9_]+)/i', $sql, $matches)) {
            $tables = array_merge($tables, $matches[1]);
        }
        
        return array_unique($tables);
    }

    /**
     * Extract specific error details to help AI understand what went wrong
     * @param string $error
     * @return string
     */
    private function extractErrorDetails($error)
    {
        if (preg_match('/column "([^"]+)" does not exist/i', $error, $matches)) {
            return "The field '{$matches[1]}' does not exist in the table. Check the exact field names in the context.";
        }
        
        if (preg_match('/relation "([^"]+)" does not exist/i', $error, $matches)) {
            return "The table '{$matches[1]}' does not exist. Check the exact table names available.";
        }
        
        if (preg_match('/syntax error/i', $error)) {
            return "There is a syntax error in the SQL query. Check field names, table names, and SQL syntax.";
        }
        
        return "Check field names and table names against the provided context.";
    }

    /**
     * Limit error message size to prevent overwhelming the AI with huge error text
     * @param string $error
     * @param int $maxLength
     * @return string
     */
    private function limitErrorSize($error, $maxLength = 1000)
    {
        if (strlen($error) <= $maxLength) {
            return $error;
        }
        
        // Truncate but keep the essential parts
        $truncated = substr($error, 0, $maxLength);
        
        // Try to break at a sentence or line boundary
        $lastPeriod = strrpos($truncated, '.');
        $lastNewline = strrpos($truncated, "\n");
        $breakPoint = max($lastPeriod, $lastNewline);
        
        if ($breakPoint !== false && $breakPoint > $maxLength * 0.7) {
            $truncated = substr($truncated, 0, $breakPoint + 1);
        }
        
        return $truncated . "\n\n[ERROR MESSAGE TRUNCATED - LENGTH: " . strlen($error) . " chars]";
    }

    /**
     * Limit SQL query size to prevent overwhelming the AI with huge SQL text
     * @param string $sql
     * @param int $maxLength
     * @return string
     */
    private function limitSqlSize($sql, $maxLength = 2000)
    {
        if (strlen($sql) <= $maxLength) {
            return $sql;
        }
        
        // For SQL, try to keep the essential parts
        $truncated = substr($sql, 0, $maxLength);
        
        // Try to break at a keyword boundary
        $keywords = ['SELECT', 'FROM', 'WHERE', 'JOIN', 'ORDER BY', 'GROUP BY', 'HAVING'];
        $lastKeywordPos = 0;
        
        foreach ($keywords as $keyword) {
            $pos = strripos($truncated, $keyword);
            if ($pos !== false && $pos > $lastKeywordPos) {
                $lastKeywordPos = $pos;
            }
        }
        
        if ($lastKeywordPos > $maxLength * 0.5) {
            $truncated = substr($truncated, 0, $lastKeywordPos);
        }
        
        return $truncated . "\n\n[SQL QUERY TRUNCATED - FULL LENGTH: " . strlen($sql) . " chars]";
    }

    /**
     * Generate response from SQL results using AI to format the data
     * @param string $question
     * @param array $sqlResults
     * @param array $sqlQueries
     * @return string
     */
    private function generateResponseFromSqlResults($question, $sqlResults, $sqlQueries)
    {
        try {
            // Flatten SQL results if nested
            $flattenedResults = [];
            foreach ($sqlResults as $resultSet) {
                if (is_array($resultSet)) {
                    $flattenedResults = array_merge($flattenedResults, $resultSet);
                } else {
                    $flattenedResults[] = $resultSet;
                }
            }
            
            Log::info('[AI Generate Response From SQL Results]', [
                'question' => $question,
                'sql_queries_count' => count($sqlQueries),
                'total_results' => count($flattenedResults),
                'results_summary' => count($flattenedResults) > 0 ? 'has_data' : 'no_data'
            ]);
            
            if (empty($flattenedResults)) {
                return $this->generateHtmlResponse([], $question, [], $sqlQueries);
            }
            
            // Use existing HTML generation method
            return $this->generateHtmlResponse($flattenedResults, $question, $flattenedResults, $sqlQueries);
            
        } catch (Exception $e) {
            Log::error('[AI Generate Response From SQL Results Failed]', [
                'question' => $question,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Fallback to simple HTML response
            return $this->generateFallbackHtmlResponse($sqlResults, $question, $sqlQueries);
        }
    }

}

