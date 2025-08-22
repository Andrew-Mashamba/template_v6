<?php
// app/Services/NbcBillsPaymentService.phpxxx

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NbcBillsPaymentService
{
    protected $baseUrl; 
    protected $username;
    protected $password;
    protected $channelId;
    protected $privateKey;
    protected $publicKey;

    public function __construct()
    {
        $this->baseUrl = 'http://22.32.245.87:4433';
        $this->username = config('nbc.bills_payment.username');
        $this->password = config('nbc.bills_payment.password');
        $this->channelId = config('nbc.bills_payment.channel_id');

        Log::info('Initializing NBC Bills Payment Service');

        // Load private key
        $privateKeyContent = Storage::get('keys/private_key.pem');
        $this->privateKey = openssl_pkey_get_private($privateKeyContent);

        if (!$this->privateKey) {
            Log::error('Failed to load private key');
            throw new \Exception("Invalid private key");
        }

        Log::info('Private key loaded successfully');

        // Load public key
        $publicKeyContent = Storage::get('keys/public_key.pem');
        $this->publicKey = openssl_pkey_get_public($publicKeyContent);

        if (!$this->publicKey) {
            Log::error('Failed to load public key');
            throw new \Exception("Invalid public key");
        }

        Log::info('Public key loaded successfully');
    }

    protected function generateDigitalSignature(array $payload): string
    {
        try {
            $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
            Log::debug('Payload to sign: ' . $jsonPayload);

            $signature = '';
            $success = openssl_sign($jsonPayload, $signature, $this->privateKey, OPENSSL_ALGO_SHA256);

            if (!$success) {
                throw new \Exception("Failed to generate digital signature: " . openssl_error_string());
            }

            Log::info('Digital signature generated successfully');
            return base64_encode($signature);

        } catch (\Exception $e) {
            Log::error("Digital Signature Generation Error: " . $e->getMessage());
            throw $e;
        }
    }

    public function verifyDigitalSignature(string $payload, string $signature): bool
    {
        $decodedSignature = base64_decode($signature);
        $result = openssl_verify($payload, $decodedSignature, $this->publicKey, OPENSSL_ALGO_SHA256);
        Log::info('Signature verification result: ' . ($result ? 'valid' : 'invalid'));
        return (bool) $result;
    }

public function getBillers()
{
    try {

        $timestamp = now()->format('Y-m-d\TH:i:s'); // shorter timestamp
        //$channelRef = 'REQ' . strtoupper(Str::random(10)); // alphanumeric, <30 chars
        $channelRef = '20230512103230499';

        $payload = [
            'channelId' => 'SACCOSAPP',
            'requestType' => 'getServiceProviders',
            //'timestamp' => $timestamp,
		'timestamp' => '2023-05-15T01:10:20',
            'channelRef' => $channelRef
        ];

        Log::info('Generating digital signature for biller request');
        $digitalSignature = $this->generateDigitalSignature($payload);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'NBC-Authorization' => 'Basic c2FjY29zaXNhbGU6QE5CQ3NhY2Nvc2lzYWxlTHRk',
            'Digital-Signature' => $digitalSignature,
            'Timestamp' => $timestamp,
        ];

        Log::info('Sending request to NBC API', [
            'url' => $this->baseUrl . '/bills-payments-engine/api/v1/billers-retrieval',
            'headers' => $headers,
            'body' => $payload
        ]);

        $response = Http::withHeaders($headers)->post("{$this->baseUrl}/bills-payments-engine/api/v1/billers-retrieval", $payload);

        Log::info('Received response', ['status' => $response->status(), 'body' => $response->body()]);

        if ($response->successful()) {
            $data = $response->json();

            if ($data['statusCode'] === '600') {
                $activeBillers = array_filter($data['serviceProviders'], fn($biller) => $biller['active'] === true);
                Log::info('Active billers retrieved: ' . count($activeBillers));
                return array_values($activeBillers);
            }

            throw new \Exception("API Error: {$data['message']}");
        }

        throw new \Exception("HTTP Error: {$response->status()}");

    } catch (\Exception $e) {
        Log::error("NBC Billers Retrieval Error: " . $e->getMessage());
        return [];
    }
}







public function inquireDetailedBill(array $params): array
{
    try {
        $timestamp = now()->format('Y-m-d\TH:i:s.v');
        $channelRef = '20230512103230499';

        $payload = [
            'channelId' => 'SACCOSAPP',
            'spCode' => $params['spCode'],
            'requestType' => 'inquiry',
            'timestamp' => $timestamp,
            'userId' => $params['userId'],
            'branchCode' => $params['branchCode'],
            'channelRef' => $channelRef,
            'billRef' => $params['billRef'],
            'extraFields' => $params['extraFields'] ?? (object)[],
        ];

        Log::info('Generating digital signature for detailed bill inquiry');
        $digitalSignature = $this->generateDigitalSignature($payload);

        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'NBC-Authorization' => 'Basic c2FjY29zaXNhbGU6QE5CQ3NhY2Nvc2lzYWxlTHRk',            
            'Digital-Signature' => $digitalSignature,
            'Timestamp' => $timestamp,
        ];

        $url = "{$this->baseUrl}/bills-payments-engine/api/v1/inquiry";

        Log::info('Sending detailed bill inquiry to NBC', [
            'url' => $url,
            'headers' => $headers,
            'payload' => $payload
        ]);

        $response = Http::withHeaders($headers)->post($url, $payload);

        Log::info('NBC inquiry response', [
            'status' => $response->status(),
            'body' => $response->body()
        ]);

        if ($response->successful()) {
            $data = $response->json();

            if ($data['statusCode'] === '600') {
                return $data['billDetails'];
            }

            throw new \Exception("API Error: {$data['message']}");
        }

        throw new \Exception("HTTP Error: {$response->status()}");

    } catch (\Exception $e) {
        Log::error("NBC Detailed Bill Inquiry Error: " . $e->getMessage());
        return [];
    }
}




