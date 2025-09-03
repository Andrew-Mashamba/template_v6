<?php

namespace App\Services\Payments;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;

/**
 * Unified Bill Payment Service
 * Handles all utility bill payments including GEPG, LUKU, and other service providers
 */
class BillPaymentService
{
    protected string $baseUrl;
    protected string $apiKey;
    protected string $clientId;
    protected string $privateKeyPath;
    
    // Bill types
    const BILL_TYPE_GEPG = 'GEPG';
    const BILL_TYPE_LUKU = 'LUKU';
    const BILL_TYPE_WATER = 'WATER';
    const BILL_TYPE_TELECOM = 'TELECOM';
    const BILL_TYPE_TV = 'TV';
    const BILL_TYPE_INSURANCE = 'INSURANCE';
    const BILL_TYPE_OTHER = 'OTHER';

    // Service provider configurations
    protected array $serviceProviders = [
        'GEPG' => [
            'name' => 'Government Electronic Payment Gateway',
            'endpoint' => '/api/nbc-sg/v2',
            'verification_required' => true
        ],
        'LUKU' => [
            'name' => 'LUKU Prepaid Electricity',
            'endpoint' => '/api/nbc-luku/v2',
            'verification_required' => true
        ],
        'DAWASCO' => [
            'name' => 'Dar es Salaam Water',
            'endpoint' => '/api/bills/dawasco',
            'verification_required' => true
        ],
        'TTCL' => [
            'name' => 'Tanzania Telecommunications',
            'endpoint' => '/api/bills/ttcl',
            'verification_required' => false
        ],
        'DSTV' => [
            'name' => 'DSTV',
            'endpoint' => '/api/bills/dstv',
            'verification_required' => true
        ],
        'AZAM' => [
            'name' => 'Azam TV',
            'endpoint' => '/api/bills/azam',
            'verification_required' => true
        ]
    ];

    public function __construct()
    {
        $this->baseUrl = config('services.nbc_payments.base_url');
        $this->apiKey = config('services.nbc_payments.api_key');
        $this->clientId = config('services.nbc_payments.client_id');
        $this->privateKeyPath = storage_path('app/keys/private_key.pem');
        
        $this->logInfo('Bill Payment Service initialized', [
            'base_url' => $this->baseUrl,
            'client_id' => $this->clientId,
            'providers' => array_keys($this->serviceProviders)
        ]);
    }

    /**
     * Inquire bill details
     * 
     * @param string $billType
     * @param string $referenceNumber
     * @param array $additionalData
     * @return array
     */
    public function inquireBill(string $billType, string $referenceNumber, array $additionalData = []): array
    {
        $startTime = microtime(true);
        
        $this->logInfo("Starting bill inquiry", [
            'bill_type' => $billType,
            'reference' => $referenceNumber
        ]);

        try {
            switch ($billType) {
                case self::BILL_TYPE_GEPG:
                    $result = $this->inquireGEPGBill($referenceNumber, $additionalData);
                    break;
                    
                case self::BILL_TYPE_LUKU:
                    $result = $this->inquireLUKUBill($referenceNumber, $additionalData);
                    break;
                    
                default:
                    $result = $this->inquireGenericBill($billType, $referenceNumber, $additionalData);
                    break;
            }
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result['success']) {
                $this->logInfo("Bill inquiry successful", [
                    'bill_type' => $billType,
                    'reference' => $referenceNumber,
                    'duration_ms' => $duration
                ]);
            }
            
            $result['response_time'] = $duration;
            return $result;

        } catch (Exception $e) {
            $this->logError("Bill inquiry failed", [
                'bill_type' => $billType,
                'reference' => $referenceNumber,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'bill_type' => $billType,
                'reference' => $referenceNumber
            ];
        }
    }

