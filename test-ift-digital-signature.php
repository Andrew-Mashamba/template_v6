<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

echo "\n════════════════════════════════════════════════════════════════════\n";
echo "   NBC INTERNAL FUNDS TRANSFER TEST (CORRECT DIGITAL SIGNATURE)     \n";
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
        'service' => 'internal_ft',  // Using simpler service name
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

// Convert body to JSON string for signing (sign the whole JSON payload)
$jsonPayload = json_encode($requestBody);

echo "SIGNATURE GENERATION:\n";
echo "━━━━━━━━━━━━━━━━━━━━\n";

// Generate digital signature using SHA256withRSA
$digitalSignature = '';
if ($privateKeyExists) {
    $privateKey = file_get_contents($privateKeyPath);
    if ($privateKey) {
        $pkeyid = openssl_pkey_get_private($privateKey);
        if ($pkeyid) {
            // Sign the whole JSON payload with SHA256withRSA
            $signSuccess = openssl_sign($jsonPayload, $signatureRaw, $pkeyid, OPENSSL_ALGO_SHA256);
            if ($signSuccess) {
                $digitalSignature = base64_encode($signatureRaw);
                echo "✓ Digital signature generated successfully\n";
                echo "  • Algorithm: SHA256withRSA\n";
                echo "  • Payload signed: Entire JSON body\n";
                echo "  • Signature length: " . strlen($digitalSignature) . " characters\n\n";
            } else {
                echo "✗ Failed to generate signature\n\n";
                $digitalSignature = $basicAuth; // Fallback
            }
            openssl_pkey_free($pkeyid);
        } else {
            echo "✗ Could not load private key\n\n";
            $digitalSignature = $basicAuth; // Fallback
        }
    }
} else {
    echo "✗ Private key not found at: $privateKeyPath\n\n";
    $digitalSignature = $basicAuth; // Fallback
}

// Prepare headers with correct tag name 'digitalsignature'
$headers = [
    'Content-Type' => 'application/json',
    'Accept' => 'application/json',
    'digitalsignature' => $digitalSignature,  // Correct tag name
    'x-api-key' => $apiKey,
    'NBC-Authorization' => 'Basic ' . $basicAuth
];

// Display request details
echo "REQUEST DETAILS:\n";
echo "━━━━━━━━━━━━━━━━\n";
echo "URL: $baseUrl\n";
echo "Method: POST\n\n";

echo "Headers (with correct tag names):\n";
foreach ($headers as $key => $value) {
    if (in_array($key, ['x-api-key', 'digitalsignature', 'NBC-Authorization'])) {
        $display = substr($value, 0, 40) . '...[MASKED]';
    } else {
        $display = $value;
    }
    echo "  $key: $display\n";
}

