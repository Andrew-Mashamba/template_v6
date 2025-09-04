#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Payments\ExternalFundsTransferService;
use App\Services\Payments\MobileWalletTransferService;

// Color codes
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$BLUE = "\033[0;34m";
$CYAN = "\033[0;36m";
$NC = "\033[0m";

echo "\n{$CYAN}================================================\n";
echo "     B2B & B2W LOOKUP VERIFICATION TEST\n";
echo "================================================{$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Environment: " . app()->environment() . "\n\n";

$results = [];

// Test 1: B2B Lookup - CRDB Bank Account
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}B2B LOOKUP TEST - CRDB BANK ACCOUNT{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

try {
    $eftService = app(ExternalFundsTransferService::class);
    echo "Testing B2B Lookup...\n";
    echo "• Account: 12334567789\n";
    echo "• Bank: CRDB (CORUTZTZ)\n";
    echo "• Amount: TZS 5,000\n\n";
    
    $startTime = microtime(true);
    $result = $eftService->lookupAccount('12334567789', 'CORUTZTZ', 5000);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($result['success']) {
        echo "{$GREEN}✅ B2B LOOKUP SUCCESS{$NC} ({$duration}ms)\n";
        echo "• Account Name: {$GREEN}{$result['account_name']}{$NC}\n";
        echo "• Actual Identifier: {$result['actual_identifier']}\n";
        echo "• FSP ID: {$result['fsp_id']}\n";
        echo "• Engine Ref: " . substr($result['engine_ref'], 0, 30) . "...\n";
        echo "• Can Receive: " . ($result['can_receive'] ? 'Yes' : 'No') . "\n";
        $results['B2B_CRDB'] = 'SUCCESS';
    } else {
        echo "{$RED}❌ B2B Lookup Failed: {$result['error']}{$NC}\n";
        $results['B2B_CRDB'] = 'FAILED';
    }
} catch (Exception $e) {
    echo "{$RED}❌ B2B Error: " . $e->getMessage() . "{$NC}\n";
    $results['B2B_CRDB'] = 'ERROR';
}

echo "\n";

// Test 2: B2B Lookup - NMB Bank Account
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}B2B LOOKUP TEST - NMB BANK ACCOUNT{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

try {
    $eftService = app(ExternalFundsTransferService::class);
    echo "Testing B2B Lookup...\n";
    echo "• Account: 20110033445\n";
    echo "• Bank: NMB (NMBTZTZX)\n";
    echo "• Amount: TZS 10,000\n\n";
    
    $startTime = microtime(true);
    $result = $eftService->lookupAccount('20110033445', 'NMBTZTZX', 10000);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($result['success']) {
        echo "{$GREEN}✅ B2B LOOKUP SUCCESS{$NC} ({$duration}ms)\n";
        echo "• Account Name: {$GREEN}{$result['account_name']}{$NC}\n";
        echo "• Actual Identifier: {$result['actual_identifier']}\n";
        echo "• FSP ID: {$result['fsp_id']}\n";
        echo "• Engine Ref: " . substr($result['engine_ref'], 0, 30) . "...\n";
        echo "• Can Receive: " . ($result['can_receive'] ? 'Yes' : 'No') . "\n";
        $results['B2B_NMB'] = 'SUCCESS';
    } else {
        echo "{$RED}❌ B2B Lookup Failed: {$result['error']}{$NC}\n";
        $results['B2B_NMB'] = 'FAILED';
    }
} catch (Exception $e) {
    echo "{$RED}❌ B2B Error: " . $e->getMessage() . "{$NC}\n";
    $results['B2B_NMB'] = 'ERROR';
}

echo "\n";

// Test 3: B2W Lookup - M-Pesa
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}B2W LOOKUP TEST - M-PESA WALLET{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

try {
    $walletService = app(MobileWalletTransferService::class);
    echo "Testing B2W Lookup...\n";
    echo "• Phone: 0748045601\n";
    echo "• Provider: M-Pesa\n";
    echo "• Amount: TZS 5,000\n\n";
    
    $startTime = microtime(true);
    $result = $walletService->lookupWallet('0748045601', 'MPESA', 5000);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($result['success']) {
        echo "{$GREEN}✅ B2W LOOKUP SUCCESS{$NC} ({$duration}ms)\n";
        echo "• Account Name: {$GREEN}{$result['account_name']}{$NC}\n";
        echo "• Actual Identifier: {$result['actual_identifier']}\n";
        echo "• FSP ID: {$result['fsp_id']}\n";
        echo "• Engine Ref: " . substr($result['engine_ref'], 0, 30) . "...\n";
        echo "• Can Receive: " . ($result['can_receive'] ? 'Yes' : 'No') . "\n";
        $results['B2W_MPESA'] = 'SUCCESS';
    } else {
        echo "{$RED}❌ B2W Lookup Failed: {$result['error']}{$NC}\n";
        $results['B2W_MPESA'] = 'FAILED';
    }
} catch (Exception $e) {
    echo "{$RED}❌ B2W Error: " . $e->getMessage() . "{$NC}\n";
    $results['B2W_MPESA'] = 'ERROR';
}

echo "\n";

// Test 4: B2W Lookup - Tigo Pesa
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}B2W LOOKUP TEST - TIGO PESA WALLET{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

try {
    $walletService = app(MobileWalletTransferService::class);
    echo "Testing B2W Lookup...\n";
    echo "• Phone: 0658045601\n";
    echo "• Provider: Tigo Pesa\n";
    echo "• Amount: TZS 3,000\n\n";
    
    $startTime = microtime(true);
    $result = $walletService->lookupWallet('0658045601', 'TIGOPESA', 3000);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($result['success']) {
        echo "{$GREEN}✅ B2W LOOKUP SUCCESS{$NC} ({$duration}ms)\n";
        echo "• Account Name: {$GREEN}{$result['account_name']}{$NC}\n";
        echo "• Actual Identifier: {$result['actual_identifier']}\n";
        echo "• FSP ID: {$result['fsp_id']}\n";
        echo "• Engine Ref: " . substr($result['engine_ref'], 0, 30) . "...\n";
        echo "• Can Receive: " . ($result['can_receive'] ? 'Yes' : 'No') . "\n";
        $results['B2W_TIGO'] = 'SUCCESS';
    } else {
        echo "{$RED}❌ B2W Lookup Failed: {$result['error']}{$NC}\n";
        $results['B2W_TIGO'] = 'FAILED';
    }
} catch (Exception $e) {
    echo "{$RED}❌ B2W Error: " . $e->getMessage() . "{$NC}\n";
    $results['B2W_TIGO'] = 'ERROR';
}

echo "\n";

// Summary
echo "{$CYAN}================================================\n";
echo "              LOOKUP TEST SUMMARY\n";
echo "================================================{$NC}\n\n";

$b2bCount = 0;
$b2wCount = 0;

echo "{$YELLOW}B2B (Bank-to-Bank) Lookups:{$NC}\n";
foreach ($results as $test => $status) {
    if (strpos($test, 'B2B') === 0) {
        $statusColor = $status === 'SUCCESS' ? $GREEN : $RED;
        $icon = $status === 'SUCCESS' ? '✓' : '✗';
        echo "• {$test}: {$statusColor}{$icon} {$status}{$NC}\n";
        if ($status === 'SUCCESS') $b2bCount++;
    }
}

echo "\n{$YELLOW}B2W (Bank-to-Wallet) Lookups:{$NC}\n";
foreach ($results as $test => $status) {
    if (strpos($test, 'B2W') === 0) {
        $statusColor = $status === 'SUCCESS' ? $GREEN : $RED;
        $icon = $status === 'SUCCESS' ? '✓' : '✗';
        echo "• {$test}: {$statusColor}{$icon} {$status}{$NC}\n";
        if ($status === 'SUCCESS') $b2wCount++;
    }
}

echo "\n{$YELLOW}Statistics:{$NC}\n";
echo "• B2B Success Rate: " . ($b2bCount > 0 ? "{$GREEN}" : "{$RED}") . $b2bCount . "/2{$NC}\n";
echo "• B2W Success Rate: " . ($b2wCount > 0 ? "{$GREEN}" : "{$RED}") . $b2wCount . "/2{$NC}\n";

echo "\n{$YELLOW}Configuration:{$NC}\n";
echo "• Endpoint: " . config('services.nbc_payments.base_url') . "/domestix/api/v2/lookup\n";
echo "• Client ID: " . config('services.nbc_payments.client_id') . "\n";
echo "• API Key: " . (config('services.nbc_payments.api_key') ? '[CONFIGURED]' : '[MISSING]') . "\n";

echo "\n{$BLUE}=== Lookup Test Complete ==={$NC}\n\n";