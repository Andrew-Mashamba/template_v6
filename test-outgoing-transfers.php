#!/usr/bin/env php
<?php

/**
 * Test the /domestix/api/v2/outgoing-transfers endpoint
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "\n=====================================\n";
echo "  TESTING OUTGOING TRANSFERS ENDPOINT\n";
echo "=====================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$baseUrl = 'https://22.32.245.67:443';
$apiKey = config('services.nbc_payments.api_key');
$clientId = 'IB';

// Generate signature
function generateSignature($payload) {
    $privateKeyPath = storage_path('app/keys/private_key.pem');
    if (!file_exists($privateKeyPath)) {
        echo "Warning: Private key not found\n";
        return 'DUMMY_SIGNATURE';
    }
    
    try {
        $privateKeyContent = file_get_contents($privateKeyPath);
        $privateKey = openssl_pkey_get_private($privateKeyContent);
        
        if (!$privateKey) {
            echo "Warning: Failed to load private key\n";
            return 'INVALID_KEY';
        }
        
        $jsonPayload = json_encode($payload);
        openssl_sign($jsonPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    } catch (Exception $e) {
        echo "Warning: Signature generation failed: " . $e->getMessage() . "\n";
        return 'ERROR_SIGNATURE';
    }
}

// Color codes
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$BLUE = "\033[0;34m";
$NC = "\033[0m";

echo "{$BLUE}Testing endpoint: POST $baseUrl/domestix/api/v2/outgoing-transfers{$NC}\n\n";

// =============================================================================
// TEST 1: Check endpoint with minimal payload
// =============================================================================
echo "{$YELLOW}TEST 1: Checking endpoint availability with minimal payload{$NC}\n";

$minimalPayload = [
    'serviceName' => 'TIPS_TRANSFER',
    'clientId' => $clientId,
    'clientRef' => 'TEST' . strtoupper(substr(md5(uniqid()), 0, 10))
];

$headers = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Timestamp' => date('c')
];

echo "Request Headers:\n";
foreach ($headers as $key => $value) {
    if ($key === 'X-Api-Key') {
        echo "  $key: " . substr($value, 0, 10) . "...[MASKED]\n";
    } else {
        echo "  $key: $value\n";
    }
}

echo "\nRequest Body:\n";
echo json_encode($minimalPayload, JSON_PRETTY_PRINT) . "\n\n";

try {
    $response = Http::withHeaders($headers)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/outgoing-transfers', $minimalPayload);
    
    $status = $response->status();
    $responseBody = $response->json() ?? $response->body();
    
    if ($status === 404) {
        echo "{$RED}✗ Endpoint NOT FOUND (404){$NC}\n";
    } elseif ($status === 400 || $status === 401 || $status === 403) {
        echo "{$GREEN}✓ Endpoint EXISTS{$NC} (Status: $status)\n";
    } elseif ($status === 200 || $status === 201) {
        echo "{$GREEN}✓ Endpoint EXISTS and ACCEPTED request{$NC}\n";
    } else {
        echo "{$YELLOW}⚠ Endpoint returned status: $status{$NC}\n";
    }
    
    echo "\nResponse Status: $status\n";
    echo "Response Body:\n";
    if (is_array($responseBody)) {
        echo json_encode($responseBody, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $responseBody . "\n";
    }
    
} catch (Exception $e) {
    echo "{$RED}Error: " . $e->getMessage() . "{$NC}\n";
}

// =============================================================================
// TEST 2: Test with complete transfer payload
// =============================================================================
echo "\n{$YELLOW}TEST 2: Testing with complete transfer payload{$NC}\n";

// Generate alphanumeric reference
$clientRef = 'IB' . strtoupper(substr(md5(uniqid()), 0, 10));

$transferPayload = [
    'serviceName' => 'TIPS_TRANSFER',
    'clientId' => $clientId,
    'clientRef' => $clientRef,
    'identifierType' => 'BANK',
    'identifier' => '12345678901',
    'destinationFsp' => 'CRDBTZTZ', // Try different bank code
    'debitAccount' => '06012040022',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'creditAccount' => '12345678901',
    'creditAccountName' => 'Test Beneficiary',
    'amount' => '10000',
    'currency' => 'TZS',
    'narration' => 'Test transfer',
    'debitAccountCategory' => 'BUSINESS',
    'chargeBearer' => 'OUR',
    'timestamp' => date('c')
];

$signature = generateSignature($transferPayload);

$transferHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'TIPS_TRANSFER',
    'Signature' => $signature,
    'Timestamp' => date('c')
];

echo "\nRequest Headers:\n";
foreach ($transferHeaders as $key => $value) {
    if (in_array($key, ['X-Api-Key', 'Signature'])) {
        echo "  $key: " . substr($value, 0, 10) . "...[MASKED]\n";
    } else {
        echo "  $key: $value\n";
    }
}

echo "\nRequest Body:\n";
echo json_encode($transferPayload, JSON_PRETTY_PRINT) . "\n\n";

try {
    $response = Http::withHeaders($transferHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/outgoing-transfers', $transferPayload);
    
    $status = $response->status();
    $responseBody = $response->json() ?? $response->body();
    
    echo "Response Status: $status\n";
    echo "Response Headers:\n";
    $responseHeaders = $response->headers();
    foreach ($responseHeaders as $key => $values) {
        if (is_array($values)) {
            echo "  $key: " . implode(', ', $values) . "\n";
        } else {
            echo "  $key: $values\n";
        }
    }
    
    echo "\nResponse Body:\n";
    if (is_array($responseBody)) {
        echo json_encode($responseBody, JSON_PRETTY_PRINT) . "\n";
        
        // Check for specific error messages
        if (isset($responseBody['message'])) {
            echo "\n{$YELLOW}API Message: {$responseBody['message']}{$NC}\n";
        }
        if (isset($responseBody['body']) && is_array($responseBody['body'])) {
            echo "{$YELLOW}Validation Errors:{$NC}\n";
            foreach ($responseBody['body'] as $error) {
                echo "  - $error\n";
            }
        }
    } else {
        echo $responseBody . "\n";
    }
    
} catch (Exception $e) {
    echo "{$RED}Error: " . $e->getMessage() . "{$NC}\n";
}

// =============================================================================
// TEST 3: Test with Mobile Wallet Transfer
// =============================================================================
echo "\n{$YELLOW}TEST 3: Testing with Mobile Wallet Transfer{$NC}\n";

$walletRef = 'WALLET' . strtoupper(substr(md5(uniqid()), 0, 10));

$walletPayload = [
    'serviceName' => 'TIPS_WALLET_TRANSFER',
    'clientId' => $clientId,
    'clientRef' => $walletRef,
    'identifierType' => 'MSISDN',
    'identifier' => '255715000000',
    'destinationFsp' => 'VMCASHIN', // M-Pesa
    'debitAccount' => '06012040022',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'amount' => '5000',
    'currency' => 'TZS',
    'narration' => 'Test wallet transfer',
    'debitAccountCategory' => 'BUSINESS',
    'receiverName' => 'Test User',
    'receiverPhone' => '255715000000',
    'chargeBearer' => 'OUR',
    'timestamp' => date('c')
];

$walletSignature = generateSignature($walletPayload);

$walletHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'TIPS_WALLET_TRANSFER',
    'Signature' => $walletSignature,
    'Timestamp' => date('c')
];

echo "\nTesting Mobile Wallet Transfer...\n";
echo "Destination: M-Pesa (VMCASHIN)\n";
echo "Phone: 255715000000\n\n";

try {
    $response = Http::withHeaders($walletHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/outgoing-transfers', $walletPayload);
    
    $status = $response->status();
    $responseBody = $response->json() ?? $response->body();
    
    echo "Response Status: $status\n";
    if (is_array($responseBody)) {
        echo json_encode($responseBody, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $responseBody . "\n";
    }
    
} catch (Exception $e) {
    echo "{$RED}Error: " . $e->getMessage() . "{$NC}\n";
}

// =============================================================================
// SUMMARY
// =============================================================================
echo "\n=====================================\n";
echo "ENDPOINT ANALYSIS SUMMARY\n";
echo "=====================================\n";

echo "Endpoint: {$BLUE}/domestix/api/v2/outgoing-transfers{$NC}\n\n";

echo "Available Operations:\n";
echo "1. Bank-to-Bank transfers (TIPS)\n";
echo "2. Bank-to-Wallet transfers\n";
echo "3. External transfers < 20M TZS\n\n";

echo "Required Headers:\n";
echo "- Content-Type: application/json\n";
echo "- X-Api-Key: [Your API Key]\n";
echo "- Client-Id: [Your Client ID]\n";
echo "- Service-Name: [TIPS_TRANSFER or TIPS_WALLET_TRANSFER]\n";
echo "- Signature: [Digital signature of payload]\n";
echo "- Timestamp: [ISO 8601 timestamp]\n\n";

echo "Key Requirements:\n";
echo "- clientRef must be alphanumeric only\n";
echo "- FSP codes must be onboarded in the system\n";
echo "- Digital signature required for authentication\n";
echo "- Amount limits apply for different transfer types\n";

echo "\n=====================================\n";