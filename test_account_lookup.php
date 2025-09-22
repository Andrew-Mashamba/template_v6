<?php

/**
 * Account Lookup Test - InternalFundsTransferService
 * 
 * Test Accounts:
 * - 011191000035 (CBN MICROFINANCE - SACCOS Account)
 * - 011201318462 (BON JON JONES - Individual Account)
 * - 074206000029 (BON JON JONES II - Individual Account)
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\Payments\InternalFundsTransferService;
use Illuminate\Support\Facades\Log;

echo "\n";
echo "========================================\n";
echo "  ACCOUNT LOOKUP TEST EXECUTION\n";
echo "  InternalFundsTransferService\n";
echo "========================================\n";
echo "\n";

try {
    $service = new InternalFundsTransferService();
    
    // Test accounts configuration
    $testAccounts = [
        [
            'account_number' => '011191000035',
            'description' => 'CBN MICROFINANCE (SACCOS Account)',
            'type' => 'source'
        ],
        [
            'account_number' => '011201318462',
            'description' => 'BON JON JONES (Individual Account)',
            'type' => 'destination'
        ],
        [
            'account_number' => '074206000029',
            'description' => 'BON JON JONES II (Individual Account)',
            'type' => 'destination'
        ]
    ];
    
    $results = [];
    
    // Perform lookups for each account
    foreach ($testAccounts as $index => $account) {
        $testNumber = $index + 1;
        echo "TEST $testNumber: {$account['description']}\n";
        echo str_repeat("-", 60) . "\n";
        echo "Account Number: {$account['account_number']}\n";
        echo "Lookup Type: {$account['type']}\n";
        echo "\n";
        
        $startTime = microtime(true);
        
        try {
            // Perform the account lookup
            $response = $service->lookupAccount(
                $account['account_number'],
                $account['type']
            );
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            // Display results
            if ($response['success']) {
                echo "✅ LOOKUP SUCCESSFUL\n";
                echo "\nAccount Details:\n";
                
                // Format the response for display
                $displayData = [
                    'Account Name' => $response['account_name'] ?? 'N/A',
                    'Account Number' => $response['account_number'] ?? $account['account_number'],
                    'Account Type' => $response['account_type'] ?? 'N/A',
                    'Currency' => $response['currency'] ?? 'TZS',
                    'Status' => $response['status'] ?? 'N/A',
                    'Branch' => $response['branch'] ?? 'N/A',
                    'Customer ID' => $response['customer_id'] ?? 'N/A',
                    'Available Balance' => isset($response['available_balance']) 
                        ? number_format($response['available_balance'], 2) 
                        : 'N/A',
                    'Actual Balance' => isset($response['actual_balance']) 
                        ? number_format($response['actual_balance'], 2) 
                        : 'N/A'
                ];
                
                foreach ($displayData as $label => $value) {
                    echo "  " . str_pad($label . ":", 20) . $value . "\n";
                }
                
                // Check for validation flags
                if (isset($response['is_valid'])) {
                    echo "\nValidation Status:\n";
                    echo "  Is Valid: " . ($response['is_valid'] ? 'Yes' : 'No') . "\n";
                }
                
                if (isset($response['can_transfer'])) {
                    echo "  Can Transfer: " . ($response['can_transfer'] ? 'Yes' : 'No') . "\n";
                }
                
                if (isset($response['validation_messages']) && !empty($response['validation_messages'])) {
                    echo "\nValidation Messages:\n";
                    foreach ($response['validation_messages'] as $msg) {
                        echo "  - $msg\n";
                    }
                }
                
            } else {
                echo "❌ LOOKUP FAILED\n";
                echo "Error: " . ($response['error'] ?? 'Unknown error') . "\n";
                
                if (isset($response['error_code'])) {
                    echo "Error Code: {$response['error_code']}\n";
                }
                
                if (isset($response['details'])) {
                    echo "Details: " . json_encode($response['details']) . "\n";
                }
            }
            
            echo "\nResponse Time: {$duration} ms\n";
            
            // Store result for summary
            $results[] = [
                'account' => $account['account_number'],
                'description' => $account['description'],
                'success' => $response['success'],
                'duration' => $duration,
                'account_name' => $response['account_name'] ?? null
            ];
            
            // Log the raw response for debugging
            Log::channel('payments')->info("Account Lookup Test Result", [
                'account_number' => $account['account_number'],
                'response' => $response,
                'duration_ms' => $duration
            ]);
            
        } catch (Exception $e) {
            echo "❌ EXCEPTION OCCURRED\n";
            echo "Error: " . $e->getMessage() . "\n";
            echo "File: " . $e->getFile() . "\n";
            echo "Line: " . $e->getLine() . "\n";
            
            $results[] = [
                'account' => $account['account_number'],
                'description' => $account['description'],
                'success' => false,
                'duration' => round((microtime(true) - $startTime) * 1000, 2),
                'error' => $e->getMessage()
            ];
        }
        
        echo "\n" . str_repeat("=", 60) . "\n\n";
    }
    
    // Display summary
    echo "SUMMARY\n";
    echo str_repeat("=", 60) . "\n";
    echo "\nTest Results:\n";
    
    $table = [];
    foreach ($results as $result) {
        $status = $result['success'] ? '✅ Success' : '❌ Failed';
        $accountInfo = $result['success'] && isset($result['account_name']) 
            ? $result['account_name'] 
            : ($result['error'] ?? 'N/A');
        
        $table[] = [
            'Account' => $result['account'],
            'Status' => $status,
            'Time (ms)' => $result['duration'],
            'Result' => substr($accountInfo, 0, 40)
        ];
    }
    
    // Display table
    $headers = array_keys($table[0]);
    $columnWidths = [];
    foreach ($headers as $header) {
        $columnWidths[$header] = strlen($header);
        foreach ($table as $row) {
            $columnWidths[$header] = max($columnWidths[$header], strlen($row[$header]));
        }
    }
    
    // Print headers
    foreach ($headers as $header) {
        echo str_pad($header, $columnWidths[$header] + 2);
    }
    echo "\n";
    
    // Print separator
    foreach ($headers as $header) {
        echo str_repeat("-", $columnWidths[$header] + 2);
    }
    echo "\n";
    
    // Print rows
    foreach ($table as $row) {
        foreach ($headers as $header) {
            echo str_pad($row[$header], $columnWidths[$header] + 2);
        }
        echo "\n";
    }
    
    echo "\n";
    
    // Statistics
    $successCount = array_filter($results, fn($r) => $r['success']);
    $totalTime = array_sum(array_column($results, 'duration'));
    $avgTime = count($results) > 0 ? $totalTime / count($results) : 0;
    
    echo "\nStatistics:\n";
    echo "  Total Tests: " . count($results) . "\n";
    echo "  Successful: " . count($successCount) . "\n";
    echo "  Failed: " . (count($results) - count($successCount)) . "\n";
    echo "  Total Time: " . number_format($totalTime, 2) . " ms\n";
    echo "  Average Time: " . number_format($avgTime, 2) . " ms\n";
    
    // Check if results are from mock data
    echo "\n";
    echo "Note: Results may be from mock data if NBC API is not accessible.\n";
    echo "Check storage/logs/laravel.log for detailed API responses.\n";
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n========================================\n";
echo "  TEST COMPLETED\n";
echo "========================================\n\n";