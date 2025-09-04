#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Color codes
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$CYAN = "\033[0;36m";
$NC = "\033[0m";

echo "\n{$CYAN}================================================\n";
echo "     B2W TEST WITH WORKING FORMAT\n";
echo "================================================{$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Use numeric timestamp as clientRef (like the working example)
$clientRef = (string)time();

$payload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => 'APP_IOS',
    'clientRef' => $clientRef,  // Just numeric timestamp
    'identifierType' => 'MSISDN',
    'identifier' => '0748045601',
    'destinationFsp' => 'VMCASHIN',
    'debitAccount' => '011103033734',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '012',
    'amount' => '900000',
    'debitAccountCategory' => 'BUSINESS'
];

echo "{$YELLOW}Request Details:{$NC}\n";
echo "• Service: TIPS_LOOKUP\n";
echo "• Phone: 0748045601\n";
echo "• Destination FSP: VMCASHIN\n";
echo "• Client Ref: {$clientRef} (numeric timestamp)\n";
echo "• Debit Account: 011103033734\n";
echo "• Amount: 900,000 TZS\n\n";

echo "{$YELLOW}Sending request...{$NC}\n";
$startTime = microtime(true);

try {
    $client = new \GuzzleHttp\Client([
        'verify' => false,
        'timeout' => 30,
        'connect_timeout' => 10,
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-api-key' => env('NBC_PAYMENTS_API_KEY')
        ]
    ]);

    $response = $client->post('https://22.32.245.67:443/domestix/api/v2/lookup', [
        'json' => $payload
    ]);

    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $statusCode = $response->getStatusCode();
    $responseBody = json_decode($response->getBody()->getContents(), true);

    echo "\n{$GREEN}Response received in {$duration}ms{$NC}\n";
    echo "HTTP Status: {$statusCode}\n\n";

    if (isset($responseBody['statusCode']) && $responseBody['statusCode'] == 600) {
        echo "{$GREEN}✅ B2W LOOKUP SUCCESS!{$NC}\n\n";
        
        $body = $responseBody['body'] ?? [];
        
        echo "{$YELLOW}Account Information:{$NC}\n";
        echo "• Full Name: {$GREEN}" . ($body['fullName'] ?? 'N/A') . "{$NC}\n";
        echo "• Phone: " . ($body['identifier'] ?? 'N/A') . "\n";
        echo "• FSP ID: " . ($body['fspId'] ?? 'N/A') . "\n";
        echo "• Account Category: " . ($body['accountCategory'] ?? 'N/A') . "\n";
        
        echo "\n{$YELLOW}Transaction Details:{$NC}\n";
        echo "• Process As: " . ($responseBody['processAs'] ?? 'N/A') . "\n";
        echo "• Use Case: " . ($responseBody['useCase'] ?? 'N/A') . "\n";
        echo "• Engine Ref: " . ($responseBody['engineRef'] ?? 'N/A') . "\n";
        echo "• Message: {$GREEN}" . ($responseBody['message'] ?? 'N/A') . "{$NC}\n";
        
    } else {
        echo "{$RED}❌ B2W LOOKUP FAILED{$NC}\n";
        echo "Status Code: " . ($responseBody['statusCode'] ?? 'Unknown') . "\n";
        echo "Message: " . ($responseBody['message'] ?? 'No message') . "\n";
        
        if (isset($responseBody['body'])) {
            echo "\nError Details:\n";
            echo json_encode($responseBody['body'], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
} catch (\GuzzleHttp\Exception\RequestException $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "\n{$RED}❌ Request Failed ({$duration}ms){$NC}\n";
    
    if ($e->hasResponse()) {
        $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
        echo "Error Response:\n";
        echo json_encode($errorResponse, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "\n{$RED}❌ Unexpected Error{$NC}\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n{$CYAN}=== Test Complete ==={$NC}\n\n";