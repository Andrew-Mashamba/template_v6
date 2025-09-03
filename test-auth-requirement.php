#!/usr/bin/env php
<?php

require_once __DIR__ . '/vendor/autoload.php';

echo "\n=====================================\n";
echo "  NBC API AUTH REQUIREMENT TEST\n";
echo "=====================================\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n\n";

$baseUrl = 'https://22.32.245.67:443';
$apiKey = config('services.nbc_payments.api_key');

// Test 1: Try with just API key
echo "Test 1: Request with API Key only\n";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $baseUrl . '/domestix/api/v2/lookup');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'X-Api-Key: ' . $apiKey,
    'Client-Id: IB'
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'serviceName' => 'TIPS_LOOKUP',
    'clientId' => 'IB',
    'clientRef' => 'TEST' . time()
]));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "Response Code: $httpCode\n";
$data = json_decode($response, true);
if (isset($data['message'])) {
    echo "Message: " . $data['message'] . "\n";
}
echo "\n";

// Test 2: Check if there's an OAuth endpoint
echo "Test 2: Looking for OAuth/Token endpoint\n";
$possibleEndpoints = [
    '/oauth/token',
    '/auth/token',
    '/api/token',
    '/api/auth/token',
    '/domestix/api/v2/token',
    '/domestix/auth/token'
];

foreach ($possibleEndpoints as $endpoint) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_NOBODY, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode != 0 && $httpCode != 404) {
        echo "  Found: $endpoint (HTTP $httpCode)\n";
    }
}

echo "\nTest completed.\n";