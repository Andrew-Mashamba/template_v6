<?php

/**
 * SIT (System Integration Testing) - Internal Funds Transfer Service
 * Real Request and Response Documentation
 * 
 * Test Data:
 * - SACCOS Account: 011191000035 (CBN MICROFINANCE)
 * - Individual Account 1: 011201318462 (BON JON JONES)
 * - Individual Account 2: 074206000029 (BON JON JONES II)
 * 
 * This service handles transfers between NBC accounts internally
 */

require_once __DIR__ . '/bootstrap/app.php';

use App\Services\Payments\InternalFundsTransferService;
use Illuminate\Support\Facades\Log;

// Enable detailed logging
Log::channel('payments')->info("===== SIT INTERNAL TRANSFER SERVICE TEST STARTED =====");

$service = new InternalFundsTransferService();

// Test configurations
$tests = [
    // Test 1: Account Lookup - SACCOS Account
    [
        'name' => 'Account Lookup - SACCOS Account',
        'type' => 'lookup',
        'method' => 'lookupAccount',
        'params' => [
            'account_number' => '011191000035',
            'account_type' => 'source'
        ]
    ],
    
    // Test 2: Account Lookup - Individual Account 1
    [
        'name' => 'Account Lookup - BON JON JONES',
        'type' => 'lookup',
        'method' => 'lookupAccount',
        'params' => [
            'account_number' => '011201318462',
            'account_type' => 'destination'
        ]
    ],
    
    // Test 3: Account Lookup - Individual Account 2
    [
        'name' => 'Account Lookup - BON JON JONES II',
        'type' => 'lookup',
        'method' => 'lookupAccount',
        'params' => [
            'account_number' => '074206000029',
            'account_type' => 'destination'
        ]
    ],
    
    // Test 4: Internal Transfer - SACCOS to Individual 1 (Small Amount)
    [
        'name' => 'Transfer - SACCOS to BON JON JONES (5,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'to_account' => '011201318462',
            'amount' => 5000,
            'from_currency' => 'TZS',
            'to_currency' => 'TZS',
            'narration' => 'SIT Test - Internal transfer to BON JON JONES',
            'sender_name' => 'CBN MICROFINANCE'
        ]
    ],
    
    // Test 5: Internal Transfer - SACCOS to Individual 2 (Medium Amount)
    [
        'name' => 'Transfer - SACCOS to BON JON JONES II (25,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'to_account' => '074206000029',
            'amount' => 25000,
            'from_currency' => 'TZS',
            'to_currency' => 'TZS',
            'narration' => 'SIT Test - Internal transfer to BON JON JONES II',
            'sender_name' => 'CBN MICROFINANCE'
        ]
    ],
    
    // Test 6: Internal Transfer - Individual 1 to Individual 2
    [
        'name' => 'Transfer - Between Individuals (10,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011201318462',
            'to_account' => '074206000029',
            'amount' => 10000,
            'from_currency' => 'TZS',
            'to_currency' => 'TZS',
            'narration' => 'SIT Test - Transfer between individual accounts',
            'sender_name' => 'BON JON JONES'
        ]
    ],
    
    // Test 7: Internal Transfer - Large Amount
    [
        'name' => 'Transfer - Large Amount (500,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'to_account' => '011201318462',
            'amount' => 500000,
            'from_currency' => 'TZS',
            'to_currency' => 'TZS',
            'narration' => 'SIT Test - Large amount internal transfer',
            'sender_name' => 'CBN MICROFINANCE'
        ]
    ],
    
    // Test 8: Internal Transfer - Minimum Amount
    [
        'name' => 'Transfer - Minimum Amount (100 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'to_account' => '074206000029',
            'amount' => 100,
            'from_currency' => 'TZS',
            'to_currency' => 'TZS',
            'narration' => 'SIT Test - Minimum amount transfer',
            'sender_name' => 'CBN MICROFINANCE'
        ]
    ],
    
    // Test 9: Internal Transfer - Decimal Amount
    [
        'name' => 'Transfer - Decimal Amount (12,345.67 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'to_account' => '011201318462',
            'amount' => 12345.67,
            'from_currency' => 'TZS',
            'to_currency' => 'TZS',
            'narration' => 'SIT Test - Decimal amount transfer',
            'sender_name' => 'CBN MICROFINANCE'
        ]
    ],
    
    // Test 10: Transfer Status Check
    [
        'name' => 'Transfer Status Check',
        'type' => 'status',
        'method' => 'getTransferStatus',
        'params' => [
            'reference' => 'IFT20240101120000ABCDEF'  // Will be replaced with actual reference
        ]
    ]
];

