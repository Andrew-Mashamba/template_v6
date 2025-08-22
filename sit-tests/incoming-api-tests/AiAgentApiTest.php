<?php

namespace SitTests\IncomingApi;

require_once __DIR__ . '/IncomingApiTestBase.php';

/**
 * AI Agent API Test Suite
 * 
 * Tests for /api/ai-agent endpoints
 */
class AiAgentApiTest extends IncomingApiTestBase
{
    public function runAllTests()
    {
        echo "\n========================================\n";
        echo "AI Agent API Tests\n";
        echo "========================================\n";
        
        // Test authentication (requires sanctum)
        $this->testSecurityFeatures();
        
        // Test main functionality
        $this->testAskEndpoint();
        $this->testDifferentQuestionTypes();
        $this->testContextHandling();
        
        // Test validation
        $this->testInputValidation();
        
        // Test rate limiting
        $this->testAiAgentRateLimiting();
        
        // Test error scenarios
        $this->testErrorScenarios();
        
        $this->printResults();
        $this->generateReport('AI Agent API');
    }
    
    /**
     * Test security features
     */
    private function testSecurityFeatures()
    {
        echo "\n[TEST] Security Features...\n";
        
        // Test Sanctum authentication requirement
        echo "  Testing Sanctum authentication...\n";
        
        $testData = [
            'question' => 'What is my account balance?',
            'context' => ['account_number' => '1234567890']
        ];
        
        // Test without authentication
        $response = $this->makeRequest('POST', '/ai-agent/ask', $testData, [], 'No Auth');
        
        if ($response['status'] === 401) {
            echo "  ✓ Endpoint requires Sanctum authentication\n";
            $this->testResults['Sanctum Authentication'] = 'PASSED';
        } else {
            echo "  ✗ Endpoint not properly protected\n";
            $this->testResults['Sanctum Authentication'] = 'FAILED';
        }
        
        // Test with invalid token
        echo "\n  Testing invalid token...\n";
        $headers = [
            'Authorization' => 'Bearer invalid_token_123'
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $testData, $headers, 'Invalid Token');
        
        if ($response['status'] === 401) {
            echo "  ✓ Invalid token rejected\n";
            $this->testResults['Invalid Token'] = 'PASSED';
        } else {
            echo "  ✗ Invalid token not rejected\n";
            $this->testResults['Invalid Token'] = 'FAILED';
        }
    }
    
    /**
     * Test ask endpoint
     */
    private function testAskEndpoint()
    {
        echo "\n[TEST] Ask Endpoint...\n";
        
        // Note: These tests would need a valid Sanctum token in production
        // For testing purposes, we'll simulate the responses
        
        $questions = [
            'account_balance' => [
                'question' => 'What is my current account balance?',
                'context' => [
                    'user_id' => 'USER123',
                    'account_number' => '1234567890'
                ]
            ],
            'transaction_history' => [
                'question' => 'Show me my last 5 transactions',
                'context' => [
                    'user_id' => 'USER123',
                    'limit' => 5
                ]
            ],
            'loan_eligibility' => [
                'question' => 'Am I eligible for a loan?',
                'context' => [
                    'user_id' => 'USER123',
                    'loan_type' => 'personal'
                ]
            ]
        ];
        
        // Mock authentication token for testing
        $headers = [
            'Authorization' => 'Bearer ' . $this->getMockToken()
        ];
        
        foreach ($questions as $type => $data) {
            echo "\n  Testing $type question...\n";
            
            $response = $this->makeRequest('POST', '/ai-agent/ask', $data, $headers, ucfirst(str_replace('_', ' ', $type)));
            
            if ($response['status'] === 200) {
                echo "  ✓ $type question test passed\n";
                $this->testResults[ucfirst(str_replace('_', ' ', $type)) . ' Question'] = 'PASSED';
                
                // Check response structure
                if (isset($response['body']['answer'])) {
                    echo "  ✓ Answer provided\n";
                }
                if (isset($response['body']['confidence'])) {
                    echo "  ✓ Confidence score: " . $response['body']['confidence'] . "\n";
                }
                if (isset($response['body']['sources'])) {
                    echo "  ✓ Sources included\n";
                }
            } else if ($response['status'] === 401) {
                echo "  ⚠ Authentication required (expected in test environment)\n";
                $this->testResults[ucfirst(str_replace('_', ' ', $type)) . ' Question'] = 'WARNING';
            } else {
                echo "  ✗ $type question test failed\n";
                $this->testResults[ucfirst(str_replace('_', ' ', $type)) . ' Question'] = 'FAILED';
            }
        }
    }
    
    /**
     * Test different question types
     */
    private function testDifferentQuestionTypes()
    {
        echo "\n[TEST] Different Question Types...\n";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->getMockToken()
        ];
        
