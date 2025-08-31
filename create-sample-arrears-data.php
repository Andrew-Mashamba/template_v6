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

echo "=== Creating Sample Arrears Data ===\n\n";

try {
    // First, let's check if we have any clients
    $clients = ClientsModel::limit(3)->get();
    if ($clients->isEmpty()) {
        echo "No clients found. Creating sample client...\n";
        
        // Create a sample client
        $client = ClientsModel::create([
            'client_number' => 'TEST001',
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john.doe@test.com',
            'phone_number' => '+255712345678',
            'client_status' => 'ACTIVE',
            'branch_id' => 1
        ]);
        echo "Created client: " . $client->client_number . "\n";
    } else {
        $client = $clients->first();
        echo "Using existing client: " . $client->client_number . "\n";
    }

    // Use existing loan - try different client number formats
    $loan = LoansModel::where('client_number', $client->client_number)->first();
    
    if (!$loan) {
        // Try with different client number format
        $loan = LoansModel::where('client_number', '1000' . substr($client->client_number, -1))->first();
    }
    
    if (!$loan) {
        // Use any existing loan
        $loan = LoansModel::first();
    }
    
    if (!$loan) {
        echo "No loan found for client: " . $client->client_number . "\n";
        exit;
    }
    
    // Update existing loan to ACTIVE status
    $loan->update([
        'status' => 'ACTIVE',
        'principle' => 1000000,
        'approved_loan_value' => 1000000,
        'approved_term' => 12,
        'disbursement_date' => now()->subMonths(6),
        'monthly_installment' => 100000
    ]);
    echo "Existing loan updated: " . $loan->loan_id . " (ID: " . $loan->id . ")\n";

    // Clear existing schedules for this loan
    loans_schedules::where('loan_id', $loan->loan_id)->delete();
    echo "Cleared existing schedules\n";

    // Create sample schedules with some in arrears
    $installmentAmount = 100000; // 100K per month
    $startDate = now()->subMonths(6);
    
    for ($i = 1; $i <= 12; $i++) {
        $installmentDate = $startDate->copy()->addMonths($i);
        $isOverdue = $installmentDate->isPast();
        $daysInArrears = $isOverdue ? now()->diffInDays($installmentDate, false) : 0;
        $paymentMade = $isOverdue ? rand(0, $installmentAmount) : 0; // Random payment for overdue installments
        $amountInArrears = max(0, $installmentAmount - $paymentMade);
        
        $schedule = loans_schedules::create([
            'loan_id' => $loan->loan_id,
            'installment' => $installmentAmount,
            'interest' => 10000, // 10% interest
            'principle' => 90000, // 90% principal
            'opening_balance' => $installmentAmount * (12 - $i + 1),
            'closing_balance' => $installmentAmount * (12 - $i),
            'installment_date' => $installmentDate,
            'next_check_date' => $installmentDate->addDays(1),
            'payment' => $paymentMade,
            'days_in_arrears' => $daysInArrears,
            'amount_in_arrears' => $amountInArrears,
            'completion_status' => $paymentMade >= $installmentAmount ? 'COMPLETED' : 'PENDING',
            'status' => $isOverdue ? 'OVERDUE' : 'PENDING',
            'member_number' => $client->client_number
        ]);
        
        echo "Created schedule " . $i . ": " . $installmentDate->format('Y-m-d') . 
             " (Days in arrears: " . $daysInArrears . ", Amount in arrears: " . number_format($amountInArrears, 2) . ")\n";
    }

    echo "\n=== Sample Data Created Successfully ===\n";
    echo "Loan ID: " . $loan->id . "\n";
    echo "Loan Number: " . $loan->loan_number . "\n";
    echo "Client: " . $client->client_number . "\n";
    echo "Schedules created: 12\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
