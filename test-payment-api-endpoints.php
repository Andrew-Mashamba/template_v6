#!/usr/bin/env php
<?php

/**
 * Direct API Endpoint Testing with Full Request/Response Logging
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

echo "\n=====================================\n";
echo "  API ENDPOINT TESTING WITH LOGGING\n";
echo "=====================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

// Configuration
$baseUrl = config('services.nbc_payments.base_url', 'https://uat-domesticapigateway.nbc.co.tz');
$apiKey = config('services.nbc_payments.api_key', 'YOUR_API_KEY');
$clientId = config('services.nbc_payments.client_id', 'SACCOS_CLIENT');

echo "Configuration:\n";
echo "- Base URL: $baseUrl\n";
echo "- Client ID: $clientId\n";
echo "- API Key: " . substr($apiKey, 0, 10) . "...\n\n";

// Create log directory
$logDir = storage_path('logs/payments');
if (!is_dir($logDir)) {
    mkdir($logDir, 0755, true);
}

// Function to make HTTP request with full logging
function makeRequest($method, $url, $headers, $payload = null) {
    echo "\n========================================\n";
    echo "HTTP REQUEST\n";
    echo "========================================\n";
    echo "Method: $method\n";
    echo "URL: $url\n";
    echo "\nHeaders:\n";
    foreach ($headers as $key => $value) {
        // Mask sensitive headers
        if (in_array(strtolower($key), ['x-api-key', 'authorization', 'signature'])) {
            echo "  $key: " . substr($value, 0, 10) . "...[MASKED]\n";
        } else {
            echo "  $key: $value\n";
        }
    }
    
    if ($payload) {
        echo "\nRequest Body:\n";
        if (is_array($payload)) {
            echo json_encode($payload, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo $payload . "\n";
        }
    }
    
    // Log to file
    Log::channel('payments')->info('API Request', [
        'method' => $method,
        'url' => $url,
        'headers' => array_keys($headers),
        'has_payload' => !empty($payload)
    ]);
    
    try {
        $startTime = microtime(true);
        
        if ($method === 'GET') {
            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => false])
                ->timeout(30)
                ->get($url);
        } else {
            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => false])
                ->timeout(30)
                ->send($method, $url, ['json' => $payload]);
        }
        
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        echo "\n========================================\n";
        echo "HTTP RESPONSE\n";
        echo "========================================\n";
        echo "Status Code: " . $response->status() . "\n";
        echo "Response Time: {$duration}ms\n";
        
        $responseHeaders = $response->headers();
        if (!empty($responseHeaders)) {
            echo "\nResponse Headers:\n";
            foreach ($responseHeaders as $key => $values) {
                echo "  $key: " . (is_array($values) ? implode(', ', $values) : $values) . "\n";
            }
        }
        
        echo "\nResponse Body:\n";
        $body = $response->body();
        if (json_decode($body)) {
            echo json_encode(json_decode($body), JSON_PRETTY_PRINT) . "\n";
        } else {
            echo substr($body, 0, 1000) . (strlen($body) > 1000 ? '...[truncated]' : '') . "\n";
        }
        
        // Log response
        Log::channel('payments')->info('API Response', [
            'status' => $response->status(),
            'duration_ms' => $duration,
            'has_body' => !empty($body)
        ]);
        
        return [
            'status' => $response->status(),
            'headers' => $responseHeaders,
            'body' => json_decode($body) ?? $body,
            'duration' => $duration
        ];
        
    } catch (Exception $e) {
        echo "\nERROR: " . $e->getMessage() . "\n";
        
        Log::channel('payments')->error('API Request Failed', [
            'url' => $url,
            'error' => $e->getMessage()
        ]);
        
        return [
            'error' => $e->getMessage()
        ];
    }
}

// Generate signature for requests
function generateSignature($payload) {
    $privateKeyPath = storage_path('app/keys/private_key.pem');
    
    if (!file_exists($privateKeyPath)) {
        echo "Warning: Private key not found at $privateKeyPath\n";
        return 'DUMMY_SIGNATURE';
    }
    
    try {
        $privateKeyContent = file_get_contents($privateKeyPath);
        $privateKey = openssl_pkey_get_private($privateKeyContent);
        
        if (!$privateKey) {
            echo "Warning: Failed to load private key\n";
            return 'INVALID_KEY_SIGNATURE';
        }
        
        $jsonPayload = json_encode($payload);
        openssl_sign($jsonPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        
        return base64_encode($signature);
    } catch (Exception $e) {
        echo "Warning: Signature generation failed: " . $e->getMessage() . "\n";
        return 'ERROR_SIGNATURE';
    }
}

// =============================================================================
// TEST 1: INTERNAL FUNDS TRANSFER - ACCOUNT VERIFICATION
// =============================================================================
echo "\n\033[0;33m=== TEST 1: IFT ACCOUNT VERIFICATION ===\033[0m\n";

$iftAccountPayload = [
    'accountNumber' => '06012040022',
    'accountType' => 'CASA',
    'verificationPurpose' => 'IFT'
];

$iftAccountHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'IFT',
    'Timestamp' => date('c')
];

$result1 = makeRequest(
    'POST',
    $baseUrl . '/api/nbc/account/verify',
    $iftAccountHeaders,
    $iftAccountPayload
);

// =============================================================================
// TEST 2: EXTERNAL FUNDS TRANSFER - TIPS LOOKUP
// =============================================================================
echo "\n\033[0;33m=== TEST 2: EFT TIPS LOOKUP ===\033[0m\n";

$tipsLookupPayload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => $clientId,
    'clientRef' => 'EFTLOOKUP_' . date('YmdHis'),
    'identifierType' => 'BANK',
    'identifier' => '12345678901',
    'destinationFsp' => 'NMIBTZTZ',
    'debitAccount' => '06012040022',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'amount' => '1',
    'debitAccountCategory' => 'BUSINESS'
];

$signature = generateSignature($tipsLookupPayload);

$tipsLookupHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'TIPS_LOOKUP',
    'Signature' => $signature,
    'Timestamp' => date('c')
];

$result2 = makeRequest(
    'POST',
    $baseUrl . '/domestix/api/v2/lookup',
    $tipsLookupHeaders,
    $tipsLookupPayload
);

// =============================================================================
// TEST 3: MOBILE WALLET - MPESA LOOKUP
// =============================================================================
echo "\n\033[0;33m=== TEST 3: MOBILE WALLET (M-PESA) LOOKUP ===\033[0m\n";

$walletLookupPayload = [
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => $clientId,
    'clientRef' => 'WALLETLOOKUP_' . date('YmdHis'),
    'identifierType' => 'MSISDN',
    'identifier' => '255715000000',
    'destinationFsp' => 'VMCASHIN',
    'debitAccount' => '06012040022',
    'debitAccountCurrency' => 'TZS',
    'debitAccountBranchCode' => '060',
    'amount' => '1',
    'debitAccountCategory' => 'BUSINESS'
];

$walletSignature = generateSignature($walletLookupPayload);

$walletLookupHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'TIPS_LOOKUP',
    'Signature' => $walletSignature,
    'Timestamp' => date('c')
];

$result3 = makeRequest(
    'POST',
    $baseUrl . '/domestix/api/v2/lookup',
    $walletLookupHeaders,
    $walletLookupPayload
);

// =============================================================================
// TEST 4: GEPG BILL INQUIRY
// =============================================================================
echo "\n\033[0;33m=== TEST 4: GEPG BILL INQUIRY ===\033[0m\n";

$gepgInquiryPayload = '<?xml version="1.0" encoding="UTF-8"?>
<GepgGateway>
    <GepgGatewayBillQryReq>
        <GepgGatewayHdr>
            <ChannelID>' . $clientId . '</ChannelID>
            <ChannelName>SACCOS</ChannelName>
            <Service>GEPG_INQ</Service>
        </GepgGatewayHdr>
        <gepgBillQryReq>
            <ChannelRef>GEPGINQ_' . date('YmdHis') . '</ChannelRef>
            <CustCtrNum>991234567890</CustCtrNum>
            <DebitAccountNo>06012040022</DebitAccountNo>
            <DebitAccountCurrency>TZS</DebitAccountCurrency>
        </gepgBillQryReq>
    </GepgGatewayBillQryReq>
</GepgGateway>';

$gepgHeaders = [
    'Content-Type' => 'application/xml',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'GEPG_INQ'
];

$result4 = makeRequest(
    'POST',
    $baseUrl . '/api/nbc-sg/v2/billquery',
    $gepgHeaders,
    $gepgInquiryPayload
);

// =============================================================================
// TEST 5: LUKU METER LOOKUP
// =============================================================================
echo "\n\033[0;33m=== TEST 5: LUKU METER LOOKUP ===\033[0m\n";

$lukuLookupPayload = [
    'serviceName' => 'LUKU_LOOKUP',
    'clientId' => $clientId,
    'clientRef' => 'LUKULOOKUP_' . date('YmdHis'),
    'meterNumber' => '01234567890123456789',
    'accountNumber' => '06012040022',
    'accountCurrency' => 'TZS'
];

$lukuSignature = generateSignature($lukuLookupPayload);

$lukuHeaders = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Client-Id' => $clientId,
    'Service-Name' => 'LUKU_LOOKUP',
    'Signature' => $lukuSignature,
    'Timestamp' => date('c')
];

$result5 = makeRequest(
    'POST',
    $baseUrl . '/api/nbc-luku/v2/lookup',
    $lukuHeaders,
    $lukuLookupPayload
);

// =============================================================================
// SUMMARY
// =============================================================================
echo "\n========================================\n";
echo "TEST SUMMARY\n";
echo "========================================\n";

$endpoints = [
    'IFT Account Verify' => $result1,
    'EFT TIPS Lookup' => $result2,
    'Wallet M-Pesa Lookup' => $result3,
    'GEPG Bill Inquiry' => $result4,
    'LUKU Meter Lookup' => $result5
];

foreach ($endpoints as $name => $result) {
    if (isset($result['status'])) {
        $status = $result['status'];
        $color = $status == 200 ? "\033[0;32m" : ($status == 400 ? "\033[0;33m" : "\033[0;31m");
        echo "$name: {$color}HTTP $status\033[0m";
        if (isset($result['duration'])) {
            echo " ({$result['duration']}ms)";
        }
        echo "\n";
    } else {
        echo "$name: \033[0;31mFAILED\033[0m";
        if (isset($result['error'])) {
            echo " - " . $result['error'];
        }
        echo "\n";
    }
}

// Write summary report
$reportContent = "# Payment API Endpoint Test Report\n\n";
$reportContent .= "**Date**: " . date('Y-m-d H:i:s') . "\n";
$reportContent .= "**Base URL**: $baseUrl\n\n";
$reportContent .= "## Endpoints Tested\n\n";
$reportContent .= "| Service | Endpoint | Method | Status |\n";
$reportContent .= "|---------|----------|--------|--------|\n";
$reportContent .= "| IFT | /api/nbc/account/verify | POST | " . ($result1['status'] ?? 'ERROR') . " |\n";
$reportContent .= "| EFT TIPS | /domestix/api/v2/lookup | POST | " . ($result2['status'] ?? 'ERROR') . " |\n";
$reportContent .= "| Wallet | /domestix/api/v2/lookup | POST | " . ($result3['status'] ?? 'ERROR') . " |\n";
$reportContent .= "| GEPG | /api/nbc-sg/v2/billquery | POST | " . ($result4['status'] ?? 'ERROR') . " |\n";
$reportContent .= "| LUKU | /api/nbc-luku/v2/lookup | POST | " . ($result5['status'] ?? 'ERROR') . " |\n";

file_put_contents(__DIR__ . '/PAYMENT_API_ENDPOINTS_REPORT.md', $reportContent);

echo "\n========================================\n";
echo "Full request/response details displayed above.\n";
echo "Logs saved to: storage/logs/payments/\n";
echo "Report saved to: PAYMENT_API_ENDPOINTS_REPORT.md\n";
echo "========================================\n";