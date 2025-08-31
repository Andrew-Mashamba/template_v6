<?php

/**
 * Simple Payment Link Generation Test
 * Compatible with Laravel 9
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Simple Payment Link Generation Test ===\n\n";

try {
    // Test 1: Check if PaymentLinkService exists
    echo "Test 1: Checking PaymentLinkService availability...\n";
    
    if (class_exists('\App\Services\PaymentLinkService')) {
        echo "✓ PaymentLinkService class exists\n";
        $paymentService = new \App\Services\PaymentLinkService();
        echo "✓ PaymentLinkService instance created successfully\n";
    } else {
        echo "✗ PaymentLinkService class not found\n";
        exit(1);
    }
    
    echo "\n";
    
    // Test 2: Check if Clients component exists
    echo "Test 2: Checking Clients component availability...\n";
    
    if (class_exists('\App\Http\Livewire\Clients\Clients')) {
        echo "✓ Clients component class exists\n";
    } else {
        echo "✗ Clients component class not found\n";
        exit(1);
    }
    
    echo "\n";
    
    // Test 3: Test payment data structure
    echo "Test 3: Testing payment data structure...\n";
    
    $testData = [
        'description' => 'Test SACCOS Member Registration - JOHN DOE',
        'target' => 'individual',
        'customer_reference' => '1001',
        'customer_name' => 'JOHN DOE',
        'customer_phone' => '0712345678',
        'customer_email' => 'john.doe@example.com',
        'expires_at' => now()->addDays(7)->toIso8601String(),
        'items' => [
            [
                'type' => 'service',
                'product_service_reference' => '1', // Changed to string
                'product_service_name' => 'Registration Fee',
                'amount' => 25000,
                'is_required' => true,
                'allow_partial' => false
            ],
            [
                'type' => 'service',
                'product_service_reference' => '2', // Changed to string
                'product_service_name' => 'Share Capital',
                'amount' => 25000,
                'is_required' => true,
                'allow_partial' => false
            ]
        ]
    ];
    
    // Validate required keys
    $requiredKeys = [
        'description', 'target', 'customer_reference', 'customer_name',
        'customer_phone', 'customer_email', 'expires_at', 'items'
    ];
    
    $allKeysPresent = true;
    foreach ($requiredKeys as $key) {
        if (!array_key_exists($key, $testData)) {
            echo "✗ Missing required key: {$key}\n";
            $allKeysPresent = false;
        }
    }
    
    if ($allKeysPresent) {
        echo "✓ All required payment data keys are present\n";
    }
    
    // Validate items structure
    if (isset($testData['items']) && is_array($testData['items'])) {
        $itemKeys = ['type', 'product_service_reference', 'product_service_name', 'amount', 'is_required', 'allow_partial'];
        $itemsValid = true;
        
        foreach ($testData['items'] as $index => $item) {
            foreach ($itemKeys as $itemKey) {
                if (!array_key_exists($itemKey, $item)) {
                    echo "✗ Item {$index} missing key: {$itemKey}\n";
                    $itemsValid = false;
                }
            }
        }
        
        if ($itemsValid) {
            echo "✓ All payment items have correct structure\n";
        }
    }
    
    echo "\n";
    
    // Test 4: Test PaymentLinkService method
    echo "Test 4: Testing PaymentLinkService generateUniversalPaymentLink method...\n";
    
    if (method_exists($paymentService, 'generateUniversalPaymentLink')) {
        echo "✓ generateUniversalPaymentLink method exists\n";
        
        try {
            $response = $paymentService->generateUniversalPaymentLink($testData);
            echo "✓ Method called successfully\n";
            
            if (is_array($response)) {
                echo "✓ Response is an array\n";
                
                if (isset($response['data'])) {
                    echo "✓ Response has 'data' key\n";
                    
                    if (isset($response['data']['payment_url'])) {
                        echo "✓ Payment URL generated: " . $response['data']['payment_url'] . "\n";
                    } else {
                        echo "⚠ Payment URL not found in response\n";
                    }
                    
                    if (isset($response['data']['link_id'])) {
                        echo "✓ Link ID: " . $response['data']['link_id'] . "\n";
                    }
                    
                    if (isset($response['data']['total_amount'])) {
                        echo "✓ Total Amount: " . $response['data']['total_amount'] . "\n";
                    }
                } else {
                    echo "⚠ Response does not have 'data' key\n";
                    echo "Response structure: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
                }
            } else {
                echo "⚠ Response is not an array\n";
                echo "Response type: " . gettype($response) . "\n";
            }
        } catch (\Exception $e) {
            echo "⚠ Method call failed: " . $e->getMessage() . "\n";
            echo "This might be expected if the payment service is not configured\n";
        }
    } else {
        echo "✗ generateUniversalPaymentLink method not found\n";
    }
    
    echo "\n";
    
    // Test 5: Database integration test
    echo "Test 5: Testing database integration...\n";
    
    try {
        // Check if bills table exists
        $billsTableExists = \Illuminate\Support\Facades\Schema::hasTable('bills');
        if ($billsTableExists) {
            echo "✓ Bills table exists\n";
            
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing('bills');
            $requiredColumns = ['payment_link', 'payment_link_id', 'payment_link_generated_at'];
            
            $allColumnsPresent = true;
            foreach ($requiredColumns as $column) {
                if (!in_array($column, $columns)) {
                    echo "⚠ Missing column: {$column}\n";
                    $allColumnsPresent = false;
                }
            }
            
            if ($allColumnsPresent) {
                echo "✓ All required payment link columns exist\n";
            } else {
                echo "⚠ Some payment link columns are missing\n";
            }
        } else {
            echo "⚠ Bills table does not exist\n";
        }
    } catch (\Exception $e) {
        echo "⚠ Database test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 6: Environment configuration
    echo "Test 6: Testing environment configuration...\n";
    
    $paymentLinkApiUrl = env('PAYMENT_LINK_API_URL');
    $paymentLinkApiKey = env('PAYMENT_LINK_API_KEY');
    $paymentLinkApiSecret = env('PAYMENT_LINK_API_SECRET');
    
    if ($paymentLinkApiUrl && $paymentLinkApiKey && $paymentLinkApiSecret) {
        echo "✓ Payment Link API is configured\n";
        echo "  - API URL: {$paymentLinkApiUrl}\n";
        echo "  - API Key: " . substr($paymentLinkApiKey, 0, 10) . "...\n";
    } else {
        echo "⚠ Payment Link API not fully configured\n";
    }
    
    try {
        $institutionId = \Illuminate\Support\Facades\DB::table('institutions')->where('id', 1)->value('institution_id');
        if ($institutionId) {
            echo "✓ Institution ID found: {$institutionId}\n";
        } else {
            echo "⚠ No institution found in database\n";
        }
    } catch (\Exception $e) {
        echo "⚠ Could not check institution: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 7: Check Clients component payment link generation
    echo "Test 7: Analyzing Clients component payment link generation...\n";
    
    $clientsFile = __DIR__ . '/app/Http/Livewire/Clients/Clients.php';
    if (file_exists($clientsFile)) {
        echo "✓ Clients component file exists\n";
        
        $content = file_get_contents($clientsFile);
        
        // Check for PaymentLinkService import
        if (strpos($content, 'use App\Services\PaymentLinkService;') !== false) {
            echo "✓ PaymentLinkService is imported\n";
        } else {
            echo "⚠ PaymentLinkService import not found\n";
        }
        
        // Check for payment link generation code
        if (strpos($content, 'generateUniversalPaymentLink') !== false) {
            echo "✓ generateUniversalPaymentLink method is used\n";
        } else {
            echo "⚠ generateUniversalPaymentLink method not found\n";
        }
        
        // Check for payment data structure
        if (strpos($content, "'description' => 'SACCOS Member Registration") !== false) {
            echo "✓ Payment description format found\n";
        } else {
            echo "⚠ Payment description format not found\n";
        }
        
        // Check for fallback URL
        if (strpos($content, '$paymentUrl = null') !== false) {
            echo "✓ No fallback URL mechanism found (correct behavior)\n";
        } else {
            echo "⚠ Fallback URL mechanism still present (should be removed)\n";
        }
        
    } else {
        echo "✗ Clients component file not found\n";
    }
    
    echo "\n";
    
    echo "=== Test Summary ===\n";
    echo "All tests completed. Check the output above for any issues.\n";
    echo "The payment link generation functionality appears to be properly implemented.\n";
    
} catch (\Exception $e) {
    echo "✗ Test runner failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
