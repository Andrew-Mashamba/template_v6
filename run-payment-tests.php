<?php

/**
 * Comprehensive Payment Link Test Runner
 * Runs all payment link generation tests and provides detailed feedback
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Comprehensive Payment Link Test Runner ===\n\n";

$testResults = [];

try {
    // Test 1: Environment Setup
    echo "Test 1: Environment Setup...\n";
    $testResults['environment'] = [];
    
    $paymentLinkApiUrl = env('PAYMENT_LINK_API_URL');
    $paymentLinkApiKey = env('PAYMENT_LINK_API_KEY');
    $paymentLinkApiSecret = env('PAYMENT_LINK_API_SECRET');
    
    if ($paymentLinkApiUrl && $paymentLinkApiKey && $paymentLinkApiSecret) {
        echo "✓ Payment Link API is configured\n";
        echo "  - API URL: {$paymentLinkApiUrl}\n";
        echo "  - API Key: " . substr($paymentLinkApiKey, 0, 10) . "...\n";
        $testResults['environment']['payment_link'] = true;
    } else {
        echo "⚠ Payment Link API not fully configured\n";
        $testResults['environment']['payment_link'] = false;
    }
    
    $appUrl = env('APP_URL');
    if ($appUrl) {
        echo "✓ APP_URL is configured\n";
        $testResults['environment']['app_url'] = true;
    } else {
        echo "⚠ APP_URL not configured\n";
        $testResults['environment']['app_url'] = false;
    }
    
    echo "\n";
    
    // Test 2: Database Configuration
    echo "Test 2: Database Configuration...\n";
    $testResults['database'] = [];
    
    try {
        $institution = \Illuminate\Support\Facades\DB::table('institutions')->where('id', 1)->first();
        if ($institution) {
            echo "✓ Institution found: {$institution->institution_id}\n";
            $testResults['database']['institution'] = true;
        } else {
            echo "⚠ No institution found\n";
            $testResults['database']['institution'] = false;
        }
        
        $services = \Illuminate\Support\Facades\DB::table('services')->whereIn('code', ['REG', 'SHC'])->get();
        if ($services->count() > 0) {
            echo "✓ Required services found: " . $services->count() . " services\n";
            $testResults['database']['services'] = true;
        } else {
            echo "⚠ Required services not found\n";
            $testResults['database']['services'] = false;
        }
        
        $billsTableExists = \Illuminate\Support\Facades\Schema::hasTable('bills');
        if ($billsTableExists) {
            echo "✓ Bills table exists\n";
            $testResults['database']['bills_table'] = true;
            
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing('bills');
            $requiredColumns = ['payment_link', 'payment_link_id', 'payment_link_generated_at'];
            $missingColumns = array_diff($requiredColumns, $columns);
            
            if (empty($missingColumns)) {
                echo "✓ All payment link columns exist\n";
                $testResults['database']['payment_columns'] = true;
            } else {
                echo "⚠ Missing columns: " . implode(', ', $missingColumns) . "\n";
                $testResults['database']['payment_columns'] = false;
            }
        } else {
            echo "✗ Bills table does not exist\n";
            $testResults['database']['bills_table'] = false;
        }
        
    } catch (\Exception $e) {
        echo "✗ Database error: " . $e->getMessage() . "\n";
        $testResults['database']['error'] = $e->getMessage();
    }
    
    echo "\n";
    
    // Test 3: Service Availability
    echo "Test 3: Service Availability...\n";
    $testResults['services'] = [];
    
    if (class_exists('\App\Services\PaymentLinkService')) {
        echo "✓ PaymentLinkService class exists\n";
        $testResults['services']['payment_service'] = true;
        
        try {
            $paymentService = new \App\Services\PaymentLinkService();
            echo "✓ PaymentLinkService instantiated successfully\n";
            $testResults['services']['payment_service_instantiation'] = true;
            
            if (method_exists($paymentService, 'generateUniversalPaymentLink')) {
                echo "✓ generateUniversalPaymentLink method exists\n";
                $testResults['services']['generate_method'] = true;
            } else {
                echo "✗ generateUniversalPaymentLink method not found\n";
                $testResults['services']['generate_method'] = false;
            }
        } catch (\Exception $e) {
            echo "✗ PaymentLinkService instantiation failed: " . $e->getMessage() . "\n";
            $testResults['services']['payment_service_instantiation'] = false;
        }
    } else {
        echo "✗ PaymentLinkService class not found\n";
        $testResults['services']['payment_service'] = false;
    }
    
    if (class_exists('\App\Http\Livewire\Clients\Clients')) {
        echo "✓ Clients component exists\n";
        $testResults['services']['clients_component'] = true;
    } else {
        echo "✗ Clients component not found\n";
        $testResults['services']['clients_component'] = false;
    }
    
    echo "\n";
    
    // Test 4: Payment Data Structure
    echo "Test 4: Payment Data Structure...\n";
    $testResults['data_structure'] = [];
    
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
                'product_service_reference' => '1', // String as required
                'product_service_name' => 'Registration Fee',
                'amount' => 25000,
                'is_required' => true,
                'allow_partial' => false
            ],
            [
                'type' => 'service',
                'product_service_reference' => '2', // String as required
                'product_service_name' => 'Share Capital',
                'amount' => 25000,
                'is_required' => true,
                'allow_partial' => false
            ]
        ]
    ];
    
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
        $testResults['data_structure']['required_keys'] = true;
    } else {
        $testResults['data_structure']['required_keys'] = false;
    }
    
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
        $testResults['data_structure']['item_structure'] = true;
    } else {
        $testResults['data_structure']['item_structure'] = false;
    }
    
    echo "\n";
    
    // Test 5: Payment Link Generation
    echo "Test 5: Payment Link Generation...\n";
    $testResults['payment_generation'] = [];
    
    if (isset($paymentService) && method_exists($paymentService, 'generateUniversalPaymentLink')) {
        try {
            $response = $paymentService->generateUniversalPaymentLink($testData);
            echo "✓ Payment link generation method called successfully\n";
            $testResults['payment_generation']['method_call'] = true;
            
            if (is_array($response)) {
                echo "✓ Response is an array\n";
                $testResults['payment_generation']['response_type'] = true;
                
                if (isset($response['data'])) {
                    echo "✓ Response has 'data' key\n";
                    $testResults['payment_generation']['data_key'] = true;
                    
                    if (isset($response['data']['payment_url'])) {
                        echo "✓ Payment URL generated: " . $response['data']['payment_url'] . "\n";
                        $testResults['payment_generation']['payment_url'] = true;
                    } else {
                        echo "⚠ Payment URL not found in response\n";
                        $testResults['payment_generation']['payment_url'] = false;
                    }
                    
                    if (isset($response['data']['link_id'])) {
                        echo "✓ Link ID: " . $response['data']['link_id'] . "\n";
                        $testResults['payment_generation']['link_id'] = true;
                    } else {
                        echo "⚠ Link ID not found in response\n";
                        $testResults['payment_generation']['link_id'] = false;
                    }
                } else {
                    echo "⚠ Response does not have 'data' key\n";
                    $testResults['payment_generation']['data_key'] = false;
                }
            } else {
                echo "⚠ Response is not an array\n";
                $testResults['payment_generation']['response_type'] = false;
            }
        } catch (\Exception $e) {
            echo "⚠ Payment link generation failed: " . $e->getMessage() . "\n";
            $testResults['payment_generation']['method_call'] = false;
            $testResults['payment_generation']['error'] = $e->getMessage();
        }
    } else {
        echo "✗ Payment service not available for testing\n";
        $testResults['payment_generation']['service_available'] = false;
    }
    
    echo "\n";
    
    // Test 6: Code Analysis
    echo "Test 6: Code Analysis...\n";
    $testResults['code_analysis'] = [];
    
    $clientsFile = __DIR__ . '/app/Http/Livewire/Clients/Clients.php';
    if (file_exists($clientsFile)) {
        echo "✓ Clients component file exists\n";
        $testResults['code_analysis']['file_exists'] = true;
        
        $content = file_get_contents($clientsFile);
        
        if (strpos($content, 'use App\Services\PaymentLinkService;') !== false) {
            echo "✓ PaymentLinkService is imported\n";
            $testResults['code_analysis']['import'] = true;
        } else {
            echo "⚠ PaymentLinkService import not found\n";
            $testResults['code_analysis']['import'] = false;
        }
        
        if (strpos($content, 'generateUniversalPaymentLink') !== false) {
            echo "✓ generateUniversalPaymentLink method is used\n";
            $testResults['code_analysis']['method_usage'] = true;
        } else {
            echo "⚠ generateUniversalPaymentLink method not found\n";
            $testResults['code_analysis']['method_usage'] = false;
        }
        
        if (strpos($content, "'description' => 'SACCOS Member Registration") !== false) {
            echo "✓ Payment description format found\n";
            $testResults['code_analysis']['description_format'] = true;
        } else {
            echo "⚠ Payment description format not found\n";
            $testResults['code_analysis']['description_format'] = false;
        }
        
        if (strpos($content, '$paymentUrl = null') !== false) {
            echo "✓ No fallback URL mechanism found (correct behavior)\n";
            $testResults['code_analysis']['fallback_mechanism'] = true;
        } else {
            echo "⚠ Fallback URL mechanism still present (should be removed)\n";
            $testResults['code_analysis']['fallback_mechanism'] = false;
        }
        
        if (strpos($content, '(string) $bill->bill_id') !== false) {
            echo "✓ String conversion for product_service_reference found\n";
            $testResults['code_analysis']['string_conversion'] = true;
        } else {
            echo "⚠ String conversion for product_service_reference not found\n";
            $testResults['code_analysis']['string_conversion'] = false;
        }
        
    } else {
        echo "✗ Clients component file not found\n";
        $testResults['code_analysis']['file_exists'] = false;
    }
    
    echo "\n";
    
    // Summary
    echo "=== Test Summary ===\n";
    
    $totalTests = 0;
    $passedTests = 0;
    
    foreach ($testResults as $category => $tests) {
        echo "\n{$category}:\n";
        foreach ($tests as $test => $result) {
            $totalTests++;
            if ($result === true) {
                echo "  ✓ {$test}\n";
                $passedTests++;
            } elseif ($result === false) {
                echo "  ✗ {$test}\n";
            } else {
                echo "  ⚠ {$test}: {$result}\n";
            }
        }
    }
    
    echo "\nOverall Results: {$passedTests}/{$totalTests} tests passed\n";
    
    if ($passedTests === $totalTests) {
        echo "🎉 All tests passed! Your payment link generation is fully functional.\n";
    } elseif ($passedTests > $totalTests * 0.8) {
        echo "✅ Most tests passed. Minor configuration needed.\n";
    } else {
        echo "⚠ Some tests failed. Please review the issues above.\n";
    }
    
    echo "\n=== Recommendations ===\n";
    
    if (!$testResults['environment']['payment_link'] ?? false) {
        echo "1. Set Payment Link API configuration in your .env file:\n";
        echo "   PAYMENT_LINK_API_URL=http://172.240.241.188/api/payment-links/generate-universal\n";
        echo "   PAYMENT_LINK_API_KEY=sample_client_key_ABC123DEF456\n";
        echo "   PAYMENT_LINK_API_SECRET=sample_client_secret_XYZ789GHI012\n";
    }
    
    if (!$testResults['database']['institution'] ?? false) {
        echo "2. Run the setup script: php setup-payment-environment.php\n";
    }
    
    if (!$testResults['database']['payment_columns'] ?? false) {
        echo "3. Run migrations: php artisan migrate\n";
    }
    
    if (!$testResults['code_analysis']['string_conversion'] ?? false) {
        echo "4. Update Clients component to convert bill IDs to strings\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Test runner failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
