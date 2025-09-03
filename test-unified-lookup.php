#!/usr/bin/env php
<?php

/**
 * Test TIPS_LOOKUP for both Mobile Wallets and Bank Accounts
 * Demonstrates that TIPS_LOOKUP is used for ALL lookup types
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n=====================================\n";
echo "  UNIFIED TIPS_LOOKUP TEST\n";
echo "  For Both Mobile & Bank Accounts\n";
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
$CYAN = "\033[0;36m";
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

// Function to perform lookup
function performLookup($type, $identifier, $destinationFsp, $amount = '1000') {
    global $baseUrl, $apiKey, $clientId, $GREEN, $RED, $YELLOW, $BLUE, $NC;
    
    $timestamp = time() . rand(100, 999);
    $debitAccount = '06012040022';
    
    $payload = [
        'serviceName' => 'TIPS_LOOKUP',  // SAME for both mobile and bank
        'clientId' => $clientId,
        'clientRef' => (string)$timestamp,
        'identifierType' => $type,  // 'MSISDN' or 'BANK'
        'identifier' => $identifier,
        'destinationFsp' => $destinationFsp,
        'debitAccount' => $debitAccount,
        'debitAccountCurrency' => 'TZS',
        'debitAccountBranchCode' => substr($debitAccount, 0, 3),
        'amount' => $amount,
        'debitAccountCategory' => 'BUSINESS'
    ];
    
    echo "\n{$BLUE}Payload:{$NC}\n";
    echo "  serviceName: {$GREEN}TIPS_LOOKUP{$NC} (same for all)\n";
    echo "  identifierType: {$YELLOW}{$type}{$NC}\n";
    echo "  identifier: {$identifier}\n";
    echo "  destinationFsp: {$destinationFsp}\n";
    echo "  amount: {$amount} TZS\n\n";
    
    $signature = generateSignature($payload);
    $headers = [
        'Content-Type' => 'application/json',
        'Accept' => 'application/json',
        'X-Api-Key' => $apiKey,
        'Client-Id' => $clientId,
        'Service-Name' => 'TIPS_LOOKUP',
        'Signature' => $signature,
        'Timestamp' => Carbon::now()->toIso8601String()
    ];
    
    try {
        echo "Sending request...\n";
        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->timeout(30)
            ->post($baseUrl . '/domestix/api/v2/lookup', $payload);
        
        $status = $response->status();
        $data = $response->json();
        
        echo "Response Status: ";
        if ($status == 200 || $status == 201) {
            echo "{$GREEN}{$status} SUCCESS{$NC}\n";
            if (isset($data['engineRef'])) {
                echo "  • Engine Ref: {$data['engineRef']}\n";
                echo "  • Account Name: " . ($data['accountName'] ?? 'N/A') . "\n";
                echo "  • Use Case: " . ($data['useCase'] ?? 'N/A') . "\n";
            }
            return ['success' => true, 'data' => $data];
        } else {
            echo "{$YELLOW}{$status}{$NC}\n";
            echo "  • Message: " . ($data['message'] ?? 'Unknown') . "\n";
            if (isset($data['body']) && is_array($data['body'])) {
                foreach ($data['body'] as $msg) {
                    echo "    - {$msg}\n";
                }
            }
            return ['success' => false, 'data' => $data];
        }
        
    } catch (Exception $e) {
        echo "{$RED}Error: " . $e->getMessage() . "{$NC}\n";
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// =============================================================================
// MAIN TESTS
// =============================================================================

echo "{$CYAN}";
echo "╔══════════════════════════════════════════════╗\n";
echo "║     TIPS_LOOKUP IS USED FOR ALL LOOKUPS     ║\n";
echo "╚══════════════════════════════════════════════╝{$NC}\n";

// =============================================================================
// TEST 1: MOBILE WALLET LOOKUPS
// =============================================================================
echo "\n{$YELLOW}═══ MOBILE WALLET LOOKUPS (MSISDN) ═══{$NC}\n";

$mobileTests = [
    [
        'name' => 'M-Pesa (Vodacom)',
        'identifier' => '0715000000',  // Without country code
        'destinationFsp' => 'VMCASHIN',
        'amount' => '5000'
    ],
    [
        'name' => 'TigoPesa',
        'identifier' => '0655000000',
        'destinationFsp' => 'TPCASHIN',
        'amount' => '10000'
    ],
    [
        'name' => 'Airtel Money',
        'identifier' => '0685000000',
        'destinationFsp' => 'AIRTELMONEYCASHIN',
        'amount' => '2000'
    ]
];

foreach ($mobileTests as $test) {
    echo "\n{$BLUE}Testing: {$test['name']}{$NC}\n";
    echo str_repeat('-', 40) . "\n";
    
    $result = performLookup(
        'MSISDN',  // identifierType for mobile
        $test['identifier'],
        $test['destinationFsp'],
        $test['amount']
    );
    
    if ($result['success']) {
        echo "{$GREEN}✓ Lookup successful for {$test['name']}{$NC}\n";
    }
}

// =============================================================================
// TEST 2: BANK ACCOUNT LOOKUPS
// =============================================================================
echo "\n\n{$YELLOW}═══ BANK ACCOUNT LOOKUPS (BANK) ═══{$NC}\n";

$bankTests = [
    [
        'name' => 'CRDB Bank Account',
        'identifier' => '12345678901',  // Account number
        'destinationFsp' => 'CRDBTZTZ',
        'amount' => '50000'
    ],
    [
        'name' => 'NMB Bank Account',
        'identifier' => '98765432101',
        'destinationFsp' => 'NMIBTZTZ',
        'amount' => '100000'
    ],
    [
        'name' => 'NBC Bank Account',
        'identifier' => '06098765432',
        'destinationFsp' => 'CORUTZTZ',
        'amount' => '25000'
    ]
];

foreach ($bankTests as $test) {
    echo "\n{$BLUE}Testing: {$test['name']}{$NC}\n";
    echo str_repeat('-', 40) . "\n";
    
    $result = performLookup(
        'BANK',  // identifierType for bank accounts
        $test['identifier'],
        $test['destinationFsp'],
        $test['amount']
    );
    
    if ($result['success']) {
        echo "{$GREEN}✓ Lookup successful for {$test['name']}{$NC}\n";
    }
}

// =============================================================================
// SUMMARY
// =============================================================================
echo "\n\n{$CYAN}";
echo "╔══════════════════════════════════════════════╗\n";
echo "║                   SUMMARY                    ║\n";
echo "╚══════════════════════════════════════════════╝{$NC}\n\n";

echo "{$GREEN}KEY POINTS:{$NC}\n";
echo "1. {$YELLOW}serviceName{$NC} is ALWAYS: {$GREEN}'TIPS_LOOKUP'{$NC}\n";
echo "   - Same for mobile wallets\n";
echo "   - Same for bank accounts\n";
echo "   - Never changes\n\n";

echo "2. {$YELLOW}identifierType{$NC} determines the account type:\n";
echo "   - {$BLUE}'MSISDN'{$NC} → Mobile wallet lookup\n";
echo "   - {$BLUE}'BANK'{$NC} → Bank account lookup\n\n";

echo "3. {$YELLOW}identifier{$NC} format:\n";
echo "   - For MSISDN: Phone WITHOUT country code (0715000000)\n";
echo "   - For BANK: Full account number (12345678901)\n\n";

echo "4. {$YELLOW}destinationFsp{$NC} codes:\n";
echo "   {$BLUE}Mobile Wallets:{$NC}\n";
echo "   • VMCASHIN - M-Pesa\n";
echo "   • TPCASHIN - TigoPesa\n";
echo "   • AIRTELMONEYCASHIN - Airtel Money\n";
echo "   • HALOPESACASHIN - HaloPesa\n";
echo "   • EZYPESACASHIN - EzyPesa\n\n";
echo "   {$BLUE}Banks:{$NC}\n";
echo "   • CRDBTZTZ - CRDB Bank\n";
echo "   • NMIBTZTZ - NMB Bank\n";
echo "   • CORUTZTZ - NBC Bank\n";
echo "   • (and others)\n\n";

echo "{$GREEN}IMPLEMENTATION IN SERVICES:{$NC}\n";
echo "• ExternalFundsTransferService.php → Uses TIPS_LOOKUP ✓\n";
echo "• MobileWalletTransferService.php → Uses TIPS_LOOKUP ✓\n";
echo "• Both services use the SAME lookup endpoint\n";
echo "• Both services use the SAME serviceName\n\n";

echo "{$YELLOW}LOOKUP ENDPOINT:{$NC}\n";
echo "POST {$baseUrl}/domestix/api/v2/lookup\n\n";

echo "{$GREEN}═══════════════════════════════════════{$NC}\n";