public function processPaymentAsync(array $params): array
{
    try {
        $timestamp = now()->format('Y-m-d\TH:i:s.v');
         $channelRef = '20230512103230499';

        $payload = [
            'channelId' => 'SACCOSAPP',
            'spCode'             => $params['spCode'],
            'requestType'        => 'payment',
            'approach'           => 'async',
            'callbackUrl'        => $params['callbackUrl'],
            'timestamp'          => $timestamp,
            'userId'             => $params['userId'],
            'branchCode'         => $params['branchCode'],
            'billRef'            => $params['billRef'],
            'channelRef'         => $channelRef,
            'amount'             => $params['amount'],
            'creditAccount'      => $params['creditAccount'],
            'creditCurrency'     => $params['creditCurrency'] ?? 'TZS',
            'debitAccount'       => $params['debitAccount'],
            'debitCurrency'      => $params['debitCurrency'] ?? 'TZS',
            'paymentType'        => $params['paymentType'] ?? 'ACCOUNT',
            'channelCode'        => $params['channelCode'] ?? 'APP',
            'payerName'          => $params['payerName'],
            'payerPhone'         => $params['payerPhone'],
            'payerEmail'         => $params['payerEmail'],
            'narration'          => $params['narration'] ?? 'Bills Payment',
            'extraFields'        => $params['extraFields'] ?? new \stdClass(),
            'inquiryRawResponse' => $params['inquiryRawResponse'] ?? '',
        ];

        Log::info('Generating digital signature for payment request');
        $digitalSignature = $this->generateDigitalSignature($payload);

        $headers = [
            'Content-Type'      => 'application/json',
            'Accept'            => 'application/json',
            'NBC-Authorization' => 'Basic c2FjY29zaXNhbGU6QE5CQ3NhY2Nvc2lzYWxlTHRk', 
            'Digital-Signature' => $digitalSignature,
            'Timestamp'         => $timestamp,
        ];

        $url = "{$this->baseUrl}/bills-payments-engine/api/v1/payment";

        Log::info('Sending async payment request to NBC', [
            'url'     => $url,
            'headers' => $headers,
            'payload' => $payload,
        ]);

        $response = Http::withHeaders($headers)->post($url, $payload);

        Log::info('NBC async payment response', [
            'status' => $response->status(),
            'body'   => $response->body(),
        ]);

        if ($response->successful()) {
            $data = $response->json();

            if ($data['statusCode'] === '600') {
                return [
                    'status'      => 'processing',
                    'gatewayRef'  => $data['gatewayRef'] ?? null,
                    'message'     => $data['message'],
                    'channelRef'  => $channelRef,
                    'timestamp'   => $timestamp,
                ];
            }

            throw new \Exception("API Error: {$data['message']}");
        }

        throw new \Exception("HTTP Error: {$response->status()}");

    } catch (\Exception $e) {
        Log::error("NBC Payment Processing Error: " . $e->getMessage());
        return [
            'status'  => 'error',
            'message' => $e->getMessage(),
        ];
    }
}




public function checkPaymentStatus(array $data): array
{
    // Timestamp format e.g., "2023-03-07T12:29:50.968"
    $timestamp = now()->format('Y-m-d\TH:i:s.v');
$channelRef = '20230512103230499';

    // Prepare request payload
    $payload = [
        'channelId' => 'SACCOSAPP',
        'spCode'      => $data['spCode'] ?? 'BPE0001000BC',
        'requestType' => 'statusCheck',
        'timestamp'   => $timestamp,
        'channelRef'  => $channelRef,
        'billRef'     => $data['billRef'],
        'extraFields' => (object) ($data['extraFields'] ?? []),
    ];

    // Log digital signature generation
    Log::info('Generating digital signature for status check');
  
        $digitalSignature = $this->generateDigitalSignature($payload);

        $headers = [
            'Content-Type'      => 'application/json',
            'Accept'            => 'application/json',
            'NBC-Authorization' => 'Basic c2FjY29zaXNhbGU6QE5CQ3NhY2Nvc2lzYWxlTHRk', 
            'Digital-Signature' => $digitalSignature,
            'Timestamp'         => $timestamp,
        ];

        $url = "{$this->baseUrl}/bills-payments-engine/api/v1/status-check";

        Log::info('Sending ststus to NBC', [
            'url'     => $url,
            'headers' => $headers,
            'payload' => $payload,
        ]);

        



    try {
        $response = Http::withHeaders($headers)->post($url, $payload);

        if ($response->successful()) {
            return [
                'status' => 'success',
                'data'   => $response->json(),
            ];
        } else {
            return [
                'status'  => 'error',
                'code'    => $response->json()['statusCode'] ?? 0,
                'message' => $response->json()['message'] ?? 'Unknown error from API',
            ];
        }
    } catch (\Exception $e) {
        Log::error('NBC Status Check failed', [
            'error'   => $e->getMessage(),
            'payload' => $payload,
        ]);

        return [
            'status'  => 'error',
            'message' => 'Exception: ' . $e->getMessage(),
        ];
    }
}





    public function getPublicKeyPem(): string
    {
        return Storage::get('keys/public_key.pem');
    }
}
