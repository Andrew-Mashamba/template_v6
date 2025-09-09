<?php

/**
 * Expense Payment Processing Test Script
 * 
 * This script tests various expense payment scenarios
 * Run with: php test_expense_payment.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Colors for output
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$reset = "\033[0m";

echo "\n{$yellow}========================================{$reset}\n";
echo "{$yellow}EXPENSE PAYMENT PROCESSING TEST SUITE{$reset}\n";
echo "{$yellow}========================================{$reset}\n\n";

$testResults = [];
$totalTests = 0;
$passedTests = 0;

/**
 * Test helper function
 */
function runTest($testName, $testFunction) {
    global $green, $red, $reset, $testResults, $totalTests, $passedTests;
    
    $totalTests++;
    echo "Test {$totalTests}: {$testName} ... ";
    
    try {
        $result = $testFunction();
        if ($result['success']) {
            echo "{$green}PASSED{$reset}\n";
            $passedTests++;
            $testResults[] = ['test' => $testName, 'status' => 'PASSED', 'message' => $result['message'] ?? 'Success'];
        } else {
            echo "{$red}FAILED{$reset}\n";
            echo "  Reason: {$result['message']}\n";
            $testResults[] = ['test' => $testName, 'status' => 'FAILED', 'message' => $result['message']];
        }
    } catch (Exception $e) {
        echo "{$red}ERROR{$reset}\n";
        echo "  Exception: {$e->getMessage()}\n";
        $testResults[] = ['test' => $testName, 'status' => 'ERROR', 'message' => $e->getMessage()];
    }
}

/**
 * Setup test data
 */
