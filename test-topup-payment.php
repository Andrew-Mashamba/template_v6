<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\NbcBillsPaymentService;
use Illuminate\Support\Facades\Log;

echo "\n=====================================\n";
echo "NBC TOP-UP SERVICE PAYMENT TEST\n";
echo "=====================================\n\n";

try {
    $service = new NbcBillsPaymentService();
    
    // Step 1: Fetch all billers
    echo "Step 1: Fetching available billers from NBC...\n";
    $billers = $service->getBillers();
    
    $allBillers = $billers['flat'] ?? [];
    
    echo "Total active billers found: " . count($allBillers) . "\n\n";
    
    if (count($allBillers) === 0) {
        throw new Exception("No billers available in the NBC system");
    }
    
    // Display available billers
    echo "Available Billers:\n";
    echo "==================\n";
    foreach ($allBillers as $index => $biller) {
        echo ($index + 1) . ". Service: " . $biller['shortName'] . "\n";
        echo "   SP Code: " . $biller['spCode'] . "\n";
        if (isset($biller['fullName'])) {
            echo "   Full Name: " . $biller['fullName'] . "\n";
        }
        if (isset($biller['category'])) {
            echo "   Category: " . $biller['category'] . "\n";
        }
        echo "\n";
    }
    
    // Use the first available biller (Top-up)
    $selectedBiller = $allBillers[0];
    echo "Selected Biller: " . $selectedBiller['shortName'] . " (" . $selectedBiller['spCode'] . ")\n";
    
    // Step 2: Try different bill references to find a valid one
    echo "\n=====================================\n";
    echo "Step 2: Bill Inquiry\n";
    echo "=====================================\n";
    
    // Common test references for voucher/top-up services
    $testReferences = [
        '7029243019',     // Original DSTV number (might work for vouchers)
        'TEST001',        // Generic test reference
        '1234567890',     // Common test number
        '0000000001',     // Sequential test
        'VOUCHER001',     // Voucher specific
        '255715000000',   // Phone number format
        '0715000000',     // Local phone format
    ];
    
    $billDetails = null;
    $validBillRef = null;
    
    foreach ($testReferences as $billRef) {
        echo "\nTrying bill reference: " . $billRef . "\n";
        
        $inquiryPayload = [
            'spCode' => $selectedBiller['spCode'],
            'billRef' => $billRef,
            'userId' => 'TEST_USER',
            'branchCode' => '015',
            'extraFields' => []
        ];
        
        $inquiryResult = $service->inquireDetailedBill($inquiryPayload);
        
        if ($inquiryResult['success'] ?? false) {
            echo "✅ Valid bill reference found: " . $billRef . "\n";
            $billDetails = $inquiryResult['data'];
            $validBillRef = $billRef;
            break;
        } else {
            echo "❌ Invalid: " . ($inquiryResult['message'] ?? 'Unknown error') . "\n";
        }
    }
    
    if ($billDetails && $validBillRef) {
        echo "\n✅ Bill inquiry successful!\n\n";
        
        echo "Bill Details:\n";
        echo "-------------\n";
        echo "Bill Reference: " . $validBillRef . "\n";
        echo "Service Name: " . ($billDetails['serviceName'] ?? 'N/A') . "\n";
        echo "Description: " . ($billDetails['description'] ?? 'N/A') . "\n";
        echo "Customer Name: " . ($billDetails['billedName'] ?? 'N/A') . "\n";
        echo "Total Amount: " . ($billDetails['totalAmount'] ?? 'N/A') . " " . ($billDetails['currency'] ?? 'TZS') . "\n";
        echo "Balance Due: " . ($billDetails['balance'] ?? 'N/A') . " " . ($billDetails['currency'] ?? 'TZS') . "\n";
        echo "Payment Mode: " . ($billDetails['paymentMode'] ?? 'N/A') . "\n";
        
        if (isset($billDetails['expiryDate'])) {
            echo "Expiry Date: " . $billDetails['expiryDate'] . "\n";
        }
        
        // Step 3: Process payment
        echo "\n=====================================\n";
        echo "Step 3: Payment Processing\n";
        echo "=====================================\n";
        
        $amount = $billDetails['balance'] ?? $billDetails['totalAmount'] ?? '10000';
        echo "Amount to pay: " . $amount . " " . ($billDetails['currency'] ?? 'TZS') . "\n";
        
        $paymentPayload = [
            'spCode' => $selectedBiller['spCode'],
            'billRef' => $validBillRef,
            'amount' => $amount,
            'callbackUrl' => 'https://saccos.test/api/nbc/payment/callback',
            'userId' => 'TEST_USER',
            'branchCode' => '015',
            'creditAccount' => $billDetails['creditAccount'] ?? '',
            'creditCurrency' => $billDetails['creditCurrency'] ?? 'TZS',
            'debitAccount' => '28012040011',
            'debitCurrency' => 'TZS',
            'payerName' => 'Test User',
            'payerPhone' => '255715000000',
            'payerEmail' => 'test@example.com',
            'narration' => 'Top-up Payment Test',
            'paymentType' => 'ACCOUNT',
            'channelCode' => 'APP',
            'extraFields' => new stdClass(),
            'inquiryRawResponse' => $inquiryResult['rawResponse'] ?? '',
            'billDetails' => $billDetails
        ];
        
        echo "\nProcessing payment (async)...\n";
        $paymentResult = $service->processPaymentAsync($paymentPayload);
        
        if ($paymentResult['status'] === 'processing') {
            echo "✅ Payment initiated successfully!\n";
            echo "Gateway Reference: " . ($paymentResult['gatewayRef'] ?? 'N/A') . "\n";
            echo "Channel Reference: " . ($paymentResult['channelRef'] ?? 'N/A') . "\n";
            echo "Status: Processing\n";
            
            // Step 4: Check payment status
            echo "\n=====================================\n";
            echo "Step 4: Checking Payment Status\n";
            echo "=====================================\n";
            
            echo "Waiting 3 seconds before checking status...\n";
            sleep(3);
            
            $statusPayload = [
                'spCode' => $selectedBiller['spCode'],
                'billRef' => $validBillRef,
                'channelRef' => $paymentResult['channelRef'] ?? ''
            ];
            
            $statusResult = $service->checkPaymentStatus($statusPayload);
            
            if ($statusResult['status'] === 'success') {
                $paymentDetails = $statusResult['data']['paymentDetails'] ?? [];
                echo "\nPayment Status Details:\n";
                echo "----------------------\n";
                echo "Accounting Status: " . ($paymentDetails['accountingStatus'] ?? 'Unknown') . "\n";
                echo "Biller Notified: " . ($paymentDetails['billerNotified'] ?? 'Unknown') . "\n";
                
                if (isset($paymentDetails['billerReceipt'])) {
                    echo "Biller Receipt: " . $paymentDetails['billerReceipt'] . "\n";
                }
                
                if (isset($paymentDetails['remarks'])) {
                    echo "Remarks: " . $paymentDetails['remarks'] . "\n";
                }
                
                if ($paymentDetails['accountingStatus'] === 'success') {
                    echo "\n✅ Payment completed successfully!\n";
                } else {
                    echo "\n⏳ Payment is still being processed...\n";
                }
            } else {
                echo "Status check response: " . ($statusResult['message'] ?? 'No status available') . "\n";
            }
        } else {
            echo "❌ Payment initiation failed!\n";
            echo "Error: " . ($paymentResult['message'] ?? 'Unknown error') . "\n";
        }
        
    } else {
        echo "\n❌ No valid bill reference found for the Top-up service.\n";
        echo "\nNote: The Top-up service may require:\n";
        echo "- A specific format for bill references\n";
        echo "- Pre-existing voucher codes\n";
        echo "- Special account numbers\n";
        echo "\nPlease contact NBC support for valid test references for the Top-up service.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    Log::error('Top-up Payment Test Error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n=====================================\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "=====================================\n\n";