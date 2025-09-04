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
echo "     TRANSFER EXECUTION TEST\n";
echo "================================================{$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

echo "{$YELLOW}⚠ WARNING: This will attempt REAL transfers!{$NC}\n";
echo "We'll use small test amounts (100 TZS) to minimize risk.\n\n";

// Test configurations
$testTransfers = [
    'internal' => [
        'enabled' => true,
        'from_account' => '015103001490', // SACCOS account
        'to_account' => '011201318462',
        'amount' => 100,
        'narration' => 'Internal transfer test'
    ],
    'external_bank' => [
        'enabled' => true,
        'from_account' => '015103001490',
        'to_account' => '12334567789',
        'bank_code' => 'CORUTZTZ', // CRDB (working)
        'amount' => 100,
        'narration' => 'External bank transfer test'
    ],
    'wallet' => [
        'enabled' => true,
        'from_account' => '015103001490',
        'phone_number' => '0748045601',
        'provider' => 'MPESA',
        'amount' => 100,
        'narration' => 'Mobile wallet transfer test'
    ]
];

echo "Press Enter to continue with test transfers or Ctrl+C to cancel...";
fgets(STDIN);

$results = [];

// Test 1: Internal Transfer (IFT)
if ($testTransfers['internal']['enabled']) {
    echo "\n{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
    echo "{$YELLOW}TEST 1: INTERNAL TRANSFER (IFT){$NC}\n";
    echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";
    
    $service = app(InternalFundsTransferService::class);
    
    echo "From: {$testTransfers['internal']['from_account']}\n";
    echo "To: {$testTransfers['internal']['to_account']}\n";
    echo "Amount: {$testTransfers['internal']['amount']} TZS\n\n";
    
    // First do lookup
    echo "Step 1: Account lookup...\n";
    $lookupResult = $service->lookupAccount($testTransfers['internal']['to_account'], 'destination');
    
    if ($lookupResult['success']) {
        echo "{$GREEN}✓ Lookup successful{$NC}\n";
        echo "Account Name: {$lookupResult['account_name']}\n\n";
        
        echo "Step 2: Executing transfer...\n";
        try {
            $startTime = microtime(true);
            $result = $service->transfer($testTransfers['internal']);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result['success'] ?? false) {
                echo "{$GREEN}✅ TRANSFER SUCCESS{$NC} ({$duration}ms)\n";
                echo "Reference: " . ($result['reference'] ?? 'N/A') . "\n";
                echo "NBC Reference: " . ($result['nbc_reference'] ?? 'N/A') . "\n";
                echo "Message: " . ($result['message'] ?? 'Transfer completed') . "\n";
                $results['internal'] = 'SUCCESS';
            } else {
                echo "{$RED}❌ TRANSFER FAILED{$NC}\n";
                echo "Error: " . ($result['error'] ?? $result['message'] ?? 'Unknown error') . "\n";
                $results['internal'] = 'FAILED';
            }
        } catch (Exception $e) {
            echo "{$RED}❌ EXCEPTION{$NC}: " . $e->getMessage() . "\n";
            $results['internal'] = 'ERROR';
        }
    } else {
        echo "{$RED}✗ Lookup failed{$NC}: {$lookupResult['error']}\n";
        echo "Skipping transfer...\n";
        $results['internal'] = 'SKIPPED';
    }
}

// Test 2: External Bank Transfer (B2B)
if ($testTransfers['external_bank']['enabled']) {
    echo "\n{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
    echo "{$YELLOW}TEST 2: EXTERNAL BANK TRANSFER (B2B){$NC}\n";
    echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";
    
    $service = app(ExternalFundsTransferService::class);
    
    echo "From: {$testTransfers['external_bank']['from_account']}\n";
    echo "To: {$testTransfers['external_bank']['to_account']} (CRDB)\n";
    echo "Amount: {$testTransfers['external_bank']['amount']} TZS\n\n";
    
    // First do lookup
    echo "Step 1: Account lookup...\n";
    $lookupResult = $service->lookupAccount(
        $testTransfers['external_bank']['to_account'],
        $testTransfers['external_bank']['bank_code'],
        $testTransfers['external_bank']['amount']
    );
    
    if ($lookupResult['success']) {
        echo "{$GREEN}✓ Lookup successful{$NC}\n";
        echo "Account Name: {$lookupResult['account_name']}\n";
        echo "FSP ID: {$lookupResult['fsp_id']}\n\n";
        
        echo "Step 2: Executing transfer...\n";
        try {
            // Add lookup_ref from the lookup result
            $transferData = $testTransfers['external_bank'];
            $transferData['lookup_ref'] = $lookupResult['engine_ref'] ?? '';
            $transferData['charge_bearer'] = 'OUR';
            $transferData['payer_phone'] = '255715000001';
            
            $startTime = microtime(true);
            $result = $service->transfer($transferData);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result['success'] ?? false) {
                echo "{$GREEN}✅ TRANSFER SUCCESS{$NC} ({$duration}ms)\n";
                echo "Reference: " . ($result['reference'] ?? 'N/A') . "\n";
                echo "NBC Reference: " . ($result['nbc_reference'] ?? 'N/A') . "\n";
                echo "Message: " . ($result['message'] ?? 'Transfer completed') . "\n";
                $results['external_bank'] = 'SUCCESS';
            } else {
                echo "{$RED}❌ TRANSFER FAILED{$NC}\n";
                echo "Error: " . ($result['error'] ?? $result['message'] ?? 'Unknown error') . "\n";
                $results['external_bank'] = 'FAILED';
            }
        } catch (Exception $e) {
            echo "{$RED}❌ EXCEPTION{$NC}: " . $e->getMessage() . "\n";
            $results['external_bank'] = 'ERROR';
        }
    } else {
        echo "{$RED}✗ Lookup failed{$NC}: {$lookupResult['error']}\n";
        echo "Skipping transfer...\n";
        $results['external_bank'] = 'SKIPPED';
    }
}

