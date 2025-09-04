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
echo "     GEPG PAYMENT TEST SCRIPT\n";
echo "===========================================\n";
echo "Control Number: 991060011847\n";
echo "Amount Range: 1000 - 2000 TZS\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

try {
    // Initialize services
    $logger = new GepgLoggerService();
    $gepgGateway = new GepgGatewayService($logger);
    
    // Test data
    $controlNumber = '991060011847';
    $accountNumber = '011191000035';
    $currency = 'TZS';
    $testAmount = 1500; // Amount between 1000-2000 as requested
    
    // STEP 1: VERIFY CONTROL NUMBER
    echo "STEP 1: VERIFYING CONTROL NUMBER\n";
    echo "-----------------------------------\n";
    echo "Control Number: $controlNumber\n";
    echo "Account Number: $accountNumber\n";
    echo "Currency: $currency\n\n";
    
    Log::info('=== GEPG TEST: STARTING CONTROL NUMBER VERIFICATION ===', [
        'control_number' => $controlNumber,
        'account' => $accountNumber,
        'currency' => $currency,
        'timestamp' => now()->toDateTimeString()
    ]);
    
    echo "Sending verification request...\n";
    $verificationResult = $gepgGateway->verifyControlNumber($controlNumber, $accountNumber, $currency);
    
    Log::info('GEPG TEST: Verification Response', [
        'response' => $verificationResult,
        'timestamp' => now()->toDateTimeString()
    ]);
    
    echo "\nVerification Response:\n";
    echo json_encode($verificationResult, JSON_PRETTY_PRINT) . "\n\n";
    
    // Check if verification was successful
    $canProceed = false;
    $billAmount = $testAmount;
    
    if (isset($verificationResult['GepgGatewayBillQryResp'])) {
        $billResp = $verificationResult['GepgGatewayBillQryResp'];
        
        if (isset($billResp['BillHdr']['BillStsCode'])) {
            $statusCode = $billResp['BillHdr']['BillStsCode'];
            $statusDesc = $billResp['BillHdr']['BillStsDesc'] ?? 'Unknown';
            
            echo "Bill Status Code: $statusCode\n";
            echo "Bill Status Description: $statusDesc\n\n";
            
            // Check if bill can be paid (7336 = Active)
            if ($statusCode == '7336') {
                echo "✓ Bill is ACTIVE and can be paid!\n\n";
                $canProceed = true;
                
                // Extract bill details
                if (isset($billResp['BillDtls'])) {
                    $billDetails = $billResp['BillDtls'];
                    echo "Bill Details:\n";
                    echo "- Description: " . ($billDetails['BillDesc'] ?? 'N/A') . "\n";
                    echo "- Bill Amount: " . ($billDetails['BillAmt'] ?? 'N/A') . "\n";
                    echo "- Paid Amount: " . ($billDetails['PaidAmt'] ?? '0') . "\n";
                    echo "- Expiry Date: " . ($billDetails['BillExpDt'] ?? 'N/A') . "\n";
                    echo "- Service Provider: " . ($billDetails['SpName'] ?? 'N/A') . "\n\n";
                    
                    // Use bill amount if available and within range
                    if (isset($billDetails['BillAmt'])) {
                        $originalAmount = floatval($billDetails['BillAmt']);
                        if ($originalAmount >= 1000 && $originalAmount <= 2000) {
                            $billAmount = $originalAmount;
                        } else {
                            echo "Note: Original bill amount ($originalAmount) is outside test range. Using $billAmount TZS\n\n";
                        }
                    }
                }
            } else {
                echo "✗ Bill cannot be paid. Status: $statusDesc (Code: $statusCode)\n";
                
                // Provide specific messages based on status codes
                switch($statusCode) {
                    case '7101':
                        echo "  → Bill is not payable (may be expired or already paid)\n";
                        break;
                    case '7204':
                        echo "  → Bill not found. Check control number.\n";
                        break;
                    case '7205':
                        echo "  → Bill has already been paid.\n";
                        break;
                    case '7206':
                        echo "  → Bill has expired.\n";
                        break;
                    case '7207':
                        echo "  → Bill has been cancelled.\n";
                        break;
                    default:
                        echo "  → Unknown status code.\n";
                }
            }
        }
    } else {
        echo "✗ Invalid response format from GEPG gateway\n";
        echo "Response: " . json_encode($verificationResult, JSON_PRETTY_PRINT) . "\n";
    }
    
    // STEP 2: PROCESS PAYMENT (if verification successful)
    if ($canProceed) {
        echo "\n===========================================\n";
        echo "STEP 2: PROCESSING PAYMENT\n";
        echo "-----------------------------------\n";
        echo "Amount to pay: TZS " . number_format($billAmount, 2) . "\n\n";
        
        // Prepare payment data
        $paymentData = [
            'control_number' => $controlNumber,
            'amount' => $billAmount,
            'currency' => 'TZS',
            'debit_account_no' => $accountNumber,
            'debit_account_type' => 'CASA',
            'debit_account_currency' => 'TZS',
            'bank_type' => 'ONUS',
            'forex' => 'N',
            'items' => [
                [
                    'item_ref' => '1',
                    'amount' => $billAmount,
                    'currency' => 'TZS'
                ]
            ],
            // Additional optional fields
            'payer_name' => 'Test Payer',
            'payer_phone' => '255000000000',
            'remarks' => 'Test payment for control number ' . $controlNumber
        ];
        
        Log::info('=== GEPG TEST: STARTING PAYMENT PROCESSING ===', [
            'payment_data' => $paymentData,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        echo "Sending payment request...\n";
        $paymentResult = $gepgGateway->payBill($paymentData);
        
        Log::info('GEPG TEST: Payment Response', [
            'response' => $paymentResult,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        echo "\nPayment Response:\n";
        echo json_encode($paymentResult, JSON_PRETTY_PRINT) . "\n\n";
        
        // Check payment result
        if (isset($paymentResult['success']) && $paymentResult['success']) {
            echo "✓ PAYMENT SUCCESSFUL!\n";
            echo "  Transaction details have been recorded.\n";
            
            // Record transaction in database
            try {
                $txnId = DB::table('transactions')->insertGetId([
                    'branch_id' => 1,
                    'service_name' => 'GEPG',
                    'service_code' => 'GEPG_PAY',
                    'action_id' => 'GEPG_TEST_' . now()->timestamp,
                    'amount' => $billAmount,
                    'reference_number' => $controlNumber,
                    'description' => 'GEPG Test Payment - Control: ' . $controlNumber,
                    'status' => 'completed',
                    'transaction_type' => 'payment',
                    'currency' => 'TZS',
                    'created_by' => 1,
                    'bank' => 'NBC',
                    'bank_account' => $accountNumber,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                
                echo "  Transaction ID: $txnId\n";
            } catch (\Exception $e) {
                echo "  Warning: Could not record transaction in database: " . $e->getMessage() . "\n";
            }
        } else {
            $errorMsg = $paymentResult['message'] ?? 'Unknown error';
            echo "✗ PAYMENT FAILED: $errorMsg\n";
            
            if (isset($paymentResult['details'])) {
                echo "  Details: " . json_encode($paymentResult['details'], JSON_PRETTY_PRINT) . "\n";
            }
        }
    } else {
        echo "\n✗ Cannot proceed with payment - Bill verification failed\n";
    }
    
    // STEP 3: CHECK PAYMENT STATUS (Optional)
    if ($canProceed) {
        echo "\n===========================================\n";
        echo "STEP 3: CHECKING PAYMENT STATUS\n";
        echo "-----------------------------------\n";
        
        sleep(2); // Wait a bit before checking status
        
        $statusData = [
            'control_number' => $controlNumber,
            'channel_ref' => 'TEST_' . now()->timestamp,
            'cbp_gw_ref' => 'CBPGW_' . now()->timestamp
        ];
        
        echo "Checking payment status...\n";
        $statusResult = $gepgGateway->checkPaymentStatus($statusData);
        
        echo "\nStatus Check Response:\n";
        echo json_encode($statusResult, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (\Exception $e) {
    echo "\n✗ ERROR OCCURRED:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Trace:\n" . $e->getTraceAsString() . "\n";
    
    Log::error('GEPG TEST: Exception occurred', [
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n===========================================\n";
echo "TEST COMPLETED\n";
echo "Check logs at: storage/logs/gepg-" . date('Y-m-d') . ".log\n";
echo "Check Laravel log at: storage/logs/laravel-" . date('Y-m-d') . ".log\n";
echo "===========================================\n\n";