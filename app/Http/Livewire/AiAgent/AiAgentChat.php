<?php

namespace App\Http\Livewire\AiAgent;

use Livewire\Component;
use App\Services\AiAgentService;
use App\Services\AiMemoryService;
use App\Services\AiValidationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AiAgentChat extends Component
{
    public $message = '';
    public $messages = [];
    public $isLoading = false;
    public $sessionId;
    public $error = '';
    public $searchQuery = '';
    public $showSettings = false;
    public $isFavorite = false;
    public $conversationHistory = [];
    public $settings = [
        'responseLength' => 'medium',
        'autoSave' => true
    ];

    protected $aiAgentService;
    protected $memoryService;
    protected $validationService;

    protected $listeners = ['sendMessage' => 'processMessage'];

    public function mount()
    {
        $this->sessionId = uniqid('session_', true);
        $this->loadRecentMessages();
        $this->loadConversationHistory();
    }

    public function boot(
        AiAgentService $aiAgentService,
        AiMemoryService $memoryService,
        AiValidationService $validationService
    ) {
        $this->aiAgentService = $aiAgentService;
        $this->memoryService = $memoryService;
        $this->validationService = $validationService;
    }

    public function render()
    {
        return view('livewire.ai-agent.ai-agent-chat');
    }

    public function sendMessage()
    {
        if (empty(trim($this->message)) || $this->isLoading) {
            return;
        }

        $userMessage = trim($this->message);
        $this->message = '';

        // Add user message to chat
        $this->addMessage($userMessage, 'user');

        // Process with AI
        $this->processAiResponse($userMessage);
    }

    private function processAiResponse($userMessage)
    {
        $this->isLoading = true;
        $this->error = '';

        try {
            // Validate input
            $validation = $this->validationService->validateQuery($userMessage, [
                'user_id' => Auth::id(),
                'user_permissions' => $this->getUserPermissions(),
                'session_id' => $this->sessionId
            ]);

            if (!$validation['valid']) {
                $this->addMessage('I cannot process that request due to security concerns.', 'ai', true);
                $this->isLoading = false;
                return;
            }

            // Use the new unified approach that handles both data and non-data questions
            $context = [
                'user_id' => Auth::id(),
                'user_permissions' => $this->getUserPermissions(),
                'session_id' => $this->sessionId
            ];

            $response = $this->aiAgentService->processRequest($userMessage, $context);
            
            // Handle different response formats
            if (is_array($response)) {
                // If response is an array, extract the main response
                $aiResponse = $response['response'] ?? $response['answer'] ?? $response['data'] ?? '';
                
                // Check if AI is requesting context
                if (trim($aiResponse) === 'CONTEXT-REQUEST' || stripos($aiResponse, 'CONTEXT-REQUEST') !== false) {
                    Log::info('[AI Context Request Received]', [
                        'session_id' => $this->sessionId,
                        'user_message' => $userMessage,
                        'ai_response' => $aiResponse
                    ]);
                    
                    // Force context refresh and retry the request
                    $this->aiAgentService->forceContextRefresh($this->sessionId, 'ai_context_request');
                    
                    // Retry the request with fresh context
                    $context['force_context_refresh'] = true;
                    $retryResponse = $this->aiAgentService->processRequest($userMessage, $context);
                    
                    if (is_array($retryResponse)) {
                        $aiResponse = $retryResponse['response'] ?? $retryResponse['answer'] ?? $retryResponse['data'] ?? '';
                        
                        // Add SQL details if available
                        if (isset($retryResponse['sql_queries']) && !empty($retryResponse['sql_queries'])) {
                            $aiResponse .= "\n\n**SQL Queries Used:**\n";
                            foreach ($retryResponse['sql_queries'] as $index => $sql) {
                                $aiResponse .= ($index + 1) . ". `" . $sql . "`\n";
                            }
                        }
                    } else {
                        $aiResponse = $retryResponse;
                    }
                    
                    Log::info('[AI Context Request Retry Complete]', [
                        'session_id' => $this->sessionId,
                        'retry_response_length' => strlen($aiResponse)
                    ]);
                } else {
                    // Add SQL details if available
                    if (isset($response['sql_queries']) && !empty($response['sql_queries'])) {
                        $aiResponse .= "\n\n**SQL Queries Used:**\n";
                        foreach ($response['sql_queries'] as $index => $sql) {
                            $aiResponse .= ($index + 1) . ". `" . $sql . "`\n";
                        }
                    }
                }
            } else {
                // If response is a string, check for context request
                if (!empty($response) && (trim($response) === 'CONTEXT-REQUEST' || stripos($response, 'CONTEXT-REQUEST') !== false)) {
                    Log::info('[AI Context Request Received - String Response]', [
                        'session_id' => $this->sessionId,
                        'user_message' => $userMessage,
                        'ai_response' => $response
                    ]);
                    
                    // Force context refresh and retry
                    $this->aiAgentService->forceContextRefresh($this->sessionId, 'ai_context_request');
                    $context['force_context_refresh'] = true;
                    $aiResponse = $this->aiAgentService->processRequest($userMessage, $context);
                } else {
                    $aiResponse = $response;
                }
            }
            
            // Log the response for debugging
            Log::info('AI Response received', [
                'user_message' => $userMessage,
                'response_type' => gettype($response),
                'response_length' => !empty($aiResponse) && is_string($aiResponse) ? strlen($aiResponse) : 0,
                'is_html' => !empty($aiResponse) && is_string($aiResponse) && (strpos($aiResponse, '<') !== false),
                'session_id' => $this->sessionId
            ]);
            
            $this->addMessage($aiResponse ?? 'No response generated', 'ai');
            
            // Save the interaction to memory
            $this->saveInteraction($userMessage, $aiResponse);

        } catch (\Exception $e) {
            $this->error = 'An error occurred while processing your request.';
            $this->addMessage('Sorry, I encountered an error. Please try again.', 'ai', true);
        }

        $this->isLoading = false;
    }

    private function addMessage($content, $sender, $isError = false)
    {
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
        
        // Clear context cache for new session
        $this->aiAgentService->clearContextCache($this->sessionId);
        
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
} 