// Store references for status checks
$transferReferences = [];

// Execute tests
$results = [];
foreach ($tests as $index => $test) {
    echo "\n\n" . str_repeat("=", 80) . "\n";
    echo "TEST: {$test['name']}\n";
    echo str_repeat("=", 80) . "\n";
    
    $startTime = microtime(true);
    
    // Use last successful transfer reference for status check
    if ($test['type'] === 'status' && !empty($transferReferences)) {
        $test['params']['reference'] = end($transferReferences);
        echo "📌 Checking status for reference: {$test['params']['reference']}\n";
    }
    
    // Log the request
    Log::channel('payments')->info("SIT TEST REQUEST", [
        'test_name' => $test['name'],
        'test_type' => $test['type'],
        'method' => $test['method'],
        'request_payload' => json_encode($test['params'], JSON_PRETTY_PRINT),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
    echo "\n📤 REQUEST:\n";
    echo json_encode($test['params'], JSON_PRETTY_PRINT) . "\n";
    
    try {
        // Execute the service method
        $method = $test['method'];
        if ($test['type'] === 'lookup') {
            $response = $service->$method(
                $test['params']['account_number'],
                $test['params']['account_type']
            );
        } elseif ($test['type'] === 'status') {
            $response = $service->$method($test['params']['reference']);
        } else {
            $response = $service->$method($test['params']);
        }
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        // Store successful transfer references
        if ($test['type'] === 'transfer' && isset($response['reference']) && $response['success']) {
            $transferReferences[] = $response['reference'];
        }
        
        // Log the response
        Log::channel('payments')->info("SIT TEST RESPONSE", [
            'test_name' => $test['name'],
            'success' => $response['success'] ?? false,
            'response_payload' => json_encode($response, JSON_PRETTY_PRINT),
            'duration_ms' => $duration,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        echo "\n📥 RESPONSE:\n";
        
        // Clean up sensitive API response data for display
        $displayResponse = $response;
        if (isset($displayResponse['api_response'])) {
            $displayResponse['api_response'] = '[API Response Details Available in Logs]';
        }
        
        echo json_encode($displayResponse, JSON_PRETTY_PRINT) . "\n";
        
        // Store result
        $results[] = [
            'test' => $test['name'],
            'type' => $test['type'],
            'success' => $response['success'] ?? false,
            'duration_ms' => $duration,
            'reference' => $response['reference'] ?? null,
            'nbc_reference' => $response['nbc_reference'] ?? null,
            'account_name' => $response['account_name'] ?? null,
            'message' => $response['message'] ?? $response['error'] ?? 'No message'
        ];
        
        // Display key information
        if ($test['type'] === 'lookup' && $response['success']) {
            echo "\n✅ Account Validated:\n";
            echo "   - Name: " . ($response['account_name'] ?? 'N/A') . "\n";
            echo "   - Status: " . ($response['account_status'] ?? 'N/A') . "\n";
            echo "   - Branch: " . ($response['branch_name'] ?? 'N/A') . "\n";
            echo "   - Can Receive: " . ($response['can_receive'] ? 'Yes' : 'No') . "\n";
            echo "   - Can Debit: " . ($response['can_debit'] ? 'Yes' : 'No') . "\n";
        }
        
        if ($test['type'] === 'transfer' && $response['success']) {
            echo "\n✅ Transfer Successful:\n";
            echo "   - Internal Ref: " . ($response['reference'] ?? 'N/A') . "\n";
            echo "   - NBC Ref: " . ($response['nbc_reference'] ?? 'N/A') . "\n";
            echo "   - Amount: " . number_format($test['params']['amount'], 2) . " TZS\n";
        }
        
    } catch (Exception $e) {
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        Log::channel('payments')->error("SIT TEST ERROR", [
            'test_name' => $test['name'],
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'duration_ms' => $duration
        ]);
        
        echo "\n❌ ERROR: " . $e->getMessage() . "\n";
        
        $results[] = [
            'test' => $test['name'],
            'type' => $test['type'],
            'success' => false,
            'duration_ms' => $duration,
            'error' => $e->getMessage()
        ];
    }
    
    // Add delay between tests
    if (next($tests)) {
        echo "\n⏳ Waiting 2 seconds before next test...\n";
        sleep(2);
    }
}

// Summary Report
echo "\n\n" . str_repeat("=", 80) . "\n";
echo "TEST SUMMARY REPORT\n";
echo str_repeat("=", 80) . "\n\n";

$totalTests = count($results);
$successfulTests = array_filter($results, function($r) { return $r['success']; });
$failedTests = array_filter($results, function($r) { return !$r['success']; });

// Categorize by type
$lookupTests = array_filter($results, function($r) { return $r['type'] === 'lookup'; });
$transferTests = array_filter($results, function($r) { return $r['type'] === 'transfer'; });
$statusTests = array_filter($results, function($r) { return $r['type'] === 'status'; });

echo "Total Tests: $totalTests\n";
echo "✅ Successful: " . count($successfulTests) . "\n";
echo "❌ Failed: " . count($failedTests) . "\n\n";

echo "BY TYPE:\n";
echo "- Lookups: " . count($lookupTests) . " (" . 
     count(array_filter($lookupTests, function($r) { return $r['success']; })) . " passed)\n";
echo "- Transfers: " . count($transferTests) . " (" . 
     count(array_filter($transferTests, function($r) { return $r['success']; })) . " passed)\n";
echo "- Status Checks: " . count($statusTests) . " (" . 
     count(array_filter($statusTests, function($r) { return $r['success']; })) . " passed)\n\n";

echo "DETAILED RESULTS:\n";
echo str_pad("Test Name", 50) . str_pad("Status", 10) . str_pad("Duration", 12) . "Message\n";
echo str_repeat("-", 110) . "\n";

foreach ($results as $result) {
    $status = $result['success'] ? '✅ PASS' : '❌ FAIL';
    $message = substr($result['message'] ?? $result['error'] ?? '', 0, 25);
    
    echo str_pad(substr($result['test'], 0, 48), 50);
    echo str_pad($status, 10);
    echo str_pad($result['duration_ms'] . ' ms', 12);
    echo $message . "\n";
    
    if (isset($result['reference']) && $result['reference']) {
        echo "    └─ Reference: {$result['reference']}\n";
    }
    if (isset($result['nbc_reference']) && $result['nbc_reference']) {
        echo "    └─ NBC Ref: {$result['nbc_reference']}\n";
    }
    if (isset($result['account_name']) && $result['account_name']) {
        echo "    └─ Account: {$result['account_name']}\n";
    }
}

// Calculate average response times
$avgLookupTime = count($lookupTests) > 0 ? 
    array_sum(array_column($lookupTests, 'duration_ms')) / count($lookupTests) : 0;
$avgTransferTime = count($transferTests) > 0 ? 
    array_sum(array_column($transferTests, 'duration_ms')) / count($transferTests) : 0;

echo "\n\nPERFORMANCE METRICS:\n";
echo "- Average Lookup Time: " . round($avgLookupTime, 2) . " ms\n";
echo "- Average Transfer Time: " . round($avgTransferTime, 2) . " ms\n";

// Save detailed report to file
$reportFile = __DIR__ . '/storage/logs/sit_internal_transfer_report_' . date('Ymd_His') . '.json';
file_put_contents($reportFile, json_encode([
    'test_date' => date('Y-m-d H:i:s'),
    'service' => 'InternalFundsTransferService',
    'environment' => 'SIT',
    'summary' => [
        'total' => $totalTests,
        'passed' => count($successfulTests),
        'failed' => count($failedTests),
        'lookups' => count($lookupTests),
        'transfers' => count($transferTests),
        'status_checks' => count($statusTests)
    ],
    'performance' => [
        'avg_lookup_time_ms' => round($avgLookupTime, 2),
        'avg_transfer_time_ms' => round($avgTransferTime, 2)
    ],
    'results' => $results,
    'test_data' => [
        'accounts' => [
            'saccos' => [
                'number' => '011191000035',
                'name' => 'CBN MICROFINANCE'
            ],
            'individual_1' => [
                'number' => '011201318462',
                'name' => 'BON JON JONES'
            ],
            'individual_2' => [
                'number' => '074206000029',
                'name' => 'BON JON JONES II'
            ]
        ],
        'transfer_references' => $transferReferences
    ]
], JSON_PRETTY_PRINT));

echo "\n\n📄 Detailed report saved to: $reportFile\n";
echo "📋 Transfer References Generated: " . count($transferReferences) . "\n";
if (!empty($transferReferences)) {
    echo "   Latest: " . end($transferReferences) . "\n";
}
echo "\n===== SIT INTERNAL TRANSFER SERVICE TEST COMPLETED =====\n";

Log::channel('payments')->info("===== SIT INTERNAL TRANSFER SERVICE TEST COMPLETED =====", [
    'summary' => [
        'total' => $totalTests,
        'passed' => count($successfulTests),
        'failed' => count($failedTests)
    ],
    'performance' => [
        'avg_lookup_ms' => round($avgLookupTime, 2),
        'avg_transfer_ms' => round($avgTransferTime, 2)
    ]
]);