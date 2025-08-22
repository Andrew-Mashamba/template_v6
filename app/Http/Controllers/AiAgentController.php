<?php

namespace App\Http\Controllers;

use App\Services\AiAgentService;
use App\Services\AiProviderService;
use App\Services\AiMemoryService;
use App\Services\AiValidationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Validation\ValidationException;

class AiAgentController extends Controller
{
    protected $aiAgentService;
    protected $providerService;
    protected $memoryService;
    protected $validationService;

    public function __construct(
        AiAgentService $aiAgentService,
        AiProviderService $providerService,
        AiMemoryService $memoryService,
        AiValidationService $validationService
    ) {
        $this->aiAgentService = $aiAgentService;
        $this->providerService = $providerService;
        $this->memoryService = $memoryService;
        $this->validationService = $validationService;
    }

    /**
     * Process AI request
     */
    public function processRequest(Request $request): JsonResponse
    {
        try {
            // Validate request
            $request->validate([
                'query' => 'required|string|max:10000',
                'context' => 'array',
                'options' => 'array'
            ]);

            $query = $request->input('query');
            $context = $request->input('context', []);
            $options = $request->input('options', []);

            // Add user context
            $context['user_id'] = Auth::id();
            $context['user_permissions'] = $this->getUserPermissions();
            $context['session_id'] = $this->memoryService->getSessionId();

            // Validate input
            $validation = $this->validationService->validateQuery($query, $context);
            if (!$validation['valid']) {
                $this->validationService->logValidationFailure('query', $query, $validation['errors'], $context);
                return response()->json([
                    'success' => false,
                    'errors' => $validation['errors']
                ], 400);
            }

            // Sanitize input
            $query = $this->validationService->sanitizeInput($query);

            // Process request
            $response = $this->aiAgentService->processRequest($query, $context, $options);

            // Validate response
            $responseValidation = $this->validationService->validateResponse($response['response'], $query);
            if (!$responseValidation['valid']) {
                $this->validationService->logValidationFailure('response', $response['response'], $responseValidation['errors'], $context);
                return response()->json([
                    'success' => false,
                    'errors' => $responseValidation['errors']
                ], 400);
            }

            // Store in memory
            $this->memoryService->addInteraction($query, $response['response'], $context, [
                'provider' => $response['provider'] ?? 'unknown',
                'response_time' => $response['usage']['total_time'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'data' => $response
            ]);

        } catch (Exception $e) {
            Log::error('AI Agent Request Failed', [
                'error' => $e->getMessage(),
                'query' => $request->input('query'),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing your request'
            ], 500);
        }
    }

    /**
     * Process question using SQL-first approach
     */
    public function processQuestionWithSql(Request $request): JsonResponse
    {
        try {
            // Validate request
            $request->validate([
                'question' => 'required|string|max:10000',
                'context' => 'array',
                'options' => 'array'
            ]);

            $question = $request->input('question');
            $context = $request->input('context', []);
            $options = $request->input('options', []);

            // Add user context
            $context['user_id'] = Auth::id();
            $context['user_permissions'] = $this->getUserPermissions();
            $context['session_id'] = $this->memoryService->getSessionId();

            // Validate input
            $validation = $this->validationService->validateQuery($question, $context);
            if (!$validation['valid']) {
                $this->validationService->logValidationFailure('question', $question, $validation['errors'], $context);
                return response()->json([
                    'success' => false,
                    'errors' => $validation['errors']
                ], 400);
            }

            // Sanitize input
            $question = $this->validationService->sanitizeInput($question);

            // Process question using SQL-first approach
            $response = $this->aiAgentService->processQuestionWithSql($question, $context, $options);

            // Store in memory
            $this->memoryService->addInteraction($question, $response['response'], $context, [
                'method' => 'sql_first',
                'sql_queries' => $response['sql_queries'] ?? [],
                'sql_results' => $response['sql_results'] ?? []
            ]);

            return response()->json([
                'success' => true,
                'data' => $response
            ]);

        } catch (Exception $e) {
            Log::error('AI SQL-First Request Failed', [
                'error' => $e->getMessage(),
                'question' => $request->input('question'),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while processing your question'
            ], 500);
        }
    }

    /**
     * Execute SQL query
     */
    public function executeSql(Request $request): JsonResponse
    {
        try {
            // Validate request
            $request->validate([
                'sql' => 'required|string|max:5000',
                'params' => 'array'
            ]);

            $sql = $request->input('sql');
            $params = $request->input('params', []);

            // Check permissions
            if (!Auth::user()->can('execute_sql')) {
                return response()->json([
                    'success' => false,
                    'error' => 'Insufficient permissions'
                ], 403);
            }

            // Validate SQL
            $validation = $this->validationService->validateSqlQuery($sql, [
                'user_permissions' => $this->getUserPermissions()
            ]);

            if (!$validation['valid']) {
                return response()->json([
                    'success' => false,
                    'errors' => $validation['errors']
                ], 400);
            }

            // Execute SQL
            $result = $this->aiAgentService->executeSqlQuery($sql, $params);

            return response()->json([
                'success' => $result['success'],
                'data' => $result['data'] ?? null,
                'error' => $result['error'] ?? null
            ]);

        } catch (Exception $e) {
            Log::error('SQL Execution Failed', [
                'error' => $e->getMessage(),
                'sql' => $request->input('sql'),
                'user_id' => Auth::id()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'An error occurred while executing the SQL query'
            ], 500);
        }
    }

    /**
     * Get provider status
     */
    public function getProviderStatus(): JsonResponse
    {
        try {
            $providers = $this->providerService->getAllProviders();
            $status = [];

            foreach ($providers as $name => $provider) {
                $status[$name] = [
                    'name' => $provider['name'],
                    'enabled' => $provider['enabled'],
                    'healthy' => $this->providerService->isProviderHealthy($name),
                    'stats' => $this->providerService->getProviderStats($name)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $status
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get provider status', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get provider status'
            ], 500);
        }
    }

    /**
     * Test provider
     */
    public function testProvider(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'provider' => 'required|string',
                'message' => 'string|max:1000'
            ]);

