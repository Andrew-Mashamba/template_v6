#!/usr/bin/env php
<?php

/**
 * Test Payment APIs with Corrected Reference Format
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n=====================================\n";
echo "  TESTING WITH CORRECTED REFERENCES\n";
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

// Test TIPS lookup with corrected reference
echo "Testing TIPS Lookup with alphanumeric reference...\n\n";

// Generate alphanumeric reference (no underscores)
$clientRef = 'IB' . strtoupper(substr(md5(uniqid()), 0, 10));

$payload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => $clientId,
    'clientRef' => $clientRef,  // Alphanumeric only
    'identifierType' => 'BANK',
    'identifier' => '12345678901',
    'destinationFsp' => 'NMIBTZTZ',
    'debitAccount' => '06012040022',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'amount' => '1',
    'debitAccountCategory' => 'BUSINESS'
];

echo "Client Reference (alphanumeric): $clientRef\n";
echo "Request Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

$signature = generateSignature($payload);

$headers = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'TIPS_LOOKUP',
    'Signature' => $signature,
    'Timestamp' => date('c')
];

try {
    $response = Http::withHeaders($headers)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/lookup', $payload);
    
    echo "Response Status: " . $response->status() . "\n";
    echo "Response Body:\n";
    echo json_encode($response->json(), JSON_PRETTY_PRINT) . "\n\n";
    
    if ($response->status() === 400) {
        $body = $response->json();
        if (isset($body['body']) && is_array($body['body'])) {
            echo "Validation Errors:\n";
            foreach ($body['body'] as $error) {
                echo "  - $error\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Test with services
echo "\n=====================================\n";
echo "Testing Updated Services\n";
echo "=====================================\n\n";

use App\Services\Payments\ExternalFundsTransferService;

$eftService = app(ExternalFundsTransferService::class);

// Use reflection to test the reference generation
$reflection = new ReflectionClass($eftService);
$method = $reflection->getMethod('generateReference');
$method->setAccessible(true);

echo "Testing Reference Generation:\n";
for ($i = 0; $i < 5; $i++) {
    $ref = $method->invoke($eftService, 'TEST');
    echo "  Generated: $ref";
    
    // Validate it's alphanumeric only
    if (preg_match('/^[A-Z0-9]+$/', $ref)) {
        echo " ✅ Valid (alphanumeric)\n";
    } else {
        echo " ❌ Invalid (contains special characters)\n";
    }
}

echo "\n=====================================\n";
echo "Summary:\n";
echo "- References are now alphanumeric only\n";
echo "- No underscores or special characters\n";
echo "- NBC API validation should pass\n";
echo "=====================================\n";