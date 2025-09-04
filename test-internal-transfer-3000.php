<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\NbcPayments\InternalFundTransferService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n===========================================\n";
echo "  NBC INTERNAL FUND TRANSFER TEST\n";
echo "===========================================\n";
echo "Transfer Amount: TZS 3,000\n";
echo "From Account: 011191000035\n";
echo "To Account: 011201318462\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

try {
    // Initialize the service
    echo "STEP 1: Initializing Internal Fund Transfer Service\n";
    echo "---------------------------------------------------\n";
    $service = new InternalFundTransferService();
    echo "✓ Service initialized successfully\n\n";
    
    // Show configuration
    echo "STEP 2: Verifying Configuration\n";
    echo "---------------------------------------------------\n";
    echo "Base URL: " . config('services.nbc_internal_fund_transfer.base_url') . "\n";
    echo "Service: " . config('services.nbc_internal_fund_transfer.service_name') . "\n";
    echo "Channel: " . config('services.nbc_internal_fund_transfer.channel_id') . "\n";
    echo "SSL: " . (config('services.nbc_internal_fund_transfer.verify_ssl') ? 'Enabled' : 'Disabled') . "\n";
    echo "Private Key: " . (file_exists('/var/www/html/template/storage/keys/private_key.pem') ? '✓ Found' : '✗ NOT FOUND') . "\n\n";
    
    // Prepare transfer data
    echo "STEP 3: Preparing Transfer Data\n";
    echo "---------------------------------------------------\n";
    $transferData = [
        'debitAccount' => '011191000035',     // Source account
        'debitCurrency' => 'TZS',
        'creditAccount' => '011201318462',    // Destination account  
        'creditCurrency' => 'TZS',
        'amount' => '3000',                   // Transfer amount
        'narration' => 'Internal Transfer Test - TZS 3000 - ' . date('Y-m-d H:i:s'),
        'pyrName' => 'SACCOS Test User'      // Payer name
    ];
    
    echo "Transfer Details:\n";
    echo "• From Account:  {$transferData['debitAccount']} ({$transferData['debitCurrency']})\n";
    echo "• To Account:    {$transferData['creditAccount']} ({$transferData['creditCurrency']})\n";
    echo "• Amount:        " . number_format($transferData['amount']) . " {$transferData['debitCurrency']}\n";
    echo "• Description:   {$transferData['narration']}\n";
    echo "• Initiated By:  {$transferData['pyrName']}\n\n";
    
    // Process the transfer
    echo "STEP 4: Processing Internal Fund Transfer\n";
    echo "---------------------------------------------------\n";
    echo "Sending request to NBC Gateway...\n";
    
    $startTime = microtime(true);
    
    Log::info('=== INTERNAL TRANSFER TEST START ===', [
        'from' => $transferData['debitAccount'],
        'to' => $transferData['creditAccount'],
        'amount' => $transferData['amount'],
        'timestamp' => now()->toDateTimeString()
    ]);
    
    $result = $service->processInternalTransfer($transferData);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    Log::info('=== INTERNAL TRANSFER TEST COMPLETE ===', [
        'duration_ms' => $duration,
        'success' => $result['success'] ?? false,
        'result' => $result
    ]);
    
    echo "Response received in {$duration}ms\n\n";
    
    // Display results
    echo "STEP 5: Transfer Results\n";
    echo "---------------------------------------------------\n";
    
    if (isset($result['success']) && $result['success']) {
        echo "✓✓✓ TRANSFER SUCCESSFUL! ✓✓✓\n\n";
        
        echo "Transaction Details:\n";
        
        if (isset($result['statusCode'])) {
            echo "• Status Code: {$result['statusCode']} ";
            if ($result['statusCode'] == 600) {
                echo "(SUCCESS)\n";
            } else {
                echo "\n";
            }
        }
        
        if (isset($result['message'])) {
            echo "• Message: {$result['message']}\n";
        }
        
        if (isset($result['data'])) {
            $data = $result['data'];
            
            if (isset($data['hostReferenceCbs'])) {
                echo "• CBS Reference: {$data['hostReferenceCbs']}\n";
            }
            
            if (isset($data['hostReferenceGw'])) {
                echo "• Gateway Reference: {$data['hostReferenceGw']}\n";
            }
            
            if (isset($data['cbsRespTime'])) {
                echo "• CBS Response Time: {$data['cbsRespTime']}\n";
            }
            
            if (isset($data['hostStatusCodeCbs'])) {
                echo "• CBS Status Code: {$data['hostStatusCodeCbs']}\n";
            }
        }
        
        // Save transaction to database
        echo "\nSaving transaction record...\n";
        try {
            DB::table('transactions')->insert([
                'transaction_uuid' => \Str::uuid(),
                'service_name' => 'NBC_INTERNAL_TRANSFER',
                'amount' => $transferData['amount'],
                'currency' => 'TZS',
                'type' => 'debit',
                'transaction_category' => 'transfer',
                'transaction_subcategory' => 'internal_transfer',
                'source' => 'test_script',
                'channel_id' => 'SACCOSAPP',
                'gateway_ref' => $result['data']['hostReferenceGw'] ?? 'N/A',
                'description' => $transferData['narration'],
                'reference' => $result['data']['hostReferenceCbs'] ?? 'N/A',
                'status' => 'completed',
                'initiated_at' => now(),
                'completed_at' => now(),
                'metadata' => json_encode([
                    'from_account' => $transferData['debitAccount'],
                    'to_account' => $transferData['creditAccount'],
                    'amount' => $transferData['amount'],
                    'payer_name' => $transferData['pyrName']
                ]),
                'created_at' => now(),
                'updated_at' => now(),
                'branch_id' => 1
            ]);
            echo "✓ Transaction saved to database\n";
        } catch (\Exception $e) {
            echo "⚠ Could not save to database: " . $e->getMessage() . "\n";
        }
        
    } else {
        echo "✗✗✗ TRANSFER FAILED ✗✗✗\n\n";
        
        echo "Error Details:\n";
        
        if (isset($result['statusCode'])) {
            echo "• Status Code: {$result['statusCode']}\n";
            
            // Interpret status codes
            $statusMessages = [
                600 => 'Success',
                626 => 'Transaction Failed',
                625 => 'No Response from CBS',
                630 => 'Currency account combination does not match',
                631 => 'Biller not defined',
                700 => 'General Failure',
                'NETWORK_ERROR' => 'Network connectivity issue'
            ];
            
            if (isset($statusMessages[$result['statusCode']])) {
                echo "• Status Meaning: {$statusMessages[$result['statusCode']]}\n";
            }
        }
        
        if (isset($result['message'])) {
            echo "• Error Message: {$result['message']}\n";
        }
        
        if (isset($result['error'])) {
            echo "• Error Details: {$result['error']}\n";
        }
        
        if (isset($result['data']) && is_array($result['data'])) {
            echo "\nAdditional Information:\n";
            foreach ($result['data'] as $key => $value) {
                if (is_array($value)) {
                    echo "• {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "• {$key}: {$value}\n";
                }
            }
        }
    }
    
    echo "\n---------------------------------------------------\n\n";
    
    // Debug output
    echo "STEP 6: Debug Information\n";
    echo "---------------------------------------------------\n";
    echo "Full Response:\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
} catch (\RuntimeException $e) {
    echo "\n✗ CONFIGURATION ERROR:\n";
    echo "  " . $e->getMessage() . "\n\n";
    echo "  Please check your .env configuration:\n";
    echo "  • NBC_INTERNAL_FUND_TRANSFER_BASE_URL\n";
    echo "  • NBC_INTERNAL_FUND_TRANSFER_API_KEY\n";
    echo "  • NBC_INTERNAL_FUND_TRANSFER_USERNAME\n";
    echo "  • NBC_INTERNAL_FUND_TRANSFER_PASSWORD\n";
    echo "  • NBC_INTERNAL_FUND_TRANSFER_PRIVATE_KEY\n\n";
    
} catch (\InvalidArgumentException $e) {
    echo "\n✗ VALIDATION ERROR:\n";
    echo "  " . $e->getMessage() . "\n\n";
    echo "  Please check your transfer data:\n";
    echo "  • Account numbers must be 10-16 digits\n";
    echo "  • Amount must be positive\n";
    echo "  • Currency must be valid (TZS, USD, EUR, GBP)\n\n";
    
} catch (\Exception $e) {
    echo "\n✗ UNEXPECTED ERROR:\n";
    echo "  Type: " . get_class($e) . "\n";
    echo "  Message: " . $e->getMessage() . "\n";
    echo "  File: " . $e->getFile() . "\n";
    echo "  Line: " . $e->getLine() . "\n\n";
    
    Log::error('Internal Fund Transfer Test Failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "===========================================\n";
echo "TEST EXECUTION COMPLETED\n";
echo "\nLog Files:\n";
echo "• Laravel: storage/logs/laravel-" . date('Y-m-d') . ".log\n";
echo "• Payments: storage/logs/payments-" . date('Y-m-d') . ".log\n";
echo "===========================================\n\n";