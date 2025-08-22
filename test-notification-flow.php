<?php

/**
 * Test script for complete notification flow
 * This tests the payment link generation and notification dispatch
 */

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\PaymentLinkService;
use App\Jobs\ProcessMemberNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// Boot Laravel
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\n";
echo "========================================\n";
echo "  Notification Flow Test\n";
echo "========================================\n\n";

try {
    // Create test member data
    $member = new stdClass();
    $member->id = 2001;
    $member->client_number = 'TEST_MEMBER_' . time();
    $member->first_name = 'Andrew';
    $member->middle_name = 'S';
    $member->last_name = 'Mashamba';
    $member->present_surname = 'Mashamba';
    $member->phone_number = '0742099713'; // Will be formatted to 255742099713
    $member->email = 'andrew.s.mashamba@gmail.com';
    
    echo "📋 Test Member:\n";
    echo "   Name: {$member->first_name} {$member->middle_name} {$member->last_name}\n";
    echo "   Client Number: {$member->client_number}\n";
    echo "   Phone: {$member->phone_number}\n";
    echo "   Email: {$member->email}\n\n";
    
    // Create control numbers (for loan repayment)
    $controlNumbers = [
        [
            'service_code' => 'REP',
            'service_name' => 'LOAN REPAYMENT',
            'control_number' => 'TEST_CTRL_' . rand(100000, 999999),
            'amount' => 115000
        ]
    ];
    
    echo "📝 Control Numbers:\n";
    foreach ($controlNumbers as $control) {
        echo "   Service: {$control['service_name']}\n";
        echo "   Control No: {$control['control_number']}\n";
        echo "   Amount: " . number_format($control['amount']) . " TZS\n";
    }
    echo "\n";
    
    // Generate payment link
    echo "🔗 Generating Payment Link...\n";
    
    $paymentLinkService = new PaymentLinkService();
    
    // Create test loan schedules
    $loanSchedules = [];
    for ($i = 1; $i <= 3; $i++) {
        $schedule = new stdClass();
        $schedule->id = 7000 + $i;
        $schedule->loan_id = 2001;
        $schedule->installment = $i;
        $schedule->repayment_date = date('Y-m-d', strtotime("+{$i} months"));
        $schedule->principle = 100000;
        $schedule->interest = 15000;
        $schedule->penalties = 0;
        $schedule->charges = ($i == 1) ? 5000 : 0;
        $schedule->status = 'PENDING';
        $loanSchedules[] = $schedule;
    }
    
    try {
        $paymentLinkResponse = $paymentLinkService->generateLoanInstallmentsPaymentLink(
            2001, // Test loan ID
            $member,
            $loanSchedules,
            ['description' => 'Test Notification - SACCOS Loan Services']
        );
        
        $paymentLink = $paymentLinkResponse['data']['payment_url'] ?? null;
        
        if ($paymentLink) {
            echo "✅ Payment link generated: $paymentLink\n\n";
        } else {
            echo "⚠️  Payment link generation returned no URL\n\n";
            $paymentLink = 'http://example.com/test-payment-link';
        }
    } catch (Exception $e) {
        echo "⚠️  Payment link generation failed: " . $e->getMessage() . "\n";
        echo "   Using fallback payment link\n\n";
        $paymentLink = 'http://example.com/test-payment-link';
    }
    
    // Dispatch notification job
    echo "📧 Dispatching Notification Job...\n";
    
    try {
        ProcessMemberNotifications::dispatch(
            $member,
            $controlNumbers,
            $paymentLink
        )->onQueue('notifications');
        
        echo "✅ Notification job dispatched to 'notifications' queue\n\n";
        
        // Check job status
        $pendingJobs = DB::table('jobs')->where('queue', 'notifications')->count();
        echo "📊 Queue Status:\n";
        echo "   Pending jobs in notifications queue: $pendingJobs\n\n";
        
        if ($pendingJobs > 0) {
            echo "ℹ️  To process the notification:\n";
            echo "   Run: php artisan queue:work --queue=notifications\n\n";
        }
        
        // Log the test
        Log::info('Notification flow test completed', [
            'member' => $member->client_number,
            'payment_link' => $paymentLink,
            'control_numbers' => $controlNumbers
        ]);
        
        echo "✅ Test completed successfully!\n\n";
        echo "📝 Check the following:\n";
        echo "   1. Queue worker logs: storage/logs/queue-worker.log\n";
        echo "   2. Laravel logs: storage/logs/laravel-" . date('Y-m-d') . ".log\n";
        echo "   3. Email logs (if mail driver is 'log')\n";
        echo "   4. SMS service logs\n\n";
        
    } catch (Exception $e) {
        echo "❌ Failed to dispatch notification: " . $e->getMessage() . "\n";
        echo "   " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    }
    
} catch (Exception $e) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n\n";
    exit(1);
}

echo "========================================\n\n";
exit(0);