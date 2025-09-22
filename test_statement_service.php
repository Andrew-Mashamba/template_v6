<?php

/**
 * NBC Statement Service (PVAS) Test Script
 * 
 * This script tests all three statement service endpoints:
 * - SC990001: Account Balance
 * - SC990002: Transaction Summary
 * - SC990003: Account Statement
 * 
 * Test Accounts (from provided data):
 * - 011191000035 (CBN MICROFINANCE)
 * - 011201318462 (BON JON JONES)
 * - 074206000029 (BON JON JONES II)
 */

require_once __DIR__ . '/bootstrap/app.php';

use App\Services\Statement\StatementService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// Enable detailed logging
Log::info("===== NBC STATEMENT SERVICE TEST STARTED =====");

// Initialize service
$service = new StatementService();

// Test accounts
$testAccounts = [
    '011191000035' => 'CBN MICROFINANCE',
    '011201318462' => 'BON JON JONES',
    '074206000029' => 'BON JON JONES II'
];

// Test date
$statementDate = Carbon::now()->subDays(1)->format('Y-m-d'); // Yesterday

// Color codes for terminal output
$colors = [
    'header' => "\033[1;34m",   // Bold Blue
    'success' => "\033[0;32m",  // Green
    'error' => "\033[0;31m",    // Red
    'warning' => "\033[0;33m",  // Yellow
    'info' => "\033[0;36m",     // Cyan
    'reset' => "\033[0m"         // Reset
];

function printHeader($text, $colors) {
    echo "\n" . $colors['header'] . str_repeat("=", 80) . $colors['reset'] . "\n";
    echo $colors['header'] . $text . $colors['reset'] . "\n";
    echo $colors['header'] . str_repeat("=", 80) . $colors['reset'] . "\n";
}

function printSuccess($text, $colors) {
    echo $colors['success'] . "✅ " . $text . $colors['reset'] . "\n";
}

function printError($text, $colors) {
    echo $colors['error'] . "❌ " . $text . $colors['reset'] . "\n";
}

function printInfo($text, $colors) {
    echo $colors['info'] . "ℹ️  " . $text . $colors['reset'] . "\n";
}

function formatCurrency($amount) {
    return 'TZS ' . number_format($amount, 2);
}

// Test results storage
$testResults = [];

// =============================================================================
// TEST 1: Account Balance (SC990001)
// =============================================================================
printHeader("TEST 1: Account Balance Service (SC990001)", $colors);

