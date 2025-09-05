<?php

echo "\n=====================================\n";
echo "NBC UAT SIMPLE TEST - NO SIGNATURE\n";
echo "=====================================\n\n";

$baseUrl = 'https://nbc-gateway-uat.intra.nbc.co.tz';
$username = 'SaccosApp@nbc.co.tz';
$password = 'SaccosAbc@123!';

// Test without signature first
$timestamp = date('Y-m-d\TH:i:s.v');
$channelRef = 'TEST' . time();

$xml = '<?xml version="1.0" encoding="UTF-8"?>
<request>
    <channelId>SACCOSNBC</channelId>
    <spCode>bpesp1004002</spCode>
    <requestType>inquiry</requestType>
    <timestamp>' . $timestamp . '</timestamp>
    <userId>TEST_USER</userId>
    <branchCode>015</branchCode>
    <channelRef>' . $channelRef . '</channelRef>
    <billRef>45467898</billRef>
    <extraFields></extraFields>
</request>';

echo "Testing WITHOUT Digital Signature:\n";
echo "===================================\n\n";

$headers = [
    'Accept: application/xml',
    'Content-Type: application/xml',
    'NBC-Authorization: Basic ' . base64_encode("$username:$password"),
    'Timestamp: ' . $timestamp,
];

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
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($response) {
    $xml = simplexml_load_string($response);
    if ($xml) {
        echo "Response: " . print_r($xml, true) . "\n";
    } else {
        echo "Response: $response\n";
    }
}

echo "\n=====================================\n";
echo "Testing WITH Empty Digital Signature:\n";
echo "=====================================\n\n";

$headers[] = 'Digital-Signature: ';

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
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($response) {
    $xml = simplexml_load_string($response);
    if ($xml) {
        echo "Response: " . print_r($xml, true) . "\n";
    } else {
        echo "Response: $response\n";
    }
}

echo "\n=====================================\n";
echo "Testing bill-pay endpoint:\n";
echo "=====================================\n\n";

// Try the payment endpoint to see if it gives different error
$paymentXml = '<?xml version="1.0" encoding="UTF-8"?>
<request>
    <channelId>SACCOSNBC</channelId>
    <spCode>bpesp1004002</spCode>
    <requestType>payment</requestType>
    <timestamp>' . $timestamp . '</timestamp>
    <userId>TEST_USER</userId>
    <branchCode>015</branchCode>
    <channelRef>PAY' . time() . '</channelRef>
    <billRef>45467898</billRef>
    <amount>10000</amount>
    <extraFields></extraFields>
</request>';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "$baseUrl/api/nbc-sg/v2/bill-pay");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $paymentXml);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/xml',
    'Content-Type: application/xml',
    'NBC-Authorization: Basic ' . base64_encode("$username:$password"),
    'Timestamp: ' . $timestamp,
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
if ($response) {
    $xml = simplexml_load_string($response);
    if ($xml) {
        echo "Response: " . print_r($xml, true) . "\n";
    } else {
        echo "Response: $response\n";
    }
}

echo "\nTest completed at: " . date('Y-m-d H:i:s') . "\n";