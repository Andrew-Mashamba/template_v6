<?php

require_once 'vendor/autoload.php';

use App\Services\AiAgentService;

echo "🧪 Testing Claude as Default Provider\n";
echo "====================================\n\n";

try {
    // Create AI Agent Service
    $aiService = new AiAgentService();
    
    // Test without specifying a provider - should default to Claude
    $testQuestion = "Hello, which AI provider are you?";
    echo "📝 Test Question: {$testQuestion}\n";
    echo "🎯 Expected: Should use Claude as default provider\n\n";
    
    // Don't specify provider - should default to Claude
    $context = ['session_id' => 'test_claude_default'];
    $options = []; // No provider specified
    
    echo "🚀 Processing request without specifying provider...\n";
    $response = $aiService->processRequest($testQuestion, $options, $context);
    
    echo "✅ Response received!\n";
    echo "📄 Response: " . substr($response, 0, 200) . "...\n\n";
    
    // Test with explicit provider specification
    echo "🔄 Testing with explicit Claude specification...\n";
    $optionsExplicit = ['provider' => 'claude'];
    $response2 = $aiService->processRequest("Test with explicit Claude", $optionsExplicit, $context);
    
    echo "✅ Explicit Claude test completed!\n";
    echo "📄 Response: " . substr($response2, 0, 200) . "...\n\n";
    
    echo "🎉 Claude Default Provider Test Completed Successfully!\n";
    echo "💡 Check the logs to see '[AI Provider Selection]' entries confirming Claude is being used.\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "💡 Make sure you have set the CLAUDE_API_KEY in your .env file\n";
}

echo "\n📋 Instructions:\n";
echo "1. Set CLAUDE_API_KEY in your .env file\n";
echo "2. Run this test: php test_claude_default.php\n";
echo "3. Check storage/logs/laravel.log for '[AI Provider Selection]' entries\n";
echo "4. Verify Claude is being selected as default provider\n"; 