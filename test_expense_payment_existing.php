<?php

/**
 * Expense Payment Test Using Existing Data
 * No mocks - Real services only
 */

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Expense;
use App\Models\User;
use App\Models\Account;
use App\Services\ExpensePaymentService;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=====================================\n";
echo "  EXPENSE PAYMENT TEST - REAL DATA  \n";
echo "=====================================\n\n";

// Initialize the payment service
$paymentService = app(ExpensePaymentService::class);

// Get existing user
$user = User::first();
if (!$user) {
    die("Error: No users found. Please seed the database.\n");
}
echo "✓ Using user: " . $user->name . " (ID: " . $user->id . ")\n";

// Get existing account
$account = Account::where('status', 'ACTIVE')->first();
if (!$account) {
    die("Error: No active accounts found.\n");
}
echo "✓ Using account: " . $account->account_number . " (Balance: " . number_format($account->balance) . ")\n";

echo "\n=====================================\n";
echo "TEST: CREATE AND PAY EXPENSE\n";
echo "=====================================\n";

try {
    // Create a simple expense with minimal fields
    $expense = new Expense();
    $expense->account_id = $account->id;
    $expense->amount = 50000; // 50,000 TZS
    $expense->description = 'Test expense payment - ' . date('Y-m-d H:i:s');
    $expense->payment_type = 'CASH';
    $expense->user_id = $user->id;
    $expense->status = 'PENDING';
    $expense->save();
    
    echo "\n✓ Created expense ID: " . $expense->id . "\n";
    echo "  Amount: " . number_format($expense->amount) . " TZS\n";
    echo "  Description: " . $expense->description . "\n";
    echo "  Status: " . $expense->status . "\n";
    
    // Approve the expense
    $expense->status = 'APPROVED';
    $expense->approval_id = $user->id;
    $expense->save();
    echo "\n✓ Expense approved by user " . $user->id . "\n";
    
    // Prepare payment data
    $paymentData = [
        'expense_id' => $expense->id,
        'payment_method' => 'cash',
        'amount' => $expense->amount,
        'payment_account_id' => $account->id,
        'payment_reference' => 'PAY-' . date('YmdHis'),
        'narration' => 'Payment for expense #' . $expense->id
    ];
    
    echo "\n📤 Processing payment...\n";
    echo "  Method: " . $paymentData['payment_method'] . "\n";
    echo "  Amount: " . number_format($paymentData['amount']) . " TZS\n";
    echo "  Reference: " . $paymentData['payment_reference'] . "\n";
    
    // Process the payment
    $startTime = microtime(true);
    $result = $paymentService->processPayment($paymentData);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    echo "\n📥 Payment Result:\n";
    echo "  Processing time: " . $duration . " ms\n";
    
    if ($result['success']) {
        echo "  ✅ PAYMENT SUCCESSFUL!\n";
        echo "  Transaction ID: " . $result['transaction_id'] . "\n";
        echo "  Payment Reference: " . $result['payment_reference'] . "\n";
        
        // Refresh expense to check status
        $expense->refresh();
        echo "  Expense Status: " . $expense->status . "\n";
        
        // Check if payment was logged
        echo "\n📋 Checking payment logs...\n";
        $logFile = storage_path('logs/payments/payments-' . date('Y-m-d') . '.log');
        
        if (file_exists($logFile)) {
            $logContent = file_get_contents($logFile);
            
            // Look for this specific expense in logs
            if (strpos($logContent, '"expense_id":' . $expense->id) !== false) {
                echo "  ✓ Payment logged successfully\n";
                
                // Extract and show the log entry
                $lines = explode("\n", $logContent);
                foreach ($lines as $line) {
                    if (strpos($line, '"expense_id":' . $expense->id) !== false) {
                        echo "\n  Log Entry:\n";
                        // Try to parse JSON
                        if (preg_match('/(\{.+\})/', $line, $matches)) {
                            $json = json_decode($matches[1], true);
                            if ($json) {
                                echo "    Type: " . ($json['type'] ?? 'N/A') . "\n";
                                echo "    Status: " . ($json['status'] ?? 'N/A') . "\n";
                                echo "    Amount: " . number_format($json['amount'] ?? 0) . "\n";
                                echo "    Method: " . ($json['payment_method'] ?? 'N/A') . "\n";
                                
                                // Check for API interactions
                                if (isset($json['api_request'])) {
                                    echo "    API Request: Yes\n";
                                    echo "      Endpoint: " . ($json['api_request']['endpoint'] ?? 'N/A') . "\n";
                                }
                                if (isset($json['api_response'])) {
                                    echo "    API Response: Yes\n";
                                    echo "      Status: " . ($json['api_response']['status'] ?? 'N/A') . "\n";
                                    if (isset($json['api_response']['transaction_id'])) {
                                        echo "      Transaction ID: " . $json['api_response']['transaction_id'] . "\n";
                                    }
                                }
                            }
                        }
                        break;
                    }
                }
            } else {
                echo "  ⚠ Payment not found in logs\n";
            }
            
            // Show log file stats
            echo "\n  Log File Stats:\n";
            echo "    File: " . basename($logFile) . "\n";
            echo "    Size: " . number_format(filesize($logFile)) . " bytes\n";
            $entryCount = substr_count($logContent, "\n") + 1;
            echo "    Total entries: " . $entryCount . "\n";
        } else {
            echo "  ⚠ Log file not found: " . basename($logFile) . "\n";
        }
        
    } else {
        echo "  ❌ Payment failed!\n";
        echo "  Error: " . $result['message'] . "\n";
        if (isset($result['errors'])) {
            echo "  Errors: " . json_encode($result['errors']) . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "\n❌ Error occurred:\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n";
    echo "  Trace:\n";
    $trace = array_slice($e->getTrace(), 0, 3);
    foreach ($trace as $i => $t) {
        echo "    #" . $i . " " . ($t['file'] ?? 'N/A') . ":" . ($t['line'] ?? 'N/A') . "\n";
    }
}

echo "\n=====================================\n";
echo "TEST WITH DIFFERENT PAYMENT METHODS\n";
echo "=====================================\n";

// Test 2: Bank Transfer
echo "\n🏦 Testing Bank Transfer:\n";
try {
    $expense2 = new Expense();
    $expense2->account_id = $account->id;
    $expense2->amount = 250000; // 250,000 TZS
    $expense2->description = 'Bank transfer test - ' . date('Y-m-d H:i:s');
    $expense2->payment_type = 'BANK_TRANSFER';
    $expense2->user_id = $user->id;
    $expense2->status = 'APPROVED';
    $expense2->approval_id = $user->id;
    $expense2->save();
    
    echo "  Created expense ID: " . $expense2->id . "\n";
    
    $paymentData = [
        'expense_id' => $expense2->id,
        'payment_method' => 'bank_transfer',
        'amount' => $expense2->amount,
        'payment_account_id' => $account->id,
        'to_account' => '0110987654321', // NBC format
        'to_bank_code' => 'NBC',
        'to_account_name' => 'Test Supplier',
        'payment_reference' => 'BANK-' . date('YmdHis'),
        'narration' => 'Bank transfer test payment'
    ];
    
    $result = $paymentService->processPayment($paymentData);
    
    if ($result['success']) {
        echo "  ✅ Bank transfer successful!\n";
        echo "    Transaction: " . $result['transaction_id'] . "\n";
        if (isset($result['external_reference'])) {
            echo "    External Ref: " . $result['external_reference'] . "\n";
        }
    } else {
        echo "  ❌ Bank transfer failed: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

// Test 3: Mobile Money
echo "\n📱 Testing Mobile Money:\n";
try {
    $expense3 = new Expense();
    $expense3->account_id = $account->id;
    $expense3->amount = 45000; // 45,000 TZS
    $expense3->description = 'Mobile money test - ' . date('Y-m-d H:i:s');
    $expense3->payment_type = 'MOBILE_MONEY';
    $expense3->user_id = $user->id;
    $expense3->status = 'APPROVED';
    $expense3->approval_id = $user->id;
    $expense3->save();
    
    echo "  Created expense ID: " . $expense3->id . "\n";
    
    $paymentData = [
        'expense_id' => $expense3->id,
        'payment_method' => 'mobile_money',
        'amount' => $expense3->amount,
        'payment_account_id' => $account->id,
        'phone_number' => '255754321098',
        'provider' => 'VODACOM',
        'recipient_name' => 'Test Recipient',
        'payment_reference' => 'MM-' . date('YmdHis'),
        'narration' => 'Mobile money test payment'
    ];
    
    $result = $paymentService->processPayment($paymentData);
    
    if ($result['success'] || (isset($result['status']) && $result['status'] === 'pending')) {
        echo "  ✅ Mobile money initiated!\n";
        echo "    Transaction: " . $result['transaction_id'] . "\n";
        echo "    Status: " . ($result['status'] ?? 'processing') . "\n";
    } else {
        echo "  ❌ Mobile money failed: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "  ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n=====================================\n";
echo "FINAL LOG SUMMARY\n";
echo "=====================================\n";

$logFile = storage_path('logs/payments/payments-' . date('Y-m-d') . '.log');
if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    
    // Count different types of entries
    $apiRequests = substr_count($content, '"api_request"');
    $apiResponses = substr_count($content, '"api_response"');
    $successCount = substr_count($content, '"status":"success"');
    $failCount = substr_count($content, '"status":"failed"');
    
    echo "📊 Payment Log Statistics:\n";
    echo "  API Requests: " . $apiRequests . "\n";
    echo "  API Responses: " . $apiResponses . "\n";
    echo "  Successful: " . $successCount . "\n";
    echo "  Failed: " . $failCount . "\n";
    
    // Show last API interaction if any
    if ($apiRequests > 0 || $apiResponses > 0) {
        echo "\n📡 Recent API Activity:\n";
        $lines = explode("\n", $content);
        $foundApi = false;
        
        // Look for last API request/response
        for ($i = count($lines) - 1; $i >= 0 && !$foundApi; $i--) {
            if (strpos($lines[$i], 'api_request') !== false || strpos($lines[$i], 'api_response') !== false) {
                if (preg_match('/(\{.+\})/', $lines[$i], $matches)) {
                    $json = json_decode($matches[1], true);
                    if ($json) {
                        if (isset($json['api_request'])) {
                            echo "  Last API Request:\n";
                            echo "    Endpoint: " . ($json['api_request']['endpoint'] ?? 'N/A') . "\n";
                            echo "    Method: " . ($json['api_request']['method'] ?? 'N/A') . "\n";
                        }
                        if (isset($json['api_response'])) {
                            echo "  Last API Response:\n";
                            echo "    Status: " . ($json['api_response']['status'] ?? 'N/A') . "\n";
                            echo "    Code: " . ($json['api_response']['status_code'] ?? 'N/A') . "\n";
                        }
                        $foundApi = true;
                    }
                }
            }
        }
    }
}

echo "\n=====================================\n";
echo "✅ TEST COMPLETE\n";
echo "=====================================\n";
echo "• All tests used REAL services\n";
echo "• No mock data or fake responses\n";
echo "• Check logs at: " . basename($logFile) . "\n\n";