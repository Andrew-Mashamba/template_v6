<?php

/**
 * AI API Test Script
 * 
 * This script tests all three AI provider APIs to ensure they're working correctly.
 */

echo "🧪 Testing AI Provider APIs\n";
echo "==========================\n\n";

// API Keys - get from environment variables
$apiKeys = [
    'groq' => getenv('GROQ_API_KEY') ?: 'your_groq_api_key_here',
    'openai' => getenv('OPENAI_API_KEY') ?: 'your_openai_api_key_here',
    'together' => getenv('TOGETHER_API_KEY') ?: 'your_together_api_key_here'
];

// Provider configurations
$providers = [
    'groq' => [
        'url' => 'https://api.groq.com/openai/v1/chat/completions',
        'model' => 'meta-llama/llama-4-scout-17b-16e-instruct'
    ],
    'openai' => [
        'url' => 'https://api.openai.com/v1/chat/completions',
        'model' => 'gpt-3.5-turbo'
    ],
    'together' => [
        'url' => 'https://api.together.xyz/v1/chat/completions',
        'model' => 'meta-llama/Llama-2-70b-chat-hf'
    ]
];

function testProvider($name, $config, $apiKey) {
    echo "Testing {$name}...\n";
    
    $data = [
        'model' => $config['model'],
        'messages' => [
            [
                'role' => 'user',
                'content' => 'Hello! Please respond with "Hello from ' . ucfirst($name) . '!"'
            ]
        ],
        'max_tokens' => 50,
        'temperature' => 0.7
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $config['url']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    
    $startTime = microtime(true);
    $response = curl_exec($ch);
    $endTime = microtime(true);
    $responseTime = round(($endTime - $startTime) * 1000, 2);
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "❌ Error: {$error}\n";
        return false;
    }
    
    if ($httpCode !== 200) {
        echo "❌ HTTP Error: {$httpCode}\n";
        echo "Response: {$response}\n";
        return false;
    }
    
    $responseData = json_decode($response, true);
    
    if (!$responseData) {
        echo "❌ Invalid JSON response\n";
        return false;
    }
    
    if (isset($responseData['error'])) {
        echo "❌ API Error: " . $responseData['error']['message'] . "\n";
        return false;
    }
    
    if (isset($responseData['choices'][0]['message']['content'])) {
        $content = trim($responseData['choices'][0]['message']['content']);
        echo "✅ Success! Response time: {$responseTime}ms\n";
        echo "📝 Response: {$content}\n";
        
        if (isset($responseData['usage'])) {
            $usage = $responseData['usage'];
            echo "📊 Usage: {$usage['prompt_tokens']} prompt + {$usage['completion_tokens']} completion = {$usage['total_tokens']} total tokens\n";
        }
        
        return true;
    } else {
        echo "❌ Unexpected response format\n";
        echo "Response: " . json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
        return false;
    }
}

// Test each provider
$results = [];
foreach ($providers as $name => $config) {
    echo "\n" . str_repeat("=", 50) . "\n";
    $results[$name] = testProvider($name, $config, $apiKeys[$name]);
    echo str_repeat("=", 50) . "\n";
}

// Summary
echo "\n📊 Test Summary\n";
echo "==============\n";

$successCount = 0;
foreach ($results as $name => $success) {
    $status = $success ? "✅ PASS" : "❌ FAIL";
    echo "{$name}: {$status}\n";
    if ($success) $successCount++;
}

echo "\nOverall: {$successCount}/" . count($results) . " providers working\n";

if ($successCount === count($results)) {
    echo "\n🎉 All providers are working correctly!\n";
    echo "Your AI agent service is ready to use.\n";
} else {
    echo "\n⚠️  Some providers failed. Please check your API keys and try again.\n";
}

echo "\n🚀 Next steps:\n";
echo "1. Add the API keys to your .env file\n";
echo "2. Run: php artisan migrate\n";
echo "3. Visit: /ai-agent to start using the AI agent\n"; 