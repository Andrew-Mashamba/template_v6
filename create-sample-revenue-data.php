<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Creating Sample Revenue Data ===\n\n";

try {
    // Get member 00006
    $member = DB::table('clients')->where('client_number', '00006')->first();
    if (!$member) {
        echo "Member 00006 not found\n";
        exit;
    }

    echo "Member: " . $member->first_name . " " . $member->last_name . " (ID: " . $member->id . ")\n\n";

    // Check existing dividend records
    $existingDividends = DB::table('dividends')->where('member_id', $member->id)->count();
    echo "Existing dividends for member: " . $existingDividends . "\n";

    if ($existingDividends == 0) {
        // Create sample dividend records
        $dividends = [
            [
                'member_id' => $member->id,
                'year' => 2023,
                'rate' => 5.5,
                'amount' => 2750.00,
                'status' => 'PAID',
                'payment_mode' => 'BANK_TRANSFER',
                'narration' => 'Annual dividend payment for 2023',
                'branch_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'member_id' => $member->id,
                'year' => 2024,
                'rate' => 6.0,
                'amount' => 3000.00,
                'status' => 'PENDING',
                'payment_mode' => 'BANK_TRANSFER',
                'narration' => 'Annual dividend payment for 2024',
                'branch_id' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        ];

        foreach ($dividends as $dividend) {
            DB::table('dividends')->insert($dividend);
            echo "Created dividend: " . $dividend['year'] . " - " . number_format($dividend['amount'], 2) . " (" . $dividend['status'] . ")\n";
        }
    }

    // Check existing interest records
    $existingInterest = DB::table('interest_payables')->where('member_id', $member->id)->count();
    echo "\nExisting interest records for member: " . $existingInterest . "\n";

    if ($existingInterest == 0) {
        // Create sample interest records
        $interestRecords = [
            [
                'member_id' => $member->id,
                'account_type' => 'MANDATORY SAVINGS',
                'amount' => 50000.00,
                'interest_rate' => 8.5,
                'deposit_date' => '2024-01-01',
                'maturity_date' => '2024-12-31',
                'payment_frequency' => 'ANNUALLY',
                'accrued_interest' => 4250.00,
                'interest_payable' => 4250.00,
                'branch_id' => 1,
                'created_at' => now()
            ],
            [
                'member_id' => $member->id,
                'account_type' => 'MANDATORY DEPOSITS',
                'amount' => 25000.00,
                'interest_rate' => 10.0,
                'deposit_date' => '2024-06-01',
                'maturity_date' => '2024-12-31',
                'payment_frequency' => 'SEMI_ANNUALLY',
                'accrued_interest' => 1750.00,
                'interest_payable' => 1750.00,
                'branch_id' => 1,
                'created_at' => now()
            ]
        ];

        foreach ($interestRecords as $interest) {
            DB::table('interest_payables')->insert($interest);
            echo "Created interest: " . $interest['account_type'] . " - " . number_format($interest['interest_payable'], 2) . "\n";
        }
    }

    // Display summary
    echo "\n=== Revenue Summary ===\n";
    
    // Calculate total dividends
    $totalDividends = DB::table('dividends')
        ->where('member_id', $member->id)
        ->sum('amount');
    echo "Total Dividends: " . number_format($totalDividends, 2) . "\n";

    // Calculate total interest
    $totalInterest = DB::table('interest_payables')
        ->where('member_id', $member->id)
        ->sum('interest_payable');
    echo "Total Interest on Savings: " . number_format($totalInterest, 2) . "\n";

    // Calculate pending dividends
    $pendingDividends = DB::table('dividends')
        ->where('member_id', $member->id)
        ->where('status', 'PENDING')
        ->sum('amount');
    echo "Pending Dividends: " . number_format($pendingDividends, 2) . "\n";

    // Calculate paid dividends
    $paidDividends = DB::table('dividends')
        ->where('member_id', $member->id)
        ->where('status', 'PAID')
        ->sum('amount');
    echo "Paid Dividends: " . number_format($paidDividends, 2) . "\n";

    echo "\nSample revenue data created successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
