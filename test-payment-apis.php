<?php

require_once __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Log;
use Dotenv\Dotenv;

// Load environment
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "\n========================================\n";
echo "PAYMENT GATEWAY CONNECTIVITY TEST\n";
echo "========================================\n\n";

$results = [];

// Test 1: NBC Gateway (GEPG/LUKU)
echo "1. Testing NBC Gateway...\n";
$nbcGatewayUrl = $_ENV['NBC_GATEWAY_BASE_URL'] ?? 'https://nbc-gateway-uat.intra.nbc.co.tz';
$nbcToken = $_ENV['NBC_GATEWAY_API_TOKEN'] ?? '';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $nbcGatewayUrl . '/api/nbc-sg/v2/status-check');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $nbcToken,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ NBC Gateway: Connection failed - $error\n";
    $results['nbc_gateway'] = ['status' => 'failed', 'error' => $error];
} else {
    echo "   ✓ NBC Gateway: HTTP $httpCode\n";
    $results['nbc_gateway'] = ['status' => 'success', 'http_code' => $httpCode];
}

// Test 2: Internal Fund Transfer API
echo "\n2. Testing Internal Fund Transfer API...\n";
$internalApiUrl = $_ENV['NBC_INTERNAL_FUND_TRANSFER_BASE_URL'] ?? 'http://cbpuat.intra.nbc.co.tz:6666';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $internalApiUrl . '/api/nbc-sg/internal_ft');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ Internal Transfer API: Connection failed - $error\n";
    $results['internal_transfer'] = ['status' => 'failed', 'error' => $error];
} else {
    echo "   ✓ Internal Transfer API: HTTP $httpCode\n";
    $results['internal_transfer'] = ['status' => 'success', 'http_code' => $httpCode];
}

// Test 3: GEPG Gateway
echo "\n3. Testing GEPG Gateway...\n";
$gepgUrl = $_ENV['GEPG_GATEWAY_URL'] ?? 'https://nbc-gateway-uat.intra.nbc.co.tz';
$gepgAuth = $_ENV['GEPG_AUTH_TOKEN'] ?? '';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $gepgUrl . '/api/v1/gepg/verify');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . $gepgAuth,
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ GEPG Gateway: Connection failed - $error\n";
    $results['gepg'] = ['status' => 'failed', 'error' => $error];
} else {
    echo "   ✓ GEPG Gateway: HTTP $httpCode\n";
    $results['gepg'] = ['status' => 'success', 'http_code' => $httpCode];
}

// Test 4: LUKU Gateway
echo "\n4. Testing LUKU Gateway...\n";
$lukuUrl = $_ENV['LUKU_GATEWAY_BASE_URL'] ?? 'https://nbc-gateway-uat.intra.nbc.co.tz';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $lukuUrl . '/api/v1/luku/lookup');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Basic ' . ($_ENV['LUKU_GATEWAY_API_TOKEN'] ?? $gepgAuth),
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ LUKU Gateway: Connection failed - $error\n";
    $results['luku'] = ['status' => 'failed', 'error' => $error];
} else {
    echo "   ✓ LUKU Gateway: HTTP $httpCode\n";
    $results['luku'] = ['status' => 'success', 'http_code' => $httpCode];
}

// Test 5: Account Details API
echo "\n5. Testing Account Details API...\n";
$accountApiUrl = $_ENV['ACCOUNT_DETAILS_BASE_URL'] ?? 'http://cbpuat.intra.nbc.co.tz:9004';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $accountApiUrl . '/api/v1/account-lookup');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . ($_ENV['ACCOUNT_DETAILS_API_KEY'] ?? ''),
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "   ❌ Account Details API: Connection failed - $error\n";
    $results['account_details'] = ['status' => 'failed', 'error' => $error];
} else {
    echo "   ✓ Account Details API: HTTP $httpCode\n";
    $results['account_details'] = ['status' => 'success', 'http_code' => $httpCode];
}

// Summary
echo "\n========================================\n";
echo "SUMMARY\n";
echo "========================================\n";

$total = count($results);
$successful = count(array_filter($results, fn($r) => $r['status'] === 'success'));
$failed = $total - $successful;

echo "Total APIs tested: $total\n";
echo "Successful: $successful\n";
echo "Failed: $failed\n";

if ($failed > 0) {
    echo "\nFailed APIs:\n";
    foreach ($results as $api => $result) {
        if ($result['status'] === 'failed') {
            echo "  - $api: {$result['error']}\n";
        }
    }
}

echo "\n";