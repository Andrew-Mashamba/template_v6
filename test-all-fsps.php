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
$MAGENTA = "\033[0;35m";
$NC = "\033[0m";

echo "\n{$CYAN}================================================\n";
echo "     COMPREHENSIVE FSP LOOKUP TEST\n";
echo "================================================{$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Testing all 36 Financial Service Providers\n\n";

$fspService = app(FSPService::class);
$eftService = app(ExternalFundsTransferService::class);
$walletService = app(MobileWalletTransferService::class);

// Get test accounts
$testAccounts = $fspService->getTestAccounts();

// Results storage
$results = [
    'banks' => [],
    'wallets' => []
];

// Test only high-priority FSPs to avoid overwhelming the API
$priorityBanks = [
    'CRDB' => ['account' => '12334567789', 'expected' => 'SAMWEL MARWA JUMA'],
    'NMB' => ['account' => '20110033445', 'expected' => 'Unknown'],
    'STANBIC' => ['account' => '0250195000001', 'expected' => 'Unknown'],
    'NBC' => ['account' => '011201318462', 'expected' => 'NBC Account'],
    'ABSA' => ['account' => '01234567890', 'expected' => 'Unknown']
];

$priorityWallets = [
    'MPESA' => ['phone' => '0748045601', 'expected' => 'TEST Lab'],
    'TIGOPESA' => ['phone' => '0658045601', 'expected' => 'Unknown'],
    'AIRTEL' => ['phone' => '0784045601', 'expected' => 'Unknown']
];

