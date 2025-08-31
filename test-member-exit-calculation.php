<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ClientsModel;

echo "=== Testing Member Exit Calculation ===\n\n";

try {
    // Test with member 00006
    $member = ClientsModel::where('client_number', '00006')->with([
        'accounts', 
        'loans.loanAccount', 
        'bills.service', 
        'dividends', 
        'interestPayables'
    ])->first();

    if ($member) {
        echo "Member: " . $member->first_name . " " . $member->last_name . " (" . $member->client_number . ")\n\n";

        // Calculate exit amount
        $totalDividends = $member->dividends->sum('amount');
        $totalInterest = $member->interestPayables->sum('interest_payable');
        $totalAccountsBalance = $member->accounts->sum('balance');
        $totalLoanBalance = $member->loans->sum(function($loan) {
            return $loan->loanAccount->balance ?? 0;
        });
        $unpaidBills = $member->bills->where('status', '!=', 'PAID');
        $totalUnpaidBills = $unpaidBills->sum('amount_due');
        $exitAmount = $totalDividends + $totalInterest + $totalAccountsBalance - $totalLoanBalance - $totalUnpaidBills;

        echo "=== EXIT CALCULATION BREAKDOWN ===\n\n";

        echo "CREDITS (+):\n";
        echo "- Dividends: " . number_format($totalDividends, 2) . " (" . $member->dividends->count() . " records)\n";
        echo "- Interest on Savings: " . number_format($totalInterest, 2) . " (" . $member->interestPayables->count() . " records)\n";
        echo "- Accounts Balance: " . number_format($totalAccountsBalance, 2) . " (" . $member->accounts->count() . " accounts)\n";
        echo "Total Credits: " . number_format($totalDividends + $totalInterest + $totalAccountsBalance, 2) . "\n\n";

        echo "DEBITS (-):\n";
        echo "- Loan Account Balance: " . number_format($totalLoanBalance, 2) . " (" . $member->loans->count() . " loans)\n";
        echo "- Unpaid Control Numbers: " . number_format($totalUnpaidBills, 2) . " (" . $unpaidBills->count() . " bills)\n";
        echo "Total Debits: " . number_format($totalLoanBalance + $totalUnpaidBills, 2) . "\n\n";

        echo "=== FINAL RESULT ===\n";
        echo "Exit Amount: " . number_format($exitAmount, 2) . "\n";
        
        if ($exitAmount > 0) {
            echo "Status: Member will RECEIVE " . number_format($exitAmount, 2) . "\n";
        } elseif ($exitAmount < 0) {
            echo "Status: Member OWES " . number_format(abs($exitAmount), 2) . "\n";
        } else {
            echo "Status: No settlement amount\n";
        }

        echo "\n=== DETAILED BREAKDOWN ===\n\n";

        // Show accounts
        echo "ACCOUNTS:\n";
        foreach ($member->accounts as $account) {
            echo "- " . $account->account_name . " (#" . $account->account_number . "): " . number_format($account->balance, 2) . "\n";
        }

        echo "\nLOANS:\n";
        foreach ($member->loans as $loan) {
            echo "- " . $loan->loan_id . " (Principal: " . number_format($loan->principle, 2) . ", Balance: " . number_format($loan->loanAccount->balance ?? 0, 2) . ")\n";
        }

        echo "\nUNPAID BILLS:\n";
        foreach ($unpaidBills as $bill) {
            echo "- " . $bill->control_number . " (" . ($bill->service->name ?? 'N/A') . "): " . number_format($bill->amount_due, 2) . "\n";
        }

        echo "\nDIVIDENDS:\n";
        foreach ($member->dividends as $dividend) {
            echo "- " . $dividend->year . " (" . $dividend->status . "): " . number_format($dividend->amount, 2) . "\n";
        }

        echo "\nINTEREST RECORDS:\n";
        foreach ($member->interestPayables as $interest) {
            echo "- " . $interest->account_type . ": " . number_format($interest->interest_payable, 2) . "\n";
        }

    } else {
        echo "Member 00006 not found\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
