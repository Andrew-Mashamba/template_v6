<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\NbcBillsPaymentUatService;
use Illuminate\Support\Facades\Log;

echo "\n=====================================\n";
echo "NBC UAT SERVICE TEST\n"; 
echo "Bill Reference: 45467898\n";
echo "=====================================\n\n";

try {
    $service = new NbcBillsPaymentUatService();
    
    // Step 1: Fetch billers
    echo "Step 1: Fetching billers from UAT...\n";
    $billers = $service->getBillers();
    
    $allBillers = $billers['flat'] ?? [];
    
    if (empty($allBillers)) {
        echo "No billers found. Trying direct inquiry...\n\n";
        
        // Try direct inquiry with known SP code
        echo "Step 2: Direct Bill Inquiry for 45467898\n";
        echo "=====================================\n";
        
        $inquiryPayload = [
            'spCode' => 'bpesp1004002',  // Top-up service
            'billRef' => '45467898',
            'userId' => 'TEST_USER',
            'branchCode' => '015',
            'extraFields' => []
        ];
        
        echo "Sending inquiry request...\n";
        echo "SP Code: bpesp1004002\n";
        echo "Bill Ref: 45467898\n\n";
        
        $inquiryResult = $service->inquireDetailedBill($inquiryPayload);
        
        if ($inquiryResult['success'] ?? false) {
            echo "✅ Bill inquiry successful!\n\n";
            
            $billDetails = $inquiryResult['data'];
            
            echo "Bill Details:\n";
            echo "=============\n";
            
            if (is_array($billDetails)) {
                foreach ($billDetails as $key => $value) {
                    if (!empty($value)) {
                        echo "$key: $value\n";
                    }
                }
            } else {
                echo "No bill details available\n";
            }
            
        } else {
            echo "❌ Bill inquiry failed!\n";
            echo "Error: " . ($inquiryResult['message'] ?? 'Unknown error') . "\n";
            if (isset($inquiryResult['statusCode'])) {
                echo "Status Code: " . $inquiryResult['statusCode'] . "\n";
            }
            
            if ($inquiryResult['statusCode'] === '699') {
                echo "\n📝 Error 699: Internal Processing Failure\n";
                echo "Possible causes:\n";
                echo "- Invalid digital signature\n";
                echo "- SP code not configured for this channel\n";
                echo "- Invalid bill reference format\n";
                echo "- UAT environment issues\n";
            }
        }
        
    } else {
        echo "Found " . count($allBillers) . " biller(s)\n\n";
        
        foreach ($allBillers as $index => $biller) {
            echo ($index + 1) . ". " . ($biller['shortName'] ?? 'Unknown') . "\n";
            echo "   SP Code: " . ($biller['spCode'] ?? 'N/A') . "\n";
            if (isset($biller['category'])) {
                echo "   Category: " . $biller['category'] . "\n";
            }
            echo "\n";
        }
        
        // Find Top-up service
        $topupBiller = null;
        foreach ($allBillers as $biller) {
            if (stripos($biller['shortName'] ?? '', 'Top-up') !== false || 
                ($biller['spCode'] ?? '') === 'bpesp1004002') {
                $topupBiller = $biller;
                break;
            }
        }
        
        if ($topupBiller) {
            echo "Testing with Top-up service...\n";
            
            $inquiryPayload = [
                'spCode' => $topupBiller['spCode'],
                'billRef' => '45467898',
                'userId' => 'TEST_USER',
                'branchCode' => '015',
                'extraFields' => []
            ];
            
            $inquiryResult = $service->inquireDetailedBill($inquiryPayload);
            
            if ($inquiryResult['success'] ?? false) {
                echo "✅ Bill inquiry successful!\n";
                print_r($inquiryResult['data']);
            } else {
                echo "❌ Bill inquiry failed: " . ($inquiryResult['message'] ?? 'Unknown') . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    
    Log::error('UAT Service Test Error', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n=====================================\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "=====================================\n\n";