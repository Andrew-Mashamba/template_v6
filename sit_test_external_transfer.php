<?php

/**
 * SIT (System Integration Testing) - External Funds Transfer Service
 * Real Request and Response Documentation
 * 
 * Test Data:
 * - SACCOS Account: 011191000035 (CBN MICROFINANCE)
 * - Individual Account 1: 011201318462 (BON JON JONES)
 * - Individual Account 2: 074206000029 (BON JON JONES II)
 */

require_once __DIR__ . '/bootstrap/app.php';

use App\Services\Payments\ExternalFundsTransferService;
use Illuminate\Support\Facades\Log;

// Enable detailed logging
Log::channel('payments')->info("===== SIT EXTERNAL TRANSFER SERVICE TEST STARTED =====");

$service = new ExternalFundsTransferService();

// Test configurations
$tests = [
    // Test 1: Account Lookup - NBC to CRDB Bank
    [
        'name' => 'Account Lookup - CRDB Bank',
        'type' => 'lookup',
        'method' => 'lookupAccount',
        'params' => [
            'account_number' => '0150388888801',
            'bank_code' => 'CORUTZTZ',  // CRDB Bank code
            'amount' => 1000
        ]
    ],
    
    // Test 2: Account Lookup - NBC to NMB Bank
    [
        'name' => 'Account Lookup - NMB Bank',
        'type' => 'lookup',
        'method' => 'lookupAccount',
        'params' => [
            'account_number' => '20110024477',
            'bank_code' => 'NMIBTZTZ',  // NMB Bank code
            'amount' => 1000
        ]
    ],
    
    // Test 3: TIPS Transfer (Amount < 20M) - NBC to CRDB
    [
        'name' => 'TIPS Transfer - NBC to CRDB (5,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',  // SACCOS Account
            'to_account' => '0150388888801',
            'bank_code' => 'CORUTZTZ',
            'amount' => 5000,
            'narration' => 'SIT Test - TIPS Transfer to CRDB',
            'sender_name' => 'CBN MICROFINANCE',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'OUR'
        ]
    ],
    
    // Test 4: TIPS Transfer - NBC to NMB
    [
        'name' => 'TIPS Transfer - NBC to NMB (10,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'to_account' => '20110024477',
            'bank_code' => 'NMIBTZTZ',
            'amount' => 10000,
            'narration' => 'SIT Test - TIPS Transfer to NMB',
            'sender_name' => 'CBN MICROFINANCE',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'OUR'
        ]
    ],
    
    // Test 5: Account Lookup - NBC to Equity Bank
    [
        'name' => 'Account Lookup - Equity Bank',
        'type' => 'lookup',
        'method' => 'lookupAccount',
        'params' => [
            'account_number' => '0240199548101',
            'bank_code' => 'EQBLTZTZ',  // Equity Bank code
            'amount' => 1000
        ]
    ],
    
    // Test 6: TIPS Transfer - NBC to Equity Bank
    [
        'name' => 'TIPS Transfer - NBC to Equity (15,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'to_account' => '0240199548101',
            'bank_code' => 'EQBLTZTZ',
            'amount' => 15000,
            'narration' => 'SIT Test - TIPS Transfer to Equity Bank',
            'sender_name' => 'CBN MICROFINANCE',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'SHA'  // Shared charges
        ]
    ],
    
    // Test 7: TISS Transfer (Amount >= 20M) - Large Amount to CRDB
    [
        'name' => 'TISS Transfer - NBC to CRDB (25,000,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'to_account' => '0150388888801',
            'bank_code' => 'CORUTZTZ',
            'amount' => 25000000,  // 25 Million - will route through TISS
            'narration' => 'SIT Test - TISS Large Transfer to CRDB',
            'sender_name' => 'CBN MICROFINANCE',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'OUR',
            'purpose_code' => 'CASH'
        ]
    ],
    
    // Test 8: Boundary Test - Exactly 20M (TISS threshold)
    [
        'name' => 'Boundary Test - Exactly 20M TZS',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'to_account' => '20110024477',
            'bank_code' => 'NMIBTZTZ',
            'amount' => 20000000,  // Exactly 20 Million - will route through TISS
            'narration' => 'SIT Test - Boundary Amount Transfer',
            'sender_name' => 'CBN MICROFINANCE',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'OUR',
            'purpose_code' => 'TRADE'
        ]
    ],
    
    // Test 9: Just Below TISS Threshold
    [
        'name' => 'Just Below TISS Threshold - 19,999,999 TZS',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'to_account' => '0150388888801',
            'bank_code' => 'CORUTZTZ',
            'amount' => 19999999,  // Just below 20 Million - will route through TIPS
            'narration' => 'SIT Test - Below Threshold Transfer',
            'sender_name' => 'CBN MICROFINANCE',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'BEN'  // Beneficiary bears charges
        ]
    ]
];

