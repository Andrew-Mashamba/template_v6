<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Exception;

class AiProviderService
{
    private $providers = [];
    private $healthCheckInterval = 300; // 5 minutes

    public function __construct()
    {
        $this->initializeProviders();
    }

    /**
     * Initialize provider configurations
     */
    private function initializeProviders()
    {
        $this->providers = [
            'groq' => [
                'name' => 'Groq',
                'url' => 'https://api.groq.com/openai/v1/chat/completions',
                'models' => [
                    'meta-llama/llama-4-scout-17b-16e-instruct' => 'Llama 4 Scout 17B',
                    'llama3-8b-8192' => 'Llama 3 8B',
                    'llama3-70b-8192' => 'Llama 3 70B',
                    'mixtral-8x7b-32768' => 'Mixtral 8x7B'
                ],
                'default_model' => 'meta-llama/llama-4-scout-17b-16e-instruct',
                'api_key' => env('GROQ_API_KEY'),
                'rate_limit' => 1000, // requests per minute
                'timeout' => 30,
                'enabled' => true
            ],
            'openai' => [
                'name' => 'OpenAI',
                'url' => 'https://api.openai.com/v1/chat/completions',
                'models' => [
                    'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
                    'gpt-4' => 'GPT-4',
                    'gpt-4-turbo' => 'GPT-4 Turbo'
                ],
                'default_model' => 'gpt-3.5-turbo',
                'api_key' => env('OPENAI_API_KEY'),
                'rate_limit' => 3000,
                'timeout' => 60,
                'enabled' => true
            ],
            'together' => [
                'name' => 'Together AI',
                'url' => 'https://api.together.xyz/v1/chat/completions',
                'models' => [
                    'meta-llama/Llama-2-70b-chat-hf' => 'Llama 2 70B',
                    'meta-llama/Llama-2-13b-chat-hf' => 'Llama 2 13B',
                    'microsoft/DialoGPT-medium' => 'DialoGPT Medium'
                ],
                'default_model' => 'meta-llama/Llama-2-70b-chat-hf',
                'api_key' => env('TOGETHER_API_KEY'),
                'rate_limit' => 500,
                'timeout' => 45,
                'enabled' => true
            ]
        ];
    }

    /**
     * Get all providers
     */
    public function getAllProviders()
    {
        return $this->providers;
    }

    /**
     * Get provider by name
     */
    public function getProvider($name)
    {
        return $this->providers[$name] ?? null;
    }

    /**
     * Get available (enabled) providers
     */
    public function getAvailableProviders()
    {
        return array_filter($this->providers, function($provider) {
            return $provider['enabled'] && !empty($provider['api_key']);
        });
    }

    /**
     * Get healthy providers
     */
    public function getHealthyProviders()
    {
        $healthyProviders = [];

        foreach ($this->providers as $name => $provider) {
            if ($this->isProviderHealthy($name)) {
                $healthyProviders[$name] = $provider;
            }
        }

        return $healthyProviders;
    }

    /**
     * Check if provider is healthy
     */
    public function isProviderHealthy($providerName)
    {
        if (!isset($this->providers[$providerName])) {
            Log::error('[AI Provider Health Check]', [
                'provider' => $providerName,
                'error' => 'Provider not found'
            ]);
            return false;
        }

        $provider = $this->providers[$providerName];
        
        if (!$provider['enabled'] || empty($provider['api_key'])) {
            Log::warning('[AI Provider Health Check]', [
                'provider' => $providerName,
                'enabled' => $provider['enabled'],
                'has_api_key' => !empty($provider['api_key'])
            ]);
            return false;
        }

        $cacheKey = "ai_provider_health_{$providerName}";
        
        // Check cache first
        if (Cache::has($cacheKey)) {
            $cachedResult = Cache::get($cacheKey);
            Log::info('[AI Provider Health Check]', [
                'provider' => $providerName,
                'result' => $cachedResult,
                'source' => 'cache'
            ]);
            return $cachedResult;
        }

        // Perform health check
        $isHealthy = $this->performHealthCheck($providerName);
        
        Log::info('[AI Provider Health Check]', [
            'provider' => $providerName,
            'result' => $isHealthy,
            'source' => 'live_check'
        ]);
        
        // Cache result
        Cache::put($cacheKey, $isHealthy, $this->healthCheckInterval);

        return $isHealthy;
    }

