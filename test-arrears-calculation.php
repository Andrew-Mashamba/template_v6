<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Services\ArrearsCalculationService;
use App\Models\LoansModel;
use App\Models\loans_schedules;
use Illuminate\Support\Facades\DB;

echo "=== Arrears Calculation Test ===\n\n";

try {
    $arrearsService = new ArrearsCalculationService();

    // Test 1: Get arrears summary
    echo "1. Testing Arrears Summary:\n";
    $summary = $arrearsService->getArrearsSummary();
    if ($summary) {
        echo "   - Loans in arrears: " . ($summary->loans_in_arrears ?? 0) . "\n";
        echo "   - Clients in arrears: " . ($summary->clients_in_arrears ?? 0) . "\n";
        echo "   - Total arrears amount: " . number_format($summary->total_arrears_amount ?? 0, 2) . "\n";
        echo "   - Max days in arrears: " . ($summary->max_days_in_arrears ?? 0) . "\n";
        echo "   - Avg days in arrears: " . round($summary->avg_days_in_arrears ?? 0, 2) . "\n";
    } else {
        echo "   - No arrears data found\n";
    }

    // Test 2: Get sample loan with arrears
    echo "\n2. Testing Sample Loan Arrears:\n";
    $sampleLoan = LoansModel::where('status', 'ACTIVE')
        ->whereHas('schedules', function($query) {
            $query->where('days_in_arrears', '>', 0);
        })
        ->first();

    if ($sampleLoan) {
        echo "   - Found loan: " . $sampleLoan->loan_number . "\n";
        echo "   - Client: " . $sampleLoan->client_number . "\n";
        echo "   - Amount: " . number_format($sampleLoan->amount, 2) . "\n";
        
        $loanArrears = $arrearsService->calculateLoanArrears($sampleLoan->id);
        if ($loanArrears) {
            echo "   - Max days in arrears: " . $loanArrears['max_days_in_arrears'] . "\n";
            echo "   - Total amount in arrears: " . number_format($loanArrears['total_amount_in_arrears'], 2) . "\n";
            echo "   - Overdue schedules: " . $loanArrears['overdue_schedules_count'] . "\n";
        }
    } else {
        echo "   - No loans with arrears found\n";
    }

    // Test 3: Check loans_schedules table structure
    echo "\n3. Testing Database Structure:\n";
    $sampleSchedule = loans_schedules::first();
    if ($sampleSchedule) {
        echo "   - Sample schedule found\n";
        echo "   - Loan ID: " . $sampleSchedule->loan_id . "\n";
        echo "   - Installment date: " . $sampleSchedule->installment_date . "\n";
        echo "   - Days in arrears: " . ($sampleSchedule->days_in_arrears ?? 0) . "\n";
        echo "   - Amount in arrears: " . number_format($sampleSchedule->amount_in_arrears ?? 0, 2) . "\n";
        echo "   - Completion status: " . ($sampleSchedule->completion_status ?? 'N/A') . "\n";
    } else {
        echo "   - No schedules found in database\n";
    }

    // Test 4: Test client arrears calculation
    echo "\n4. Testing Client Arrears:\n";
    $sampleClient = DB::table('loans')
        ->where('status', 'ACTIVE')
        ->first();

    if ($sampleClient) {
        echo "   - Testing client: " . $sampleClient->client_number . "\n";
        $clientArrears = $arrearsService->calculateClientArrears($sampleClient->client_number);
        if ($clientArrears) {
            echo "   - Total loans: " . $clientArrears['total_loans'] . "\n";
            echo "   - Loans with arrears: " . $clientArrears['loans_with_arrears'] . "\n";
            echo "   - Total amount in arrears: " . number_format($clientArrears['total_amount_in_arrears'], 2) . "\n";
            echo "   - Max days in arrears: " . $clientArrears['max_days_in_arrears'] . "\n";
        }
    } else {
        echo "   - No active loans found\n";
    }

    echo "\n=== Test Completed Successfully ===\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
