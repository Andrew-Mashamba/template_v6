<?php

require_once 'vendor/autoload.php';

echo "\n=====================================\n";
echo "NBC UAT API DIRECT TEST\n";
echo "=====================================\n\n";

$baseUrl = 'https://nbc-gateway-uat.intra.nbc.co.tz';
$username = 'SaccosApp@nbc.co.tz';
$password = 'SaccosAbc@123!';
$channelId = 'SACCOSNBC';

$timestamp = date('Y-m-d\TH:i:s.v');
$channelRef = 'TEST' . time();

$payload = [
    'channelId' => $channelId,
    'spCode' => 'bpesp1004002',
    'requestType' => 'inquiry',
    'timestamp' => $timestamp,
    'userId' => 'TEST_USER',
    'branchCode' => '015',
    'channelRef' => $channelRef,
    'billRef' => '45467898',
    'extraFields' => new stdClass(),
];

echo "Request Details:\n";
echo "URL: $baseUrl/api/nbc-sg/v2/billquery\n";
echo "Channel ID: $channelId\n";
echo "SP Code: bpesp1004002\n";
echo "Bill Ref: 45467898\n\n";

echo "Payload:\n";
echo json_encode($payload, JSON_PRETTY_PRINT) . "\n\n";

// Generate signature (simplified for testing)
$jsonPayload = json_encode($payload);
$privateKeyContent = file_exists('storage/app/keys/private_key.pem') 
    ? file_get_contents('storage/app/keys/private_key.pem')
    : null;

$digitalSignature = '';
if ($privateKeyContent) {
    $privateKey = openssl_pkey_get_private($privateKeyContent);
    if ($privateKey) {
        openssl_sign($jsonPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $digitalSignature = base64_encode($signature);
        echo "Digital Signature: Generated\n";
    } else {
        echo "Digital Signature: Failed to load private key\n";
    }
} else {
    echo "Digital Signature: No private key found\n";
}

$basicAuth = base64_encode("$username:$password");

$headers = [
    'Accept: application/json',
    'Content-Type: application/json',
    'NBC-Authorization: Basic ' . $basicAuth,
    'Digital-Signature: ' . $digitalSignature,
    'Timestamp: ' . $timestamp,
];

echo "\nHeaders:\n";
foreach ($headers as $header) {
    $parts = explode(': ', $header, 2);
    if ($parts[0] === 'NBC-Authorization') {
        echo "NBC-Authorization: Basic [credentials]\n";
    } elseif ($parts[0] === 'Digital-Signature') {
        echo "Digital-Signature: [signature]\n";
    } else {
        echo "$header\n";
    }
}

echo "\n=====================================\n";
echo "Sending Request with cURL...\n";
echo "=====================================\n\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/nbc-sg/v2/billquery");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);

curl_close($ch);

echo "HTTP Response Code: $httpCode\n\n";

if ($error) {
    echo "cURL Error: $error\n\n";
}

if ($response) {
    echo "Response Body:\n";
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
    } else {
        echo $response . "\n";
    }
} else {
    echo "No response body received\n";
}

if ($httpCode === 415) {
    echo "\n⚠️ Error 415: Unsupported Media Type\n";
    echo "The server is rejecting the Content-Type header.\n";
    echo "This might indicate:\n";
    echo "1. Wrong API endpoint\n";
    echo "2. API expects different content type\n";
    echo "3. API version mismatch\n";
}

echo "\n=====================================\n";
echo "Verbose cURL Log:\n";
echo "=====================================\n";
echo $verboseLog;

echo "\n=====================================\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "=====================================\n\n";