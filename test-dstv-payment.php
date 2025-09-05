<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\NbcBillsPaymentService;
use Illuminate\Support\Facades\Log;

echo "\n=====================================\n";
echo "NBC DSTV PAYMENT TEST\n";
echo "Account: 7029243019\n";
echo "=====================================\n\n";

try {
    $service = new NbcBillsPaymentService();
    
    // Step 1: Fetch all billers and search for DSTV
    echo "Step 1: Fetching billers from NBC...\n";
    $billers = $service->getBillers();
    
    $dstvBiller = null;
    $allBillers = $billers['flat'] ?? [];
    
    echo "Total active billers found: " . count($allBillers) . "\n";
    
    // Search for DSTV biller
    foreach ($allBillers as $biller) {
        if (stripos($biller['shortName'] ?? '', 'DSTV') !== false || 
            stripos($biller['fullName'] ?? '', 'DSTV') !== false ||
            stripos($biller['shortName'] ?? '', 'DStv') !== false ||
            stripos($biller['fullName'] ?? '', 'MultiChoice') !== false) {
            $dstvBiller = $biller;
            break;
        }
    }
    
    // If not found by name, list all billers for manual selection
    if (!$dstvBiller) {
        echo "\n⚠️  DSTV biller not found by name. Available billers:\n";
        echo "------------------------------------------------\n";
        foreach ($allBillers as $index => $biller) {
            echo ($index + 1) . ". " . $biller['shortName'] . " (" . $biller['spCode'] . ")";
            if (isset($biller['fullName'])) {
                echo " - " . $biller['fullName'];
            }
            if (isset($biller['category'])) {
                echo " [" . $biller['category'] . "]";
            }
            echo "\n";
        }
        
        // Try to find media/entertainment category
        $mediaCategory = $billers['grouped']['media'] ?? $billers['grouped']['entertainment'] ?? null;
        if ($mediaCategory) {
            echo "\n📺 Media/Entertainment billers:\n";
            foreach ($mediaCategory as $biller) {
                echo "  - " . $biller['shortName'] . " (" . $biller['spCode'] . ")\n";
            }
        }
        
        // For testing, we'll use the first biller if DSTV is not found
        if (count($allBillers) > 0) {
            $dstvBiller = $allBillers[0];
            echo "\n⚠️  Using first biller for testing: " . $dstvBiller['shortName'] . "\n";
        } else {
            throw new Exception("No billers available in the system");
        }
    } else {
        echo "✅ DSTV Biller found: " . $dstvBiller['shortName'] . " (" . $dstvBiller['spCode'] . ")\n";
    }
    
    // Step 2: Perform bill inquiry
    echo "\n=====================================\n";
    echo "Step 2: Bill Inquiry\n";
    echo "=====================================\n";
    echo "Biller: " . $dstvBiller['shortName'] . "\n";
    echo "SP Code: " . $dstvBiller['spCode'] . "\n";
    echo "Account: 7029243019\n\n";
    
    $inquiryPayload = [
        'spCode' => $dstvBiller['spCode'],
        'billRef' => '7029243019',
        'userId' => 'TEST_USER',
        'branchCode' => '015',
        'extraFields' => []
    ];
    
    echo "Sending inquiry request...\n";
    $inquiryResult = $service->inquireDetailedBill($inquiryPayload);
    
    if ($inquiryResult['success'] ?? false) {
        echo "✅ Bill inquiry successful!\n\n";
        
        $billDetails = $inquiryResult['data'];
        echo "Bill Details:\n";
        echo "-------------\n";
        echo "Customer Name: " . ($billDetails['billedName'] ?? 'N/A') . "\n";
        echo "Service: " . ($billDetails['serviceName'] ?? 'N/A') . "\n";
        echo "Description: " . ($billDetails['description'] ?? 'N/A') . "\n";
        echo "Total Amount: " . ($billDetails['totalAmount'] ?? 'N/A') . " " . ($billDetails['currency'] ?? 'TZS') . "\n";
        echo "Balance Due: " . ($billDetails['balance'] ?? 'N/A') . " " . ($billDetails['currency'] ?? 'TZS') . "\n";
        echo "Payment Mode: " . ($billDetails['paymentMode'] ?? 'N/A') . "\n";
        echo "Expiry Date: " . ($billDetails['expiryDate'] ?? 'N/A') . "\n";
        
        // Step 3: Process payment
        echo "\n=====================================\n";
        echo "Step 3: Payment Processing\n";
        echo "=====================================\n";
        
        $amount = $billDetails['balance'] ?? $billDetails['totalAmount'] ?? '50000';
        echo "Amount to pay: " . $amount . " " . ($billDetails['currency'] ?? 'TZS') . "\n";
        
        $paymentPayload = [
            'spCode' => $dstvBiller['spCode'],
            'billRef' => '7029243019',
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
            'narration' => 'DSTV Payment Test',
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
                'spCode' => $dstvBiller['spCode'],
                'billRef' => '7029243019',
                'channelRef' => $paymentResult['channelRef'] ?? ''
            ];
            
            $statusResult = $service->checkPaymentStatus($statusPayload);
            
            if ($statusResult['status'] === 'success') {
                $paymentDetails = $statusResult['data']['paymentDetails'] ?? [];
                echo "\nPayment Status: " . ($paymentDetails['accountingStatus'] ?? 'Unknown') . "\n";
                echo "Biller Notified: " . ($paymentDetails['billerNotified'] ?? 'Unknown') . "\n";
                
                if (isset($paymentDetails['billerReceipt'])) {
                    echo "Biller Receipt: " . $paymentDetails['billerReceipt'] . "\n";
                }
                
                if ($paymentDetails['accountingStatus'] === 'success') {
                    echo "\n✅ Payment completed successfully!\n";
                } else {
                    echo "\n⏳ Payment still processing...\n";
                }
            } else {
                echo "❌ Status check failed: " . ($statusResult['message'] ?? 'Unknown error') . "\n";
            }
        } else {
            echo "❌ Payment initiation failed!\n";
            echo "Error: " . ($paymentResult['message'] ?? 'Unknown error') . "\n";
        }
        
    } else {
        echo "❌ Bill inquiry failed!\n";
        echo "Error: " . ($inquiryResult['message'] ?? 'Unknown error') . "\n";
        echo "Status Code: " . ($inquiryResult['statusCode'] ?? 'N/A') . "\n";
        
        // Common error codes
        if ($inquiryResult['statusCode'] === '601') {
            echo "\n📝 Note: Bill might already be paid or doesn't exist.\n";
        } elseif ($inquiryResult['statusCode'] === '602') {
            echo "\n📝 Note: Invalid bill reference or validation failed.\n";
        } elseif ($inquiryResult['statusCode'] === '636') {
            echo "\n📝 Note: The biller is currently disabled.\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    Log::error('DSTV Payment Test Error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n=====================================\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "=====================================\n\n";