            $provider = $request->input('provider');
            $message = $request->input('message', 'Hello, this is a test message.');

            $result = $this->providerService->testProvider($provider, $message);

            return response()->json([
                'success' => $result['success'],
                'data' => $result
            ]);

        } catch (Exception $e) {
            Log::error('Provider test failed', [
                'error' => $e->getMessage(),
                'provider' => $request->input('provider')
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Provider test failed'
            ], 500);
        }
    }

    /**
     * Get conversation history
     */
    public function getConversationHistory(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->input('session_id');
            $limit = $request->input('limit', 10);

            $interactions = $this->memoryService->getRecentInteractions($limit);

            return response()->json([
                'success' => true,
                'data' => $interactions
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get conversation history', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get conversation history'
            ], 500);
        }
    }

    /**
     * Search interactions
     */
    public function searchInteractions(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'query' => 'string|max:1000',
                'filters' => 'array'
            ]);

            $query = $request->input('query', '');
            $filters = $request->input('filters', []);

            $results = $this->memoryService->searchInteractions($query, $filters);

            return response()->json([
                'success' => true,
                'data' => $results
            ]);

        } catch (Exception $e) {
            Log::error('Failed to search interactions', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to search interactions'
            ], 500);
        }
    }

    /**
     * Clear conversation history
     */
    public function clearHistory(Request $request): JsonResponse
    {
        try {
            $sessionId = $request->input('session_id');
            
            $this->memoryService->clearSessionMemory($sessionId);

            return response()->json([
                'success' => true,
                'message' => 'Conversation history cleared successfully'
            ]);

        } catch (Exception $e) {
            Log::error('Failed to clear conversation history', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to clear conversation history'
            ], 500);
        }
    }

    /**
     * Get AI agent statistics
     */
    public function getStats(Request $request): JsonResponse
    {
        try {
            $timeframe = $request->input('timeframe', '24h');
            
            $stats = [
                'validation_stats' => $this->validationService->getValidationStats($timeframe),
                'memory_stats' => $this->memoryService->getStats(),
                'provider_stats' => $this->getProviderStats()
            ];

            return response()->json([
                'success' => true,
                'data' => $stats
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get AI agent stats', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get statistics'
            ], 500);
        }
    }

    /**
     * Get provider statistics
     */
    private function getProviderStats()
    {
        $providers = $this->providerService->getAllProviders();
        $stats = [];

        foreach ($providers as $name => $provider) {
            $stats[$name] = $this->providerService->getProviderStats($name);
        }

        return $stats;
    }

    /**
     * Get user permissions
     */
    private function getUserPermissions()
    {
        if (!Auth::check()) {
            return [];
        }

        $user = Auth::user();
        $permissions = [];

        // Get user permissions from roles/permissions system
        if (method_exists($user, 'getAllPermissions')) {
            $permissions = $user->getAllPermissions()->pluck('name')->toArray();
        }

        // Add role-based permissions
        if (method_exists($user, 'roles')) {
            $roles = $user->roles->pluck('name')->toArray();
            $permissions = array_merge($permissions, $roles);
        }

        return $permissions;
    }

    /**
     * Show AI agent dashboard
     */
    public function dashboard()
    {
        try {
            $data = [
                'providers' => $this->providerService->getAllProviders(),
                'recent_interactions' => $this->memoryService->getRecentInteractions(5),
                'stats' => $this->validationService->getValidationStats('24h')
            ];

            return view('ai-agent.dashboard', $data);

        } catch (Exception $e) {
            Log::error('Failed to load AI agent dashboard', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to load dashboard');
        }
    }

    /**
     * Show conversation interface
     */
    public function conversation()
    {
        try {
            $data = [
                'session_id' => $this->memoryService->getSessionId(),
                'recent_interactions' => $this->memoryService->getRecentInteractions(10)
            ];

            return view('ai-agent.conversation', $data);

        } catch (Exception $e) {
            Log::error('Failed to load conversation interface', [
                'error' => $e->getMessage()
            ]);

            return redirect()->back()->with('error', 'Failed to load conversation interface');
        }
    }

    /**
     * Handle AI agent questions from the frontend
     */
    public function ask(Request $request)
    {
        try {
            // Validate the request
            $request->validate([
                'question' => 'required|string|max:1000'
            ]);

            $question = $request->input('question');
            
            // Get user context
            $context = [
                'user_id' => auth()->id(),
                'user_permissions' => [],
                'session_id' => session()->getId()
            ];

            // Process the question using the AI agent service
            $response = $this->aiAgentService->processRequest($question, $context);

            return response()->json([
                'success' => true,
                'response' => $response,
                'question' => $question,
                'timestamp' => now()->toISOString()
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid question format',
                'errors' => $e->errors()
            ], 422);

        } catch (Exception $e) {
            Log::error('AI Agent API Error', [
                'question' => $request->input('question'),
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing your question. Please try again.',
                'error_code' => 'AI_PROCESSING_ERROR'
            ], 500);
        }
    }
} 