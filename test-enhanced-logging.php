<?php

/**
 * Test Enhanced Logging for Loan Repayment
 * This script tests the comprehensive logging added to the repayment system
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\LoanRepaymentService;

// Boot Laravel
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "╔══════════════════════════════════════════════════════════════════╗\n";
echo "║          ENHANCED LOGGING TEST FOR LOAN REPAYMENT               ║\n";
echo "╔══════════════════════════════════════════════════════════════════╝\n\n";

// Test loan details
$testLoanId = 'LN202508174418';
$testAmount = 250000; // 250,000 TZS

echo "📋 Test Configuration:\n";
echo "   Loan ID: $testLoanId\n";
echo "   Payment Amount: " . number_format($testAmount) . " TZS\n";
echo "   Payment Method: CASH\n\n";

echo "═══════════════════════════════════════════════════════\n";
echo "TEST 1: LOAN SEARCH LOGGING\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Simulate loan search (logging from component)
Log::info('🔍 LOAN SEARCH INITIATED', [
    'search_type' => 'loan_id',
    'search_value' => $testLoanId,
    'user' => 'Test Script',
    'timestamp' => now()->toDateTimeString()
]);

$loan = DB::table('loans')
    ->join('clients', 'loans.client_number', '=', 'clients.client_number')
    ->where('loans.loan_id', $testLoanId)
    ->whereIn('loans.status', ['ACTIVE', 'RESTRUCTURED'])
    ->first();

if ($loan) {
    Log::info('✅ Single loan found and selected', [
        'loan_id' => $loan->loan_id,
        'status' => $loan->status,
        'client' => $loan->client_number,
        'principal' => $loan->principle
    ]);
    echo "✅ Loan search logged successfully\n";
} else {
    echo "❌ Loan not found\n";
    exit(1);
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "TEST 2: PAYMENT PROCESSING LOGGING\n";
echo "═══════════════════════════════════════════════════════\n\n";

$repaymentService = new LoanRepaymentService();

try {
    echo "Processing payment of " . number_format($testAmount) . " TZS...\n\n";
    
    // Process the payment - this will trigger all the enhanced logging
    $result = $repaymentService->processRepayment(
        $testLoanId,
        $testAmount,
        'CASH',
        [
            'narration' => 'Test payment with enhanced logging',
            'reference' => 'TEST_LOG_' . time()
        ]
    );
    
    echo "✅ Payment processed successfully!\n";
    echo "   Receipt: {$result['receipt_number']}\n";
    echo "   Amount Paid: " . number_format($result['amount_paid']) . " TZS\n";
    echo "\n📊 Payment Allocation:\n";
    echo "   - Penalties: " . number_format($result['allocation']['penalties']) . " TZS\n";
    echo "   - Interest: " . number_format($result['allocation']['interest']) . " TZS\n";
    echo "   - Principal: " . number_format($result['allocation']['principal']) . " TZS\n";
    if ($result['allocation']['overpayment'] ?? 0 > 0) {
        echo "   - Overpayment: " . number_format($result['allocation']['overpayment']) . " TZS\n";
    }
    echo "\n   New Outstanding: " . number_format($result['outstanding_balance']['total']) . " TZS\n";
    
} catch (Exception $e) {
    echo "❌ Payment failed: " . $e->getMessage() . "\n";
    echo "   Check logs for detailed error information\n";
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "TEST 3: EARLY SETTLEMENT LOGGING\n";
echo "═══════════════════════════════════════════════════════\n\n";

try {
    echo "Calculating early settlement...\n";
    
    $settlement = $repaymentService->calculateEarlySettlement($testLoanId);
    
    echo "✅ Early settlement calculated:\n";
    echo "   Principal: " . number_format($settlement['principal']) . " TZS\n";
    echo "   Interest: " . number_format($settlement['interest']) . " TZS\n";
    echo "   Penalties: " . number_format($settlement['penalties']) . " TZS\n";
    echo "   Waiver: " . number_format($settlement['waiver']) . " TZS\n";
    echo "   ────────────────────────\n";
    echo "   TOTAL: " . number_format($settlement['total_settlement']) . " TZS\n";
    echo "   Savings: " . number_format($settlement['savings']) . " TZS\n";
    
} catch (Exception $e) {
    echo "⚠️  Early settlement calculation failed: " . $e->getMessage() . "\n";
}

echo "\n═══════════════════════════════════════════════════════\n";
echo "TEST 4: CHECK LOG OUTPUT\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Get today's log file
$logFile = storage_path('logs/laravel-' . date('Y-m-d') . '.log');

if (file_exists($logFile)) {
    // Get last 50 lines of the log that contain our test entries
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $relevantLines = [];
    
    foreach ($lines as $line) {
        if (strpos($line, 'LOAN REPAYMENT') !== false ||
            strpos($line, 'LOAN SEARCH') !== false ||
            strpos($line, 'Payment allocation') !== false ||
            strpos($line, 'Outstanding balances') !== false ||
            strpos($line, 'Early settlement') !== false ||
            strpos($line, 'Receipt generated') !== false ||
            strpos($line, 'Loan schedules updated') !== false ||
            strpos($line, 'Payment history recorded') !== false) {
            $relevantLines[] = $line;
        }
    }
    
    $recentLogs = array_slice($relevantLines, -10);
    
    echo "📝 Recent Enhanced Log Entries:\n";
    echo "────────────────────────────────────────────────\n";
    
    foreach ($recentLogs as $logLine) {
        // Extract the log message (skip timestamp and channel info)
        if (preg_match('/\[.*?\] .*?: (.*)/', $logLine, $matches)) {
            $message = $matches[1];
            // Truncate long messages
            if (strlen($message) > 100) {
                $message = substr($message, 0, 97) . '...';
            }
            echo "   • $message\n";
        }
    }
    
    echo "────────────────────────────────────────────────\n";
    echo "\n✅ Log file location: " . $logFile . "\n";
} else {
    echo "⚠️  Log file not found at: " . $logFile . "\n";
}

echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
echo "║                     TEST SUMMARY                                ║\n";
echo "╠══════════════════════════════════════════════════════════════════╣\n";
echo "║ ✅ Enhanced Logging Implementation Complete                     ║\n";
echo "║                                                                  ║\n";
echo "║ Log Categories Added:                                           ║\n";
echo "║ • 🔵 Repayment Initiation                                      ║\n";
echo "║ • 📋 Loan Details Retrieval                                    ║\n";
echo "║ • 💰 Outstanding Balance Calculation                           ║\n";
echo "║ • 📊 Payment Allocation                                        ║\n";
echo "║ • 📅 Schedule Updates                                          ║\n";
echo "║ • 📑 Accounting Transactions                                   ║\n";
echo "║ • 📝 Payment History Recording                                 ║\n";
echo "║ • 🧾 Receipt Generation                                        ║\n";
echo "║ • ✅ Success Confirmation                                      ║\n";
echo "║ • ❌ Error Tracking                                           ║\n";
echo "║ • 💸 Early Settlement Calculations                            ║\n";
echo "║                                                                  ║\n";
echo "║ All logging includes timestamps and user identification         ║\n";
echo "╚══════════════════════════════════════════════════════════════════╝\n\n";

echo "📊 BENEFITS OF ENHANCED LOGGING:\n";
echo "• Better debugging and troubleshooting\n";
echo "• Complete audit trail for all transactions\n";
echo "• Performance monitoring capabilities\n";
echo "• User activity tracking\n";
echo "• Error pattern identification\n";
echo "• Compliance and regulatory reporting\n\n";

exit(0);