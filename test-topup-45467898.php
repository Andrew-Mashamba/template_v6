<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\NbcBillsPaymentService;
use Illuminate\Support\Facades\Log;

echo "\n=====================================\n";
echo "NBC TOP-UP PAYMENT TEST\n";
echo "Bill Reference: 45467898\n";
echo "=====================================\n\n";

try {
    $service = new NbcBillsPaymentService();
    
    // Step 1: Fetch billers to confirm Top-up service
    echo "Step 1: Fetching Top-up service details...\n";
    $billers = $service->getBillers();
    
    $allBillers = $billers['flat'] ?? [];
    $topupBiller = null;
    
    foreach ($allBillers as $biller) {
        if (stripos($biller['shortName'], 'Top-up') !== false || 
            $biller['spCode'] === 'bpesp1004002') {
            $topupBiller = $biller;
            break;
        }
    }
    
    if (!$topupBiller) {
        throw new Exception("Top-up service not found in NBC system");
    }
    
    echo "✅ Found Top-up Service\n";
    echo "   Service Name: " . $topupBiller['shortName'] . "\n";
    echo "   SP Code: " . $topupBiller['spCode'] . "\n";
    echo "   Category: " . ($topupBiller['category'] ?? 'N/A') . "\n\n";
    
    // Step 2: Perform bill inquiry for 45467898
    echo "=====================================\n";
    echo "Step 2: Bill Inquiry for 45467898\n";
    echo "=====================================\n";
    
    $billRef = '45467898';
    
    $inquiryPayload = [
        'spCode' => $topupBiller['spCode'],
        'billRef' => $billRef,
        'userId' => auth()->id() ?? 'TEST_USER',
        'branchCode' => '015',
        'extraFields' => []
    ];
    
    echo "Sending inquiry request...\n";
    echo "SP Code: " . $topupBiller['spCode'] . "\n";
    echo "Bill Ref: " . $billRef . "\n\n";
    
    $inquiryResult = $service->inquireDetailedBill($inquiryPayload);
    
    if ($inquiryResult['success'] ?? false) {
        echo "✅ Bill inquiry successful!\n\n";
        
        $billDetails = $inquiryResult['data'];
        
        echo "Bill Details:\n";
        echo "=============\n";
        
        // Display all available fields
        $fields = [
            'billRef' => 'Bill Reference',
            'serviceName' => 'Service Name',
            'description' => 'Description',
            'billedName' => 'Customer Name',
            'totalAmount' => 'Total Amount',
            'balance' => 'Balance Due',
            'currency' => 'Currency',
            'paymentMode' => 'Payment Mode',
            'expiryDate' => 'Expiry Date',
            'phoneNumber' => 'Phone Number',
            'email' => 'Email',
            'creditAccount' => 'Credit Account',
            'creditCurrency' => 'Credit Currency'
        ];
        
        foreach ($fields as $key => $label) {
            if (isset($billDetails[$key]) && !empty($billDetails[$key])) {
                if ($key === 'totalAmount' || $key === 'balance') {
                    echo $label . ": " . number_format($billDetails[$key], 2) . " " . ($billDetails['currency'] ?? 'TZS') . "\n";
                } else {
                    echo $label . ": " . $billDetails[$key] . "\n";
                }
            }
        }
        
        // Show any extra fields
        if (isset($billDetails['extraFields']) && !empty($billDetails['extraFields'])) {
            echo "\nExtra Fields:\n";
            foreach ($billDetails['extraFields'] as $key => $value) {
                echo "  " . $key . ": " . json_encode($value) . "\n";
            }
        }
        
        // Step 3: Process payment
        echo "\n=====================================\n";
        echo "Step 3: Processing Payment\n";
        echo "=====================================\n";
        
        // Determine amount based on payment mode
        $amount = null;
        $paymentMode = $billDetails['paymentMode'] ?? 'exact';
        
        if ($paymentMode === 'exact') {
            $amount = $billDetails['balance'] ?? $billDetails['totalAmount'];
            echo "Payment Mode: EXACT (must pay exact amount)\n";
        } elseif ($paymentMode === 'full') {
            $amount = $billDetails['balance'] ?? $billDetails['totalAmount'];
            echo "Payment Mode: FULL (minimum amount required)\n";
        } elseif ($paymentMode === 'partial') {
            $amount = '5000'; // Pay partial amount for testing
            echo "Payment Mode: PARTIAL (can pay any amount)\n";
        } else {
            $amount = $billDetails['balance'] ?? $billDetails['totalAmount'] ?? '10000';
            echo "Payment Mode: " . strtoupper($paymentMode) . "\n";
        }
        
        echo "Amount to Pay: " . number_format($amount, 2) . " " . ($billDetails['currency'] ?? 'TZS') . "\n\n";
        
        $paymentPayload = [
            'spCode' => $topupBiller['spCode'],
            'billRef' => $billRef,
            'amount' => $amount,
            'callbackUrl' => url('/api/nbc/payment/callback'),
            'userId' => auth()->id() ?? 'TEST_USER',
            'branchCode' => '015',
            'channelRef' => 'PAY' . time(),
            'creditAccount' => $billDetails['creditAccount'] ?? '',
            'creditCurrency' => $billDetails['creditCurrency'] ?? 'TZS',
            'debitAccount' => '28012040011', // Test account
            'debitCurrency' => 'TZS',
            'payerName' => 'Test User',
            'payerPhone' => '255715000000',
            'payerEmail' => 'test@example.com',
            'narration' => 'Top-up Payment for ' . $billRef,
            'paymentType' => 'ACCOUNT',
            'channelCode' => 'APP',
            'extraFields' => $billDetails['extraFields'] ?? new stdClass(),
            'inquiryRawResponse' => $inquiryResult['rawResponse'] ?? '',
            'billDetails' => $billDetails
        ];
        
        echo "Initiating payment...\n";
        $paymentResult = $service->processPaymentAsync($paymentPayload);
        
        if ($paymentResult['status'] === 'processing') {
            echo "✅ Payment initiated successfully!\n\n";
            
            echo "Payment Reference Details:\n";
            echo "Gateway Reference: " . ($paymentResult['gatewayRef'] ?? 'N/A') . "\n";
            echo "Channel Reference: " . ($paymentResult['channelRef'] ?? 'N/A') . "\n";
            echo "Status: PROCESSING\n";
            echo "Message: " . ($paymentResult['message'] ?? 'Payment is being processed') . "\n";
            
            // Step 4: Check payment status after a delay
            echo "\n=====================================\n";
            echo "Step 4: Checking Payment Status\n";
            echo "=====================================\n";
            
            echo "Waiting 5 seconds for processing...\n";
            for ($i = 5; $i > 0; $i--) {
                echo $i . "... ";
                sleep(1);
            }
            echo "\n\n";
            
            $statusPayload = [
                'spCode' => $topupBiller['spCode'],
                'billRef' => $billRef,
                'channelRef' => $paymentResult['channelRef'] ?? $paymentPayload['channelRef']
            ];
            
            echo "Checking payment status...\n";
            $statusResult = $service->checkPaymentStatus($statusPayload);
            
            if ($statusResult['status'] === 'success') {
                $paymentDetails = $statusResult['data']['paymentDetails'] ?? [];
                
                echo "\n✅ Status Check Successful\n\n";
                echo "Payment Status Details:\n";
                echo "======================\n";
                
                $statusFields = [
                    'accountingStatus' => 'Accounting Status',
                    'billerNotified' => 'Biller Notification',
                    'gatewayRef' => 'Gateway Reference',
                    'billerReceipt' => 'Biller Receipt',
                    'transactionTime' => 'Transaction Time',
                    'remarks' => 'Remarks'
                ];
                
                foreach ($statusFields as $key => $label) {
                    if (isset($paymentDetails[$key]) && !empty($paymentDetails[$key])) {
                        echo $label . ": " . $paymentDetails[$key] . "\n";
                    }
                }
                
                if (($paymentDetails['accountingStatus'] ?? '') === 'success') {
                    echo "\n🎉 PAYMENT COMPLETED SUCCESSFULLY!\n";
                } elseif (($paymentDetails['accountingStatus'] ?? '') === 'failed') {
                    echo "\n❌ PAYMENT FAILED\n";
                } else {
                    echo "\n⏳ PAYMENT STILL PROCESSING\n";
                }
            } else {
                echo "❌ Status check failed: " . ($statusResult['message'] ?? 'Unknown error') . "\n";
            }
            
        } elseif ($paymentResult['status'] === 'error') {
            echo "❌ Payment initiation failed!\n";
            echo "Error: " . ($paymentResult['message'] ?? 'Unknown error') . "\n";
        } else {
            echo "⚠️ Unexpected payment response status: " . ($paymentResult['status'] ?? 'unknown') . "\n";
            echo "Message: " . ($paymentResult['message'] ?? 'No message') . "\n";
        }
        
    } else {
        echo "❌ Bill inquiry failed!\n";
        echo "Error: " . ($inquiryResult['message'] ?? 'Unknown error') . "\n";
        echo "Status Code: " . ($inquiryResult['statusCode'] ?? 'N/A') . "\n\n";
        
        // Provide helpful information based on error
        if ($inquiryResult['statusCode'] === '601') {
            echo "📝 This usually means:\n";
            echo "   - The bill reference doesn't exist\n";
            echo "   - The bill has already been paid\n";
            echo "   - The reference format is incorrect\n";
        } elseif ($inquiryResult['statusCode'] === '602') {
            echo "📝 Validation failed. Check:\n";
            echo "   - Bill reference format\n";
            echo "   - Required fields\n";
        } elseif ($inquiryResult['statusCode'] === '636') {
            echo "📝 The Top-up service is currently disabled.\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    Log::error('Top-up Payment Test Error', [
        'billRef' => '45467898',
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n=====================================\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "=====================================\n\n";