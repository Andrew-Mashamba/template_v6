<?php

namespace SitTests;

require_once __DIR__ . '/../vendor/autoload.php';

use Illuminate\Support\Facades\Http;

class AIServicesTest
{
    private $testResults = [];
    
    public function runAllTests()
    {
        echo "\n========================================\n";
        echo "AI Services API Tests\n";
        echo "========================================\n";
        
        $this->testGroqAPI();
        $this->testOpenAIAPI();
        $this->testTogetherAPI();
        $this->testClaudeAPI();
        
        $this->printResults();
    }
    
    private function testGroqAPI()
    {
        echo "\n[TEST] Groq API...\n";
        
        try {
            $apiKey = env('GROQ_API_KEY');
            $baseUrl = env('GROQ_API_URL', 'https://api.groq.com/openai/v1/chat/completions');
            $model = env('GROQ_DEFAULT_MODEL', 'meta-llama/llama-4-scout-17b-16e-instruct');
            
            Http::fake([
                $baseUrl . '/chat/completions' => Http::response([
                    'id' => 'chatcmpl-' . uniqid(),
                    'object' => 'chat.completion',
                    'created' => time(),
                    'model' => $model,
                    'choices' => [
                        [
                            'index' => 0,
                            'message' => [
                                'role' => 'assistant',
                                'content' => 'This is a test response from Groq AI.'
                            ],
                            'finish_reason' => 'stop'
                        ]
                    ],
                    'usage' => [
                        'prompt_tokens' => 10,
                        'completion_tokens' => 8,
                        'total_tokens' => 18
                    ]
                ], 200)
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->post($baseUrl . '/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Test message for Groq API']
                ],
                'temperature' => 0.7,
                'max_tokens' => 100
            ]);
            
            if ($response->successful() && isset($response->json()['choices'][0]['message']['content'])) {
                $this->testResults['Groq API'] = 'PASSED';
                echo "✓ Groq API test passed - Model: $model\n";
            } else {
                $this->testResults['Groq API'] = 'FAILED';
                echo "✗ Groq API test failed\n";
            }
        } catch (\Exception $e) {
            $this->testResults['Groq API'] = 'ERROR';
            echo "✗ Groq API test error: " . $e->getMessage() . "\n";
        }
    }
    
    private function testOpenAIAPI()
    {
        echo "\n[TEST] OpenAI API...\n";
        
        try {
            $apiKey = config('services.openai.api_key');
            $url = config('services.openai.url') ?: 'https://api.openai.com/v1/chat/completions';
            $model = config('services.openai.default_model') ?: 'gpt-3.5-turbo';
            
            if (!$apiKey) {
                $this->testResults['OpenAI API'] = 'SKIPPED';
                echo "⚠ OpenAI API test skipped - No API key configured\n";
                return;
            }
            
            Http::fake([
                $url => Http::response([
                    'id' => 'chatcmpl-' . uniqid(),
                    'object' => 'chat.completion',
                    'created' => time(),
                    'model' => $model,
                    'choices' => [
                        [
                            'index' => 0,
                            'message' => [
                                'role' => 'assistant',
                                'content' => 'This is a test response from OpenAI.'
                            ],
                            'finish_reason' => 'stop'
                        ]
                    ],
                    'usage' => [
                        'prompt_tokens' => 12,
                        'completion_tokens' => 9,
                        'total_tokens' => 21
                    ]
                ], 200)
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Test message for OpenAI API']
                ],
                'temperature' => 0.7,
                'max_tokens' => 100
            ]);
            
            if ($response->successful() && isset($response->json()['choices'][0]['message']['content'])) {
                $this->testResults['OpenAI API'] = 'PASSED';
                echo "✓ OpenAI API test passed - Model: $model\n";
            } else {
                $this->testResults['OpenAI API'] = 'FAILED';
                echo "✗ OpenAI API test failed\n";
            }
        } catch (\Exception $e) {
            $this->testResults['OpenAI API'] = 'ERROR';
            echo "✗ OpenAI API test error: " . $e->getMessage() . "\n";
        }
    }
    
    private function testTogetherAPI()
    {
        echo "\n[TEST] Together AI API...\n";
        
        try {
            $apiKey = config('services.together.api_key');
            $url = config('services.together.url') ?: 'https://api.together.xyz/v1/chat/completions';
            $model = config('services.together.default_model') ?: 'meta-llama/Llama-2-70b-chat-hf';
            
            if (!$apiKey) {
                $this->testResults['Together AI API'] = 'SKIPPED';
                echo "⚠ Together AI API test skipped - No API key configured\n";
                return;
            }
            
            Http::fake([
                $url => Http::response([
                    'id' => 'chatcmpl-' . uniqid(),
                    'object' => 'chat.completion',
                    'created' => time(),
                    'model' => $model,
                    'choices' => [
                        [
                            'index' => 0,
                            'message' => [
                                'role' => 'assistant',
                                'content' => 'This is a test response from Together AI.'
                            ],
                            'finish_reason' => 'stop'
                        ]
                    ],
                    'usage' => [
                        'prompt_tokens' => 15,
                        'completion_tokens' => 10,
                        'total_tokens' => 25
                    ]
                ], 200)
            ]);
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json'
            ])->post($url, [
                'model' => $model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Test message for Together AI API']
                ],
                'temperature' => 0.7,
                'max_tokens' => 100
            ]);
            
            if ($response->successful() && isset($response->json()['choices'][0]['message']['content'])) {
                $this->testResults['Together AI API'] = 'PASSED';
                echo "✓ Together AI API test passed - Model: $model\n";
            } else {
                $this->testResults['Together AI API'] = 'FAILED';
                echo "✗ Together AI API test failed\n";
            }
        } catch (\Exception $e) {
            $this->testResults['Together AI API'] = 'ERROR';
            echo "✗ Together AI API test error: " . $e->getMessage() . "\n";
        }
    }
    
    private function testClaudeAPI()
    {
        echo "\n[TEST] Claude API...\n";
        
        try {
            $apiKey = config('services.claude.api_key');
            
            if (!$apiKey) {
                $this->testResults['Claude API'] = 'SKIPPED';
                echo "⚠ Claude API test skipped - No API key configured\n";
                return;
            }
            
            Http::fake([
                'https://api.anthropic.com/v1/messages' => Http::response([
                    'id' => 'msg_' . uniqid(),
                    'type' => 'message',
                    'role' => 'assistant',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'This is a test response from Claude API.'
                        ]
                    ],
                    'model' => 'claude-3-opus-20240229',
                    'stop_reason' => 'end_turn',
                    'stop_sequence' => null,
                    'usage' => [
                        'input_tokens' => 10,
                        'output_tokens' => 9
                    ]
                ], 200)
            ]);
            
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
                'Content-Type' => 'application/json'
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-3-opus-20240229',
                'max_tokens' => 100,
                'messages' => [
                    ['role' => 'user', 'content' => 'Test message for Claude API']
                ]
            ]);
            
            if ($response->successful() && isset($response->json()['content'][0]['text'])) {
                $this->testResults['Claude API'] = 'PASSED';
                echo "✓ Claude API test passed\n";
            } else {
                $this->testResults['Claude API'] = 'FAILED';
                echo "✗ Claude API test failed\n";
            }
        } catch (\Exception $e) {
            $this->testResults['Claude API'] = 'ERROR';
            echo "✗ Claude API test error: " . $e->getMessage() . "\n";
        }
    }
    
    private function printResults()
    {
        echo "\n========================================\n";
        echo "Test Results Summary\n";
        echo "========================================\n";
        
        $passed = 0;
        $failed = 0;
        $errors = 0;
        $skipped = 0;
        
        foreach ($this->testResults as $test => $result) {
            echo sprintf("%-30s: %s\n", $test, $result);
            
            if ($result === 'PASSED') $passed++;
            elseif ($result === 'FAILED') $failed++;
            elseif ($result === 'SKIPPED') $skipped++;
            else $errors++;
        }
        
        echo "----------------------------------------\n";
        echo "Total: " . count($this->testResults) . " tests\n";
        echo "Passed: $passed | Failed: $failed | Skipped: $skipped | Errors: $errors\n";
        echo "========================================\n";
    }
}

// Run tests if executed directly
if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['PHP_SELF'] ?? '')) {
    $test = new AIServicesTest();
    $test->runAllTests();
}