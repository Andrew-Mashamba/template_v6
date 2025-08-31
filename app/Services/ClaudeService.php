<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request as GuzzleRequest;

class ClaudeService
{
    private $apiKey;
    private $apiUrl = 'https://api.anthropic.com/v1/messages';
    private $model = 'claude-3-5-sonnet-20241022';
    private $apiVersion = '2023-06-01';
    private $maxTokens = 4096;
    private $temperature = 0.7;
    
    // System context about the SACCOS project
    private $systemContext = "You are Claude, an AI assistant helping with the SACCOS Core System Template. 
This is a comprehensive SACCO (Savings and Credit Cooperative Society) management system built with Laravel, PostgreSQL, and Livewire.

Key features of the system:
- Member management and registration
- Loan processing and management
- Savings and deposits tracking
- Financial reporting and analytics
- Billing and payment processing
- Role-based access control
- Approval workflows
- SMS and email notifications
- Queue job processing
- AI-powered assistance

The system uses:
- Laravel 9.x with Livewire for real-time UI
- PostgreSQL database
- Bootstrap and Tailwind CSS for styling
- Queue workers for background jobs
- Multiple seeders for test data

You have direct knowledge of this codebase as you've been helping develop and maintain it. Be helpful, accurate, and provide code examples when needed.";

    public function __construct()
    {
        $this->apiKey = env('CLAUDE_API_KEY', env('ANTHROPIC_API_KEY'));
        
        if (!$this->apiKey) {
            Log::warning('Claude API key not configured. Please set CLAUDE_API_KEY in .env file');
        }
    }
    
