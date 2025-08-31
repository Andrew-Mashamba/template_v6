<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\LoansModel;
use App\Models\AccountsModel;
use App\Models\ClientsModel;
use Illuminate\Support\Facades\DB;

echo "=== Setting up Loan Account Relationships ===\n\n";

try {
    // Get all loans
    $loans = LoansModel::all();
    echo "Found " . $loans->count() . " loans\n\n";

    foreach ($loans as $loan) {
        echo "Processing loan: " . $loan->loan_id . " (Client: " . $loan->client_number . ")\n";
        
        // Check if loan already has an account number
        if ($loan->loan_account_number) {
            echo "  - Already has account number: " . $loan->loan_account_number . "\n";
            continue;
        }

        // Find client accounts
        $clientAccounts = AccountsModel::where('client_number', $loan->client_number)->get();
        echo "  - Found " . $clientAccounts->count() . " accounts for client\n";

        if ($clientAccounts->count() > 0) {
            // Use the first account as loan account
            $loanAccount = $clientAccounts->first();
            $loan->update(['loan_account_number' => $loanAccount->account_number]);
            echo "  - Set loan account number to: " . $loanAccount->account_number . "\n";
            echo "  - Account balance: " . number_format($loanAccount->balance, 2) . "\n";
        } else {
            // Create a new loan account
            $newAccountNumber = '010000' . str_pad($loan->client_number, 6, '0', STR_PAD_LEFT) . '01';
            
            $newAccount = AccountsModel::create([
                'institution_number' => '01',
                'branch_number' => '01',
                'client_number' => $loan->client_number,
                'account_use' => 'loan',
                'product_number' => '1000',
                'sub_product_number' => '1001',
                'major_category_code' => '1000',
                'category_code' => '1000',
                'sub_category_code' => '1001',
                'member_account_code' => '01',
                'account_name' => 'LOAN ACCOUNT: ' . $loan->loan_id,
                'account_number' => $newAccountNumber,
                'status' => 'ACTIVE',
                'balance' => $loan->principle, // Set balance to loan principal
                'notes' => 'Loan account for ' . $loan->loan_id,
                'account_level' => 3,
                'branch_id' => $loan->branch_id ?? 1
            ]);

            $loan->update(['loan_account_number' => $newAccountNumber]);
            echo "  - Created new loan account: " . $newAccountNumber . "\n";
            echo "  - Account balance: " . number_format($newAccount->balance, 2) . "\n";
        }
        
        echo "\n";
    }

    echo "=== Loan Account Relationships Setup Complete ===\n";
    
    // Test the relationships
    echo "\n=== Testing Relationships ===\n";
    $testLoans = LoansModel::with('loanAccount')->limit(3)->get();
    foreach ($testLoans as $loan) {
        echo "Loan: " . $loan->loan_id . "\n";
        echo "  - Principal: " . number_format($loan->principle, 2) . "\n";
        echo "  - Account Number: " . ($loan->loan_account_number ?? 'NULL') . "\n";
        if ($loan->loanAccount) {
            echo "  - Account Balance: " . number_format($loan->loanAccount->balance, 2) . "\n";
        } else {
            echo "  - No account found\n";
        }
        echo "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
