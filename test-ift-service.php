#!/usr/bin/env php
<?php

/**
 * Test script for Internal Funds Transfer Service
 * Tests transfer between accounts 011191000035 (debit) and 011191000036 (credit)
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel application
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Payments\InternalFundsTransferService;
use Illuminate\Support\Facades\Log;

// Color output helpers
function success($msg) { echo "\033[32m✓ $msg\033[0m\n"; }
function error($msg) { echo "\033[31m✗ $msg\033[0m\n"; }
function info($msg) { echo "\033[36mℹ $msg\033[0m\n"; }
function warning($msg) { echo "\033[33m⚠ $msg\033[0m\n"; }
function section($msg) { echo "\n\033[1;34m═══ $msg ═══\033[0m\n\n"; }

// Test accounts
$testAccounts = [
    'debit' => '011191000035',
    'credit' => '011191000036'
];

try {
    section("INTERNAL FUNDS TRANSFER TEST");
    
    info("Test Accounts:");
    info("  Debit Account:  " . $testAccounts['debit']);
    info("  Credit Account: " . $testAccounts['credit']);
    echo "\n";

    // Initialize the service
    info("Initializing IFT Service...");
    $iftService = new InternalFundsTransferService();
    success("Service initialized");
    echo "\n";

    // Test 1: Account Lookup - Source Account
    section("TEST 1: ACCOUNT LOOKUP - SOURCE");
    info("Looking up source account: " . $testAccounts['debit']);
    
    $sourceResult = $iftService->lookupAccount($testAccounts['debit'], 'source');
    
    if ($sourceResult['success']) {
        success("Source account validated");
        info("  Account Number: " . ($sourceResult['account_number'] ?? 'N/A'));
        info("  Account Name: " . ($sourceResult['account_name'] ?? 'N/A'));
        info("  Status: " . ($sourceResult['account_status'] ?? 'N/A'));
        info("  Branch: " . ($sourceResult['branch_name'] ?? 'N/A') . " (" . ($sourceResult['branch_code'] ?? 'N/A') . ")");
        info("  Can Debit: " . ($sourceResult['can_debit'] ? 'Yes' : 'No'));
        info("  Response Time: " . ($sourceResult['response_time'] ?? 'N/A') . " ms");
        
        if (isset($sourceResult['validation_type'])) {
            warning("  Validation Type: " . $sourceResult['validation_type'] . " (API might be unreachable)");
        }
    } else {
        error("Source account validation failed: " . ($sourceResult['error'] ?? 'Unknown error'));
    }
    echo "\n";

    // Test 2: Account Lookup - Destination Account
    section("TEST 2: ACCOUNT LOOKUP - DESTINATION");
    info("Looking up destination account: " . $testAccounts['credit']);
    
    $destResult = $iftService->lookupAccount($testAccounts['credit'], 'destination');
    
    if ($destResult['success']) {
        success("Destination account validated");
        info("  Account Number: " . ($destResult['account_number'] ?? 'N/A'));
        info("  Account Name: " . ($destResult['account_name'] ?? 'N/A'));
        info("  Status: " . ($destResult['account_status'] ?? 'N/A'));
        info("  Branch: " . ($destResult['branch_name'] ?? 'N/A') . " (" . ($destResult['branch_code'] ?? 'N/A') . ")");
        info("  Can Receive: " . ($destResult['can_receive'] ? 'Yes' : 'No'));
        info("  Response Time: " . ($destResult['response_time'] ?? 'N/A') . " ms");
        
        if (isset($destResult['validation_type'])) {
            warning("  Validation Type: " . $destResult['validation_type'] . " (API might be unreachable)");
        }
    } else {
        error("Destination account validation failed: " . ($destResult['error'] ?? 'Unknown error'));
    }
    echo "\n";

    // Test 3: Small Amount Transfer
    section("TEST 3: TRANSFER - SMALL AMOUNT");
    
    $transferData = [
        'from_account' => $testAccounts['debit'],
        'to_account' => $testAccounts['credit'],
        'amount' => 1000,  // Small test amount
        'from_currency' => 'TZS',
        'to_currency' => 'TZS',
        'sender_name' => 'Test User',
        'narration' => 'Test Transfer - ' . date('Y-m-d H:i:s')
    ];
    
    info("Transfer Details:");
    info("  From: " . $transferData['from_account']);
    info("  To: " . $transferData['to_account']);
    info("  Amount: TZS " . number_format($transferData['amount'], 2));
    info("  Narration: " . $transferData['narration']);
    echo "\n";
    
    info("Executing transfer...");
    $startTime = microtime(true);
    
    $transferResult = $iftService->transfer($transferData);
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($transferResult['success']) {
        success("Transfer completed successfully!");
        info("  Reference: " . ($transferResult['reference'] ?? 'N/A'));
        info("  NBC Reference: " . ($transferResult['nbc_reference'] ?? 'N/A'));
        info("  Message: " . ($transferResult['message'] ?? 'N/A'));
        info("  Total Time: " . $duration . " ms");
        info("  API Response Time: " . ($transferResult['response_time'] ?? 'N/A') . " ms");
        
        if (isset($transferResult['api_response'])) {
            info("\n  NBC API Response:");
            foreach ($transferResult['api_response'] as $key => $value) {
                if (!is_array($value)) {
                    info("    $key: " . (is_bool($value) ? ($value ? 'true' : 'false') : $value));
                }
            }
        }
    } else {
        error("Transfer failed: " . ($transferResult['error'] ?? 'Unknown error'));
        info("  Reference: " . ($transferResult['reference'] ?? 'N/A'));
        info("  Total Time: " . $duration . " ms");
    }
    echo "\n";

    // Test 4: Transfer Status Check (if transfer was successful)
    if (isset($transferResult['success']) && $transferResult['success'] && isset($transferResult['reference'])) {
        section("TEST 4: STATUS CHECK");
        
        info("Checking status for reference: " . $transferResult['reference']);
        
        $statusResult = $iftService->getTransferStatus($transferResult['reference']);
        
        if ($statusResult['success']) {
            success("Status retrieved");
            info("  Status: " . ($statusResult['status'] ?? 'N/A'));
            info("  Message: " . ($statusResult['message'] ?? 'N/A'));
        } else {
            warning("Status check failed: " . ($statusResult['error'] ?? 'Unknown error'));
        }
    }

    // Summary
    section("TEST SUMMARY");
    
    $tests = [
        'Account Lookup (Source)' => $sourceResult['success'] ?? false,
        'Account Lookup (Destination)' => $destResult['success'] ?? false,
        'Transfer Execution' => $transferResult['success'] ?? false
    ];
    
    $passed = 0;
    $failed = 0;
    
    foreach ($tests as $testName => $result) {
        if ($result) {
            success($testName . ": PASSED");
            $passed++;
        } else {
            error($testName . ": FAILED");
            $failed++;
        }
    }
    
    echo "\n";
    info("Total: " . count($tests) . " tests");
    success("Passed: $passed");
    if ($failed > 0) {
        error("Failed: $failed");
    }
    
    // Check configuration
    echo "\n";
    section("CONFIGURATION CHECK");
    
    $configs = [
        'Base URL' => config('services.nbc_internal_fund_transfer.base_url'),
        'API Key' => config('services.nbc_internal_fund_transfer.api_key') ? '***' . substr(config('services.nbc_internal_fund_transfer.api_key'), -4) : 'NOT SET',
        'Channel ID' => config('services.nbc_internal_fund_transfer.channel_id'),
        'Username' => config('services.nbc_internal_fund_transfer.username') ?: 'NOT SET',
        'Private Key' => config('services.nbc_internal_fund_transfer.private_key') ? 'CONFIGURED' : 'NOT SET',
        'SSL Verify' => config('services.nbc_internal_fund_transfer.verify_ssl', false) ? 'Yes' : 'No',
        'Timeout' => config('services.nbc_internal_fund_transfer.timeout', 30) . ' seconds'
    ];
    
    foreach ($configs as $key => $value) {
        if ($value === 'NOT SET' || $value === null) {
            warning("$key: $value");
        } else {
            info("$key: $value");
        }
    }
    
    // Check account details service config
    echo "\n";
    info("Account Details Service Configuration:");
    $accountConfigs = [
        'Base URL' => config('services.account_details.base_url'),
        'API Key' => config('services.account_details.api_key') ? '***' . substr(config('services.account_details.api_key'), -4) : 'NOT SET',
        'Channel Code' => config('services.account_details.channel_code'),
        'Channel Name' => config('services.account_details.channel_name')
    ];
    
    foreach ($accountConfigs as $key => $value) {
        if ($value === 'NOT SET' || $value === null) {
            warning("  $key: $value");
        } else {
            info("  $key: $value");
        }
    }

} catch (Exception $e) {
    error("Test failed with exception: " . $e->getMessage());
    error("Stack trace:");
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n";
success("Test completed!");
echo "\n";