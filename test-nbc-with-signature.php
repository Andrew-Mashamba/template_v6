#!/usr/bin/env php
<?php

/**
 * NBC Internal Funds Transfer Test with Digital Signature
 * Tests the complete authentication flow including signature
 */

echo "============================================\n";
echo "NBC IFT Test with Digital Signature\n";
echo "============================================\n\n";

// Configuration
$apiUrl = 'http://cbpuat.intra.nbc.co.tz:6666/api/nbc-sg/internal_ft';
$apiKey = 'b1f6c3a92e4d9a7c34f981cf22b54e716e5e8d2aab57ff449c6a1347088c3f55';
$username = 'saccosnbc';
$password = '@NBCsaccosisaleLtd';
$privateKeyPath = '/var/www/html/template/storage/keys/private.pem';
$channelId = 'SACCOSNBC';

// Generate unique reference
$channelRef = 'CH' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));

// Prepare request payload
$payload = [
    'header' => [
        'service' => 'internal_ft',
        'extra' => [
            'pyrName' => 'Test User'
        ]
    ],
    'channelId' => $channelId,
    'channelRef' => $channelRef,
    'creditAccount' => '011191000036',
    'creditCurrency' => 'TZS',
    'debitAccount' => '011191000035',
    'debitCurrency' => 'TZS',
    'amount' => '1000',
    'narration' => 'Test NBC Internal Transfer - ' . date('Y-m-d H:i:s')
];

$payloadJson = json_encode($payload, JSON_UNESCAPED_SLASHES);

echo "URL: $apiUrl\n";
echo "Channel Reference: $channelRef\n\n";

// Prepare headers
$headers = [
    'Content-Type: application/json',
    'Accept: application/json',
    'x-api-key: ' . $apiKey  // lowercase as per NBC documentation
];

// Add Basic Authentication
$basicAuth = base64_encode($username . ':' . $password);
$headers[] = 'NBC-Authorization: Basic ' . $basicAuth;
echo "NBC-Authorization: Basic " . substr($basicAuth, 0, 10) . "..." . substr($basicAuth, -4) . "\n";

// Generate digital signature if private key exists
$signature = null;
if (file_exists($privateKeyPath)) {
    echo "\nGenerating digital signature...\n";
    
    // Read private key
    $privateKeyContent = file_get_contents($privateKeyPath);
    $privateKey = openssl_pkey_get_private($privateKeyContent);
    
    if ($privateKey) {
        // Generate signature
        $signatureRaw = '';
        if (openssl_sign($payloadJson, $signatureRaw, $privateKey, OPENSSL_ALGO_SHA256)) {
            $signature = base64_encode($signatureRaw);
            $headers[] = 'Signature: ' . $signature;
            echo "✓ Signature generated successfully\n";
            echo "  Algorithm: SHA256\n";
            echo "  Signature: " . substr($signature, 0, 20) . "..." . substr($signature, -10) . "\n";
        } else {
            echo "✗ Failed to generate signature: " . openssl_error_string() . "\n";
        }
    } else {
        echo "✗ Failed to load private key: " . openssl_error_string() . "\n";
    }
} else {
    echo "✗ Private key not found at: $privateKeyPath\n";
}

echo "\nRequest Body:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n";

echo "\n============================================\n";
echo "Sending request...\n";
echo "============================================\n\n";

// Initialize cURL
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payloadJson);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_VERBOSE, false);

// Execute request
$startTime = microtime(true);
$response = curl_exec($ch);
$duration = round((microtime(true) - $startTime) * 1000, 2);

$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $httpCode\n";
echo "Response Time: {$duration}ms\n\n";

if ($curlError) {
    echo "✗ cURL Error: $curlError\n";
} else {
    echo "Response:\n";
    if ($response) {
        $responseData = json_decode($response, true);
        if ($responseData) {
            echo json_encode($responseData, JSON_PRETTY_PRINT) . "\n";
            
            // Check NBC status code
            if (isset($responseData['statusCode'])) {
                echo "\nNBC Status Code: " . $responseData['statusCode'] . "\n";
                if ($responseData['statusCode'] == 600) {
                    echo "✓ Transfer successful!\n";
                } else {
                    $errorMessages = [
                        626 => 'Transaction Failed',
                        625 => 'No Response from CBS',
                        630 => 'Currency account combination does not match',
                        631 => 'Biller not defined',
                        700 => 'General Failure'
                    ];
                    $errorMsg = $errorMessages[$responseData['statusCode']] ?? 'Unknown error';
                    echo "✗ NBC Error: $errorMsg\n";
                }
            }
        } else {
            echo $response . "\n";
        }
    } else {
        echo "(Empty response)\n";
    }
}

// Interpret HTTP status
echo "\n============================================\n";
echo "Result Analysis:\n";
echo "============================================\n\n";

switch ($httpCode) {
    case 200:
    case 201:
        echo "✓ HTTP request successful\n";
        break;
    case 401:
        echo "✗ Authentication failed (HTTP 401)\n";
        echo "\nPossible issues:\n";
        echo "  1. Invalid API key: Check x-api-key header\n";
        echo "  2. Invalid credentials: Check username/password\n";
        echo "  3. Missing signature: NBC may require digital signature\n";
        echo "  4. Wrong auth format: Check NBC-Authorization header format\n";
        break;
    case 400:
        echo "✗ Bad request (HTTP 400)\n";
        echo "Check request body format and required fields\n";
        break;
    case 404:
        echo "✗ Endpoint not found (HTTP 404)\n";
        echo "Check API URL: $apiUrl\n";
        break;
    case 500:
    case 502:
    case 503:
        echo "✗ Server error (HTTP $httpCode)\n";
        echo "NBC server encountered an error\n";
        break;
    default:
        echo "✗ Unexpected status (HTTP $httpCode)\n";
}

echo "\n============================================\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";
echo "============================================\n";