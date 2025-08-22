<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\AiInteraction;
use Exception;

class AiMemoryService
{
    private $maxMemorySize = 100;
    private $memoryTtl = 3600; // 1 hour
    private $sessionId = null;

    public function __construct($sessionId = null)
    {
        $this->sessionId = $sessionId ?? $this->generateSessionId();
    }

    /**
     * Generate unique session ID
     */
    private function generateSessionId()
    {
        return uniqid('ai_session_', true);
    }

    /**
     * Add interaction to memory
     */
    public function addInteraction($query, $response, $context = [], $metadata = [])
    {
        $interaction = [
            'id' => uniqid('interaction_', true),
            'session_id' => $this->sessionId,
            'query' => $query,
            'response' => $response,
            'context' => $context,
            'metadata' => array_merge($metadata, [
                'timestamp' => now(),
                'user_id' => auth()->id() ?? null,
                'ip_address' => request()->ip() ?? null
            ]),
            'created_at' => now()
        ];

        // Store in cache for quick access
        $this->storeInCache($interaction);

        // Store in database for persistence
        $this->storeInDatabase($interaction);

        // Clean up old memories if needed
        $this->cleanupMemory();

        return $interaction;
    }

    /**
     * Store interaction in cache
     */
    private function storeInCache($interaction)
    {
        $cacheKey = "ai_memory_{$this->sessionId}";
        $memory = Cache::get($cacheKey, []);
        
        $memory[] = $interaction;
        
        // Keep only recent interactions
        if (count($memory) > $this->maxMemorySize) {
            $memory = array_slice($memory, -$this->maxMemorySize);
        }

        Cache::put($cacheKey, $memory, $this->memoryTtl);
    }

    /**
     * Store interaction in database
     */
    private function storeInDatabase($interaction)
    {
        try {
            // Start a fresh transaction
            DB::beginTransaction();
            
            DB::table('ai_interactions')->insert([
                'session_id' => $interaction['session_id'],
                'user_id' => $interaction['metadata']['user_id'] ?? null,
                'query' => $interaction['query'],
                'response' => is_array($interaction['response']) ? json_encode($interaction['response']) : $interaction['response'],
                'context' => json_encode($interaction['context']),
                'metadata' => json_encode($interaction['metadata']),
                'created_at' => $interaction['created_at'],
                'updated_at' => now()
            ]);
            
            // Commit the transaction
            DB::commit();
            
        } catch (Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            
            Log::error('Failed to store AI interaction in database', [
                'error' => $e->getMessage(),
                'interaction' => $interaction
            ]);
        }
    }

    /**
     * Get recent interactions
     */
    public function getRecentInteractions($limit = 10)
    {
        $cacheKey = "ai_memory_{$this->sessionId}";
        $memory = Cache::get($cacheKey, []);

        return array_slice($memory, -$limit);
    }

    /**
     * Get interactions by session
     */
    public function getSessionInteractions($sessionId = null)
    {
        $sessionId = $sessionId ?? $this->sessionId;
        
        try {
            // Start a fresh transaction
            DB::beginTransaction();
            
            $interactions = DB::table('ai_interactions')
                ->where('session_id', $sessionId)
                ->orderBy('created_at', 'desc')
                ->get();
            
            // Commit the transaction
            DB::commit();
            
            return $interactions;
            
        } catch (Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            
            Log::error('Failed to retrieve session interactions', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);
            return collect();
        }
    }

    /**
     * Get context from recent interactions
     */
    public function getContext($limit = 5)
    {
        $interactions = $this->getRecentInteractions($limit);
        $context = [];

        foreach ($interactions as $interaction) {
            $context[] = [
                'query' => $interaction['query'],
                'response' => $interaction['response'],
                'timestamp' => $interaction['metadata']['timestamp'] ?? null
            ];
        }

        return $context;
    }

    /**
     * Get formatted context for AI prompt
     */
    public function getFormattedContext($limit = 5)
    {
        $context = $this->getContext($limit);
        $formatted = [];

        foreach ($context as $item) {
            $formatted[] = "Q: {$item['query']} A: {$item['response']}";
        }

        return implode("\n", $formatted);
    }

