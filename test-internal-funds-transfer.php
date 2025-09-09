<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\Payments\InternalFundsTransferService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

/**
 * Extended service class to capture HTTP request/response data
 */
class InternalFundsTransferServiceWithCapture extends InternalFundsTransferService
{
    public array $capturedRequests = [];
    public array $capturedResponses = [];
    
    /**
     * Override sendRequest to capture HTTP traffic
     */
    protected function sendRequest(string $endpoint, array $payload): array
    {
        $url = $this->baseUrl . $endpoint;
        $requestId = uniqid('req_');
        
        // Prepare headers
        $headers = [
            'Content-Type' => 'application/json',
            'X-Api-Key' => $this->apiKey,
            'Client-Id' => $this->clientId,
            'Service-Name' => 'IFT'
        ];
        
        // Capture request
        $this->capturedRequests[] = [
            'id' => $requestId,
            'timestamp' => Carbon::now()->toIso8601String(),
            'method' => 'POST',
            'url' => $url,
            'headers' => $headers,
            'body' => $payload
        ];
        
        // Display request in real-time
        echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
        echo "║                        OUTGOING REQUEST                          ║\n";
        echo "╚══════════════════════════════════════════════════════════════════╝\n";
        echo "Request ID: {$requestId}\n";
        echo "Timestamp: " . Carbon::now()->toDateTimeString() . "\n";
        echo "Method: POST\n";
        echo "URL: {$url}\n\n";
        
        echo "Headers:\n";
        foreach ($headers as $key => $value) {
            // Mask sensitive headers
            if (in_array($key, ['X-Api-Key'])) {
                $displayValue = substr($value, 0, 8) . '...[MASKED]';
            } else {
                $displayValue = $value;
            }
            echo "  {$key}: {$displayValue}\n";
        }
        
        echo "\nRequest Body:\n";
        echo json_encode($payload, JSON_PRETTY_PRINT) . "\n";
        echo "════════════════════════════════════════════════════════════════════\n";
        
        try {
            $startTime = microtime(true);
            
            // Make the actual HTTP request
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => false,
                    'on_stats' => function (\GuzzleHttp\TransferStats $stats) {
                        if ($stats->hasResponse()) {
                            echo "\nConnection Stats:\n";
                            echo "  • DNS Lookup: " . round($stats->getHandshakeTime() * 1000, 2) . " ms\n";
                            echo "  • Total Time: " . round($stats->getTransferTime() * 1000, 2) . " ms\n";
                        }
                    }
                ])
                ->timeout(30)
                ->post($url, $payload);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseHeaders = $response->headers();
            $responseJson = $response->json() ?? [];
            
            // Capture response
            $this->capturedResponses[] = [
                'request_id' => $requestId,
                'timestamp' => Carbon::now()->toIso8601String(),
                'status_code' => $statusCode,
                'headers' => $responseHeaders,
                'body' => $responseBody,
                'json' => $responseJson,
                'response_time_ms' => $duration
            ];
            
            // Display response in real-time
            echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
            echo "║                        INCOMING RESPONSE                         ║\n";
            echo "╚══════════════════════════════════════════════════════════════════╝\n";
            echo "Request ID: {$requestId}\n";
            echo "Timestamp: " . Carbon::now()->toDateTimeString() . "\n";
            echo "Response Time: {$duration} ms\n";
            echo "HTTP Status: {$statusCode}\n\n";
            
            echo "Response Headers:\n";
            foreach ($responseHeaders as $key => $values) {
                foreach ($values as $value) {
                    echo "  {$key}: {$value}\n";
                }
            }
            
            echo "\nResponse Body (Raw):\n";
            echo $responseBody . "\n\n";
            
            if (!empty($responseJson)) {
                echo "Response Body (JSON):\n";
                echo json_encode($responseJson, JSON_PRETTY_PRINT) . "\n";
            }
            echo "════════════════════════════════════════════════════════════════════\n\n";
            
            // Return in the expected format
            if ($statusCode === 200 || $statusCode === 201) {
                return [
                    'success' => true,
                    'data' => $responseJson
                ];
            }
            
            return [
                'success' => false,
                'message' => $responseJson['message'] ?? "Request failed with status {$statusCode}"
            ];
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            // Capture error response
            $this->capturedResponses[] = [
                'request_id' => $requestId,
                'timestamp' => Carbon::now()->toIso8601String(),
                'error' => true,
                'error_message' => $e->getMessage(),
                'response_time_ms' => $duration
            ];
            
