<?php

/**
 * Test Script for LoanDecisionController
 * This script tests the loan decision API endpoint with various scenarios
 */

// Configuration
$baseUrl = 'http://localhost:8000'; // Adjust this to your local server URL
$apiEndpoint = '/api/loan-decision';

// Test Cases
$testCases = [
    'approved_loan' => [
        'name' => 'Approved Loan - All conditions met',
        'data' => [
            'member_number' => '00001',
            'product_id' => '731205',
            'tenure' => 12,
            'approved_loan_value' => 8000,
            'approved_term' => 12,
            'score' => ['score' => 750],
            'take_home' => 800000,
            'monthly_installment_value' => 450000,
            'collateral_value' => 8000000,
            'is_physical_collateral' => true,
            'product' => [
                'principle_max_value' => 10000,
                'max_term' => 24,
                'score_limit' => 600,
                'ltv' => 70,
                'loan_multiplier' => 2.5
            ]
        ]
    ],
    
    'loan_amount_exceeded' => [
        'name' => 'Loan Amount Exceeded',
        'data' => [
            'member_number' => '00002',
            'product_id' => '731205',
            'tenure' => 12,
            'approved_loan_value' => 15000000, // Exceeds max of 10,000,000
            'approved_term' => 12,
            'score' => ['score' => 750],
            'take_home' => 800000,
            'monthly_installment_value' => 450000,
            'collateral_value' => 8000000,
            'is_physical_collateral' => true,
            'product' => [
                'principle_max_value' => 10000,
                'max_term' => 24,
                'score_limit' => 600,
                'ltv' => 70,
                'loan_multiplier' => 2.5
            ]
        ]
    ],
    
    'term_exceeded' => [
        'name' => 'Term Exceeded',
        'data' => [
            'member_number' => '10002',
            'product_id' => '731205',
            'tenure' => 12,
            'approved_loan_value' => 5000000,
            'approved_term' => 36, // Exceeds max of 24
            'score' => ['score' => 750],
            'take_home' => 800000,
            'monthly_installment_value' => 450000,
            'collateral_value' => 8000000,
            'is_physical_collateral' => true,
            'product' => [
                'principle_max_value' => 10000,
                'max_term' => 24,
                'score_limit' => 600,
                'ltv' => 70,
                'loan_multiplier' => 2.5
            ]
        ]
    ],
    
    'low_credit_score' => [
        'name' => 'Low Credit Score',
        'data' => [
            'member_number' => '10003',
            'product_id' => '731205',
            'tenure' => 12,
            'approved_loan_value' => 5000000,
            'approved_term' => 12,
            'score' => ['score' => 550], // Below limit of 600
            'take_home' => 800000,
            'monthly_installment_value' => 450000,
            'collateral_value' => 8000000,
            'is_physical_collateral' => true,
            'product' => [
                'principle_max_value' => 10000,
                'max_term' => 24,
                'score_limit' => 600,
                'ltv' => 70,
                'loan_multiplier' => 2.5
            ]
        ]
    ],
    
    'high_monthly_payment' => [
        'name' => 'High Monthly Payment (Above 50% of take-home)',
        'data' => [
            'member_number' => '10001',
            'product_id' => '731205',
            'tenure' => 12,
            'approved_loan_value' => 5000000,
            'approved_term' => 12,
            'score' => ['score' => 750],
            'take_home' => 800000,
            'monthly_installment_value' => 500000, // 62.5% of take-home (above 50%)
            'collateral_value' => 8000000,
            'is_physical_collateral' => true,
            'product' => [
                'principle_max_value' => 10000,
                'max_term' => 24,
                'score_limit' => 600,
                'ltv' => 70,
                'loan_multiplier' => 2.5
            ]
        ]
    ],
    
    'edge_case_minimum_values' => [
        'name' => 'Edge Case - Minimum Values',
        'data' => [
            'member_number' => '10001',
            'product_id' => '731205',
            'tenure' => 12,
            'approved_loan_value' => 1000000,
            'approved_term' => 1,
            'score' => ['score' => 600], // Exactly at limit
            'take_home' => 400000,
            'monthly_installment_value' => 200000, // Exactly 50% of take-home
            'collateral_value' => 2000000,
            'is_physical_collateral' => false,
            'product' => [
                'principle_max_value' => 10000,
                'max_term' => 24,
                'score_limit' => 600,
                'ltv' => 70,
                'loan_multiplier' => 2.5
            ]
        ]
    ]
];

/**
 * Make HTTP POST request to the API
 */
function makeApiRequest($url, $data) {
    $jsonData = json_encode($data);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData),
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

/**
 * Display test results in a formatted way
 */
function displayTestResult($testName, $testData, $result) {
    echo "\n" . str_repeat("=", 80) . "\n";
    echo "TEST: {$testName}\n";
    echo str_repeat("=", 80) . "\n";
    
    echo "Request Data:\n";
    echo json_encode($testData, JSON_PRETTY_PRINT) . "\n\n";
    
    echo "HTTP Status Code: {$result['http_code']}\n";
    
    if ($result['error']) {
        echo "cURL Error: {$result['error']}\n";
    } else {
        echo "Response:\n";
        $responseData = json_decode($result['response'], true);
        if ($responseData) {
            echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
            
            // Analyze the response
            if (isset($responseData['approved'])) {
                echo "\nDecision: " . ($responseData['approved'] ? "APPROVED" : "DECLINED") . "\n";
                echo "Message: " . $responseData['message'] . "\n";
                
                if (isset($responseData['exceptions'])) {
                    echo "\nValidation Results:\n";
                    foreach ($responseData['exceptions'] as $exception) {
                        $status = $exception['exceeded'] ? "❌ FAILED" : "✅ PASSED";
                        echo "  {$exception['exception']}: {$status}\n";
                        echo "    Limit: {$exception['limit']} | Given: {$exception['given']}\n";
                    }
                }
            }
        } else {
            echo "Invalid JSON response: " . $result['response'] . "\n";
        }
    }
    echo str_repeat("=", 80) . "\n";
}

/**
 * Main test execution
 */
echo "LoanDecisionController API Test Script\n";
echo "=====================================\n";
echo "Base URL: {$baseUrl}\n";
echo "API Endpoint: {$apiEndpoint}\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";

$fullUrl = $baseUrl . $apiEndpoint;
echo "Testing endpoint: {$fullUrl}\n\n";

$passedTests = 0;
$totalTests = count($testCases);

foreach ($testCases as $testKey => $testCase) {
    $result = makeApiRequest($fullUrl, $testCase['data']);
    displayTestResult($testCase['name'], $testCase['data'], $result);
    
    // Count successful tests (no cURL errors and valid response)
    if (!$result['error'] && $result['http_code'] == 200) {
        $passedTests++;
    }
    
    // Add a small delay between requests
    sleep(1);
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "TEST SUMMARY\n";
echo str_repeat("=", 80) . "\n";
echo "Total Tests: {$totalTests}\n";
echo "Passed: {$passedTests}\n";
echo "Failed: " . ($totalTests - $passedTests) . "\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n";

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n"; 