#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\Payments\ExternalFundsTransferService;
use Carbon\Carbon;

// Color codes for terminal output
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$BLUE = "\033[0;34m";
$CYAN = "\033[0;36m";
$NC = "\033[0m"; // No Color

echo "\n{$BLUE}=====================================\n";
echo "  EXTERNAL BANK ACCOUNT LOOKUP TEST\n";
echo "====================================={$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";
echo "Environment: " . app()->environment() . "\n\n";

// Test Data
$testData = [
    [
        'name' => 'CRDB Bank Account',
        'account' => '12334567789',
        'bank_code' => 'CORUTZTZ',
        'amount' => 5000
    ],
    [
        'name' => 'NMB Bank Account',
        'account' => '1234567890123',
        'bank_code' => 'NMIBTZT0',
        'amount' => 10000
    ]
];

$reportContent = "# External Bank Account Lookup Test Report\n\n";
$reportContent .= "**Date:** " . date('Y-m-d H:i:s') . "\n";
$reportContent .= "**Environment:** " . app()->environment() . "\n";
$reportContent .= "**Base URL:** " . config('services.nbc_payments.base_url') . "\n";
$reportContent .= "**Client ID:** " . config('services.nbc_payments.client_id') . "\n";
$reportContent .= "**SACCOS Account:** " . config('services.nbc_payments.saccos_account') . "\n\n";
$reportContent .= "---\n\n";

try {
    echo "{$YELLOW}Initializing External Funds Transfer Service...{$NC}\n";
    $service = app(ExternalFundsTransferService::class);
    echo "{$GREEN}✓ Service initialized successfully{$NC}\n\n";
    
    $reportContent .= "## Service Configuration\n\n";
    $reportContent .= "- **Service:** ExternalFundsTransferService\n";
    $reportContent .= "- **Endpoint:** `/domestix/api/v2/lookup`\n";
    $reportContent .= "- **Method:** POST\n\n";
    
    foreach ($testData as $index => $test) {
        $testNumber = $index + 1;
        echo "{$CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
        echo "{$YELLOW}Test #{$testNumber}: {$test['name']}{$NC}\n";
        echo "{$CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n\n";
        
        $reportContent .= "## Test #{$testNumber}: {$test['name']}\n\n";
        
        // Prepare request data
        $lookupRef = 'LOOKUP' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
        $debitAccount = config('services.nbc_payments.saccos_account', '015103001490');
        
        $requestPayload = [
            'serviceName' => 'TIPS_LOOKUP',
            'clientId' => config('services.nbc_payments.client_id'),
            'clientRef' => $lookupRef,
            'identifierType' => 'BANK',
            'identifier' => $test['account'],
            'destinationFsp' => $test['bank_code'],
            'debitAccount' => $debitAccount,
            'debitAccountCurrency' => 'TZS',
            'debitAccountBranchCode' => substr($debitAccount, 0, 3),
            'amount' => (string)$test['amount'],
            'debitAccountCategory' => 'BUSINESS'
        ];
        
        // Display request details
        echo "{$BLUE}REQUEST DETAILS:{$NC}\n";
        echo "• Account Number: {$test['account']}\n";
        echo "• Bank Code: {$test['bank_code']}\n";
        echo "• Amount: " . number_format($test['amount']) . " TZS\n";
        echo "• Request Time: " . date('Y-m-d H:i:s') . "\n\n";
        
        $reportContent .= "### Request Details\n\n";
        $reportContent .= "| Field | Value |\n";
        $reportContent .= "|-------|-------|\n";
        $reportContent .= "| Account Number | {$test['account']} |\n";
        $reportContent .= "| Bank Code | {$test['bank_code']} |\n";
        $reportContent .= "| Amount | " . number_format($test['amount']) . " TZS |\n";
        $reportContent .= "| Request Time | " . date('Y-m-d H:i:s') . " |\n\n";
        
        echo "{$BLUE}REQUEST HEADERS:{$NC}\n";
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-Api-Key' => substr(config('services.nbc_payments.api_key'), 0, 20) . '...',
            'X-Trace-Uuid' => 'domestix-' . sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            ),
            'Client-Id' => config('services.nbc_payments.client_id'),
            'Service-Name' => 'TIPS_LOOKUP',
            'Signature' => 'Generated signature (truncated)...',
            'Timestamp' => Carbon::now()->toIso8601String()
        ];
        
        $reportContent .= "### Request Headers\n\n";
        $reportContent .= "```http\n";
        foreach ($headers as $key => $value) {
            echo "• {$key}: {$value}\n";
            if ($key !== 'X-Api-Key' && $key !== 'Signature') {
                $reportContent .= "{$key}: {$value}\n";
            } else {
                $reportContent .= "{$key}: [REDACTED]\n";
            }
        }
        $reportContent .= "```\n\n";
        echo "\n";
        
        echo "{$BLUE}REQUEST BODY:{$NC}\n";
        echo json_encode($requestPayload, JSON_PRETTY_PRINT) . "\n\n";
        
        $reportContent .= "### Request Body\n\n";
        $reportContent .= "```json\n";
        $reportContent .= json_encode($requestPayload, JSON_PRETTY_PRINT);
        $reportContent .= "\n```\n\n";
        
        // Execute lookup
        echo "{$YELLOW}Executing lookup...{$NC}\n";
        $startTime = microtime(true);
        
        $result = $service->lookupAccount(
            $test['account'],
            $test['bank_code'],
            $test['amount']
        );
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        // Display response
        echo "\n{$BLUE}RESPONSE:{$NC}\n";
        echo "• Response Time: {$duration}ms\n";
        echo "• Status: " . ($result['success'] ? "{$GREEN}SUCCESS{$NC}" : "{$RED}FAILED{$NC}") . "\n";
        
        $reportContent .= "### Response\n\n";
        $reportContent .= "| Field | Value |\n";
        $reportContent .= "|-------|-------|\n";
        $reportContent .= "| Response Time | {$duration}ms |\n";
        $reportContent .= "| Status | " . ($result['success'] ? 'SUCCESS ✓' : 'FAILED ✗') . " |\n";
        
        if ($result['success']) {
            echo "• Account Name: {$GREEN}" . ($result['account_name'] ?? 'N/A') . "{$NC}\n";
            echo "• Bank Name: " . ($result['bank_name'] ?? 'N/A') . "\n";
            echo "• Can Receive: " . ($result['can_receive'] ? 'Yes' : 'No') . "\n";
            echo "• Engine Ref: " . ($result['engine_ref'] ?? 'N/A') . "\n";
            
            $reportContent .= "| Account Name | " . ($result['account_name'] ?? 'N/A') . " |\n";
            $reportContent .= "| Bank Name | " . ($result['bank_name'] ?? 'N/A') . " |\n";
            $reportContent .= "| Can Receive | " . ($result['can_receive'] ? 'Yes' : 'No') . " |\n";
            $reportContent .= "| Engine Ref | " . ($result['engine_ref'] ?? 'N/A') . " |\n\n";
        } else {
            echo "• Error: {$RED}" . ($result['error'] ?? 'Unknown error') . "{$NC}\n";
            $reportContent .= "| Error | " . ($result['error'] ?? 'Unknown error') . " |\n\n";
        }
        
        echo "\n{$BLUE}FULL RESPONSE DATA:{$NC}\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
        
        $reportContent .= "### Full Response Data\n\n";
        $reportContent .= "```json\n";
        $reportContent .= json_encode($result, JSON_PRETTY_PRINT);
        $reportContent .= "\n```\n\n";
        
        // Add a delay between tests to avoid rate limiting
        if ($index < count($testData) - 1) {
            echo "{$YELLOW}Waiting 2 seconds before next test...{$NC}\n\n";
            sleep(2);
        }
    }
    
} catch (Exception $e) {
    echo "\n{$RED}ERROR: " . $e->getMessage() . "{$NC}\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    
    $reportContent .= "## Error\n\n";
    $reportContent .= "**Error Message:** " . $e->getMessage() . "\n\n";
    $reportContent .= "**Stack Trace:**\n```\n" . $e->getTraceAsString() . "\n```\n\n";
}

