<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n════════════════════════════════════════════════════════════════════\n";
echo "     NBC INTERNAL FUNDS TRANSFER TEST (WITH PROPER SIGNATURE)       \n";
echo "════════════════════════════════════════════════════════════════════\n\n";

// Configuration
$baseUrl = 'http://cbpuat.intra.nbc.co.tz:6666/api/nbc-sg/internal_ft';
$apiKey = 'b1f6c3a92e4d9a7c34f981cf22b54e716e5e8d2aab57ff449c6a1347088c3f55';
$basicAuth = 'c2FjY29zbmJjOkBOQkNzYWNjb3Npc2FsZUx0ZA==';

// Private key path for signature
$privateKeyPath = '/var/www/html/template/storage/keys/private_key.pem';
$privateKeyExists = file_exists($privateKeyPath);

// Generate channel reference
$channelRef = 'CH' . date('YmdHis') . rand(1000, 9999);

// Prepare request body
$requestBody = [
    'header' => [
        'service' => 'internal_ft_saccos',  // This should be provided by NBC
        'extra' => [
            'pyrName' => 'SACCOS Member'  // Payer Name
        ]
    ],
    'channelId' => 'SACCOSNBC',
    'channelRef' => $channelRef,
    'creditAccount' => '011201318462',
    'creditCurrency' => 'TZS',
    'debitAccount' => '011191000035',
    'debitCurrency' => 'TZS',
    'amount' => '1000',
    'narration' => 'Internal Transfer Test - ' . date('Y-m-d H:i:s')
];

// Convert body to JSON string for signing
$jsonPayload = json_encode($requestBody);

// Generate digital signature using SHA256withRSA
$signature = '';
if ($privateKeyExists) {
    $privateKey = file_get_contents($privateKeyPath);
    if ($privateKey) {
        $pkeyid = openssl_pkey_get_private($privateKey);
        if ($pkeyid) {
            openssl_sign($jsonPayload, $signatureRaw, $pkeyid, OPENSSL_ALGO_SHA256);
            $signature = base64_encode($signatureRaw);
            echo "✓ Digital signature generated using private key\n\n";
        } else {
            echo "⚠ Could not load private key, using basic auth as signature\n\n";
            $signature = $basicAuth;
        }
    }
} else {
    echo "⚠ Private key not found at: $privateKeyPath\n";
    echo "  Using basic auth token as signature fallback\n\n";
    $signature = $basicAuth;
}

// Prepare headers according to API documentation
$headers = [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
    'Signature' => $signature,  // Digital signature of the payload
    'x-api-key' => $apiKey,
    'NBC-Authorization' => 'Basic ' . $basicAuth  // NBC-specific auth header
];

// Display request details
echo "REQUEST DETAILS (Per API Documentation v1.2):\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "URL: $baseUrl\n";
echo "Method: POST\n\n";

echo "Authentication Methods Used:\n";
echo "1. NBC-Authorization: Basic authentication\n";
echo "2. Digital Signature: " . ($privateKeyExists ? "SHA256withRSA" : "Fallback mode") . "\n";
echo "3. API Key: Configured\n\n";

