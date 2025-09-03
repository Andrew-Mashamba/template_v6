#!/usr/bin/env php
<?php

/**
 * Test B2B (Bank-to-Bank) and B2W (Bank-to-Wallet) transfers
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

echo "\n=====================================\n";
echo "  TESTING B2B AND B2W TRANSFERS\n";
echo "=====================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$baseUrl = 'https://22.32.245.67:443';
$apiKey = config('services.nbc_payments.api_key');
$clientId = 'IB';

// Color codes
$GREEN = "\033[0;32m";
$RED = "\033[0;31m";
$YELLOW = "\033[0;33m";
$BLUE = "\033[0;34m";
$NC = "\033[0m";

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

// =============================================================================
// TEST 1: B2B (Bank-to-Bank) Transfer
// =============================================================================
echo "{$BLUE}=== B2B (BANK-TO-BANK) TRANSFER TEST ==={$NC}\n\n";

// Step 1: B2B Lookup
echo "{$YELLOW}Step 1: B2B Lookup{$NC}\n";

$b2bLookupRef = 'IB' . strtoupper(substr(md5(uniqid()), 0, 10));
$b2bLookupPayload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => $clientId,
    'clientRef' => $b2bLookupRef,
    'identifierType' => 'BANK',
    'identifier' => '12345678901',
    'destinationFsp' => 'CRDBTZTZ',  // External bank
    'debitAccount' => '06012040022',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'amount' => '10000',
    'debitAccountCategory' => 'BUSINESS'
];

echo "B2B Lookup Payload:\n";
echo "  From: NBC Account (06012040022)\n";
echo "  To: External Bank Account (12345678901)\n";
echo "  Bank: CRDBTZTZ\n";
echo "  Amount: 10,000 TZS\n\n";

$b2bLookupSignature = generateSignature($b2bLookupPayload);
$b2bLookupHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'TIPS_LOOKUP',
    'Signature' => $b2bLookupSignature,
    'Timestamp' => date('c')
];

try {
    $b2bLookupResponse = Http::withHeaders($b2bLookupHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/lookup', $b2bLookupPayload);
    
    $b2bLookupData = $b2bLookupResponse->json();
    echo "B2B Lookup Response: " . $b2bLookupResponse->status() . "\n";
    
    if (isset($b2bLookupData['engineRef'])) {
        $b2bEngineRef = $b2bLookupData['engineRef'];
        // Convert engineRef to alphanumeric only
        $b2bAlphanumericRef = preg_replace('/[^A-Za-z0-9]/', '', $b2bEngineRef);
        echo "Engine Ref: $b2bEngineRef\n";
        echo "Alphanumeric Ref: $b2bAlphanumericRef\n";
    } else {
        $b2bAlphanumericRef = $b2bLookupRef;
    }
    
} catch (Exception $e) {
    echo "B2B Lookup Error: " . $e->getMessage() . "\n";
    $b2bAlphanumericRef = $b2bLookupRef;
}

// Step 2: B2B Transfer
echo "\n{$YELLOW}Step 2: B2B Transfer{$NC}\n";

$b2bTransferRef = 'IB' . strtoupper(substr(md5(uniqid()), 0, 10));
$b2bTransferPayload = [
    'serviceName' => 'P2P',  // or whatever the correct service name is
    'clientId' => $clientId,
    'clientRef' => $b2bTransferRef,
    'lookupRef' => $b2bAlphanumericRef,
    
    // Payer Details (Bank Account)
    'payerDetails' => [
        'identifier' => '06012040022',
        'identifierType' => 'BANK',
        'accountType' => 'BANK',  // BANK for B2B
        'accountCategory' => 'BUSINESS',
        'fullName' => 'SACCOS NBC Account',
        'fspId' => 'NBC',
        'initiatorId' => 'SACCOS_USER'
    ],
    
    // Payee Details (Bank Account)
    'payeeDetails' => [
        'identifier' => '12345678901',
        'identifierType' => 'BANK',
        'accountType' => 'BANK',  // BANK for B2B
        'accountCategory' => 'PERSON',
        'fullName' => 'External Bank Customer',
        'fspId' => 'CRDB',
        'destinationFsp' => 'CRDBTZTZ'
    ],
    
    // Transaction Details
    'transactionDetails' => [
        'debitAmount' => '10000',
        'creditAmount' => '10000',
        'debitCurrency' => 'TZS',
        'creditCurrency' => 'TZS',
        'narration' => 'B2B Transfer Test',
        'transactionType' => 'TRANSFER',
        'chargeBearer' => 'OUR'
    ],
    
    'timestamp' => date('c')
];

echo "B2B Transfer Structure:\n";
echo "  Payer Account Type: {$GREEN}BANK{$NC}\n";
echo "  Payee Account Type: {$GREEN}BANK{$NC}\n";
echo "  Transfer Type: Bank-to-Bank\n\n";

$b2bTransferSignature = generateSignature($b2bTransferPayload);
$b2bTransferHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'P2P',
    'Signature' => $b2bTransferSignature,
    'Timestamp' => date('c')
];

try {
    $b2bTransferResponse = Http::withHeaders($b2bTransferHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/outgoing-transfers', $b2bTransferPayload);
    
    $b2bTransferData = $b2bTransferResponse->json();
    echo "B2B Transfer Response: " . $b2bTransferResponse->status() . "\n";
    
    if ($b2bTransferResponse->status() == 200) {
        echo "{$GREEN}✓ B2B Transfer Successful{$NC}\n";
    } else {
        echo "{$YELLOW}B2B Transfer Status: " . ($b2bTransferData['message'] ?? 'Unknown') . "{$NC}\n";
    }
    
} catch (Exception $e) {
    echo "B2B Transfer Error: " . $e->getMessage() . "\n";
}

// =============================================================================
// TEST 2: B2W (Bank-to-Wallet) Transfer
// =============================================================================
echo "\n{$BLUE}=== B2W (BANK-TO-WALLET) TRANSFER TEST ==={$NC}\n\n";

// Step 1: B2W Lookup
echo "{$YELLOW}Step 1: B2W Lookup{$NC}\n";

$b2wLookupRef = 'IB' . strtoupper(substr(md5(uniqid()), 0, 10));
$b2wLookupPayload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => $clientId,
    'clientRef' => $b2wLookupRef,
    'identifierType' => 'MSISDN',  // Mobile number for wallet
    'identifier' => '255715000000',
    'destinationFsp' => 'VMCASHIN',  // M-Pesa
    'debitAccount' => '06012040022',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'amount' => '5000',
    'debitAccountCategory' => 'BUSINESS'
];

echo "B2W Lookup Payload:\n";
echo "  From: NBC Account (06012040022)\n";
echo "  To: M-Pesa Wallet (255715000000)\n";
echo "  Provider: VMCASHIN (M-Pesa)\n";
echo "  Amount: 5,000 TZS\n\n";

$b2wLookupSignature = generateSignature($b2wLookupPayload);
$b2wLookupHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'TIPS_LOOKUP',
    'Signature' => $b2wLookupSignature,
    'Timestamp' => date('c')
];

try {
    $b2wLookupResponse = Http::withHeaders($b2wLookupHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/lookup', $b2wLookupPayload);
    
    $b2wLookupData = $b2wLookupResponse->json();
    echo "B2W Lookup Response: " . $b2wLookupResponse->status() . "\n";
    
    if (isset($b2wLookupData['engineRef'])) {
        $b2wEngineRef = $b2wLookupData['engineRef'];
        // Convert engineRef to alphanumeric only
        $b2wAlphanumericRef = preg_replace('/[^A-Za-z0-9]/', '', $b2wEngineRef);
        echo "Engine Ref: $b2wEngineRef\n";
        echo "Alphanumeric Ref: $b2wAlphanumericRef\n";
    } else {
        $b2wAlphanumericRef = $b2wLookupRef;
    }
    
} catch (Exception $e) {
    echo "B2W Lookup Error: " . $e->getMessage() . "\n";
    $b2wAlphanumericRef = $b2wLookupRef;
}

// Step 2: B2W Transfer
echo "\n{$YELLOW}Step 2: B2W Transfer{$NC}\n";

$b2wTransferRef = 'IB' . strtoupper(substr(md5(uniqid()), 0, 10));
$b2wTransferPayload = [
    'serviceName' => 'P2W',  // or whatever the correct service name is
    'clientId' => $clientId,
    'clientRef' => $b2wTransferRef,
    'lookupRef' => $b2wAlphanumericRef,
    
    // Payer Details (Bank Account)
    'payerDetails' => [
        'identifier' => '06012040022',
        'identifierType' => 'BANK',
        'accountType' => 'BANK',  // BANK for payer
        'accountCategory' => 'BUSINESS',
        'fullName' => 'SACCOS NBC Account',
        'fspId' => 'NBC',
        'initiatorId' => 'SACCOS_USER'
    ],
    
    // Payee Details (Wallet)
    'payeeDetails' => [
        'identifier' => '255715000000',
        'identifierType' => 'MSISDN',
        'accountType' => 'WALLET',  // WALLET for B2W
        'accountCategory' => 'PERSON',
        'fullName' => 'Mobile Wallet User',
        'fspId' => 'VODACOM',
        'destinationFsp' => 'VMCASHIN'
    ],
    
    // Transaction Details
    'transactionDetails' => [
        'debitAmount' => '5000',
        'creditAmount' => '5000',
        'debitCurrency' => 'TZS',
        'creditCurrency' => 'TZS',
        'narration' => 'B2W Transfer Test',
        'transactionType' => 'TRANSFER',
        'chargeBearer' => 'OUR'
    ],
    
    'timestamp' => date('c')
];

echo "B2W Transfer Structure:\n";
echo "  Payer Account Type: {$GREEN}BANK{$NC}\n";
echo "  Payee Account Type: {$GREEN}WALLET{$NC}\n";
echo "  Transfer Type: Bank-to-Wallet\n\n";

$b2wTransferSignature = generateSignature($b2wTransferPayload);
$b2wTransferHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'P2W',
    'Signature' => $b2wTransferSignature,
    'Timestamp' => date('c')
];

try {
    $b2wTransferResponse = Http::withHeaders($b2wTransferHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/outgoing-transfers', $b2wTransferPayload);
    
    $b2wTransferData = $b2wTransferResponse->json();
    echo "B2W Transfer Response: " . $b2wTransferResponse->status() . "\n";
    
    if ($b2wTransferResponse->status() == 200) {
        echo "{$GREEN}✓ B2W Transfer Successful{$NC}\n";
    } else {
        echo "{$YELLOW}B2W Transfer Status: " . ($b2wTransferData['message'] ?? 'Unknown') . "{$NC}\n";
    }
    
} catch (Exception $e) {
    echo "B2W Transfer Error: " . $e->getMessage() . "\n";
}

// =============================================================================
// SUMMARY
// =============================================================================
echo "\n=====================================\n";
echo "SUMMARY: B2B vs B2W Configuration\n";
echo "=====================================\n\n";

echo "{$BLUE}B2B (Bank-to-Bank) Transfer:{$NC}\n";
echo "  • identifierType: BANK (both payer and payee)\n";
echo "  • accountType: BANK (both payer and payee)\n";
echo "  • destinationFsp: Bank code (e.g., CRDBTZTZ)\n";
echo "  • identifier: Account number\n";
echo "  • Use case: P2P_B2B\n\n";

echo "{$BLUE}B2W (Bank-to-Wallet) Transfer:{$NC}\n";
echo "  • Payer identifierType: BANK\n";
echo "  • Payer accountType: BANK\n";
echo "  • Payee identifierType: MSISDN\n";
echo "  • Payee accountType: WALLET\n";
echo "  • destinationFsp: Wallet provider code (e.g., VMCASHIN)\n";
echo "  • identifier: Phone number for wallet\n";
echo "  • Use case: P2P_B2W\n\n";

echo "{$GREEN}Key Differences:{$NC}\n";
echo "1. accountType field determines transfer type\n";
echo "2. identifierType matches the account type\n";
echo "3. destinationFsp changes based on target (bank vs wallet)\n";
echo "4. Same endpoint handles both transfer types\n";
echo "5. Two-step process: Lookup → Transfer\n\n";

echo "{$YELLOW}Provider Codes:{$NC}\n";
echo "Banks:\n";
echo "  • CRDBTZTZ - CRDB Bank\n";
echo "  • NMIBTZTZ - NMB Bank\n";
echo "  • CORUTZTZ - NBC Bank\n";
echo "\nWallets:\n";
echo "  • VMCASHIN - M-Pesa (Vodacom)\n";
echo "  • TPCASHIN - TigoPesa\n";
echo "  • AIRTELMONEYCASHIN - Airtel Money\n";
echo "  • HALOPESACASHIN - HaloPesa\n";
echo "  • EZYPESACASHIN - EzyPesa\n";

echo "\n=====================================\n";