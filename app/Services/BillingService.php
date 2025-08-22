<?php

namespace App\Services;

use App\Models\Bill;
use App\Models\Payment;
use App\Models\PaymentNotification;
use App\Models\AccountsModel;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    private $logger;

    public function __construct()
    {
        $this->logger = new TransactionLogger();
    }

    public function generateControlNumber($member, $service, $isRecurring, $paymentMode)
    {
        $institution_id = DB::table('institutions')->where('id',1)->value('institution_id');
        $sacco = preg_replace('/[^0-9]/', '', $institution_id);
        $controlNumber = '1' . 
            str_pad($sacco, 4, '0', STR_PAD_LEFT) .
            str_pad($member, 5, '0', STR_PAD_LEFT) .
            $service .
            $isRecurring .
            $paymentMode;

        $this->logger->logTransactionStart([
            'member' => $member,
            'service' => $service,
            'is_recurring' => $isRecurring,
            'payment_mode' => $paymentMode,
            'control_number' => $controlNumber
        ]);

        return $controlNumber;
    }

    public function createBill($clientNumber, $serviceId, $isRecurring, $paymentMode, $controlNumber, $amount)
    {
        try {
            $this->logger->logTransactionStart([
                'client_number' => $clientNumber,
                'service_id' => $serviceId,
                'control_number' => $controlNumber,
                'amount' => $amount
            ]);

            // Get service details
            $service = DB::table('services')->where('id', $serviceId)->first();
            if (!$service) {
                throw new \Exception("Service with ID {$serviceId} not found");
            }

            Log::info('Service details retrieved', [
                'service_id' => $serviceId,
                'service_name' => $service->name,
                'debit_account' => $service->debit_account,
                'credit_account' => $service->credit_account
            ]);

            $creditAccount = session()->get('saved_credit_account');
            if (!$creditAccount) {
                $creditAccount = $service->credit_account;
            }else{
                $creditAccount = $creditAccount->account_number;
            }

            //TODO: log the inserted bill data 
            Log::info('Inserted bill data --------- xxxxxxxxxxxxxxxxxxxxxxxxxxx', [
                'member_id' => $clientNumber,
                'client_number' => $clientNumber,
                'service_id' => DB::table('sub_products')->where('product_account', $service->debit_account)->value('id') ?? $serviceId,
                'control_number' => $controlNumber,
                'amount_due' => $amount,
                'amount_paid' => 0,
                'is_recurring' => $isRecurring,
                'payment_mode' => $paymentMode,
                'status' => 'PENDING',
                'due_date' => now()->addDays(14),
                'credit_account_number' => $creditAccount ?? null,
                'debit_account_number' => $service->debit_account ?? null,
                'created_by' => auth()->id() ?? 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);


            // Create the bill record with both client_number and member_id
            $bill = DB::table('bills')->insertGetId([
                'member_id' => $clientNumber,
                'client_number' => $clientNumber,  // Added client_number
                'service_id' => DB::table('sub_products')->where('product_account', $service->debit_account)->value('id') ?? $serviceId,
                'control_number' => $controlNumber,
                'amount_due' => $amount,
                'amount_paid' => 0,
                'is_recurring' => $isRecurring,
                'payment_mode' => $paymentMode,
                'status' => 'PENDING',
                'due_date' => now()->addDays(14),
                'credit_account_number' => $creditAccount ?? null,
                'debit_account_number' => $service->debit_account ?? null,
               
                'created_by' => auth()->id() ?? 1,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            session()->forget('saved_credit_account');

            Log::info('Bill created with account numbers', [
                'bill_id' => $bill,
                'control_number' => $controlNumber,
                'credit_account_number' => $service->credit_account,
                'debit_account_number' => $service->debit_account
            ]);

            // Log the transaction record with all required fields
            $this->logger->logTransactionRecord([
                'reference_number' => $controlNumber,
                'transaction_type' => 'BILL_CREATION',
                'amount' => $amount,
                'debit_account' => $service->debit_account ?? $clientNumber,
                'credit_account' => $service->credit_account ?? 'SERVICE_' . $serviceId
            ]);

            $this->logger->logTransactionCompletion($controlNumber, 'SUCCESS');

            return $bill;

        } catch (\Exception $e) {
            $this->logger->logError($e, [
                'client_number' => $clientNumber,
                'service_id' => $serviceId,
                'control_number' => $controlNumber
            ]);
            throw $e;
        }
    }

    public function processPayment($controlNumber, $paymentData)
    {
        Log::info('Starting payment processing', [
            'control_number' => $controlNumber,
            'payment_data' => $paymentData,
            'timestamp' => now()->toIso8601String()
        ]);

        $this->logger->logTransactionStart([
            'control_number' => $controlNumber,
            'payment_data' => $paymentData
        ]);

        try {
            DB::beginTransaction();
            Log::info('Database transaction started');

            $bill = Bill::where('control_number', $controlNumber)->first();
            
            if (!$bill) {
                Log::error('Bill not found', [
                    'control_number' => $controlNumber,
                    'search_time' => now()->toIso8601String()
                ]);

                $this->logger->logError(new \Exception("Bill not found"), [
                    'control_number' => $controlNumber
                ]);
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => "Bill with control number {$controlNumber} not found.",
                    'status_code' => 404,
                    'bill' => null
                ];
            }

            Log::info('Bill found', [
                'bill_id' => $bill->id,
                'control_number' => $bill->control_number,
                'amount_due' => $bill->amount_due,
                'amount_paid' => $bill->amount_paid,
                'status' => $bill->status,
                'service_id' => $bill->service_id,
                'client_number' => $bill->client_number
            ]);

            $validationResult = $this->validatePaymentAmount($bill, $paymentData['amount']);
            Log::info('Payment amount validation result', [
                'validation_result' => $validationResult,
                'amount_paid' => $paymentData['amount'],
                'amount_due' => $bill->amount_due,
                'payment_mode' => $bill->payment_mode
            ]);

            if (!$validationResult['success']) {
                $this->logger->logTransactionRecord([
                    'control_number' => $controlNumber,
                    'validation_result' => $validationResult
                ]);
                return $validationResult;
            }

            $bill->amount_paid += $paymentData['amount'];
            Log::info('Updated bill amount paid', [
                'bill_id' => $bill->id,
                'new_amount_paid' => $bill->amount_paid,
                'amount_due' => $bill->amount_due,
                'payment_amount' => $paymentData['amount']
            ]);

            if ($bill->amount_paid > $bill->amount_due) {
                Log::error('Payment amount exceeds total amount due', [
                    'bill_id' => $bill->id,
                    'amount_paid' => $bill->amount_paid,
                    'amount_due' => $bill->amount_due,
                    'excess_amount' => $bill->amount_paid - $bill->amount_due
                ]);

                $this->logger->logTransactionRecord([
                    'control_number' => $controlNumber,
                    'amount_paid' => $bill->amount_paid,
                    'amount_due' => $bill->amount_due
                ]);
                return [
                    'success' => false,
                    'status' => 'error',
                    'message' => "Payment amount exceeds the total amount due.",
                    'status_code' => 422,
                    'bill' => $bill
                ];
            }

            // Update bill status
            $oldStatus = $bill->status;
            if ($bill->amount_paid >= $bill->amount_due) {
                $bill->status = 'PAID';




            } elseif ($bill->due_date < now()) {
                $bill->status = 'OVERDUE';
            }

            Log::info('Updating bill status', [
                'bill_id' => $bill->id,
                'old_status' => $oldStatus,
                'new_status' => $bill->status,
                'amount_paid' => $bill->amount_paid,
                'amount_due' => $bill->amount_due,
                'due_date' => $bill->due_date
            ]);

            $bill->save();

            $this->logger->logTransactionRecord([
                'control_number' => $controlNumber,
                'new_status' => $bill->status,
                'amount_paid' => $bill->amount_paid
            ]);

            DB::commit();
            Log::info('Database transaction committed successfully');

            $narrationSuffix = $bill->service->name;
            $debitAccountCode = $bill->debit_account_number;
            $creditAccountCode = $bill->credit_account_number;

            Log::info('Processing transaction', [
                'bill_id' => $bill->id,
                'narration' => $narrationSuffix,
                'debit_account' => $debitAccountCode,
                'credit_account' => $creditAccountCode,
                'amount' => $paymentData['amount']
            ]);

            $this->processTransaction($paymentData['amount'], $narrationSuffix, $bill);

            $this->logger->logTransactionCompletion($controlNumber, 'success');

            Log::info('Payment processing completed successfully', [
                'bill_id' => $bill->id,
                'control_number' => $controlNumber,
                'final_status' => $bill->status,
                'final_amount_paid' => $bill->amount_paid
            ]);

            return [
                'success' => true,
                'status' => 'success',
                'message' => 'Payment processed successfully',
                'status_code' => 200,
                'bill' => $bill
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'control_number' => $controlNumber,
                'payment_data' => $paymentData,
                'failed_at' => now()->toIso8601String()
            ]);

            $this->logger->logError($e, [
                'control_number' => $controlNumber,
                'payment_data' => $paymentData
            ]);
            return [
                'success' => false,
                'status' => 'error',
                'message' => $e->getMessage(),
                'status_code' => 500,
                'bill' => null
            ];
        }
    }

    protected function validatePaymentAmount($bill, $amount)
    {
        try {
            switch ($bill->payment_mode) {
                case '1': // Partial
                    if ($amount <= 0) {
                        throw new \Exception('Partial payment amount must be greater than 0');
                    }
                    break;

                case '2': // Full
                    if ($amount < $bill->amount_due) {
                        throw new \Exception('Full payment must cover the entire amount due');
                    }
                    break;

                case '3': // Exact
                    if ($amount != $bill->amount_due) {
                        throw new \Exception('Exact payment must match the amount due');
                    }
                    break;

                case '4': // Limited
                    $remaining = $bill->amount_due - $bill->amount_paid;
                    if ($amount > $remaining) {
                        throw new \Exception('Limited payment cannot exceed remaining amount');
                    }
                    break;

                case '5': // Infinity
                    if ($amount <= 0) {
                        throw new \Exception('Infinity payment amount must be greater than 0');
                    }
                    break;
            }

            return [
                'success' => true,
                'status' => 'valid',
                'message' => 'Payment amount is valid',
                'status_code' => 200
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'status' => 'error',
                'message' => 'Payment amount is invalid. ' . $e->getMessage(),
                'status_code' => 422
            ];
        }
    }

    public function handlePaymentNotification($notificationData)
    {
        try {
            DB::beginTransaction();

            $notification = PaymentNotification::create([
                'control_number' => $notificationData['control_number'],
                'received_at' => now(),
                'raw_payload' => $notificationData,
                'status' => 'Pending'
            ]);

            // Process the payment
            $this->processPayment(
                $notificationData['control_number'],
                [
                    'payment_ref' => $notificationData['payment_ref'],
                    'amount' => $notificationData['amount'],
                    'payment_channel' => $notificationData['payment_channel'],
                    'paid_at' => $notificationData['paid_at'] ?? now()
                ]
            );

            $notification->update([
                'status' => 'Processed',
                'processed_at' => now()
            ]);

            DB::commit();
            return $notification;

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment notification processing failed: ' . $e->getMessage());
            
            if (isset($notification)) {
                $notification->update([
                    'status' => 'Failed',
                    'processed_at' => now()
                ]);
            }

            throw $e;
        }
    }

    public function getBillStatus($controlNumber)
    {
        $bill = Bill::where('control_number', $controlNumber)
            ->with(['sacco', 'member', 'service'])
            ->firstOrFail();

        return [
            'control_number' => $bill->control_number,
            'amount_due' => $bill->amount_due,
            'amount_paid' => $bill->amount_paid,
            'status' => $bill->status,
            'due_date' => $bill->due_date,
            'member_name' => $bill->member->name,
            'service_name' => $bill->service->name
        ];
    }

    protected function validateAccounts($debitAccountNumber, $creditAccountNumber)
    {
        Log::info('Starting account validation', [
            'debit_account_number' => $debitAccountNumber,
            'credit_account_number' => $creditAccountNumber,
            'timestamp' => now()->toIso8601String()
        ]);

        $debitAccount = AccountsModel::where('account_number', $debitAccountNumber)->first();
        $creditAccount = AccountsModel::where('account_number', $creditAccountNumber)->first();

        if (!$debitAccount) {
            Log::error('Debit account not found', [
                'account_number' => $debitAccountNumber,
                'validation_time' => now()->toIso8601String()
            ]);
        } else {
            Log::info('Debit account found', [
                'account_number' => $debitAccount->account_number,
                'account_name' => $debitAccount->account_name,
                'sub_category_code' => $debitAccount->sub_category_code,
                'status' => $debitAccount->status
            ]);
        }

        if (!$creditAccount) {
            Log::error('Credit account not found', [
                'account_number' => $creditAccountNumber,
                'validation_time' => now()->toIso8601String()
            ]);
        } else {
            Log::info('Credit account found', [
                'account_number' => $creditAccount->account_number,
                'account_name' => $creditAccount->account_name,
                'sub_category_code' => $creditAccount->sub_category_code,
                'status' => $creditAccount->status
            ]);
        }

        // Skip status check for internal operating accounts
        $isValid = !is_null($debitAccount) && !is_null($creditAccount) && 
                  ($creditAccount->status === 'ACTIVE' || 
                   ($debitAccount->account_use === 'internal' && $debitAccount->status === 'PENDING'));
        
        Log::info('Account validation completed', [
            'is_valid' => $isValid,
            'debit_account_found' => !is_null($debitAccount),
            'credit_account_found' => !is_null($creditAccount),
            'validation_time' => now()->toIso8601String()
        ]);

        return [
            'debit_account' => $debitAccountNumber,
            'credit_account' => $creditAccountNumber,
            'is_valid' => $isValid,
            'validation_details' => [
                'debit_account_details' => $debitAccount ? [
                    'account_name' => $debitAccount->account_name,
                    'sub_category_code' => $debitAccount->sub_category_code,
                    'status' => $debitAccount->status,
                    'account_use' => $debitAccount->account_use
                ] : null,
                'credit_account_details' => $creditAccount ? [
                    'account_name' => $creditAccount->account_name,
                    'sub_category_code' => $creditAccount->sub_category_code,
                    'status' => $creditAccount->status
                ] : null
            ]
        ];
    }

    protected function getGroupAccount($creditAccount)
    {
        try {
            $account = DB::table('accounts')
                ->where('account_number', $creditAccount)
                ->first();

            if (!$account) {
                Log::error('Group account not found', ['credit_account' => $creditAccount]);
                throw new \Exception("Group account not found for account: {$creditAccount}");
            }

            return $account;
        } catch (\Exception $e) {
            Log::error('Error retrieving group account', [
                'credit_account' => $creditAccount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    protected function processTransaction($amount, $narrationSuffix, $bill)
    {
        $this->logger->logTransactionStart([
            'amount' => $amount,
            'narration' => $narrationSuffix,
            'bill_id' => $bill->id,
            'control_number' => $bill->control_number
        ]);

        try {
            // Validate bill reference
            if (!$bill->control_number) {
                throw new \Exception('Bill reference is required');
            }

            // Get service details
            $service = DB::table('services')->where('id', $bill->service_id)->first();
            if (!$service) {
                Log::error('Service not found', [
                    'service_id' => $bill->service_id,
                    'bill_id' => $bill->id,
                    'error' => 'Service not found for bill ID: ' . $bill->id
                ]);
                throw new \Exception("Service not found for bill ID: {$bill->id}");
            }

            Log::info('Service details --------- xxxxxxxxxxxxxxxxxxxxxxxxxxx', [
                'service' => $service,
                'bill_id' => $bill->id,
                'control_number' => $bill->control_number
            ]);

            $debitAccountCode = $service->debit_account;
            $creditAccountCode = $bill->credit_account_number;

            Log::info('Starting transaction processing', [
                'bill_id' => $bill->id,
                'debit_account' => $debitAccountCode,
                'credit_account' => $creditAccountCode,
                'amount' => $amount,
                'service_id' => $service->id,
                'service_name' => $service->name
            ]);

            // Validate accounts
            $accountValidation = $this->validateAccounts($debitAccountCode, $creditAccountCode);

            // if (!$accountValidation['is_valid']) {
            //     Log::error('Account validation failed', [
            //         'validation_details' => $accountValidation['validation_details'],
            //         'bill_id' => $bill->id,
            //         'debit_account' => $debitAccountCode,
            //         'credit_account' => $creditAccountCode
            //     ]);
            //     throw new \Exception('Invalid account configuration');
            // }

            $narration = "{$narrationSuffix} : Bill ID {$bill->id}";
            $debit_account = $accountValidation['debit_account'];
            $credit_account = $accountValidation['credit_account'];

            // Handle different service types
            if ($service->code == 'REG') {
                Log::info('Processing registration service', [
                    'bill_id' => $bill->id,
                    'service_code' => $service->code,
                    'narration' => $narration
                ]);





            } else {
                try {
                    $groupAccount = $this->getGroupAccount($credit_account);
                    Log::info('Group account details', [
                        'group_account' => $groupAccount,
                        'credit_account' => $credit_account,
                        'bill_id' => $bill->id
                    ]);

                    $majorCategory = $groupAccount->major_category_code;
                    $category = $groupAccount->category_code;
                    $subCategory = $groupAccount->sub_category_code;

                    $memberAccount = DB::table('accounts')
                        ->where('major_category_code', $majorCategory)
                        ->where('category_code', $category)
                        ->where('client_number', $bill->client_number)
                        ->first();

                    if (!$memberAccount) {
                        Log::error('Member account not found', [
                            'client_number' => $bill->client_number,
                            'major_category' => $majorCategory,
                            'category' => $category,
                            'bill_id' => $bill->id
                        ]);
                        throw new \Exception("Member account not found for member: {$bill->client_number}");
                    }

                    Log::info('Member account found', [
                        'member_account' => $memberAccount,
                        'client_number' => $bill->client_number,
                        'bill_id' => $bill->id
                    ]);

                    $credit_account = $memberAccount->account_number;
                } catch (\Exception $e) {
                    Log::error('Error processing group account', [
                        'error' => $e->getMessage(),
                        'credit_account' => $credit_account,
                        'member' => $bill->client_number,
                        'bill_id' => $bill->id,
                        'trace' => $e->getTraceAsString()
                    ]);
                    throw $e;
                }
            }

            // Post the transaction
            $transactionService = new TransactionPostingService();
            $transactionData = [
                'first_account' => $credit_account,
                'second_account' => $debit_account,
                'amount' => $amount,
                'narration' => $narration,
            ];

            Log::info('Posting transaction', [
                'debit_account' => $debitAccountCode,
                'credit_account' => $creditAccountCode,
                'amount' => $amount,
                'bill_id' => $bill->id,
                'transaction_data' => $transactionData
            ]);

            try {
                $response = $transactionService->postTransaction($transactionData);
                
                Log::info('Transaction posted successfully', [
                    'response' => $response,
                    'bill_id' => $bill->id,
                    'control_number' => $bill->control_number,
                    'reference_number' => $response['reference_number'] ?? null
                ]);

                $this->logger->logTransactionRecord([
                    'reference_number' => $response['reference_number'] ?? null,
                    'transaction_type' => 'payment',
                    'amount' => $amount,
                    'debit_account' => $debitAccountCode,
                    'credit_account' => $creditAccountCode,
                    'bill_id' => $bill->id,
                    'control_number' => $bill->control_number
                ]);


                if($service->code == 'SHC'){
                    $sub_product = DB::table('sub_products')->where('product_account', $service->debit_account)->first();
                    Log::info('Sub product details', [
                        'service_code' => $service->code,
                        'product_account' => $service->debit_account,
                        'sub_product' => $sub_product,
                        'service_id' => $service->id,
                        'bill_id' => $bill->id,
                        'control_number' => $bill->control_number
                    ]);
    
                    $shareIssuanceService = new ShareIssuanceService(new TransactionPostingService());
    
                    $data = [
                        'product_id' => $sub_product->id,
                        'client_number' => $bill->client_number,
                        'number_of_shares' => $sub_product->minimum_required_shares,
                        'price_per_share' =>  $sub_product->nominal_price,
                        'total_value' => $amount,
                        'linked_savings_account' => $credit_account,
                        'share_account' => $debit_account,
                        'reference_number' => '1000'
                    ];
    
                    $result = $shareIssuanceService->issueShares($data);
    
                    if ($result['success']) {
                        session()->flash('success', $result['message']);
                        Log::info('Share issuance successful', [
                            'data' => $data,
                            'result' => $result
                        ]);
                        // Handle success
                    } else {
                        if (isset($result['errors'])) {
                            Log::error('Share issuance failed', [
                                'data' => $data,
                                'result' => $result
                            ]);
                            session()->flash('validation_errors', $result['errors']);
                        } else {
                            Log::error('Share issuance failed', [
                                'data' => $data,
                                'result' => $result
                            ]);
                            session()->flash('error', $result['message']);
                        }
                    }
                }
                






                session()->flash('message', json_encode($response));
                return $response;

            } catch (\Exception $e) {
                Log::error('Transaction posting failed', [
                    'error' => $e->getMessage(),
                    'transaction_data' => $transactionData,
                    'bill_id' => $bill->id,
                    'trace' => $e->getTraceAsString()
                ]);
                throw $e;
            }

        } catch (\Exception $e) {
            Log::error('Transaction processing failed', [
                'error' => $e->getMessage(),
                'bill_id' => $bill->id,
                'debit_account' => $debitAccountCode ?? null,
                'credit_account' => $creditAccountCode ?? null,
                'amount' => $amount,
                'trace' => $e->getTraceAsString()
            ]);

            $this->logger->logError($e, [
                'debit_account' => $debitAccountCode ?? null,
                'credit_account' => $creditAccountCode ?? null,
                'amount' => $amount,
                'bill_id' => $bill->id,
                'control_number' => $bill->control_number,
                'error_message' => $e->getMessage()
            ]);
            
            session()->flash('error', 'Transaction failed: ' . $e->getMessage());
            throw $e;
        }
    }
} 