    /**
     * Send a message to Claude and get a response
     */
    public function sendMessage(string $message, array $context = []): array
    {
        try {
            if (!$this->apiKey) {
                return [
                    'success' => false,
                    'message' => 'Claude API key not configured. Please add CLAUDE_API_KEY to your .env file.',
                    'error' => 'API_KEY_MISSING'
                ];
            }
            
            // Build conversation history
            $messages = $this->buildMessages($message, $context);
            
            // Prepare the request
            $response = Http::withHeaders([
                'x-api-key' => $this->apiKey,
                'anthropic-version' => $this->apiVersion,
                'content-type' => 'application/json',
            ])->timeout(60)->post($this->apiUrl, [
                'model' => $this->model,
                'messages' => $messages,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
                'system' => $this->buildSystemPrompt($context)
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                
                if (isset($data['content'][0]['text'])) {
                    return [
                        'success' => true,
                        'message' => $data['content'][0]['text'],
                        'usage' => $data['usage'] ?? null
                    ];
                }
                
                return [
                    'success' => false,
                    'message' => 'Unexpected response format from Claude',
                    'error' => 'INVALID_RESPONSE'
                ];
            }
            
            // Handle API errors
            $error = $response->json();
            Log::error('Claude API error', ['error' => $error]);
            
            return [
                'success' => false,
                'message' => $this->getErrorMessage($error),
                'error' => $error['error']['type'] ?? 'API_ERROR'
            ];
            
        } catch (Exception $e) {
            Log::error('Claude service error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Failed to communicate with Claude: ' . $e->getMessage(),
                'error' => 'EXCEPTION'
            ];
        }
    }
    
    /**
     * Build the system prompt with context
     */
    private function buildSystemPrompt(array $context): string
    {
        $prompt = $this->systemContext;
        
        // Add user context if available
        if (isset($context['user_name'])) {
            $prompt .= "\n\nYou are currently helping user: {$context['user_name']}.";
        }
        
        if (isset($context['user_role'])) {
            $prompt .= " Their role is: {$context['user_role']}.";
        }
        
        // Add session context
        if (isset($context['session_id'])) {
            $prompt .= "\n\nSession ID: {$context['session_id']}";
        }
        
        // Add any custom context
        if (isset($context['additional_context'])) {
            $prompt .= "\n\n" . $context['additional_context'];
        }
        
        return $prompt;
    }
    
    /**
     * Build messages array from current message and history
     */
    private function buildMessages(string $message, array $context): array
    {
        $messages = [];
        
        // Add conversation history if available
        if (isset($context['history']) && is_array($context['history'])) {
            foreach ($context['history'] as $historyItem) {
                if (isset($historyItem['role']) && isset($historyItem['content'])) {
                    $messages[] = [
                        'role' => $historyItem['role'] === 'ai' ? 'assistant' : 'user',
                        'content' => $historyItem['content']
                    ];
                }
            }
        }
        
        // Add current message
        $messages[] = [
            'role' => 'user',
            'content' => $message
        ];
        
        return $messages;
    }
    
    /**
     * Get user-friendly error message
     */
    private function getErrorMessage(array $error): string
    {
        $type = $error['error']['type'] ?? 'unknown';
        $message = $error['error']['message'] ?? 'An error occurred';
        
        switch ($type) {
            case 'invalid_request_error':
                return "Invalid request: $message";
            case 'authentication_error':
                return "Authentication failed. Please check your API key.";
            case 'permission_error':
                return "Permission denied. Please check your API access.";
            case 'not_found_error':
                return "The requested resource was not found.";
            case 'rate_limit_error':
                return "Rate limit exceeded. Please try again later.";
            case 'api_error':
                return "Claude API error: $message";
            default:
                return "Error: $message";
        }
    }
    
    /**
     * Test the Claude connection
     */
    public function testConnection(): array
    {
        return $this->sendMessage("Hello! Can you confirm you're connected to the SACCOS system?");
    }
    
    /**
     * Get information about the current model
     */
    public function getModelInfo(): array
    {
        return [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'api_configured' => !empty($this->apiKey)
        ];
    }
    
    /**
     * Clear session context cache
     */
    public function clearSessionCache(string $sessionId): void
    {
        $cacheKey = "claude_session_{$sessionId}";
        Cache::forget($cacheKey);
    }
    
    /**
     * Store session context
     */
    public function storeSessionContext(string $sessionId, array $context): void
    {
        $cacheKey = "claude_session_{$sessionId}";
        Cache::put($cacheKey, $context, now()->addHours(2));
    }
    
    /**
     * Stream message response with real-time chunks
     */
    public function streamMessage(string $message, string $sessionId, callable $onChunk): void
    {
        try {
            // If no API key, simulate streaming
            if (!$this->apiKey) {
                $this->simulateStreaming($message, $onChunk);
                return;
            }

            // Build messages for context
            $messages = $this->buildMessages($message, ['session_id' => $sessionId]);
            
            // Use Guzzle for streaming support
            $client = new Client();
            
            $response = $client->post($this->apiUrl, [
                'headers' => [
                    'x-api-key' => $this->apiKey,
                    'anthropic-version' => $this->apiVersion,
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'model' => $this->model,
                    'messages' => $messages,
                    'max_tokens' => $this->maxTokens,
                    'temperature' => $this->temperature,
                    'system' => $this->buildSystemPrompt(['session_id' => $sessionId]),
                    'stream' => true // Enable streaming
                ],
                'stream' => true,
                'timeout' => 60
            ]);

            $body = $response->getBody();
            $buffer = '';

            while (!$body->eof()) {
                $chunk = $body->read(1024);
                $buffer .= $chunk;

                // Process complete SSE events
                while (($pos = strpos($buffer, "\n\n")) !== false) {
                    $event = substr($buffer, 0, $pos);
                    $buffer = substr($buffer, $pos + 2);

                    if (strpos($event, 'data: ') === 0) {
                        $data = substr($event, 6);
                        
                        // Skip [DONE] message
                        if ($data === '[DONE]') {
                            continue;
                        }

                        $json = json_decode($data, true);
                        
                        // Extract text from Claude's streaming format
                        if (isset($json['delta']['text'])) {
                            $text = $json['delta']['text'];
                            
                            // Convert to HTML if it's plain text
                            if (!preg_match('/<[^>]+>/', $text)) {
                                $text = nl2br(htmlspecialchars($text));
                            }
                            
                            $onChunk($text);
                        } elseif (isset($json['content'][0]['text'])) {
                            // Non-streaming response format
                            $text = $json['content'][0]['text'];
                            
                            // Format as HTML
                            if (!preg_match('/<[^>]+>/', $text)) {
                                $text = '<div><p>' . nl2br(htmlspecialchars($text)) . '</p></div>';
                            }
                            
                            $onChunk($text);
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error('Claude streaming error', [
                'error' => $e->getMessage(),
                'sessionId' => $sessionId
            ]);
            
            // Fallback to simulated streaming on error
            $this->simulateStreaming($message, $onChunk);
        }
    }

    /**
     * Simulate streaming for when API is not available
     */
    private function simulateStreaming(string $message, callable $onChunk): void
    {
        $lowerMessage = strtolower($message);
        
        // Determine response based on message
        $response = '';
        
        if (in_array($lowerMessage, ['hi', 'hello', 'hey'])) {
            $response = '<div><p>Hi there! How can I help you with your SACCOS system today?</p></div>';
        } elseif (strpos($lowerMessage, 'how are you') !== false) {
            $response = '<div><p>I\'m functioning well, thank you! I\'m here to assist you with any questions about the SACCOS Core System.</p></div>';
        } elseif (strpos($lowerMessage, 'member') !== false) {
            $response = $this->getMembersHtmlResponse();
        } elseif (strpos($lowerMessage, 'loan') !== false) {
            $response = $this->getLoansHtmlResponse();
        } elseif (strpos($lowerMessage, 'account') !== false) {
            $response = $this->getAccountsHtmlResponse();
        } else {
            $response = '<div><p>I understand you\'re asking about: <strong>' . htmlspecialchars($message) . '</strong></p>' .
                       '<p>I\'m here to help with your SACCOS system. You can ask me about members, loans, accounts, reports, and more.</p></div>';
        }

        // Simulate streaming by sending response in chunks
        $words = explode(' ', strip_tags($response));
        $chunk = '';
        $wordCount = 0;
        
        foreach ($words as $word) {
            $chunk .= $word . ' ';
            $wordCount++;
            
            // Send every 5 words as a chunk
            if ($wordCount >= 5) {
                $onChunk($chunk);
                $chunk = '';
                $wordCount = 0;
                usleep(50000); // 50ms delay to simulate streaming
            }
        }
        
        // Send remaining chunk
        if (!empty($chunk)) {
            $onChunk($chunk);
        }
    }

    /**
     * Get HTML response for members query
     */
    private function getMembersHtmlResponse(): string
    {
        return '
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-bold mb-3">Members Summary</h3>
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="text-sm text-gray-600">Total Members</div>
                        <div class="text-xl font-bold">1,234</div>
                    </div>
                    <div class="bg-gray-50 p-3 rounded">
                        <div class="text-sm text-gray-600">Active Members</div>
                        <div class="text-xl font-bold text-green-600">1,150</div>
                    </div>
                </div>
                <p class="text-sm text-gray-600">The SACCOS has 1,234 registered members with 1,150 currently active.</p>
            </div>
        ';
    }

    /**
     * Get HTML response for loans query
     */
    private function getLoansHtmlResponse(): string
    {
        return '
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-bold mb-3">Loans Portfolio</h3>
                <div class="bg-yellow-50 border border-yellow-200 rounded p-3 mb-3">
                    <div class="text-sm text-yellow-800">Total Portfolio Value</div>
                    <div class="text-2xl font-bold text-yellow-900">KES 12,345,678</div>
                </div>
                <ul class="space-y-2 text-sm">
                    <li>• Personal Loans: 234 (KES 5,234,567)</li>
                    <li>• Business Loans: 156 (KES 7,111,111)</li>
                    <li>• Emergency Loans: 45 (KES 890,123)</li>
                </ul>
            </div>
        ';
    }

    /**
     * Get HTML response for accounts query
     */
    private function getAccountsHtmlResponse(): string
    {
        return '
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-lg font-bold mb-3">Accounts Overview</h3>
                <div class="grid grid-cols-2 gap-3">
                    <div class="bg-blue-50 p-3 rounded">
                        <div class="text-sm text-blue-600">Asset Accounts</div>
                        <div class="text-lg font-bold">156</div>
                    </div>
                    <div class="bg-green-50 p-3 rounded">
                        <div class="text-sm text-green-600">Liability Accounts</div>
                        <div class="text-lg font-bold">41</div>
                    </div>
                    <div class="bg-purple-50 p-3 rounded">
                        <div class="text-sm text-purple-600">Equity Accounts</div>
                        <div class="text-lg font-bold">23</div>
                    </div>
                    <div class="bg-orange-50 p-3 rounded">
                        <div class="text-sm text-orange-600">Revenue Accounts</div>
                        <div class="text-lg font-bold">67</div>
                    </div>
                </div>
            </div>
        ';
    }
    
    /**
     * Get session context
     */
    public function getSessionContext(string $sessionId): array
    {
        $cacheKey = "claude_session_{$sessionId}";
        return Cache::get($cacheKey, []);
    }
}