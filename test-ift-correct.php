<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n════════════════════════════════════════════════════════════════════\n";
echo "           NBC INTERNAL FUNDS TRANSFER TEST (CORRECT FORMAT)        \n";
echo "════════════════════════════════════════════════════════════════════\n\n";

// Configuration
$baseUrl = 'http://cbpuat.intra.nbc.co.tz:6666/api/nbc-sg/internal_ft';

// Generate signature (using the authorization token as signature for now)
$signature = 'c2FjY29zbmJjOkBOQkNzYWNjb3Npc2FsZUx0ZA==';
$apiKey = 'b1f6c3a92e4d9a7c34f981cf22b54e716e5e8d2aab57ff449c6a1347088c3f55';

// Generate channel reference
$channelRef = 'CH' . date('YmdHis') . rand(1000, 9999);

// Prepare request body with ALL required fields including currency
$requestBody = [
    'header' => [
        'service' => 'internal_ft',  // Service name from NBC
        'extra' => [
            'pyrName' => 'Test User'  // Payer Name - Mandatory
        ]
    ],
    'channelId' => 'SACCOSNBC',  // Valid channel ID
    'channelRef' => $channelRef,  // Unique reference
    'creditAccount' => '011201318462',  // Your specified destination account
    'creditCurrency' => 'TZS',  // Currency for credit account - MANDATORY
    'debitAccount' => '011191000035',  // Your specified source account  
    'debitCurrency' => 'TZS',  // Currency for debit account - MANDATORY
    'amount' => '1000',  // Transfer amount
    'narration' => 'Test NBC Internal Transfer - ' . date('Y-m-d H:i:s')  // Transfer narration
];

// Prepare headers according to API documentation
$headers = [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',  // Note: lowercase 'json' as per doc
    'Signature' => $signature,  // Signature header (not Authorization)
    'x-api-key' => $apiKey  // lowercase x-api-key as per documentation
];

// Display request details
echo "REQUEST DETAILS (Per Official API Documentation):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "URL: $baseUrl\n";
echo "Method: POST\n\n";

echo "Headers:\n";
foreach ($headers as $key => $value) {
    if (in_array($key, ['x-api-key', 'Signature'])) {
        $display = substr($value, 0, 20) . '...[MASKED]';
    } else {
        $display = $value;
    }
    echo "  $key: $display\n";
}

echo "\nRequest Body (with all mandatory fields):\n";
echo json_encode($requestBody, JSON_PRETTY_PRINT) . "\n\n";

echo "════════════════════════════════════════════════════════════════════\n";
echo "SENDING REQUEST...\n";
echo "════════════════════════════════════════════════════════════════════\n\n";

try {
    $startTime = microtime(true);
    
    // Make the HTTP request with correct headers
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
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Status Code: $statusCode\n";
    echo "Response Time: {$duration}ms\n\n";
    
    echo "Response Headers:\n";
    foreach ($response->headers() as $key => $values) {
        foreach ($values as $value) {
            echo "  $key: $value\n";
        }
    }
    
    echo "\nResponse Body (Raw):\n";
    echo "───────────────────\n";
    echo ($responseBody ?: "[Empty Response]") . "\n\n";
    
    if (!empty($responseJson)) {
        echo "Response Body (Parsed JSON):\n";
        echo "──────────────────────────\n";
        echo json_encode($responseJson, JSON_PRETTY_PRINT) . "\n\n";
    }
    
    // Interpret results
    echo "════════════════════════════════════════════════════════════════════\n";
    echo "RESULT SUMMARY:\n";
    echo "════════════════════════════════════════════════════════════════════\n";
    
    if ($statusCode === 200 || $statusCode === 201) {
        echo "✓ TRANSFER SUCCESSFUL\n";
        echo "  • Channel Ref: $channelRef\n";
        echo "  • From Account: 011191000035 (TZS)\n";
        echo "  • To Account: 011201318462 (TZS)\n";
        echo "  • Amount: TZS 1,000.00\n";
        if (isset($responseJson['referenceNumber'])) {
            echo "  • NBC Reference: {$responseJson['referenceNumber']}\n";
        }
        if (isset($responseJson['message'])) {
            echo "  • Message: {$responseJson['message']}\n";
        }
    } else {
        echo "✗ TRANSFER FAILED\n";
        echo "  • Status Code: $statusCode\n";
        
        // Common error codes
        switch($statusCode) {
            case 400:
                echo "  • Error Type: Bad Request (Invalid parameters)\n";
                break;
            case 401:
                echo "  • Error Type: Unauthorized (Authentication failed)\n";
                break;
            case 403:
                echo "  • Error Type: Forbidden (Access denied)\n";
                break;
            case 404:
                echo "  • Error Type: Not Found (Invalid endpoint or account)\n";
                break;
            case 500:
                echo "  • Error Type: Server Error\n";
                break;
        }
        
        if (isset($responseJson['message'])) {
            echo "  • Error Message: {$responseJson['message']}\n";
        }
        if (isset($responseJson['error'])) {
            echo "  • Error Details: {$responseJson['error']}\n";
        }
        if (isset($responseJson['errors'])) {
            echo "  • Validation Errors:\n";
            foreach ($responseJson['errors'] as $field => $error) {
                echo "    - $field: " . (is_array($error) ? implode(', ', $error) : $error) . "\n";
            }
        }
    }
    
    // Save full response for debugging
    $logFile = 'storage/logs/ift-response-' . date('Y-m-d-His') . '.json';
    $logData = [
        'request' => [
            'url' => $baseUrl,
            'headers' => $headers,
            'body' => $requestBody
        ],
        'response' => [
            'status' => $statusCode,
            'headers' => $response->headers(),
            'body' => $responseBody,
            'json' => $responseJson,
            'time_ms' => $duration
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
    file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
    echo "\n  • Full details saved to: $logFile\n";
    
} catch (Exception $e) {
    $duration = isset($startTime) ? round((microtime(true) - $startTime) * 1000, 2) : 0;
    
    echo "ERROR OCCURRED:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Response Time: {$duration}ms\n";
    
    if (method_exists($e, 'response') && $e->response) {
        echo "\nResponse Status: " . $e->response->status() . "\n";
        echo "Response Body: " . $e->response->body() . "\n";
    }
}

echo "\n════════════════════════════════════════════════════════════════════\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";
echo "Channel Reference: $channelRef\n";
echo "════════════════════════════════════════════════════════════════════\n\n";