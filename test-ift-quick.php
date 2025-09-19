<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n════════════════════════════════════════════════════════════════════\n";
echo "                 NBC INTERNAL FUNDS TRANSFER TEST                   \n";
echo "════════════════════════════════════════════════════════════════════\n\n";

// Configuration
$baseUrl = 'http://cbpuat.intra.nbc.co.tz:6666/api/nbc-sg/internal_ft';
$apiKey = 'b1f6c3a92e4d9a7c34f981cf22b54e716e5e8d2aab57ff449c6a1347088c3f55';
$authToken = 'c2FjY29zbmJjOkBOQkNzYWNjb3Npc2FsZUx0ZA=='; // New authorization

// Generate channel reference
$channelRef = 'CH' . date('YmdHis') . rand(1000, 9999);

// Prepare request body
$requestBody = [
    'header' => [
        'service' => 'internal_ft',
        'extra' => ['pyrName' => 'Test User']
    ],
    'channelId' => 'SACCOSNBC',
    'channelRef' => $channelRef,
    'creditAccount' => '011201318462',  // Your specified destination account
    'debitAccount' => '011191000035',   // Your specified source account
    'amount' => '1000',
    'narration' => 'Test NBC Internal Transfer - ' . date('Y-m-d H:i:s')
];

// Prepare headers
$headers = [
    'Content-Type' => 'application/json',
    'X-Api-Key' => $apiKey,
    'Authorization' => 'Basic ' . $authToken,
    'NBC-Authorization' => 'Basic ' . $authToken
];

// Display request details
echo "REQUEST DETAILS:\n";
echo "----------------\n";
echo "URL: $baseUrl\n\n";

echo "Headers:\n";
foreach ($headers as $key => $value) {
    if (in_array($key, ['X-Api-Key', 'Authorization', 'NBC-Authorization'])) {
        $display = substr($value, 0, 20) . '...[MASKED]';
    } else {
        $display = $value;
    }
    echo "  $key: $display\n";
}

echo "\nRequest Body:\n";
echo json_encode($requestBody, JSON_PRETTY_PRINT) . "\n\n";

echo "════════════════════════════════════════════════════════════════════\n";
echo "SENDING REQUEST...\n";
echo "════════════════════════════════════════════════════════════════════\n\n";

try {
    $startTime = microtime(true);
    
    // Make the HTTP request
    $response = Http::withHeaders($headers)
        ->withOptions(['verify' => false])
        ->timeout(30)
        ->post($baseUrl, $requestBody);
    
    $duration = round((microtime(true) - $startTime) * 1000, 2);
    
    // Get response details
    $statusCode = $response->status();
    $responseBody = $response->body();
    $responseJson = $response->json() ?? [];
    
    echo "RESPONSE DETAILS:\n";
    echo "-----------------\n";
    echo "Status Code: $statusCode\n";
    echo "Response Time: {$duration}ms\n\n";
    
    echo "Response Headers:\n";
    foreach ($response->headers() as $key => $values) {
        foreach ($values as $value) {
            echo "  $key: $value\n";
        }
    }
    
    echo "\nResponse Body (Raw):\n";
    echo $responseBody . "\n\n";
    
    if (!empty($responseJson)) {
        echo "Response Body (Parsed):\n";
        echo json_encode($responseJson, JSON_PRETTY_PRINT) . "\n\n";
    }
    
    // Interpret results
    echo "════════════════════════════════════════════════════════════════════\n";
    echo "RESULT SUMMARY:\n";
    echo "════════════════════════════════════════════════════════════════════\n";
    
    if ($statusCode === 200 || $statusCode === 201) {
        echo "✓ TRANSFER SUCCESSFUL\n";
        echo "  • Channel Ref: $channelRef\n";
        echo "  • From Account: 011191000035\n";
        echo "  • To Account: 011201318462\n";
        echo "  • Amount: TZS 1,000.00\n";
        if (isset($responseJson['referenceNumber'])) {
            echo "  • NBC Reference: {$responseJson['referenceNumber']}\n";
        }
    } else {
        echo "✗ TRANSFER FAILED\n";
        echo "  • Status Code: $statusCode\n";
        if (isset($responseJson['message'])) {
            echo "  • Error Message: {$responseJson['message']}\n";
        }
        if (isset($responseJson['error'])) {
            echo "  • Error: {$responseJson['error']}\n";
        }
    }
    
} catch (Exception $e) {
    $duration = isset($startTime) ? round((microtime(true) - $startTime) * 1000, 2) : 0;
    
    echo "ERROR OCCURRED:\n";
    echo "---------------\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Response Time: {$duration}ms\n";
}

echo "\n════════════════════════════════════════════════════════════════════\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";
echo "════════════════════════════════════════════════════════════════════\n\n";