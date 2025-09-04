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
echo "     FSP WORKING STATUS REPORT\n";
echo "================================================{$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$fspService = app(FSPService::class);

// From the test results
$testResults = [
    'banks' => [
        'CRDB' => ['status' => 'WORKING', 'account' => '12334567789', 'name' => 'SAMWEL MARWA JUMA', 'time' => 860],
        'NMB' => ['status' => 'WORKING', 'account' => '20110033445', 'name' => 'MMMM WWW', 'time' => 743],
        'NBC' => ['status' => 'WORKING', 'account' => '011201318462', 'name' => 'BON JON JONES', 'time' => 25425],
        'STANBIC' => ['status' => 'FAILED', 'error' => 'Failed to retrieve beneficiary information', 'time' => 25344]
    ],
    'wallets' => [
        'MPESA' => ['status' => 'WORKING', 'phone' => '0748045601', 'name' => 'TEST Lab', 'time' => 1870],
        'TIGOPESA' => ['status' => 'FAILED', 'error' => 'BOT Service timeout', 'time' => 30000]
    ]
];

// Generate comprehensive report
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}EXECUTIVE SUMMARY{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

$stats = $fspService->getStatistics();
echo "Total FSPs Available: {$GREEN}{$stats['total_fsps']}{$NC}\n";
echo "• Banks: {$stats['total_banks']}\n";
echo "• Mobile Wallets: {$stats['total_wallets']}\n\n";

echo "{$YELLOW}Testing Status:{$NC}\n";
echo "• Banks Tested: 4\n";
echo "• Banks Working: {$GREEN}3{$NC} (CRDB, NMB, NBC)\n";
echo "• Banks Failed: {$RED}1{$NC} (STANBIC)\n";
echo "• Wallets Tested: 2\n";
echo "• Wallets Working: {$GREEN}1{$NC} (M-Pesa)\n";
echo "• Wallets Failed: {$RED}1{$NC} (TigoPesa)\n\n";

echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}CONFIRMED WORKING FSPS{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

echo "{$GREEN}Banks:{$NC}\n";
foreach ($testResults['banks'] as $bank => $result) {
    if ($result['status'] === 'WORKING') {
        $time = isset($result['time']) ? " (~{$result['time']}ms)" : "";
        echo "✓ {$GREEN}{$bank}{$NC} - Account: {$result['account']} - Name: \"{$result['name']}\"{$time}\n";
    }
}

echo "\n{$GREEN}Mobile Wallets:{$NC}\n";
foreach ($testResults['wallets'] as $wallet => $result) {
    if ($result['status'] === 'WORKING') {
        $time = isset($result['time']) ? " (~{$result['time']}ms)" : "";
        echo "✓ {$GREEN}{$wallet}{$NC} - Phone: {$result['phone']} - Name: \"{$result['name']}\"{$time}\n";
    }
}

echo "\n{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}FAILED FSPS{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

foreach ($testResults['banks'] as $bank => $result) {
    if ($result['status'] === 'FAILED') {
        echo "✗ {$RED}{$bank}{$NC} - {$result['error']}\n";
    }
}
foreach ($testResults['wallets'] as $wallet => $result) {
    if ($result['status'] === 'FAILED') {
        echo "✗ {$RED}{$wallet}{$NC} - {$result['error']}\n";
    }
}

echo "\n{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}RECOMMENDATIONS{$NC}\n";
echo "{$BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";

echo "1. {$GREEN}Ready for Production:{$NC}\n";
echo "   • CRDB Bank transfers (B2B)\n";
echo "   • NMB Bank transfers (B2B)\n";
echo "   • NBC internal transfers (IFT)\n";
echo "   • M-Pesa mobile transfers (B2W)\n\n";

echo "2. {$YELLOW}Requires Investigation:{$NC}\n";
echo "   • STANBIC - FSP retrieval failure\n";
echo "   • TigoPesa - BOT timeout issues\n\n";

echo "3. {$CYAN}Next Steps:{$NC}\n";
echo "   • Test additional banks during business hours\n";
echo "   • Contact NBC support about timeout issues\n";
echo "   • Implement retry logic for failed lookups\n";

// Save detailed report
$report = [
    'generated_at' => date('Y-m-d H:i:s'),
    'environment' => 'NBC UAT',
    'endpoint' => 'https://22.32.245.67:443',
    'total_fsps' => 36,
    'test_results' => $testResults,
    'recommendations' => [
        'production_ready' => ['CRDB', 'NMB', 'NBC', 'MPESA'],
        'needs_investigation' => ['STANBIC', 'TIGOPESA'],
        'untested' => 'Remaining 30 FSPs'
    ],
    'api_performance' => [
        'fast_response' => 'CRDB (860ms), NMB (743ms)',
        'slow_response' => 'NBC (25s), STANBIC (25s)',
        'timeout' => 'TigoPesa (30s)'
    ]
];

$reportPath = __DIR__ . '/storage/logs/fsp_status_report_' . date('Ymd_His') . '.json';
file_put_contents($reportPath, json_encode($report, JSON_PRETTY_PRINT));

echo "\n{$GREEN}Full report saved to:{$NC}\n";
echo "storage/logs/fsp_status_report_" . date('Ymd_His') . ".json\n";

echo "\n{$BLUE}=== Report Complete ==={$NC}\n\n";