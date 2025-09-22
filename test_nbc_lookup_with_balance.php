<?php

/**
 * Test NBC Account Lookup with Balance Extraction
 * Testing the updated InternalFundsTransferService
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use App\Services\Payments\InternalFundsTransferService;

echo "\n";
echo "========================================\n";
echo "  NBC ACCOUNT LOOKUP WITH BALANCE TEST\n";
echo "========================================\n";
echo "\n";

try {
    $service = new InternalFundsTransferService();
    
    // Test accounts
    $testAccounts = [
        '011191000035' => 'CBN MICROFINANCE (SACCOS)',
        '011201318462' => 'BON JON JONES',
        '074206000029' => 'BON JON JONES II'
    ];
    
    foreach ($testAccounts as $accountNumber => $description) {
        echo "Testing: $description\n";
        echo "Account: $accountNumber\n";
        echo str_repeat("-", 50) . "\n";
        
        $startTime = microtime(true);
        $result = $service->lookupAccount($accountNumber, 'source');
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($result['success']) {
            echo "✅ SUCCESS\n";
            echo "Account Name: " . ($result['account_name'] ?? 'N/A') . "\n";
            echo "Customer Name: " . ($result['customer_name'] ?? 'N/A') . "\n";
            echo "Branch: " . ($result['branch_name'] ?? 'N/A') . "\n";
            echo "Status: " . ($result['account_status'] ?? 'N/A') . "\n";
            echo "Currency: " . ($result['currency'] ?? 'N/A') . "\n";
            
            // The key information - Available Balance
            if (isset($result['available_balance'])) {
                echo "💰 Available Balance: " . number_format($result['available_balance'], 2) . " " . ($result['currency'] ?? 'TZS') . "\n";
            } else {
                echo "Balance: Not available\n";
            }
            
            if (isset($result['blocked'])) {
                echo "Blocked: " . ($result['blocked'] ? 'Yes' : 'No') . "\n";
            }
            if (isset($result['restricted'])) {
                echo "Restricted: " . ($result['restricted'] ? 'Yes' : 'No') . "\n";
            }
            
            echo "Response Time: {$duration} ms\n";
        } else {
            echo "❌ FAILED\n";
            echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        }
        
        echo "\n" . str_repeat("=", 50) . "\n\n";
    }
    
    echo "Test completed successfully!\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n========================================\n";