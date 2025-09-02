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
    ->first();

if (!$loan) {
    echo "No active loans found\n";
    exit;
}

echo "Testing loan payment for:\n";
echo "- Loan ID: {$loan->id}\n";
echo "- Loan String ID: {$loan->loan_id}\n";
echo "- Client: {$loan->client_number}\n";
echo "- Principal: {$loan->principle}\n\n";

// Check schedules
$schedulesNumeric = DB::table('loans_schedules')
    ->where('loan_id', (string)$loan->id)
    ->get();

$schedulesString = DB::table('loans_schedules')
    ->where('loan_id', $loan->loan_id)
    ->get();

echo "Schedules found:\n";
echo "- With numeric ID ({$loan->id}): " . $schedulesNumeric->count() . "\n";
echo "- With string ID ({$loan->loan_id}): " . $schedulesString->count() . "\n\n";

// Show first schedule details
$schedules = $schedulesNumeric->count() > 0 ? $schedulesNumeric : $schedulesString;
if ($schedules->count() > 0) {
    $firstSchedule = $schedules->first();
    echo "First schedule details:\n";
    echo "- ID: {$firstSchedule->id}\n";
    echo "- Loan ID in schedule: {$firstSchedule->loan_id}\n";
    echo "- Installment: {$firstSchedule->installment}\n";
    echo "- Interest: {$firstSchedule->interest}\n";
    echo "- Principal: {$firstSchedule->principle}\n";
    echo "- Status: {$firstSchedule->completion_status}\n";
    echo "- Interest Payment: " . ($firstSchedule->interest_payment ?? 'NULL') . "\n";
    echo "- Principal Payment: " . ($firstSchedule->principle_payment ?? 'NULL') . "\n\n";
    
    // Calculate payment amount (use first installment amount)
    $paymentAmount = $firstSchedule->installment;
    
    echo "Making payment of: {$paymentAmount}\n\n";
    
    // Process payment
    try {
        $repaymentService = new LoanRepaymentService();
        
        echo "Processing payment...\n";
        $result = $repaymentService->processRepayment(
            $loan->loan_id,
            $paymentAmount,
            'CASH',
            ['narration' => 'Test payment']
        );
        
        echo "Payment successful!\n";
        echo "Receipt: {$result['receipt_number']}\n";
        echo "Amount Paid: {$result['amount_paid']}\n";
        echo "Outstanding: {$result['outstanding_balance']['total']}\n\n";
        
        // Check schedule after payment - get the first non-completed schedule
        $updatedSchedule = DB::table('loans_schedules')
            ->where('loan_id', $firstSchedule->loan_id)
            ->where('completion_status', '!=', 'COMPLETED')
            ->orderBy('installment_date', 'asc')
            ->first();
            
        echo "Schedule after payment:\n";
        echo "- Status: {$updatedSchedule->completion_status}\n";
        echo "- Interest Payment: " . ($updatedSchedule->interest_payment ?? 'NULL') . "\n";
        echo "- Principal Payment: " . ($updatedSchedule->principle_payment ?? 'NULL') . "\n";
        echo "- Total Payment: " . ($updatedSchedule->payment ?? 'NULL') . "\n\n";
        
        // Check loan status
        $updatedLoan = DB::table('loans')
            ->where('id', $loan->id)
            ->first();
            
        echo "Loan status after payment: {$updatedLoan->status}\n";
        
    } catch (Exception $e) {
        echo "Payment failed: " . $e->getMessage() . "\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "Trace:\n" . $e->getTraceAsString() . "\n";
    }
} else {
    echo "No schedules found for this loan\n";
}