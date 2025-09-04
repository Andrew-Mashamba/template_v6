#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Payments\FSPService;
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
echo "     BATCH FSP TESTING (REMAINING FSPS)\n";
echo "================================================{$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Note: This will test FSPs not yet verified\n\n";

$workingFsps = config('working_fsps');
$pendingBanks = $workingFsps['pending_testing']['banks'] ?? [];
$pendingWallets = $workingFsps['pending_testing']['wallets'] ?? [];

echo "{$YELLOW}Pending Tests:{$NC}\n";
echo "• Banks: " . count($pendingBanks) . "\n";
echo "• Wallets: " . count($pendingWallets) . "\n\n";

// Ask for confirmation
echo "{$YELLOW}This will test " . (count($pendingBanks) + count($pendingWallets)) . " FSPs.{$NC}\n";
echo "Testing will be done in batches with delays to avoid overwhelming the API.\n";
echo "Estimated time: " . round((count($pendingBanks) + count($pendingWallets)) * 2 / 60, 1) . " minutes\n\n";

echo "Press Enter to continue or Ctrl+C to cancel...";
fgets(STDIN);

$fspService = app(FSPService::class);
$eftService = app(ExternalFundsTransferService::class);
$walletService = app(MobileWalletTransferService::class);

// Test accounts to use
$testAccounts = [
    'default' => '01234567890',
    'fallback' => '98765432100'
];

$testPhones = [
    'vodacom' => '0748000000',
    'tigo' => '0658000000', 
    'airtel' => '0784000000',
    'halotel' => '0628000000'
];

$results = [];
$batchSize = 3; // Test 3 FSPs at a time
$batchDelay = 5000000; // 5 seconds between batches

// Get all FSP configurations
$allBanks = config('fsp_providers.banks', []);
$allWallets = config('fsp_providers.mobile_wallets', []);

echo "\n{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}TESTING REMAINING BANKS{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

$testedCount = 0;
$successCount = 0;
$failedCount = 0;

// Test banks in batches
$bankBatches = array_chunk($pendingBanks, $batchSize);

foreach ($bankBatches as $batchIndex => $batch) {
    echo "{$CYAN}Batch " . ($batchIndex + 1) . " of " . count($bankBatches) . "{$NC}\n";
    
    foreach ($batch as $bankKey) {
        if (!isset($allBanks[$bankKey])) {
            continue;
        }
        
        $bank = $allBanks[$bankKey];
        $testedCount++;
        
        echo "\n[{$testedCount}] Testing {$bankKey} ({$bank['name']})...\n";
        echo "  Code: {$bank['code']}\n";
        echo "  FSP ID: {$bank['fsp_id']}\n";
        
        try {
            $startTime = microtime(true);
            
            // Use shorter timeout for batch testing
            $result = $eftService->lookupAccount(
                $testAccounts['default'],
                $bank['code'],
                1000
            );
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result['success']) {
                $successCount++;
                echo "  {$GREEN}✓ SUCCESS{$NC} ({$duration}ms)\n";
                echo "  Response: Account lookup successful\n";
                
                $results['banks'][$bankKey] = [
                    'status' => 'SUCCESS',
                    'time' => $duration,
                    'fsp_id' => $bank['fsp_id']
                ];
            } else {
                $failedCount++;
                $errorMsg = substr($result['error'], 0, 60);
                echo "  {$RED}✗ FAILED{$NC} ({$duration}ms)\n";
                echo "  Error: {$errorMsg}\n";
                
                $results['banks'][$bankKey] = [
                    'status' => 'FAILED',
                    'error' => $result['error'],
                    'time' => $duration
                ];
            }
        } catch (Exception $e) {
            $failedCount++;
            echo "  {$RED}✗ ERROR{$NC}: " . substr($e->getMessage(), 0, 50) . "\n";
            
            $results['banks'][$bankKey] = [
                'status' => 'ERROR',
                'error' => $e->getMessage()
            ];
        }
        
        // Small delay between individual tests
        usleep(500000); // 0.5 seconds
    }
    
    if ($batchIndex < count($bankBatches) - 1) {
        echo "\n{$YELLOW}Waiting before next batch...{$NC}\n";
        usleep($batchDelay);
    }
}

