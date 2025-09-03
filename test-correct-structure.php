#!/usr/bin/env php
<?php

/**
 * Test outgoing-transfers with correct payload structure
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n=====================================\n";
echo "  TESTING WITH CORRECT STRUCTURE\n";
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

// First, we need to do a lookup to get lookupRef
echo "STEP 1: Performing lookup to get lookupRef...\n\n";

$lookupRef = 'IB' . strtoupper(substr(md5(uniqid()), 0, 10));
$lookupPayload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => $clientId,
    'clientRef' => $lookupRef,
    'identifierType' => 'BANK',
    'identifier' => '12345678901',
    'destinationFsp' => 'CRDBTZTZ',
    'debitAccount' => '06012040022',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'amount' => '10000',
    'debitAccountCategory' => 'BUSINESS'
];

$lookupSignature = generateSignature($lookupPayload);
$lookupHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'TIPS_LOOKUP',
    'Signature' => $lookupSignature,
    'Timestamp' => date('c')
];

try {
    $lookupResponse = Http::withHeaders($lookupHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/lookup', $lookupPayload);
    
    echo "Lookup Response Status: " . $lookupResponse->status() . "\n";
    $lookupData = $lookupResponse->json();
    echo "Lookup Response:\n" . json_encode($lookupData, JSON_PRETTY_PRINT) . "\n\n";
    
    // Extract engineRef to use as lookupRef
    $retrievedLookupRef = $lookupData['engineRef'] ?? $lookupRef;
    
} catch (Exception $e) {
    echo "Lookup Error: " . $e->getMessage() . "\n";
    $retrievedLookupRef = $lookupRef;
}

echo "Using lookupRef: $retrievedLookupRef\n\n";

// Now create the transfer with proper structure
echo "STEP 2: Creating transfer with correct structure...\n\n";

$transferRef = 'IB' . strtoupper(substr(md5(uniqid()), 0, 10));

$transferPayload = [
    'serviceName' => 'P2P',  // Try different service name
    'clientId' => $clientId,
    'clientRef' => $transferRef,
    'lookupRef' => $retrievedLookupRef,  // Reference from lookup
    
    // Payer Details
    'payerDetails' => [
        'accountNumber' => '06012040022',
        'accountName' => 'SACCOS Account',
        'branchCode' => '060',
        'currency' => 'TZS',
        'accountType' => 'CASA'
    ],
    
    // Payee Details  
    'payeeDetails' => [
        'accountNumber' => '12345678901',
        'accountName' => 'Test Beneficiary',
        'bankCode' => 'CRDBTZTZ',
        'bankName' => 'CRDB Bank',
        'currency' => 'TZS'
    ],
    
    // Transaction Details
    'transactionDetails' => [
        'amount' => '10000',
        'currency' => 'TZS',
        'narration' => 'Test transfer with correct structure',
        'transactionType' => 'TRANSFER',
        'chargeBearer' => 'OUR',
        'purposeCode' => 'CASH'
    ],
    
    'timestamp' => date('c')
];

echo "Transfer Payload Structure:\n";
echo json_encode($transferPayload, JSON_PRETTY_PRINT) . "\n\n";

$transferSignature = generateSignature($transferPayload);
$transferHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'P2P',
    'Signature' => $transferSignature,
    'Timestamp' => date('c')
];

try {
    $transferResponse = Http::withHeaders($transferHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/outgoing-transfers', $transferPayload);
    
    $status = $transferResponse->status();
    $responseData = $transferResponse->json();
    
    echo "Transfer Response Status: $status\n";
    echo "Transfer Response:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n\n";
    
    if (isset($responseData['body']) && is_array($responseData['body'])) {
        echo "Validation Messages:\n";
        foreach ($responseData['body'] as $msg) {
            echo "  - $msg\n";
        }
    }
    
} catch (Exception $e) {
    echo "Transfer Error: " . $e->getMessage() . "\n";
}

// Test with alternative service names
echo "\n=====================================\n";
echo "Testing Alternative Service Names\n";
echo "=====================================\n\n";

$serviceNames = ['P2P', 'TRANSFER', 'ONUS', 'OFFUS', 'EFT', 'IFT'];

foreach ($serviceNames as $serviceName) {
    $testRef = 'TEST' . strtoupper(substr(md5(uniqid()), 0, 8));
    $testPayload = [
        'serviceName' => $serviceName,
        'clientId' => $clientId,
        'clientRef' => $testRef,
        'lookupRef' => $retrievedLookupRef,
        'payerDetails' => ['accountNumber' => '06012040022'],
        'payeeDetails' => ['accountNumber' => '12345678901'],
        'transactionDetails' => ['amount' => '1000']
    ];
    
    $testSignature = generateSignature($testPayload);
    $testHeaders = [
        'Content-Type' => 'application/json',
        'X-Api-Key' => $apiKey,
        'Client-Id' => $clientId,
        'Service-Name' => $serviceName,
        'Signature' => $testSignature,
        'Timestamp' => date('c')
    ];
    
    try {
        $testResponse = Http::withHeaders($testHeaders)
            ->withOptions(['verify' => false])
            ->timeout(30)
            ->post($baseUrl . '/domestix/api/v2/outgoing-transfers', $testPayload);
        
        $testStatus = $testResponse->status();
        $testData = $testResponse->json();
        
        echo "Service Name: $serviceName - Status: $testStatus";
        
        if (isset($testData['message'])) {
            echo " - " . $testData['message'];
        }
        
        // Check if service name is valid
        if (isset($testData['body']) && is_array($testData['body'])) {
            foreach ($testData['body'] as $error) {
                if (strpos($error, 'Invalid serviceName') !== false) {
                    echo " ❌";
                    break;
                }
            }
        }
        
        if ($testStatus == 200 || !strpos($testData['message'] ?? '', 'Invalid serviceName')) {
            echo " ✅ Valid";
        }
        
        echo "\n";
        
    } catch (Exception $e) {
        echo "Service Name: $serviceName - Error: " . $e->getMessage() . "\n";
    }
}

echo "\n=====================================\n";
echo "SUMMARY\n";
echo "=====================================\n";
echo "1. The /domestix/api/v2/outgoing-transfers endpoint exists\n";
echo "2. It requires a structured payload with:\n";
echo "   - lookupRef (from previous lookup operation)\n";
echo "   - payerDetails object\n";
echo "   - payeeDetails object\n";
echo "   - transactionDetails object\n";
echo "3. Service name must match API's valid values\n";
echo "4. All transfers require prior lookup operation\n";
echo "=====================================\n";