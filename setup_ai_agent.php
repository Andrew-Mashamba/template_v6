<?php

/**
 * AI Agent Setup Script
 * 
 * This script helps you configure the AI agent service with your API keys.
 * Run this script to set up your environment variables.
 */

echo "🤖 AI Agent Service Setup\n";
echo "========================\n\n";

// API Keys - get from environment variables
$apiKeys = [
    'GROQ_API_KEY' => getenv('GROQ_API_KEY') ?: 'your_groq_api_key_here',
    'OPENAI_API_KEY' => getenv('OPENAI_API_KEY') ?: 'your_openai_api_key_here',
    'TOGETHER_API_KEY' => getenv('TOGETHER_API_KEY') ?: 'your_together_api_key_here'
];

// Default configuration
$defaultConfig = [
    'GROQ_API_URL' => 'https://api.groq.com/openai/v1/chat/completions',
    'GROQ_DEFAULT_MODEL' => 'meta-llama/llama-4-scout-17b-16e-instruct',
    'GROQ_TIMEOUT' => '30',
    'GROQ_RATE_LIMIT' => '1000',
    'OPENAI_API_URL' => 'https://api.openai.com/v1/chat/completions',
    'OPENAI_DEFAULT_MODEL' => 'gpt-3.5-turbo',
    'OPENAI_TIMEOUT' => '60',
    'OPENAI_RATE_LIMIT' => '3000',
    'TOGETHER_API_URL' => 'https://api.together.xyz/v1/chat/completions',
    'TOGETHER_DEFAULT_MODEL' => 'meta-llama/Llama-2-70b-chat-hf',
    'TOGETHER_TIMEOUT' => '45',
    'TOGETHER_RATE_LIMIT' => '500'
];

echo "📋 Configuration Summary:\n";
echo "------------------------\n";

foreach ($apiKeys as $key => $value) {
    echo "✅ {$key}: " . substr($value, 0, 20) . "...\n";
}

echo "\n🔧 Default Configuration:\n";
echo "-------------------------\n";

foreach ($defaultConfig as $key => $value) {
    echo "⚙️  {$key}: {$value}\n";
}

echo "\n📝 Environment Variables to Add:\n";
echo "================================\n\n";

// Generate .env entries
foreach ($apiKeys as $key => $value) {
    echo "{$key}={$value}\n";
}

echo "\n";

foreach ($defaultConfig as $key => $value) {
    echo "{$key}={$value}\n";
}

echo "\n🚀 Next Steps:\n";
echo "==============\n";
echo "1. Add the above environment variables to your .env file\n";
echo "2. Run: php artisan migrate\n";
echo "3. Test the AI agent at: /ai-agent\n";
echo "4. Start chatting at: /ai-agent/conversation\n\n";

echo "🧪 Test Commands:\n";
echo "=================\n";
echo "Test Groq API:\n";
echo "curl https://api.groq.com/openai/v1/chat/completions -s \\\n";
echo "-H \"Content-Type: application/json\" \\\n";
echo "-H \"Authorization: Bearer {$apiKeys['GROQ_API_KEY']}\" \\\n";
echo "-d '{\n";
echo "\"model\": \"{$defaultConfig['GROQ_DEFAULT_MODEL']}\",\n";
echo "\"messages\": [{\n";
echo "    \"role\": \"user\",\n";
echo "    \"content\": \"Hello, this is a test message\"\n";
echo "}]\n";
echo "}'\n\n";

echo "Test OpenAI API:\n";
echo "curl https://api.openai.com/v1/chat/completions -s \\\n";
echo "-H \"Content-Type: application/json\" \\\n";
echo "-H \"Authorization: Bearer {$apiKeys['OPENAI_API_KEY']}\" \\\n";
echo "-d '{\n";
echo "\"model\": \"{$defaultConfig['OPENAI_DEFAULT_MODEL']}\",\n";
echo "\"messages\": [{\n";
echo "    \"role\": \"user\",\n";
echo "    \"content\": \"Hello, this is a test message\"\n";
echo "}]\n";
echo "}'\n\n";

echo "Test Together AI API:\n";
echo "curl https://api.together.xyz/v1/chat/completions -s \\\n";
echo "-H \"Content-Type: application/json\" \\\n";
echo "-H \"Authorization: Bearer {$apiKeys['TOGETHER_API_KEY']}\" \\\n";
echo "-d '{\n";
echo "\"model\": \"{$defaultConfig['TOGETHER_DEFAULT_MODEL']}\",\n";
echo "\"messages\": [{\n";
echo "    \"role\": \"user\",\n";
echo "    \"content\": \"Hello, this is a test message\"\n";
echo "}]\n";
echo "}'\n\n";

echo "✅ Setup complete! Your AI agent is ready to use.\n"; 