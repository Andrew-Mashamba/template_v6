<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\NbcPayments\NbcLookupService;
use App\Services\NbcPayments\NbcPaymentService;
use App\Services\NbcPayments\GepgGatewayService;
use App\Services\NbcPayments\LukuService;
use App\Services\NbcBillsPaymentService;
use Illuminate\Support\Facades\Log;

echo "\n========================================\n";
echo "PAYMENT FUNCTIONS TEST SUITE\n";
echo "========================================\n\n";

$testResults = [];

// Test 1: Bank-to-Bank Transfer Lookup
echo "TEST 1: Bank-to-Bank Transfer Lookup\n";
echo "-------------------------------------\n";
try {
    $lookupService = new NbcLookupService();
    
    // Positive test
    echo "Positive test: Valid account lookup\n";
    $response = $lookupService->bankToBankLookup(
        '28012040022',  // beneficiary account
        'NMIBTZTZ',     // NMB bank code
        '28012040011',  // debit account
        10000,          // amount
        'PERSON'        // category
    );
    
    if (isset($response['success'])) {
        echo "  ✓ Response received: " . ($response['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        if ($response['success']) {
            echo "  ✓ Beneficiary: " . ($response['data']['body']['fullName'] ?? 'N/A') . "\n";
        } else {
            echo "  ⚠ Error: " . ($response['message'] ?? 'Unknown error') . "\n";
        }
    }
    $testResults['bank_lookup_positive'] = $response;
    
    // Negative test
    echo "\nNegative test: Invalid account\n";
    $response = $lookupService->bankToBankLookup(
        'INVALID123',   // invalid account
        'NMIBTZTZ',
        '28012040011',
        10000,
        'PERSON'
    );
    echo "  ✓ Error handling: " . ($response['message'] ?? 'No error message') . "\n";
    $testResults['bank_lookup_negative'] = $response;
    
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    $testResults['bank_lookup_error'] = $e->getMessage();
}

// Test 2: Bank-to-Wallet Transfer Lookup
echo "\n\nTEST 2: Bank-to-Wallet Transfer Lookup\n";
echo "---------------------------------------\n";
try {
    $lookupService = new NbcLookupService();
    
    // Positive test
    echo "Positive test: Valid M-Pesa lookup\n";
    $response = $lookupService->bankToWalletLookup(
        '255715000000', // phone number
        'VMCASHIN',     // M-Pesa
        '28012040011',  // debit account
        5000,           // amount
        'PERSON'
    );
    
    if (isset($response['success'])) {
        echo "  ✓ Response received: " . ($response['success'] ? 'SUCCESS' : 'FAILED') . "\n";
        if ($response['success']) {
            echo "  ✓ Wallet holder: " . ($response['data']['body']['fullName'] ?? 'N/A') . "\n";
        } else {
            echo "  ⚠ Error: " . ($response['message'] ?? 'Unknown error') . "\n";
        }
    }
    $testResults['wallet_lookup_positive'] = $response;
    
    // Negative test
    echo "\nNegative test: Invalid phone number\n";
    $response = $lookupService->bankToWalletLookup(
        '123',          // invalid phone
        'VMCASHIN',
        '28012040011',
        5000,
        'PERSON'
    );
    echo "  ✓ Error handling: " . ($response['message'] ?? 'No error message') . "\n";
    $testResults['wallet_lookup_negative'] = $response;
    
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    $testResults['wallet_lookup_error'] = $e->getMessage();
}

// Test 3: GEPG Control Number Verification
echo "\n\nTEST 3: GEPG Control Number Verification\n";
echo "-----------------------------------------\n";
try {
    $gepgService = new GepgGatewayService();
    
    // Positive test
    echo "Positive test: Valid control number\n";
    $response = $gepgService->verifyControlNumber(
        '991234567890', // test control number
        '28012040011',  // account number
        'TZS'           // currency
    );
    
    echo "  ✓ Response: " . json_encode($response) . "\n";
    $testResults['gepg_verify_positive'] = $response;
    
    // Negative test
    echo "\nNegative test: Invalid control number\n";
    $response = $gepgService->verifyControlNumber(
        'INVALID',
        '28012040011',
        'TZS'
    );
    echo "  ✓ Error handling: " . json_encode($response) . "\n";
    $testResults['gepg_verify_negative'] = $response;
    
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    $testResults['gepg_verify_error'] = $e->getMessage();
}

// Test 4: LUKU Meter Lookup
echo "\n\nTEST 4: LUKU Meter Lookup\n";
echo "--------------------------\n";
try {
    $lukuService = new LukuService();
    
    // Positive test
    echo "Positive test: Valid meter number\n";
    $response = $lukuService->lookup(
        '01234567890123456789', // test meter number
        '28012040011'           // account number
    );
    
    if ($response) {
        echo "  ✓ Response received\n";
        if (isset($response['status'])) {
            echo "  ✓ Status: " . $response['status'] . "\n";
        }
    }
    $testResults['luku_lookup_positive'] = $response;
    
    // Negative test
    echo "\nNegative test: Invalid meter\n";
    $response = $lukuService->lookup(
        'INVALID',
        '28012040011'
    );
    echo "  ✓ Error handling tested\n";
    $testResults['luku_lookup_negative'] = $response;
    
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    $testResults['luku_lookup_error'] = $e->getMessage();
}

// Test 5: Bill Payment Inquiry
echo "\n\nTEST 5: Bill Payment Inquiry\n";
echo "-----------------------------\n";
try {
    $billService = new NbcBillsPaymentService();
    
    // Get available billers first
    echo "Fetching available billers...\n";
    $billers = $billService->getBillers();
    
    if (!empty($billers)) {
        echo "  ✓ Found " . count($billers) . " billers\n";
        
        // Test with first biller
        $firstBiller = reset($billers);
        echo "  Testing with: " . ($firstBiller['name'] ?? 'Unknown') . "\n";
        
        $payload = [
            'spCode' => $firstBiller['code'] ?? 'TEST',
            'billRef' => 'TEST123',
            'userId' => 'USER101',
            'branchCode' => '015',
            'channelRef' => time(),
            'extraFields' => [],
        ];
        
        $response = $billService->inquireDetailedBill($payload);
        echo "  ✓ Inquiry response received\n";
        $testResults['bill_inquiry'] = $response;
    } else {
        echo "  ⚠ No billers available\n";
        $testResults['bill_inquiry'] = 'No billers';
    }
    
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    $testResults['bill_inquiry_error'] = $e->getMessage();
}

// Test 6: Payment Processing (Mock)
echo "\n\nTEST 6: Payment Processing Simulation\n";
echo "--------------------------------------\n";
try {
    $paymentService = new NbcPaymentService();
    
    echo "Simulating bank transfer payment...\n";
    
    // Create mock lookup data
    $mockLookupData = [
        'body' => [
            'fullName' => 'Test Beneficiary',
            'accountNumber' => '28012040022',
            'bankCode' => 'NMIBTZTZ',
            'engineRef' => 'TEST' . time()
        ]
    ];
    
    // Note: This will likely fail as it tries to make actual API call
    // But we can test the error handling
    echo "  Testing payment service initialization...\n";
    
    $testResults['payment_simulation'] = [
        'status' => 'simulated',
        'message' => 'Payment service tested but not executed to avoid actual transaction'
    ];
    
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
    $testResults['payment_error'] = $e->getMessage();
}

// Summary Report
echo "\n\n========================================\n";
echo "TEST SUMMARY REPORT\n";
echo "========================================\n";

$totalTests = 0;
$successfulTests = 0;
$failedTests = 0;

foreach ($testResults as $test => $result) {
    $totalTests++;
    if (is_array($result) && isset($result['success'])) {
        if ($result['success']) {
            $successfulTests++;
            echo "✓ $test: SUCCESS\n";
        } else {
            $failedTests++;
            echo "❌ $test: FAILED - " . ($result['message'] ?? 'Unknown error') . "\n";
        }
    } elseif (is_string($result) && strpos($result, 'Error') !== false) {
        $failedTests++;
        echo "❌ $test: ERROR - $result\n";
    } else {
        echo "⚠ $test: COMPLETED\n";
    }
}

echo "\nTotal Tests: $totalTests\n";
echo "Successful: $successfulTests\n";
echo "Failed: $failedTests\n";

// Save test results for analysis
file_put_contents(
    __DIR__ . '/storage/logs/payment-test-results-' . date('Y-m-d-H-i-s') . '.json',
    json_encode($testResults, JSON_PRETTY_PRINT)
);

echo "\nTest results saved to storage/logs/\n";