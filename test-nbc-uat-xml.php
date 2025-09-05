<?php

require_once 'vendor/autoload.php';

echo "\n=====================================\n";
echo "NBC UAT API XML TEST\n";
echo "=====================================\n\n";

$baseUrl = 'https://nbc-gateway-uat.intra.nbc.co.tz';
$username = 'SaccosApp@nbc.co.tz';
$password = 'SaccosAbc@123!';
$channelId = 'SACCOSNBC';

$timestamp = date('Y-m-d\TH:i:s.v');
$channelRef = 'TEST' . time();

// Create XML payload
$xml = '<?xml version="1.0" encoding="UTF-8"?>
<request>
    <channelId>' . $channelId . '</channelId>
    <spCode>bpesp1004002</spCode>
    <requestType>inquiry</requestType>
    <timestamp>' . $timestamp . '</timestamp>
    <userId>TEST_USER</userId>
    <branchCode>015</branchCode>
    <channelRef>' . $channelRef . '</channelRef>
    <billRef>45467898</billRef>
    <extraFields></extraFields>
</request>';

echo "Request Details:\n";
echo "URL: $baseUrl/api/nbc-sg/v2/billquery\n";
echo "Channel ID: $channelId\n";
echo "SP Code: bpesp1004002\n";
echo "Bill Ref: 45467898\n\n";

echo "XML Payload:\n";
echo $xml . "\n\n";

// Generate signature for XML
$privateKeyContent = file_exists('storage/app/keys/private_key.pem') 
    ? file_get_contents('storage/app/keys/private_key.pem')
    : null;

$digitalSignature = '';
if ($privateKeyContent) {
    $privateKey = openssl_pkey_get_private($privateKeyContent);
    if ($privateKey) {
        openssl_sign($xml, $signature, $privateKey, OPENSSL_ALGO_SHA256);
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
    'Accept: application/xml',
    'Content-Type: application/xml',
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
curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Response Code: $httpCode\n\n";

if ($error) {
    echo "cURL Error: $error\n\n";
}

if ($response) {
    echo "Response Body:\n";
    // Try to parse as XML
    $xml = simplexml_load_string($response);
    if ($xml !== false) {
        echo "XML Response:\n";
        echo print_r($xml, true);
    } else {
        // Try JSON
        $decoded = json_decode($response, true);
        if ($decoded) {
            echo json_encode($decoded, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo $response . "\n";
        }
    }
} else {
    echo "No response body received\n";
}

if ($httpCode === 200) {
    echo "\n✅ Request successful!\n";
} elseif ($httpCode === 415) {
    echo "\n⚠️ Error 415: Still unsupported media type\n";
} else {
    echo "\n❌ Request failed with code: $httpCode\n";
}

echo "\n=====================================\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "=====================================\n\n";