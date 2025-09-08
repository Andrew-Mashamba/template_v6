<?php

/**
 * Expense Payment Processing Test with Detailed Logging
 * 
 * This script tests payment scenarios and captures all service logs
 * Run with: php test_payment_with_logs.php
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Colors for output
$green = "\033[32m";
$red = "\033[31m";
$yellow = "\033[33m";
$blue = "\033[34m";
$cyan = "\033[36m";
$reset = "\033[0m";

echo "\n{$yellow}========================================{$reset}\n";
echo "{$yellow}PAYMENT SERVICE TESTING WITH LOGS{$reset}\n";
echo "{$yellow}========================================{$reset}\n\n";

// Configure detailed logging
Log::listen(function ($message) use ($cyan, $reset) {
    // Capture and display all log messages
    echo "{$cyan}[LOG] {$message->message}{$reset}\n";
});

/**
 * Setup test data
 */
function setupTestData() {
    echo "\n📋 Setting up test data...\n";
    
    // Clean up previous test data
    DB::table('expenses')->where('description', 'LIKE', 'LOG_TEST_%')->delete();
    DB::table('approvals')->where('comments', 'LIKE', 'LOG_TEST_%')->delete();
    
    // Create or get test user
    $testUser = DB::table('users')->where('email', 'test_logs@test.com')->first();
    if (!$testUser) {
        $userId = DB::table('users')->insertGetId([
            'name' => 'Test Log User',
            'email' => 'test_logs@test.com',
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now()
        ]);
    } else {
        $userId = $testUser->id;
    }
    
    // Create test accounts
    DB::table('accounts')->where('account_number', 'LIKE', 'LOG-TEST-%')->delete();
    
    $expenseAccountId = DB::table('accounts')->insertGetId([
        'account_number' => 'LOG-TEST-EXP-001',
        'account_name' => 'LOG TEST Office Expenses',
        'major_category_code' => 5000,
        'balance' => 0,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    $cashAccountId = DB::table('accounts')->insertGetId([
        'account_number' => 'LOG-TEST-CASH-001',
        'account_name' => 'LOG TEST Cash Account',
        'major_category_code' => 1000,
        'balance' => 5000000,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    $bankAccountId = DB::table('accounts')->insertGetId([
        'account_number' => 'LOG-TEST-BANK-001',
        'account_name' => 'LOG TEST Bank Account',
        'major_category_code' => 1000,
        'balance' => 100000000,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "✅ Test data setup complete\n";
    
    return [
        'user_id' => $userId,
        'expense_account_id' => $expenseAccountId,
        'cash_account_id' => $cashAccountId,
        'bank_account_id' => $bankAccountId
    ];
}

/**
 * Test 1: Simple Cash Payment with Logging
 */
function testCashPaymentWithLogs($testAccounts) {
    global $blue, $green, $red, $yellow, $reset;
    
    echo "\n{$blue}========================================{$reset}\n";
    echo "{$blue}TEST 1: CASH PAYMENT WITH DETAILED LOGS{$reset}\n";
    echo "{$blue}========================================{$reset}\n\n";
    
    // Create expense
    $expenseId = DB::table('expenses')->insertGetId([
        'description' => 'LOG_TEST_Cash Payment',
        'amount' => 75000,
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
        'comments' => 'LOG_TEST_Approved for cash payment',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "📝 Created expense ID: {$expenseId}\n";
    echo "💰 Amount: TZS 75,000\n";
    echo "🔄 Processing payment...\n\n";
    
    try {
        // Process payment
        $service = new \App\Services\ExpensePaymentService();
        $result = $service->processPayment($expenseId);
        
        echo "\n{$green}✅ PAYMENT RESULT:{$reset}\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
        // Check transaction details
        if ($result['success'] && isset($result['transaction_id'])) {
            $transaction = DB::table('transactions')->find($result['transaction_id']);
            echo "\n{$yellow}📊 TRANSACTION DETAILS:{$reset}\n";
            echo "Transaction ID: " . $transaction->id . "\n";
            echo "Type: " . $transaction->type . "\n";
            echo "Amount: TZS " . number_format($transaction->amount, 2) . "\n";
            echo "Status: " . $transaction->status . "\n";
            echo "Reference: " . $transaction->reference . "\n";
            echo "Description: " . $transaction->description . "\n";
        }
        
    } catch (\Exception $e) {
        echo "{$red}❌ ERROR: {$e->getMessage()}{$reset}\n";
    }
}

/**
 * Test 2: Bank Transfer with External API Mock
 */
function testBankTransferWithLogs($testAccounts) {
    global $blue, $green, $red, $yellow, $cyan, $reset;
    
    echo "\n{$blue}========================================{$reset}\n";
    echo "{$blue}TEST 2: BANK TRANSFER WITH API LOGS{$reset}\n";
    echo "{$blue}========================================{$reset}\n\n";
    
    // Create expense
    $expenseId = DB::table('expenses')->insertGetId([
        'description' => 'LOG_TEST_Bank Transfer Payment',
        'amount' => 250000,
        'account_id' => $testAccounts['expense_account_id'],
        'user_id' => $testAccounts['user_id'],
        'status' => 'PENDING_APPROVAL',
        'payment_type' => 'bank_transfer',
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
        'comments' => 'LOG_TEST_Approved for bank transfer',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    // Create bank account entry
    DB::table('bank_accounts')->where('account_number', 'LIKE', 'LOG-TEST%')->delete();
    $bankAccountId = DB::table('bank_accounts')->insertGetId([
        'account_name' => 'LOG TEST NBC Account',
        'account_number' => 'LOG-TEST-0110000001',
        'bank_name' => 'NBC Bank',
        'swift_code' => 'NLCBTZTX',
        'current_balance' => 50000000,
        'status' => 'ACTIVE',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    echo "📝 Created expense ID: {$expenseId}\n";
    echo "💰 Amount: TZS 250,000\n";
    echo "🏦 Bank: NBC Bank\n";
    echo "🔄 Processing bank transfer payment...\n\n";
    
    $paymentData = [
        'funding_source' => 'bank_account',
        'source_account_id' => $bankAccountId,
        'payment_method' => 'bank_transfer',
        'account_holder_name' => 'ABC Suppliers Ltd',
        'recipient_account_number' => '0123456789',
        'recipient_bank_code' => 'CRDB',
        'payment_notes' => 'Payment for office supplies',
        'requires_external_transfer' => true
    ];
    
    try {
        echo "{$cyan}📡 Payment Data Being Sent:{$reset}\n";
        echo json_encode($paymentData, JSON_PRETTY_PRINT) . "\n\n";
        
        $service = new \App\Services\ExpensePaymentService();
        $result = $service->processEnhancedPayment($expenseId, $paymentData);
        
        echo "\n{$green}✅ PAYMENT RESULT:{$reset}\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
        // Check for external transfer logs
        if (isset($result['transfer_status'])) {
            echo "\n{$yellow}🔄 EXTERNAL TRANSFER STATUS: {$result['transfer_status']}{$reset}\n";
        }
        
    } catch (\Exception $e) {
        echo "{$red}❌ ERROR: {$e->getMessage()}{$reset}\n";
        echo "{$red}Stack trace:\n{$e->getTraceAsString()}{$reset}\n";
    }
}

/**
 * Test 3: Mobile Money Payment with API Logs
 */
function testMobileMoneyWithLogs($testAccounts) {
    global $blue, $green, $red, $yellow, $cyan, $reset;
    
    echo "\n{$blue}========================================{$reset}\n";
    echo "{$blue}TEST 3: MOBILE MONEY WITH API LOGS{$reset}\n";
    echo "{$blue}========================================{$reset}\n\n";
    
    // Create expense
    $expenseId = DB::table('expenses')->insertGetId([
        'description' => 'LOG_TEST_Mobile Money Payment',
        'amount' => 150000,
        'account_id' => $testAccounts['expense_account_id'],
        'user_id' => $testAccounts['user_id'],
        'status' => 'PENDING_APPROVAL',
        'payment_type' => 'mobile_money',
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
        'comments' => 'LOG_TEST_Approved for mobile money',
        'created_at' => now(),
        'updated_at' => now()
    ]);
    
    // Get or create bank account
    $bankAccount = DB::table('bank_accounts')
        ->where('account_number', 'LIKE', 'LOG-TEST%')
        ->first();
    
    echo "📝 Created expense ID: {$expenseId}\n";
    echo "💰 Amount: TZS 150,000\n";
    echo "📱 Provider: M-Pesa\n";
    echo "📞 Phone: 255712345678\n";
    echo "🔄 Processing mobile money payment...\n\n";
    
    $paymentData = [
        'funding_source' => 'bank_account',
        'source_account_id' => $bankAccount ? $bankAccount->id : 1,
        'payment_method' => 'mobile_money',
        'phone_number' => '255712345678',
        'mno_provider' => 'mpesa',
        'account_holder_name' => 'John Doe',
        'requires_external_transfer' => true
    ];
    
    try {
        echo "{$cyan}📡 Mobile Money Request Data:{$reset}\n";
        echo json_encode($paymentData, JSON_PRETTY_PRINT) . "\n\n";
        
        $service = new \App\Services\ExpensePaymentService();
        $result = $service->processEnhancedPayment($expenseId, $paymentData);
        
        echo "\n{$green}✅ PAYMENT RESULT:{$reset}\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
        if (isset($result['transfer_status'])) {
            echo "\n{$yellow}📱 MOBILE TRANSFER STATUS: {$result['transfer_status']}{$reset}\n";
        }
        
    } catch (\Exception $e) {
        echo "{$red}❌ ERROR: {$e->getMessage()}{$reset}\n";
    }
}

/**
 * Test 4: Check logs from different channels
 */
function checkLogFiles() {
    global $blue, $yellow, $reset;
    
    echo "\n{$blue}========================================{$reset}\n";
    echo "{$blue}CHECKING LOG FILES{$reset}\n";
    echo "{$blue}========================================{$reset}\n\n";
    
    // Check budget management logs
    $budgetLogPath = storage_path('logs/budget_management.log');
    if (file_exists($budgetLogPath)) {
        echo "{$yellow}📁 Budget Management Log (Last 20 lines):{$reset}\n";
        $lines = file($budgetLogPath);
        $lastLines = array_slice($lines, -20);
        foreach ($lastLines as $line) {
            if (strpos($line, 'EXPENSE') !== false || strpos($line, 'PAYMENT') !== false) {
                echo trim($line) . "\n";
            }
        }
    }
    
    // Check Laravel log
    $laravelLogPath = storage_path('logs/laravel.log');
    if (file_exists($laravelLogPath)) {
        echo "\n{$yellow}📁 Laravel Log (Payment-related entries):{$reset}\n";
        $lines = file($laravelLogPath);
        $lastLines = array_slice($lines, -30);
        foreach ($lastLines as $line) {
            if (strpos($line, 'payment') !== false || strpos($line, 'Payment') !== false || 
                strpos($line, 'expense') !== false || strpos($line, 'Expense') !== false) {
                echo trim($line) . "\n";
            }
        }
    }
}

/**
 * Clean up test data
 */
function cleanupTestData() {
    DB::table('expenses')->where('description', 'LIKE', 'LOG_TEST_%')->delete();
    DB::table('approvals')->where('comments', 'LIKE', 'LOG_TEST_%')->delete();
    DB::table('accounts')->where('account_number', 'LIKE', 'LOG-TEST-%')->delete();
    DB::table('transactions')->where('description', 'LIKE', '%LOG_TEST_%')->delete();
    DB::table('bank_accounts')->where('account_number', 'LIKE', 'LOG-TEST%')->delete();
}

// Run tests
try {
    $testAccounts = setupTestData();
    
    // Test 1: Cash Payment
    testCashPaymentWithLogs($testAccounts);
    sleep(1); // Give time for logs to write
    
    // Test 2: Bank Transfer
    testBankTransferWithLogs($testAccounts);
    sleep(1);
    
    // Test 3: Mobile Money
    testMobileMoneyWithLogs($testAccounts);
    sleep(1);
    
    // Check log files
    checkLogFiles();
    
    // Clean up
    echo "\n{$yellow}🧹 Cleaning up test data...{$reset}\n";
    cleanupTestData();
    echo "{$green}✅ Test data cleaned up successfully.{$reset}\n\n";
    
} catch (\Exception $e) {
    echo "{$red}❌ FATAL ERROR: {$e->getMessage()}{$reset}\n";
    echo "{$red}Stack trace:\n{$e->getTraceAsString()}{$reset}\n";
}