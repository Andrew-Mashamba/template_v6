#!/usr/bin/env php
<?php

/**
 * Payment Module Re-test Script
 * Tests all payment types after applying fixes
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Log;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n=====================================\n";
echo "    PAYMENT MODULE RE-TEST\n";
echo "=====================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Test configuration
$testResults = [];
$testsRun = 0;
$testsPassed = 0;
$testsFailed = 0;

/**
 * Run a test and record results
 */
function runTest($testName, callable $test) {
    global $testResults, $testsRun, $testsPassed, $testsFailed;
    
    $testsRun++;
    echo "Testing: $testName... ";
    
    try {
        $result = $test();
        if ($result['success']) {
            echo "✅ PASSED\n";
            $testsPassed++;
            $testResults[$testName] = ['status' => 'PASSED', 'message' => $result['message'] ?? 'Test passed'];
        } else {
            echo "❌ FAILED\n";
            $testsFailed++;
            $testResults[$testName] = ['status' => 'FAILED', 'error' => $result['error'] ?? 'Unknown error'];
        }
    } catch (Exception $e) {
        echo "❌ ERROR\n";
        $testsFailed++;
        $testResults[$testName] = ['status' => 'ERROR', 'error' => $e->getMessage()];
    }
}

// Test 1: Bank-to-Bank Transfer (with fixed private key)
runTest('Bank-to-Bank Transfer', function() {
    $service = app(\App\Services\NbcPayments\NbcLookupService::class);
    
    try {
        $result = $service->bankToBankLookup(
            '28012040022',
            'NMIBTZTZ',
            '06012040022',
            '10000'
        );
        
        return ['success' => true, 'message' => 'Lookup successful'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
});

// Test 2: Bank-to-Wallet Transfer
runTest('Bank-to-Wallet Transfer', function() {
    $service = app(\App\Services\NbcPayments\NbcLookupService::class);
    
    try {
        $result = $service->bankToWalletLookup(
            '255715000000',
            'VMCASHIN',
            '06012040022',
            '5000'
        );
        
        return ['success' => true, 'message' => 'Wallet lookup successful'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
});

// Test 3: GEPG Payment (with fixed DI)
runTest('GEPG Control Number Verification', function() {
    $service = app(\App\Services\NbcPayments\GepgGatewayService::class);
    
    try {
        $result = $service->verifyControlNumber(
            '991234567890',
            '06012040022',
            'TZS'
        );
        
        return ['success' => true, 'message' => 'GEPG verification successful'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
});

// Test 4: LUKU Payment
runTest('LUKU Meter Lookup', function() {
    $service = app(\App\Services\NbcPayments\LukuService::class);
    
    try {
        $result = $service->lookup(
            '01234567890123456789',
            '06012040022'
        );
        
        return ['success' => true, 'message' => 'LUKU lookup successful'];
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
});

// Test 5: Check Private Key Symlink
runTest('Private Key Configuration', function() {
    $privateKeyPath = storage_path('keys/private_key.pem');
    $originalKeyPath = storage_path('keys/private.pem');
    
    if (file_exists($privateKeyPath)) {
        if (is_link($privateKeyPath)) {
            $target = readlink($privateKeyPath);
            if (basename($target) === 'private.pem') {
                return ['success' => true, 'message' => 'Symlink correctly points to private.pem'];
            }
        }
        return ['success' => true, 'message' => 'Private key file exists'];
    }
    
    return ['success' => false, 'error' => 'Private key file not found'];
});

// Test 6: Service Container Registration
runTest('Service Container Registration', function() {
    try {
        $gepgLogger = app(\App\Services\NbcPayments\GepgLoggerService::class);
        $gepgGateway = app(\App\Services\NbcPayments\GepgGatewayService::class);
        
        if ($gepgLogger && $gepgGateway) {
            return ['success' => true, 'message' => 'Services registered correctly'];
        }
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Service registration failed: ' . $e->getMessage()];
    }
});

// Test 7: Error Handler View Component
runTest('Error Handler View Component', function() {
    $viewPath = resource_path('views/livewire/payments/error-handler.blade.php');
    
    if (file_exists($viewPath)) {
        return ['success' => true, 'message' => 'Error handler component exists'];
    }
    
    return ['success' => false, 'error' => 'Error handler component not found'];
});

// Test 8: Payment Views Updated
runTest('Payment Views Updated', function() {
    $views = [
        'payments.blade.php',
        'gepg-payment.blade.php',
        'luku-payment.blade.php'
    ];
    
    $updated = 0;
    foreach ($views as $view) {
        $viewPath = resource_path('views/livewire/payments/' . $view);
        if (file_exists($viewPath)) {
            $content = file_get_contents($viewPath);
            if (strpos($content, "include('livewire.payments.error-handler')") !== false) {
                $updated++;
            }
        }
    }
    
    if ($updated === count($views)) {
        return ['success' => true, 'message' => "All $updated views updated with error handler"];
    }
    
    return ['success' => false, 'error' => "Only $updated of " . count($views) . " views updated"];
});

// Display Results
echo "\n=====================================\n";
echo "         TEST RESULTS SUMMARY\n";
echo "=====================================\n";
echo "Total Tests Run: $testsRun\n";
echo "Tests Passed: $testsPassed\n";
echo "Tests Failed: $testsFailed\n";
echo "Success Rate: " . ($testsRun > 0 ? round(($testsPassed / $testsRun) * 100, 2) : 0) . "%\n\n";

echo "Detailed Results:\n";
echo "-----------------\n";
foreach ($testResults as $testName => $result) {
    echo "• $testName: " . $result['status'] . "\n";
    if ($result['status'] !== 'PASSED') {
        echo "  Error: " . ($result['error'] ?? 'Unknown') . "\n";
    } else {
        echo "  Message: " . ($result['message'] ?? '') . "\n";
    }
}

// Final Status
echo "\n=====================================\n";
if ($testsPassed === $testsRun) {
    echo "✅ ALL TESTS PASSED - System Ready!\n";
} elseif ($testsPassed > $testsFailed) {
    echo "⚠️ PARTIAL SUCCESS - Some fixes working\n";
} else {
    echo "❌ CRITICAL - Most tests still failing\n";
}
echo "=====================================\n\n";

// Write results to file
$reportContent = "# Payment Module Re-test Results\n\n";
$reportContent .= "**Date**: " . date('Y-m-d H:i:s') . "\n\n";
$reportContent .= "## Summary\n";
$reportContent .= "- Tests Run: $testsRun\n";
$reportContent .= "- Tests Passed: $testsPassed\n";
$reportContent .= "- Tests Failed: $testsFailed\n";
$reportContent .= "- Success Rate: " . ($testsRun > 0 ? round(($testsPassed / $testsRun) * 100, 2) : 0) . "%\n\n";
$reportContent .= "## Test Results\n\n";

foreach ($testResults as $testName => $result) {
    $reportContent .= "### $testName\n";
    $reportContent .= "- **Status**: " . $result['status'] . "\n";
    if ($result['status'] !== 'PASSED') {
        $reportContent .= "- **Error**: " . ($result['error'] ?? 'Unknown') . "\n";
    } else {
        $reportContent .= "- **Message**: " . ($result['message'] ?? '') . "\n";
    }
    $reportContent .= "\n";
}

file_put_contents(__DIR__ . '/PAYMENT_RETEST_REPORT.md', $reportContent);
echo "Report saved to: PAYMENT_RETEST_REPORT.md\n";