    /**
     * Search interactions
     */
    public function searchInteractions($query, $filters = [])
    {
        try {
            // Start a fresh transaction
            DB::beginTransaction();
            
            $queryBuilder = DB::table('ai_interactions')
                ->where('session_id', $this->sessionId);

            if (!empty($query)) {
                $queryBuilder->where(function($q) use ($query) {
                    $q->where('query', 'like', "%{$query}%")
                      ->orWhere('response', 'like', "%{$query}%");
                });
            }

            // Apply filters
            if (!empty($filters['date_from'])) {
                $queryBuilder->where('created_at', '>=', $filters['date_from']);
            }

            if (!empty($filters['date_to'])) {
                $queryBuilder->where('created_at', '<=', $filters['date_to']);
            }

            if (!empty($filters['user_id'])) {
                $queryBuilder->whereRaw("JSON_EXTRACT(metadata, '$.user_id') = ?", [$filters['user_id']]);
            }

            $results = $queryBuilder->orderBy('created_at', 'desc')->get();
            
            // Commit the transaction
            DB::commit();
            
            return $results;

        } catch (Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            
            Log::error('Failed to search interactions', [
                'error' => $e->getMessage(),
                'query' => $query,
                'filters' => $filters
            ]);
            return collect();
        }
    }

    /**
     * Get interaction statistics
     */
    public function getStats($sessionId = null)
    {
        $sessionId = $sessionId ?? $this->sessionId;

        try {
            $stats = DB::table('ai_interactions')
                ->where('session_id', $sessionId)
                ->selectRaw('
                    COUNT(*) as total_interactions,
                    MIN(created_at) as first_interaction,
                    MAX(created_at) as last_interaction,
                    AVG(LENGTH(query)) as avg_query_length,
                    AVG(LENGTH(response)) as avg_response_length
                ')
                ->first();

            return $stats;

        } catch (Exception $e) {
            Log::error('Failed to get interaction stats', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);
            return null;
        }
    }

    /**
     * Clear session memory
     */
    public function clearSessionMemory($sessionId = null)
    {
        $sessionId = $sessionId ?? $this->sessionId;

        // Clear cache
        Cache::forget("ai_memory_{$sessionId}");

        // Clear database
        try {
            DB::table('ai_interactions')
                ->where('session_id', $sessionId)
                ->delete();
        } catch (Exception $e) {
            Log::error('Failed to clear session memory from database', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);
        }
    }

    /**
     * Clean up old memories
     */
    private function cleanupMemory()
    {
        try {
            // Start a fresh transaction
            DB::beginTransaction();
            
            // Remove old interactions from database (older than 24 hours)
            $cutoffTime = now()->subHours(24);
            
            DB::table('ai_interactions')
                ->where('created_at', '<', $cutoffTime)
                ->delete();
            
            // Commit the transaction
            DB::commit();

        } catch (Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            
            Log::error('Failed to cleanup old memories', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get learning patterns
     */
    public function getLearningPatterns($sessionId = null)
    {
        $sessionId = $sessionId ?? $this->sessionId;

        try {
            // Start a fresh transaction
            DB::beginTransaction();
            
            $patterns = DB::table('ai_interactions')
                ->where('session_id', $sessionId)
                ->selectRaw('
                    DATE(created_at) as date,
                    COUNT(*) as interaction_count,
                    AVG(LENGTH(query)) as avg_query_length,
                    AVG(LENGTH(response)) as avg_response_length
                ')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->limit(30)
                ->get();
            
            // Commit the transaction
            DB::commit();

            return $patterns;

        } catch (Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            
            Log::error('Failed to get learning patterns', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId
            ]);
            return collect();
        }
    }

    /**
     * Export session data
     */
    public function exportSessionData($sessionId = null, $format = 'json')
    {
        $sessionId = $sessionId ?? $this->sessionId;
        $interactions = $this->getSessionInteractions($sessionId);

        switch ($format) {
            case 'json':
                return $interactions->toJson();
            
            case 'csv':
                return $this->convertToCsv($interactions);
            
            default:
                return $interactions;
        }
    }

    /**
     * Convert interactions to CSV
     */
    private function convertToCsv($interactions)
    {
        $csv = "Query,Response,Context,Timestamp\n";
        
        foreach ($interactions as $interaction) {
            $query = str_replace('"', '""', $interaction->query);
            $response = str_replace('"', '""', $interaction->response);
            $context = str_replace('"', '""', $interaction->context);
            $timestamp = $interaction->created_at;
            
            $csv .= "\"{$query}\",\"{$response}\",\"{$context}\",\"{$timestamp}\"\n";
        }

        return $csv;
    }

    /**
     * Get session ID
     */
    public function getSessionId()
    {
        return $this->sessionId;
    }

    /**
     * Set session ID
     */
    public function setSessionId($sessionId)
    {
        $this->sessionId = $sessionId;
    }

    /**
     * Store a conversation message
     */
    public function storeMessage($sessionId, $role, $content, $metadata = [])
    {
        try {
            Log::info('[AI Memory Store Message]', [
                'session_id' => $sessionId,
                'role' => $role,
                'content_length' => strlen($content),
                'metadata' => $metadata
            ]);

            // Start a fresh transaction
            DB::beginTransaction();

            $message = new AiInteraction();
            $message->session_id = $sessionId;
            $message->role = $role;
            $message->content = $content;
            $message->metadata = $metadata;
            $message->created_at = now();
            $message->save();
            
            // Commit the transaction
            DB::commit();

            Log::info('[AI Memory Message Stored]', [
                'message_id' => $message->id,
                'session_id' => $sessionId
            ]);

            return $message;
        } catch (Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            
            Log::error('[AI Memory Store Message Error]', [
                'session_id' => $sessionId,
                'role' => $role,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get conversation history
     */
    public function getConversationHistory($sessionId, $limit = 50)
    {
        try {
            Log::info('[AI Memory Get History]', [
                'session_id' => $sessionId,
                'limit' => $limit
            ]);

            // Start a fresh transaction
            DB::beginTransaction();

            $messages = AiInteraction::where('session_id', $sessionId)
                ->orderBy('created_at', 'asc')
                ->limit($limit)
                ->get();
            
            // Commit the transaction
            DB::commit();

            Log::info('[AI Memory History Retrieved]', [
                'session_id' => $sessionId,
                'message_count' => $messages->count(),
                'first_message_time' => $messages->first()?->created_at,
                'last_message_time' => $messages->last()?->created_at
            ]);

            return $messages;
        } catch (Exception $e) {
            // Rollback the transaction
            DB::rollBack();
            
            Log::error('[AI Memory Get History Error]', [
                'session_id' => $sessionId,
                'limit' => $limit,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return collect();
        }
    }

    /**
     * Build conversation context
     */
    public function buildContext($sessionId, $maxTokens = 4000)
    {
        try {
            Log::info('[AI Memory Build Context]', [
                'session_id' => $sessionId,
                'max_tokens' => $maxTokens
            ]);

            $messages = $this->getConversationHistory($sessionId);
            
            if ($messages->isEmpty()) {
                Log::info('[AI Memory Context Empty]', [
                    'session_id' => $sessionId
                ]);
                return [];
            }

            $context = [];
            $currentTokens = 0;

            foreach ($messages as $message) {
                $messageTokens = $this->estimateTokens($message->content);
                
                if ($currentTokens + $messageTokens > $maxTokens) {
                    Log::info('[AI Memory Context Token Limit]', [
                        'session_id' => $sessionId,
                        'current_tokens' => $currentTokens,
                        'message_tokens' => $messageTokens,
                        'max_tokens' => $maxTokens,
                        'messages_included' => count($context)
                    ]);
                    break;
                }

                $context[] = [
                    'role' => $message->role,
                    'content' => $message->content
                ];
                $currentTokens += $messageTokens;
            }

            Log::info('[AI Memory Context Built]', [
                'session_id' => $sessionId,
                'context_messages' => count($context),
                'total_tokens' => $currentTokens,
                'context_size' => strlen(json_encode($context))
            ]);

            return $context;
        } catch (Exception $e) {
            Log::error('[AI Memory Build Context Error]', [
                'session_id' => $sessionId,
                'max_tokens' => $maxTokens,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get conversation summary
     */
    public function getConversationSummary($sessionId)
    {
        try {
            Log::info('[AI Memory Get Summary]', [
                'session_id' => $sessionId
            ]);

            $summary = AiInteraction::where('session_id', $sessionId)
                ->where('role', 'assistant')
                ->whereNotNull('metadata->summary')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($summary) {
                Log::info('[AI Memory Summary Found]', [
                    'session_id' => $sessionId,
                    'summary_id' => $summary->id,
                    'summary_length' => strlen($summary->metadata['summary'] ?? '')
                ]);
                return $summary->metadata['summary'] ?? null;
            }

            Log::info('[AI Memory No Summary Found]', [
                'session_id' => $sessionId
            ]);
            return null;
        } catch (Exception $e) {
            Log::error('[AI Memory Get Summary Error]', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Store conversation summary
     */
    public function storeSummary($sessionId, $summary)
    {
        try {
            Log::info('[AI Memory Store Summary]', [
                'session_id' => $sessionId,
                'summary_length' => strlen($summary)
            ]);

            $message = new AiInteraction();
            $message->session_id = $sessionId;
            $message->role = 'assistant';
            $message->content = 'Conversation summary stored';
            $message->metadata = ['summary' => $summary];
            $message->created_at = now();
            $message->save();

            Log::info('[AI Memory Summary Stored]', [
                'session_id' => $sessionId,
                'summary_id' => $message->id
            ]);

            return $message;
        } catch (Exception $e) {
            Log::error('[AI Memory Store Summary Error]', [
                'session_id' => $sessionId,
                'summary_length' => strlen($summary),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Clear conversation history
     */
    public function clearConversation($sessionId)
    {
        try {
            Log::info('[AI Memory Clear Conversation]', [
                'session_id' => $sessionId
            ]);

            $deletedCount = AiInteraction::where('session_id', $sessionId)->delete();

            Log::info('[AI Memory Conversation Cleared]', [
                'session_id' => $sessionId,
                'deleted_messages' => $deletedCount
            ]);

            return $deletedCount;
        } catch (Exception $e) {
            Log::error('[AI Memory Clear Conversation Error]', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Get conversation statistics
     */
    public function getConversationStats($sessionId)
    {
        try {
            Log::info('[AI Memory Get Stats]', [
                'session_id' => $sessionId
            ]);

            $stats = [
                'total_messages' => 0,
                'user_messages' => 0,
                'assistant_messages' => 0,
                'first_message' => null,
                'last_message' => null,
                'total_tokens' => 0
            ];

            $messages = AiInteraction::where('session_id', $sessionId)
                ->orderBy('created_at', 'asc')
                ->get();

            if ($messages->isNotEmpty()) {
                $stats['total_messages'] = $messages->count();
                $stats['user_messages'] = $messages->where('role', 'user')->count();
                $stats['assistant_messages'] = $messages->where('role', 'assistant')->count();
                $stats['first_message'] = $messages->first()->created_at;
                $stats['last_message'] = $messages->last()->created_at;

                foreach ($messages as $message) {
                    $stats['total_tokens'] += $this->estimateTokens($message->content);
                }
            }

            Log::info('[AI Memory Stats Retrieved]', [
                'session_id' => $sessionId,
                'stats' => $stats
            ]);

            return $stats;
        } catch (Exception $e) {
            Log::error('[AI Memory Get Stats Error]', [
                'session_id' => $sessionId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Estimate token count for text
     */
    private function estimateTokens($text)
    {
        try {
            // Rough estimation: 1 token ≈ 4 characters for English text
            $estimatedTokens = ceil(strlen($text) / 4);
            
            Log::debug('[AI Memory Token Estimation]', [
                'text_length' => strlen($text),
                'estimated_tokens' => $estimatedTokens
            ]);
            
            return $estimatedTokens;
        } catch (Exception $e) {
            Log::error('[AI Memory Token Estimation Error]', [
                'text_length' => strlen($text),
                'error' => $e->getMessage()
            ]);
            return strlen($text) / 4; // Fallback estimation
        }
    }

    /**
     * Get conversation list for sidebar
     */
    public function getConversationList($limit = 10)
    {
        try {
            $userId = auth()->id();
            
            Log::info('Getting conversation list', [
                'user_id' => $userId,
                'authenticated' => auth()->check()
            ]);
            
            if (!$userId) {
                Log::warning('No authenticated user found for conversation list');
                return [];
            }
            
            // Get the latest interaction for each session
            $sessions = DB::table('ai_interactions as ai1')
                ->select('ai1.session_id', 'ai1.query', 'ai1.created_at')
                ->where('ai1.user_id', $userId)
                ->whereRaw('ai1.created_at = (
                    SELECT MAX(ai2.created_at) 
                    FROM ai_interactions as ai2 
                    WHERE ai2.session_id = ai1.session_id 
                    AND ai2.user_id = ai1.user_id
                )')
                ->orderBy('ai1.created_at', 'desc')
                ->limit($limit)
                ->get();

            $conversations = [];
            foreach ($sessions as $session) {
                $conversations[] = [
                    'id' => $session->session_id,
                    'title' => substr($session->query, 0, 50) . '...',
                    'time' => \Carbon\Carbon::parse($session->created_at)->diffForHumans()
                ];
            }

            Log::info('Retrieved conversation list', [
                'user_id' => $userId,
                'count' => count($conversations),
                'conversations' => $conversations
            ]);

            return $conversations;
        } catch (Exception $e) {
            Log::error('Failed to get conversation list', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Search conversations
     */
    public function searchConversations($query)
    {
        try {
            // Get the latest interaction for each session that matches the search
            $sessions = DB::table('ai_interactions as ai1')
                ->select('ai1.session_id', 'ai1.query', 'ai1.created_at')
                ->where('ai1.user_id', auth()->id())
                ->where('ai1.query', 'like', "%{$query}%")
                ->whereRaw('ai1.created_at = (
                    SELECT MAX(ai2.created_at) 
                    FROM ai_interactions as ai2 
                    WHERE ai2.session_id = ai1.session_id 
                    AND ai2.user_id = ai1.user_id
                )')
                ->orderBy('ai1.created_at', 'desc')
                ->limit(10)
                ->get();

            $conversations = [];
            foreach ($sessions as $session) {
                $conversations[] = [
                    'id' => $session->session_id,
                    'title' => substr($session->query, 0, 50) . '...',
                    'time' => \Carbon\Carbon::parse($session->created_at)->diffForHumans()
                ];
            }

            Log::info('Searched conversations', [
                'user_id' => auth()->id(),
                'query' => $query,
                'count' => count($conversations)
            ]);

            return $conversations;
        } catch (Exception $e) {
            Log::error('Failed to search conversations', [
                'error' => $e->getMessage(),
                'query' => $query,
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Get conversation by ID
     */
    public function getConversation($conversationId)
    {
        try {
            $interactions = DB::table('ai_interactions')
                ->where('session_id', $conversationId)
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'asc')
                ->get();

            $messages = [];
            foreach ($interactions as $interaction) {
                // Only add user message if query is not empty
                if (!empty($interaction->query)) {
                    $messages[] = [
                        'id' => uniqid('msg_', true),
                        'content' => $interaction->query,
                        'sender' => 'user',
                        'isError' => false,
                        'timestamp' => \Carbon\Carbon::parse($interaction->created_at),
                        'liked' => false
                    ];
                }

                // Only add AI response if response is not empty
                if (!empty($interaction->response)) {
                    $messages[] = [
                        'id' => uniqid('msg_', true),
                        'content' => $interaction->response,
                        'sender' => 'ai',
                        'isError' => false,
                        'timestamp' => \Carbon\Carbon::parse($interaction->created_at),
                        'liked' => false
                    ];
                }
            }

            Log::info('Retrieved conversation', [
                'conversation_id' => $conversationId,
                'user_id' => auth()->id(),
                'interaction_count' => $interactions->count(),
                'message_count' => count($messages)
            ]);

            return [
                'id' => $conversationId,
                'messages' => $messages
            ];
        } catch (Exception $e) {
            Log::error('Failed to get conversation', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversationId,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Set conversation favorite status
     */
    public function setConversationFavorite($sessionId, $isFavorite)
    {
        try {
            // Store favorite status in cache for now
            $cacheKey = "ai_favorite_{$sessionId}";
            Cache::put($cacheKey, $isFavorite, 86400); // 24 hours

            return true;
        } catch (Exception $e) {
            Log::error('Failed to set conversation favorite', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
                'is_favorite' => $isFavorite
            ]);
            return false;
        }
    }
} 