// Test 3: Mobile Wallet Transfer (B2W)
if ($testTransfers['wallet']['enabled']) {
    echo "\n{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
    echo "{$YELLOW}TEST 3: MOBILE WALLET TRANSFER (B2W){$NC}\n";
    echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";
    
    $service = app(MobileWalletTransferService::class);
    
    echo "From: {$testTransfers['wallet']['from_account']}\n";
    echo "To: {$testTransfers['wallet']['phone_number']} (M-Pesa)\n";
    echo "Amount: {$testTransfers['wallet']['amount']} TZS\n\n";
    
    // First do lookup
    echo "Step 1: Wallet lookup...\n";
    $lookupResult = $service->lookupWallet(
        $testTransfers['wallet']['phone_number'],
        $testTransfers['wallet']['provider'],
        $testTransfers['wallet']['amount']
    );
    
    if ($lookupResult['success']) {
        echo "{$GREEN}✓ Lookup successful{$NC}\n";
        echo "Account Name: {$lookupResult['account_name']}\n";
        echo "FSP ID: {$lookupResult['fsp_id']}\n\n";
        
        echo "Step 2: Executing transfer...\n";
        try {
            // Add required fields
            $transferData = $testTransfers['wallet'];
            $transferData['charge_bearer'] = 'OUR';
            $transferData['payer_phone'] = '255715000001';
            
            $startTime = microtime(true);
            $result = $service->transfer($transferData);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result['success'] ?? false) {
                echo "{$GREEN}✅ TRANSFER SUCCESS{$NC} ({$duration}ms)\n";
                echo "Reference: " . ($result['reference'] ?? 'N/A') . "\n";
                echo "NBC Reference: " . ($result['nbc_reference'] ?? 'N/A') . "\n";
                echo "Message: " . ($result['message'] ?? 'Transfer completed') . "\n";
                $results['wallet'] = 'SUCCESS';
            } else {
                echo "{$RED}❌ TRANSFER FAILED{$NC}\n";
                echo "Error: " . ($result['error'] ?? $result['message'] ?? 'Unknown error') . "\n";
                $results['wallet'] = 'FAILED';
            }
        } catch (Exception $e) {
            echo "{$RED}❌ EXCEPTION{$NC}: " . $e->getMessage() . "\n";
            $results['wallet'] = 'ERROR';
        }
    } else {
        echo "{$RED}✗ Lookup failed{$NC}: {$lookupResult['error']}\n";
        echo "Skipping transfer...\n";
        $results['wallet'] = 'SKIPPED';
    }
}

// Summary
echo "\n{$CYAN}================================================\n";
echo "              TRANSFER TEST SUMMARY\n";
echo "================================================{$NC}\n\n";

foreach ($results as $type => $status) {
    $color = $status === 'SUCCESS' ? $GREEN : ($status === 'SKIPPED' ? $YELLOW : $RED);
    $icon = $status === 'SUCCESS' ? '✓' : ($status === 'SKIPPED' ? '⚠' : '✗');
    echo "• " . str_pad(ucfirst(str_replace('_', ' ', $type)), 20) . ": {$color}{$icon} {$status}{$NC}\n";
}

echo "\n{$YELLOW}Important Notes:{$NC}\n";
echo "• Transfers use real accounts and may debit actual funds\n";
echo "• NBC UAT environment may have balance/limit restrictions\n";
echo "• Some transfers may fail due to account status or balance\n";
echo "• Check logs in storage/logs/MoneyTransfer/ for details\n";

echo "\n{$BLUE}=== Test Complete ==={$NC}\n\n";