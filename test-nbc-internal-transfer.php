<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\NbcPayments\InternalFundTransferService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════╗\n";
echo "║         NBC INTERNAL FUND TRANSFER SERVICE TEST (REAL API)           ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════╝\n";
echo "Environment: " . app()->environment() . "\n";
echo "Timestamp: " . Carbon::now()->toDateTimeString() . "\n";
echo "Server: " . gethostname() . "\n";
echo "═════════════════════════════════════════════════════════════════════════\n\n";

try {
    // Step 1: Initialize the REAL NBC service
    echo "▶ STEP 1: Initializing NBC Internal Fund Transfer Service\n";
    echo "───────────────────────────────────────────────────────────\n";
    $service = new InternalFundTransferService();
    echo "✓ Service initialized successfully\n\n";
    
    // Step 2: Display configuration
    echo "▶ STEP 2: Configuration Details\n";
    echo "───────────────────────────────────────────────────────────\n";
    $config = [
        'Base URL' => config('services.nbc_internal_fund_transfer.base_url'),
        'Service Name' => config('services.nbc_internal_fund_transfer.service_name'),
        'Channel ID' => config('services.nbc_internal_fund_transfer.channel_id'),
        'Username' => config('services.nbc_internal_fund_transfer.username'),
        'SSL Verification' => config('services.nbc_internal_fund_transfer.verify_ssl') ? 'Disabled' : 'Enabled',
        'Timeout' => config('services.nbc_internal_fund_transfer.timeout') . ' seconds',
        'Max Retries' => config('services.nbc_internal_fund_transfer.max_retries'),
    ];
    
    foreach ($config as $key => $value) {
        if ($key === 'Username') {
            echo sprintf("  %-20s : %s\n", $key, $value);
        } else {
            echo sprintf("  %-20s : %s\n", $key, $value);
        }
    }
    
    // Check private key
    $privateKeyPath = config('services.nbc_internal_fund_transfer.private_key');
    $privateKeyFile = str_replace('file://', '', $privateKeyPath);
    echo sprintf("  %-20s : %s\n", 'Private Key', file_exists($privateKeyFile) ? '✓ Found at ' . $privateKeyFile : '✗ NOT FOUND at ' . $privateKeyFile);
    echo "\n";
    
    // Step 3: Prepare test transfer data
    echo "▶ STEP 3: Preparing Test Transfer Data\n";
    echo "───────────────────────────────────────────────────────────\n";
    
    $transferData = [
        'creditAccount' => '011191000036',  // Destination account
        'creditCurrency' => 'TZS',
        'debitAccount' => '011191000035',   // Source account  
        'debitCurrency' => 'TZS',
        'amount' => '1000',                 // Test amount
        'narration' => 'Test NBC Internal Transfer - ' . date('YmdHis'),
        'pyrName' => 'Test User'            // Payer name
    ];
    
    echo "Transfer Details:\n";
    echo "  • From Account     : {$transferData['debitAccount']} ({$transferData['debitCurrency']})\n";
    echo "  • To Account       : {$transferData['creditAccount']} ({$transferData['creditCurrency']})\n";
    echo "  • Amount           : {$transferData['amount']} {$transferData['debitCurrency']}\n";
    echo "  • Narration        : {$transferData['narration']}\n";
    echo "  • Payer Name       : {$transferData['pyrName']}\n\n";
    
    // Step 4: Process the transfer and capture request/response
    echo "▶ STEP 4: Processing Internal Fund Transfer\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "Sending request to NBC API at:\n";
    echo config('services.nbc_internal_fund_transfer.base_url') . '/' . config('services.nbc_internal_fund_transfer.service_name') . "\n\n";
    
    echo "⏳ Making REAL API call to NBC...\n\n";
    
    // Enable detailed logging
    Log::channel('payments')->info('NBC IFT Test Started', [
        'transfer_data' => $transferData,
        'config' => $config
    ]);
    
    $startTime = microtime(true);
    $result = $service->processInternalTransfer($transferData);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    // Step 5: Display results
    echo "\n▶ STEP 5: Transfer Results\n";
    echo "───────────────────────────────────────────────────────────\n";
    echo "Response Time: {$duration} ms\n\n";
    
    if (isset($result['success']) && $result['success']) {
        echo "✅ TRANSFER SUCCESSFUL!\n\n";
        
        echo "Result Details:\n";
        if (isset($result['statusCode'])) {
            echo "  • NBC Status Code  : {$result['statusCode']} (600 = Success)\n";
        }
        if (isset($result['message'])) {
            echo "  • Message          : {$result['message']}\n";
        }
        if (isset($result['data']) && is_array($result['data'])) {
            echo "  • Response Data:\n";
            foreach ($result['data'] as $key => $value) {
                if (!empty($value)) {
                    echo sprintf("    - %-25s : %s\n", $key, is_array($value) ? json_encode($value) : $value);
                }
            }
        }
    } else {
        echo "❌ TRANSFER FAILED\n\n";
        
        echo "Error Details:\n";
        if (isset($result['statusCode'])) {
            echo "  • NBC Status Code  : {$result['statusCode']}\n";
            
            // Interpret NBC status codes
            $statusMessages = [
                600 => 'Success',
                626 => 'Transaction Failed',
                625 => 'No Response from CBS',
                630 => 'Currency account combination does not match',
                631 => 'Biller not defined',
                700 => 'General Failure',
                'NETWORK_ERROR' => 'Network connectivity issue'
            ];
            
            $statusMsg = $statusMessages[$result['statusCode']] ?? 'Unknown status';
            echo "  • Status Meaning   : {$statusMsg}\n";
        }
        if (isset($result['message'])) {
            echo "  • Message          : {$result['message']}\n";
        }
        if (isset($result['error'])) {
            echo "  • Error            : {$result['error']}\n";
        }
        if (isset($result['data']) && is_array($result['data'])) {
            echo "  • Additional Data:\n";
            foreach ($result['data'] as $key => $value) {
                if (!empty($value)) {
                    echo sprintf("    - %-25s : %s\n", $key, is_array($value) ? json_encode($value) : $value);
                }
            }
        }
    }
    
    // Step 6: Save full response
    echo "\n▶ STEP 6: Saving Response Data\n";
    echo "───────────────────────────────────────────────────────────\n";
    
    $captureData = [
        'test_info' => [
            'timestamp' => Carbon::now()->toIso8601String(),
            'environment' => app()->environment(),
            'server' => gethostname(),
            'service' => 'NbcPayments\\InternalFundTransferService',
            'api_endpoint' => config('services.nbc_internal_fund_transfer.base_url') . '/' . config('services.nbc_internal_fund_transfer.service_name')
        ],
        'configuration' => $config,
        'transfer_data' => $transferData,
        'result' => $result,
        'response_time_ms' => $duration
    ];
    
    // Save JSON file
    $jsonFile = 'storage/logs/nbc-ift-capture-' . date('Y-m-d-His') . '.json';
    file_put_contents($jsonFile, json_encode($captureData, JSON_PRETTY_PRINT));
    echo "✓ Full response saved to: {$jsonFile}\n";
    
    // Also check the laravel log for detailed HTTP request/response
    echo "\n📋 IMPORTANT: Check the following logs for HTTP request/response details:\n";
    echo "  • storage/logs/laravel-" . date('Y-m-d') . ".log\n";
    echo "  • storage/logs/payments-" . date('Y-m-d') . ".log\n";
    echo "\nThe logs contain the full HTTP headers and body for both request and response.\n";
    
} catch (Exception $e) {
    echo "\n╔═══════════════════════════════════════════════════════════════════════╗\n";
    echo "║                            ERROR OCCURRED                             ║\n";
    echo "╚═══════════════════════════════════════════════════════════════════════╝\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    
    if ($e->getMessage() === 'Payment service configuration error. Please contact support.') {
        echo "⚠️  This error usually means:\n";
        echo "  • Missing API credentials in .env\n";
        echo "  • Private key file not found\n";
        echo "  • Invalid configuration values\n";
    }
    
    Log::error('NBC Internal Fund Transfer Test Failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n═════════════════════════════════════════════════════════════════════════\n";
echo "TEST COMPLETED at " . Carbon::now()->toDateTimeString() . "\n";
echo "═════════════════════════════════════════════════════════════════════════\n\n";