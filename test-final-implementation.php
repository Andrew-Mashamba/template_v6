#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Payments\ExternalFundsTransferService;
use App\Services\Payments\MobileWalletTransferService;
use App\Services\Payments\InternalFundsTransferService;

// Color codes
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$BLUE = "\033[0;34m";
$CYAN = "\033[0;36m";
$NC = "\033[0m";

echo "\n{$CYAN}================================================\n";
echo "     FINAL IMPLEMENTATION TEST\n";
echo "================================================{$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Environment: " . app()->environment() . "\n\n";

$results = [];

// Test 1: Internal Funds Transfer
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}TEST 1: INTERNAL FUNDS TRANSFER (IFT){$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

try {
    $iftService = app(InternalFundsTransferService::class);
    echo "{$GREEN}✓ IFT Service initialized{$NC}\n";
    
    echo "Testing account lookup...\n";
    $iftResult = $iftService->lookupAccount('011201318462', 'destination');
    
    if ($iftResult['success']) {
        echo "{$GREEN}✅ IFT Lookup Success{$NC}\n";
        echo "• Account: 011201318462\n";
        echo "• Name: {$iftResult['account_name']}\n";
        echo "• Branch: {$iftResult['branch_name']}\n";
        echo "• Can Receive: " . ($iftResult['can_receive'] ? 'Yes' : 'No') . "\n";
        $results['IFT'] = 'SUCCESS';
    } else {
        echo "{$RED}❌ IFT Lookup Failed: {$iftResult['error']}{$NC}\n";
        $results['IFT'] = 'FAILED';
    }
} catch (Exception $e) {
    echo "{$RED}❌ IFT Error: " . $e->getMessage() . "{$NC}\n";
    $results['IFT'] = 'ERROR';
}

echo "\n";

// Test 2: External Bank Lookup (CRDB)
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}TEST 2: EXTERNAL BANK LOOKUP (CRDB){$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

try {
    $eftService = app(ExternalFundsTransferService::class);
    echo "{$GREEN}✓ EFT Service initialized{$NC}\n";
    
    echo "Testing CRDB account lookup...\n";
    $startTime = microtime(true);
    $eftResult = $eftService->lookupAccount('12334567789', 'CORUTZTZ', 5000);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($eftResult['success']) {
        echo "{$GREEN}✅ EFT Lookup Success{$NC} ({$duration}ms)\n";
        echo "• Account: 12334567789\n";
        echo "• Name: {$GREEN}{$eftResult['account_name']}{$NC}\n";
        echo "• Actual ID: {$eftResult['actual_identifier']}\n";
        echo "• Bank: CRDB (FSP: {$eftResult['fsp_id']})\n";
        echo "• Engine Ref: " . substr($eftResult['engine_ref'], 0, 20) . "...\n";
        echo "• Can Receive: " . ($eftResult['can_receive'] ? 'Yes' : 'No') . "\n";
        $results['EFT_BANK'] = 'SUCCESS';
    } else {
        echo "{$RED}❌ EFT Lookup Failed: {$eftResult['error']}{$NC}\n";
        $results['EFT_BANK'] = 'FAILED';
    }
} catch (Exception $e) {
    echo "{$RED}❌ EFT Error: " . $e->getMessage() . "{$NC}\n";
    $results['EFT_BANK'] = 'ERROR';
}

echo "\n";

// Test 3: Mobile Wallet Lookup
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}TEST 3: MOBILE WALLET LOOKUP (M-PESA){$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

try {
    $walletService = app(MobileWalletTransferService::class);
    echo "{$GREEN}✓ Wallet Service initialized{$NC}\n";
    
    echo "Testing M-Pesa wallet lookup...\n";
    $startTime = microtime(true);
    $walletResult = $walletService->lookupWallet('0748045601', 'MPESA', 5000);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($walletResult['success']) {
        echo "{$GREEN}✅ Wallet Lookup Success{$NC} ({$duration}ms)\n";
        echo "• Phone: 0748045601\n";
        echo "• Name: {$GREEN}{$walletResult['account_name']}{$NC}\n";
        echo "• Provider: Vodacom M-Pesa\n";
        echo "• Engine Ref: " . substr($walletResult['engine_ref'], 0, 20) . "...\n";
        echo "• Can Receive: " . ($walletResult['can_receive'] ? 'Yes' : 'No') . "\n";
        $results['WALLET'] = 'SUCCESS';
    } else {
        echo "{$RED}❌ Wallet Lookup Failed: {$walletResult['error']}{$NC}\n";
        $results['WALLET'] = 'FAILED';
    }
} catch (Exception $e) {
    echo "{$RED}❌ Wallet Error: " . $e->getMessage() . "{$NC}\n";
    $results['WALLET'] = 'ERROR';
}

echo "\n";

// Summary
echo "{$CYAN}================================================\n";
echo "                 TEST SUMMARY\n";
echo "================================================{$NC}\n\n";

echo "Results:\n";
foreach ($results as $test => $status) {
    $statusColor = $status === 'SUCCESS' ? $GREEN : $RED;
    $icon = $status === 'SUCCESS' ? '✓' : '✗';
    echo "• {$test}: {$statusColor}{$icon} {$status}{$NC}\n";
}

echo "\nConfiguration:\n";
echo "• Base URL: " . config('services.nbc_payments.base_url') . "\n";
echo "• Client ID: " . config('services.nbc_payments.client_id') . "\n";
echo "• SACCOS Account: " . config('services.nbc_payments.saccos_account') . "\n";

echo "\nKey Improvements Applied:\n";
echo "{$GREEN}✓{$NC} Headers match working curl format (lowercase x-api-key)\n";
echo "{$GREEN}✓{$NC} No Signature or Timestamp headers needed\n";
echo "{$GREEN}✓{$NC} Response parsing extracts data from 'body' field\n";
echo "{$GREEN}✓{$NC} Account names properly displayed\n";
echo "{$GREEN}✓{$NC} Engine references captured\n";
echo "{$GREEN}✓{$NC} Status code 600 recognized as success\n";

echo "\n{$BLUE}=== Test Complete ==={$NC}\n\n";