echo "\nRequest Body (to be signed):\n";
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
    echo "━━━━━━━━━━━━━━━━\n";
    echo "HTTP Status Code: $statusCode\n";
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
            echo "Parsed JSON Response:\n";
            echo json_encode($responseJson, JSON_PRETTY_PRINT) . "\n\n";
        }
    } else {
        echo "[Empty Response Body]\n\n";
    }
    
    // Interpret results based on API documentation
    echo "════════════════════════════════════════════════════════════════════\n";
    echo "RESULT INTERPRETATION:\n";
    echo "════════════════════════════════════════════════════════════════════\n";
    
    // Check for documented API status codes in response body
    if (isset($responseJson['statusCode'])) {
        $apiStatusCode = $responseJson['statusCode'];
        
        switch($apiStatusCode) {
            case 600:
                echo "✓ TRANSFER SUCCESSFUL (API Status 600)\n";
                echo "  • Message: " . ($responseJson['message'] ?? 'SUCCESS') . "\n";
                if (isset($responseJson['body'])) {
                    $body = $responseJson['body'];
                    echo "  • CBS Host Reference: " . ($body['hostReferenceCbs'] ?? 'N/A') . "\n";
                    echo "  • CBS Status Code: " . ($body['hostStatusCodeCbs'] ?? 'N/A') . "\n";
                    echo "  • Gateway Reference: " . ($body['hostReferenceGw'] ?? 'N/A') . "\n";
                    echo "  • CBS Response Time: " . ($body['cbsRespTime'] ?? 'N/A') . "\n";
                }
                break;
                
            case 700:
                echo "✗ TRANSFER FAILED (API Status 700)\n";
                echo "  • Message: " . ($responseJson['message'] ?? 'FAILED') . "\n";
                if (isset($responseJson['body'])) {
                    $body = $responseJson['body'];
                    echo "  • CBS Status Code: " . ($body['hostStatusCodeCbs'] ?? 'N/A') . "\n";
                    
                    // Interpret CBS status codes
                    $cbsCode = $body['hostStatusCodeCbs'] ?? '';
                    if ($cbsCode == '57') {
                        echo "  • CBS Error: Transaction not permitted\n";
                    } elseif ($cbsCode) {
                        echo "  • CBS Error Code: $cbsCode\n";
                    }
                    
                    echo "  • Gateway Reference: " . ($body['hostReferenceGw'] ?? 'N/A') . "\n";
                }
                break;
                
            case 625:
                echo "✗ NO RESPONSE (API Status 625)\n";
                echo "  • CBS system did not respond\n";
                break;
                
            case 626:
                echo "✗ TRANSACTION FAILED (API Status 626)\n";
                break;
                
            case 630:
                echo "✗ CURRENCY MISMATCH (API Status 630)\n";
                echo "  • Currency account combination does not match\n";
                break;
                
            case 631:
                echo "✗ BILLER NOT DEFINED (API Status 631)\n";
                break;
                
            default:
                echo "⚠ API Status Code: $apiStatusCode\n";
                echo "  • Message: " . ($responseJson['message'] ?? 'Unknown') . "\n";
        }
    } else {
        // HTTP status interpretation
        switch($statusCode) {
            case 200:
            case 201:
                echo "✓ HTTP REQUEST SUCCESSFUL (Status $statusCode)\n";
                if (isset($responseJson['message'])) {
                    echo "  • Message: {$responseJson['message']}\n";
                }
                break;
            case 400:
                echo "✗ BAD REQUEST (Status 400)\n";
                echo "  • Check request parameters and format\n";
                break;
            case 401:
                echo "✗ UNAUTHORIZED (Status 401)\n";
                echo "  • Authentication failed\n";
                echo "  • Verify: NBC-Authorization, digitalsignature, x-api-key\n";
                break;
            case 403:
                echo "✗ FORBIDDEN (Status 403)\n";
                break;
            case 404:
                echo "✗ NOT FOUND (Status 404)\n";
                break;
            case 500:
                echo "✗ SERVER ERROR (Status 500)\n";
                break;
            default:
                echo "⚠ HTTP Status: $statusCode\n";
        }
        
        if (isset($responseJson['error'])) {
            echo "  • Error: {$responseJson['error']}\n";
        }
    }
    
    echo "\nTransaction Summary:\n";
    echo "  • Channel Reference: $channelRef\n";
    echo "  • Debit Account: 011191000035 (TZS)\n";
    echo "  • Credit Account: 011201318462 (TZS)\n";
    echo "  • Amount: TZS 1,000.00\n";
    echo "  • Timestamp: " . date('Y-m-d H:i:s') . "\n";
    
    // Save full details for debugging
    $logFile = 'storage/logs/ift-digital-' . date('Y-m-d-His') . '.json';
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'channel_ref' => $channelRef,
        'signature_info' => [
            'algorithm' => 'SHA256withRSA',
            'header_name' => 'digitalsignature',
            'payload_signed' => 'entire JSON body',
            'private_key_used' => $privateKeyExists
        ],
        'request' => [
            'url' => $baseUrl,
            'headers' => array_map(function($v, $k) {
                return in_array($k, ['x-api-key', 'digitalsignature', 'NBC-Authorization']) 
                    ? substr($v, 0, 30) . '...[MASKED]' 
                    : $v;
            }, $headers, array_keys($headers)),
            'body' => $requestBody
        ],
        'response' => [
            'http_status' => $statusCode,
            'body_raw' => $responseBody,
            'body_json' => $responseJson,
            'response_time_ms' => $duration
        ]
    ];
    
    @file_put_contents($logFile, json_encode($logData, JSON_PRETTY_PRINT));
    echo "\n  • Full log saved to: $logFile\n";
    
} catch (Exception $e) {
    $duration = isset($startTime) ? round((microtime(true) - $startTime) * 1000, 2) : 0;
    
    echo "ERROR OCCURRED:\n";
    echo "━━━━━━━━━━━━━━━\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "Response Time: {$duration}ms\n";
    
    if (method_exists($e, 'response') && $e->response) {
        echo "Response Status: " . $e->response->status() . "\n";
        echo "Response Body: " . $e->response->body() . "\n";
    }
}

echo "\n════════════════════════════════════════════════════════════════════\n";
echo "Test completed at " . date('Y-m-d H:i:s') . "\n";
echo "════════════════════════════════════════════════════════════════════\n\n";