// Execute tests
$results = [];
foreach ($tests as $test) {
    echo "\n\n" . str_repeat("=", 80) . "\n";
    echo "TEST: {$test['name']}\n";
    echo str_repeat("=", 80) . "\n";
    
    $startTime = microtime(true);
    
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
    
    // Determine routing system for transfers
    if ($test['type'] === 'transfer') {
        $routingSystem = $test['params']['amount'] >= 20000000 ? 'TISS' : 'TIPS';
        echo "\n🔀 ROUTING: $routingSystem (Amount: " . number_format($test['params']['amount']) . " TZS)\n";
    }
    
    try {
        // Execute the service method
        $method = $test['method'];
        if ($test['type'] === 'lookup') {
            $response = $service->$method(
                $test['params']['account_number'],
                $test['params']['bank_code'],
                $test['params']['amount']
            );
        } else {
            $response = $service->$method($test['params']);
        }
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        // Log the response
        Log::channel('payments')->info("SIT TEST RESPONSE", [
            'test_name' => $test['name'],
            'success' => $response['success'] ?? false,
            'response_payload' => json_encode($response, JSON_PRETTY_PRINT),
            'duration_ms' => $duration,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        echo "\n📥 RESPONSE:\n";
        echo json_encode($response, JSON_PRETTY_PRINT) . "\n";
        
        // Store result
        $results[] = [
            'test' => $test['name'],
            'type' => $test['type'],
            'success' => $response['success'] ?? false,
            'duration_ms' => $duration,
            'reference' => $response['reference'] ?? null,
            'nbc_reference' => $response['nbc_reference'] ?? null,
            'routing_system' => $response['routing_system'] ?? null,
            'message' => $response['message'] ?? $response['error'] ?? 'No message'
        ];
        
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

echo "Total Tests: $totalTests\n";
echo "✅ Successful: " . count($successfulTests) . "\n";
echo "❌ Failed: " . count($failedTests) . "\n\n";

// Separate by routing system
$tipsTests = array_filter($results, function($r) { return ($r['routing_system'] ?? '') === 'TIPS'; });
$tissTests = array_filter($results, function($r) { return ($r['routing_system'] ?? '') === 'TISS'; });

echo "TIPS Transfers: " . count($tipsTests) . "\n";
echo "TISS Transfers: " . count($tissTests) . "\n\n";

echo "DETAILED RESULTS:\n";
echo str_pad("Test Name", 45) . str_pad("Status", 10) . str_pad("Duration", 12) . "Message\n";
echo str_repeat("-", 100) . "\n";

foreach ($results as $result) {
    $status = $result['success'] ? '✅ PASS' : '❌ FAIL';
    $message = substr($result['message'] ?? $result['error'] ?? '', 0, 25);
    
    echo str_pad(substr($result['test'], 0, 43), 45);
    echo str_pad($status, 10);
    echo str_pad($result['duration_ms'] . ' ms', 12);
    echo $message . "\n";
    
    if (isset($result['reference']) && $result['reference']) {
        echo "    └─ Reference: {$result['reference']}\n";
    }
    if (isset($result['nbc_reference']) && $result['nbc_reference']) {
        echo "    └─ NBC Ref: {$result['nbc_reference']}\n";
    }
    if (isset($result['routing_system']) && $result['routing_system']) {
        echo "    └─ Routing: {$result['routing_system']}\n";
    }
}

// Save detailed report to file
$reportFile = __DIR__ . '/storage/logs/sit_external_transfer_report_' . date('Ymd_His') . '.json';
file_put_contents($reportFile, json_encode([
    'test_date' => date('Y-m-d H:i:s'),
    'service' => 'ExternalFundsTransferService',
    'environment' => 'SIT',
    'summary' => [
        'total' => $totalTests,
        'passed' => count($successfulTests),
        'failed' => count($failedTests),
        'tips_transfers' => count($tipsTests),
        'tiss_transfers' => count($tissTests)
    ],
    'results' => $results,
    'test_data' => [
        'saccos_account' => '011191000035',
        'test_banks' => [
            'CRDB' => 'CORUTZTZ',
            'NMB' => 'NMIBTZTZ',
            'EQUITY' => 'EQBLTZTZ'
        ],
        'routing_thresholds' => [
            'TIPS' => '< 20,000,000 TZS',
            'TISS' => '>= 20,000,000 TZS'
        ]
    ]
], JSON_PRETTY_PRINT));

echo "\n\n📄 Detailed report saved to: $reportFile\n";
echo "===== SIT EXTERNAL TRANSFER SERVICE TEST COMPLETED =====\n";

Log::channel('payments')->info("===== SIT EXTERNAL TRANSFER SERVICE TEST COMPLETED =====", [
    'summary' => [
        'total' => $totalTests,
        'passed' => count($successfulTests),
        'failed' => count($failedTests),
        'tips' => count($tipsTests),
        'tiss' => count($tissTests)
    ]
]);