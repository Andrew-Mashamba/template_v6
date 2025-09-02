<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\LoanRepaymentService;
use Illuminate\Support\Facades\Log;

// Find an active loan
$loan = DB::table('loans')
    ->where('status', 'ACTIVE')
    ->where('loan_id', 'LN202508311692')
    ->first();

if (!$loan) {
    echo "Loan not found\n";
    exit;
}

echo "Testing multiple payments for loan:\n";
echo "- Loan ID: {$loan->id}\n";
echo "- Loan String ID: {$loan->loan_id}\n\n";

// Get unpaid schedules
$unpaidSchedules = DB::table('loans_schedules')
    ->where('loan_id', (string)$loan->id)
    ->where('completion_status', '!=', 'PAID')
    ->orderBy('installment_date', 'asc')
    ->limit(3)
    ->get();

echo "Next 3 unpaid schedules:\n";
foreach ($unpaidSchedules as $index => $schedule) {
    echo ($index + 1) . ". Schedule ID {$schedule->id}: ";
    echo "Installment: " . number_format($schedule->installment, 2) . " ";
    echo "(Interest: " . number_format($schedule->interest, 2) . ", ";
    echo "Principal: " . number_format($schedule->principle, 2) . ") ";
    echo "Status: {$schedule->completion_status}\n";
}

if ($unpaidSchedules->count() > 0) {
    // Make a payment for 1.5 installments
    $paymentAmount = $unpaidSchedules->first()->installment * 1.5;
    
    echo "\nMaking payment of: " . number_format($paymentAmount, 2) . " (1.5 installments)\n\n";
    
    try {
        $repaymentService = new LoanRepaymentService();
        
        echo "Processing payment...\n";
        $result = $repaymentService->processRepayment(
            $loan->loan_id,
            $paymentAmount,
            'CASH',
            ['narration' => 'Test multiple payment']
        );
        
        echo "Payment successful!\n";
        echo "Receipt: {$result['receipt_number']}\n";
        echo "Amount Paid: " . number_format($result['amount_paid'], 2) . "\n";
        echo "Outstanding: " . number_format($result['outstanding_balance']['total'], 2) . "\n\n";
        
        // Check schedules after payment
        $updatedSchedules = DB::table('loans_schedules')
            ->where('loan_id', (string)$loan->id)
            ->whereIn('id', $unpaidSchedules->pluck('id'))
            ->orderBy('installment_date', 'asc')
            ->get();
            
        echo "Schedules after payment:\n";
        foreach ($updatedSchedules as $index => $schedule) {
            echo ($index + 1) . ". Schedule ID {$schedule->id}: ";
            echo "Status: {$schedule->completion_status}, ";
            echo "Interest Paid: " . number_format($schedule->interest_payment ?? 0, 2) . "/" . number_format($schedule->interest, 2) . ", ";
            echo "Principal Paid: " . number_format($schedule->principle_payment ?? 0, 2) . "/" . number_format($schedule->principle, 2) . "\n";
        }
        
        // Check loan status
        $updatedLoan = DB::table('loans')
            ->where('id', $loan->id)
            ->first();
            
        echo "\nLoan status after payment: {$updatedLoan->status}\n";
        
        // Check remaining unpaid schedules
        $remainingUnpaid = DB::table('loans_schedules')
            ->where('loan_id', (string)$loan->id)
            ->where('completion_status', '!=', 'PAID')
            ->count();
            
        echo "Remaining unpaid schedules: {$remainingUnpaid}\n";
        
    } catch (Exception $e) {
        echo "Payment failed: " . $e->getMessage() . "\n";
    }
} else {
    echo "No unpaid schedules found for this loan\n";
}