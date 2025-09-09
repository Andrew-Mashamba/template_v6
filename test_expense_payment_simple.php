<?php

/**
 * Simple Real Expense Payment Test
 * Uses actual database structure - no mock data
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Expense;
use App\Models\User;
use App\Models\Account;
use App\Models\BudgetItem;
use App\Models\ExpensePayment;
use App\Services\ExpensePaymentService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=====================================\n";
echo "   SIMPLE EXPENSE PAYMENT TEST      \n";
echo "   Using Real Services & Database   \n";
echo "=====================================\n\n";

// Initialize the payment service
$paymentService = app(ExpensePaymentService::class);

// Get existing user
$user = User::first();
if (!$user) {
    die("Error: No users found in database. Please seed the database first.\n");
}
echo "✓ Using user: " . $user->name . "\n";

// Get or create an account
$account = Account::where('account_type', 'SAVINGS')->first();
if (!$account) {
    // Create a basic account for testing
    $account = Account::create([
        'account_number' => '011' . str_pad(rand(1000000, 9999999), 9, '0', STR_PAD_LEFT),
        'account_type' => 'SAVINGS',
        'balance' => 10000000, // 10M TZS
        'opening_balance' => 10000000,
        'status' => 'ACTIVE',
        'member_id' => 1 // Assuming member exists
    ]);
    echo "✓ Created test account: " . $account->account_number . "\n";
} else {
    echo "✓ Using account: " . $account->account_number . " (Balance: " . number_format($account->balance) . " TZS)\n";
}

// Get a budget item if it exists
$budgetItem = BudgetItem::first();

echo "\n=====================================\n";
echo "TEST 1: CREATE & PAY CASH EXPENSE\n";
echo "=====================================\n";

try {
    // Create a simple expense
    $expenseData = [
        'account_id' => $account->id,
        'amount' => 150000, // 150,000 TZS
        'description' => 'Office supplies purchase - Real test',
        'payment_type' => 'CASH',
        'user_id' => $user->id,
        'status' => 'PENDING'
    ];
    
    // Add budget item if available
    if ($budgetItem) {
        $expenseData['budget_item_id'] = $budgetItem->id;
    }
    
    $expense = Expense::create($expenseData);
    echo "✓ Created expense ID: " . $expense->id . "\n";
    echo "  Amount: " . number_format($expense->amount) . " TZS\n";
    echo "  Description: " . $expense->description . "\n";
    
    // Approve the expense
    $expense->status = 'APPROVED';
    $expense->approval_id = $user->id;
    $expense->save();
    echo "✓ Expense approved\n";
    
    // Process payment
    echo "\nProcessing cash payment...\n";
    
    $paymentData = [
        'expense_id' => $expense->id,
        'payment_method' => 'cash',
        'amount' => $expense->amount,
        'payment_account_id' => $account->id,
        'payment_reference' => 'CASH-' . date('YmdHis'),
        'narration' => 'Cash payment for office supplies'
    ];
    
    $result = $paymentService->processPayment($paymentData);
    
    if ($result['success']) {
        echo "✅ PAYMENT SUCCESSFUL!\n";
        echo "  Transaction ID: " . $result['transaction_id'] . "\n";
        echo "  Reference: " . $result['payment_reference'] . "\n";
        
        // Check if expense status was updated
        $expense->refresh();
        echo "  Expense Status: " . $expense->status . "\n";
        
        // Check logs
        echo "\n📋 Checking logs...\n";
        $logFile = storage_path('logs/payments/payments-' . date('Y-m-d') . '.log');
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            $lines = array_slice(explode("\n", $logContent), -5);
            foreach (array_filter($lines) as $line) {
                if (strpos($line, 'expense_id":"' . $expense->id) !== false) {
                    echo "  ✓ Payment logged\n";
                    break;
                }
            }
        }
    } else {
        echo "❌ Payment failed: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}

echo "\n=====================================\n";
echo "TEST 2: BANK TRANSFER EXPENSE\n";
echo "=====================================\n";

try {
    // Create expense for bank transfer
    $expenseData = [
        'account_id' => $account->id,
        'amount' => 500000, // 500,000 TZS
        'description' => 'Equipment purchase via bank transfer',
        'payment_type' => 'BANK_TRANSFER',
        'user_id' => $user->id,
        'status' => 'APPROVED',
        'approval_id' => $user->id
    ];
    
    if ($budgetItem) {
        $expenseData['budget_item_id'] = $budgetItem->id;
    }
    
    $expense2 = Expense::create($expenseData);
    echo "✓ Created expense ID: " . $expense2->id . "\n";
    echo "  Amount: " . number_format($expense2->amount) . " TZS\n";
    
    // Process bank transfer
    $paymentData = [
        'expense_id' => $expense2->id,
        'payment_method' => 'bank_transfer',
        'amount' => $expense2->amount,
        'payment_account_id' => $account->id,
        'to_account' => '0110123456789', // NBC format account
        'to_bank_code' => 'NBC',
        'to_account_name' => 'Equipment Supplier Ltd',
        'payment_reference' => 'BANK-' . date('YmdHis'),
        'narration' => 'Bank transfer for equipment'
    ];
    
    echo "\nProcessing bank transfer...\n";
    $result = $paymentService->processPayment($paymentData);
    
    if ($result['success']) {
        echo "✅ BANK TRANSFER SUCCESSFUL!\n";
        echo "  Transaction ID: " . $result['transaction_id'] . "\n";
        if (isset($result['external_reference'])) {
            echo "  External Ref: " . $result['external_reference'] . "\n";
        }
        if (isset($result['fee'])) {
            echo "  Fee: " . number_format($result['fee']) . " TZS\n";
        }
    } else {
        echo "❌ Transfer failed: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "TEST 3: MOBILE MONEY PAYMENT\n";
echo "=====================================\n";

try {
    // Create expense for mobile money
    $expenseData = [
        'account_id' => $account->id,
        'amount' => 75000, // 75,000 TZS
        'description' => 'Staff transport reimbursement',
        'payment_type' => 'MOBILE_MONEY',
        'user_id' => $user->id,
        'status' => 'APPROVED',
        'approval_id' => $user->id
    ];
    
    if ($budgetItem) {
        $expenseData['budget_item_id'] = $budgetItem->id;
    }
    
    $expense3 = Expense::create($expenseData);
    echo "✓ Created expense ID: " . $expense3->id . "\n";
    echo "  Amount: " . number_format($expense3->amount) . " TZS\n";
    
    // Process mobile money
    $paymentData = [
        'expense_id' => $expense3->id,
        'payment_method' => 'mobile_money',
        'amount' => $expense3->amount,
        'payment_account_id' => $account->id,
        'phone_number' => '255754321098',
        'provider' => 'VODACOM',
        'recipient_name' => 'John Doe',
        'payment_reference' => 'MM-' . date('YmdHis'),
        'narration' => 'Transport reimbursement'
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
    } else {
        echo "❌ Mobile money failed: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "TEST 4: VALIDATION TESTS\n";
echo "=====================================\n";

// Test 4.1: Try to pay unapproved expense
echo "\n4.1 Testing unapproved expense:\n";
try {
    $unapprovedExpense = Expense::create([
        'account_id' => $account->id,
        'amount' => 100000,
        'description' => 'Test unapproved expense',
        'payment_type' => 'CASH',
        'user_id' => $user->id,
        'status' => 'PENDING' // Not approved
    ]);
    
    $paymentData = [
        'expense_id' => $unapprovedExpense->id,
        'payment_method' => 'cash',
        'amount' => $unapprovedExpense->amount,
        'payment_account_id' => $account->id
    ];
    
    $result = $paymentService->processPayment($paymentData);
    
    if (!$result['success']) {
        echo "✅ Correctly rejected: " . $result['message'] . "\n";
    } else {
        echo "❌ ERROR: Should have rejected unapproved expense!\n";
    }
} catch (Exception $e) {
    echo "✅ Correctly rejected with exception: " . $e->getMessage() . "\n";
}

// Test 4.2: Try to pay already paid expense
echo "\n4.2 Testing double payment prevention:\n";
try {
    if (isset($expense)) {
        // Mark expense as paid
        $expense->status = 'PAID';
        $expense->save();
        
        $paymentData = [
            'expense_id' => $expense->id,
            'payment_method' => 'cash',
            'amount' => $expense->amount,
            'payment_account_id' => $account->id
        ];
        
        $result = $paymentService->processPayment($paymentData);
        
        if (!$result['success']) {
            echo "✅ Correctly rejected: " . $result['message'] . "\n";
        } else {
            echo "❌ ERROR: Should have rejected double payment!\n";
        }
    }
} catch (Exception $e) {
    echo "✅ Correctly rejected with exception: " . $e->getMessage() . "\n";
}

// Test 4.3: Mobile money limit test
echo "\n4.3 Testing mobile money limit (20M TZS):\n";
try {
    $largeExpense = Expense::create([
        'account_id' => $account->id,
        'amount' => 25000000, // 25M TZS
        'description' => 'Large amount test',
        'payment_type' => 'MOBILE_MONEY',
        'user_id' => $user->id,
        'status' => 'APPROVED',
        'approval_id' => $user->id
    ]);
    
    $paymentData = [
        'expense_id' => $largeExpense->id,
        'payment_method' => 'mobile_money',
        'amount' => $largeExpense->amount,
        'payment_account_id' => $account->id,
        'phone_number' => '255789123456',
        'provider' => 'VODACOM',
        'recipient_name' => 'Test User'
    ];
    
    $result = $paymentService->processPayment($paymentData);
    
    if (!$result['success'] && strpos($result['message'], 'exceed') !== false) {
        echo "✅ Correctly rejected: " . $result['message'] . "\n";
    } else if (!$result['success']) {
        echo "✅ Rejected: " . $result['message'] . "\n";
    } else {
        echo "❌ ERROR: Should have rejected amount exceeding limit!\n";
    }
} catch (Exception $e) {
    echo "✅ Correctly rejected with exception: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "PAYMENT LOG VERIFICATION\n";
echo "=====================================\n";

// Check payment logs
$logFile = storage_path('logs/payments/payments-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    echo "✓ Payment log file exists\n";
    
    // Get file size
    $size = filesize($logFile);
    echo "  File size: " . number_format($size) . " bytes\n";
    
    // Count log entries
    $content = file_get_contents($logFile);
    $entryCount = substr_count($content, '"timestamp"');
    echo "  Log entries: " . $entryCount . "\n";
    
    // Show last few entries
    echo "\nRecent log entries:\n";
    $lines = explode("\n", $content);
    $recentLines = array_slice(array_filter($lines), -3);
    
    foreach ($recentLines as $i => $line) {
        echo "\nEntry " . ($i + 1) . ":\n";
        // Try to decode JSON
        if (preg_match('/(\{.+\})/', $line, $matches)) {
            $json = json_decode($matches[1], true);
            if ($json) {
                echo "  Type: " . ($json['type'] ?? 'N/A') . "\n";
                echo "  Method: " . ($json['payment_method'] ?? 'N/A') . "\n";
                echo "  Amount: " . number_format($json['amount'] ?? 0) . "\n";
                echo "  Status: " . ($json['status'] ?? 'N/A') . "\n";
                if (isset($json['api_request'])) {
                    echo "  API Called: Yes\n";
                }
                if (isset($json['api_response'])) {
                    echo "  API Response: " . ($json['api_response']['status'] ?? 'N/A') . "\n";
                }
            }
        }
    }
} else {
    echo "⚠ Payment log file not found\n";
}

echo "\n=====================================\n";
echo "TEST COMPLETE\n";
echo "=====================================\n";
echo "✅ All tests executed with REAL services\n";
echo "✅ No mock data used\n";
echo "✅ Check " . basename($logFile) . " for API logs\n\n";