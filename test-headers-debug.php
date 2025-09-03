#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n=====================================\n";
echo "  NBC API HEADERS DEBUG TEST\n";
echo "=====================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$baseUrl = 'https://22.32.245.67:443';
$apiKey = config('services.nbc_payments.api_key');
$clientId = 'IB';

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

$lookupRef = 'CLREF' . time();
$payload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => $clientId,
    'clientRef' => $lookupRef,
    'identifierType' => 'BANK',
    'identifier' => '12334567789',
    'destinationFsp' => 'CORUTZTZ',
    'debitAccount' => '06012040022',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'amount' => '1000',
    'debitAccountCategory' => 'BUSINESS'
];

$signature = generateSignature($payload);
$uuid = generateUUID();

echo "Testing different header combinations:\n\n";

// Test 1: Headers in exact order from working example
echo "Test 1: Headers in working example order\n";
$headers1 = [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'X-Trace-Uuid' => 'domestix-' . $uuid,
    'Signature' => $signature,
    'x-api-key' => $apiKey  // lowercase like in the example
];

echo "Headers being sent:\n";
foreach ($headers1 as $key => $value) {
    echo "  $key: " . (strlen($value) > 50 ? substr($value, 0, 50) . '...' : $value) . "\n";
}

try {
    $response = Http::withHeaders($headers1)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/lookup', $payload);
    
    echo "Response Status: " . $response->status() . "\n";
    $data = $response->json();
    if (isset($data['message'])) {
        echo "Message: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 2: Try with Authorization Bearer header
echo "Test 2: With Authorization Bearer header\n";
$headers2 = [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'Authorization' => 'Bearer ' . $apiKey,
    'X-Trace-Uuid' => 'domestix-' . generateUUID(),
    'Signature' => $signature,
    'x-api-key' => $apiKey
];

try {
    $response = Http::withHeaders($headers2)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/lookup', $payload);
    
    echo "Response Status: " . $response->status() . "\n";
    $data = $response->json();
    if (isset($data['message'])) {
        echo "Message: " . $data['message'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n";

// Test 3: Without Client-Id header
echo "Test 3: Without Client-Id header (not in working example)\n";
$headers3 = [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'X-Trace-Uuid' => 'domestix-' . generateUUID(),
    'Signature' => $signature,
    'x-api-key' => $apiKey  // lowercase
];

try {
    $response = Http::withHeaders($headers3)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/lookup', $payload);
    
    echo "Response Status: " . $response->status() . "\n";
    $data = $response->json();
    if (isset($data['message'])) {
        echo "Message: " . $data['message'] . "\n";
    }
    if (isset($data['body'])) {
        echo "Body: " . json_encode($data['body']) . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTest completed.\n";