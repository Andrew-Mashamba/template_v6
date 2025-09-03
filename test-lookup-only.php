#!/usr/bin/env php
<?php

/**
 * Test TIPS_LOOKUP with exact structure from documentation
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n=====================================\n";
echo "  TESTING TIPS_LOOKUP STRUCTURE\n";
echo "=====================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$baseUrl = 'https://22.32.245.67:443';
$apiKey = config('services.nbc_payments.api_key');

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
// TEST 1: WALLET LOOKUP (MSISDN)
// =============================================================================
echo "{$BLUE}=== TEST 1: WALLET LOOKUP (M-PESA) ==={$NC}\n\n";

$timestamp = time();
$walletLookupPayload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => 'IB',  // Using IB as in your services
    'clientRef' => (string)$timestamp,
    'identifierType' => 'MSISDN',
    'identifier' => '0715000000',  // Without country code as in example
    'destinationFsp' => 'VMCASHIN',
    'debitAccount' => '06012040022',  // Your SACCOS account
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'amount' => '5000',
    'debitAccountCategory' => 'BUSINESS'
];

echo "Wallet Lookup Payload:\n";
echo json_encode($walletLookupPayload, JSON_PRETTY_PRINT) . "\n\n";

$walletSignature = generateSignature($walletLookupPayload);
$walletHeaders = [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => 'IB',
    'Service-Name' => 'TIPS_LOOKUP',
    'Signature' => $walletSignature,
    'Timestamp' => Carbon::now()->toIso8601String()
];

try {
    echo "Sending wallet lookup request...\n";
    $walletResponse = Http::withHeaders($walletHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/lookup', $walletLookupPayload);
    
    $walletStatus = $walletResponse->status();
    $walletData = $walletResponse->json();
    
    echo "Response Status: {$walletStatus}\n";
    
    if ($walletStatus == 200 || $walletStatus == 201) {
        echo "{$GREEN}✅ Wallet Lookup Successful!{$NC}\n";
        if (isset($walletData['engineRef'])) {
            echo "Engine Ref: {$walletData['engineRef']}\n";
            echo "Account Name: " . ($walletData['accountName'] ?? 'N/A') . "\n";
        }
    } else {
        echo "{$YELLOW}⚠ Response:{$NC}\n";
        echo json_encode($walletData, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "{$RED}Error: " . $e->getMessage() . "{$NC}\n";
}

// =============================================================================
// TEST 2: BANK ACCOUNT LOOKUP
// =============================================================================
echo "\n{$BLUE}=== TEST 2: BANK ACCOUNT LOOKUP ==={$NC}\n\n";

$bankTimestamp = time() + 1;
$bankLookupPayload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => 'IB',
    'clientRef' => (string)$bankTimestamp,
    'identifierType' => 'BANK',  // Changed to BANK for bank accounts
    'identifier' => '12345678901',  // Bank account number
    'destinationFsp' => 'CRDBTZTZ',  // Bank code
    'debitAccount' => '06012040022',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'amount' => '10000',
    'debitAccountCategory' => 'BUSINESS'
];

echo "Bank Lookup Payload:\n";
echo json_encode($bankLookupPayload, JSON_PRETTY_PRINT) . "\n\n";

$bankSignature = generateSignature($bankLookupPayload);
$bankHeaders = [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => 'IB',
    'Service-Name' => 'TIPS_LOOKUP',
    'Signature' => $bankSignature,
    'Timestamp' => Carbon::now()->toIso8601String()
];

try {
    echo "Sending bank lookup request...\n";
    $bankResponse = Http::withHeaders($bankHeaders)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl . '/domestix/api/v2/lookup', $bankLookupPayload);
    
    $bankStatus = $bankResponse->status();
    $bankData = $bankResponse->json();
    
    echo "Response Status: {$bankStatus}\n";
    
    if ($bankStatus == 200 || $bankStatus == 201) {
        echo "{$GREEN}✅ Bank Lookup Successful!{$NC}\n";
        if (isset($bankData['engineRef'])) {
            echo "Engine Ref: {$bankData['engineRef']}\n";
            echo "Account Name: " . ($bankData['accountName'] ?? 'N/A') . "\n";
        }
    } else {
        echo "{$YELLOW}⚠ Response:{$NC}\n";
        echo json_encode($bankData, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (Exception $e) {
    echo "{$RED}Error: " . $e->getMessage() . "{$NC}\n";
}

// =============================================================================
// TEST 3: OTHER WALLET PROVIDERS
// =============================================================================
echo "\n{$BLUE}=== TEST 3: OTHER WALLET PROVIDERS ==={$NC}\n\n";

$providers = [
    'TIGO' => ['code' => 'TPCASHIN', 'number' => '0655000000'],
    'AIRTEL' => ['code' => 'AIRTELMONEYCASHIN', 'number' => '0685000000'],
    'HALO' => ['code' => 'HALOPESACASHIN', 'number' => '0625000000']
];

foreach ($providers as $name => $provider) {
    echo "\n{$YELLOW}Testing {$name}:{$NC}\n";
    
    $providerTimestamp = time() + rand(10, 99);
    $providerPayload = [
        'serviceName' => 'TIPS_LOOKUP',
        'clientId' => 'IB',
        'clientRef' => (string)$providerTimestamp,
        'identifierType' => 'MSISDN',
        'identifier' => $provider['number'],
        'destinationFsp' => $provider['code'],
        'debitAccount' => '06012040022',
        'debitAccountCurrency' => 'TZS',
        'debitAccountBranchCode' => '060',
        'amount' => '1000',
        'debitAccountCategory' => 'BUSINESS'
    ];
    
    echo "  • Phone: {$provider['number']}\n";
    echo "  • FSP Code: {$provider['code']}\n";
    
    $providerSignature = generateSignature($providerPayload);
    $providerHeaders = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'X-Api-Key' => $apiKey,
        'Client-Id' => 'IB',
        'Service-Name' => 'TIPS_LOOKUP',
        'Signature' => $providerSignature,
        'Timestamp' => Carbon::now()->toIso8601String()
    ];
    
    try {
        $providerResponse = Http::withHeaders($providerHeaders)
            ->withOptions(['verify' => false])
            ->timeout(30)
            ->post($baseUrl . '/domestix/api/v2/lookup', $providerPayload);
        
        $status = $providerResponse->status();
        echo "  • Status: {$status}\n";
        
        if ($status == 200) {
            echo "  {$GREEN}✓ Lookup successful{$NC}\n";
        } else {
            $data = $providerResponse->json();
            echo "  • Message: " . ($data['message'] ?? 'Unknown') . "\n";
        }
        
    } catch (Exception $e) {
        echo "  {$RED}• Error: " . $e->getMessage() . "{$NC}\n";
    }
}

// =============================================================================
// SUMMARY
// =============================================================================
echo "\n=====================================\n";
echo "LOOKUP STRUCTURE REQUIREMENTS\n";
echo "=====================================\n\n";

echo "{$GREEN}Required Fields for TIPS_LOOKUP:{$NC}\n";
echo "✅ serviceName: 'TIPS_LOOKUP'\n";
echo "✅ clientId: Your client ID (e.g., 'IB')\n";
echo "✅ clientRef: Unique reference (timestamp works)\n";
echo "✅ identifierType: 'MSISDN' for wallets, 'BANK' for accounts\n";
echo "✅ identifier: Phone number (without country code) or account number\n";
echo "✅ destinationFsp: Provider code (VMCASHIN, TPCASHIN, etc.)\n";
echo "✅ debitAccount: Source account number\n";
echo "✅ debitAccountCurrency: 'TZS'\n";
echo "✅ debitAccountBranchCode: Branch code (first 3 digits of account)\n";
echo "✅ amount: Amount to transfer (string)\n";
echo "✅ debitAccountCategory: 'BUSINESS' or 'PERSON'\n\n";

echo "{$BLUE}Wallet Provider Codes:{$NC}\n";
echo "• VMCASHIN - M-Pesa (Vodacom)\n";
echo "• TPCASHIN - TigoPesa\n";
echo "• AIRTELMONEYCASHIN - Airtel Money\n";
echo "• HALOPESACASHIN - HaloPesa\n";
echo "• EZYPESACASHIN - EzyPesa\n\n";

echo "{$BLUE}Phone Number Format:{$NC}\n";
echo "• For lookup: Use without country code (e.g., '0715000000')\n";
echo "• The API expects local format for MSISDN lookups\n";

echo "\n=====================================\n";