echo "\n{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}TESTING REMAINING WALLETS{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

// Test wallets
foreach ($pendingWallets as $walletKey) {
    if (!isset($allWallets[$walletKey])) {
        continue;
    }
    
    $wallet = $allWallets[$walletKey];
    $testedCount++;
    
    // Determine test phone based on provider
    $testPhone = $testPhones['vodacom']; // default
    if (strpos(strtolower($walletKey), 'tigo') !== false) {
        $testPhone = $testPhones['tigo'];
    } elseif (strpos(strtolower($walletKey), 'airtel') !== false) {
        $testPhone = $testPhones['airtel'];
    } elseif (strpos(strtolower($walletKey), 'halo') !== false) {
        $testPhone = $testPhones['halotel'];
    }
    
    echo "\n[{$testedCount}] Testing {$walletKey} ({$wallet['name']})...\n";
    echo "  Code: {$wallet['code']}\n";
    echo "  FSP ID: {$wallet['fsp_id']}\n";
    echo "  Test Phone: {$testPhone}\n";
    
    try {
        $startTime = microtime(true);
        
        $result = $walletService->lookupWallet(
            $testPhone,
            $walletKey,
            1000
        );
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($result['success']) {
            $successCount++;
            echo "  {$GREEN}✓ SUCCESS{$NC} ({$duration}ms)\n";
            
            $results['wallets'][$walletKey] = [
                'status' => 'SUCCESS',
                'time' => $duration,
                'fsp_id' => $wallet['fsp_id']
            ];
        } else {
            $failedCount++;
            echo "  {$RED}✗ FAILED{$NC} ({$duration}ms)\n";
            echo "  Error: " . substr($result['error'], 0, 60) . "\n";
            
            $results['wallets'][$walletKey] = [
                'status' => 'FAILED',
                'error' => $result['error'],
                'time' => $duration
            ];
        }
    } catch (Exception $e) {
        $failedCount++;
        echo "  {$RED}✗ ERROR{$NC}: " . substr($e->getMessage(), 0, 50) . "\n";
        
        $results['wallets'][$walletKey] = [
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];
    }
    
    usleep(1000000); // 1 second delay between wallet tests
}

// Generate Summary
echo "\n{$CYAN}================================================\n";
echo "              BATCH TEST SUMMARY\n";
echo "================================================{$NC}\n\n";

echo "{$YELLOW}Results:{$NC}\n";
echo "• Total Tested: {$testedCount}\n";
echo "• Successful: {$GREEN}{$successCount}{$NC}\n";
echo "• Failed: {$RED}{$failedCount}{$NC}\n";
echo "• Success Rate: " . ($testedCount > 0 ? round($successCount/$testedCount*100, 1) : 0) . "%\n\n";

// Show newly working FSPs
$newlyWorking = [];
foreach ($results['banks'] ?? [] as $bank => $result) {
    if ($result['status'] === 'SUCCESS') {
        $newlyWorking[] = $bank;
    }
}
foreach ($results['wallets'] ?? [] as $wallet => $result) {
    if ($result['status'] === 'SUCCESS') {
        $newlyWorking[] = $wallet;
    }
}

if (count($newlyWorking) > 0) {
    echo "{$GREEN}Newly Working FSPs:{$NC}\n";
    foreach ($newlyWorking as $fsp) {
        echo "• {$fsp}\n";
    }
    echo "\n";
}

// Save results
$reportPath = __DIR__ . '/storage/logs/batch_fsp_test_' . date('Ymd_His') . '.json';
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'tested' => $testedCount,
    'successful' => $successCount,
    'failed' => $failedCount,
    'results' => $results,
    'newly_working' => $newlyWorking
];

file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));

echo "{$GREEN}Results saved to:{$NC}\n";
echo "storage/logs/batch_fsp_test_" . date('Ymd_His') . ".json\n";

echo "\n{$BLUE}=== Batch Testing Complete ==={$NC}\n\n";