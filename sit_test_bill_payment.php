<?php

/**
 * SIT (System Integration Testing) - Bill Payment Service
 * Real Request and Response Documentation
 * 
 * Test Data:
 * - GEPG Control Numbers: 991060011846 (EXACT), 991060011847 (PARTIAL)
 * - DSTV: 7029243019
 * - LUKU Meter: 43026323915
 * - SACCOS Account: 011191000035 (CBN MICROFINANCE)
 */

require_once __DIR__ . '/bootstrap/app.php';

use App\Services\Payments\BillPaymentService;
use Illuminate\Support\Facades\Log;

// Enable detailed logging
Log::channel('payments')->info("===== SIT BILL PAYMENT SERVICE TEST STARTED =====");

$service = new BillPaymentService();

// Test configurations
$tests = [
    // Test 1: GEPG Bill Inquiry (Exact Amount)
    [
        'name' => 'GEPG Bill Inquiry - Exact Amount',
        'type' => 'inquiry',
        'method' => 'inquireBill',
        'params' => [
            'bill_type' => BillPaymentService::BILL_TYPE_GEPG,
            'reference' => '991060011846',
            'additional_data' => [
                'account_number' => '011191000035' // SACCOS Account
            ]
        ]
    ],
    
    // Test 2: GEPG Bill Payment (Exact Amount)
    [
        'name' => 'GEPG Bill Payment - Exact Amount',
        'type' => 'payment',
        'method' => 'payBill',
        'params' => [
            'bill_type' => BillPaymentService::BILL_TYPE_GEPG,
            'payment_data' => [
                'control_number' => '991060011846',
                'from_account' => '011191000035',
                'amount' => 2000,
                'payer_name' => 'CBN MICROFINANCE',
                'bill_status' => 'PENDING',
                'sp_code' => 'SP2020',
                'narration' => 'GEPG Payment Test - Exact Amount'
            ]
        ]
    ],
    
    // Test 3: GEPG Bill Inquiry (Partial Amount)
    [
        'name' => 'GEPG Bill Inquiry - Partial Amount',
        'type' => 'inquiry',
        'method' => 'inquireBill',
        'params' => [
            'bill_type' => BillPaymentService::BILL_TYPE_GEPG,
            'reference' => '991060011847',
            'additional_data' => [
                'account_number' => '011191000035'
            ]
        ]
    ],
    
    // Test 4: GEPG Bill Payment (Partial Amount - 10000 of 50000)
    [
        'name' => 'GEPG Bill Payment - Partial Amount',
        'type' => 'payment',
        'method' => 'payBill',
        'params' => [
            'bill_type' => BillPaymentService::BILL_TYPE_GEPG,
            'payment_data' => [
                'control_number' => '991060011847',
                'from_account' => '011191000035',
                'amount' => 10000, // Partial payment
                'payer_name' => 'CBN MICROFINANCE',
                'bill_status' => 'PENDING',
                'sp_code' => 'SP2020',
                'narration' => 'GEPG Payment Test - Partial Amount'
            ]
        ]
    ],
    
    // Test 5: LUKU Meter Inquiry
    [
        'name' => 'LUKU Meter Inquiry',
        'type' => 'inquiry',
        'method' => 'inquireBill',
        'params' => [
            'bill_type' => BillPaymentService::BILL_TYPE_LUKU,
            'reference' => '43026323915',
            'additional_data' => [
                'account_number' => '011191000035'
            ]
        ]
    ],
    
    // Test 6: LUKU Payment
    [
        'name' => 'LUKU Payment',
        'type' => 'payment',
        'method' => 'payBill',
        'params' => [
            'bill_type' => BillPaymentService::BILL_TYPE_LUKU,
            'payment_data' => [
                'meter_number' => '43026323915',
                'from_account' => '011191000035',
                'amount' => 5000,
                'customer_name' => 'CBN MICROFINANCE',
                'customer_phone' => '255715000001',
                'narration' => 'LUKU Electricity Purchase'
            ]
        ]
    ],
    
    // Test 7: DSTV Inquiry (Generic Bill)
    [
        'name' => 'DSTV Inquiry',
        'type' => 'inquiry',
        'method' => 'inquireBill',
        'params' => [
            'bill_type' => 'DSTV',
            'reference' => '7029243019',
            'additional_data' => [
                'account_number' => '011191000035',
                'package_type' => 'COMPACT'
            ]
        ]
    ],
    
    // Test 8: DSTV Payment
    [
        'name' => 'DSTV Payment',
        'type' => 'payment',
        'method' => 'payBill',
        'params' => [
            'bill_type' => 'DSTV',
            'payment_data' => [
                'bill_reference' => '7029243019',
                'from_account' => '011191000035',
                'amount' => 25000,
                'customer_name' => 'CBN MICROFINANCE',
                'narration' => 'DSTV Subscription Payment'
            ]
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
    
    try {
        // Execute the service method
        $method = $test['method'];
        if ($test['type'] === 'inquiry') {
            $response = $service->$method(
                $test['params']['bill_type'],
                $test['params']['reference'],
                $test['params']['additional_data'] ?? []
            );
        } else {
            $response = $service->$method(
                $test['params']['bill_type'],
                $test['params']['payment_data']
            );
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
            'token' => $response['token'] ?? null,
            'message' => $response['message'] ?? $response['error'] ?? 'No message'
        ];
        
        // Special handling for LUKU token
        if (isset($response['token']) && !empty($response['token'])) {
            echo "\n🔑 LUKU TOKEN: {$response['token']}\n";
            echo "⚡ UNITS: {$response['units']}\n";
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

echo "Total Tests: $totalTests\n";
echo "✅ Successful: " . count($successfulTests) . "\n";
echo "❌ Failed: " . count($failedTests) . "\n\n";

echo "DETAILED RESULTS:\n";
echo str_pad("Test Name", 40) . str_pad("Status", 10) . str_pad("Duration", 12) . "Message\n";
echo str_repeat("-", 100) . "\n";

foreach ($results as $result) {
    $status = $result['success'] ? '✅ PASS' : '❌ FAIL';
    $message = substr($result['message'] ?? $result['error'] ?? '', 0, 30);
    
    echo str_pad(substr($result['test'], 0, 38), 40);
    echo str_pad($status, 10);
    echo str_pad($result['duration_ms'] . ' ms', 12);
    echo $message . "\n";
    
    if (isset($result['reference']) && $result['reference']) {
        echo "    └─ Reference: {$result['reference']}\n";
    }
    if (isset($result['token']) && $result['token']) {
        echo "    └─ Token: {$result['token']}\n";
    }
}

// Save detailed report to file
$reportFile = __DIR__ . '/storage/logs/sit_bill_payment_report_' . date('Ymd_His') . '.json';
file_put_contents($reportFile, json_encode([
    'test_date' => date('Y-m-d H:i:s'),
    'service' => 'BillPaymentService',
    'environment' => 'SIT',
    'summary' => [
        'total' => $totalTests,
        'passed' => count($successfulTests),
        'failed' => count($failedTests)
    ],
    'results' => $results,
    'test_data' => [
        'gepg_control_numbers' => ['991060011846', '991060011847'],
        'luku_meter' => '43026323915',
        'dstv_account' => '7029243019',
        'saccos_account' => '011191000035'
    ]
], JSON_PRETTY_PRINT));

echo "\n\n📄 Detailed report saved to: $reportFile\n";
echo "===== SIT BILL PAYMENT SERVICE TEST COMPLETED =====\n";

Log::channel('payments')->info("===== SIT BILL PAYMENT SERVICE TEST COMPLETED =====", [
    'summary' => [
        'total' => $totalTests,
        'passed' => count($successfulTests),
        'failed' => count($failedTests)
    ]
]);