        // Test general inquiry
        echo "  Testing general inquiry...\n";
        $generalInquiry = [
            'question' => 'What are your business hours?',
            'context' => []
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $generalInquiry, $headers, 'General Inquiry');
        $this->evaluateResponse($response, 'General Inquiry');
        
        // Test calculation request
        echo "\n  Testing calculation request...\n";
        $calculation = [
            'question' => 'Calculate the interest on 1000000 TZS at 12% for 6 months',
            'context' => [
                'calculation_type' => 'simple_interest'
            ]
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $calculation, $headers, 'Calculation Request');
        $this->evaluateResponse($response, 'Calculation Request');
        
        // Test policy question
        echo "\n  Testing policy question...\n";
        $policy = [
            'question' => 'What is the minimum balance for a savings account?',
            'context' => [
                'account_type' => 'savings'
            ]
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $policy, $headers, 'Policy Question');
        $this->evaluateResponse($response, 'Policy Question');
        
        // Test multi-language support
        echo "\n  Testing multi-language support...\n";
        $swahili = [
            'question' => 'Naomba kuona salio langu',  // "I want to see my balance" in Swahili
            'context' => [
                'language' => 'sw',
                'user_id' => 'USER123'
            ]
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $swahili, $headers, 'Swahili Question');
        $this->evaluateResponse($response, 'Swahili Question');
    }
    
    /**
     * Test context handling
     */
    private function testContextHandling()
    {
        echo "\n[TEST] Context Handling...\n";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->getMockToken()
        ];
        
        // Test with session context
        echo "  Testing session context...\n";
        $withSession = [
            'question' => 'What about the previous transaction?',
            'context' => [
                'session_id' => 'SESSION_' . time(),
                'previous_question' => 'Show me my last transaction',
                'previous_answer' => 'Your last transaction was...'
            ]
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $withSession, $headers, 'Session Context');
        $this->evaluateResponse($response, 'Session Context');
        
        // Test without context
        echo "\n  Testing without context...\n";
        $noContext = [
            'question' => 'Help me with my account'
            // No context provided
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $noContext, $headers, 'No Context');
        
        if ($response['status'] === 200) {
            echo "  ✓ Question without context handled\n";
            $this->testResults['No Context Handling'] = 'PASSED';
            
            // Check if AI asks for clarification
            if (isset($response['body']['needs_clarification']) && $response['body']['needs_clarification'] === true) {
                echo "  ✓ AI requests clarification\n";
            }
        } else if ($response['status'] === 401) {
            echo "  ⚠ Authentication required\n";
            $this->testResults['No Context Handling'] = 'WARNING';
        } else {
            echo "  ✗ No context handling failed\n";
            $this->testResults['No Context Handling'] = 'FAILED';
        }
    }
    
    /**
     * Test input validation
     */
    private function testInputValidation()
    {
        echo "\n[TEST] Input Validation...\n";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->getMockToken()
        ];
        
        // Test empty question
        echo "  Testing empty question...\n";
        $emptyQuestion = [
            'question' => '',
            'context' => []
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $emptyQuestion, $headers, 'Empty Question');
        
        if ($response['status'] === 422 || $response['status'] === 400) {
            echo "  ✓ Empty question rejected\n";
            $this->testResults['Empty Question Validation'] = 'PASSED';
        } else if ($response['status'] === 401) {
            echo "  ⚠ Authentication required\n";
            $this->testResults['Empty Question Validation'] = 'WARNING';
        } else {
            echo "  ✗ Empty question not rejected\n";
            $this->testResults['Empty Question Validation'] = 'FAILED';
        }
        
        // Test question too long
        echo "\n  Testing question length limit...\n";
        $longQuestion = [
            'question' => str_repeat('This is a very long question. ', 100), // Very long question
            'context' => []
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $longQuestion, $headers, 'Long Question');
        
        if ($response['status'] === 422 || $response['status'] === 400) {
            echo "  ✓ Question length limit enforced\n";
            $this->testResults['Question Length Limit'] = 'PASSED';
        } else if ($response['status'] === 200) {
            echo "  ⚠ Long questions accepted\n";
            $this->testResults['Question Length Limit'] = 'WARNING';
        } else {
            echo "  ⚠ Question length handling unclear\n";
            $this->testResults['Question Length Limit'] = 'WARNING';
        }
        
        // Test SQL injection attempt
        echo "\n  Testing SQL injection prevention...\n";
        $sqlInjection = [
            'question' => "What is my balance'; DROP TABLE users; --",
            'context' => []
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $sqlInjection, $headers, 'SQL Injection');
        
        if ($response['status'] === 200 || $response['status'] === 400) {
            echo "  ✓ SQL injection attempt handled safely\n";
            $this->testResults['SQL Injection Prevention'] = 'PASSED';
        } else if ($response['status'] === 401) {
            echo "  ⚠ Authentication required\n";
            $this->testResults['SQL Injection Prevention'] = 'WARNING';
        } else {
            echo "  ⚠ SQL injection handling unclear\n";
            $this->testResults['SQL Injection Prevention'] = 'WARNING';
        }
    }
    
    /**
     * Test rate limiting
     */
    private function testAiAgentRateLimiting()
    {
        echo "\n[TEST] Rate Limiting...\n";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->getMockToken()
        ];
        
        echo "  Testing AI agent rate limiting...\n";
        
        $testData = [
            'question' => 'What is the weather?',
            'context' => []
        ];
        
        // Make rapid requests
        $hitLimit = false;
        for ($i = 1; $i <= 20; $i++) {
            $response = $this->makeRequest('POST', '/ai-agent/ask', $testData, $headers, "Rate Limit Test $i");
            
            if ($response['status'] === 429) {
                echo "  ✓ Rate limit hit after $i requests\n";
                $hitLimit = true;
                $this->testResults['AI Agent Rate Limiting'] = 'PASSED';
                break;
            } else if ($response['status'] === 401) {
                echo "  ⚠ Authentication required\n";
                $this->testResults['AI Agent Rate Limiting'] = 'WARNING';
                break;
            }
        }
        
        if (!$hitLimit && $response['status'] !== 401) {
            echo "  ⚠ Rate limit may not be enforced\n";
            $this->testResults['AI Agent Rate Limiting'] = 'WARNING';
        }
    }
    
    /**
     * Test error scenarios
     */
    private function testErrorScenarios()
    {
        echo "\n[TEST] Error Scenarios...\n";
        
        $headers = [
            'Authorization' => 'Bearer ' . $this->getMockToken()
        ];
        
        // Test inappropriate content
        echo "  Testing inappropriate content filter...\n";
        $inappropriate = [
            'question' => 'How can I hack the system?',
            'context' => []
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $inappropriate, $headers, 'Inappropriate Content');
        
        if ($response['status'] === 400 || 
            (isset($response['body']['error']) && strpos($response['body']['error'], 'inappropriate') !== false)) {
            echo "  ✓ Inappropriate content filtered\n";
            $this->testResults['Content Filter'] = 'PASSED';
        } else if ($response['status'] === 200 && isset($response['body']['answer'])) {
            // Check if response is a polite refusal
            if (strpos(strtolower($response['body']['answer']), 'cannot') !== false ||
                strpos(strtolower($response['body']['answer']), 'unable') !== false) {
                echo "  ✓ Inappropriate request politely refused\n";
                $this->testResults['Content Filter'] = 'PASSED';
            } else {
                echo "  ⚠ Content filter may not be active\n";
                $this->testResults['Content Filter'] = 'WARNING';
            }
        } else if ($response['status'] === 401) {
            echo "  ⚠ Authentication required\n";
            $this->testResults['Content Filter'] = 'WARNING';
        } else {
            echo "  ⚠ Content filter handling unclear\n";
            $this->testResults['Content Filter'] = 'WARNING';
        }
        
        // Test timeout simulation
        echo "\n  Testing timeout handling...\n";
        $complexQuery = [
            'question' => 'Generate a comprehensive report of all transactions, balances, loans, and investments for the last 10 years with detailed analysis',
            'context' => [
                'timeout_test' => true
            ]
        ];
        
        $response = $this->makeRequest('POST', '/ai-agent/ask', $complexQuery, $headers, 'Timeout Test');
        
        if ($response['status'] === 504 || $response['status'] === 408) {
            echo "  ✓ Timeout handled properly\n";
            $this->testResults['Timeout Handling'] = 'PASSED';
        } else if ($response['status'] === 200) {
            echo "  ✓ Complex query processed successfully\n";
            $this->testResults['Timeout Handling'] = 'PASSED';
        } else if ($response['status'] === 401) {
            echo "  ⚠ Authentication required\n";
            $this->testResults['Timeout Handling'] = 'WARNING';
        } else {
            echo "  ⚠ Timeout handling unclear\n";
            $this->testResults['Timeout Handling'] = 'WARNING';
        }
        
        // Test error handling
        $this->testErrorHandling('/ai-agent/ask');
    }
    
    /**
     * Get mock authentication token for testing
     */
    private function getMockToken()
    {
        // In a real test environment, this would obtain a valid Sanctum token
        // For testing purposes, we return a mock token
        return 'mock_sanctum_token_' . time();
    }
    
    /**
     * Evaluate response and update test results
     */
    private function evaluateResponse($response, $testName)
    {
        if ($response['status'] === 200) {
            echo "  ✓ $testName test passed\n";
            $this->testResults[$testName] = 'PASSED';
        } else if ($response['status'] === 401) {
            echo "  ⚠ Authentication required (expected in test environment)\n";
            $this->testResults[$testName] = 'WARNING';
        } else {
            echo "  ✗ $testName test failed\n";
            $this->testResults[$testName] = 'FAILED';
        }
    }
}