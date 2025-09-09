<?php

/**
 * Real Expense Payment Test
 * 
 * This test imitates the exact expense payment process
 * No mock data - uses real services and database
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Expense;
use App\Models\User;
use App\Models\Account;
use App\Models\GeneralLedger;
use App\Models\ExpensePayment;
use App\Models\PaymentMethod;
use App\Services\ExpensePaymentService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=====================================\n";
echo "   REAL EXPENSE PAYMENT TEST        \n";
echo "   No Mock Data - Real Services     \n";
echo "=====================================\n\n";

// Get or create test user
$user = User::where('email', 'expense.tester@saccos.org')->first();
if (!$user) {
    $user = User::create([
        'name' => 'Expense Tester',
        'email' => 'expense.tester@saccos.org',
        'password' => bcrypt('password123')
    ]);
    echo "✓ Created test user\n";
} else {
    echo "✓ Using existing test user\n";
}

// Get or create expense account
$expenseAccount = Account::where('account_code', 'EXP-001')->first();
if (!$expenseAccount) {
    $expenseAccount = Account::create([
        'account_code' => 'EXP-001',
        'account_name' => 'Operating Expenses',
        'account_type' => 'EXPENSE',
        'currency' => 'TZS',
        'balance' => 0,
        'status' => 'ACTIVE'
    ]);
    echo "✓ Created expense account\n";
} else {
    echo "✓ Using existing expense account\n";
}

// Get or create payment account (for cash/bank payments)
$paymentAccount = Account::where('account_code', 'CASH-001')->first();
if (!$paymentAccount) {
    $paymentAccount = Account::create([
        'account_code' => 'CASH-001',
        'account_name' => 'Petty Cash Account',
        'account_type' => 'ASSET',
        'currency' => 'TZS',
        'balance' => 10000000, // 10M TZS
        'status' => 'ACTIVE'
    ]);
    echo "✓ Created payment account with 10M TZS\n";
} else {
    echo "✓ Using existing payment account (Balance: " . number_format($paymentAccount->balance) . " TZS)\n";
}

// Initialize the payment service
$paymentService = app(ExpensePaymentService::class);

echo "\n=====================================\n";
echo "TEST 1: CASH PAYMENT (REAL)\n";
echo "=====================================\n";

try {
    // Create a real expense
    $expense1 = Expense::create([
        'expense_code' => 'EXP-' . date('YmdHis') . '-001',
        'description' => 'Office Supplies Purchase',
        'amount' => 150000, // 150,000 TZS
        'expense_account_id' => $expenseAccount->id,
        'payment_account_id' => $paymentAccount->id,
        'vendor_name' => 'ABC Office Supplies Ltd',
        'vendor_phone' => '255712345678',
        'status' => 'APPROVED', // Must be approved for payment
        'approved_by' => $user->id,
        'approved_at' => now(),
        'created_by' => $user->id,
        'branch_id' => 1,
        'currency' => 'TZS',
        'created_at' => now()
    ]);
    
    echo "✓ Created expense: " . $expense1->expense_code . "\n";
    echo "  Amount: " . number_format($expense1->amount) . " TZS\n";
    echo "  Status: " . $expense1->status . "\n";
    
    // Process cash payment
    $paymentData = [
        'expense_id' => $expense1->id,
        'payment_method' => 'cash',
        'amount' => $expense1->amount,
        'payment_account_id' => $paymentAccount->id,
        'expense_account_id' => $expenseAccount->id,
        'payment_reference' => 'CASH-' . uniqid(),
        'narration' => 'Cash payment for office supplies'
    ];
    
    echo "\nProcessing cash payment...\n";
    $result = $paymentService->processPayment($paymentData);
    
    if ($result['success']) {
        echo "✅ CASH PAYMENT SUCCESSFUL!\n";
        echo "  Transaction ID: " . $result['transaction_id'] . "\n";
        echo "  Payment Ref: " . $result['payment_reference'] . "\n";
        
        // Verify expense status updated
        $expense1->refresh();
        echo "  Expense Status: " . $expense1->status . "\n";
        
        // Check payment record
        $payment = ExpensePayment::where('expense_id', $expense1->id)->first();
        if ($payment) {
            echo "  Payment Record: ✓ Created\n";
            echo "  Payment Amount: " . number_format($payment->amount) . " TZS\n";
        }
    } else {
        echo "❌ Cash payment failed: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test 1 Error: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "TEST 2: BANK TRANSFER (REAL)\n";
echo "=====================================\n";

try {
    // Create expense for bank transfer
    $expense2 = Expense::create([
        'expense_code' => 'EXP-' . date('YmdHis') . '-002',
        'description' => 'Rent Payment - December 2024',
        'amount' => 2500000, // 2.5M TZS
        'expense_account_id' => $expenseAccount->id,
        'payment_account_id' => $paymentAccount->id,
        'vendor_name' => 'Property Management Co',
        'vendor_phone' => '255789012345',
        'vendor_bank_account' => '0110123456789',
        'vendor_bank_name' => 'CRDB Bank',
        'vendor_bank_code' => 'CRDB',
        'status' => 'APPROVED',
        'approved_by' => $user->id,
        'approved_at' => now(),
        'created_by' => $user->id,
        'branch_id' => 1,
        'currency' => 'TZS',
        'created_at' => now()
    ]);
    
    echo "✓ Created expense: " . $expense2->expense_code . "\n";
    echo "  Amount: " . number_format($expense2->amount) . " TZS\n";
    echo "  Vendor Account: " . $expense2->vendor_bank_account . "\n";
    
    // Process bank transfer
    $paymentData = [
        'expense_id' => $expense2->id,
        'payment_method' => 'bank_transfer',
        'amount' => $expense2->amount,
        'payment_account_id' => $paymentAccount->id,
        'expense_account_id' => $expenseAccount->id,
        'to_account' => $expense2->vendor_bank_account,
        'to_bank_code' => $expense2->vendor_bank_code,
        'to_account_name' => $expense2->vendor_name,
        'payment_reference' => 'RENT-DEC-2024',
        'narration' => 'Rent payment for December 2024'
    ];
    
    echo "\nProcessing bank transfer...\n";
    $result = $paymentService->processPayment($paymentData);
    
    if ($result['success']) {
        echo "✅ BANK TRANSFER SUCCESSFUL!\n";
        echo "  Transaction ID: " . $result['transaction_id'] . "\n";
        echo "  External Ref: " . ($result['external_reference'] ?? 'N/A') . "\n";
        echo "  Fee: " . number_format($result['fee'] ?? 0) . " TZS\n";
        
        // Verify expense status
        $expense2->refresh();
        echo "  Expense Status: " . $expense2->status . "\n";
    } else {
        echo "❌ Bank transfer failed: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test 2 Error: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "TEST 3: MOBILE MONEY (REAL)\n";
echo "=====================================\n";

try {
    // Create expense for mobile money
    $expense3 = Expense::create([
        'expense_code' => 'EXP-' . date('YmdHis') . '-003',
        'description' => 'Staff Transport Reimbursement',
        'amount' => 75000, // 75,000 TZS
        'expense_account_id' => $expenseAccount->id,
        'payment_account_id' => $paymentAccount->id,
        'vendor_name' => 'John Doe',
        'vendor_phone' => '255754321098',
        'mobile_provider' => 'VODACOM',
        'status' => 'APPROVED',
        'approved_by' => $user->id,
        'approved_at' => now(),
        'created_by' => $user->id,
        'branch_id' => 1,
        'currency' => 'TZS',
        'created_at' => now()
    ]);
    
    echo "✓ Created expense: " . $expense3->expense_code . "\n";
    echo "  Amount: " . number_format($expense3->amount) . " TZS\n";
    echo "  Recipient Phone: " . $expense3->vendor_phone . "\n";
    
    // Process mobile money payment
    $paymentData = [
        'expense_id' => $expense3->id,
        'payment_method' => 'mobile_money',
        'amount' => $expense3->amount,
        'payment_account_id' => $paymentAccount->id,
        'expense_account_id' => $expenseAccount->id,
        'phone_number' => $expense3->vendor_phone,
        'provider' => $expense3->mobile_provider,
        'recipient_name' => $expense3->vendor_name,
        'payment_reference' => 'MM-' . uniqid(),
        'narration' => 'Transport reimbursement via mobile money'
    ];
    
    echo "\nProcessing mobile money transfer...\n";
    $result = $paymentService->processPayment($paymentData);
    
    if ($result['success'] || (isset($result['status']) && $result['status'] === 'pending')) {
        echo "✅ MOBILE MONEY INITIATED!\n";
        echo "  Transaction ID: " . $result['transaction_id'] . "\n";
        echo "  Status: " . ($result['status'] ?? 'processing') . "\n";
        
        if ($result['status'] === 'pending') {
            echo "  ⏳ Awaiting customer confirmation\n";
        }
        
        // Check expense status
        $expense3->refresh();
        echo "  Expense Status: " . $expense3->status . "\n";
    } else {
        echo "❌ Mobile money failed: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test 3 Error: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "TEST 4: BATCH PAYMENT (REAL)\n";
echo "=====================================\n";

try {
    // Create multiple expenses for batch payment
    $batchExpenses = [];
    
    for ($i = 1; $i <= 3; $i++) {
        $expense = Expense::create([
            'expense_code' => 'EXP-' . date('YmdHis') . '-B0' . $i,
            'description' => 'Batch Payment Item ' . $i,
            'amount' => 50000 * $i, // 50K, 100K, 150K
            'expense_account_id' => $expenseAccount->id,
            'payment_account_id' => $paymentAccount->id,
            'vendor_name' => 'Vendor ' . $i,
            'vendor_phone' => '25575432109' . $i,
            'status' => 'APPROVED',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'created_by' => $user->id,
            'branch_id' => 1,
            'currency' => 'TZS',
            'created_at' => now()
        ]);
        
        $batchExpenses[] = $expense;
        echo "✓ Created expense: " . $expense->expense_code . " - " . number_format($expense->amount) . " TZS\n";
    }
    
    $totalAmount = array_sum(array_column($batchExpenses, 'amount'));
    echo "\nTotal batch amount: " . number_format($totalAmount) . " TZS\n";
    
    // Process batch payment
    echo "\nProcessing batch payment...\n";
    
    $successCount = 0;
    $failCount = 0;
    
    foreach ($batchExpenses as $expense) {
        $paymentData = [
            'expense_id' => $expense->id,
            'payment_method' => 'cash', // Using cash for simplicity
            'amount' => $expense->amount,
            'payment_account_id' => $paymentAccount->id,
            'expense_account_id' => $expenseAccount->id,
            'payment_reference' => 'BATCH-' . uniqid(),
            'narration' => 'Batch payment for ' . $expense->expense_code
        ];
        
        $result = $paymentService->processPayment($paymentData);
        
        if ($result['success']) {
            $successCount++;
            echo "  ✓ " . $expense->expense_code . " - PAID\n";
        } else {
            $failCount++;
            echo "  ✗ " . $expense->expense_code . " - FAILED: " . $result['message'] . "\n";
        }
    }
    
    echo "\n✅ BATCH PAYMENT COMPLETE!\n";
    echo "  Successful: " . $successCount . "/" . count($batchExpenses) . "\n";
    echo "  Failed: " . $failCount . "/" . count($batchExpenses) . "\n";
    
} catch (Exception $e) {
    echo "❌ Test 4 Error: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "TEST 5: VALIDATION CHECKS (REAL)\n";
echo "=====================================\n";

try {
    // Test 5.1: Try to pay unapproved expense
    echo "\n5.1 Testing unapproved expense payment:\n";
    
    $unapprovedExpense = Expense::create([
        'expense_code' => 'EXP-' . date('YmdHis') . '-U01',
        'description' => 'Unapproved Expense',
        'amount' => 100000,
        'expense_account_id' => $expenseAccount->id,
        'payment_account_id' => $paymentAccount->id,
        'vendor_name' => 'Test Vendor',
        'status' => 'PENDING', // Not approved
        'created_by' => $user->id,
        'branch_id' => 1,
        'currency' => 'TZS'
    ]);
    
    $paymentData = [
        'expense_id' => $unapprovedExpense->id,
        'payment_method' => 'cash',
        'amount' => $unapprovedExpense->amount,
        'payment_account_id' => $paymentAccount->id,
        'expense_account_id' => $expenseAccount->id
    ];
    
    $result = $paymentService->processPayment($paymentData);
    
    if (!$result['success']) {
        echo "✅ Correctly rejected: " . $result['message'] . "\n";
    } else {
        echo "❌ ERROR: Should have rejected unapproved expense!\n";
    }
    
    // Test 5.2: Try to pay already paid expense
    echo "\n5.2 Testing double payment prevention:\n";
    
    if (isset($expense1)) {
        $expense1->status = 'PAID'; // Mark as already paid
        $expense1->save();
        
        $paymentData = [
            'expense_id' => $expense1->id,
            'payment_method' => 'cash',
            'amount' => $expense1->amount,
            'payment_account_id' => $paymentAccount->id,
            'expense_account_id' => $expenseAccount->id
        ];
        
        $result = $paymentService->processPayment($paymentData);
        
        if (!$result['success']) {
            echo "✅ Correctly rejected: " . $result['message'] . "\n";
        } else {
            echo "❌ ERROR: Should have rejected double payment!\n";
        }
    }
    
    // Test 5.3: Test mobile money limit
    echo "\n5.3 Testing mobile money limit (20M TZS):\n";
    
    $largeExpense = Expense::create([
        'expense_code' => 'EXP-' . date('YmdHis') . '-L01',
        'description' => 'Large Amount Expense',
        'amount' => 25000000, // 25M TZS - exceeds limit
        'expense_account_id' => $expenseAccount->id,
        'payment_account_id' => $paymentAccount->id,
        'vendor_name' => 'Big Vendor',
        'vendor_phone' => '255789123456',
        'mobile_provider' => 'VODACOM',
        'status' => 'APPROVED',
        'approved_by' => $user->id,
        'approved_at' => now(),
        'created_by' => $user->id,
        'branch_id' => 1,
        'currency' => 'TZS'
    ]);
    
    $paymentData = [
        'expense_id' => $largeExpense->id,
        'payment_method' => 'mobile_money',
        'amount' => $largeExpense->amount,
        'payment_account_id' => $paymentAccount->id,
        'expense_account_id' => $expenseAccount->id,
        'phone_number' => $largeExpense->vendor_phone,
        'provider' => $largeExpense->mobile_provider,
        'recipient_name' => $largeExpense->vendor_name
    ];
    
    $result = $paymentService->processPayment($paymentData);
    
    if (!$result['success'] && strpos($result['message'], 'exceeds') !== false) {
        echo "✅ Correctly rejected: " . $result['message'] . "\n";
    } else {
        echo "❌ ERROR: Should have rejected amount exceeding mobile money limit!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Validation Test Error: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "LOG CHECK\n";
echo "=====================================\n";

// Check if logs were created
$logFile = storage_path('logs/payments/payments-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    echo "✓ Payment log file exists: " . basename($logFile) . "\n";
    
    // Get last 5 log entries
    $logContent = file_get_contents($logFile);
    $lines = explode("\n", $logContent);
    $recentLines = array_slice(array_filter($lines), -5);
    
    if (count($recentLines) > 0) {
        echo "\nRecent log entries:\n";
        foreach ($recentLines as $line) {
            // Show only the message part for readability
            if (preg_match('/\[([\d\-\s:]+)\].*?: (.+)/', $line, $matches)) {
                echo "  [" . date('H:i:s', strtotime($matches[1])) . "] " . substr($matches[2], 0, 80) . "...\n";
            }
        }
    }
} else {
    echo "⚠ Log file not found\n";
}

echo "\n=====================================\n";
echo "DATABASE VERIFICATION\n";
echo "=====================================\n";

// Count payment records created
$paymentCount = ExpensePayment::whereDate('created_at', today())->count();
echo "✓ Payment records created today: " . $paymentCount . "\n";

// Check general ledger entries
$glEntries = GeneralLedger::whereDate('created_at', today())
    ->where('reference', 'like', '%EXP-%')
    ->count();
echo "✓ General ledger entries: " . $glEntries . "\n";

// Check account balances
$paymentAccount->refresh();
echo "✓ Payment account balance: " . number_format($paymentAccount->balance) . " TZS\n";

$expenseAccount->refresh();
echo "✓ Expense account balance: " . number_format($expenseAccount->balance) . " TZS\n";

echo "\n=====================================\n";
echo "TEST SUMMARY\n";
echo "=====================================\n";
echo "All tests completed using REAL services.\n";
echo "No mock data was used.\n";
echo "Check the payment logs for API responses.\n";
echo "\n✅ TEST SUITE COMPLETE!\n\n";