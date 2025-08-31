<?php

namespace App\Http\Livewire\AiAgent;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\AiMemoryService;
use App\Services\AiValidationService;
use App\Services\DirectClaudeCliService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Blade;

class AiAgentChat extends Component
{
    use WithFileUploads;
    
    public $message = '';
    public $messages = [];
    public $isLoading = false;
    public $sessionId;
    public $error = '';
    public $searchQuery = '';
    public $showSettings = false;
    public $isFavorite = false;
    public $conversationHistory = [];
    public $currentResponse = '';
    public $settings = [
        'responseLength' => 'medium',
        'autoSave' => true
    ];
    
    // File upload properties
    public $uploadedFiles = [];
    public $attachedFiles = [];
    public $showFileUpload = false;
    public $showAttachments = false;
    public $tempFiles = [];
    public $fileUploadProgress = 0;
    public $isDragging = false;
    
    // Voice recording
    public $isRecording = false;
    
    // Global loading states
    public $showGlobalLoading = false;
    
    // Streaming states
    public $isStreaming = false;
    public $streamingMessageId = null;
    public $streamBuffer = '';
    public $globalLoadingMessage = '';
    
    // Retry handling for malformed templates
    public $retryRequired = false;
    public $retryErrors = [];
    public $retryCount = 0;
    public $maxRetries = 3;

    protected $memoryService;
    protected $validationService;
    protected $claudeService; // This is the DirectClaudeCliService instance

    protected $listeners = [
        'sendMessage' => 'processMessage',
        'processDirectMessage' => 'processDirectMessage'
    ];

    public function mount()
    {
        $this->sessionId = uniqid('session_', true);
        $this->message = ''; // Ensure message is always a string
        $this->loadRecentMessages();
        $this->loadConversationHistory();
        
        // Initialize Claude CLI service
        try {
            $this->claudeService = new DirectClaudeCliService();
            Log::info('[LIVEWIRE-AI] DirectClaudeCliService initialized');
        } catch (\Exception $e) {
            Log::error('[LIVEWIRE-AI] Failed to initialize DirectClaudeCliService', [
                'error' => $e->getMessage()
            ]);
            $this->claudeService = null;
        }
    }

    public function boot(
        AiMemoryService $memoryService,
        AiValidationService $validationService
    ) {
        $this->memoryService = $memoryService;
        $this->validationService = $validationService;
    }

    public function render()
    {
        // Always use the direct blade file for simpler Livewire integration
        return view('livewire.ai-agent.ai-agent-chat-direct');
    }
    
