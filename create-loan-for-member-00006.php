<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\LoansModel;
use App\Models\loans_schedules;
use App\Models\ClientsModel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== Creating Sample Loan for Member 00006 ===\n\n";

try {
    // Find member 00006
    $member = ClientsModel::where('client_number', '00006')->first();
    if (!$member) {
        echo "Member 00006 not found!\n";
        exit;
    }
    
    echo "Found member: " . $member->first_name . " " . $member->last_name . " (" . $member->client_number . ")\n";

    // Check if member already has a loan
    $existingLoan = LoansModel::where('client_number', $member->client_number)->first();
    if ($existingLoan) {
        echo "Member already has a loan: " . $existingLoan->loan_id . " (ID: " . $existingLoan->id . ")\n";
        echo "Updating existing loan...\n";
        $loan = $existingLoan;
    } else {
        echo "Creating new loan for member...\n";
        $loan = null;
    }

    // Create or update loan
    if (!$loan) {
        // Find the next available ID to avoid sequence issues
        $maxId = LoansModel::max('id') ?? 0;
        $nextId = $maxId + 1;
        
        // Create new loan
        $loan = LoansModel::create([
            'id' => $nextId,
            'loan_id' => 'LOAN' . $member->client_number,
            'client_number' => $member->client_number,
            'principle' => 2000000, // 2M TZS
            'interest' => 15.00, // 15% interest rate
            'status' => 'ACTIVE',
            'branch_id' => 1,
            'loan_sub_product' => 1,
            'approved_loan_value' => 2000000,
            'approved_term' => 24, // 24 months
            'disbursement_date' => now()->subMonths(3), // Disbursed 3 months ago
            'monthly_installment' => 96667, // Monthly payment
            'tenure' => 24,
            'loan_type_2' => 'New',
            'heath' => 'GOOD',
            'pay_method' => 'internal_transfer',
            'supervisor_id' => 1
        ]);
        echo "New loan created: " . $loan->loan_id . " (ID: " . $loan->id . ")\n";
    } else {
        // Update existing loan
        $loan->update([
            'status' => 'ACTIVE',
            'principle' => 2000000,
            'interest' => 15.00,
            'approved_loan_value' => 2000000,
            'approved_term' => 24,
            'disbursement_date' => now()->subMonths(3),
            'monthly_installment' => 96667,
            'tenure' => 24
        ]);
        echo "Existing loan updated: " . $loan->loan_id . " (ID: " . $loan->id . ")\n";
    }

    // Clear existing schedules for this loan
    loans_schedules::where('loan_id', $loan->loan_id)->delete();
    echo "Cleared existing schedules\n";

    // Create sample schedules with some in arrears
    $installmentAmount = 96667; // Monthly installment
    $startDate = now()->subMonths(3); // Start from disbursement date
    $totalSchedules = 24;
    
    echo "Creating " . $totalSchedules . " loan schedules...\n";
    
    for ($i = 1; $i <= $totalSchedules; $i++) {
        $installmentDate = $startDate->copy()->addMonths($i);
        $isOverdue = $installmentDate->isPast();
        $daysInArrears = $isOverdue ? now()->diffInDays($installmentDate) : 0;
        
        // Simulate different payment scenarios
        if ($i <= 3) {
            // First 3 months: fully paid
            $paymentMade = $installmentAmount;
            $completionStatus = 'COMPLETED';
            $status = 'COMPLETED';
        } elseif ($i <= 6) {
            // Months 4-6: partially paid (some arrears)
            $paymentMade = rand(50000, $installmentAmount - 10000);
            $completionStatus = 'PENDING';
            $status = 'OVERDUE';
        } else {
            // Months 7+: no payment (full arrears)
            $paymentMade = 0;
            $completionStatus = 'PENDING';
            $status = 'OVERDUE';
        }
        
        $amountInArrears = max(0, $installmentAmount - $paymentMade);
        $interestAmount = $installmentAmount * 0.15 / 12; // Monthly interest
        $principalAmount = $installmentAmount - $interestAmount;
        
        $openingBalance = $installmentAmount * ($totalSchedules - $i + 1);
        $closingBalance = $installmentAmount * ($totalSchedules - $i);
        
        $schedule = loans_schedules::create([
            'loan_id' => $loan->loan_id,
            'installment' => $installmentAmount,
            'interest' => $interestAmount,
            'principle' => $principalAmount,
            'opening_balance' => $openingBalance,
            'closing_balance' => $closingBalance,
            'installment_date' => $installmentDate,
            'next_check_date' => $installmentDate->addDays(1),
            'payment' => $paymentMade,
            'days_in_arrears' => $daysInArrears,
            'amount_in_arrears' => $amountInArrears,
            'completion_status' => $completionStatus,
            'status' => $status,
            'member_number' => $member->client_number
        ]);
        
        $statusText = $isOverdue ? "OVERDUE" : "PENDING";
        echo "Schedule " . $i . ": " . $installmentDate->format('Y-m-d') . 
             " (Status: " . $statusText . ", Payment: " . number_format($paymentMade, 2) . 
             ", Arrears: " . number_format($amountInArrears, 2) . 
             ", Days: " . $daysInArrears . ")\n";
    }

    echo "\n=== Loan and Schedules Created Successfully ===\n";
    echo "Member: " . $member->first_name . " " . $member->last_name . " (" . $member->client_number . ")\n";
    echo "Loan ID: " . $loan->id . "\n";
    echo "Loan Number: " . $loan->loan_id . "\n";
    echo "Amount: " . number_format($loan->principle, 2) . " TZS\n";
    echo "Term: " . $loan->approved_term . " months\n";
    echo "Monthly Installment: " . number_format($loan->monthly_installment, 2) . " TZS\n";
    echo "Schedules created: " . $totalSchedules . "\n";
    echo "Disbursement Date: " . $loan->disbursement_date . "\n";

    // Calculate and display arrears summary
    echo "\n=== Arrears Summary ===\n";
    $arrearsService = new App\Services\ArrearsCalculationService();
    $loanArrears = $arrearsService->calculateLoanArrears($loan->id);
    
    if ($loanArrears) {
        echo "Total Amount in Arrears: " . number_format($loanArrears['total_amount_in_arrears'], 2) . " TZS\n";
        echo "Max Days in Arrears: " . $loanArrears['max_days_in_arrears'] . " days\n";
        echo "Overdue Schedules: " . $loanArrears['overdue_schedules_count'] . "\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
