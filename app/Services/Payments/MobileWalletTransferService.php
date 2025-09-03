<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Mobile Wallet Transfer Service
 * Handles transfers from SACCOS NBC account to mobile wallets
 * Via TIPS only (amounts must be below 20,000,000 TZS)
 */
class MobileWalletTransferService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $clientId;
    protected string $privateKeyPath;
    
    const MAX_AMOUNT = 20000000; // 20 million TZS
    
    // Wallet provider codes
    const PROVIDERS = [
        'MPESA' => 'VMCASHIN',
        'TIGOPESA' => 'TPCASHIN',
        'AIRTELMONEY' => 'AIRTELMONEYCASHIN',
        'HALOPESA' => 'HALOPESACASHIN',
        'EZYPESA' => 'EZYPESACASHIN'
    ];

    public function __construct()
    {
        $this->baseUrl = config('services.nbc_payments.base_url');
        $this->apiKey = config('services.nbc_payments.api_key');
        $this->clientId = config('services.nbc_payments.client_id');
        $this->privateKeyPath = storage_path('app/keys/private_key.pem');
        
        $this->logInfo('Mobile Wallet Transfer Service initialized', [
            'base_url' => $this->baseUrl,
            'client_id' => $this->clientId,
            'max_amount' => self::MAX_AMOUNT
        ]);
    }

    /**
     * Lookup mobile wallet before transfer
     * 
     * @param string $phoneNumber
     * @param string $provider
     * @param float $amount
     * @return array
     */
    public function lookupWallet(string $phoneNumber, string $provider, float $amount = 1): array
    {
        $startTime = microtime(true);
        
        // Normalize phone number
        $phoneNumber = $this->normalizePhoneNumber($phoneNumber);
        
        $this->logInfo("Starting wallet lookup", [
            'phone' => $this->maskPhoneNumber($phoneNumber),
            'provider' => $provider
        ]);

        try {
            // Validate provider
            if (!isset(self::PROVIDERS[$provider])) {
                throw new Exception("Invalid wallet provider: {$provider}");
            }

            $providerCode = self::PROVIDERS[$provider];
            $lookupRef = $this->generateReference('LOOKUPW');
            $debitAccount = config('services.nbc_payments.saccos_account', '06012040022');
            
            // For MSISDN lookup, use phone without country code
            $lookupPhone = $this->getPhoneWithoutCountryCode($phoneNumber);

            $payload = [
                'serviceName' => 'TIPS_LOOKUP',
                'clientId' => $this->clientId,
                'clientRef' => $lookupRef,
                'identifierType' => 'MSISDN',
                'identifier' => $lookupPhone,  // Without country code for lookup
                'destinationFsp' => $providerCode,
                'debitAccount' => $debitAccount,
                'debitAccountCurrency' => 'TZS',
                'debitAccountBranchCode' => substr($debitAccount, 0, 3),
                'amount' => (string)$amount,
                'debitAccountCategory' => 'BUSINESS'
            ];

            // Generate UUID for tracing
            $uuid = $this->generateUUID();
            
            // Use exact headers from working curl command
            $response = $this->sendRequest('/domestix/api/v2/lookup', $payload, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Trace-Uuid' => 'domestix-' . $uuid,
                'x-api-key' => $this->apiKey,  // lowercase as in working curl
                'Client-Id' => $this->clientId,
                'Service-Name' => 'TIPS_LOOKUP'
                // Note: No Signature or Timestamp headers - they're not needed
            ]);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response['success']) {
                $this->logInfo("Wallet lookup successful", [
                    'phone' => $this->maskPhoneNumber($phoneNumber),
                    'provider' => $provider,
                    'name' => $response['data']['accountName'] ?? 'N/A',
                    'engineRef' => $response['data']['engineRef'] ?? 'N/A',
                    'duration_ms' => $duration
                ]);

                return [
                    'success' => true,
                    'phone_number' => $phoneNumber,
                    'provider' => $provider,
                    'provider_code' => $providerCode,
                    'account_name' => $response['data']['accountName'] ?? '',
                    'engine_ref' => $response['data']['engineRef'] ?? null,
                    'wallet_status' => $response['data']['status'] ?? 'ACTIVE',
                    'can_receive' => true,
                    'response_time' => $duration
                ];
            }

            throw new Exception($response['message'] ?? 'Wallet lookup failed');

        } catch (Exception $e) {
            $this->logError("Wallet lookup failed", [
                'phone' => $this->maskPhoneNumber($phoneNumber),
                'provider' => $provider,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'phone_number' => $phoneNumber,
                'provider' => $provider
            ];
        }
    }

    /**
     * Transfer funds to mobile wallet
     * 
     * @param array $transferData
     * @return array
     */
    public function transfer(array $transferData): array
    {
        $startTime = microtime(true);
        $reference = $this->generateReference('WALLET');
        
        $this->logInfo("Starting wallet transfer", [
            'reference' => $reference,
            'from_account' => $transferData['from_account'],
            'to_phone' => $this->maskPhoneNumber($transferData['phone_number'] ?? ''),
            'provider' => $transferData['provider'] ?? '',
            'amount' => $transferData['amount']
        ]);

        try {
            // Validate transfer data
            $this->validateTransferData($transferData);
            
            // Check amount limit
            if ($transferData['amount'] >= self::MAX_AMOUNT) {
                throw new Exception("Amount exceeds maximum limit of " . number_format(self::MAX_AMOUNT) . " TZS for mobile wallet transfers");
            }

            // Normalize phone number
            $phoneNumber = $this->normalizePhoneNumber($transferData['phone_number']);

            // Step 1: Lookup source account (NBC account)
            $sourceAccount = $this->lookupSourceAccount($transferData['from_account']);
            if (!$sourceAccount['success']) {
                throw new Exception("Source account verification failed: " . $sourceAccount['error']);
            }

            // Step 2: Lookup wallet
            $walletAccount = $this->lookupWallet($phoneNumber, $transferData['provider'], $transferData['amount']);
            if (!$walletAccount['success']) {
                throw new Exception("Wallet verification failed: " . $walletAccount['error']);
            }
            
            // Get lookup reference and ensure it's alphanumeric
            $lookupRef = isset($walletAccount['engine_ref']) ? 
                $this->toAlphanumeric($walletAccount['engine_ref']) : 
                $this->generateReference('LOOKUPREF');
            
            // Generate customer reference and initiator ID
            $timestamp = time();
            $customerRef = 'CUSTOMERREF' . $timestamp;
            $initiatorId = (string)$timestamp;

            // Step 3: Execute transfer with correct structure
            $payload = [
                'serviceName' => 'TIPS_B2W_TRANSFER',
                'clientId' => $this->clientId,
                'clientRef' => $reference,
                'customerRef' => $customerRef,
                'lookupRef' => $lookupRef,
                'timestamp' => Carbon::now()->toIso8601String(),
                'callbackUrl' => config('services.nbc_payments.callback_url', 'http://localhost:90/post'),
                
                'payerDetails' => [
                    'identifierType' => 'BANK',
                    'identifier' => $transferData['from_account'],
                    'phoneNumber' => $transferData['payer_phone'] ?? '255715000001',
                    'initiatorId' => $initiatorId,
                    'branchCode' => substr($transferData['from_account'], 0, 3),
                    'fspId' => substr($transferData['from_account'], 0, 3),
                    'fullName' => $sourceAccount['account_name'] ?? 'SACCOS Account',
                    'accountCategory' => 'BUSINESS',
                    'accountType' => 'BANK',
                    'identity' => [
                        'type' => '',
                        'value' => ''
                    ]
                ],
                
                'payeeDetails' => [
                    'identifierType' => 'MSISDN',
                    'identifier' => $this->getPhoneWithoutCountryCode($phoneNumber), // Local format for wallet
                    'fspId' => $this->getProviderFspId($transferData['provider']),
                    'destinationFsp' => $walletAccount['provider_code'],
                    'fullName' => $walletAccount['account_name'] ?? 'Wallet User',
                    'accountCategory' => 'PERSON',
                    'accountType' => 'WALLET',
                    'identity' => [
                        'type' => '',
                        'value' => ''
                    ]
                ],
                
                'transactionDetails' => [
                    'debitAmount' => (string)$transferData['amount'],
                    'debitCurrency' => 'TZS',
                    'creditAmount' => (string)$transferData['amount'],
                    'creditCurrency' => 'TZS',
                    'productCode' => '',
                    'isServiceChargeApplicable' => true,
                    'serviceChargeBearer' => $transferData['charge_bearer'] ?? 'OUR'
                ],
                
                'remarks' => $transferData['narration'] ?? "Transfer to {$transferData['provider']} wallet"
            ];

            // Use exact headers from working curl command
            $response = $this->sendRequest('/domestix/api/v2/outgoing-transfers', $payload, [
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'X-Trace-Uuid' => 'domestix-' . $this->generateUUID(),
                'x-api-key' => $this->apiKey  // lowercase as in working curl
                // Note: No Signature header - it's not needed
            ]);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($response['success']) {
                // Save transaction
                $this->saveTransaction([
                    'reference' => $reference,
                    'type' => 'WALLET_TRANSFER',
                    'from_account' => $transferData['from_account'],
                    'to_wallet' => $phoneNumber,
                    'provider' => $transferData['provider'],
                    'amount' => $transferData['amount'],
                    'status' => 'SUCCESS',
                    'response_code' => $response['data']['responseCode'] ?? '',
                    'response_message' => $response['data']['message'] ?? '',
                    'nbc_reference' => $response['data']['nbcReference'] ?? '',
                    'duration_ms' => $duration
                ]);

                $this->logInfo("Wallet transfer successful", [
                    'reference' => $reference,
                    'nbc_reference' => $response['data']['nbcReference'] ?? '',
                    'duration_ms' => $duration
                ]);

                return [
                    'success' => true,
                    'reference' => $reference,
                    'nbc_reference' => $response['data']['nbcReference'] ?? '',
                    'message' => "Transfer to {$transferData['provider']} wallet successful",
                    'from_account' => $transferData['from_account'],
                    'to_phone' => $this->maskPhoneNumber($phoneNumber),
                    'provider' => $transferData['provider'],
                    'amount' => $transferData['amount'],
                    'timestamp' => Carbon::now()->toIso8601String(),
                    'response_time' => $duration
                ];
            }

            throw new Exception($response['message'] ?? 'Transfer failed');

        } catch (Exception $e) {
            $this->logError("Wallet transfer failed", [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            // Save failed transaction
            $this->saveTransaction([
                'reference' => $reference,
                'type' => 'WALLET_TRANSFER',
                'from_account' => $transferData['from_account'] ?? '',
                'to_wallet' => $transferData['phone_number'] ?? '',
                'provider' => $transferData['provider'] ?? '',
                'amount' => $transferData['amount'] ?? 0,
                'status' => 'FAILED',
                'error_message' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            return [
                'success' => false,
                'reference' => $reference,
                'error' => $e->getMessage(),
                'timestamp' => Carbon::now()->toIso8601String()
            ];
        }
    }

    /**
     * Get available wallet providers
     * 
     * @return array
     */
    public function getProviders(): array
    {
        return array_keys(self::PROVIDERS);
    }

    /**
     * Normalize phone number to E.164 format
     * 
     * @param string $phoneNumber
     * @return string
     */
    protected function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // Handle different formats
        if (substr($phoneNumber, 0, 3) === '255') {
            // Already has country code
            return $phoneNumber;
        } elseif (substr($phoneNumber, 0, 1) === '0') {
            // Remove leading zero and add country code
            return '255' . substr($phoneNumber, 1);
        } elseif (strlen($phoneNumber) === 9) {
            // Missing country code
            return '255' . $phoneNumber;
        }
        
        return $phoneNumber;
    }

    /**
     * Mask phone number for logging
     * 
     * @param string $phoneNumber
     * @return string
     */
    protected function maskPhoneNumber(string $phoneNumber): string
    {
        if (strlen($phoneNumber) > 6) {
            return substr($phoneNumber, 0, 6) . '****' . substr($phoneNumber, -2);
        }
        return $phoneNumber;
    }

    /**
     * Lookup source account
     */
    protected function lookupSourceAccount(string $accountNumber): array
    {
        try {
            $payload = [
                'accountNumber' => $accountNumber,
                'accountType' => 'CASA',
                'verificationPurpose' => 'WALLET_TRANSFER'
            ];

            $response = $this->sendRequest('/api/nbc/account/verify', $payload);
            
            if ($response['success']) {
                return [
                    'success' => true,
                    'account_number' => $accountNumber,
                    'account_name' => $response['data']['accountName'] ?? '',
                    'can_debit' => $response['data']['canDebit'] ?? false
                ];
            }

            return [
                'success' => false,
                'error' => $response['message'] ?? 'Account verification failed'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate transfer data
     */
    protected function validateTransferData(array $data): void
    {
        $required = ['from_account', 'phone_number', 'provider', 'amount'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new Exception("Invalid amount");
        }

        // Validate phone number format
        $phone = $this->normalizePhoneNumber($data['phone_number']);
        if (strlen($phone) !== 12 || substr($phone, 0, 3) !== '255') {
            throw new Exception("Invalid phone number format");
        }

        // Validate provider
        if (!isset(self::PROVIDERS[$data['provider']])) {
            throw new Exception("Invalid wallet provider. Available providers: " . implode(', ', array_keys(self::PROVIDERS)));
        }
    }

    /**
     * Generate digital signature
     */
    protected function generateSignature(array $payload): string
    {
        try {
            if (!file_exists($this->privateKeyPath)) {
                throw new Exception("Private key file not found");
            }

            $privateKeyContent = file_get_contents($this->privateKeyPath);
            $privateKey = openssl_pkey_get_private($privateKeyContent);

            if (!$privateKey) {
                throw new Exception("Failed to load private key");
            }

            $jsonPayload = json_encode($payload);
            openssl_sign($jsonPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256);

            return base64_encode($signature);

        } catch (Exception $e) {
            $this->logError("Signature generation failed", ['error' => $e->getMessage()]);
            throw new Exception("Failed to generate digital signature: " . $e->getMessage());
        }
    }

    /**
     * Send HTTP request
     */
    protected function sendRequest(string $endpoint, array $payload, array $additionalHeaders = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            
            // Generate UUID if not provided in additional headers
            if (!isset($additionalHeaders['X-Trace-Uuid'])) {
                $additionalHeaders['X-Trace-Uuid'] = 'domestix-' . $this->generateUUID();
            }
            
            // Use only the headers that are proven to work
            $headers = array_merge([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json'
            ], $additionalHeaders);

            $this->logDebug("Sending wallet transfer request", [
                'url' => $url,
                'headers' => array_keys($headers)
            ]);

            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => false])
                ->timeout(30)
                ->post($url, $payload);

            $statusCode = $response->status();
            $responseData = $response->json() ?? [];

            $this->logDebug("Wallet transfer response received", [
                'status_code' => $statusCode,
                'has_data' => !empty($responseData)
            ]);

            if ($statusCode === 200 || $statusCode === 201) {
                return [
                    'success' => true,
                    'data' => $responseData
                ];
            }

            return [
                'success' => false,
                'message' => $responseData['message'] ?? "Request failed with status {$statusCode}"
            ];

        } catch (Exception $e) {
            $this->logError("Request failed", [
                'endpoint' => $endpoint,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Generate unique reference
     */
    protected function generateReference(string $prefix = 'WALLET'): string
    {
        // NBC API requires alphanumeric clientRef only (no underscores or special chars)
        return $prefix . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    /**
     * Get phone number without country code
     * 
     * @param string $phoneNumber
     * @return string
     */
    protected function getPhoneWithoutCountryCode(string $phoneNumber): string
    {
        // Remove all non-numeric characters
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);
        
        // If starts with 255, remove it and add 0
        if (substr($phoneNumber, 0, 3) === '255') {
            return '0' . substr($phoneNumber, 3);
        }
        
        // If already starts with 0, return as is
        if (substr($phoneNumber, 0, 1) === '0') {
            return $phoneNumber;
        }
        
        // If 9 digits, add 0 prefix
        if (strlen($phoneNumber) === 9) {
            return '0' . $phoneNumber;
        }
        
        return $phoneNumber;
    }
    
    /**
     * Convert string to alphanumeric only
     */
    protected function toAlphanumeric(string $str): string
    {
        return preg_replace('/[^A-Za-z0-9]/', '', $str);
    }
    
    /**
     * Generate UUID
     */
    protected function generateUUID(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
    
    /**
     * Get FSP ID for provider
     */
    protected function getProviderFspId(string $provider): string
    {
        $fspIds = [
            'MPESA' => '504',
            'TIGOPESA' => '505',
            'AIRTELMONEY' => '506',
            'HALOPESA' => '507',
            'EZYPESA' => '508'
        ];
        
        return $fspIds[$provider] ?? '504';
    }

    /**
     * Save transaction to database
     */
    protected function saveTransaction(array $data): void
    {
        try {
            DB::table('payment_transactions')->insert([
                'reference' => $data['reference'],
                'type' => $data['type'],
                'from_account' => $data['from_account'],
                'to_wallet' => $data['to_wallet'] ?? null,
                'provider' => $data['provider'] ?? null,
                'amount' => $data['amount'],
                'status' => $data['status'],
                'response_code' => $data['response_code'] ?? null,
                'response_message' => $data['response_message'] ?? null,
                'nbc_reference' => $data['nbc_reference'] ?? null,
                'error_message' => $data['error_message'] ?? null,
                'duration_ms' => $data['duration_ms'] ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (Exception $e) {
            $this->logError("Failed to save transaction", [
                'error' => $e->getMessage(),
                'reference' => $data['reference']
            ]);
        }
    }

    /**
     * Log information
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel('payments')->info("[WALLET] {$message}", $context);
    }

    /**
     * Log error
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::channel('payments')->error("[WALLET] {$message}", $context);
    }

    /**
     * Log debug
     */
    protected function logDebug(string $message, array $context = []): void
    {
        Log::channel('payments')->debug("[WALLET] {$message}", $context);
    }
}