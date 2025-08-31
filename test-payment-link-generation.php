<?php

/**
 * Payment Link Generation Test Runner
 * 
 * This script tests the payment link generation functionality in the Clients component
 * Run with: php test-payment-link-generation.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Bootstrap Laravel
$app = Application::configure(basePath: __DIR__)
    ->withRouting(
        web: __DIR__.'/routes/web.php',
        commands: __DIR__.'/routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        //
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== Payment Link Generation Test Runner ===\n\n";

try {
    // Test 1: Basic Payment Link Generation
    echo "Test 1: Testing basic payment link generation...\n";
    
    $paymentService = new \App\Services\PaymentLinkService();
    
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
                'product_service_reference' => 1,
                'product_service_name' => 'Registration Fee',
                'amount' => 25000,
                'is_required' => true,
                'allow_partial' => false
            ],
            [
                'type' => 'service',
                'product_service_reference' => 2,
                'product_service_name' => 'Share Capital',
                'amount' => 25000,
                'is_required' => true,
                'allow_partial' => false
            ]
        ]
    ];
    
    $response = $paymentService->generateUniversalPaymentLink($testData);
    
    if (isset($response['data']['payment_url'])) {
        echo "✓ Payment link generated successfully\n";
        echo "  URL: " . $response['data']['payment_url'] . "\n";
        echo "  Link ID: " . ($response['data']['link_id'] ?? 'N/A') . "\n";
        echo "  Total Amount: " . ($response['data']['total_amount'] ?? 'N/A') . "\n";
    } else {
        echo "✗ Payment link generation failed\n";
        echo "  Response: " . json_encode($response, JSON_PRETTY_PRINT) . "\n";
    }
    
    echo "\n";
    
    // Test 2: Payment Data Structure Validation
    echo "Test 2: Testing payment data structure validation...\n";
    
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
    
    // Test 3: Error Handling
    echo "Test 3: Testing error handling...\n";
    
    try {
        $invalidData = [
            'description' => 'Test',
            // Missing required fields
        ];
        
        $response = $paymentService->generateUniversalPaymentLink($invalidData);
        echo "✗ Should have thrown an exception for invalid data\n";
    } catch (\Exception $e) {
        echo "✓ Error handling works correctly: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 4: Database Integration Test
    echo "Test 4: Testing database integration...\n";
    
    // Check if bills table exists and has required columns
    try {
        $billsTableExists = \Illuminate\Support\Facades\Schema::hasTable('bills');
        if ($billsTableExists) {
            echo "✓ Bills table exists\n";
            
            $columns = \Illuminate\Support\Facades\Schema::getColumnListing('bills');
            $requiredColumns = ['payment_link', 'payment_link_id', 'payment_link_generated_at'];
            
            $allColumnsPresent = true;
            foreach ($requiredColumns as $column) {
                if (!in_array($column, $columns)) {
                    echo "✗ Missing column: {$column}\n";
                    $allColumnsPresent = false;
                }
            }
            
            if ($allColumnsPresent) {
                echo "✓ All required payment link columns exist\n";
            }
        } else {
            echo "✗ Bills table does not exist\n";
        }
    } catch (\Exception $e) {
        echo "✗ Database test failed: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Test 5: Environment Configuration
    echo "Test 5: Testing environment configuration...\n";
    
    $paymentLink = env('PAYMENT_LINK');
    if ($paymentLink) {
        echo "✓ PAYMENT_LINK environment variable is set: {$paymentLink}\n";
    } else {
        echo "✗ PAYMENT_LINK environment variable is not set\n";
    }
    
    $institutionId = \Illuminate\Support\Facades\DB::table('institutions')->where('id', 1)->value('institution_id');
    if ($institutionId) {
        echo "✓ Institution ID found: {$institutionId}\n";
    } else {
        echo "✗ No institution found in database\n";
    }
    
    echo "\n";
    
    echo "=== Test Summary ===\n";
    echo "All tests completed. Check the output above for any failures.\n";
    
} catch (\Exception $e) {
    echo "✗ Test runner failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