function setupTestData() {
    // Clean up previous test data
    DB::table('expenses')->where('description', 'LIKE', 'TEST_%')->delete();
    DB::table('approvals')->where('comments', 'LIKE', 'TEST_%')->delete();
    
    // Create test user if doesn't exist
    $testUser = DB::table('users')->where('email', 'test_expense@test.com')->first();
    if (!$testUser) {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test Expense User',
            'email' => 'test_expense@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    } else {
        $userId = $testUser->id;
    }
    
    // Create test accounts if they don't exist
    $expenseAccountId = DB::table('accounts')->insertGetId([
        'account_number' => 'TEST-EXP-001',
        'account_name' => 'TEST Office Supplies Expense',
        'major_category_code' => 5000,
        'balance' => 0,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    $cashAccountId = DB::table('accounts')->insertGetId([
        'account_number' => 'TEST-CASH-001',
        'account_name' => 'TEST Petty Cash Account',
        'major_category_code' => 1000,
        'balance' => 1000000,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    $bankAccountId = DB::table('accounts')->insertGetId([
        'account_number' => 'TEST-BANK-001',
        'account_name' => 'TEST Bank Account',
        'major_category_code' => 1000,
        'balance' => 50000000,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    return [
        'user_id' => $userId,
        'expense_account_id' => $expenseAccountId,
        'cash_account_id' => $cashAccountId,
        'bank_account_id' => $bankAccountId
    ];
}

/**
 * Clean up test data
 */
function cleanupTestData() {
    DB::table('expenses')->where('description', 'LIKE', 'TEST_%')->delete();
    DB::table('approvals')->where('comments', 'LIKE', 'TEST_%')->delete();
    DB::table('accounts')->where('account_number', 'LIKE', 'TEST-%')->delete();
    DB::table('transactions')->where('description', 'LIKE', '%TEST_%')->delete();
    DB::table('bank_accounts')->where('account_number', 'LIKE', 'TEST%')->delete();
    // Keep the test user for future runs
}

// Setup test environment
$testAccounts = setupTestData();

/**
 * TEST 1: Process approved expense with cash payment
 */
runTest("Process approved expense with cash payment", function() use ($testAccounts) {
    // Create expense
    $expenseId = DB::table('expenses')->insertGetId([
        'description' => 'TEST_Cash Payment Expense',
        'amount' => 50000,
        'account_id' => $testAccounts['expense_account_id'],
        'user_id' => $testAccounts['user_id'],
        'status' => 'PENDING_APPROVAL',
        'payment_type' => 'cash',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    // Create approval
    DB::table('approvals')->insert([
        'process_code' => 'EXPENSE_REG',
        'process_id' => $expenseId,
        'approval_status' => 'APPROVED',
        'approver_id' => $testAccounts['user_id'],
        'user_id' => $testAccounts['user_id'],
        'approved_at' => now(),
        'comments' => 'TEST_Approved for testing',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    // Process payment
    $service = new \App\Services\ExpensePaymentService();
    $result = $service->processPayment($expenseId);
    
    // Verify expense status
    $expense = DB::table('expenses')->find($expenseId);
    if ($expense->status !== 'PAID') {
        return ['success' => false, 'message' => 'Expense status not updated to PAID'];
    }
    
    return $result;
});

/**
 * TEST 2: Prevent double payment
 */
runTest("Prevent double payment of expense", function() use ($testAccounts) {
    // Create and pay expense
    $expenseId = DB::table('expenses')->insertGetId([
        'description' => 'TEST_Double Payment Check',
        'amount' => 25000,
        'account_id' => $testAccounts['expense_account_id'],
        'user_id' => $testAccounts['user_id'],
        'status' => 'PENDING_APPROVAL',
        'payment_type' => 'cash',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    DB::table('approvals')->insert([
        'process_code' => 'EXPENSE_REG',
        'process_id' => $expenseId,
        'approval_status' => 'APPROVED',
        'approver_id' => $testAccounts['user_id'],
        'user_id' => $testAccounts['user_id'],
        'approved_at' => now(),
        'comments' => 'TEST_Approved',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    $service = new \App\Services\ExpensePaymentService();
    
    // First payment
    $result1 = $service->processPayment($expenseId);
    if (!$result1['success']) {
        return ['success' => false, 'message' => 'First payment failed'];
    }
    
    // Second payment attempt
    $result2 = $service->processPayment($expenseId);
    if ($result2['success']) {
        return ['success' => false, 'message' => 'Second payment should have been rejected'];
    }
    
    return ['success' => true, 'message' => 'Double payment properly prevented'];
});

/**
 * TEST 3: Reject payment for unapproved expense
 */
runTest("Reject payment for unapproved expense", function() use ($testAccounts) {
    $expenseId = DB::table('expenses')->insertGetId([
        'description' => 'TEST_Unapproved Expense',
        'amount' => 30000,
        'account_id' => $testAccounts['expense_account_id'],
        'user_id' => $testAccounts['user_id'],
        'status' => 'PENDING_APPROVAL',
        'payment_type' => 'cash',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    // No approval record created
    
    $service = new \App\Services\ExpensePaymentService();
    $result = $service->processPayment($expenseId);
    
    if ($result['success']) {
        return ['success' => false, 'message' => 'Payment should have been rejected for unapproved expense'];
    }
    
    return ['success' => true, 'message' => 'Unapproved expense payment properly rejected'];
});

/**
 * TEST 4: Process payment with bank transfer details
 */
runTest("Process payment with bank transfer details", function() use ($testAccounts) {
    $expenseId = DB::table('expenses')->insertGetId([
        'description' => 'TEST_Bank Transfer Payment',
        'amount' => 100000,
        'account_id' => $testAccounts['expense_account_id'],
        'user_id' => $testAccounts['user_id'],
        'status' => 'PENDING_APPROVAL',
        'payment_type' => 'bank_transfer',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    DB::table('approvals')->insert([
        'process_code' => 'EXPENSE_REG',
        'process_id' => $expenseId,
        'approval_status' => 'APPROVED',
        'approver_id' => $testAccounts['user_id'],
        'user_id' => $testAccounts['user_id'],
        'approved_at' => now(),
        'comments' => 'TEST_Approved for bank transfer',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    $paymentData = [
        'payment_method' => 'bank_transfer',
        'bank_account_id' => $testAccounts['bank_account_id'],
        'account_holder_name' => 'TEST Supplier Ltd'
    ];
    
    $service = new \App\Services\ExpensePaymentService();
    $result = $service->processPaymentWithDetails($expenseId, $paymentData);
    
    return $result;
});

/**
 * TEST 5: Process petty cash payment
 */
runTest("Process petty cash payment", function() use ($testAccounts) {
    $expenseId = DB::table('expenses')->insertGetId([
        'description' => 'TEST_Petty Cash Payment',
        'amount' => 15000,
        'account_id' => $testAccounts['expense_account_id'],
        'user_id' => $testAccounts['user_id'],
        'status' => 'PENDING_APPROVAL',
        'payment_type' => 'cash',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    DB::table('approvals')->insert([
        'process_code' => 'EXPENSE_REG',
        'process_id' => $expenseId,
        'approval_status' => 'APPROVED',
        'approver_id' => $testAccounts['user_id'],
        'user_id' => $testAccounts['user_id'],
        'approved_at' => now(),
        'comments' => 'TEST_Approved for petty cash',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    // Create bank account entry for enhanced payment
    DB::table('bank_accounts')->insert([
        'account_name' => 'TEST NBC Account',
        'account_number' => 'TEST0110000001',
        'bank_name' => 'NBC Bank',
        'bank_code' => 'NBC',
        'current_balance' => 10000000,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    $paymentData = [
        'funding_source' => 'petty_cash',
        'payment_method' => 'cash',
        'requires_external_transfer' => false
    ];
    
    $service = new \App\Services\ExpensePaymentService();
    $result = $service->processEnhancedPayment($expenseId, $paymentData);
    
    return $result;
});

/**
 * TEST 6: Process batch payment
 */
runTest("Process batch payment", function() use ($testAccounts) {
    $expenseIds = [];
    
    // Create 3 expenses
    for ($i = 1; $i <= 3; $i++) {
        $expenseId = DB::table('expenses')->insertGetId([
            'description' => "TEST_Batch Expense $i",
            'amount' => 10000 * $i,
            'account_id' => $testAccounts['expense_account_id'],
            'user_id' => $testAccounts['user_id'],
                'status' => 'PENDING_APPROVAL',
            'payment_type' => 'cash',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        DB::table('approvals')->insert([
            'process_code' => 'EXPENSE_REG',
            'process_id' => $expenseId,
            'approval_status' => 'APPROVED',
            'approver_id' => $testAccounts['user_id'],
        'user_id' => $testAccounts['user_id'],
            'approved_at' => now(),
            'comments' => "TEST_Batch approval $i",
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $expenseIds[] = $expenseId;
    }
    
    $service = new \App\Services\ExpensePaymentService();
    $results = $service->processBatchPayment($expenseIds);
    
    if (count($results['successful']) !== 3) {
        return ['success' => false, 'message' => 'Not all batch payments were successful'];
    }
    
    if ($results['total_amount'] !== 60000) {
        return ['success' => false, 'message' => 'Batch total amount incorrect'];
    }
    
    return ['success' => true, 'message' => 'Batch payment processed successfully'];
});

/**
 * TEST 7: Mobile money payment with validation
 */
runTest("Mobile money payment within limit", function() use ($testAccounts) {
    $expenseId = DB::table('expenses')->insertGetId([
        'description' => 'TEST_Mobile Money Payment',
        'amount' => 500000, // 500K - well under 20M limit
        'account_id' => $testAccounts['expense_account_id'],
        'user_id' => $testAccounts['user_id'],
        'status' => 'PENDING_APPROVAL',
        'payment_type' => 'mobile_money',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    DB::table('approvals')->insert([
        'process_code' => 'EXPENSE_REG',
        'process_id' => $expenseId,
        'approval_status' => 'APPROVED',
        'approver_id' => $testAccounts['user_id'],
        'user_id' => $testAccounts['user_id'],
        'approved_at' => now(),
        'comments' => 'TEST_Mobile money approved',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    // Get or create bank account ID
    $bankAccount = DB::table('bank_accounts')
        ->where('account_number', 'LIKE', 'TEST%')
        ->first();
    
    if (!$bankAccount) {
        $bankAccountId = DB::table('bank_accounts')->insertGetId([
            'account_name' => 'TEST Mobile Bank',
            'account_number' => 'TEST0110000002',
            'bank_name' => 'NBC Bank',
            'swift_code' => 'NLCBTZTX',
            'current_balance' => 5000000,
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now()
        ]);
    } else {
        $bankAccountId = $bankAccount->id;
    }
    
    $paymentData = [
        'funding_source' => 'bank_account',
        'source_account_id' => $bankAccountId,
        'payment_method' => 'mobile_money',
        'phone_number' => '255712345678',
        'mno_provider' => 'mpesa',
        'account_holder_name' => 'TEST John Doe',
        'requires_external_transfer' => true
    ];
    
    $service = new \App\Services\ExpensePaymentService();
    $result = $service->processEnhancedPayment($expenseId, $paymentData);
    
    return $result;
});

/**
 * TEST 8: Reject payment for rejected expense
 */
runTest("Reject payment for rejected expense", function() use ($testAccounts) {
    $expenseId = DB::table('expenses')->insertGetId([
        'description' => 'TEST_Rejected Expense',
        'amount' => 40000,
        'account_id' => $testAccounts['expense_account_id'],
        'user_id' => $testAccounts['user_id'],
        'status' => 'REJECTED',
        'payment_type' => 'cash',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    DB::table('approvals')->insert([
        'process_code' => 'EXPENSE_REG',
        'process_id' => $expenseId,
        'approval_status' => 'REJECTED',
        'approver_id' => $testAccounts['user_id'],
        'user_id' => $testAccounts['user_id'],
        'comments' => 'TEST_Rejected - not justified',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    $service = new \App\Services\ExpensePaymentService();
    $result = $service->processPayment($expenseId);
    
    if ($result['success']) {
        return ['success' => false, 'message' => 'Payment should have been rejected for rejected expense'];
    }
    
    return ['success' => true, 'message' => 'Rejected expense payment properly prevented'];
});

// Print summary
echo "\n{$yellow}========================================{$reset}\n";
echo "{$yellow}TEST RESULTS SUMMARY{$reset}\n";
echo "{$yellow}========================================{$reset}\n\n";

echo "Total Tests: {$totalTests}\n";
echo "{$green}Passed: {$passedTests}{$reset}\n";
echo "{$red}Failed: " . ($totalTests - $passedTests) . "{$reset}\n";
echo "Success Rate: " . round(($passedTests / $totalTests) * 100, 2) . "%\n\n";

// Display detailed results
echo "{$yellow}Detailed Results:{$reset}\n";
foreach ($testResults as $index => $result) {
    $statusColor = $result['status'] === 'PASSED' ? $green : $red;
    echo ($index + 1) . ". {$result['test']}: {$statusColor}{$result['status']}{$reset}\n";
    if ($result['status'] !== 'PASSED') {
        echo "   → {$result['message']}\n";
    }
}

// Cleanup
echo "\n{$yellow}Cleaning up test data...{$reset}\n";
cleanupTestData();
echo "{$green}Test data cleaned up successfully.{$reset}\n\n";

exit($passedTests === $totalTests ? 0 : 1);