echo "Headers:\n";
foreach ($headers as $key => $value) {
    if (in_array($key, ['x-api-key', 'Signature', 'NBC-Authorization'])) {
        $display = substr($value, 0, 30) . '...[MASKED]';
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
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Status Code: $statusCode\n";
    echo "Response Time: {$duration}ms\n\n";
    
    echo "Response Headers:\n";
    foreach ($response->headers() as $key => $values) {
        foreach ($values as $value) {
            echo "  $key: $value\n";
        }
    }
    
    echo "\nResponse Body:\n";
    echo "─────────────\n";
    if ($responseBody) {
        echo $responseBody . "\n\n";
        
        if (!empty($responseJson)) {
            echo "Parsed JSON:\n";
            echo json_encode($responseJson, JSON_PRETTY_PRINT) . "\n\n";
        }
    } else {
        echo "[Empty Response Body]\n\n";
    }
    
    // Interpret results based on documentation
    echo "════════════════════════════════════════════════════════════════════\n";
    echo "RESULT INTERPRETATION (Based on API Documentation):\n";
    echo "════════════════════════════════════════════════════════════════════\n";
    
    // Check for documented status codes
    if (isset($responseJson['statusCode'])) {
        $apiStatusCode = $responseJson['statusCode'];
        
        switch($apiStatusCode) {
            case 600:
                echo "✓ TRANSFER SUCCESSFUL (Status 600)\n";
                echo "  • Message: " . ($responseJson['message'] ?? 'SUCCESS') . "\n";
                if (isset($responseJson['body'])) {
                    echo "  • CBS Host Reference: " . ($responseJson['body']['hostReferenceCbs'] ?? 'N/A') . "\n";
                    echo "  • Gateway Reference: " . ($responseJson['body']['hostReferenceGw'] ?? 'N/A') . "\n";
                    echo "  • CBS Response Time: " . ($responseJson['body']['cbsRespTime'] ?? 'N/A') . "\n";
                }
                break;
                
            case 700:
                echo "✗ TRANSFER FAILED (Status 700)\n";
                echo "  • Message: " . ($responseJson['message'] ?? 'FAILED') . "\n";
                if (isset($responseJson['body'])) {
                    echo "  • CBS Status Code: " . ($responseJson['body']['hostStatusCodeCbs'] ?? 'N/A') . "\n";
                    echo "  • Gateway Reference: " . ($responseJson['body']['hostReferenceGw'] ?? 'N/A') . "\n";
                }
                break;
                
            case 625:
                echo "✗ NO RESPONSE (Status 625)\n";
                echo "  • The CBS system did not respond in time\n";
                break;
                
            case 626:
                echo "✗ TRANSACTION FAILED (Status 626)\n";
                echo "  • The transaction could not be processed\n";
                break;
                
            case 630:
                echo "✗ CURRENCY MISMATCH (Status 630)\n";
                echo "  • Currency account combination does not match\n";
                break;
                
            case 631:
                echo "✗ BILLER NOT DEFINED (Status 631)\n";
                echo "  • The biller configuration is missing\n";
                break;
                
            default:
                echo "⚠ UNKNOWN STATUS CODE: $apiStatusCode\n";
                echo "  • Message: " . ($responseJson['message'] ?? 'Unknown') . "\n";
        }
    } else {
        // HTTP status interpretation
        switch($statusCode) {
            case 200:
            case 201:
                echo "✓ HTTP REQUEST SUCCESSFUL\n";
                break;
            case 400:
                echo "✗ BAD REQUEST\n";
                echo "  • Invalid parameters or missing required fields\n";
                echo "  • Check that the service name in header is correct\n";
                echo "  • Verify account numbers are valid NBC accounts\n";
                break;
            case 401:
                echo "✗ UNAUTHORIZED\n";
                echo "  • Authentication failed\n";
                echo "  • Check NBC-Authorization, Signature, and x-api-key\n";
                break;
            case 403:
                echo "✗ FORBIDDEN\n";
                echo "  • Access denied to this resource\n";
                break;
            case 404:
                echo "✗ NOT FOUND\n";
                echo "  • Endpoint or account not found\n";
                break;
            case 500:
                echo "✗ INTERNAL SERVER ERROR\n";
                echo "  • NBC gateway error\n";
                break;
        }
        
        if (isset($responseJson['message'])) {
            echo "  • Server Message: {$responseJson['message']}\n";
        }
        if (isset($responseJson['error'])) {
            echo "  • Error Details: {$responseJson['error']}\n";
        }
    }
    
    echo "\nTransfer Details:\n";
    echo "  • Channel Ref: $channelRef\n";
    echo "  • From Account: 011191000035 (TZS)\n";
    echo "  • To Account: 011201318462 (TZS)\n";
    echo "  • Amount: TZS 1,000.00\n";
    
    // Save for debugging
    $logFile = 'storage/logs/ift-test-' . date('Y-m-d-His') . '.json';
    $logData = [
        'test_timestamp' => date('Y-m-d H:i:s'),
        'channel_ref' => $channelRef,
        'request' => [
            'url' => $baseUrl,
            'headers' => array_map(function($v, $k) {
                return in_array($k, ['x-api-key', 'Signature', 'NBC-Authorization']) 
                    ? substr($v, 0, 20) . '...[MASKED]' 
                    : $v;
            }, $headers, array_keys($headers)),
            'body' => $requestBody
        ],
        'response' => [
            'http_status' => $statusCode,
            'headers' => $response->headers(),
            'body_raw' => $responseBody,
            'body_json' => $responseJson,
            'time_ms' => $duration
        ],
        'authentication' => [
            'methods_used' => ['NBC-Authorization', 'Digital Signature', 'API Key'],
            'private_key_exists' => $privateKeyExists,
            'signature_type' => $privateKeyExists ? 'SHA256withRSA' : 'Fallback'
        ]
    ];
    
    @file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
    echo "\n  • Test details saved to: $logFile\n";
    
} catch (Exception $e) {
    $duration = isset($startTime) ? round((microtime(true) - $startTime) * 1000, 2) : 0;
    
    echo "ERROR OCCURRED:\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Response Time: {$duration}ms\n";
}

echo "\n════════════════════════════════════════════════════════════════════\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";
echo "════════════════════════════════════════════════════════════════════\n\n";