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
$BLUE = "\033[0;34m";
$NC = "\033[0m";

echo "\n{$CYAN}================================================\n";
echo "     FSP RETRIEVAL TEST\n";
echo "================================================{$NC}\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Generate required values
$uuid = \Illuminate\Support\Str::uuid()->toString();
$clientRef = 'CREF' . time();
$isoTimestamp = date('c'); // ISO 8601 format

$payload = [
    'serviceName' => 'FSP_RETRIEVAL',
    'clientId' => 'APP_ANDROID',
    'clientRef' => $clientRef,
    'timestamp' => $isoTimestamp
];

$headers = [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'X-Trace-Uuid' => 'domestix-' . $uuid,
    'Signature' => 'asdasdasdasd',
    'x-api-key' => 'MDcyNjY2NWVkZDlkYTJmYWZiZTFiODFhNDQ5MWNkNTY3ODZhZjA2NTNiOTMwNzNiODVkMzVlOTNmN2UxZDE5NTUwZjc3M2I5MzQwYmRlZGRiYzdlMjUxMmU5NGUxMmQ4NmQxOGQ1NTIyYmM3YzlkNjYyY2U2ZjE2YjZhNjFkZjU='
];

echo "{$YELLOW}Request Details:{$NC}\n";
echo "• Endpoint: https://22.32.245.67/domestix/info/api/v2/financial-service-providers\n";
echo "• Service: FSP_RETRIEVAL\n";
echo "• Client ID: APP_ANDROID\n";
echo "• Client Ref: {$clientRef}\n";
echo "• Trace UUID: domestix-{$uuid}\n";
echo "• Timestamp: {$isoTimestamp}\n\n";

echo "{$YELLOW}Headers:{$NC}\n";
foreach ($headers as $key => $value) {
    if ($key === 'x-api-key') {
        echo "• {$key}: " . substr($value, 0, 20) . "...\n";
    } else {
        echo "• {$key}: {$value}\n";
    }
}

echo "\n{$YELLOW}Payload:{$NC}\n";
echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";

echo "{$YELLOW}Sending request...{$NC}\n";
$startTime = microtime(true);

try {
    $client = new \GuzzleHttp\Client([
        'verify' => false,
        'timeout' => 30,
        'connect_timeout' => 10
    ]);

    $response = $client->post('https://22.32.245.67/domestix/info/api/v2/financial-service-providers', [
        'headers' => $headers,
        'json' => $payload
    ]);

    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $statusCode = $response->getStatusCode();
    $responseBody = json_decode($response->getBody()->getContents(), true);

    echo "\n{$GREEN}Response received in {$duration}ms{$NC}\n";
    echo "HTTP Status: {$statusCode}\n\n";

    if (isset($responseBody['statusCode']) && $responseBody['statusCode'] == 600) {
        echo "{$GREEN}✅ FSP RETRIEVAL SUCCESS{$NC}\n\n";
        
        if (isset($responseBody['data']['body']) && is_array($responseBody['data']['body'])) {
            $fsps = $responseBody['data']['body'];
            echo "{$YELLOW}Financial Service Providers ({count($fsps)} found):{$NC}\n\n";
            
            // Group by type
            $banks = [];
            $wallets = [];
            
            foreach ($fsps as $fsp) {
                if (isset($fsp['fspType']) && $fsp['fspType'] === 'BANK') {
                    $banks[] = $fsp;
                } else {
                    $wallets[] = $fsp;
                }
            }
            
            if (count($banks) > 0) {
                echo "{$BLUE}BANKS:{$NC}\n";
                foreach ($banks as $bank) {
                    echo "• {$GREEN}" . ($bank['fspName'] ?? 'N/A') . "{$NC}\n";
                    echo "  - Code: " . ($bank['fspCode'] ?? 'N/A') . "\n";
                    echo "  - ID: " . ($bank['fspId'] ?? 'N/A') . "\n";
                    echo "  - BIC: " . ($bank['bicCode'] ?? 'N/A') . "\n";
                    echo "  - Status: " . ($bank['status'] ?? 'N/A') . "\n";
                    echo "\n";
                }
            }
            
            if (count($wallets) > 0) {
                echo "{$BLUE}MOBILE WALLETS:{$NC}\n";
                foreach ($wallets as $wallet) {
                    echo "• {$GREEN}" . ($wallet['fspName'] ?? 'N/A') . "{$NC}\n";
                    echo "  - Code: " . ($wallet['fspCode'] ?? 'N/A') . "\n";
                    echo "  - ID: " . ($wallet['fspId'] ?? 'N/A') . "\n";
                    echo "  - Type: " . ($wallet['fspType'] ?? 'N/A') . "\n";
                    echo "  - Status: " . ($wallet['status'] ?? 'N/A') . "\n";
                    echo "\n";
                }
            }
            
            echo "{$YELLOW}Summary:{$NC}\n";
            echo "• Total FSPs: " . count($fsps) . "\n";
            echo "• Banks: " . count($banks) . "\n";
            echo "• Mobile Wallets: " . count($wallets) . "\n";
            
        } else {
            echo "No FSP data in response\n";
        }
        
    } else {
        echo "{$RED}❌ FSP RETRIEVAL FAILED{$NC}\n";
        echo "Status Code: " . ($responseBody['statusCode'] ?? 'Unknown') . "\n";
        echo "Message: " . ($responseBody['message'] ?? 'No message') . "\n";
        
        if (isset($responseBody['data']['body'])) {
            echo "\nResponse Body:\n";
            echo json_encode($responseBody['data']['body'], JSON_PRETTY_PRINT) . "\n";
        }
    }
    
    // Show full response for debugging
    echo "\n{$YELLOW}Full Response:{$NC}\n";
    echo json_encode($responseBody, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
    
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