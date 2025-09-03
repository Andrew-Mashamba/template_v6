#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Payments\InternalFundsTransferService;

echo "\n=====================================\n";
echo "  TESTING UPDATED IFT SERVICE\n";
echo "=====================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Color codes
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$BLUE = "\033[0;34m";
$NC = "\033[0m";

try {
    echo "{$YELLOW}1. Testing IFT Service Initialization{$NC}\n";
    $iftService = app(InternalFundsTransferService::class);
    echo "   {$GREEN}✓ Service initialized successfully{$NC}\n\n";
    
    echo "{$YELLOW}2. Testing Account Lookup (Local Validation){$NC}\n";
    $sourceAccount = '06012040022';
    $destAccount = '011201318462';
    
    echo "   Testing source account: {$sourceAccount}\n";
    $sourceResult = $iftService->lookupAccount($sourceAccount, 'source');
    
    if ($sourceResult['success']) {
        echo "   {$GREEN}✓ Source account validated{$NC}\n";
        echo "     • Account Name: {$sourceResult['account_name']}\n";
        echo "     • Branch: {$sourceResult['branch_name']}\n";
        echo "     • Can Debit: " . ($sourceResult['can_debit'] ? 'Yes' : 'No') . "\n\n";
    } else {
        echo "   {$RED}✗ Source account validation failed: {$sourceResult['error']}{$NC}\n\n";
    }
    
    echo "   Testing destination account: {$destAccount}\n";
    $destResult = $iftService->lookupAccount($destAccount, 'destination');
    
    if ($destResult['success']) {
        echo "   {$GREEN}✓ Destination account validated{$NC}\n";
        echo "     • Account Name: {$destResult['account_name']}\n";
        echo "     • Branch: {$destResult['branch_name']}\n";
        echo "     • Can Receive: " . ($destResult['can_receive'] ? 'Yes' : 'No') . "\n\n";
    } else {
        echo "   {$RED}✗ Destination account validation failed: {$destResult['error']}{$NC}\n\n";
    }
    
    echo "{$YELLOW}3. Testing IFT Transfer{$NC}\n";
    echo "   From: {$sourceAccount}\n";
    echo "   To: {$destAccount}\n";
    echo "   Amount: 5,000 TZS\n\n";
    
    $transferResult = $iftService->transfer([
        'from_account' => $sourceAccount,
        'to_account' => $destAccount,
        'amount' => 5000,
        'narration' => 'Test IFT Transfer'
    ]);
    
    if ($transferResult['success']) {
        echo "   {$GREEN}✓ Transfer completed successfully{$NC}\n";
        echo "     • Reference: {$transferResult['reference']}\n";
        echo "     • NBC Reference: {$transferResult['nbc_reference']}\n";
        echo "     • Message: {$transferResult['message']}\n";
        echo "     • Response Time: {$transferResult['response_time']}ms\n";
    } else {
        echo "   {$RED}✗ Transfer failed: {$transferResult['error']}{$NC}\n";
    }
    
} catch (Exception $e) {
    echo "{$RED}Error: " . $e->getMessage() . "{$NC}\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n{$BLUE}=== Test Completed ==={$NC}\n\n";