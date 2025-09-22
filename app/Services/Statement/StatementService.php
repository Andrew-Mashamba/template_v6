<?php

namespace App\Services\Statement;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Exception;

/**
 * NBC Partners Values Added Services (PVAS) - Statement Service
 * 
 * Handles integration with NBC Statement APIs for:
 * - Account Balance (SC990001)
 * - Transaction Summary (SC990002)
 * - Account Statement (SC990003)
 * 
 * @version v1.2
 * @author NBC Development Team
 */
class StatementService
{
    protected string $baseUrl;
    protected string $username;
    protected string $password;
    protected string $privateKeyPath;
    protected string $nbcPublicKeyPath;
    protected ?string $token = null;
    protected ?int $tokenExpiry = null;
    
    // Service codes
    const SERVICE_BALANCE = 'SC990001';
    const SERVICE_SUMMARY = 'SC990002';
    const SERVICE_STATEMENT = 'SC990003';
    
    // Response codes
    const CODE_SUCCESS = 600;
    const CODE_FAILED = 601;
    const CODE_SIGNATURE_FAILURE = 602;
    const CODE_UNAUTHORIZED = 613;
    const CODE_AUTH_FAILED = 615;
    const CODE_EXCEPTION = 699;

    public function __construct()
    {
        $this->baseUrl = config('services.nbc_statement.base_url');
        $this->username = config('services.nbc_statement.username');
        $this->password = config('services.nbc_statement.password');
        $this->privateKeyPath = storage_path('keys/partner_private_key.pem');
        $this->nbcPublicKeyPath = storage_path('keys/nbc_public_key.pem');
        
        $this->logInfo('Statement Service initialized', [
            'base_url' => $this->baseUrl,
            'username' => $this->username ? substr($this->username, 0, 3) . '***' : 'Not configured'
        ]);
    }