foreach ($testAccounts as $accountNumber => $accountName) {
    echo "\n" . $colors['info'] . "Testing account: $accountNumber ($accountName)" . $colors['reset'] . "\n";
    
    $startTime = microtime(true);
    $partnerRef = 'BAL' . date('ymdHis') . rand(100, 999);
    
    try {
        // Call the service
        $response = $service->getAccountBalance($accountNumber, $statementDate, $partnerRef);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        if ($response['success']) {
            printSuccess("Balance retrieved successfully", $colors);
            
            // Format and display balance data
            if (!empty($response['data'])) {
                $balanceData = $service->formatBalanceData($response['data']);
                
                echo "\n" . $colors['info'] . "Balance Information:" . $colors['reset'] . "\n";
                echo "  • Currency: " . $balanceData['currency'] . "\n";
                echo "  • Opening Balance: " . $balanceData['opening_balance'] . "\n";
                echo "  • Closing Balance: " . $balanceData['closing_balance'] . "\n";
                echo "  • Total Transactions: " . $balanceData['total_transactions'] . "\n";
                echo "  • Total Debits: " . $balanceData['total_debits'] . " (" . $balanceData['debit_count'] . " transactions)\n";
                echo "  • Total Credits: " . $balanceData['total_credits'] . " (" . $balanceData['credit_count'] . " transactions)\n";
            }
            
            echo $colors['info'] . "  • Partner Ref: " . $response['partner_ref'] . $colors['reset'] . "\n";
            echo $colors['info'] . "  • Bank Ref: " . ($response['bank_ref'] ?? 'N/A') . $colors['reset'] . "\n";
            echo $colors['info'] . "  • Response Time: " . $duration . " ms" . $colors['reset'] . "\n";
            
            $testResults[] = [
                'test' => 'Account Balance',
                'account' => $accountNumber,
                'success' => true,
                'duration' => $duration
            ];
        } else {
            printError("Failed: " . $response['error'], $colors);
            $testResults[] = [
                'test' => 'Account Balance',
                'account' => $accountNumber,
                'success' => false,
                'error' => $response['error'],
                'duration' => $duration
            ];
        }
        
    } catch (Exception $e) {
        printError("Exception: " . $e->getMessage(), $colors);
        $testResults[] = [
            'test' => 'Account Balance',
            'account' => $accountNumber,
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
    
    // Small delay between requests
    sleep(1);
}

// =============================================================================
// TEST 2: Transaction Summary (SC990002)
// =============================================================================
printHeader("TEST 2: Transaction Summary Service (SC990002)", $colors);

$testAccount = '011191000035'; // Test with SACCOS account
$partnerRef = 'SUM' . date('ymdHis') . rand(100, 999);

echo "\n" . $colors['info'] . "Testing account: $testAccount (CBN MICROFINANCE)" . $colors['reset'] . "\n";

$startTime = microtime(true);

try {
    $response = $service->getTransactionSummary($testAccount, $statementDate, $partnerRef);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($response['success']) {
        printSuccess("Transaction summary retrieved successfully", $colors);
        
        if (!empty($response['data']) && is_array($response['data'])) {
            $summaryData = $response['data'][0] ?? [];
            
            if (isset($summaryData['balancesInfo']['item'])) {
                $balanceInfo = $summaryData['balancesInfo']['item'];
                
                echo "\n" . $colors['info'] . "Account Information:" . $colors['reset'] . "\n";
                echo "  • Account Title: " . ($balanceInfo['accountTitle'] ?? 'N/A') . "\n";
                echo "  • Branch: " . ($balanceInfo['branchName'] ?? 'N/A') . " (Code: " . ($balanceInfo['branchCode'] ?? 'N/A') . ")\n";
                echo "  • Product: " . ($balanceInfo['productName'] ?? 'N/A') . "\n";
                echo "  • Status: " . ($balanceInfo['currentStatusDescription'] ?? 'N/A') . "\n";
                echo "  • Currency: " . ($balanceInfo['currencyShortName'] ?? 'N/A') . "\n";
                echo "  • Balance: " . formatCurrency($balanceInfo['balanceBook'] ?? 0) . "\n";
            }
            
            if (isset($summaryData['transactionsHistory'])) {
                $history = $summaryData['transactionsHistory'];
                
                echo "\n" . $colors['info'] . "Transaction History Summary:" . $colors['reset'] . "\n";
                echo "  • Available Balance: " . formatCurrency($history['balAvail'] ?? 0) . "\n";
                echo "  • Total Credits: " . formatCurrency($history['amtTotCr'] ?? 0) . "\n";
                echo "  • Total Debits: " . formatCurrency($history['amtTotDr'] ?? 0) . "\n";
                echo "  • Last Credit Date: " . ($history['datLastCr'] ?? 'N/A') . "\n";
                echo "  • Last Debit Date: " . ($history['datLastDr'] ?? 'N/A') . "\n";
                echo "  • Last Statement Date: " . ($history['datLastStmt'] ?? 'N/A') . "\n";
            }
        }
        
        echo "\n" . $colors['info'] . "  • Partner Ref: " . $response['partner_ref'] . $colors['reset'] . "\n";
        echo $colors['info'] . "  • Bank Ref: " . ($response['bank_ref'] ?? 'N/A') . $colors['reset'] . "\n";
        echo $colors['info'] . "  • Response Time: " . $duration . " ms" . $colors['reset'] . "\n";
        
        $testResults[] = [
            'test' => 'Transaction Summary',
            'account' => $testAccount,
            'success' => true,
            'duration' => $duration
        ];
    } else {
        printError("Failed: " . $response['error'], $colors);
        $testResults[] = [
            'test' => 'Transaction Summary',
            'account' => $testAccount,
            'success' => false,
            'error' => $response['error'],
            'duration' => $duration
        ];
    }
    
} catch (Exception $e) {
    printError("Exception: " . $e->getMessage(), $colors);
    $testResults[] = [
        'test' => 'Transaction Summary',
        'account' => $testAccount,
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// =============================================================================
// TEST 3: Account Statement (SC990003)
// =============================================================================
printHeader("TEST 3: Account Statement Service (SC990003)", $colors);

$testAccount = '011191000035'; // Test with SACCOS account
$partnerRef = 'STMT' . date('ymdHis') . rand(100, 999);

echo "\n" . $colors['info'] . "Testing account: $testAccount (CBN MICROFINANCE)" . $colors['reset'] . "\n";
echo $colors['info'] . "Statement Date: $statementDate" . $colors['reset'] . "\n";

$startTime = microtime(true);

try {
    $response = $service->getAccountStatement($testAccount, $statementDate, $partnerRef);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    if ($response['success']) {
        printSuccess("Statement retrieved successfully", $colors);
        
        $transactions = $response['transactions'] ?? [];
        $transactionCount = count($transactions);
        
        echo "\n" . $colors['info'] . "Statement Details:" . $colors['reset'] . "\n";
        echo "  • Total Transactions: " . $transactionCount . "\n";
        
        if ($transactionCount > 0) {
            // Show first 5 transactions as sample
            echo "\n" . $colors['info'] . "Sample Transactions (showing first 5):" . $colors['reset'] . "\n";
            echo str_repeat("-", 80) . "\n";
            echo sprintf("%-12s %-8s %-30s %15s %15s\n", "Date", "Type", "Description", "Amount", "Balance");
            echo str_repeat("-", 80) . "\n";
            
            $sampleTransactions = array_slice($transactions, 0, 5);
            foreach ($sampleTransactions as $transaction) {
                $formatted = $service->formatTransaction($transaction);
                echo sprintf("%-12s %-8s %-30s %15s %15s\n",
                    $formatted['date'],
                    $formatted['type'],
                    substr($formatted['description'], 0, 30),
                    $formatted['amount'],
                    $formatted['balance']
                );
            }
            
            if ($transactionCount > 5) {
                echo "... and " . ($transactionCount - 5) . " more transactions\n";
            }
        } else {
            echo "  • No transactions found for this date\n";
        }
        
        echo "\n" . $colors['info'] . "  • Partner Ref: " . $response['partner_ref'] . $colors['reset'] . "\n";
        echo $colors['info'] . "  • Bank Ref: " . ($response['bank_ref'] ?? 'N/A') . $colors['reset'] . "\n";
        echo $colors['info'] . "  • Response Time: " . $duration . " ms" . $colors['reset'] . "\n";
        
        $testResults[] = [
            'test' => 'Account Statement',
            'account' => $testAccount,
            'success' => true,
            'transaction_count' => $transactionCount,
            'duration' => $duration
        ];
    } else {
        printError("Failed: " . $response['error'], $colors);
        $testResults[] = [
            'test' => 'Account Statement',
            'account' => $testAccount,
            'success' => false,
            'error' => $response['error'],
            'duration' => $duration
        ];
    }
    
} catch (Exception $e) {
    printError("Exception: " . $e->getMessage(), $colors);
    $testResults[] = [
        'test' => 'Account Statement',
        'account' => $testAccount,
        'success' => false,
        'error' => $e->getMessage()
    ];
}

// =============================================================================
// TEST 4: Response Code Descriptions
// =============================================================================
printHeader("TEST 4: Response Code Verification", $colors);

$responseCodes = [600, 601, 602, 613, 615, 699];

echo "\n" . $colors['info'] . "NBC Response Codes:" . $colors['reset'] . "\n";
foreach ($responseCodes as $code) {
    $description = $service->getResponseCodeDescription($code);
    $symbol = $code === 600 ? '✅' : '⚠️';
    echo "  $symbol $code: $description\n";
}

// =============================================================================
// TEST SUMMARY
// =============================================================================
printHeader("TEST SUMMARY REPORT", $colors);

$totalTests = count($testResults);
$successfulTests = array_filter($testResults, function($r) { return $r['success']; });
$failedTests = array_filter($testResults, function($r) { return !$r['success']; });

echo "\n" . $colors['info'] . "Overall Results:" . $colors['reset'] . "\n";
echo "  • Total Tests: $totalTests\n";
echo "  • " . $colors['success'] . "Successful: " . count($successfulTests) . $colors['reset'] . "\n";
echo "  • " . $colors['error'] . "Failed: " . count($failedTests) . $colors['reset'] . "\n";

if (count($successfulTests) > 0) {
    $avgDuration = array_sum(array_column($successfulTests, 'duration')) / count($successfulTests);
    echo "  • Average Response Time: " . round($avgDuration, 2) . " ms\n";
}

echo "\n" . $colors['info'] . "Detailed Results:" . $colors['reset'] . "\n";
echo str_repeat("-", 80) . "\n";
echo sprintf("%-25s %-15s %-10s %15s\n", "Test", "Account", "Status", "Duration (ms)");
echo str_repeat("-", 80) . "\n";

foreach ($testResults as $result) {
    $status = $result['success'] ? 
        $colors['success'] . "✅ PASS" . $colors['reset'] : 
        $colors['error'] . "❌ FAIL" . $colors['reset'];
    
    echo sprintf("%-25s %-15s %-20s %15s\n",
        $result['test'],
        substr($result['account'], -6),
        $status,
        isset($result['duration']) ? round($result['duration'], 2) : 'N/A'
    );
    
    if (!$result['success'] && isset($result['error'])) {
        echo $colors['error'] . "    Error: " . $result['error'] . $colors['reset'] . "\n";
    }
}

echo str_repeat("-", 80) . "\n";

// Save detailed report
$reportFile = __DIR__ . '/storage/logs/statement_service_test_' . date('Ymd_His') . '.json';
$reportData = [
    'test_date' => date('Y-m-d H:i:s'),
    'service' => 'NBC Statement Service (PVAS)',
    'endpoints_tested' => [
        'SC990001' => 'Account Balance',
        'SC990002' => 'Transaction Summary',
        'SC990003' => 'Account Statement'
    ],
    'summary' => [
        'total' => $totalTests,
        'passed' => count($successfulTests),
        'failed' => count($failedTests)
    ],
    'test_accounts' => $testAccounts,
    'results' => $testResults
];

@mkdir(dirname($reportFile), 0755, true);
file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT));

echo "\n" . $colors['info'] . "📄 Detailed report saved to: $reportFile" . $colors['reset'] . "\n";

printHeader("NBC STATEMENT SERVICE TEST COMPLETED", $colors);

Log::info("===== NBC STATEMENT SERVICE TEST COMPLETED =====", [
    'summary' => [
        'total' => $totalTests,
        'passed' => count($successfulTests),
        'failed' => count($failedTests)
    ]
]);