    /**
     * Process message directly in Livewire component
     */
    public function processDirectMessage($message = null)
    {
        // Use the message parameter or fall back to the message property
        $userMessage = $message ?? $this->message;
        
        if (empty(trim($userMessage))) {
            return;
        }
        
        // Clear message input
        $this->message = '';
        
        // Add user message to chat
        $this->addMessage($userMessage, 'user');
        
        // Set loading state
        $this->isLoading = true;
        $this->error = '';
        
        try {
            // Check if DirectClaudeCliService is initialized
            if (!$this->claudeService) {
                // Try to reinitialize
                try {
                    $this->claudeService = new DirectClaudeCliService();
                    Log::info('[LIVEWIRE-DIRECT] Reinitialized DirectClaudeCliService');
                } catch (\Exception $e) {
                    Log::error('[LIVEWIRE-DIRECT] Failed to reinitialize', [
                        'error' => $e->getMessage()
                    ]);
                    throw new \Exception('Failed to initialize Claude CLI service');
                }
            }
            
            // Append Zona AI system prompt to user message
            $zonaPrompt = $this->getZonaAiSystemPrompt();
            $userMessageWithSystem = $userMessage . "\n\n" . $zonaPrompt;
            
            // Build context for Claude CLI
            $context = [
                'user_name' => Auth::user()->name ?? 'User',
                'user_role' => Auth::user()->role ?? 'Member',
                'session_id' => $this->sessionId,
                'request_type' => 'chat',
                'timestamp' => now()->toIso8601String(),
                'format' => 'html',
                'formatting_instructions' => $this->getFormattingInstructions()
            ];
            
            // LOG THE COMPLETE PROMPT
            Log::info('[LIVEWIRE-DIRECT] FULL PROMPT TO CLAUDE CLI', [
                'session_id' => $this->sessionId,
                'original_user_message' => $userMessage,
                'zona_prompt_length' => strlen($zonaPrompt),
                'complete_prompt_length' => strlen($userMessageWithSystem),
                'complete_prompt' => $userMessageWithSystem,
                'context' => $context
            ]);
            
            // Send message to Claude CLI with Zona AI system prompt
            $response = $this->claudeService->sendMessage($userMessageWithSystem, $context);
            
            Log::info('[LIVEWIRE-DIRECT] Response received', [
                'session_id' => $this->sessionId,
                'success' => $response['success'] ?? false,
                'has_message' => isset($response['message']),
                'message_length' => isset($response['message']) ? strlen($response['message']) : 0
            ]);
            
            if ($response['success']) {
                // Format and add the response
                $formattedResponse = $this->formatClaudeResponse($response['message']);
                $this->addMessage($formattedResponse, 'ai');
                
                // Save interaction
                $this->saveInteraction($userMessage, $response['message']);
                
                Log::info('[LIVEWIRE-DIRECT] Message processed successfully', [
                    'session_id' => $this->sessionId
                ]);
            } else {
                // Handle error
                $errorMessage = $response['message'] ?? 'Failed to get response';
                $this->addMessage($errorMessage, 'ai', true);
                
                Log::error('[LIVEWIRE-DIRECT] Processing failed', [
                    'error' => $response['error'] ?? 'Unknown',
                    'message' => $errorMessage
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('[LIVEWIRE-DIRECT] Exception during processing', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Check if it's a timeout or connection issue
            if (strpos($e->getMessage(), 'not available') !== false) {
                $errorMessage = '<div class="bg-red-50 border border-red-200 rounded-lg p-3">';
                $errorMessage .= '<div class="text-red-800 font-semibold mb-2">Local Claude CLI Not Available</div>';
                $errorMessage .= '<div class="text-red-600 text-sm space-y-2">';
                $errorMessage .= '<p>Please ensure Claude CLI is installed and running:</p>';
                $errorMessage .= '<ul class="list-disc list-inside mt-2 space-y-1">';
                $errorMessage .= '<li>Install: <code class="bg-gray-100 px-1 py-0.5 rounded">brew install claude</code></li>';
                $errorMessage .= '<li>Check: <code class="bg-gray-100 px-1 py-0.5 rounded">which claude</code></li>';
                $errorMessage .= '</ul>';
                $errorMessage .= '</div>';
                $errorMessage .= '</div>';
            } else {
                $errorMessage = 'Sorry, I encountered an error. Please try again.';
            }
            
            $this->addMessage($errorMessage, 'ai', true);
        } finally {
            $this->isLoading = false;
        }
    }

    public function sendMessage()
    {
        // Reset retry count for new messages
        $this->retryCount = 0;
        $this->retryRequired = false;
        $this->retryErrors = [];
        
        // Use the enhanced version with file support
        $this->sendMessageWithFiles();
    }

    private function processAiResponse($userMessage)
    {
        $this->isLoading = true;
        $this->error = '';
        
        // LOG POINT 1: Log the user prompt and full prompt with Zona AI
        $zonaPrompt = $this->getZonaAiSystemPrompt();
        $fullPrompt = $userMessage . "\n\n" . $zonaPrompt;
        
        Log::channel('ai_chat')->info('📝 [CLAUDE-PROMPT] COMPLETE PROMPT WITH ZONA AI', [
            'session_id' => $this->sessionId,
            'original_user_message' => $userMessage,
            'user_message_length' => strlen($userMessage),
            'zona_prompt_length' => strlen($zonaPrompt),
            'full_prompt_length' => strlen($fullPrompt),
            'full_prompt_to_claude' => $fullPrompt,
            'timestamp' => now()->toIso8601String()
        ]);

        try {
            // Validate input
            $validation = $this->validationService->validateQuery($userMessage, [
                'user_id' => Auth::id(),
                'user_permissions' => $this->getUserPermissions(),
                'session_id' => $this->sessionId
            ]);
            
            // LOG POINT 2: Validation result
            Log::channel('ai_chat')->info('🔷 [PROMPT-CHAIN] Validation Complete', [
                'session_id' => $this->sessionId,
                'validation_result' => $validation['valid'] ? 'PASSED' : 'FAILED',
                'location' => 'AiAgentChat::processAiResponse::validation'
            ]);

            if (!$validation['valid']) {
                $this->addMessage('I cannot process that request due to security concerns.', 'ai', true);
                $this->isLoading = false;
                return;
            }

            // Always use DirectClaudeCliService (local Claude CLI)
            $this->processWithDirectClaudeCli($userMessage);

        } catch (\Exception $e) {
            $this->error = 'An error occurred while processing your request.';
            $this->addMessage('Sorry, I encountered an error. Please try again.', 'ai', true);
        }

        $this->isLoading = false;
    }

    /**
     * Process message with DirectClaudeCliService
     */
    private function processWithDirectClaudeCli($userMessage)
    {
        try {
            // Initialize or get the DirectClaudeCliService
            if (!$this->claudeService) {
                try {
                    $this->claudeService = new DirectClaudeCliService();
                    Log::info('[PROCESS-WITH-CLI] Initialized DirectClaudeCliService');
                } catch (\Exception $e) {
                    Log::error('[PROCESS-WITH-CLI] Failed to initialize', [
                        'error' => $e->getMessage()
                    ]);
                    $this->addMessage('Failed to initialize Claude CLI service', 'ai', true);
                    $this->isLoading = false;
                    return;
                }
            }
            
            // Append Zona AI system prompt to user message
            $zonaPrompt = $this->getZonaAiSystemPrompt();
            $userMessageWithSystem = $userMessage . "\n\n" . $zonaPrompt;
            
            // Build context
            $context = [
                'user_name' => Auth::user()->name ?? 'User',
                'user_role' => Auth::user()->role ?? 'Member',
                'session_id' => $this->sessionId,
                'format' => 'html',
                'formatting_instructions' => $this->getFormattingInstructions()
            ];
            
            // LOG THE COMPLETE PROMPT
            Log::info('[PROCESS-WITH-CLI] FULL PROMPT TO CLAUDE CLI', [
                'session_id' => $this->sessionId,
                'original_user_message' => $userMessage,
                'zona_prompt_length' => strlen($zonaPrompt),
                'complete_prompt_length' => strlen($userMessageWithSystem),
                'complete_prompt' => $userMessageWithSystem,
                'context' => $context
            ]);
            
            // Send message to DirectClaudeCliService with Zona AI system prompt
            $response = $this->claudeService->sendMessage($userMessageWithSystem, $context);
            
            if ($response['success']) {
                // Format and add the response
                $formattedResponse = $this->formatClaudeResponse($response['message']);
                $this->addMessage($formattedResponse, 'ai');
                
                // Save interaction
                $this->saveInteraction($userMessage, $response['message']);
                
                Log::info('[PROCESS-WITH-CLI] Message processed successfully');
            } else {
                // Handle error
                $errorMessage = $response['message'] ?? 'Failed to get response';
                $this->addMessage($errorMessage, 'ai', true);
                
                Log::error('[PROCESS-WITH-CLI] Processing failed', [
                    'error' => $response['error'] ?? 'Unknown'
                ]);
            }
        } catch (\Exception $e) {
            Log::error('[PROCESS-WITH-CLI] Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->addMessage('Sorry, I encountered an error. Please try again.', 'ai', true);
        } finally {
            $this->isLoading = false;
        }
    }
    private function formatClaudeResponse($markdown)
    {
        // Convert markdown to HTML for better display
        // Basic conversion (you might want to use a proper markdown parser)
        $html = $markdown;
        
        // Convert headers
        $html = preg_replace('/^### (.*?)$/m', '<h3 class="text-lg font-semibold text-blue-600 mb-2">$1</h3>', $html);
        $html = preg_replace('/^## (.*?)$/m', '<h2 class="text-xl font-bold text-blue-700 mb-3">$1</h2>', $html);
        $html = preg_replace('/^# (.*?)$/m', '<h1 class="text-2xl font-bold text-blue-800 mb-4">$1</h1>', $html);
        
        // Convert bold and italic
        $html = preg_replace('/\*\*\*(.*?)\*\*\*/s', '<strong><em>$1</em></strong>', $html);
        $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $html);
        
        // Convert code blocks
        $html = preg_replace('/```(.*?)```/s', '<pre class="bg-gray-100 p-3 rounded-lg overflow-x-auto"><code>$1</code></pre>', $html);
        $html = preg_replace('/`(.*?)`/', '<code class="bg-gray-100 px-1 py-0.5 rounded text-sm">$1</code>', $html);
        
        // Convert lists
        $html = preg_replace('/^\- (.*?)$/m', '<li class="ml-4">$1</li>', $html);
        $html = preg_replace('/(<li.*?<\/li>\n?)+/s', '<ul class="list-disc list-inside mb-4">$0</ul>', $html);
        
        // Convert line breaks
        $html = nl2br($html);
        
        return $html;
    }

    private function addMessage($content, $sender, $isError = false)
    {
        // Process the content if it's from AI
        if ($sender === 'ai' && !$isError) {
            $content = $this->processAiContent($content);
        }
        
        $message = [
            'id' => uniqid('msg_', true),
            'content' => $content,
            'sender' => $sender,
            'isError' => $isError,
            'timestamp' => \Carbon\Carbon::now(),
            'liked' => false
        ];

        $this->messages[] = $message;

        $this->emit('messageAdded');
    }
    
    /**
     * Process AI content - expects pure HTML
     */
    private function processAiContent($content)
    {
        // LOG POINT 5: Log content processing
        Log::channel('ai_chat')->info('🔄 [CONTENT-PROCESSING] Processing AI Content', [
            'session_id' => $this->sessionId,
            'content_length' => strlen($content),
            'content_preview' => substr($content, 0, 200),
            'starts_with_html' => preg_match('/^\s*<[a-zA-Z]/', $content) ? 'YES' : 'NO',
            'timestamp' => now()->toIso8601String()
        ]);
        
        // Trim any whitespace
        $content = trim($content);
        
        // Validate that content is pure HTML
        $validationResult = $this->validatePureHtml($content);
        if (!$validationResult['valid']) {
            // LOG POINT 6: Log validation failure
            Log::channel('ai_chat')->warning('⚠️ [VALIDATION-FAILED] HTML Validation Failed', [
                'session_id' => $this->sessionId,
                'errors' => $validationResult['errors'],
                'content_sample' => substr($content, 0, 500),
                'will_retry' => true,
                'timestamp' => now()->toIso8601String()
            ]);
            
            // Request retry with pure HTML
            $this->requestHtmlRetry($validationResult['errors']);
            return '<div class="p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">Processing response... Please wait.</div>';
        }
        
        // LOG POINT 7: Log successful validation
        Log::channel('ai_chat')->info('✅ [VALIDATION-PASSED] HTML Validation Successful', [
            'session_id' => $this->sessionId,
            'content_is_pure_html' => true,
            'timestamp' => now()->toIso8601String()
        ]);
        
        // Check if content starts with HTML tag
        if (preg_match('/^<[a-zA-Z][^>]*>/', $content)) {
            // It's HTML, apply compact Tailwind classes and return
            $processedHtml = $this->ensureTailwindClasses($content);
            
            // LOG POINT 8: Log final processed HTML
            Log::channel('ai_chat')->info('🎯 [FINAL-HTML] Final Processed HTML', [
                'session_id' => $this->sessionId,
                'original_length' => strlen($content),
                'processed_length' => strlen($processedHtml),
                'has_compact_classes' => strpos($processedHtml, 'text-xs') !== false,
                'preview' => substr($processedHtml, 0, 300),
                'timestamp' => now()->toIso8601String()
            ]);
            
            return $processedHtml;
        }
        
        // Legacy: Check if content contains Blade template markers
        if (strpos($content, '[BLADE_TEMPLATE]') !== false && strpos($content, '[BLADE_DATA]') !== false) {
            return $this->processBladeTemplate($content);
        }
        
        // Legacy: Extract HTML code blocks (```html ... ```)
        $pattern = '/```html\s*(.*?)\s*```/s';
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[0] as $index => $fullMatch) {
                $htmlContent = $matches[1][$index];
                // Replace the code block with the actual HTML
                $content = str_replace($fullMatch, $htmlContent, $content);
            }
        }
        
        // Legacy: Process Markdown to HTML (but preserve existing HTML)
        // Check if content has HTML tags already
        if (!preg_match('/<[^>]+>/', $content)) {
            // No HTML tags found, convert Markdown to HTML
            $content = $this->convertMarkdownToHtml($content);
        } else {
            // Has HTML, but still process Markdown outside HTML tags
            $content = $this->processMarkdownWithHtml($content);
        }
        
        // Ensure Tailwind classes are properly applied
        $content = $this->ensureTailwindClasses($content);
        
        return $content;
    }
    
    /**
     * Convert Markdown to HTML
     */
    private function convertMarkdownToHtml($content)
    {
        // Convert headers with compact spacing
        $content = preg_replace('/^### (.*?)$/m', '<h3 class="text-sm font-semibold text-blue-900 mb-1">$1</h3>', $content);
        $content = preg_replace('/^## (.*?)$/m', '<h2 class="text-base font-bold text-blue-900 mb-1.5">$1</h2>', $content);
        $content = preg_replace('/^# (.*?)$/m', '<h1 class="text-lg font-bold text-blue-900 mb-2">$1</h1>', $content);
        
        // Convert bold and italic with smaller text
        $content = preg_replace('/\*\*\*(.*?)\*\*\*/s', '<strong class="font-semibold text-gray-800 text-xs"><em>$1</em></strong>', $content);
        $content = preg_replace('/\*\*(.*?)\*\*/s', '<strong class="font-semibold text-gray-800 text-xs">$1</strong>', $content);
        $content = preg_replace('/\*(.*?)\*/s', '<em class="italic text-xs">$1</em>', $content);
        
        // Convert bullet lists with compact spacing
        $content = preg_replace('/^\* (.*?)$/m', '<li class="ml-2 text-xs text-gray-700">$1</li>', $content);
        $content = preg_replace('/^\- (.*?)$/m', '<li class="ml-2 text-xs text-gray-700">$1</li>', $content);
        $content = preg_replace('/(<li.*?<\/li>\s*)+/s', '<ul class="list-disc list-inside mb-1 space-y-0.5 text-xs">$0</ul>', $content);
        
        // Convert numbered lists with compact spacing
        $content = preg_replace('/^\d+\. (.*?)$/m', '<li class="ml-2 text-xs text-gray-700">$1</li>', $content);
        
        // Convert line breaks to paragraphs (but not within HTML tags)
        $lines = explode("\n\n", $content);
        $processedLines = [];
        foreach ($lines as $line) {
            if (!empty(trim($line)) && !preg_match('/^<[^>]+>/', trim($line))) {
                $processedLines[] = '<p class="text-xs text-gray-700 mb-1">' . trim($line) . '</p>';
            } else {
                $processedLines[] = $line;
            }
        }
        $content = implode("\n", $processedLines);
        
        return $content;
    }
    
    /**
     * Process Markdown in content that already has HTML
     */
    private function processMarkdownWithHtml($content)
    {
        // Split content by HTML tags
        $parts = preg_split('/(<[^>]+>)/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);
        $processed = [];
        $inHtml = false;
        
        foreach ($parts as $part) {
            if (preg_match('/^<[^>]+>$/', $part)) {
                // This is an HTML tag
                $processed[] = $part;
                $inHtml = !preg_match('/^<\//', $part); // Check if it's a closing tag
            } else if (!$inHtml && !empty(trim($part))) {
                // Process Markdown in non-HTML parts
                $processed[] = $this->convertMarkdownToHtml($part);
            } else {
                $processed[] = $part;
            }
        }
        
        return implode('', $processed);
    }
    
    /**
     * Ensure Tailwind classes are present on HTML elements
     */
    private function ensureTailwindClasses($content)
    {
        // Add compact classes to tables
        $content = preg_replace('/<table(?![^>]*class)/', '<table class="min-w-full text-xs divide-y divide-gray-200 mb-1"', $content);
        $content = preg_replace('/<th(?![^>]*class)/', '<th class="px-1 py-0.5 text-xs font-medium text-left bg-blue-900 text-white"', $content);
        $content = preg_replace('/<td(?![^>]*class)/', '<td class="px-1 py-0.5 text-xs text-gray-700 border-b"', $content);
        
        // Add compact classes to cards
        $content = preg_replace('/<div class="card"/', '<div class="bg-white rounded-lg shadow-sm p-2 border border-gray-200 mb-1"', $content);
        
        // Process existing padding/margin classes to make them compact
        $content = str_replace('p-4', 'p-2', $content);
        $content = str_replace('p-6', 'p-3', $content);
        $content = str_replace('p-8', 'p-4', $content);
        $content = str_replace('px-4', 'px-2', $content);
        $content = str_replace('px-6', 'px-3', $content);
        $content = str_replace('py-4', 'py-1.5', $content);
        $content = str_replace('py-3', 'py-1', $content);
        $content = str_replace('mb-4', 'mb-2', $content);
        $content = str_replace('mb-3', 'mb-1.5', $content);
        $content = str_replace('mt-4', 'mt-2', $content);
        $content = str_replace('mt-3', 'mt-1.5', $content);
        $content = str_replace('text-base', 'text-sm', $content);
        $content = str_replace('text-lg', 'text-base', $content);
        $content = str_replace('text-xl', 'text-lg', $content);
        $content = str_replace('text-sm', 'text-xs', $content);
        
        return $content;
    }

    private function saveInteraction($userMessage, $aiResponse)
    {
        try {
            $this->memoryService->setSessionId($this->sessionId);
            
            // Add debugging
            Log::info('Saving interaction', [
                'session_id' => $this->sessionId,
                'user_id' => Auth::id(),
                'user_message_length' => strlen($userMessage),
                'ai_response_length' => !empty($aiResponse) ? strlen($aiResponse) : 0
            ]);
            
            $this->memoryService->addInteraction($userMessage, $aiResponse ?? 'No response generated', [
                'user_id' => Auth::id(),
                'user_permissions' => $this->getUserPermissions(),
                'session_id' => $this->sessionId
            ]);
            
            // Refresh conversation history after saving
            $this->loadConversationHistory();
            
            // Emit event for successful save
            $this->emit('conversationSaved');
            
            Log::info('Interaction saved successfully');
        } catch (\Exception $e) {
            Log::error('Failed to save interaction to memory', [
                'error' => $e->getMessage(),
                'user_message' => $userMessage,
                'ai_response' => $aiResponse,
                'trace' => $e->getTraceAsString()
            ]);
            
            // Emit event for failed save
            $this->emit('conversationSaveFailed');
        }
    }

    private function loadRecentMessages()
    {
        $this->memoryService->setSessionId($this->sessionId);
        $recentInteractions = $this->memoryService->getRecentInteractions(10);

        foreach ($recentInteractions as $interaction) {
            // Add user message
            $this->messages[] = [
                'id' => uniqid('msg_', true),
                'content' => $interaction['query'],
                'sender' => 'user',
                'isError' => false,
                'timestamp' => isset($interaction['metadata']['timestamp']) ? \Carbon\Carbon::parse($interaction['metadata']['timestamp']) : \Carbon\Carbon::now(),
                'liked' => false
            ];

            // Add AI response
            $this->messages[] = [
                'id' => uniqid('msg_', true),
                'content' => $interaction['response'],
                'sender' => 'ai',
                'isError' => false,
                'timestamp' => isset($interaction['metadata']['timestamp']) ? \Carbon\Carbon::parse($interaction['metadata']['timestamp']) : \Carbon\Carbon::now(),
                'liked' => false
            ];
        }
    }

    public function clearHistory()
    {
        $this->memoryService->clearSessionMemory($this->sessionId);
        $this->messages = [];
        $this->error = '';
        $this->emit('historyCleared');
    }

    public function newConversation()
    {
        $this->sessionId = uniqid('session_', true);
        $this->messages = [];
        $this->error = '';
        $this->memoryService->setSessionId($this->sessionId);
        
        // Clear context cache for new session (if needed)
        
        $this->emit('newConversationStarted');
    }

    public function loadConversation($conversationId)
    {
        // Load conversation from memory service
        $conversation = $this->memoryService->getConversation($conversationId);
        if ($conversation) {
            $this->messages = $conversation['messages'];
            $this->sessionId = $conversationId;
            $this->error = '';
        }
    }

    public function loadConversationHistory()
    {
        // Load recent conversations for sidebar
        $this->conversationHistory = $this->memoryService->getConversationList(10);
        
        Log::info('Loaded conversation history', [
            'user_id' => Auth::id(),
            'count' => count($this->conversationHistory),
            'conversations' => $this->conversationHistory
        ]);
    }

    public function copyMessage($messageId)
    {
        $message = collect($this->messages)->firstWhere('id', $messageId);
        if ($message) {
            $this->emit('copyToClipboard', $message['content']);
        }
    }

    public function likeMessage($messageId)
    {
        $messageIndex = collect($this->messages)->search(function($message) use ($messageId) {
            return $message['id'] === $messageId;
        });

        if ($messageIndex !== false) {
            $this->messages[$messageIndex]['liked'] = !$this->messages[$messageId]['liked'];
        }
    }

    public function toggleFavorite()
    {
        $this->isFavorite = !$this->isFavorite;
        // Save favorite status to memory service
        $this->memoryService->setConversationFavorite($this->sessionId, $this->isFavorite);
    }

    public function openSettings()
    {
        $this->showSettings = true;
    }

    public function closeSettings()
    {
        $this->showSettings = false;
    }

    public function saveSettings()
    {
        // Save settings to user preferences
        if (Auth::check()) {
            $user = Auth::user();
            // Store settings in session for now, or implement a proper settings table
            session(['ai_settings_' . $user->id => $this->settings]);
        }
        
        $this->closeSettings();
        $this->emit('settingsSaved');
    }

    public function attachFile()
    {
        $this->emit('openFileDialog');
    }

    public function uploadFile()
    {
        $this->emit('openFileUpload');
    }

    public function attachFileToMessage($fileName)
    {
        // Add file attachment to current message
        $this->message .= "\n[Attached file: {$fileName}]";
        $this->emit('fileAttached', $fileName);
    }

    public function updatedSearchQuery()
    {
        // Filter conversation history based on search query
        if (!empty($this->searchQuery)) {
            $this->conversationHistory = $this->memoryService->searchConversations($this->searchQuery);
        } else {
            $this->conversationHistory = $this->memoryService->getConversationList(10);
        }
    }

    private function getUserPermissions()
    {
        if (!Auth::check()) {
            return [];
        }

        $user = Auth::user();
        $permissions = [];

        // Add role-based permissions
        if (method_exists($user, 'roles')) {
            try {
                $roles = $user->roles->pluck('name')->toArray();
                $permissions = array_merge($permissions, $roles);
            } catch (\Exception $e) {
                // Method exists but might not be working as expected
            }
        }

        return $permissions;
    }

    public function updatedMessage()
    {
        // Ensure message is always a string
        if (!is_string($this->message)) {
            $this->message = '';
        }
        
        // Auto-resize textarea if needed
        $this->emit('resizeTextarea');
    }

    public function getMessageLengthProperty()
    {
        return is_string($this->message) ? strlen($this->message) : 0;
    }

    // Test method to manually save a conversation
    public function testSaveConversation()
    {
        try {
            $testMessage = 'Test message from ' . now();
            $testResponse = 'Test response from ' . now();
            
            $this->memoryService->setSessionId($this->sessionId);
            $result = $this->memoryService->addInteraction($testMessage, $testResponse, [
                'user_id' => Auth::id(),
                'user_permissions' => $this->getUserPermissions(),
                'session_id' => $this->sessionId
            ]);
            
            // Refresh conversation history
            $this->loadConversationHistory();
            
            Log::info('Test conversation saved', [
                'result' => $result,
                'conversation_count' => count($this->conversationHistory)
            ]);
            
            $this->emit('testConversationSaved', 'Test conversation saved successfully!');
            
        } catch (\Exception $e) {
            Log::error('Failed to save test conversation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->emit('testConversationFailed', 'Failed to save test conversation: ' . $e->getMessage());
        }
    }
    
    // Test method to verify HTML rendering
    public function testHtmlRendering()
    {
        $htmlResponse = '<div class="container mx-auto p-4">
            <h1 class="text-2xl font-bold text-blue-600 mb-4">Test HTML Response</h1>
            <div class="bg-white shadow-md rounded p-4">
                <h2 class="text-lg font-semibold mb-2">Sample Data</h2>
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="border border-gray-300 px-4 py-2">Column 1</th>
                            <th class="border border-gray-300 px-4 py-2">Column 2</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="border border-gray-300 px-4 py-2">Data 1</td>
                            <td class="border border-gray-300 px-4 py-2">Data 2</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>';
        
        $this->addMessage($htmlResponse, 'ai');
        
        Log::info('HTML test response added', [
            'html_length' => strlen($htmlResponse),
            'contains_html' => strpos($htmlResponse, '<') !== false
        ]);
    }
    
    // File upload methods
    public function updatedUploadedFiles()
    {
        $this->validate([
            'uploadedFiles.*' => 'file|max:10240', // 10MB max per file
        ]);
        
        $this->processUploadedFiles();
    }
    
    public function removeUploadedFile($index)
    {
        if (isset($this->uploadedFiles[$index])) {
            unset($this->uploadedFiles[$index]);
            $this->uploadedFiles = array_values($this->uploadedFiles);
        }
    }
    
    public function processUploadedFiles()
    {
        try {
            foreach ($this->uploadedFiles as $file) {
                // Generate unique filename
                $filename = Str::random(20) . '_' . $file->getClientOriginalName();
                
                // Store file in public/uploads directory
                $path = $file->storeAs('public/uploads', $filename);
                
                // Get public URL
                $publicPath = Storage::url($path);
                
                // Add to attached files
                $this->attachedFiles[] = [
                    'id' => Str::random(10),
                    'name' => $file->getClientOriginalName(),
                    'path' => $publicPath,
                    'fullPath' => storage_path('app/' . $path),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                    'uploadedAt' => now()
                ];
                
                Log::info('File uploaded successfully', [
                    'filename' => $filename,
                    'path' => $publicPath,
                    'size' => $file->getSize()
                ]);
            }
            
            // Clear temporary files
            $this->uploadedFiles = [];
            $this->showFileUpload = false;
            
            // Add file context to message
            $this->addFilesToMessageContext();
            
            $this->emit('filesUploaded', count($this->attachedFiles));
            
        } catch (\Exception $e) {
            Log::error('File upload failed', [
                'error' => $e->getMessage()
            ]);
            
            $this->error = 'Failed to upload files: ' . $e->getMessage();
        }
    }
    
    public function handleFileDrop($files)
    {
        $this->uploadedFiles = $files;
        $this->processUploadedFiles();
    }
    
    public function removeAttachment($fileId)
    {
        $this->attachedFiles = collect($this->attachedFiles)
            ->reject(function ($file) use ($fileId) {
                return $file['id'] === $fileId;
            })
            ->values()
            ->toArray();
            
        $this->addFilesToMessageContext();
    }
    
    public function clearAllAttachments()
    {
        $this->attachedFiles = [];
        $this->uploadedFiles = [];
    }
    
    private function addFilesToMessageContext()
    {
        if (empty($this->attachedFiles)) {
            return;
        }
        
        $fileContext = "\n\n**Attached Files:**\n";
        foreach ($this->attachedFiles as $file) {
            $fileContext .= "- {$file['name']} (Path: {$file['fullPath']})\n";
        }
        
        // This context will be added when sending the message
        $this->emit('filesContextUpdated', $fileContext);
    }
    
    public function toggleFileUpload()
    {
        $this->showFileUpload = !$this->showFileUpload;
    }
    
    public function sendMessageWithFiles()
    {
        if (empty(trim($this->message)) && empty($this->attachedFiles)) {
            return;
        }
        
        $originalMessage = trim($this->message);
        $userMessage = $originalMessage;
        
        // Add file context if files are attached
        if (!empty($this->attachedFiles)) {
            $userMessage .= "\n\nAttached files for context:";
            foreach ($this->attachedFiles as $file) {
                $userMessage .= "\n- File: {$file['name']} at path: {$file['fullPath']}";
                $userMessage .= "\n  Please read this file and use it as context for answering my questions.";
            }
        }
        
        // Clear message input
        $this->message = '';
        
        // Add user message to chat (show without file paths for clean UI)
        $displayMessage = $originalMessage;
        if (!empty($this->attachedFiles)) {
            $fileCount = count($this->attachedFiles);
            $displayMessage = $originalMessage ? $originalMessage . "\n📎 " . $fileCount . " file(s) attached" : "📎 " . $fileCount . " file(s) attached";
        }
        $this->addMessage($displayMessage, 'user');
        
        // Process with AI (includes file context and Zona AI system prompt will be added in processAiResponse)
        $this->processAiResponse($userMessage);
        
        // Clear attachments after sending
        $this->clearAllAttachments();
    }
    
    
    // Send quick message from welcome suggestions
    public function sendQuickMessage($message)
    {
        $this->message = $message;
        $this->sendMessage();
    }
    
    // Clear chat messages
    public function clearChat()
    {
        $this->messages = [];
        $this->clearAllAttachments();
        $this->error = '';
        $this->emit('chatCleared');
    }
    
    // Export chat conversation
    public function exportChat()
    {
        try {
            $filename = 'chat_export_' . date('Y-m-d_H-i-s') . '.txt';
            $content = "SACCOS AI Chat Export\n";
            $content .= "Date: " . date('Y-m-d H:i:s') . "\n";
            $content .= "Session ID: " . $this->sessionId . "\n";
            $content .= str_repeat('=', 50) . "\n\n";
            
            foreach ($this->messages as $message) {
                $sender = $message['sender'] === 'user' ? 'USER' : 'AI';
                $content .= "[{$sender}] {$message['timestamp']}\n";
                $content .= strip_tags($message['content']) . "\n";
                $content .= str_repeat('-', 30) . "\n\n";
            }
            
            // Save to public/exports directory
            $exportPath = 'public/exports/' . $filename;
            Storage::makeDirectory('public/exports');
            Storage::put($exportPath, $content);
            
            // Get download URL
            $downloadUrl = Storage::url($exportPath);
            
            $this->emit('chatExported', $downloadUrl);
            
        } catch (\Exception $e) {
            Log::error('Failed to export chat', [
                'error' => $e->getMessage()
            ]);
            $this->error = 'Failed to export chat: ' . $e->getMessage();
        }
    }
    
    // Toggle voice input
    public function toggleVoiceInput()
    {
        $this->isRecording = !$this->isRecording;
        $this->emit('toggleVoiceRecording');
    }
    
    // Clear message input
    public function clearMessage()
    {
        $this->message = '';
    }
    
    // Toggle templates
    public function toggleTemplates()
    {
        $this->emit('showTemplates');
    }
    
    /**
     * Process Blade template from AI response
     */
    private function processBladeTemplate($content)
    {
        try {
            // Extract Blade template
            preg_match('/\[BLADE_TEMPLATE\]\s*(.*?)\s*\[\/BLADE_TEMPLATE\]/s', $content, $templateMatch);
            
            // Extract Blade data
            preg_match('/\[BLADE_DATA\]\s*(.*?)\s*\[\/BLADE_DATA\]/s', $content, $dataMatch);
            
            if (!empty($templateMatch[1])) {
                $bladeTemplate = trim($templateMatch[1]);
                
                // Validate Blade template syntax
                $validationResult = $this->validateBladeTemplate($bladeTemplate);
                if (!$validationResult['valid']) {
                    // Template is malformed, request retry
                    $this->requestBladeRetry($validationResult['errors']);
                    return '<div class="p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">Processing response... Please wait.</div>';
                }
                
                // Parse data if provided
                $data = [];
                if (!empty($dataMatch[1])) {
                    $dataString = trim($dataMatch[1]);
                    // Safely evaluate the PHP array
                    // Remove PHP opening tags if present
                    $dataString = preg_replace('/^<\?php\s*/', '', $dataString);
                    $dataString = preg_replace('/\?>$/', '', $dataString);
                    
                    // Try to parse as JSON first (safer)
                    $jsonData = json_decode($dataString, true);
                    if ($jsonData !== null) {
                        $data = $jsonData;
                    } else {
                        // Fallback: Try to evaluate as PHP array (be careful with this)
                        // Only allow array syntax for safety
                        if (preg_match('/^\[.*\]$/s', $dataString) || preg_match('/^array\s*\(.*\)$/s', $dataString)) {
                            try {
                                eval('$data = ' . $dataString . ';');
                            } catch (\Exception $e) {
                                Log::warning('Could not parse Blade data', ['error' => $e->getMessage()]);
                                // Request retry with better data format
                                $this->requestBladeRetry(['Data must be valid JSON or PHP array format']);
                                return '<div class="p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">Processing response... Please wait.</div>';
                            }
                        }
                    }
                }
                
                // Add default data if needed
                $data = array_merge([
                    'members' => [],
                    'items' => [],
                    'stats' => [],
                    'headers' => [],
                    'rows' => [],
                    'actions' => [],
                    'title' => 'Results'
                ], $data);
                
                // Try to render the Blade template with data
                try {
                    $html = Blade::render($bladeTemplate, $data);
                    
                    // Check if rendered HTML is valid
                    if (empty(trim($html))) {
                        throw new \Exception('Rendered template is empty');
                    }
                    
                    // Apply compact Tailwind classes to the rendered HTML
                    return $this->ensureTailwindClasses($html);
                } catch (\Exception $renderError) {
                    Log::error('Blade render error', ['error' => $renderError->getMessage()]);
                    // Request retry with specific error
                    $this->requestBladeRetry(['Blade rendering failed: ' . $renderError->getMessage()]);
                    return '<div class="p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">Processing response... Please wait.</div>';
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing Blade template', [
                'error' => $e->getMessage(),
                'content' => substr($content, 0, 500)
            ]);
            
            // Request retry for general errors
            $this->requestBladeRetry(['Template processing failed: ' . $e->getMessage()]);
            return '<div class="p-2 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">Processing response... Please wait.</div>';
        }
        
        // Fallback to original processing if Blade template fails
        return $this->convertMarkdownToHtml($content);
    }
    
    /**
     * Validate Blade template syntax
     */
    private function validateBladeTemplate($template)
    {
        $errors = [];
        $valid = true;
        
        // Check for basic Blade directive balance
        $directives = [
            'foreach' => 'endforeach',
            'if' => 'endif',
            'for' => 'endfor',
            'while' => 'endwhile',
            'forelse' => 'endforelse',
            'isset' => 'endisset',
            'empty' => 'endempty'
        ];
        
        foreach ($directives as $open => $close) {
            $openCount = substr_count($template, '@' . $open);
            $closeCount = substr_count($template, '@' . $close);
            
            if ($openCount !== $closeCount) {
                $errors[] = "Unbalanced @{$open}/@{$close} directives (found {$openCount} @{$open} and {$closeCount} @{$close})";
                $valid = false;
            }
        }
        
        // Check for unclosed HTML tags
        $htmlTags = ['div', 'table', 'thead', 'tbody', 'tr', 'td', 'th', 'ul', 'li', 'span', 'p', 'h1', 'h2', 'h3', 'h4'];
        foreach ($htmlTags as $tag) {
            $openTags = preg_match_all('/<' . $tag . '[^>]*>/i', $template);
            $closeTags = preg_match_all('/<\/' . $tag . '>/i', $template);
            
            if ($openTags !== $closeTags) {
                $errors[] = "Unbalanced <{$tag}> tags (found {$openTags} opening and {$closeTags} closing)";
                $valid = false;
            }
        }
        
        // Check for proper Blade variable syntax
        if (preg_match('/{{[^}]*{{/', $template)) {
            $errors[] = 'Nested {{ }} brackets detected';
            $valid = false;
        }
        
        // Check for required Tailwind classes
        if (!preg_match('/text-xs|text-sm/', $template)) {
            $errors[] = 'Missing compact text sizing (text-xs or text-sm)';
            $valid = false;
        }
        
        if (preg_match('/p-6|p-8|px-6|py-4/', $template)) {
            $errors[] = 'Large padding detected. Use compact spacing (px-2 py-1)';
            $valid = false;
        }
        
        return ['valid' => $valid, 'errors' => $errors];
    }
    
    /**
     * Request Claude to retry with proper Blade template
     */
    private function requestBladeRetry($errors)
    {
        // Check if we've exceeded max retries
        if ($this->retryCount >= $this->maxRetries) {
            Log::warning('Max retries reached for Blade template, stopping retry loop', [
                'retry_count' => $this->retryCount,
                'session_id' => $this->sessionId
            ]);
            
            // Reset retry count for next message
            $this->retryCount = 0;
            $this->retryRequired = false;
            $this->retryErrors = [];
            
            // Add an error message to the user
            $this->addMessage('Sorry, I\'m having trouble formatting the response. Please try again.', 'ai', true);
            $this->isLoading = false;
            return;
        }
        
        // Increment retry count
        $this->retryCount++;
        
        // Store the retry request in session
        $this->retryRequired = true;
        $this->retryErrors = $errors;
        
        // Log the retry request
        Log::warning('Requesting Blade template retry', [
            'errors' => $errors,
            'session_id' => $this->sessionId,
            'retry_attempt' => $this->retryCount
        ]);
        
        // Get the last user message to retry
        $lastUserMessage = null;
        foreach (array_reverse($this->messages) as $message) {
            if ($message['sender'] === 'user') {
                $lastUserMessage = $message['content'];
                break;
            }
        }
        
        if ($lastUserMessage) {
            // Create retry message with specific error feedback
            $retryMessage = $lastUserMessage . "\n\n[SYSTEM: Your previous Blade template had errors:\n" . 
                           implode("\n", $errors) . 
                           "\nPlease provide a properly formatted Blade template following the compact Tailwind CSS rules.]";
            
            // Process the retry
            $this->isLoading = true;
            $this->processAiResponse($retryMessage);
        }
    }
    
    /**
     * Validate that content is pure HTML with no text outside tags
     */
    private function validatePureHtml($content)
    {
        $errors = [];
        $valid = true;
        
        // Trim whitespace
        $content = trim($content);
        
        // Check if content starts with <div
        if (!preg_match('/^<div/i', $content)) {
            $errors[] = 'Response must start with <div>';
            $valid = false;
        }
        
        // Check if content ends with </div>
        if (!preg_match('/<\/div>\s*$/i', $content)) {
            $errors[] = 'Response must end with </div>';
            $valid = false;
        }
        
        // Check if content has markdown code blocks
        if (preg_match('/```/', $content)) {
            $errors[] = 'Contains markdown code blocks (```). Return pure HTML only.';
            $valid = false;
        }
        
        return ['valid' => $valid, 'errors' => $errors];
    }
    
    /**
     * Request Claude to retry with pure HTML
     */
    private function requestHtmlRetry($errors)
    {
        // Check if we've exceeded max retries
        if ($this->retryCount >= $this->maxRetries) {
            Log::warning('Max retries reached, stopping retry loop', [
                'retry_count' => $this->retryCount,
                'session_id' => $this->sessionId
            ]);
            
            // Reset retry count for next message
            $this->retryCount = 0;
            $this->retryRequired = false;
            $this->retryErrors = [];
            
            // Add an error message to the user
            $this->addMessage('Sorry, I\'m having trouble connecting to the AI service. Please try again later.', 'ai', true);
            $this->isLoading = false;
            return;
        }
        
        // Increment retry count
        $this->retryCount++;
        
        // Store the retry request
        $this->retryRequired = true;
        $this->retryErrors = $errors;
        
        // Log the retry request
        Log::warning('Requesting pure HTML retry', [
            'errors' => $errors,
            'session_id' => $this->sessionId,
            'retry_attempt' => $this->retryCount
        ]);
        
        // Get the last user message to retry
        $lastUserMessage = null;
        foreach (array_reverse($this->messages) as $message) {
            if ($message['sender'] === 'user') {
                $lastUserMessage = $message['content'];
                break;
            }
        }
        
        if ($lastUserMessage) {
            // Create retry message with specific error feedback
            $retryMessage = $lastUserMessage . "\n\n[SYSTEM ERROR: Your response must be ONLY HTML.\n" . 
                           "Errors found:\n" . implode("\n", $errors) . 
                           "\n\nCRITICAL: Return ONLY HTML starting with <div> and ending with </div>. " .
                           "NO text before or after the HTML. NO markdown. NO explanations.]";
            
            // Process the retry
            $this->isLoading = true;
            $this->processAiResponse($retryMessage);
        }
    }
    
    /**
     * Get formatting instructions for AI responses
     */
    private function getFormattingInstructions()
    {
        return '
CRITICAL: Return ONLY HTML.
1. Start with <div>
2. End with </div>
3. NO text before or after the HTML
4. NO markdown (no ```)
5. Just pure HTML

Example:
<div>Your content here</div>

That\'s it. Start with <div>, end with </div>. Nothing else.';
    }
    
    /**
     * Get Zona AI system prompt
     */
    private function getZonaAiSystemPrompt()
    {
        return '
Zona AI System Prompt

You are Zona AI, the SACCOS System AI Assistant.
You are running inside this Laravel project:

/Volumes/DATA/PROJECTS/SACCOS/SYSTEMS/SACCOS_CORE_SYSTEM/

Execution Rules

Load Environment Credentials

Retrieve database and system credentials from the .env file.

Database Access

You are allowed to run all necessary and non-dangerous queries on the connected database.

Examples of allowed queries:

SELECT queries to fetch data

JOIN, COUNT, SUM, AVG, etc. for reports

Safe lookups needed to answer user questions

Never run destructive queries (DROP, DELETE, TRUNCATE, schema changes, etc.).

Context Usage

Before answering, determine if additional domain/system knowledge is required.

If yes, load the file:

zona_ai/context.md

Only load this file when necessary to provide a correct and complete response.

Answering User Queries

Use:

.env credentials

Database queries (safe only)

(optional) zona_ai/context.md content

If the question cannot be answered with these sources, respond with:

Insufficient information to answer this question.

Constraints

Do not invent or hallucinate information outside the defined sources.

Always prefer accuracy and reliability over guessing.

Only run safe queries and never perform destructive operations.';
    }
    
    // Toggle settings
    public function toggleSettings()
    {
        $this->showSettings = !$this->showSettings;
    }
    
    // Regenerate AI response for a message
    public function regenerateResponse($messageId)
    {
        $messageIndex = collect($this->messages)->search(function($msg) use ($messageId) {
            return $msg['id'] === $messageId;
        });
        
        if ($messageIndex !== false && $messageIndex > 0) {
            // Get the previous user message
            $userMessage = null;
            for ($i = $messageIndex - 1; $i >= 0; $i--) {
                if ($this->messages[$i]['sender'] === 'user') {
                    $userMessage = strip_tags($this->messages[$i]['content']);
                    break;
                }
            }
            
            if ($userMessage) {
                // Remove the AI message
                array_splice($this->messages, $messageIndex, 1);
                
                // Regenerate response
                $this->processAiResponse($userMessage);
            }
        }
    }
    
    /**
     * Listen for streaming updates from Claude
     */
    public function listenForStreamingUpdates()
    {
        // This method will be called from JavaScript via Livewire
        // to check for streaming updates
        if ($this->isStreaming && $this->streamingMessageId) {
            $streamKey = "claude_stream_{$this->sessionId}";
            $currentStream = \Cache::get($streamKey, '');
            
            if (!empty($currentStream) && $currentStream !== $this->streamBuffer) {
                // Get the new chunk
                $newChunk = substr($currentStream, strlen($this->streamBuffer));
                $this->streamBuffer = $currentStream;
                
                // Update the message in the messages array
                foreach ($this->messages as &$msg) {
                    if (isset($msg['id']) && $msg['id'] === $this->streamingMessageId) {
                        $msg['content'] = $this->formatClaudeResponse($this->streamBuffer);
                        break;
                    }
                }
                
                // Emit update
                $this->emit('streamUpdate', $this->streamingMessageId, $newChunk);
            }
        }
    }
    
    /**
     * Complete streaming for current message
     */
    public function completeStreaming()
    {
        if ($this->isStreaming && $this->streamingMessageId) {
            // Mark message as complete
            foreach ($this->messages as &$msg) {
                if (isset($msg['id']) && $msg['id'] === $this->streamingMessageId) {
                    unset($msg['isStreaming']);
                    
                    // Save the interaction if buffer has content
                    if (!empty($this->streamBuffer)) {
                        // Find the user message
                        $userMessage = '';
                        for ($i = count($this->messages) - 2; $i >= 0; $i--) {
                            if ($this->messages[$i]['sender'] === 'user') {
                                $userMessage = strip_tags($this->messages[$i]['content']);
                                break;
                            }
                        }
                        
                        if ($userMessage) {
                            $this->saveInteraction($userMessage, $this->streamBuffer);
                        }
                    }
                    break;
                }
            }
            
            // Clear streaming state
            $this->isStreaming = false;
            $this->streamingMessageId = null;
            $this->streamBuffer = '';
            
            // Clear cache
            $streamKey = "claude_stream_{$this->sessionId}";
            \Cache::forget($streamKey);
            
            // Emit completion
            $this->emit('streamComplete');
        }
    }
    
    /**
     * Enable streaming mode for next request
     */
    public function enableStreaming()
    {
        // Pre-warm Claude process for faster streaming
        try {
            $service = app(LocalClaudeService::class);
            $service->prewarm();
            
            Log::info('Claude pre-warmed for streaming', [
                'session_id' => $this->sessionId
            ]);
        } catch (\Exception $e) {
            Log::warning('Failed to pre-warm Claude', [
                'error' => $e->getMessage()
            ]);
        }
    }
} 