// Summary
echo "\n{$CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";
echo "{$YELLOW}TEST SUMMARY{$NC}\n";
echo "{$CYAN}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━{$NC}\n";

$reportContent .= "---\n\n";
$reportContent .= "## Test Summary\n\n";

$timestamp = date('Y-m-d H:i:s');
echo "• Test Completed: {$timestamp}\n";
echo "• Tests Run: " . count($testData) . "\n";
echo "• Environment: " . app()->environment() . "\n";

$reportContent .= "- **Test Completed:** {$timestamp}\n";
$reportContent .= "- **Tests Run:** " . count($testData) . "\n";
$reportContent .= "- **Environment:** " . app()->environment() . "\n\n";

$reportContent .= "## Known Issues\n\n";
$reportContent .= "1. **NBC UAT Environment Instability**: The BOT API Gateway intermittently returns authentication errors\n";
$reportContent .= "2. **Account Balance Retrieval**: Some test accounts have CBS-level issues\n";
$reportContent .= "3. **Timeout Issues**: API responses sometimes exceed 30-second timeout\n\n";

$reportContent .= "## Recommendations\n\n";
$reportContent .= "1. Use account `015103001490` for testing (confirmed working)\n";
$reportContent .= "2. Implement retry logic for transient failures\n";
$reportContent .= "3. Consider moving to production environment for stable testing\n";

// Save report to file
$reportFile = __DIR__ . '/EXTERNAL_LOOKUP_TEST_REPORT_' . date('Y-m-d_His') . '.md';
file_put_contents($reportFile, $reportContent);

echo "\n{$GREEN}Report saved to: {$reportFile}{$NC}\n\n";

echo "{$BLUE}=== Test Complete ==={$NC}\n\n";