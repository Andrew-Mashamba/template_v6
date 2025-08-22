<?php

require_once 'vendor/autoload.php';

use App\Services\AiAgentService;

// Set your Claude API key here
$claudeApiKey = 'your_claude_api_key_here';

// Update environment for testing
$_ENV['CLAUDE_API_KEY'] = $claudeApiKey;

echo "🧪 Testing Claude AI Integration\n";
echo "================================\n\n";

try {
    // Create AI Agent Service
    $aiService = new AiAgentService();
    
    // Test simple question
    $testQuestion = "What is 2 + 2?";
    echo "📝 Test Question: {$testQuestion}\n\n";
    
    // Force use of Claude provider
    $options = ['provider' => 'claude'];
    $context = ['session_id' => 'test_session'];
    
    echo "🚀 Calling Claude AI...\n";
    $response = $aiService->processRequest($testQuestion, $context, $options);
    
    echo "✅ Claude Response:\n";
    echo "==================\n";
    echo $response['response'] . "\n\n";
    
    echo "📊 Response Details:\n";
    echo "Provider: " . ($response['provider'] ?? 'unknown') . "\n";
    echo "Method: " . ($response['method'] ?? 'unknown') . "\n";
    echo "Timestamp: " . ($response['timestamp'] ?? 'unknown') . "\n\n";
    
    echo "🎉 Claude AI integration test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ Error testing Claude AI integration:\n";
    echo $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}

echo "\n💡 Next Steps:\n";
echo "1. Add your actual Claude API key to the .env file\n";
echo "2. Set CLAUDE_API_KEY=your_actual_api_key\n";
echo "3. Test with actual questions about your SACCO system\n"; 