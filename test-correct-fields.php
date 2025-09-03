#!/usr/bin/env php
<?php

/**
 * Test with correct field structure based on working example
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n=====================================\n";
echo "  TESTING WITH CORRECT FIELD STRUCTURE\n";
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

// Color codes
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$BLUE = "\033[0;34m";
$NC = "\033[0m";

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

// =============================================================================
// TEST 1: B2W Transfer with Correct Structure
// =============================================================================
echo "{$BLUE}=== B2W TRANSFER WITH CORRECT STRUCTURE ==={$NC}\n\n";

// Step 1: Lookup first
echo "{$YELLOW}Step 1: Performing Lookup{$NC}\n";

$lookupRef = 'CLREF' . time();
$lookupPayload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => $clientId,
    'clientRef' => $lookupRef,
    'identifierType' => 'MSISDN',
    'identifier' => '255715000000',
    'destinationFsp' => 'VMCASHIN',
    'debitAccount' => '06012040022',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'amount' => '5000',
    'debitAccountCategory' => 'BUSINESS'
];

$lookupSignature = generateSignature($lookupPayload);
$lookupHeaders = [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
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
    
    $lookupData = $lookupResponse->json();
    echo "Lookup Response Status: " . $lookupResponse->status() . "\n";
    
    if (isset($lookupData['engineRef'])) {
        $engineRef = $lookupData['engineRef'];
        echo "Engine Ref: $engineRef\n\n";
    } else {
        $engineRef = 'LOOKUPREF' . time();
        echo "Using generated ref: $engineRef\n\n";
    }
    
} catch (Exception $e) {
    echo "Lookup Error: " . $e->getMessage() . "\n";
    $engineRef = 'LOOKUPREF' . time();
}

// Step 2: Transfer with correct structure
echo "{$YELLOW}Step 2: B2W Transfer{$NC}\n";

$timestamp = time();
$clientRef = 'CLREF' . $timestamp;
$customerRef = 'CUSTOMERREF' . $timestamp;
$initiatorId = (string)$timestamp;
$randomAmount = rand(100, 999) . '00'; // Random amount like 10000, 25500, etc

$transferPayload = [
    'serviceName' => 'TIPS_B2W_TRANSFER',
    'clientId' => $clientId,
    'clientRef' => $clientRef,
    'customerRef' => $customerRef,
    'lookupRef' => $engineRef,
    'timestamp' => date('c'),
    'callbackUrl' => 'http://localhost:90/post',  // This will be replaced with actual callback
    
    'payerDetails' => [
        'identifierType' => 'BANK',
        'identifier' => '06012040022',
        'phoneNumber' => '255715000001',  // Payer's phone
        'initiatorId' => $initiatorId,
        'branchCode' => '060',
        'fspId' => '060',  // NBC FSP ID
        'fullName' => 'SACCOS NBC ACCOUNT',
        'accountCategory' => 'BUSINESS',  // Changed from PERSON to BUSINESS for SACCOS
        'accountType' => 'BANK',
        'identity' => [
            'type' => '',
            'value' => ''
        ]
    ],
    
    'payeeDetails' => [
        'identifierType' => 'MSISDN',
        'identifier' => '0715000000',  // Without country code for wallet
        'fspId' => '504',  // Vodacom FSP ID
        'destinationFsp' => 'VMCASHIN',
        'fullName' => 'Test Wallet User',
        'accountCategory' => 'PERSON',
        'accountType' => 'WALLET',
        'identity' => [
            'type' => '',
            'value' => ''
        ]
    ],
    
    'transactionDetails' => [
        'debitAmount' => $randomAmount,
        'debitCurrency' => 'TZS',
        'creditAmount' => $randomAmount,
        'creditCurrency' => 'TZS',
        'productCode' => '',
        'isServiceChargeApplicable' => true,
        'serviceChargeBearer' => 'OUR'
    ],
    
    'remarks' => 'Test B2W Transfer from SACCOS to M-Pesa'
];

echo "Transfer Payload:\n";
echo json_encode($transferPayload, JSON_PRETTY_PRINT) . "\n\n";

$transferSignature = generateSignature($transferPayload);
$uuid = generateUUID();

$transferHeaders = [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'X-Trace-Uuid' => 'domestix-' . $uuid,
    'Signature' => $transferSignature,
    'X-Api-Key' => $apiKey
];

echo "Request Headers:\n";
foreach ($transferHeaders as $key => $value) {
    if (in_array($key, ['X-Api-Key', 'Signature'])) {
        echo "  $key: " . substr($value, 0, 20) . "...[MASKED]\n";
    } else {
        echo "  $key: $value\n";
    }
}
echo "\n";

try {
    $transferResponse = Http::withHeaders($transferHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/outgoing-transfers', $transferPayload);
    
    $status = $transferResponse->status();
    $responseData = $transferResponse->json();
    
    echo "Transfer Response Status: $status\n";
    
    if ($status == 200 || $status == 201) {
        echo "{$GREEN}✅ Transfer Successful!{$NC}\n";
    } elseif ($status == 400) {
        echo "{$YELLOW}⚠ Validation Error{$NC}\n";
        if (isset($responseData['body']) && is_array($responseData['body'])) {
            echo "Validation Messages:\n";
            foreach ($responseData['body'] as $msg) {
                echo "  • $msg\n";
            }
        }
    } else {
        echo "{$RED}❌ Error{$NC}\n";
    }
    
    echo "\nFull Response:\n";
    echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
    
} catch (Exception $e) {
    echo "{$RED}Transfer Error: " . $e->getMessage() . "{$NC}\n";
}

// =============================================================================
// TEST 2: B2B Transfer with Correct Structure
// =============================================================================
echo "\n{$BLUE}=== B2B TRANSFER WITH CORRECT STRUCTURE ==={$NC}\n\n";

$b2bTimestamp = time();
$b2bClientRef = 'CLREF' . $b2bTimestamp;
$b2bCustomerRef = 'CUSTOMERREF' . $b2bTimestamp;
$b2bInitiatorId = (string)$b2bTimestamp;
$b2bAmount = rand(100, 999) . '00';

$b2bPayload = [
    'serviceName' => 'TIPS_B2B_TRANSFER',  // Assuming B2B service name
    'clientId' => $clientId,
    'clientRef' => $b2bClientRef,
    'customerRef' => $b2bCustomerRef,
    'lookupRef' => 'LOOKUPREF' . $b2bTimestamp,  // Would come from B2B lookup
    'timestamp' => date('c'),
    'callbackUrl' => 'http://localhost:90/post',
    
    'payerDetails' => [
        'identifierType' => 'BANK',
        'identifier' => '06012040022',
        'phoneNumber' => '255715000001',
        'initiatorId' => $b2bInitiatorId,
        'branchCode' => '060',
        'fspId' => '060',
        'fullName' => 'SACCOS NBC ACCOUNT',
        'accountCategory' => 'BUSINESS',
        'accountType' => 'BANK',
        'identity' => [
            'type' => '',
            'value' => ''
        ]
    ],
    
    'payeeDetails' => [
        'identifierType' => 'BANK',  // BANK for B2B
        'identifier' => '12345678901',  // Bank account number
        'fspId' => '030',  // CRDB FSP ID (example)
        'destinationFsp' => 'CRDBTZTZ',  // Bank code
        'fullName' => 'Test Bank Account',
        'accountCategory' => 'PERSON',
        'accountType' => 'BANK',  // BANK for B2B
        'identity' => [
            'type' => '',
            'value' => ''
        ]
    ],
    
    'transactionDetails' => [
        'debitAmount' => $b2bAmount,
        'debitCurrency' => 'TZS',
        'creditAmount' => $b2bAmount,
        'creditCurrency' => 'TZS',
        'productCode' => '',
        'isServiceChargeApplicable' => true,
        'serviceChargeBearer' => 'OUR'
    ],
    
    'remarks' => 'Test B2B Transfer from SACCOS to External Bank'
];

echo "B2B Transfer Payload:\n";
echo "  From: NBC Account (BANK)\n";
echo "  To: External Bank Account (BANK)\n";
echo "  Amount: $b2bAmount TZS\n\n";

$b2bSignature = generateSignature($b2bPayload);
$b2bUuid = generateUUID();

$b2bHeaders = [
    'Accept' => 'application/json',
    'Content-Type' => 'application/json',
    'X-Trace-Uuid' => 'domestix-' . $b2bUuid,
    'Signature' => $b2bSignature,
    'X-Api-Key' => $apiKey
];

try {
    $b2bResponse = Http::withHeaders($b2bHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/outgoing-transfers', $b2bPayload);
    
    $b2bStatus = $b2bResponse->status();
    $b2bResponseData = $b2bResponse->json();
    
    echo "B2B Transfer Response Status: $b2bStatus\n";
    
    if ($b2bStatus == 200 || $b2bStatus == 201) {
        echo "{$GREEN}✅ B2B Transfer Successful!{$NC}\n";
    } else {
        echo "{$YELLOW}Status: " . ($b2bResponseData['message'] ?? 'Unknown') . "{$NC}\n";
    }
    
} catch (Exception $e) {
    echo "{$RED}B2B Transfer Error: " . $e->getMessage() . "{$NC}\n";
}

// =============================================================================
// SUMMARY
// =============================================================================
echo "\n=====================================\n";
echo "KEY FIELD MAPPINGS\n";
echo "=====================================\n\n";

echo "{$BLUE}Required Fields from Example:{$NC}\n";
echo "✅ serviceName: TIPS_B2W_TRANSFER (for B2W)\n";
echo "✅ clientRef: CLREF + timestamp\n";
echo "✅ customerRef: CUSTOMERREF + timestamp\n";
echo "✅ lookupRef: From lookup response\n";
echo "✅ callbackUrl: Webhook for async response\n\n";

echo "{$BLUE}Payer Details (Bank Account):{$NC}\n";
echo "✅ identifierType: BANK\n";
echo "✅ identifier: Account number\n";
echo "✅ phoneNumber: Contact phone\n";
echo "✅ initiatorId: Unique ID\n";
echo "✅ branchCode: Branch code\n";
echo "✅ fspId: FSP ID\n";
echo "✅ fullName: Account name\n";
echo "✅ accountCategory: BUSINESS/PERSON\n";
echo "✅ accountType: BANK\n";
echo "✅ identity: {type:'', value:''}\n\n";

echo "{$BLUE}Payee Details (Wallet/Bank):{$NC}\n";
echo "For Wallet:\n";
echo "  • identifierType: MSISDN\n";
echo "  • identifier: Phone (without country code)\n";
echo "  • accountType: WALLET\n";
echo "  • destinationFsp: VMCASHIN/TPCASHIN/etc\n\n";

echo "For Bank:\n";
echo "  • identifierType: BANK\n";
echo "  • identifier: Account number\n";
echo "  • accountType: BANK\n";
echo "  • destinationFsp: Bank code\n\n";

echo "{$BLUE}Transaction Details:{$NC}\n";
echo "✅ debitAmount & creditAmount\n";
echo "✅ debitCurrency & creditCurrency\n";
echo "✅ isServiceChargeApplicable: true/false\n";
echo "✅ serviceChargeBearer: OUR/BEN/SHA\n";

echo "\n=====================================\n";