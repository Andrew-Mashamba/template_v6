<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Payments\InternalFundsTransferService;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class TestNbcInternalTransfer extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nbc:test-ift 
                            {--from=011191000035 : Source account number}
                            {--to=011191000036 : Destination account number}
                            {--amount=1000 : Transfer amount}
                            {--raw : Show raw curl request for debugging}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test NBC Internal Funds Transfer with detailed request/response logging';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('========================================');
        $this->info('NBC INTERNAL FUNDS TRANSFER TEST');
        $this->info('========================================');
        $this->newLine();

        $fromAccount = $this->option('from');
        $toAccount = $this->option('to');
        $amount = $this->option('amount');
        $showRaw = $this->option('raw');

        // Configuration
        $baseUrl = config('services.nbc_internal_fund_transfer.base_url');
        $apiKey = config('services.nbc_internal_fund_transfer.api_key');
        $username = config('services.nbc_internal_fund_transfer.username');
        $password = config('services.nbc_internal_fund_transfer.password');
        $channelId = config('services.nbc_internal_fund_transfer.channel_id');
        $serviceName = config('services.nbc_internal_fund_transfer.service_name', 'internal_ft');
        $privateKeyPath = config('services.nbc_internal_fund_transfer.private_key');
        
        // DEBUG: Show all configuration values
        $this->info('DEBUG - CONFIGURATION VALUES:');
        $this->line('  Base URL: ' . ($baseUrl ?: 'NOT SET'));
        $this->line('  API Key: ' . ($apiKey ?: 'NOT SET'));
        $this->line('  Username: ' . ($username ?: 'NOT SET'));
        $this->line('  Password: ' . ($password ?: 'NOT SET'));
        $this->line('  Channel ID: ' . ($channelId ?: 'NOT SET'));
        $this->line('  Service Name: ' . ($serviceName ?: 'NOT SET'));
        $this->line('  Private Key Path: ' . ($privateKeyPath ?: 'NOT SET'));
        $this->newLine();

        // Build full URL
        $url = rtrim($baseUrl, '/') . '/' . ltrim($serviceName, '/');
        
        // Generate unique reference
        $channelRef = 'CH' . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));

        // Build request payload
        $payload = [
            'header' => [
                'service' => $serviceName,
                'extra' => [
                    'pyrName' => 'Test User'
                ]
            ],
            'channelId' => $channelId,
            'channelRef' => $channelRef,
            'creditAccount' => $toAccount,
            'creditCurrency' => 'TZS',
            'debitAccount' => $fromAccount,
            'debitCurrency' => 'TZS',
            'amount' => (string) $amount,
            'narration' => 'Test NBC Internal Transfer - ' . Carbon::now()->format('Y-m-d H:i:s')
        ];

        // Generate headers
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'x-api-key' => $apiKey  // lowercase as per NBC documentation
        ];

        // Add Basic Authentication if credentials exist
        if ($username && $password) {
            $basicAuth = base64_encode($username . ':' . $password);
            $headers['NBC-Authorization'] = 'Basic ' . $basicAuth;
            $this->info('DEBUG - Basic Auth:');
            $this->line('  Credentials: ' . $username . ':' . $password);
            $this->line('  Base64 Encoded: ' . $basicAuth);
            $this->line('  Full NBC-Authorization Header: Basic ' . $basicAuth);
            $this->newLine();
        }

        // Generate digital signature if private key exists
        $signature = null;
        if ($privateKeyPath && file_exists(str_replace('file://', '', $privateKeyPath))) {
            $privateKeyFile = str_replace('file://', '', $privateKeyPath);
            $this->info('Generating digital signature...');
            
            try {
                $privateKeyContent = file_get_contents($privateKeyFile);
                $privateKey = openssl_pkey_get_private($privateKeyContent);
                
                if ($privateKey) {
                    $payloadString = json_encode($payload, JSON_UNESCAPED_SLASHES);
                    $signatureRaw = '';
                    
                    $this->info('DEBUG - Payload being signed:');
                    $this->line($payloadString);
                    $this->newLine();
                    
                    if (openssl_sign($payloadString, $signatureRaw, $privateKey, OPENSSL_ALGO_SHA256)) {
                        $signature = base64_encode($signatureRaw);
                        $headers['Signature'] = $signature;
                        $this->info('✓ Signature generated successfully');
                        $this->line('  Full Signature: ' . $signature);
                    } else {
                        $this->warn('Failed to generate signature: ' . openssl_error_string());
                    }
                } else {
                    $this->warn('Failed to load private key: ' . openssl_error_string());
                }
            } catch (\Exception $e) {
                $this->warn('Signature generation error: ' . $e->getMessage());
            }
        }

        // Display request details
        $this->info('URL:');
        $this->line($url);
        $this->newLine();

        $this->info('HEADERS (FULL DEBUG MODE - SHOWING ALL VALUES):');
        foreach ($headers as $key => $value) {
            $this->line("  $key: $value");
        }
        $this->newLine();

        $this->info('REQUEST BODY:');
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        $this->newLine();

        // Show raw curl command if requested
        if ($showRaw) {
            $this->info('RAW CURL COMMAND:');
            $curlCommand = $this->buildCurlCommand($url, $headers, $payload);
            $this->line($curlCommand);
            $this->newLine();
        }

        $this->info('========================================');
        $this->info('SENDING REQUEST...');
        $this->info('========================================');
        $this->newLine();

        // Make the actual request
        try {
            $startTime = microtime(true);
            
            $httpClient = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => config('services.nbc_internal_fund_transfer.verify_ssl', false)
                ])
                ->timeout(config('services.nbc_internal_fund_transfer.timeout', 30));

            $response = $httpClient->post($url, $payload);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            $statusCode = $response->status();
            $responseBody = $response->body();
            $responseData = $response->json();

            // Display response details
            $this->info('HTTP STATUS: ' . $statusCode);
            $this->info('RESPONSE TIME: ' . $duration . 'ms');
            $this->newLine();

            $this->info('RESPONSE HEADERS:');
            foreach ($response->headers() as $key => $values) {
                $this->line("  $key: " . implode(', ', $values));
            }
            $this->newLine();

            $this->info('RESPONSE BODY:');
            if ($responseData) {
                $this->line(json_encode($responseData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                
                // Check NBC status code
                if (isset($responseData['statusCode'])) {
                    $this->newLine();
                    $this->info('NBC Status Code: ' . $responseData['statusCode']);
                    
                    if ($responseData['statusCode'] == 600) {
                        $this->info('✓ NBC Transfer successful!');
                        if (isset($responseData['hostReferenceCbs'])) {
                            $this->info('CBS Reference: ' . $responseData['hostReferenceCbs']);
                        }
                        if (isset($responseData['hostReferenceGw'])) {
                            $this->info('Gateway Reference: ' . $responseData['hostReferenceGw']);
                        }
                    } else {
                        $errorMessages = [
                            626 => 'Transaction Failed',
                            625 => 'No Response from CBS',
                            630 => 'Currency account combination does not match',
                            631 => 'Biller not defined',
                            700 => 'General Failure'
                        ];
                        $errorMsg = $errorMessages[$responseData['statusCode']] ?? 'Unknown error';
                        $this->error('✗ NBC Error: ' . $errorMsg);
                    }
                }
            } else {
                $this->line($responseBody ?: '(Empty response)');
            }
            $this->newLine();

            // Interpret HTTP status
            $this->info('========================================');
            $this->info('RESULT ANALYSIS:');
            $this->info('========================================');
            $this->newLine();

            switch ($statusCode) {
                case 200:
                case 201:
                    $this->info('✓ HTTP request successful');
                    break;
                case 401:
                    $this->error('✗ Authentication failed (HTTP 401)');
                    $this->newLine();
                    $this->warn('Possible issues:');
                    $this->line('  1. Invalid API key in x-api-key header');
                    $this->line('  2. Invalid username/password in NBC-Authorization header');
                    $this->line('  3. Missing or invalid digital signature');
                    $this->line('  4. Incorrect authentication format');
                    $this->newLine();
                    $this->info('Current configuration (FULL DEBUG MODE):');
                    $this->line('  Username: ' . ($username ?: 'NOT SET'));
                    $this->line('  Password: ' . ($password ?: 'NOT SET'));
                    $this->line('  API Key: ' . ($apiKey ?: 'NOT SET'));
                    $this->line('  Private Key Path: ' . ($privateKeyPath ?: 'NOT SET'));
                    $this->line('  Signature: ' . ($signature ?: 'Not generated'));
                    if ($signature) {
                        $this->line('  Full Signature: ' . $signature);
                    }
                    break;
                case 400:
                    $this->error('✗ Bad request (HTTP 400)');
                    $this->warn('Check request body format and required fields');
                    break;
                case 404:
                    $this->error('✗ Endpoint not found (HTTP 404)');
                    $this->warn('Check API URL: ' . $url);
                    break;
                case 500:
                case 502:
                case 503:
                    $this->error('✗ Server error (HTTP ' . $statusCode . ')');
                    $this->warn('NBC server encountered an error');
                    break;
                default:
                    $this->error('✗ Unexpected status (HTTP ' . $statusCode . ')');
            }

        } catch (\Exception $e) {
            $this->error('Request failed with exception:');
            $this->error($e->getMessage());
            $this->newLine();
            
            if ($e instanceof \Illuminate\Http\Client\ConnectionException) {
                $this->warn('Connection error - check if the NBC server is accessible');
                $this->warn('URL: ' . $url);
            }
        }

        $this->newLine();
        $this->info('========================================');
        $this->info('Test completed at ' . Carbon::now()->format('Y-m-d H:i:s'));
        $this->info('========================================');

        return Command::SUCCESS;
    }

    /**
     * Build a curl command for debugging
     */
    private function buildCurlCommand($url, $headers, $payload): string
    {
        $curl = "curl -X POST '$url' \\\n";
        
        // Show all headers in full for debugging
        foreach ($headers as $key => $value) {
            $curl .= "  -H '$key: $value' \\\n";
        }
        
        $curl .= "  -d '" . json_encode($payload, JSON_UNESCAPED_SLASHES) . "'";
        
        return $curl;
    }
}