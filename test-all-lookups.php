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
echo "     COMPREHENSIVE LOOKUP VERIFICATION\n";
echo "================================================{$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$results = [];

// Test 1: IFT (Internal Funds Transfer)
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}1. INTERNAL FUNDS TRANSFER (IFT){$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

try {
    $iftService = app(InternalFundsTransferService::class);
    $result = $iftService->lookupAccount('011201318462', 'destination');
    
    if ($result['success']) {
        echo "{$GREEN}✅ IFT Lookup SUCCESS{$NC}\n";
        echo "• Account: 011201318462\n";
        echo "• Name: {$result['account_name']}\n";
        $results['IFT'] = 'SUCCESS';
    } else {
        echo "{$RED}❌ IFT Lookup Failed{$NC}\n";
        $results['IFT'] = 'FAILED';
    }
} catch (Exception $e) {
    echo "{$RED}❌ IFT Error: " . $e->getMessage() . "{$NC}\n";
    $results['IFT'] = 'ERROR';
}

echo "\n";

// Test 2: B2B - CRDB
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}2. B2B LOOKUP - CRDB BANK{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

try {
    $eftService = app(ExternalFundsTransferService::class);
    $startTime = microtime(true);
    $result = $eftService->lookupAccount('12334567789', 'CORUTZTZ', 5000);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($result['success']) {
        echo "{$GREEN}✅ B2B Lookup SUCCESS{$NC} ({$duration}ms)\n";
        echo "• Account: 12334567789\n";
        echo "• Name: {$GREEN}{$result['account_name']}{$NC}\n";
        echo "• Bank: CRDB\n";
        echo "• FSP ID: {$result['fsp_id']}\n";
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

// Test 3: B2W - M-Pesa
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}3. B2W LOOKUP - M-PESA{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

try {
    $walletService = app(MobileWalletTransferService::class);
    $startTime = microtime(true);
    $result = $walletService->lookupWallet('0748045601', 'MPESA', 5000);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($result['success']) {
        echo "{$GREEN}✅ B2W Lookup SUCCESS{$NC} ({$duration}ms)\n";
        echo "• Phone: 0748045601\n";
        echo "• Name: {$GREEN}{$result['account_name']}{$NC}\n";
        echo "• Provider: M-Pesa\n";
        echo "• FSP ID: {$result['fsp_id']}\n";
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

// Test 4: B2W - Tigo Pesa  
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}4. B2W LOOKUP - TIGO PESA{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

try {
    $walletService = app(MobileWalletTransferService::class);
    $startTime = microtime(true);
    $result = $walletService->lookupWallet('0658045601', 'TIGOPESA', 3000);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($result['success']) {
        echo "{$GREEN}✅ B2W Lookup SUCCESS{$NC} ({$duration}ms)\n";
        echo "• Phone: 0658045601\n";
        echo "• Name: {$GREEN}{$result['account_name']}{$NC}\n";
        echo "• Provider: Tigo Pesa\n";
        echo "• FSP ID: {$result['fsp_id']}\n";
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
echo "                SUMMARY\n";
echo "================================================{$NC}\n\n";

$successCount = 0;
$totalCount = count($results);

foreach ($results as $test => $status) {
    $statusColor = $status === 'SUCCESS' ? $GREEN : $RED;
    $icon = $status === 'SUCCESS' ? '✓' : '✗';
    echo "• {$test}: {$statusColor}{$icon} {$status}{$NC}\n";
    if ($status === 'SUCCESS') $successCount++;
}

echo "\n{$YELLOW}Statistics:{$NC}\n";
echo "• Success Rate: ";
if ($successCount == $totalCount) {
    echo "{$GREEN}{$successCount}/{$totalCount} (100%){$NC}\n";
} elseif ($successCount > 0) {
    echo "{$YELLOW}{$successCount}/{$totalCount} (" . round($successCount/$totalCount*100) . "%){$NC}\n";
} else {
    echo "{$RED}{$successCount}/{$totalCount} (0%){$NC}\n";
}

echo "\n{$YELLOW}Key Improvements Applied:{$NC}\n";
echo "{$GREEN}✓{$NC} Numeric timestamp for lookup clientRef\n";
echo "{$GREEN}✓{$NC} Lowercase x-api-key header\n";
echo "{$GREEN}✓{$NC} No Signature/Timestamp headers for lookups\n";
echo "{$GREEN}✓{$NC} Proper response parsing from body field\n";
echo "{$GREEN}✓{$NC} Status code 600 recognized as success\n";

echo "\n{$BLUE}=== Test Complete ==={$NC}\n\n";