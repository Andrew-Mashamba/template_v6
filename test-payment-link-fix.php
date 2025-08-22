<?php

/**
 * Test script to verify payment link generation fix
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\PaymentLinkService;
use Illuminate\Support\Facades\DB;

// Boot Laravel
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "========================================\n";
echo "  Payment Link Generation Test\n";
echo "========================================\n\n";

try {
    // Get loan 7 details
    $loan = DB::table('loans')->find(7);
    if (!$loan) {
        throw new Exception('Loan 7 not found');
    }
    
    $client = DB::table('clients')->where('client_number', $loan->client_number)->first();
    if (!$client) {
        throw new Exception('Client not found');
    }
    
    echo "📋 Loan Details:\n";
    echo "   Loan ID: {$loan->id}\n";
    echo "   Client: {$client->first_name} {$client->last_name}\n";
    echo "   Amount: " . number_format($loan->approved_loan_value) . " TZS\n";
    echo "   Status: {$loan->status}\n\n";
    
    // Get loan schedules
    $schedules = DB::table('loans_schedules')
        ->where('loan_id', $loan->id)
        ->orderBy('installment_date', 'asc')
        ->get();
    
    echo "📅 Repayment Schedule:\n";
    echo "   Total Installments: " . $schedules->count() . "\n";
    
    if ($schedules->count() > 0) {
        $firstSchedule = $schedules->first();
        $lastSchedule = $schedules->last();
        
        echo "   First Installment Date: " . ($firstSchedule->installment_date ?? 'Not set') . "\n";
        echo "   Last Installment Date: " . ($lastSchedule->installment_date ?? 'Not set') . "\n";
        
        // Show first 3 schedules
        echo "\n   First 3 Installments:\n";
        foreach ($schedules->take(3) as $schedule) {
            $amount = ($schedule->principle ?? 0) + ($schedule->interest ?? 0);
            echo "   - Date: " . ($schedule->installment_date ?? 'Not set') . 
                 ", Amount: " . number_format($amount) . " TZS\n";
        }
    }
    
    echo "\n🔗 Generating Payment Link...\n";
    
    // Initialize PaymentLinkService
    $paymentLinkService = new PaymentLinkService();
    
    // Convert client to stdClass for compatibility
    $clientObj = new stdClass();
    foreach ($client as $key => $value) {
        $clientObj->$key = $value;
    }
    
    try {
        $response = $paymentLinkService->generateLoanInstallmentsPaymentLink(
            $loan->id,
            $clientObj,
            $schedules,
            ['description' => 'Test Payment Link Generation - SACCOS Loan Services']
        );
        
        if (isset($response['status']) && $response['status'] === 'success') {
            echo "✅ Payment link generated successfully!\n";
            echo "   Link ID: " . ($response['data']['link_id'] ?? 'N/A') . "\n";
            echo "   Payment URL: " . ($response['data']['payment_url'] ?? 'N/A') . "\n";
            echo "   Total Amount: " . number_format($response['data']['total_amount'] ?? 0) . " TZS\n";
            echo "   Items Count: " . count($response['data']['items'] ?? []) . "\n";
            
            if (isset($response['data']['items']) && count($response['data']['items']) > 0) {
                echo "\n   Payment Items:\n";
                foreach (array_slice($response['data']['items'], 0, 3) as $item) {
                    echo "   - " . $item['product_service_name'] . ": " . 
                         number_format($item['amount']) . " TZS\n";
                }
            }
        } else {
            echo "⚠️  Payment link generation returned unexpected response:\n";
            print_r($response);
        }
    } catch (Exception $e) {
        echo "❌ Payment link generation failed:\n";
        echo "   Error: " . $e->getMessage() . "\n";
        echo "   File: " . $e->getFile() . ":" . $e->getLine() . "\n";
        
        // Show more details
        if (strpos($e->getMessage(), 'property') !== false) {
            echo "\n   ⚠️  Property access error detected\n";
            echo "   This usually means the schedule object is missing expected properties\n";
            
            if ($schedules->count() > 0) {
                echo "\n   Available properties in schedule:\n";
                $firstSchedule = $schedules->first();
                foreach (get_object_vars($firstSchedule) as $prop => $value) {
                    echo "   - $prop: " . (is_null($value) ? 'null' : gettype($value)) . "\n";
                }
            }
        }
    }
    
    echo "\n========================================\n";
    echo "  Test Completed\n";
    echo "========================================\n\n";
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}

exit(0);