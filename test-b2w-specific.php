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
$BLUE = "\033[0;34m";
$CYAN = "\033[0;36m";
$NC = "\033[0m";

echo "\n{$CYAN}================================================\n";
echo "     B2W LOOKUP TEST WITH SPECIFIC PARAMETERS\n";
echo "================================================{$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Prepare the request
$baseUrl = 'https://22.32.245.67:443';
$apiKey = env('NBC_PAYMENTS_API_KEY');

// Generate alphanumeric reference
$clientRef = 'REF' . strtoupper(bin2hex(random_bytes(8)));

$payload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => 'APP_IOS',
    'clientRef' => $clientRef,
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
echo "• Endpoint: {$baseUrl}/domestix/api/v2/lookup\n";
echo "• Service: TIPS_LOOKUP\n";
echo "• Identifier Type: MSISDN\n";
echo "• Phone: 0748045601\n";
echo "• Destination FSP: VMCASHIN (M-Pesa)\n";
echo "• Amount: TZS 900,000\n";
echo "• Client Ref: {$clientRef}\n\n";

echo "{$YELLOW}Payload:{$NC}\n";
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

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
            'x-api-key' => $apiKey
        ]
    ]);

    $response = $client->post($baseUrl . '/domestix/api/v2/lookup', [
        'json' => $payload
    ]);

    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $statusCode = $response->getStatusCode();
    $responseBody = json_decode($response->getBody()->getContents(), true);

    echo "\n{$YELLOW}Response received in {$duration}ms{$NC}\n";
    echo "Status Code: {$statusCode}\n\n";

    if (isset($responseBody['statusCode']) && $responseBody['statusCode'] == 600) {
        echo "{$GREEN}✅ B2W LOOKUP SUCCESS{$NC}\n\n";
        
        $data = $responseBody['data'] ?? [];
        $body = $data['body'] ?? [];
        
        echo "{$YELLOW}Account Information:{$NC}\n";
        echo "• Account Name: {$GREEN}" . ($body['fullName'] ?? 'N/A') . "{$NC}\n";
        echo "• Phone Number: " . ($body['msisdn'] ?? $payload['identifier']) . "\n";
        echo "• FSP ID: " . ($body['fspId'] ?? 'N/A') . "\n";
        echo "• Actual Identifier: " . ($body['actualIdentifier'] ?? 'N/A') . "\n";
        echo "• Can Receive: " . (isset($body['canReceive']) ? ($body['canReceive'] ? 'Yes' : 'No') : 'N/A') . "\n";
        
        if (isset($data['engineRef'])) {
            echo "• Engine Reference: " . substr($data['engineRef'], 0, 40) . "...\n";
        }
        
        echo "\n{$YELLOW}Full Response Body:{$NC}\n";
        echo json_encode($body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        
    } else {
        echo "{$RED}❌ B2W LOOKUP FAILED{$NC}\n";
        echo "Status Code: " . ($responseBody['statusCode'] ?? 'Unknown') . "\n";
        echo "Message: " . ($responseBody['message'] ?? 'No message') . "\n";
        
        if (isset($responseBody['data']['body']['errorDescription'])) {
            echo "Error: {$RED}" . $responseBody['data']['body']['errorDescription'] . "{$NC}\n";
        }
        
        echo "\n{$YELLOW}Full Response:{$NC}\n";
        echo json_encode($responseBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    }
    
} catch (\GuzzleHttp\Exception\RequestException $e) {
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    echo "\n{$RED}❌ Request Failed ({$duration}ms){$NC}\n";
    
    if ($e->hasResponse()) {
        $errorResponse = json_decode($e->getResponse()->getBody()->getContents(), true);
        echo "Status Code: " . $e->getResponse()->getStatusCode() . "\n";
        echo "Error Response:\n";
        echo json_encode($errorResponse, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
} catch (Exception $e) {
    echo "\n{$RED}❌ Unexpected Error{$NC}\n";
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n{$BLUE}=== Test Complete ==={$NC}\n\n";