<?php

/**
 * SIT (System Integration Testing) - Mobile Wallet Transfer Service
 * Real Request and Response Documentation
 * 
 * Test Data:
 * - SACCOS Account: 011191000035 (CBN MICROFINANCE)
 * - Test Phone Numbers for different providers
 * 
 * This service handles transfers from NBC accounts to mobile wallets
 * Limited to amounts < 20,000,000 TZS (TIPS only)
 */

require_once __DIR__ . '/bootstrap/app.php';

use App\Services\Payments\MobileWalletTransferService;
use Illuminate\Support\Facades\Log;

// Enable detailed logging
Log::channel('payments')->info("===== SIT MOBILE WALLET SERVICE TEST STARTED =====");

$service = new MobileWalletTransferService();

// Test configurations
$tests = [
    // Test 1: M-Pesa Wallet Lookup
    [
        'name' => 'M-Pesa Wallet Lookup',
        'type' => 'lookup',
        'method' => 'lookupWallet',
        'params' => [
            'phone_number' => '0765123456',  // Test M-Pesa number
            'provider' => 'MPESA',
            'amount' => 1000
        ]
    ],
    
    // Test 2: M-Pesa Transfer
    [
        'name' => 'M-Pesa Transfer (5,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'phone_number' => '0765123456',
            'provider' => 'MPESA',
            'amount' => 5000,
            'narration' => 'SIT Test - M-Pesa Transfer',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'OUR'
        ]
    ],
    
    // Test 3: Airtel Money Wallet Lookup
    [
        'name' => 'Airtel Money Wallet Lookup',
        'type' => 'lookup',
        'method' => 'lookupWallet',
        'params' => [
            'phone_number' => '0785234567',  // Test Airtel number
            'provider' => 'AIRTELMONEY',
            'amount' => 1000
        ]
    ],
    
    // Test 4: Airtel Money Transfer
    [
        'name' => 'Airtel Money Transfer (10,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'phone_number' => '0785234567',
            'provider' => 'AIRTELMONEY',
            'amount' => 10000,
            'narration' => 'SIT Test - Airtel Money Transfer',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'OUR'
        ]
    ],
    
    // Test 5: Tigo Pesa Wallet Lookup
    [
        'name' => 'Tigo Pesa Wallet Lookup',
        'type' => 'lookup',
        'method' => 'lookupWallet',
        'params' => [
            'phone_number' => '0715345678',  // Test Tigo number
            'provider' => 'TIGOPESA',
            'amount' => 1000
        ]
    ],
    
    // Test 6: Tigo Pesa Transfer
    [
        'name' => 'Tigo Pesa Transfer (15,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'phone_number' => '0715345678',
            'provider' => 'TIGOPESA',
            'amount' => 15000,
            'narration' => 'SIT Test - Tigo Pesa Transfer',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'SHA'
        ]
    ],
    
    // Test 7: Halo Pesa Wallet Lookup
    [
        'name' => 'Halo Pesa Wallet Lookup',
        'type' => 'lookup',
        'method' => 'lookupWallet',
        'params' => [
            'phone_number' => '0625456789',  // Test Halo number
            'provider' => 'HALOPESA',
            'amount' => 1000
        ]
    ],
    
    // Test 8: Halo Pesa Transfer
    [
        'name' => 'Halo Pesa Transfer (20,000 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'phone_number' => '0625456789',
            'provider' => 'HALOPESA',
            'amount' => 20000,
            'narration' => 'SIT Test - Halo Pesa Transfer',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'BEN'
        ]
    ],
    
    // Test 9: Large Amount Transfer (Near Limit)
    [
        'name' => 'Large Amount M-Pesa Transfer (19,999,999 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'phone_number' => '0765123456',
            'provider' => 'MPESA',
            'amount' => 19999999,  // Just below 20M limit
            'narration' => 'SIT Test - Large M-Pesa Transfer',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'OUR'
        ]
    ],
    
    // Test 10: Minimum Amount Transfer
    [
        'name' => 'Minimum Amount Transfer (100 TZS)',
        'type' => 'transfer',
        'method' => 'transfer',
        'params' => [
            'from_account' => '011191000035',
            'phone_number' => '0765123456',
            'provider' => 'MPESA',
            'amount' => 100,
            'narration' => 'SIT Test - Minimum Amount Transfer',
            'payer_phone' => '255715000001',
            'charge_bearer' => 'OUR'
        ]
    ],
    
    // Test 11: Phone Number Format Test - International Format
    [
        'name' => 'Phone Format Test - International (255765123456)',
        'type' => 'lookup',
        'method' => 'lookupWallet',
        'params' => [
            'phone_number' => '255765123456',  // International format
            'provider' => 'MPESA',
            'amount' => 1000
        ]
    ],
    
    // Test 12: Phone Number Format Test - Without Zero
    [
        'name' => 'Phone Format Test - Without Zero (765123456)',
        'type' => 'lookup',
        'method' => 'lookupWallet',
        'params' => [
            'phone_number' => '765123456',  // Without leading zero
            'provider' => 'MPESA',
            'amount' => 1000
        ]
    ],
    
    // Test 13: Get Available Providers
    [
        'name' => 'Get Available Wallet Providers',
        'type' => 'providers',
        'method' => 'getProviders',
        'params' => []
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
    if ($test['type'] !== 'providers') {
        Log::channel('payments')->info("SIT TEST REQUEST", [
            'test_name' => $test['name'],
            'test_type' => $test['type'],
            'method' => $test['method'],
            'request_payload' => json_encode($test['params'], JSON_PRETTY_PRINT),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        
        echo "\n📤 REQUEST:\n";
        echo json_encode($test['params'], JSON_PRETTY_PRINT) . "\n";
    }
    
    try {
        // Execute the service method
        $method = $test['method'];
        if ($test['type'] === 'lookup') {
            $response = $service->$method(
                $test['params']['phone_number'],
                $test['params']['provider'],
                $test['params']['amount']
            );
        } elseif ($test['type'] === 'providers') {
            $providers = $service->$method();
            $response = [
                'success' => true,
                'providers' => $providers
            ];
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
            'provider' => $test['params']['provider'] ?? null,
            'message' => $response['message'] ?? $response['error'] ?? 'No message'
        ];
        
        // Display key information
        if ($test['type'] === 'lookup' && $response['success']) {
            echo "\n✅ Wallet Validated:\n";
            echo "   - Provider: " . ($response['provider'] ?? 'N/A') . "\n";
            echo "   - Account Name: " . ($response['account_name'] ?? 'N/A') . "\n";
            echo "   - Phone: " . ($response['phone_number'] ?? 'N/A') . "\n";
        }
        
        if ($test['type'] === 'transfer' && $response['success']) {
            echo "\n✅ Transfer Successful:\n";
            echo "   - Reference: " . ($response['reference'] ?? 'N/A') . "\n";
            echo "   - NBC Ref: " . ($response['nbc_reference'] ?? 'N/A') . "\n";
            echo "   - Provider: " . ($response['provider'] ?? 'N/A') . "\n";
            echo "   - Amount: " . number_format($test['params']['amount'], 2) . " TZS\n";
        }
        
        if ($test['type'] === 'providers') {
            echo "\n📱 Available Wallet Providers:\n";
            foreach ($response['providers'] as $provider) {
                echo "   - $provider\n";
            }
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
$providerTests = array_filter($results, function($r) { return $r['type'] === 'providers'; });

// Categorize by provider
$mpesaTests = array_filter($results, function($r) { return ($r['provider'] ?? '') === 'MPESA'; });
$airtelTests = array_filter($results, function($r) { return ($r['provider'] ?? '') === 'AIRTELMONEY'; });
$tigoTests = array_filter($results, function($r) { return ($r['provider'] ?? '') === 'TIGOPESA'; });
$haloTests = array_filter($results, function($r) { return ($r['provider'] ?? '') === 'HALOPESA'; });

echo "Total Tests: $totalTests\n";
echo "✅ Successful: " . count($successfulTests) . "\n";
echo "❌ Failed: " . count($failedTests) . "\n\n";

echo "BY TYPE:\n";
echo "- Lookups: " . count($lookupTests) . " (" . 
     count(array_filter($lookupTests, function($r) { return $r['success']; })) . " passed)\n";
echo "- Transfers: " . count($transferTests) . " (" . 
     count(array_filter($transferTests, function($r) { return $r['success']; })) . " passed)\n";
echo "- Provider Info: " . count($providerTests) . " (" . 
     count(array_filter($providerTests, function($r) { return $r['success']; })) . " passed)\n\n";

echo "BY PROVIDER:\n";
echo "- M-Pesa: " . count($mpesaTests) . " tests\n";
echo "- Airtel Money: " . count($airtelTests) . " tests\n";
echo "- Tigo Pesa: " . count($tigoTests) . " tests\n";
echo "- Halo Pesa: " . count($haloTests) . " tests\n\n";

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
    if (isset($result['provider']) && $result['provider']) {
        echo "    └─ Provider: {$result['provider']}\n";
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
$reportFile = __DIR__ . '/storage/logs/sit_mobile_wallet_report_' . date('Ymd_His') . '.json';
file_put_contents($reportFile, json_encode([
    'test_date' => date('Y-m-d H:i:s'),
    'service' => 'MobileWalletTransferService',
    'environment' => 'SIT',
    'summary' => [
        'total' => $totalTests,
        'passed' => count($successfulTests),
        'failed' => count($failedTests),
        'lookups' => count($lookupTests),
        'transfers' => count($transferTests)
    ],
    'by_provider' => [
        'mpesa' => count($mpesaTests),
        'airtel' => count($airtelTests),
        'tigo' => count($tigoTests),
        'halo' => count($haloTests)
    ],
    'performance' => [
        'avg_lookup_time_ms' => round($avgLookupTime, 2),
        'avg_transfer_time_ms' => round($avgTransferTime, 2)
    ],
    'results' => $results,
    'test_data' => [
        'saccos_account' => '011191000035',
        'providers' => [
            'MPESA' => 'VMCASHIN',
            'AIRTELMONEY' => 'AMCASHIN',
            'TIGOPESA' => 'TPCASHIN',
            'HALOPESA' => 'HPCASHIN'
        ],
        'max_amount' => '19,999,999 TZS (TIPS only)',
        'test_phones' => [
            'mpesa' => '0765123456',
            'airtel' => '0785234567',
            'tigo' => '0715345678',
            'halo' => '0625456789'
        ]
    ]
], JSON_PRETTY_PRINT));

echo "\n\n📄 Detailed report saved to: $reportFile\n";
echo "===== SIT MOBILE WALLET SERVICE TEST COMPLETED =====\n";

Log::channel('payments')->info("===== SIT MOBILE WALLET SERVICE TEST COMPLETED =====", [
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