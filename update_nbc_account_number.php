<?php

/**
 * Update NBC account number in bank_accounts table
 * Set account_number to 011191000035 (CBN MICROFINANCE)
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

use Illuminate\Support\Facades\DB;
use App\Models\BankAccount;

echo "\n";
echo "========================================\n";
echo "  UPDATE NBC ACCOUNT NUMBER\n";
echo "========================================\n";
echo "\n";

try {
    // First, let's find NBC accounts
    echo "Step 1: Finding NBC bank accounts...\n";
    echo str_repeat("-", 40) . "\n";
    
    $nbcAccounts = BankAccount::where(function($query) {
        $query->where('bank_name', 'LIKE', '%NBC%')
              ->orWhere('bank_name', 'LIKE', '%National Bank%')
              ->orWhere('bank_name', 'LIKE', '%NATIONAL BANK%');
    })->get();
    
    if ($nbcAccounts->isEmpty()) {
        echo "No NBC accounts found. Creating one...\n\n";
        
        // Create NBC account if it doesn't exist
        $nbcAccount = BankAccount::create([
            'bank_name' => 'NBC',
            'account_name' => 'CBN MICROFINANCE',
            'account_number' => '011191000035',
            'branch_name' => 'CORPORATE',
            'swift_code' => 'NLCBTZTX',
            'currency' => 'TZS',
            'opening_balance' => 430025,
            'current_balance' => 430025,
            'internal_mirror_account_number' => '1001',
            'status' => 'active',
            'description' => 'NBC Main Operations Account - CBN MICROFINANCE',
            'account_type' => 'main_operations'
        ]);
        
        echo "✅ Created NBC account:\n";
        echo "   ID: " . $nbcAccount->id . "\n";
        echo "   Bank: " . $nbcAccount->bank_name . "\n";
        echo "   Account Name: " . $nbcAccount->account_name . "\n";
        echo "   Account Number: " . $nbcAccount->account_number . "\n";
        echo "   Current Balance: TZS " . number_format($nbcAccount->current_balance, 2) . "\n";
        
    } else {
        echo "Found " . count($nbcAccounts) . " NBC account(s):\n\n";
        
        foreach ($nbcAccounts as $index => $account) {
            $num = $index + 1;
            echo "Account #{$num}:\n";
            echo "   ID: " . $account->id . "\n";
            echo "   Bank: " . $account->bank_name . "\n";
            echo "   Account Name: " . $account->account_name . "\n";
            echo "   Current Account Number: " . $account->account_number . "\n";
            echo "   Current Balance: TZS " . number_format($account->current_balance, 2) . "\n";
            echo "\n";
        }
        
        echo str_repeat("-", 40) . "\n";
        echo "Step 2: Updating account number(s)...\n";
        echo str_repeat("-", 40) . "\n\n";
        
        $updateCount = 0;
        foreach ($nbcAccounts as $account) {
            $oldAccountNumber = $account->account_number;
            
            // Update the account number
            $account->account_number = '011191000035';
            
            // Also update account name if needed
            if (empty($account->account_name) || $account->account_name == 'NBC Account') {
                $account->account_name = 'CBN MICROFINANCE';
            }
            
            // Update branch if needed
            if (empty($account->branch_name)) {
                $account->branch_name = 'CORPORATE';
            }
            
            $account->save();
            $updateCount++;
            
            echo "✅ Updated Account ID " . $account->id . ":\n";
            echo "   Old Account Number: " . $oldAccountNumber . "\n";
            echo "   New Account Number: " . $account->account_number . "\n";
            echo "   Account Name: " . $account->account_name . "\n";
            echo "   Branch: " . $account->branch_name . "\n";
            echo "\n";
        }
        
        echo "Total accounts updated: " . $updateCount . "\n";
    }
    
    echo "\n" . str_repeat("=", 40) . "\n";
    echo "Step 3: Verification\n";
    echo str_repeat("=", 40) . "\n\n";
    
    // Verify the update
    $verifyAccount = BankAccount::where('account_number', '011191000035')->first();
    
    if ($verifyAccount) {
        echo "✅ Verification Successful!\n\n";
        echo "NBC Account Details:\n";
        echo "   ID: " . $verifyAccount->id . "\n";
        echo "   Bank Name: " . $verifyAccount->bank_name . "\n";
        echo "   Account Name: " . $verifyAccount->account_name . "\n";
        echo "   Account Number: " . $verifyAccount->account_number . "\n";
        echo "   Branch: " . $verifyAccount->branch_name . "\n";
        echo "   Swift Code: " . $verifyAccount->swift_code . "\n";
        echo "   Currency: " . $verifyAccount->currency . "\n";
        echo "   Current Balance: TZS " . number_format($verifyAccount->current_balance, 2) . "\n";
        echo "   Status: " . $verifyAccount->status . "\n";
        echo "   Account Type: " . $verifyAccount->account_type . "\n";
        
        // Now let's also fetch the real balance from NBC API
        echo "\n" . str_repeat("-", 40) . "\n";
        echo "Fetching real-time balance from NBC API...\n";
        echo str_repeat("-", 40) . "\n\n";
        
        $service = new \App\Services\Payments\InternalFundsTransferService();
        $lookup = $service->lookupAccount('011191000035', 'source');
        
        if ($lookup['success']) {
            echo "NBC API Response:\n";
            echo "   Account Name: " . $lookup['account_name'] . "\n";
            echo "   Available Balance: TZS " . number_format($lookup['available_balance'], 2) . "\n";
            echo "   Branch: " . $lookup['branch_name'] . "\n";
            echo "   Status: " . $lookup['account_status'] . "\n";
            
            // Update the balance in database if different
            if (isset($lookup['available_balance']) && $lookup['available_balance'] != $verifyAccount->current_balance) {
                $verifyAccount->current_balance = $lookup['available_balance'];
                $verifyAccount->save();
                echo "\n✅ Updated balance in database to match NBC API\n";
            }
        } else {
            echo "⚠️ Could not fetch balance from NBC API\n";
        }
        
    } else {
        echo "❌ Verification Failed: Account not found with number 011191000035\n";
    }
    
    echo "\n========================================\n";
    echo "  UPDATE COMPLETED SUCCESSFULLY\n";
    echo "========================================\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}