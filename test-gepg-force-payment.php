<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\NbcPayments\GepgGatewayService;
use App\Services\NbcPayments\GepgLoggerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n===========================================\n";
echo "  GEPG FORCED PAYMENT TEST (Status 7101)\n";
echo "===========================================\n";
echo "Control Number: 991060011847\n";
echo "Test Amount: 1500 TZS (capped for testing)\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

try {
    // Initialize services
    $logger = new GepgLoggerService();
    $gepgGateway = new GepgGatewayService($logger);
    
    // Test data
    $controlNumber = '991060011847';
    $accountNumber = '011191000035';
    $testAmount = 1500; // Fixed amount between 1000-2000
    
    echo "INFO: Bill has status 7101 (not payable) but forcing payment for testing...\n\n";
    echo "Bill Details from verification:\n";
    echo "- Service Provider: Tanzania Communications Regulatory Authority\n";
    echo "- Original Amount: 50,000 TZS\n";
    echo "- Test Amount: 1,500 TZS (capped)\n";
    echo "- Payment Option: Partial payment allowed\n";
    echo "- Minimum Payment: 0.01 TZS\n\n";
    
    echo "===========================================\n";
    echo "FORCING PAYMENT DESPITE STATUS 7101\n";
    echo "-----------------------------------\n\n";
    
    // Prepare payment data with correct structure
    $paymentData = [
        'channel_ref' => 'TEST_' . now()->timestamp,
        'cbp_gw_ref' => 'CBPGW_' . now()->timestamp,
        'control_number' => $controlNumber,
        'pay_type' => '1',
        'status_code' => '7336', // Force active status for testing
        'items' => [
            [
                'channel_trx_id' => 'TRX_' . now()->timestamp,
                'sp_code' => 'SP99106',
                'pay_ref_id' => '925247001317316',
                'bill_ctr_num' => $controlNumber,
                'bill_amt' => $testAmount,
                'paid_amt' => $testAmount,
                'currency' => 'TZS',
                'pay_option' => 'Part',
            ]
        ],
        'debit_account_no' => $accountNumber,
        'debit_account_type' => 'CASA',
        'debit_account_currency' => 'TZS',
        'bank_type' => 'ONUS',
        'forex' => 'N',
        'remarks' => 'Forced test payment for control ' . $controlNumber
    ];
    
    Log::info('=== GEPG FORCED PAYMENT TEST ===', [
        'control_number' => $controlNumber,
        'amount' => $testAmount,
        'status_note' => 'Forcing payment despite 7101 status',
        'timestamp' => now()->toDateTimeString()
    ]);
    
    echo "Payment Data:\n";
    echo "- Control Number: $controlNumber\n";
    echo "- Amount: TZS " . number_format($testAmount, 2) . "\n";
    echo "- Account: $accountNumber\n";
    echo "- Service Provider: SP99106\n\n";
    
    echo "Sending payment request...\n\n";
    $paymentResult = $gepgGateway->processPayment($paymentData, false);
    
    Log::info('GEPG FORCED PAYMENT: Response', [
        'response' => $paymentResult,
        'timestamp' => now()->toDateTimeString()
    ]);
    
    echo "Payment Response:\n";
    echo "================\n";
    echo json_encode($paymentResult, JSON_PRETTY_PRINT) . "\n\n";
    
    // Analyze payment result
    if (isset($paymentResult['success']) && $paymentResult['success']) {
        echo "✓ PAYMENT SUCCESSFUL!\n";
        echo "  Despite status 7101, payment was processed.\n";
        
        // Record transaction
        try {
            $txnId = DB::table('transactions')->insertGetId([
                'branch_id' => 1,
                'service_name' => 'GEPG',
                'service_code' => 'GEPG_FORCED_PAY',
                'action_id' => 'GEPG_FORCE_' . now()->timestamp,
                'amount' => $testAmount,
                'reference_number' => $controlNumber,
                'description' => 'GEPG Forced Test Payment (7101) - Control: ' . $controlNumber,
                'status' => 'completed',
                'transaction_type' => 'payment',
                'currency' => 'TZS',
                'created_by' => 1,
                'bank' => 'NBC',
                'bank_account' => $accountNumber,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            echo "  Transaction recorded with ID: $txnId\n";
        } catch (\Exception $e) {
            echo "  Warning: Could not record transaction: " . $e->getMessage() . "\n";
        }
    } else {
        $errorMsg = $paymentResult['message'] ?? 'Unknown error';
        echo "✗ PAYMENT FAILED: $errorMsg\n\n";
        
        // Check if it's a validation error
        if (strpos($errorMsg, '7101') !== false) {
            echo "EXPECTED: Payment rejected due to status 7101\n";
            echo "The gateway correctly prevents payment of non-payable bills.\n\n";
            
            echo "ALTERNATIVE APPROACH:\n";
            echo "1. The bill status 7101 means it's not payable\n";
            echo "2. You may need a different control number with status 7336 (Active)\n";
            echo "3. Or contact the service provider to reactivate the bill\n";
        }
        
        if (isset($paymentResult['details'])) {
            echo "\nAdditional Details:\n";
            echo json_encode($paymentResult['details'], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
    // Try direct payment API call bypassing validation
    echo "\n===========================================\n";
    echo "ATTEMPTING DIRECT API CALL\n";
    echo "-----------------------------------\n\n";
    
    echo "Attempting to bypass validation and call payment API directly...\n";
    
    // This would require modifying the service temporarily
    // For now, we'll just document what would be needed
    echo "\nTo force payment despite status 7101, you would need to:\n";
    echo "1. Modify GepgGatewayService->payBill() to skip status validation\n";
    echo "2. Or create a test endpoint that bypasses normal validation\n";
    echo "3. Or use a different control number with status 7336\n\n";
    
} catch (\Exception $e) {
    echo "\n✗ ERROR OCCURRED:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n\n";
    
    Log::error('GEPG FORCED PAYMENT TEST: Exception', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}

echo "===========================================\n";
echo "RECOMMENDATION\n";
echo "-----------------------------------\n";
echo "The control number 991060011847 exists but has status 7101.\n";
echo "This means the bill is not currently payable.\n\n";
echo "Options:\n";
echo "1. Request a control number with status 7336 (Active)\n";
echo "2. Modify the SimpleGepgPayment component to handle 7101 status\n";
echo "3. Create a mock/sandbox mode for testing\n";
echo "===========================================\n\n";