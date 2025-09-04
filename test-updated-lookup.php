#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Payments\ExternalFundsTransferService;

// Color codes
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$BLUE = "\033[0;34m";
$NC = "\033[0m";

echo "\n{$BLUE}=====================================\n";
echo "  TESTING UPDATED LOOKUP (Like CURL)\n";
echo "====================================={$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

echo "{$YELLOW}Configuration:{$NC}\n";
echo "• Base URL: " . config('services.nbc_payments.base_url') . "\n";
echo "• Client ID: " . config('services.nbc_payments.client_id') . "\n";
echo "• API Key: " . substr(config('services.nbc_payments.api_key'), 0, 20) . "...\n";
echo "• SACCOS Account: " . config('services.nbc_payments.saccos_account') . "\n\n";

try {
    echo "{$YELLOW}Initializing External Funds Transfer Service...{$NC}\n";
    $service = app(ExternalFundsTransferService::class);
    echo "{$GREEN}✓ Service initialized{$NC}\n\n";
    
    // Test exact same parameters as working curl
    echo "{$BLUE}Testing CRDB Bank Lookup (same as working curl):{$NC}\n";
    echo "• Account: 12334567789\n";
    echo "• Bank Code: CORUTZTZ\n";
    echo "• Amount: 5000 TZS\n\n";
    
    echo "{$YELLOW}Executing lookup...{$NC}\n";
    $startTime = microtime(true);
    
    $result = $service->lookupAccount(
        '12334567789',
        'CORUTZTZ',
        5000
    );
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    echo "\n{$BLUE}Response received in {$duration}ms{$NC}\n";
    
    if ($result['success']) {
        echo "{$GREEN}✅ LOOKUP SUCCESSFUL!{$NC}\n\n";
        echo "Response Details:\n";
        echo "• Account Number: " . ($result['account_number'] ?? 'N/A') . "\n";
        echo "• Account Name: " . ($result['account_name'] ?? 'N/A') . "\n";
        echo "• Bank Code: " . ($result['bank_code'] ?? 'N/A') . "\n";
        echo "• Can Receive: " . ($result['can_receive'] ? 'Yes' : 'No') . "\n";
        echo "• Engine Ref: " . ($result['engine_ref'] ?? 'N/A') . "\n";
        
        echo "\n{$GREEN}Full Response:{$NC}\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
        
    } else {
        echo "{$RED}❌ LOOKUP FAILED{$NC}\n";
        echo "Error: " . ($result['error'] ?? 'Unknown error') . "\n";
        
        echo "\n{$RED}Full Response:{$NC}\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "\n{$RED}Exception: " . $e->getMessage() . "{$NC}\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n{$BLUE}=== Test Complete ==={$NC}\n\n";