    /**
     * Perform health check on provider
     */
    private function performHealthCheck($providerName)
    {
        try {
            $provider = $this->providers[$providerName];
            
            Log::info('[AI Provider Health Check Start]', [
                'provider' => $providerName,
                'url' => $provider['url']
            ]);
            
            // Simple health check - send a minimal request
            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $provider['api_key'],
                    'Content-Type' => 'application/json'
                ])
                ->post($provider['url'], [
                    'model' => $provider['default_model'],
                    'messages' => [
                        ['role' => 'user', 'content' => 'Hello']
                    ],
                    'max_tokens' => 5
                ]);

            $isHealthy = $response->successful();
            
            Log::info('[AI Provider Health Check Result]', [
                'provider' => $providerName,
                'success' => $isHealthy,
                'status_code' => $response->status(),
                'response_body' => $response->body()
            ]);

            return $isHealthy;

        } catch (Exception $e) {
            Log::error('[AI Provider Health Check Error]', [
                'provider' => $providerName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Get provider statistics
     */
    public function getProviderStats($providerName)
    {
        try {
            $cacheKey = "ai_provider_stats_{$providerName}";
            
            if (Cache::has($cacheKey)) {
                $cachedStats = Cache::get($cacheKey);
                Log::info('[AI Provider Stats]', [
                    'provider' => $providerName,
                    'source' => 'cache',
                    'stats' => $cachedStats
                ]);
                return $cachedStats;
            }

            $stats = [
                'total_requests' => 0,
                'successful_requests' => 0,
                'failed_requests' => 0,
                'average_response_time' => 0,
                'last_used' => null,
                'is_healthy' => $this->isProviderHealthy($providerName)
            ];

            // Get from database or logs
            $stats = $this->loadProviderStats($providerName, $stats);
            
            Log::info('[AI Provider Stats]', [
                'provider' => $providerName,
                'source' => 'calculated',
                'stats' => $stats
            ]);
            
            Cache::put($cacheKey, $stats, 300); // Cache for 5 minutes

            return $stats;
        } catch (Exception $e) {
            Log::error('[AI Provider Stats Error]', [
                'provider' => $providerName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Load provider statistics from database
     */
    private function loadProviderStats($providerName, $defaultStats)
    {
        // This would typically query a database table
        // For now, return default stats
        return $defaultStats;
    }

    /**
     * Update provider configuration
     */
    public function updateProviderConfig($providerName, $config)
    {
        try {
            if (!isset($this->providers[$providerName])) {
                Log::error('[AI Provider Config Update]', [
                    'provider' => $providerName,
                    'error' => 'Provider not found'
                ]);
                throw new Exception("Provider {$providerName} not found");
            }

            $oldConfig = $this->providers[$providerName];
            $this->providers[$providerName] = array_merge(
                $this->providers[$providerName], 
                $config
            );

            Log::info('[AI Provider Config Updated]', [
                'provider' => $providerName,
                'old_config' => $oldConfig,
                'new_config' => $this->providers[$providerName]
            ]);

            // Clear health cache
            Cache::forget("ai_provider_health_{$providerName}");
            Cache::forget("ai_provider_stats_{$providerName}");

            return $this->providers[$providerName];
        } catch (Exception $e) {
            Log::error('[AI Provider Config Update Error]', [
                'provider' => $providerName,
                'config' => $config,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Enable/disable provider
     */
    public function setProviderStatus($providerName, $enabled)
    {
        if (!isset($this->providers[$providerName])) {
            throw new Exception("Provider {$providerName} not found");
        }

        $this->providers[$providerName]['enabled'] = $enabled;
        
        // Clear health cache
        Cache::forget("ai_provider_health_{$providerName}");

        return $this->providers[$providerName];
    }

    /**
     * Test provider connection
     */
    public function testProvider($providerName, $testMessage = 'Hello, this is a test message.')
    {
        try {
            if (!isset($this->providers[$providerName])) {
                Log::error('[AI Provider Test]', [
                    'provider' => $providerName,
                    'error' => 'Provider not found'
                ]);
                throw new Exception("Provider {$providerName} not found");
            }

            $provider = $this->providers[$providerName];
            
            if (!$provider['enabled'] || empty($provider['api_key'])) {
                Log::error('[AI Provider Test]', [
                    'provider' => $providerName,
                    'error' => 'Provider not properly configured',
                    'enabled' => $provider['enabled'],
                    'has_api_key' => !empty($provider['api_key'])
                ]);
                throw new Exception("Provider {$providerName} is not properly configured");
            }

            Log::info('[AI Provider Test Start]', [
                'provider' => $providerName,
                'message' => $testMessage,
                'model' => $provider['default_model']
            ]);

            $startTime = microtime(true);
            
            $response = Http::timeout($provider['timeout'])
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $provider['api_key'],
                    'Content-Type' => 'application/json'
                ])
                ->post($provider['url'], [
                    'model' => $provider['default_model'],
                    'messages' => [
                        ['role' => 'user', 'content' => $testMessage]
                    ],
                    'max_tokens' => 100,
                    'temperature' => 0.7
                ]);

            $endTime = microtime(true);
            $responseTime = ($endTime - $startTime) * 1000; // Convert to milliseconds

            if (!$response->successful()) {
                Log::error('[AI Provider Test HTTP Error]', [
                    'provider' => $providerName,
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'response_time' => $responseTime
                ]);
                throw new Exception("Provider returned error: " . $response->body());
            }

            $responseData = $response->json();
            
            Log::info('[AI Provider Test Success]', [
                'provider' => $providerName,
                'response_time' => $responseTime,
                'response' => $responseData
            ]);
            
            return [
                'success' => true,
                'response_time' => round($responseTime, 2),
                'response' => $responseData['choices'][0]['message']['content'] ?? 'No content',
                'usage' => $responseData['usage'] ?? null
            ];

        } catch (Exception $e) {
            Log::error('[AI Provider Test Error]', [
                'provider' => $providerName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'response_time' => null
            ];
        }
    }

    /**
     * Get provider models
     */
    public function getProviderModels($providerName)
    {
        if (!isset($this->providers[$providerName])) {
            return [];
        }

        return $this->providers[$providerName]['models'] ?? [];
    }

    /**
     * Get best provider based on criteria
     */
    public function getBestProvider($criteria = [])
    {
        $availableProviders = $this->getHealthyProviders();
        
        if (empty($availableProviders)) {
            return null;
        }

        // Simple selection logic - can be enhanced with more sophisticated algorithms
        $preferredProvider = $criteria['preferred'] ?? null;
        
        if ($preferredProvider && isset($availableProviders[$preferredProvider])) {
            return $preferredProvider;
        }

        // Return the first available provider
        return array_key_first($availableProviders);
    }

    /**
     * Log provider usage
     */
    public function logProviderUsage($providerName, $success, $responseTime = null, $error = null)
    {
        $logData = [
            'provider' => $providerName,
            'success' => $success,
            'response_time' => $responseTime,
            'error' => $error,
            'timestamp' => now()
        ];

        Log::info('AI Provider Usage', $logData);

        // Update statistics
        $this->updateProviderStats($providerName, $logData);
    }

    /**
     * Update provider statistics
     */
    private function updateProviderStats($providerName, $usageData)
    {
        $cacheKey = "ai_provider_stats_{$providerName}";
        
        $stats = Cache::get($cacheKey, [
            'total_requests' => 0,
            'successful_requests' => 0,
            'failed_requests' => 0,
            'average_response_time' => 0,
            'last_used' => null
        ]);

        $stats['total_requests']++;
        $stats['last_used'] = now();

        if ($usageData['success']) {
            $stats['successful_requests']++;
        } else {
            $stats['failed_requests']++;
        }

        if ($usageData['response_time']) {
            $totalTime = $stats['average_response_time'] * ($stats['total_requests'] - 1);
            $stats['average_response_time'] = ($totalTime + $usageData['response_time']) / $stats['total_requests'];
        }

        Cache::put($cacheKey, $stats, 300);
    }
} 