    /**
     * Get Account Balance (SC990001)
     * 
     * @param string $accountNumber
     * @param string $statementDate Format: YYYY-MM-DD
     * @param string $partnerRef Unique partner reference
     * @return array
     */
    public function getAccountBalance(string $accountNumber, string $statementDate, string $partnerRef = null): array
    {
        $startTime = microtime(true);
        $partnerRef = $partnerRef ?? $this->generatePartnerRef('BAL');
        
        $this->logInfo("Getting account balance", [
            'account' => $this->maskAccount($accountNumber),
            'date' => $statementDate,
            'partner_ref' => $partnerRef
        ]);

        try {
            // Ensure we have valid token
            $this->ensureAuthenticated();
            
            // Prepare request
            $payload = [
                'timestamp' => Carbon::now()->toIso8601String(),
                'serviceCode' => self::SERVICE_BALANCE,
                'partnerRef' => $partnerRef,
                'accountNumber' => $accountNumber,
                'statementDate' => $statementDate
            ];
            
            // Send request
            $response = $this->sendSignedRequest('/api/v1/casa/balance', $payload);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response['statusCode'] === self::CODE_SUCCESS) {
                $this->logInfo("Balance retrieved successfully", [
                    'partner_ref' => $partnerRef,
                    'bank_ref' => $response['bankRef'] ?? null,
                    'duration_ms' => $duration
                ]);
                
                return [
                    'success' => true,
                    'partner_ref' => $partnerRef,
                    'bank_ref' => $response['bankRef'] ?? null,
                    'data' => $response['data'] ?? [],
                    'response_time' => $duration
                ];
            }
            
            throw new Exception($response['message'] ?? 'Failed to get balance');
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logError("Failed to get balance", [
                'partner_ref' => $partnerRef,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            
            return [
                'success' => false,
                'partner_ref' => $partnerRef,
                'error' => $e->getMessage(),
                'response_time' => $duration
            ];
        }
    }

    /**
     * Get Transaction Summary (SC990002)
     * 
     * @param string $accountNumber
     * @param string $statementDate Format: YYYY-MM-DD
     * @param string $partnerRef Unique partner reference
     * @return array
     */
    public function getTransactionSummary(string $accountNumber, string $statementDate, string $partnerRef = null): array
    {
        $startTime = microtime(true);
        $partnerRef = $partnerRef ?? $this->generatePartnerRef('SUM');
        
        $this->logInfo("Getting transaction summary", [
            'account' => $this->maskAccount($accountNumber),
            'date' => $statementDate,
            'partner_ref' => $partnerRef
        ]);

        try {
            // Ensure we have valid token
            $this->ensureAuthenticated();
            
            // Prepare request
            $payload = [
                'timestamp' => Carbon::now()->toIso8601String(),
                'serviceCode' => self::SERVICE_SUMMARY,
                'partnerRef' => $partnerRef,
                'accountNumber' => $accountNumber,
                'statementDate' => $statementDate
            ];
            
            // Send request
            $response = $this->sendSignedRequest('/api/v1/casa/summary', $payload);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response['statusCode'] === self::CODE_SUCCESS) {
                $this->logInfo("Transaction summary retrieved successfully", [
                    'partner_ref' => $partnerRef,
                    'bank_ref' => $response['bankRef'] ?? null,
                    'duration_ms' => $duration
                ]);
                
                return [
                    'success' => true,
                    'partner_ref' => $partnerRef,
                    'bank_ref' => $response['bankRef'] ?? null,
                    'data' => $response['data'] ?? [],
                    'response_time' => $duration
                ];
            }
            
            throw new Exception($response['message'] ?? 'Failed to get transaction summary');
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logError("Failed to get transaction summary", [
                'partner_ref' => $partnerRef,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            
            return [
                'success' => false,
                'partner_ref' => $partnerRef,
                'error' => $e->getMessage(),
                'response_time' => $duration
            ];
        }
    }

    /**
     * Get Account Statement (SC990003)
     * 
     * @param string $accountNumber
     * @param string $statementDate Format: YYYY-MM-DD
     * @param string $partnerRef Unique partner reference
     * @return array
     */
    public function getAccountStatement(string $accountNumber, string $statementDate, string $partnerRef = null): array
    {
        $startTime = microtime(true);
        $partnerRef = $partnerRef ?? $this->generatePartnerRef('STMT');
        
        $this->logInfo("Getting account statement", [
            'account' => $this->maskAccount($accountNumber),
            'date' => $statementDate,
            'partner_ref' => $partnerRef
        ]);

        try {
            // Ensure we have valid token
            $this->ensureAuthenticated();
            
            // Prepare request
            $payload = [
                'timestamp' => Carbon::now()->toIso8601String(),
                'serviceCode' => self::SERVICE_STATEMENT,
                'partnerRef' => $partnerRef,
                'accountNumber' => $accountNumber,
                'statementDate' => $statementDate
            ];
            
            // Send request
            $response = $this->sendSignedRequest('/api/v1/casa/statement', $payload);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response['statusCode'] === self::CODE_SUCCESS) {
                $transactions = $response['data']['transactions'] ?? [];
                
                $this->logInfo("Statement retrieved successfully", [
                    'partner_ref' => $partnerRef,
                    'bank_ref' => $response['bankRef'] ?? null,
                    'transaction_count' => count($transactions),
                    'duration_ms' => $duration
                ]);
                
                return [
                    'success' => true,
                    'partner_ref' => $partnerRef,
                    'bank_ref' => $response['bankRef'] ?? null,
                    'transactions' => $transactions,
                    'response_time' => $duration
                ];
            }
            
            throw new Exception($response['message'] ?? 'Failed to get statement');
            
        } catch (Exception $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            $this->logError("Failed to get statement", [
                'partner_ref' => $partnerRef,
                'error' => $e->getMessage(),
                'duration_ms' => $duration
            ]);
            
            return [
                'success' => false,
                'partner_ref' => $partnerRef,
                'error' => $e->getMessage(),
                'response_time' => $duration
            ];
        }
    }

    /**
     * Authenticate and get JWT token
     * 
     * @return bool
     * @throws Exception
     */
    protected function authenticate(): bool
    {
        $this->logInfo("Authenticating with NBC API");
        
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])
            ->withOptions(['verify' => false])
            ->timeout(30)
            ->post($this->baseUrl . '/api/auth/login', [
                'username' => $this->username,
                'password' => $this->password
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                $this->token = $data['token'] ?? null;
                $this->tokenExpiry = time() + (($data['expiry'] ?? 86400000) / 1000); // Convert ms to seconds
                
                // Cache token
                Cache::put('nbc_statement_token', $this->token, $this->tokenExpiry - time());
                Cache::put('nbc_statement_token_expiry', $this->tokenExpiry, $this->tokenExpiry - time());
                
                $this->logInfo("Authentication successful", [
                    'expiry_seconds' => $this->tokenExpiry - time()
                ]);
                
                return true;
            }
            
            throw new Exception("Authentication failed: " . ($response->json()['message'] ?? 'Unknown error'));
            
        } catch (Exception $e) {
            $this->logError("Authentication failed", [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Ensure we have valid authentication token
     * 
     * @throws Exception
     */
    protected function ensureAuthenticated(): void
    {
        // Check cached token
        if (!$this->token) {
            $this->token = Cache::get('nbc_statement_token');
            $this->tokenExpiry = Cache::get('nbc_statement_token_expiry');
        }
        
        // Check if token is still valid (with 5 minute buffer)
        if (!$this->token || !$this->tokenExpiry || (time() + 300) >= $this->tokenExpiry) {
            $this->authenticate();
        }
    }

    /**
     * Send signed request to NBC API
     * 
     * @param string $endpoint
     * @param array $payload
     * @return array
     * @throws Exception
     */
    protected function sendSignedRequest(string $endpoint, array $payload): array
    {
        // Generate signature
        $signature = $this->generateSignature($payload);
        
        // Prepare headers
        $headers = [
            'Authorization' => 'Bearer ' . $this->token,
            'X-Signature' => $signature,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'Connection' => 'Keep-Alive',
            'Accept-Encoding' => 'br,deflate,gzip,x-gzip'
        ];
        
        $this->logDebug("Sending signed request", [
            'endpoint' => $endpoint,
            'service_code' => $payload['serviceCode'] ?? null,
            'partner_ref' => $payload['partnerRef'] ?? null
        ]);
        
        // Send request
        $response = Http::withHeaders($headers)
            ->withOptions(['verify' => false])
            ->timeout(30)
            ->post($this->baseUrl . $endpoint, $payload);
        
        $responseData = $response->json() ?? [];
        
        $this->logDebug("Response received", [
            'status_code' => $responseData['statusCode'] ?? null,
            'message' => $responseData['message'] ?? null,
            'bank_ref' => $responseData['bankRef'] ?? null
        ]);
        
        // Check for specific error codes
        if (isset($responseData['statusCode'])) {
            switch ($responseData['statusCode']) {
                case self::CODE_AUTH_FAILED:
                    // Try to re-authenticate
                    $this->token = null;
                    $this->tokenExpiry = null;
                    throw new Exception("Authentication failed - please retry");
                    
                case self::CODE_SIGNATURE_FAILURE:
                    throw new Exception("Digital signature verification failed");
                    
                case self::CODE_UNAUTHORIZED:
                    throw new Exception("Unauthorized service access");
            }
        }
        
        return $responseData;
    }

    /**
     * Generate digital signature for request
     * 
     * @param array $payload
     * @return string
     * @throws Exception
     */
    protected function generateSignature(array $payload): string
    {
        if (!file_exists($this->privateKeyPath)) {
            throw new Exception("Private key file not found");
        }
        
        $jsonPayload = json_encode($payload, JSON_UNESCAPED_SLASHES);
        $privateKey = openssl_pkey_get_private(file_get_contents($this->privateKeyPath));
        
        if (!$privateKey) {
            throw new Exception("Failed to load private key");
        }
        
        $signature = '';
        if (!openssl_sign($jsonPayload, $signature, $privateKey, OPENSSL_ALGO_SHA256)) {
            throw new Exception("Failed to generate signature");
        }
        
        return base64_encode($signature);
    }

    /**
     * Verify NBC signature (for responses)
     * 
     * @param string $signature
     * @param array $data
     * @return bool
     */
    protected function verifyNBCSignature(string $signature, array $data): bool
    {
        if (!file_exists($this->nbcPublicKeyPath)) {
            $this->logWarning("NBC public key not found - skipping verification");
            return true; // Skip verification if key not available
        }
        
        $jsonData = json_encode($data, JSON_UNESCAPED_SLASHES);
        $publicKey = openssl_pkey_get_public(file_get_contents($this->nbcPublicKeyPath));
        
        if (!$publicKey) {
            $this->logWarning("Failed to load NBC public key");
            return true;
        }
        
        $decodedSignature = base64_decode($signature);
        $verified = openssl_verify($jsonData, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);
        
        return $verified === 1;
    }

    /**
     * Generate unique partner reference
     * 
     * @param string $prefix
     * @return string
     */
    protected function generatePartnerRef(string $prefix = 'CB'): string
    {
        return $prefix . date('ymdHis') . rand(100, 999);
    }

    /**
     * Mask account number for logging
     * 
     * @param string $accountNumber
     * @return string
     */
    protected function maskAccount(string $accountNumber): string
    {
        if (strlen($accountNumber) > 6) {
            return substr($accountNumber, 0, 3) . '****' . substr($accountNumber, -3);
        }
        return '******';
    }

    /**
     * Get response code description
     * 
     * @param int $code
     * @return string
     */
    public function getResponseCodeDescription(int $code): string
    {
        $codes = [
            600 => 'Success',
            601 => 'Failed',
            602 => 'Digital Signature Verification Failure',
            613 => 'Unauthorized service access request',
            615 => 'Authentication Failed',
            699 => 'Exception caught'
        ];
        
        return $codes[$code] ?? 'Unknown error code';
    }

    /**
     * Format balance data for display
     * 
     * @param array $balanceData
     * @return array
     */
    public function formatBalanceData(array $balanceData): array
    {
        return [
            'currency' => $balanceData['currency'] ?? 'TZS',
            'opening_balance' => number_format($balanceData['openingBalance'] ?? 0, 2),
            'closing_balance' => number_format($balanceData['closingBalance'] ?? 0, 2),
            'total_transactions' => $balanceData['totalTransactionsCount'] ?? 0,
            'total_debits' => number_format($balanceData['totalDebitAmount'] ?? 0, 2),
            'total_credits' => number_format($balanceData['totalCreditAmount'] ?? 0, 2),
            'debit_count' => $balanceData['totalDebitCount'] ?? 0,
            'credit_count' => $balanceData['totalCreditCount'] ?? 0
        ];
    }

    /**
     * Format transaction for display
     * 
     * @param array $transaction
     * @return array
     */
    public function formatTransaction(array $transaction): array
    {
        return [
            'date' => Carbon::parse($transaction['transactionDate'])->format('Y-m-d'),
            'reference' => $transaction['reference'] ?? '',
            'description' => $transaction['description'] ?? '',
            'type' => $transaction['debitCredit'] === 'D' ? 'Debit' : 'Credit',
            'amount' => number_format($transaction['amount'] ?? 0, 2),
            'balance' => number_format($transaction['balance'] ?? 0, 2),
            'currency' => $transaction['currency'] ?? 'TZS'
        ];
    }

    /**
     * Log information
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel('statements')->info("[STATEMENT] {$message}", $context);
    }

    /**
     * Log error
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::channel('statements')->error("[STATEMENT] {$message}", $context);
    }

    /**
     * Log debug
     */
    protected function logDebug(string $message, array $context = []): void
    {
        Log::channel('statements')->debug("[STATEMENT] {$message}", $context);
    }

    /**
     * Log warning
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::channel('statements')->warning("[STATEMENT] {$message}", $context);
    }
}