            echo "\n╔══════════════════════════════════════════════════════════════════╗\n";
            echo "║                         REQUEST ERROR                            ║\n";
            echo "╚══════════════════════════════════════════════════════════════════╝\n";
            echo "Request ID: {$requestId}\n";
            echo "Error Type: " . get_class($e) . "\n";
            echo "Error Message: " . $e->getMessage() . "\n";
            echo "Response Time: {$duration} ms\n";
            echo "════════════════════════════════════════════════════════════════════\n\n";
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get all captured traffic
     */
    public function getCapturedTraffic(): array
    {
        return [
            'requests' => $this->capturedRequests,
            'responses' => $this->capturedResponses
        ];
    }
}

// Main test execution
echo "\n";
echo "╔═══════════════════════════════════════════════════════════════════════╗\n";
echo "║          INTERNAL FUNDS TRANSFER SERVICE TEST WITH CAPTURE           ║\n";
echo "╚═══════════════════════════════════════════════════════════════════════╝\n";
echo "Environment: " . app()->environment() . "\n";
echo "Timestamp: " . Carbon::now()->toDateTimeString() . "\n";
echo "Server: " . gethostname() . "\n";
echo "═════════════════════════════════════════════════════════════════════════\n\n";

try {
    // Step 1: Initialize service
    echo "▶ STEP 1: Initializing Service\n";
    echo "───────────────────────────────\n";
    $service = new InternalFundsTransferServiceWithCapture();
    echo "✓ Service initialized\n\n";
    
    // Step 2: Display configuration
    echo "▶ STEP 2: Configuration\n";
    echo "───────────────────────────────\n";
    echo "Base URL: " . config('services.nbc_payments.base_url') . "\n";
    echo "Client ID: " . config('services.nbc_payments.client_id') . "\n";
    echo "API Key: " . (config('services.nbc_payments.api_key') ? '[CONFIGURED]' : '[NOT SET]') . "\n\n";
    
    // Step 3: Prepare transfer data
    echo "▶ STEP 3: Transfer Data\n";
    echo "───────────────────────────────\n";
    $transferData = [
        'from_account' => '011191000035',    // Source NBC account
        'to_account' => '011191000036',      // Destination NBC account
        'amount' => 1000,                    // Transfer amount
        'narration' => 'Test Internal Transfer - ' . date('YmdHis'),
        'sender_name' => 'Test Sender',
        'recipient_name' => 'Test Recipient'
    ];
    
    echo "From Account: {$transferData['from_account']}\n";
    echo "To Account: {$transferData['to_account']}\n";
    echo "Amount: TZS " . number_format($transferData['amount'], 2) . "\n";
    echo "Narration: {$transferData['narration']}\n\n";
    
    // Step 4: Perform account lookups (if API supports it)
    echo "▶ STEP 4: Account Validation\n";
    echo "───────────────────────────────\n";
    
    echo "Validating source account...\n";
    $sourceValidation = $service->lookupAccount($transferData['from_account'], 'source');
    if ($sourceValidation['success']) {
        echo "✓ Source account validated\n";
        echo "  • Account: {$sourceValidation['account_number']}\n";
        echo "  • Name: {$sourceValidation['account_name']}\n";
        echo "  • Status: {$sourceValidation['account_status']}\n";
    } else {
        echo "✗ Source account validation failed: {$sourceValidation['error']}\n";
    }
    
    echo "\nValidating destination account...\n";
    $destValidation = $service->lookupAccount($transferData['to_account'], 'destination');
    if ($destValidation['success']) {
        echo "✓ Destination account validated\n";
        echo "  • Account: {$destValidation['account_number']}\n";
        echo "  • Name: {$destValidation['account_name']}\n";
        echo "  • Status: {$destValidation['account_status']}\n";
    } else {
        echo "✗ Destination account validation failed: {$destValidation['error']}\n";
    }
    echo "\n";
    
    // Step 5: Execute transfer
    echo "▶ STEP 5: Executing Transfer\n";
    echo "───────────────────────────────\n";
    echo "Initiating internal funds transfer...\n\n";
    
    $result = $service->transfer($transferData);
    
    // Step 6: Display results
    echo "\n▶ STEP 6: Transfer Results\n";
    echo "───────────────────────────────\n";
    
    if ($result['success']) {
        echo "✓ TRANSFER SUCCESSFUL\n\n";
        echo "Details:\n";
        echo "  • Reference: {$result['reference']}\n";
        if (isset($result['nbc_reference'])) {
            echo "  • NBC Reference: {$result['nbc_reference']}\n";
        }
        echo "  • Message: {$result['message']}\n";
        echo "  • Amount: TZS " . number_format($result['amount'], 2) . "\n";
        echo "  • From: {$result['from_account']}\n";
        echo "  • To: {$result['to_account']}\n";
        echo "  • Timestamp: {$result['timestamp']}\n";
        if (isset($result['response_time'])) {
            echo "  • Response Time: {$result['response_time']} ms\n";
        }
    } else {
        echo "✗ TRANSFER FAILED\n\n";
        echo "Error Details:\n";
        echo "  • Reference: {$result['reference']}\n";
        echo "  • Error: {$result['error']}\n";
        echo "  • Timestamp: {$result['timestamp']}\n";
    }
    
    // Step 7: Save captured data
    echo "\n▶ STEP 7: Saving Captured Data\n";
    echo "───────────────────────────────\n";
    
    $capturedTraffic = $service->getCapturedTraffic();
    $captureData = [
        'test_info' => [
            'timestamp' => Carbon::now()->toIso8601String(),
            'environment' => app()->environment(),
            'server' => gethostname(),
            'service' => 'InternalFundsTransferService'
        ],
        'transfer_data' => $transferData,
        'result' => $result,
        'http_traffic' => $capturedTraffic
    ];
    
    // Save JSON file
    $jsonFile = 'storage/logs/ift-capture-' . date('Y-m-d-His') . '.json';
    file_put_contents($jsonFile, json_encode($captureData, JSON_PRETTY_PRINT));
    echo "✓ JSON capture saved to: {$jsonFile}\n";
    
    // Save human-readable file
    $txtFile = 'storage/logs/ift-capture-' . date('Y-m-d-His') . '.txt';
    $txtContent = "INTERNAL FUNDS TRANSFER TEST CAPTURE\n";
    $txtContent .= "=====================================\n\n";
    $txtContent .= "Test Timestamp: " . Carbon::now()->toDateTimeString() . "\n";
    $txtContent .= "Environment: " . app()->environment() . "\n\n";
    
    $txtContent .= "TRANSFER DATA:\n";
    $txtContent .= "--------------\n";
    foreach ($transferData as $key => $value) {
        $txtContent .= "  {$key}: {$value}\n";
    }
    
    $txtContent .= "\nHTTP REQUESTS:\n";
    $txtContent .= "--------------\n";
    foreach ($capturedTraffic['requests'] as $req) {
        $txtContent .= "Request ID: {$req['id']}\n";
        $txtContent .= "URL: {$req['url']}\n";
        $txtContent .= "Headers: " . json_encode($req['headers']) . "\n";
        $txtContent .= "Body: " . json_encode($req['body']) . "\n\n";
    }
    
    $txtContent .= "HTTP RESPONSES:\n";
    $txtContent .= "---------------\n";
    foreach ($capturedTraffic['responses'] as $resp) {
        $txtContent .= "Request ID: {$resp['request_id']}\n";
        if (isset($resp['status_code'])) {
            $txtContent .= "Status: {$resp['status_code']}\n";
            $txtContent .= "Response Time: {$resp['response_time_ms']} ms\n";
            if (isset($resp['body'])) {
                $txtContent .= "Body: {$resp['body']}\n";
            }
        } else if (isset($resp['error'])) {
            $txtContent .= "Error: {$resp['error_message']}\n";
        }
        $txtContent .= "\n";
    }
    
    $txtContent .= "FINAL RESULT:\n";
    $txtContent .= "-------------\n";
    $txtContent .= json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    file_put_contents($txtFile, $txtContent);
    echo "✓ Text capture saved to: {$txtFile}\n";
    
} catch (Exception $e) {
    echo "\n╔═══════════════════════════════════════════════════════════════════════╗\n";
    echo "║                            ERROR OCCURRED                             ║\n";
    echo "╚═══════════════════════════════════════════════════════════════════════╝\n";
    echo "Type: " . get_class($e) . "\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n\n";
    
    Log::error('Internal Funds Transfer Test Failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}

echo "\n═════════════════════════════════════════════════════════════════════════\n";
echo "TEST COMPLETED at " . Carbon::now()->toDateTimeString() . "\n";
echo "Check captured data at:\n";
echo " • JSON: storage/logs/ift-capture-*.json\n";
echo " • Text: storage/logs/ift-capture-*.txt\n";
echo " • Laravel Log: storage/logs/laravel-" . date('Y-m-d') . ".log\n";
echo " • Payments Log: storage/logs/payments-" . date('Y-m-d') . ".log\n";
echo "═════════════════════════════════════════════════════════════════════════\n\n";