<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\NbcPayments\InternalFundTransferService;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "\n===========================================\n";
echo "  NBC INTERNAL FUND TRANSFER TEST\n";
echo "===========================================\n";
echo "Environment: UAT\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "===========================================\n\n";

try {
    // Initialize the service
    echo "1. Initializing Internal Fund Transfer Service...\n";
    $service = new InternalFundTransferService();
    echo "   ✓ Service initialized successfully\n\n";
    
    // Show configuration
    echo "2. Configuration Details:\n";
    echo "   - Base URL: " . config('services.nbc_internal_fund_transfer.base_url') . "\n";
    echo "   - Service Name: " . config('services.nbc_internal_fund_transfer.service_name') . "\n";
    echo "   - Channel ID: " . config('services.nbc_internal_fund_transfer.channel_id') . "\n";
    echo "   - SSL Verification: " . (config('services.nbc_internal_fund_transfer.verify_ssl') ? 'Enabled' : 'Disabled') . "\n";
    echo "   - Private Key: " . (file_exists('/var/www/html/template/storage/keys/private_key.pem') ? 'Found' : 'NOT FOUND') . "\n\n";
    
    // Prepare test transfer data
    echo "3. Preparing test transfer data...\n";
    $transferData = [
        'creditAccount' => '011191000036',  // Destination account
        'creditCurrency' => 'TZS',
        'debitAccount' => '011191000035',   // Source account
        'debitCurrency' => 'TZS',
        'amount' => '1000',                 // Test amount
        'narration' => 'Test Internal Fund Transfer - ' . date('YmdHis'),
        'pyrName' => 'Test User'           // Payer name
    ];
    
    echo "   Transfer Details:\n";
    echo "   - From Account: {$transferData['debitAccount']} ({$transferData['debitCurrency']})\n";
    echo "   - To Account: {$transferData['creditAccount']} ({$transferData['creditCurrency']})\n";
    echo "   - Amount: {$transferData['amount']} {$transferData['debitCurrency']}\n";
    echo "   - Narration: {$transferData['narration']}\n";
    echo "   - Payer Name: {$transferData['pyrName']}\n\n";
    
    // Process the transfer
    echo "4. Processing Internal Fund Transfer...\n";
    echo "   Sending request to NBC API...\n";
    
    $startTime = microtime(true);
    $result = $service->processInternalTransfer($transferData);
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    echo "   Response received in {$duration}ms\n\n";
    
    // Display results
    echo "5. Transfer Results:\n";
    echo "   " . str_repeat("=", 50) . "\n";
    
    if (isset($result['success']) && $result['success']) {
        echo "   ✓ TRANSFER SUCCESSFUL!\n\n";
        
        if (isset($result['statusCode'])) {
            echo "   Status Code: {$result['statusCode']}\n";
        }
        
        if (isset($result['message'])) {
            echo "   Message: {$result['message']}\n";
        }
        
        if (isset($result['data']) && is_array($result['data'])) {
            echo "   Response Data:\n";
            foreach ($result['data'] as $key => $value) {
                if (is_array($value)) {
                    echo "   - {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "   - {$key}: {$value}\n";
                }
            }
        }
        
        if (isset($result['hostReferenceCbs'])) {
            echo "   CBS Reference: {$result['hostReferenceCbs']}\n";
        }
        
        if (isset($result['hostReferenceGw'])) {
            echo "   Gateway Reference: {$result['hostReferenceGw']}\n";
        }
    } else {
        echo "   ✗ TRANSFER FAILED\n\n";
        
        if (isset($result['statusCode'])) {
            echo "   Status Code: {$result['statusCode']}\n";
            
            // Interpret status codes
            $statusMessages = [
                600 => 'Success',
                626 => 'Transaction Failed',
                625 => 'No Response',
                630 => 'Currency account combination does not match',
                631 => 'Biller not defined',
                700 => 'General Failure'
            ];
            
            if (isset($statusMessages[$result['statusCode']])) {
                echo "   Status: {$statusMessages[$result['statusCode']]}\n";
            }
        }
        
        if (isset($result['message'])) {
            echo "   Error Message: {$result['message']}\n";
        }
        
        if (isset($result['error'])) {
            echo "   Error Details: {$result['error']}\n";
        }
        
        if (isset($result['data']) && is_array($result['data'])) {
            echo "   Additional Details:\n";
            foreach ($result['data'] as $key => $value) {
                if (is_array($value)) {
                    echo "   - {$key}: " . json_encode($value) . "\n";
                } else {
                    echo "   - {$key}: {$value}\n";
                }
            }
        }
    }
    
    echo "   " . str_repeat("=", 50) . "\n\n";
    
    // Full response dump
    echo "6. Full Response (Debug):\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
    
} catch (Exception $e) {
    echo "\n✗ ERROR OCCURRED:\n";
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
echo "TEST COMPLETED\n";
echo "Check logs at:\n";
echo " - storage/logs/laravel-" . date('Y-m-d') . ".log\n";
echo " - storage/logs/payments-" . date('Y-m-d') . ".log\n";
echo "===========================================\n\n";