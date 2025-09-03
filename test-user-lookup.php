#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n=====================================\n";
echo "  TESTING USER PROVIDED LOOKUP\n";
echo "=====================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$baseUrl = 'https://22.32.245.67:443';
$apiKey = config('services.nbc_payments.api_key');

// Generate signature
function generateSignature($payload) {
    $privateKeyPath = storage_path('app/keys/private_key.pem');
    if (!file_exists($privateKeyPath)) {
        return 'DUMMY_SIGNATURE';
    }
    
    try {
        $privateKeyContent = file_get_contents($privateKeyPath);
        $privateKey = openssl_pkey_get_private($privateKeyContent);
        
        if (!$privateKey) {
            return 'INVALID_KEY';
        }
        
        $jsonPayload = json_encode($payload);
        openssl_sign($jsonPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        return base64_encode($signature);
    } catch (Exception $e) {
        return 'ERROR_SIGNATURE';
    }
}

// Generate UUID
function generateUUID() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// Color codes
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$BLUE = "\033[0;34m";
$NC = "\033[0m";

// User provided payload
$payload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => 'APP_IOS',  // Changed from 'IB' to 'APP_IOS'
    'clientRef' => (string)time(),
    'identifierType' => 'MSISDN',
    'identifier' => '0748045601',
    'destinationFsp' => 'VMCASHIN',
    'debitAccount' => '011103033734',  // Different account
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '012',  // Different branch
    'amount' => '900000',
    'debitAccountCategory' => 'BUSINESS'
];

echo "{$YELLOW}Testing MSISDN Lookup with user-provided payload{$NC}\n\n";
echo "Payload Details:\n";
echo "  • Service: TIPS_LOOKUP\n";
echo "  • Client ID: {$GREEN}APP_IOS{$NC} (not 'IB')\n";
echo "  • Identifier Type: MSISDN\n";
echo "  • Phone: 0748045601\n";
echo "  • Provider: VMCASHIN (Vodacom M-Pesa)\n";
echo "  • Debit Account: 011103033734\n";
echo "  • Branch: 012\n";
echo "  • Amount: 900,000 TZS\n\n";

$signature = generateSignature($payload);
$uuid = generateUUID();

// Headers matching the working example format
$headers = [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'X-Trace-Uuid' => 'domestix-' . $uuid,
    'Signature' => $signature,
    'x-api-key' => $apiKey  // lowercase
];

echo "Headers being sent:\n";
foreach ($headers as $key => $value) {
    if ($key === 'Signature' || $key === 'x-api-key') {
        echo "  $key: " . substr($value, 0, 20) . "... (truncated)\n";
    } else {
        echo "  $key: $value\n";
    }
}
echo "\n";

echo "Request URL: {$baseUrl}/domestix/api/v2/lookup\n\n";

try {
    echo "Sending request...\n";
    $startTime = microtime(true);
    
    $response = Http::withHeaders($headers)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/lookup', $payload);
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    $status = $response->status();
    $responseData = $response->json();
    
    echo "Response received in {$duration}ms\n";
    echo "Status Code: ";
    
    if ($status == 200 || $status == 201) {
        echo "{$GREEN}{$status}{$NC}\n\n";
        echo "{$GREEN}✅ LOOKUP SUCCESSFUL!{$NC}\n\n";
        
        if (isset($responseData['accountName'])) {
            echo "Account Name: {$responseData['accountName']}\n";
        }
        if (isset($responseData['engineRef'])) {
            echo "Engine Ref: {$responseData['engineRef']}\n";
        }
        if (isset($responseData['message'])) {
            echo "Message: {$responseData['message']}\n";
        }
        
        echo "\nFull Response:\n";
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
        
    } elseif ($status == 400) {
        echo "{$YELLOW}{$status}{$NC}\n\n";
        echo "{$YELLOW}⚠ Validation Error{$NC}\n";
        
        if (isset($responseData['body'])) {
            echo "Errors:\n";
            foreach ((array)$responseData['body'] as $error) {
                echo "  • $error\n";
            }
        }
        if (isset($responseData['message'])) {
            echo "Message: {$responseData['message']}\n";
        }
        
    } elseif ($status == 502) {
        echo "{$RED}{$status}{$NC}\n\n";
        echo "{$RED}❌ Gateway Error{$NC}\n";
        
        if (isset($responseData['message'])) {
            echo "Error: {$responseData['message']}\n";
        }
        
        echo "\nFull Response:\n";
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
        
    } else {
        echo "{$RED}{$status}{$NC}\n\n";
        echo "{$RED}❌ Unexpected Error{$NC}\n";
        echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "{$RED}Exception: " . $e->getMessage() . "{$NC}\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n{$BLUE}=== Test Completed ==={$NC}\n\n";