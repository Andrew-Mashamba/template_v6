<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Internal Funds Transfer (IFT) Service
 * Handles transfers between SACCOS accounts and member accounts within NBC Bank
 */
class InternalFundsTransferService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $clientId;
    protected array $transactionLog = [];

    public function __construct()
    {
        $this->baseUrl = config('services.nbc_payments.base_url');
        $this->apiKey = config('services.nbc_payments.api_key');
        $this->clientId = config('services.nbc_payments.client_id');
        
        $this->logInfo('IFT Service initialized', [
            'base_url' => $this->baseUrl,
            'client_id' => $this->clientId
        ]);
    }

    /**
     * Perform account lookup before transfer
     * 
     * @param string $accountNumber
     * @param string $accountType 'source' or 'destination'
     * @return array
     */
    public function lookupAccount(string $accountNumber, string $accountType = 'destination'): array
    {
        $startTime = microtime(true);
        $this->logInfo("Starting account lookup", [
            'account' => $accountNumber,
            'type' => $accountType
        ]);

        try {
            // Validate account format
            if (!$this->validateAccountNumber($accountNumber)) {
                throw new Exception("Invalid account number format");
            }

            $payload = [
                'accountNumber' => $accountNumber,
                'accountType' => 'CASA',
                'verificationPurpose' => 'IFT',
                'clientRef' => $this->generateReference('LOOKUP')
            ];

            $response = $this->sendRequest('/api/nbc/account/verify', $payload);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response['success']) {
                $this->logInfo("Account lookup successful", [
                    'account' => $accountNumber,
                    'name' => $response['data']['accountName'] ?? 'N/A',
                    'status' => $response['data']['status'] ?? 'N/A',
                    'duration_ms' => $duration
                ]);

                return [
                    'success' => true,
                    'account_number' => $accountNumber,
                    'account_name' => $response['data']['accountName'] ?? '',
                    'account_status' => $response['data']['status'] ?? '',
                    'branch_code' => $response['data']['branchCode'] ?? '',
                    'currency' => $response['data']['currency'] ?? 'TZS',
                    'can_receive' => $response['data']['canReceiveFunds'] ?? false,
                    'can_debit' => $response['data']['canDebit'] ?? false,
                    'response_time' => $duration
                ];
            }

            throw new Exception($response['message'] ?? 'Account lookup failed');

        } catch (Exception $e) {
            $this->logError("Account lookup failed", [
                'account' => $accountNumber,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'account_number' => $accountNumber
            ];
        }
    }

    /**
     * Perform Internal Funds Transfer
     * 
     * @param array $transferData
     * @return array
     */
    public function transfer(array $transferData): array
    {
        $startTime = microtime(true);
        $reference = $this->generateReference('IFT');
        
        $this->logInfo("Starting IFT transfer", [
            'reference' => $reference,
            'from_account' => $transferData['from_account'],
            'to_account' => $transferData['to_account'],
            'amount' => $transferData['amount']
        ]);

        try {
            // Validate required fields
            $this->validateTransferData($transferData);

            // Step 1: Lookup source account
            $sourceAccount = $this->lookupAccount($transferData['from_account'], 'source');
            if (!$sourceAccount['success']) {
                throw new Exception("Source account verification failed: " . $sourceAccount['error']);
            }

            if (!$sourceAccount['can_debit']) {
                throw new Exception("Source account cannot be debited");
            }

            // Step 2: Lookup destination account
            $destAccount = $this->lookupAccount($transferData['to_account'], 'destination');
            if (!$destAccount['success']) {
                throw new Exception("Destination account verification failed: " . $destAccount['error']);
            }

            if (!$destAccount['can_receive']) {
                throw new Exception("Destination account cannot receive funds");
            }

            // Step 3: Prepare transfer payload
            $payload = [
                'transferType' => 'IFT',
                'clientRef' => $reference,
                'fromAccount' => [
                    'accountNumber' => $transferData['from_account'],
                    'accountName' => $sourceAccount['account_name'],
                    'branchCode' => $sourceAccount['branch_code'],
                    'currency' => $sourceAccount['currency']
                ],
                'toAccount' => [
                    'accountNumber' => $transferData['to_account'],
                    'accountName' => $destAccount['account_name'],
                    'branchCode' => $destAccount['branch_code'],
                    'currency' => $destAccount['currency']
                ],
                'amount' => $transferData['amount'],
                'currency' => 'TZS',
                'narration' => $transferData['narration'] ?? 'Internal Funds Transfer',
                'chargeBearer' => $transferData['charge_bearer'] ?? 'OUR',
                'purposeCode' => $transferData['purpose_code'] ?? 'CASH',
                'timestamp' => Carbon::now()->toIso8601String()
            ];

            // Step 4: Execute transfer
            $response = $this->sendRequest('/api/nbc/ift/transfer', $payload);
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($response['success']) {
                // Save transaction to database
                $this->saveTransaction([
                    'reference' => $reference,
                    'type' => 'IFT',
                    'from_account' => $transferData['from_account'],
                    'to_account' => $transferData['to_account'],
                    'amount' => $transferData['amount'],
                    'status' => 'SUCCESS',
                    'response_code' => $response['data']['responseCode'] ?? '',
                    'response_message' => $response['data']['message'] ?? '',
                    'nbc_reference' => $response['data']['nbcReference'] ?? '',
                    'duration_ms' => $duration
                ]);

                $this->logInfo("IFT transfer successful", [
                    'reference' => $reference,
                    'nbc_reference' => $response['data']['nbcReference'] ?? '',
                    'duration_ms' => $duration
                ]);

                return [
                    'success' => true,
                    'reference' => $reference,
                    'nbc_reference' => $response['data']['nbcReference'] ?? '',
                    'message' => 'Transfer completed successfully',
                    'from_account' => $transferData['from_account'],
                    'to_account' => $transferData['to_account'],
                    'amount' => $transferData['amount'],
                    'timestamp' => Carbon::now()->toIso8601String(),
                    'response_time' => $duration
                ];
            }

            throw new Exception($response['message'] ?? 'Transfer failed');

        } catch (Exception $e) {
            $this->logError("IFT transfer failed", [
                'reference' => $reference,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            // Save failed transaction
            $this->saveTransaction([
                'reference' => $reference,
                'type' => 'IFT',
                'from_account' => $transferData['from_account'] ?? '',
                'to_account' => $transferData['to_account'] ?? '',
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
     * Get transfer status
     * 
     * @param string $reference
     * @return array
     */
    public function getTransferStatus(string $reference): array
    {
        $this->logInfo("Checking transfer status", ['reference' => $reference]);

        try {
            $payload = [
                'clientRef' => $reference,
                'transferType' => 'IFT'
            ];

            $response = $this->sendRequest('/api/nbc/ift/status', $payload);

            if ($response['success']) {
                return [
                    'success' => true,
                    'reference' => $reference,
                    'status' => $response['data']['status'] ?? 'UNKNOWN',
                    'message' => $response['data']['message'] ?? '',
                    'timestamp' => $response['data']['timestamp'] ?? ''
                ];
            }

            throw new Exception($response['message'] ?? 'Status check failed');

        } catch (Exception $e) {
            $this->logError("Status check failed", [
                'reference' => $reference,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'reference' => $reference,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Validate account number format
     * 
     * @param string $accountNumber
     * @return bool
     */
    protected function validateAccountNumber(string $accountNumber): bool
    {
        // NBC account format validation
        if (strlen($accountNumber) < 10 || strlen($accountNumber) > 16) {
            return false;
        }

        if (!is_numeric($accountNumber)) {
            return false;
        }

        return true;
    }

    /**
     * Validate transfer data
     * 
     * @param array $data
     * @throws Exception
     */
    protected function validateTransferData(array $data): void
    {
        $required = ['from_account', 'to_account', 'amount'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new Exception("Invalid amount");
        }

        if ($data['from_account'] === $data['to_account']) {
            throw new Exception("Source and destination accounts cannot be the same");
        }
    }

    /**
     * Send HTTP request to NBC API
     * 
     * @param string $endpoint
     * @param array $payload
     * @return array
     */
    protected function sendRequest(string $endpoint, array $payload): array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            
            $this->logDebug("Sending request", [
                'url' => $url,
                'payload' => $payload
            ]);

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Api-Key' => $this->apiKey,
                'Client-Id' => $this->clientId,
                'Service-Name' => 'IFT'
            ])->withOptions(['verify' => false])
              ->timeout(30)
              ->post($url, $payload);

            $statusCode = $response->status();
            $responseData = $response->json() ?? [];

            $this->logDebug("Response received", [
                'status_code' => $statusCode,
                'response' => $responseData
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
     * 
     * @param string $prefix
     * @return string
     */
    protected function generateReference(string $prefix = 'IFT'): string
    {
        // NBC API requires alphanumeric clientRef only (no underscores or special chars)
        return $prefix . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    /**
     * Save transaction to database
     * 
     * @param array $data
     */
    protected function saveTransaction(array $data): void
    {
        try {
            DB::table('payment_transactions')->insert([
                'reference' => $data['reference'],
                'type' => $data['type'],
                'from_account' => $data['from_account'],
                'to_account' => $data['to_account'],
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
                'data' => $data
            ]);
        }
    }

    /**
     * Log information
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel('payments')->info("[IFT] {$message}", $context);
    }

    /**
     * Log error
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::channel('payments')->error("[IFT] {$message}", $context);
    }

    /**
     * Log debug information
     */
    protected function logDebug(string $message, array $context = []): void
    {
        Log::channel('payments')->debug("[IFT] {$message}", $context);
    }
}