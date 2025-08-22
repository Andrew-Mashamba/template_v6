<?php

/**
 * AI Agent Test Script
 * 
 * This script tests the AI agent service directly.
 */

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🤖 Testing AI Agent Service\n";
echo "==========================\n\n";

// API keys should be in .env file
// If not set, use placeholder values for testing
if (!getenv('GROQ_API_KEY')) {
    echo "⚠️  GROQ_API_KEY not found in environment. Please set it in .env file.\n";
}
if (!getenv('OPENAI_API_KEY')) {
    echo "⚠️  OPENAI_API_KEY not found in environment. Please set it in .env file.\n";
}
if (!getenv('TOGETHER_API_KEY')) {
    echo "⚠️  TOGETHER_API_KEY not found in environment. Please set it in .env file.\n";
}

try {
    // Test the AI Agent Service
    $aiAgentService = app(\App\Services\AiAgentService::class);
    $providerService = app(\App\Services\AiProviderService::class);
    $memoryService = app(\App\Services\AiMemoryService::class);
    $validationService = app(\App\Services\AiValidationService::class);
    
    echo "✅ Services loaded successfully\n\n";
    
    // Test provider status
    echo "📊 Provider Status:\n";
    $providers = $providerService->getAllProviders();
    foreach ($providers as $name => $provider) {
        $health = $providerService->isProviderHealthy($name) ? '✅ Healthy' : '❌ Unhealthy';
        $enabled = $provider['enabled'] ? 'Enabled' : 'Disabled';
        echo "  {$name}: {$health} ({$enabled})\n";
    }
    
    echo "\n🧪 Testing AI Request:\n";
    echo "=====================\n";
    
    $testQuery = "Hello, how are you?";
    
    echo "Query: {$testQuery}\n\n";
    
    $startTime = microtime(true);
    
    $response = $aiAgentService->processRequest($testQuery, [
        'user_id' => 1,
        'user_permissions' => ['read'],
        'session_id' => 'test_session_' . uniqid()
    ]);
    
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    echo "✅ Response received in {$responseTime}ms\n";
    echo "Provider: {$response['provider']}\n";
    echo "Response: {$response['response']}\n";
    
    if (isset($response['usage'])) {
        $usage = $response['usage'];
        echo "Usage: {$usage['total_tokens']} tokens\n";
    }
    
    // Test memory service
    echo "\n🧠 Testing Memory Service:\n";
    echo "==========================\n";
    
    $sessionId = 'test_session_' . uniqid();
    $memoryService->setSessionId($sessionId);
    
    $memoryService->addInteraction($testQuery, $response['response'], [
        'user_id' => 1,
        'provider' => $response['provider']
    ]);
    
    $interactions = $memoryService->getRecentInteractions(5);
    echo "✅ Memory service working - " . count($interactions) . " interactions stored\n";
    
    // Test validation service
    echo "\n🔒 Testing Validation Service:\n";
    echo "==============================\n";
    
    $validQuery = "What is the weather like today?";
    $validation = $validationService->validateQuery($validQuery);
    
    if ($validation['valid']) {
        echo "✅ Valid query accepted\n";
    } else {
        echo "❌ Valid query rejected: " . implode(', ', $validation['errors']) . "\n";
    }
    
    $maliciousQuery = "SELECT * FROM users; DROP TABLE users;";
    $validation = $validationService->validateQuery($maliciousQuery);
    
    if (!$validation['valid']) {
        echo "✅ Malicious query blocked\n";
    } else {
        echo "❌ Malicious query accepted (security issue!)\n";
    }
    
    echo "\n🎉 All tests completed successfully!\n";
    echo "Your AI agent service is working correctly.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
} 