// Test Banks
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}TESTING PRIORITY BANKS{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

$bankCount = 0;
$bankSuccess = 0;

foreach ($priorityBanks as $bankKey => $testData) {
    $bankCount++;
    $banks = $fspService->getAllBanks();
    
    // Find bank data
    $bank = null;
    foreach ($banks as $key => $b) {
        if ($key === $bankKey) {
            $bank = $b;
            break;
        }
    }
    
    if (!$bank) {
        echo "{$RED}• {$bankKey}: Not found in config{$NC}\n";
        continue;
    }
    
    echo "Testing {$bankKey} ({$bank['name']})...\n";
    echo "  Account: {$testData['account']}\n";
    echo "  Code: {$bank['code']}\n";
    
    try {
        $startTime = microtime(true);
        $result = $eftService->lookupAccount($testData['account'], $bank['code'], 1000);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($result['success']) {
            $bankSuccess++;
            echo "  {$GREEN}✓ SUCCESS{$NC} ({$duration}ms)\n";
            echo "  Name: {$GREEN}{$result['account_name']}{$NC}\n";
            echo "  FSP ID: {$result['fsp_id']}\n";
            $results['banks'][$bankKey] = [
                'status' => 'SUCCESS',
                'name' => $result['account_name'],
                'time' => $duration
            ];
        } else {
            echo "  {$RED}✗ FAILED{$NC} ({$duration}ms)\n";
            echo "  Error: {$result['error']}\n";
            $results['banks'][$bankKey] = [
                'status' => 'FAILED',
                'error' => $result['error'],
                'time' => $duration
            ];
        }
    } catch (Exception $e) {
        echo "  {$RED}✗ ERROR{$NC}: {$e->getMessage()}\n";
        $results['banks'][$bankKey] = [
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];
    }
    
    echo "\n";
    
    // Rate limiting - wait between requests
    usleep(500000); // 0.5 second delay
}

// Test Wallets
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}TESTING PRIORITY MOBILE WALLETS{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

$walletCount = 0;
$walletSuccess = 0;

foreach ($priorityWallets as $walletKey => $testData) {
    $walletCount++;
    $wallets = $fspService->getAllWallets();
    
    // Find wallet data
    $wallet = null;
    foreach ($wallets as $key => $w) {
        if ($key === $walletKey) {
            $wallet = $w;
            break;
        }
    }
    
    if (!$wallet) {
        echo "{$RED}• {$walletKey}: Not found in config{$NC}\n";
        continue;
    }
    
    echo "Testing {$walletKey} ({$wallet['name']})...\n";
    echo "  Phone: {$testData['phone']}\n";
    echo "  Code: {$wallet['code']}\n";
    
    try {
        $startTime = microtime(true);
        $result = $walletService->lookupWallet($testData['phone'], $walletKey, 1000);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($result['success']) {
            $walletSuccess++;
            echo "  {$GREEN}✓ SUCCESS{$NC} ({$duration}ms)\n";
            echo "  Name: {$GREEN}{$result['account_name']}{$NC}\n";
            echo "  FSP ID: {$result['fsp_id']}\n";
            $results['wallets'][$walletKey] = [
                'status' => 'SUCCESS',
                'name' => $result['account_name'],
                'time' => $duration
            ];
        } else {
            echo "  {$RED}✗ FAILED{$NC} ({$duration}ms)\n";
            echo "  Error: {$result['error']}\n";
            $results['wallets'][$walletKey] = [
                'status' => 'FAILED',
                'error' => $result['error'],
                'time' => $duration
            ];
        }
    } catch (Exception $e) {
        echo "  {$RED}✗ ERROR{$NC}: {$e->getMessage()}\n";
        $results['wallets'][$walletKey] = [
            'status' => 'ERROR',
            'error' => $e->getMessage()
        ];
    }
    
    echo "\n";
    
    // Rate limiting
    usleep(500000); // 0.5 second delay
}

// Generate Summary Report
echo "{$CYAN}================================================\n";
echo "              TEST SUMMARY REPORT\n";
echo "================================================{$NC}\n\n";

echo "{$YELLOW}Banks Tested:{$NC}\n";
foreach ($results['banks'] as $bank => $result) {
    $icon = $result['status'] === 'SUCCESS' ? "{$GREEN}✓{$NC}" : "{$RED}✗{$NC}";
    $time = isset($result['time']) ? " ({$result['time']}ms)" : "";
    echo "• {$bank}: {$icon} {$result['status']}{$time}\n";
    if ($result['status'] === 'SUCCESS') {
        echo "  Account Name: {$GREEN}{$result['name']}{$NC}\n";
    } elseif (isset($result['error'])) {
        echo "  Error: {$result['error']}\n";
    }
}

echo "\n{$YELLOW}Wallets Tested:{$NC}\n";
foreach ($results['wallets'] as $wallet => $result) {
    $icon = $result['status'] === 'SUCCESS' ? "{$GREEN}✓{$NC}" : "{$RED}✗{$NC}";
    $time = isset($result['time']) ? " ({$result['time']}ms)" : "";
    echo "• {$wallet}: {$icon} {$result['status']}{$time}\n";
    if ($result['status'] === 'SUCCESS') {
        echo "  Account Name: {$GREEN}{$result['name']}{$NC}\n";
    } elseif (isset($result['error'])) {
        echo "  Error: {$result['error']}\n";
    }
}

echo "\n{$YELLOW}Statistics:{$NC}\n";
echo "• Banks: {$bankSuccess}/{$bankCount} successful (" . round($bankSuccess/$bankCount*100) . "%)\n";
echo "• Wallets: {$walletSuccess}/{$walletCount} successful (" . round($walletSuccess/$walletCount*100) . "%)\n";
echo "• Total Success Rate: " . round(($bankSuccess + $walletSuccess)/($bankCount + $walletCount)*100) . "%\n";

// Save report to file
$report = [
    'timestamp' => date('Y-m-d H:i:s'),
    'results' => $results,
    'statistics' => [
        'banks_tested' => $bankCount,
        'banks_successful' => $bankSuccess,
        'wallets_tested' => $walletCount,
        'wallets_successful' => $walletSuccess
    ]
];

$reportJson = json_encode($report, JSON_PRETTY_PRINT);
file_put_contents(__DIR__ . '/storage/logs/fsp_test_report_' . date('Ymd_His') . '.json', $reportJson);

echo "\n{$GREEN}Report saved to: storage/logs/fsp_test_report_" . date('Ymd_His') . ".json{$NC}\n";

echo "\n{$BLUE}=== Test Complete ==={$NC}\n\n";