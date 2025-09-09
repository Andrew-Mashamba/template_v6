<?php

/**
 * Test script for Transaction Notification System
 * Tests all three scenarios:
 * 1. Internal-to-Internal (both non-member accounts)
 * 2. Member-to-Member (both member accounts)
 * 3. Mixed (one member, one internal)
 */

require_once 'vendor/autoload.php';

use App\Services\TransactionPostingService;
use App\Models\AccountsModel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "\n========================================\n";
echo "Transaction Notification System Test\n";
echo "========================================\n\n";

$service = new TransactionPostingService();

// Test 1: Internal-to-Internal Transaction
echo "Test 1: Internal-to-Internal Transaction\n";
echo "-----------------------------------------\n";
try {
    $data = [
        'first_account' => '0101500056005618',  // RENOVATION EXPENSES (Internal)
        'second_account' => '0101100010001030', // OPERATIONAL ACCOUNT - BANK B (Internal)
        'amount' => 100.00,
        'narration' => 'TEST 1: Internal to Internal - Control emails should be notified',
        'action' => 'test_notification'
    ];
    
    $result = $service->postTransaction($data);
    echo "✅ Transaction posted successfully\n";
    echo "   Reference: " . $result['reference_number'] . "\n";
    echo "   Expected: Control emails notified\n\n";
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n\n";
}

// Test 2: Member-to-Member Transaction (if member accounts exist)
echo "Test 2: Member-to-Member Transaction\n";
echo "-----------------------------------------\n";
try {
    // First, let's find member accounts
    $memberAccounts = AccountsModel::whereNotNull('client_number')
        ->where('client_number', '!=', '0000')
        ->where('client_number', '!=', '0')
        ->where('status', 'ACTIVE')
        ->limit(2)
        ->get();
    
    if ($memberAccounts->count() >= 2) {
        $data = [
            'first_account' => $memberAccounts[0]->account_number,
            'second_account' => $memberAccounts[1]->account_number,
            'amount' => 50.00,
            'narration' => 'TEST 2: Member to Member - Member accounts should be notified',
            'action' => 'test_notification'
        ];
        
        echo "   Using accounts:\n";
        echo "   - {$memberAccounts[0]->account_name} (Member: {$memberAccounts[0]->client_number})\n";
        echo "   - {$memberAccounts[1]->account_name} (Member: {$memberAccounts[1]->client_number})\n";
        
        $result = $service->postTransaction($data);
        echo "✅ Transaction posted successfully\n";
        echo "   Reference: " . $result['reference_number'] . "\n";
        echo "   Expected: Member accounts notified via SMS/Email\n\n";
    } else {
        echo "⚠️  Skipped: Not enough member accounts found\n\n";
    }
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n\n";
}

// Test 3: Mixed Transaction (Member + Internal)
echo "Test 3: Mixed Transaction (Member + Internal)\n";
echo "-----------------------------------------\n";
try {
    // Find one member account
    $memberAccount = AccountsModel::whereNotNull('client_number')
        ->where('client_number', '!=', '0000')
        ->where('client_number', '!=', '0')
        ->where('status', 'ACTIVE')
        ->first();
    
    if ($memberAccount) {
        $data = [
            'first_account' => $memberAccount->account_number,  // Member account
            'second_account' => '0101100010001030', // OPERATIONAL ACCOUNT - BANK B (Internal)
            'amount' => 75.00,
            'narration' => 'TEST 3: Mixed (Member + Internal) - BOTH member and control emails should be notified',
            'action' => 'test_notification'
        ];
        
        echo "   Using accounts:\n";
        echo "   - {$memberAccount->account_name} (Member: {$memberAccount->client_number})\n";
        echo "   - OPERATIONAL ACCOUNT - BANK B (Internal)\n";
        
        $result = $service->postTransaction($data);
        echo "✅ Transaction posted successfully\n";
        echo "   Reference: " . $result['reference_number'] . "\n";
        echo "   Expected: BOTH member account AND control emails notified\n\n";
    } else {
        echo "⚠️  Skipped: No member accounts found\n\n";
    }
} catch (Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n\n";
}

// Check notification queue status
echo "========================================\n";
echo "Checking Notification Queue Status\n";
echo "========================================\n";

$pendingJobs = DB::table('jobs')
    ->where('queue', 'notifications')
    ->count();

echo "Pending notification jobs: $pendingJobs\n";

if ($pendingJobs > 0) {
    echo "\n⏳ Notifications are queued and will be processed by the queue workers.\n";
    echo "   Run: php artisan queue:work notifications\n";
    echo "   Or check: tail -f storage/logs/queue-notifications.log\n";
}

// Show recent notifications from the database
echo "\n========================================\n";
echo "Recent Email Notifications (Last 5)\n";
echo "========================================\n";

$recentEmails = DB::table('emails')
    ->orderBy('created_at', 'desc')
    ->limit(5)
    ->get(['id', 'recipient_email', 'subject', 'is_sent', 'created_at']);

foreach ($recentEmails as $email) {
    $status = $email->is_sent ? '✅ Sent' : '⏳ Pending';
    echo "{$status} | {$email->recipient_email} | " . substr($email->subject, 0, 50) . "...\n";
}

echo "\n✅ Test completed!\n\n";