    /**
     * Pay bill
     * 
     * @param string $billType
     * @param array $paymentData
     * @return array
     */
    public function payBill(string $billType, array $paymentData): array
    {
        $startTime = microtime(true);
        $reference = $this->generateReference('BILL');
        
        $this->logInfo("Starting bill payment", [
            'reference' => $reference,
            'bill_type' => $billType,
            'amount' => $paymentData['amount'] ?? 0
        ]);

        try {
            // Validate payment data
            $this->validatePaymentData($paymentData);

            // Process based on bill type
            switch ($billType) {
                case self::BILL_TYPE_GEPG:
                    $result = $this->payGEPGBill($reference, $paymentData);
                    break;
                    
                case self::BILL_TYPE_LUKU:
                    $result = $this->payLUKUBill($reference, $paymentData);
                    break;
                    
                default:
                    $result = $this->payGenericBill($billType, $reference, $paymentData);
                    break;
            }
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);

            if ($result['success']) {
                // Save transaction
                $this->saveTransaction([
                    'reference' => $reference,
                    'type' => 'BILL_PAYMENT',
                    'bill_type' => $billType,
                    'bill_reference' => $paymentData['bill_reference'] ?? '',
                    'from_account' => $paymentData['from_account'],
                    'amount' => $paymentData['amount'],
                    'status' => 'SUCCESS',
                    'response_code' => $result['response_code'] ?? '',
                    'response_message' => $result['message'] ?? '',
                    'provider_reference' => $result['provider_reference'] ?? '',
                    'token' => $result['token'] ?? null,
                    'duration_ms' => $duration
                ]);

                $this->logInfo("Bill payment successful", [
                    'reference' => $reference,
                    'bill_type' => $billType,
                    'provider_reference' => $result['provider_reference'] ?? '',
                    'duration_ms' => $duration
                ]);

                $result['reference'] = $reference;
                $result['response_time'] = $duration;
                return $result;
            }

            throw new Exception($result['error'] ?? 'Payment failed');

        } catch (Exception $e) {
            $this->logError("Bill payment failed", [
                'reference' => $reference,
                'bill_type' => $billType,
                'error' => $e->getMessage(),
                'duration_ms' => round((microtime(true) - $startTime) * 1000, 2)
            ]);

            // Save failed transaction
            $this->saveTransaction([
                'reference' => $reference,
                'type' => 'BILL_PAYMENT',
                'bill_type' => $billType,
                'bill_reference' => $paymentData['bill_reference'] ?? '',
                'from_account' => $paymentData['from_account'] ?? '',
                'amount' => $paymentData['amount'] ?? 0,
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
     * Inquire GEPG bill
     */
    protected function inquireGEPGBill(string $controlNumber, array $additionalData): array
    {
        try {
            $payload = [
                'GepgGatewayBillQryReq' => [
                    'GepgGatewayHdr' => [
                        'ChannelID' => $this->clientId,
                        'ChannelName' => 'SACCOS',
                        'Service' => 'GEPG_INQ',
                    ],
                    'gepgBillQryReq' => [
                        'ChannelRef' => $this->generateReference('GEPGINQ'),
                        'CustCtrNum' => $controlNumber,
                        'DebitAccountNo' => $additionalData['account_number'] ?? config('services.nbc_payments.saccos_account'),
                        'DebitAccountCurrency' => 'TZS',
                    ],
                ]
            ];

            $response = $this->sendXMLRequest('/api/nbc-sg/v2/billquery', $payload, 'GEPG_INQ');

            if ($response['success']) {
                $billData = $response['data']['GepgGatewayBillQryResp'] ?? [];
                
                return [
                    'success' => true,
                    'bill_type' => self::BILL_TYPE_GEPG,
                    'control_number' => $controlNumber,
                    'bill_amount' => $billData['BillDtl']['BillAmt'] ?? 0,
                    'minimum_amount' => $billData['BillDtl']['MinPayAmt'] ?? 0,
                    'service_provider' => $billData['BillDtl']['SpName'] ?? '',
                    'payer_name' => $billData['BillDtl']['PyrName'] ?? '',
                    'bill_description' => $billData['BillDtl']['BillDesc'] ?? '',
                    'bill_status' => $billData['BillHdr']['BillStsCode'] ?? '',
                    'expiry_date' => $billData['BillDtl']['BillExprDt'] ?? '',
                    'raw_response' => $billData
                ];
            }

            throw new Exception($response['message'] ?? 'GEPG inquiry failed');

        } catch (Exception $e) {
            throw new Exception("GEPG bill inquiry error: " . $e->getMessage());
        }
    }

    /**
     * Pay GEPG bill
     */
    protected function payGEPGBill(string $reference, array $paymentData): array
    {
        try {
            $payload = [
                'GepgGatewayPaymentReq' => [
                    'GepgGatewayHdr' => [
                        'ChannelID' => $this->clientId,
                        'ChannelName' => 'SACCOS',
                        'Service' => 'GEPG_PAY',
                    ],
                    'PmtHdr' => [
                        'ChannelRef' => $reference,
                        'CbpGwRef' => $paymentData['cbp_gw_ref'] ?? '',
                        'CustCtrNum' => $paymentData['control_number'],
                        'PayType' => '1',
                        'EntryCnt' => 1,
                        'BillStsCode' => $paymentData['bill_status'] ?? '',
                    ],
                    'PmtDtls' => [
                        'PmtDtl' => [
                            'ChannelTrxId' => $reference,
                            'SpCode' => $paymentData['sp_code'] ?? '',
                            'PayRefId' => $paymentData['pay_ref_id'] ?? '',
                            'BillCtrNum' => $paymentData['control_number'],
                            'PaidAmt' => $paymentData['amount'],
                            'TrxDtTm' => Carbon::now()->format('Y-m-d\TH:i:s'),
                            'PayOpt' => '1',
                            'Ccy' => 'TZS',
                            'PyrName' => $paymentData['payer_name'] ?? '',
                            'DebitAmount' => $paymentData['amount'],
                        ]
                    ],
                    'GepgGatewayProcessingInfo' => [
                        'BankType' => 'ONUS',
                        'Forex' => 'N',
                        'DebitAccountNo' => $paymentData['from_account'],
                        'DebitAccountType' => 'CASA',
                        'DebitAccountCurrency' => 'TZS',
                        'DebitAmount' => $paymentData['amount'],
                    ]
                ]
            ];

            $response = $this->sendXMLRequest('/api/nbc-sg/v2/bill-pay', $payload, 'GEPG_PAY');

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => 'GEPG payment successful',
                    'provider_reference' => $response['data']['PmtTrxInf']['TrxId'] ?? '',
                    'response_code' => $response['data']['PmtTrxInf']['TrxSts'] ?? '',
                    'control_number' => $paymentData['control_number']
                ];
            }

            throw new Exception($response['message'] ?? 'GEPG payment failed');

        } catch (Exception $e) {
            throw new Exception("GEPG payment error: " . $e->getMessage());
        }
    }

    /**
     * Inquire LUKU bill
     */
    protected function inquireLUKUBill(string $meterNumber, array $additionalData): array
    {
        try {
            $payload = [
                'serviceName' => 'LUKU_LOOKUP',
                'clientId' => $this->clientId,
                'clientRef' => $this->generateReference('LUKULOOKUP'),
                'meterNumber' => $meterNumber,
                'accountNumber' => $additionalData['account_number'] ?? config('services.nbc_payments.saccos_account'),
                'accountCurrency' => 'TZS'
            ];

            $signature = $this->generateSignature($payload);
            
            $response = $this->sendRequest('/api/nbc-luku/v2/lookup', $payload, [
                'Signature' => $signature,
                'Service-Name' => 'LUKU_LOOKUP'
            ]);

            if ($response['success']) {
                $lukuData = $response['data'];
                
                return [
                    'success' => true,
                    'bill_type' => self::BILL_TYPE_LUKU,
                    'meter_number' => $meterNumber,
                    'owner_name' => $lukuData['owner'] ?? '',
                    'meter_status' => $lukuData['statusDescription'] ?? '',
                    'debts' => $lukuData['debts'] ?? [],
                    'reference' => $lukuData['reference'] ?? '',
                    'raw_response' => $lukuData
                ];
            }

            throw new Exception($response['message'] ?? 'LUKU inquiry failed');

        } catch (Exception $e) {
            throw new Exception("LUKU meter inquiry error: " . $e->getMessage());
        }
    }

    /**
     * Pay LUKU bill
     */
    protected function payLUKUBill(string $reference, array $paymentData): array
    {
        try {
            $payload = [
                'serviceName' => 'LUKU_PAYMENT',
                'clientId' => $this->clientId,
                'clientRef' => $reference,
                'meterNumber' => $paymentData['meter_number'],
                'amount' => $paymentData['amount'],
                'accountNumber' => $paymentData['from_account'],
                'accountCurrency' => 'TZS',
                'customerName' => $paymentData['customer_name'] ?? '',
                'customerPhone' => $paymentData['customer_phone'] ?? '',
                'timestamp' => Carbon::now()->toIso8601String()
            ];

            $signature = $this->generateSignature($payload);
            
            $response = $this->sendRequest('/api/nbc-luku/v2/payment', $payload, [
                'Signature' => $signature,
                'Service-Name' => 'LUKU_PAYMENT'
            ]);

            if ($response['success']) {
                $lukuResponse = $response['data'];
                
                // Save token if received
                if (isset($lukuResponse['token'])) {
                    $this->saveLukuToken([
                        'reference' => $reference,
                        'meter_number' => $paymentData['meter_number'],
                        'token' => $lukuResponse['token'],
                        'units' => $lukuResponse['units'] ?? '',
                        'amount' => $paymentData['amount']
                    ]);
                }
                
                return [
                    'success' => true,
                    'message' => 'LUKU payment successful',
                    'provider_reference' => $lukuResponse['receiptNumber'] ?? '',
                    'token' => $lukuResponse['token'] ?? '',
                    'units' => $lukuResponse['units'] ?? '',
                    'meter_number' => $paymentData['meter_number']
                ];
            }

            throw new Exception($response['message'] ?? 'LUKU payment failed');

        } catch (Exception $e) {
            throw new Exception("LUKU payment error: " . $e->getMessage());
        }
    }

    /**
     * Inquire generic bill
     */
    protected function inquireGenericBill(string $billType, string $referenceNumber, array $additionalData): array
    {
        try {
            if (!isset($this->serviceProviders[$billType])) {
                throw new Exception("Unknown bill type: {$billType}");
            }

            $provider = $this->serviceProviders[$billType];
            
            $payload = [
                'serviceName' => "{$billType}_INQUIRY",
                'clientId' => $this->clientId,
                'clientRef' => $this->generateReference("{$billType}INQ"),
                'referenceNumber' => $referenceNumber,
                'accountNumber' => $additionalData['account_number'] ?? config('services.nbc_payments.saccos_account'),
                'additionalData' => $additionalData
            ];

            $response = $this->sendRequest($provider['endpoint'] . '/inquiry', $payload);

            if ($response['success']) {
                return [
                    'success' => true,
                    'bill_type' => $billType,
                    'reference_number' => $referenceNumber,
                    'bill_amount' => $response['data']['amount'] ?? 0,
                    'customer_name' => $response['data']['customerName'] ?? '',
                    'bill_description' => $response['data']['description'] ?? '',
                    'due_date' => $response['data']['dueDate'] ?? '',
                    'raw_response' => $response['data']
                ];
            }

            throw new Exception($response['message'] ?? 'Bill inquiry failed');

        } catch (Exception $e) {
            throw new Exception("Bill inquiry error: " . $e->getMessage());
        }
    }

    /**
     * Pay generic bill
     */
    protected function payGenericBill(string $billType, string $reference, array $paymentData): array
    {
        try {
            if (!isset($this->serviceProviders[$billType])) {
                throw new Exception("Unknown bill type: {$billType}");
            }

            $provider = $this->serviceProviders[$billType];
            
            $payload = [
                'serviceName' => "{$billType}_PAYMENT",
                'clientId' => $this->clientId,
                'clientRef' => $reference,
                'referenceNumber' => $paymentData['bill_reference'],
                'amount' => $paymentData['amount'],
                'accountNumber' => $paymentData['from_account'],
                'customerName' => $paymentData['customer_name'] ?? '',
                'timestamp' => Carbon::now()->toIso8601String()
            ];

            $response = $this->sendRequest($provider['endpoint'] . '/payment', $payload);

            if ($response['success']) {
                return [
                    'success' => true,
                    'message' => "{$billType} payment successful",
                    'provider_reference' => $response['data']['transactionId'] ?? '',
                    'response_code' => $response['data']['responseCode'] ?? '',
                    'bill_reference' => $paymentData['bill_reference']
                ];
            }

            throw new Exception($response['message'] ?? 'Payment failed');

        } catch (Exception $e) {
            throw new Exception("Payment error: " . $e->getMessage());
        }
    }

    /**
     * Save LUKU token
     */
    protected function saveLukuToken(array $tokenData): void
    {
        try {
            DB::table('luku_tokens')->insert([
                'reference' => $tokenData['reference'],
                'meter_number' => $tokenData['meter_number'],
                'token' => $tokenData['token'],
                'units' => $tokenData['units'],
                'amount' => $tokenData['amount'],
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->logInfo("LUKU token saved", [
                'reference' => $tokenData['reference'],
                'meter' => $tokenData['meter_number']
            ]);
        } catch (Exception $e) {
            $this->logError("Failed to save LUKU token", [
                'error' => $e->getMessage(),
                'reference' => $tokenData['reference']
            ]);
        }
    }

    /**
     * Validate payment data
     */
    protected function validatePaymentData(array $data): void
    {
        $required = ['from_account', 'amount'];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                throw new Exception("Missing required field: {$field}");
            }
        }

        if (!is_numeric($data['amount']) || $data['amount'] <= 0) {
            throw new Exception("Invalid amount");
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
     * Send JSON request
     */
    protected function sendRequest(string $endpoint, array $payload, array $additionalHeaders = []): array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            
            $headers = array_merge([
                'Content-Type' => 'application/json',
                'X-Api-Key' => $this->apiKey,
                'Client-Id' => $this->clientId,
                'Timestamp' => Carbon::now()->toIso8601String()
            ], $additionalHeaders);

            $this->logDebug("Sending bill payment request", [
                'url' => $url,
                'service' => $payload['serviceName'] ?? 'UNKNOWN'
            ]);

            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => false])
                ->timeout(30)
                ->post($url, $payload);

            $statusCode = $response->status();
            $responseData = $response->json() ?? [];

            $this->logDebug("Bill payment response received", [
                'status_code' => $statusCode,
                'service' => $payload['serviceName'] ?? 'UNKNOWN'
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
     * Send XML request (for GEPG)
     */
    protected function sendXMLRequest(string $endpoint, array $payload, string $service): array
    {
        try {
            $url = $this->baseUrl . $endpoint;
            $xml = $this->arrayToXml($payload);
            
            $headers = [
                'Content-Type' => 'application/xml',
                'X-Api-Key' => $this->apiKey,
                'Client-Id' => $this->clientId,
                'Service-Name' => $service
            ];

            $this->logDebug("Sending XML request", [
                'url' => $url,
                'service' => $service
            ]);

            $response = Http::withHeaders($headers)
                ->withOptions(['verify' => false])
                ->timeout(30)
                ->send('POST', $url, ['body' => $xml]);

            $statusCode = $response->status();
            $xmlResponse = $response->body();

            $this->logDebug("XML response received", [
                'status_code' => $statusCode,
                'service' => $service
            ]);

            if ($statusCode === 200 || $statusCode === 201) {
                $responseData = $this->xmlToArray($xmlResponse);
                return [
                    'success' => true,
                    'data' => $responseData
                ];
            }

            return [
                'success' => false,
                'message' => "Request failed with status {$statusCode}"
            ];

        } catch (Exception $e) {
            $this->logError("XML request failed", [
                'endpoint' => $endpoint,
                'service' => $service,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Convert array to XML
     */
    protected function arrayToXml(array $data, $rootElement = null): string
    {
        if ($rootElement === null) {
            $rootElement = new \SimpleXMLElement('<root/>');
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $subNode = $rootElement->addChild($key);
                $this->arrayToXml($value, $subNode);
            } else {
                $rootElement->addChild($key, htmlspecialchars($value));
            }
        }

        return $rootElement->asXML();
    }

    /**
     * Convert XML to array
     */
    protected function xmlToArray(string $xml): array
    {
        $xmlObject = simplexml_load_string($xml);
        $json = json_encode($xmlObject);
        return json_decode($json, true);
    }

    /**
     * Generate unique reference
     */
    protected function generateReference(string $prefix = 'BILL'): string
    {
        // NBC API requires alphanumeric clientRef only (no underscores or special chars)
        return $prefix . date('YmdHis') . strtoupper(substr(md5(uniqid()), 0, 6));
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
                'bill_type' => $data['bill_type'] ?? null,
                'bill_reference' => $data['bill_reference'] ?? null,
                'from_account' => $data['from_account'],
                'amount' => $data['amount'],
                'status' => $data['status'],
                'response_code' => $data['response_code'] ?? null,
                'response_message' => $data['response_message'] ?? null,
                'provider_reference' => $data['provider_reference'] ?? null,
                'token' => $data['token'] ?? null,
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
        Log::channel('payments')->info("[BILL] {$message}", $context);
    }

    /**
     * Log error
     */
    protected function logError(string $message, array $context = []): void
    {
        Log::channel('payments')->error("[BILL] {$message}", $context);
    }

    /**
     * Log debug
     */
    protected function logDebug(string $message, array $context = []): void
    {
        Log::channel('payments')->debug("[BILL] {$message}", $context);
    }
}