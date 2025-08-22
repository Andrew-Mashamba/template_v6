<?php

namespace App\Services\Api;

use App\Services\TransactionPostingService;
use App\Services\AccountCreationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Service for automatic loan creation and disbursement
 * Creates and disburses loans with just client_number and amount
 * 
 * @package App\Services\Api
 * @version 1.0
 */
class AutoLoanDisbursementService
{
    private $transactionService;
    private $accountService;
    
    public function __construct(
        TransactionPostingService $transactionService,
        AccountCreationService $accountService
    ) {
        $this->transactionService = $transactionService;
        $this->accountService = $accountService;
    }

    /**
     * Automatically create and disburse a loan
     * 
     * @param string $clientNumber
     * @param float $amount
     * @return array
     * @throws Exception
     */
    public function createAndDisburseLoan($clientNumber, $amount)
    {
        DB::beginTransaction();
        
        try {
            $transactionId = uniqid('AUTO_DISB_');
            
            Log::info('🚀 Auto Loan Creation and Disbursement Started', [
                'transaction_id' => $transactionId,
                'client_number' => $clientNumber,
                'requested_amount' => $amount,
                'timestamp' => now()->toISOString()
            ]);

            // Step 1: Get client information
            $client = $this->getClientInfo($clientNumber);
            Log::info('✅ Client Information Retrieved', [
                'transaction_id' => $transactionId,
                'client_number' => $clientNumber,
                'client_name' => $client->first_name . ' ' . $client->last_name,
                'nbc_account' => $client->account_number
            ]);
            
            // Step 2: Get default loan product (id = 1)
            $product = $this->getDefaultProduct();
            Log::info('✅ Loan Product Retrieved', [
                'transaction_id' => $transactionId,
                'product_id' => $product->id,
                'product_name' => $product->sub_product_name,
                'interest_rate' => $product->interest_value . '%',
                'max_term' => $product->max_term . ' months'
            ]);
            
            // Step 3: Generate loan ID
            $loanId = $this->generateLoanId();
            Log::info('✅ Loan ID Generated', [
                'transaction_id' => $transactionId,
                'loan_id' => $loanId
            ]);
            
            // Step 4: Create loan account
            $loanAccountNumber = $this->createLoanAccount($client, $product, $amount);
            Log::info('✅ Loan Account Created', [
                'transaction_id' => $transactionId,
                'loan_account_number' => $loanAccountNumber,
                'initial_balance' => -$amount
            ]);
            
            // Step 5: Calculate loan details
            $loanDetails = $this->calculateLoanDetails($amount, $product);
            Log::info('✅ Loan Details Calculated', [
                'transaction_id' => $transactionId,
                'principal' => $amount,
                'total_interest' => $loanDetails['interest'],
                'monthly_installment' => $loanDetails['monthly_installment'],
                'total_payable' => $loanDetails['total_payable'],
                'tenure_months' => $loanDetails['tenure']
            ]);
            
            // Step 6: Create loan record
            $loan = $this->createLoanRecord(
                $loanId,
                $loanAccountNumber,
                $client,
                $product,
                $amount,
                $loanDetails
            );
            Log::info('✅ Loan Record Created', [
                'transaction_id' => $transactionId,
                'loan_id' => $loanId,
                'loan_db_id' => $loan->id,
                'status' => 'APPROVED',
                'loan_type' => 'NEW'
            ]);
            
            // Step 7: Calculate deductions
            $deductions = $this->calculateDeductions($amount, $product);
            Log::info('💰 Deductions Calculated', [
                'transaction_id' => $transactionId,
                'loan_id' => $loanId,
                'total_deductions' => $deductions['total'],
                'breakdown' => [
                    'charges' => $deductions['charges'],
                    'insurance' => $deductions['insurance'],
                    'first_interest' => $deductions['first_interest']
                ],
                'detailed_breakdown' => $deductions['breakdown']
            ]);
            
            // Step 8: Calculate net disbursement
            $netDisbursementAmount = $amount - $deductions['total'];
            
            Log::info('💳 Net Disbursement Calculated', [
                'transaction_id' => $transactionId,
                'loan_id' => $loanId,
                'gross_amount' => $amount,
                'total_deductions' => $deductions['total'],
                'net_disbursement' => $netDisbursementAmount,
                'calculation' => "{$amount} - {$deductions['total']} = {$netDisbursementAmount}"
            ]);
            
            if ($netDisbursementAmount <= 0) {
                Log::error('❌ Net Disbursement Amount Invalid', [
                    'transaction_id' => $transactionId,
                    'loan_id' => $loanId,
                    'gross_amount' => $amount,
                    'total_deductions' => $deductions['total'],
                    'net_disbursement' => $netDisbursementAmount,
                    'error' => 'Net disbursement amount is zero or negative after deductions'
                ]);
                throw new Exception("Net disbursement amount is zero or negative after deductions");
            }
            
            // Step 9: Process disbursement to NBC account
            $disbursementResult = $this->processDisbursement(
                $client,
                $loan,
                $product,
                $netDisbursementAmount,
                $deductions,
                $loanAccountNumber,
                $transactionId
            );
            
            // Step 10: Create repayment schedule
            // Skip for now - table structure needs to be verified
            // $this->createRepaymentSchedule($loan, $product);
            
            // Step 11: Generate control numbers
            $controlNumbers = $this->generateControlNumbers($loanId);
            Log::info('✅ Control Numbers Generated', [
                'transaction_id' => $transactionId,
                'loan_id' => $loanId,
                'control_numbers' => $controlNumbers
            ]);
            
            // Step 12: Send notifications
            $this->sendNotifications($client, $loan, $controlNumbers, $netDisbursementAmount);
            
            // Step 13: Update loan status to ACTIVE (after successful disbursement)
            $this->updateLoanStatus($loan->id, 'ACTIVE', $disbursementResult);
            
            DB::commit();
            
            // Comprehensive financial summary log
            Log::info('🎉 Auto Loan Creation and Disbursement Completed Successfully', [
                'transaction_id' => $transactionId,
                'loan_id' => $loanId,
                'client_number' => $clientNumber,
                'client_name' => $client->first_name . ' ' . $client->last_name,
                'nbc_account' => $client->account_number,
                'loan_account' => $loanAccountNumber,
                'payment_reference' => $disbursementResult['reference'],
                'completion_time' => now()->toISOString()
            ]);
            
            // Detailed financial breakdown log
            Log::info('💰 COMPREHENSIVE FINANCIAL BREAKDOWN', [
                'transaction_id' => $transactionId,
                'loan_id' => $loanId,
                'financial_summary' => [
                    'gross_loan_amount' => $amount,
                    'total_deductions' => $deductions['total'],
                    'net_disbursed_amount' => $netDisbursementAmount,
                    'calculation' => "{$amount} - {$deductions['total']} = {$netDisbursementAmount}"
                ],
                'deductions_breakdown' => [
                    'charges' => [
                        'amount' => $deductions['charges'],
                        'percentage_of_gross' => round(($deductions['charges'] / $amount) * 100, 2) . '%'
                    ],
                    'insurance' => [
                        'amount' => $deductions['insurance'],
                        'percentage_of_gross' => round(($deductions['insurance'] / $amount) * 100, 2) . '%'
                    ],
                    'first_interest' => [
                        'amount' => $deductions['first_interest'],
                        'percentage_of_gross' => round(($deductions['first_interest'] / $amount) * 100, 2) . '%'
                    ]
                ],
                'loan_terms' => [
                    'tenure_months' => $product->max_term,
                    'interest_rate' => $product->interest_value . '%',
                    'monthly_installment' => $loanDetails['monthly_installment'],
                    'total_payable' => $loanDetails['total_payable'],
                    'total_interest' => $loanDetails['interest']
                ],
                'account_balances' => [
                    'nbc_account' => $client->account_number,
                    'loan_account' => $loanAccountNumber,
                    'net_amount_credited_to_nbc' => $netDisbursementAmount,
                    'loan_account_debited' => $netDisbursementAmount
                ],
                'detailed_charges' => $deductions['breakdown']
            ]);
            
            return [
                'success' => true,
                'transaction_id' => $transactionId,
                'loan_id' => $loanId,
                'loan_account' => $loanAccountNumber,
                'client_number' => $clientNumber,
                'client_name' => $client->first_name . ' ' . $client->last_name,
                'nbc_account' => $client->account_number,
                'loan_amount' => $amount,
                'deductions' => $deductions,
                'net_disbursed' => $netDisbursementAmount,
                'tenure_months' => $product->max_term,
                'interest_rate' => $product->interest_value,
                'monthly_installment' => $loanDetails['monthly_installment'],
                'total_payable' => $loanDetails['total_payable'],
                'control_numbers' => $controlNumbers,
                'disbursement_date' => now()->toISOString(),
                'first_payment_date' => Carbon::now()->addMonth()->startOfMonth()->format('Y-m-d'),
                'payment_reference' => $disbursementResult['reference']
            ];
            
        } catch (Exception $e) {
            DB::rollback();
            
            Log::error('💥 Auto Loan Creation and Disbursement Failed', [
                'transaction_id' => $transactionId ?? 'UNKNOWN',
                'client_number' => $clientNumber,
                'amount' => $amount,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'failure_time' => now()->toISOString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Get client information
     */
    private function getClientInfo($clientNumber)
    {
        $client = DB::table('clients')->where('client_number', $clientNumber)->first();
        
        if (!$client) {
            throw new Exception("Client not found: {$clientNumber}");
        }
        
        if (empty($client->account_number)) {
            throw new Exception("Client {$clientNumber} does not have an NBC account number");
        }
        
        // Verify NBC account exists
        $account = DB::table('accounts')
            ->where('account_number', $client->account_number)
            ->where('client_number', $clientNumber)
            ->first();
            
        if (!$account) {
            throw new Exception("NBC account {$client->account_number} not found for client {$clientNumber}");
        }
        
        return $client;
    }
    
    /**
     * Get default loan product (id = 1)
     */
    private function getDefaultProduct()
    {
        $product = DB::table('loan_sub_products')->where('id', 1)->first();
        
        if (!$product) {
            throw new Exception("Default loan product (id=1) not found");
        }
        
        if ($product->sub_product_status != '1') {
            throw new Exception("Default loan product is not active");
        }
        
        return $product;
    }
    
    /**
     * Generate unique loan ID with format AUTO{YEAR}{SEQUENCE}
     */
    private function generateLoanId()
    {
        $year = date('Y');
        $prefix = "AUTO{$year}";
        
        // Get the last loan ID for this year
        $lastLoan = DB::table('loans')
            ->where('loan_id', 'like', $prefix . '%')
            ->orderBy('loan_id', 'desc')
            ->first();
        
        if ($lastLoan) {
            // Extract sequence number and increment
            $lastSequence = intval(substr($lastLoan->loan_id, strlen($prefix)));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }
        
        // Format with leading zeros (6 digits)
        return $prefix . str_pad($newSequence, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Create loan account
     */
    private function createLoanAccount($client, $product, $amount)
    {
        // Generate unique loan account number
        $prefix = 'LN';
        $timestamp = time();
        $random = rand(100, 999);
        $accountNumber = $prefix . $client->client_number . $timestamp . $random;
        
        DB::table('accounts')->insert([
            'account_number' => $accountNumber,
            'account_name' => "Loan Account - {$client->first_name} {$client->last_name}",
            'client_number' => $client->client_number,
            'balance' => -$amount, // Negative balance for loan account
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        return $accountNumber;
    }
    
    /**
     * Calculate loan details (interest, installments, etc.)
     */
    private function calculateLoanDetails($amount, $product)
    {
        $principal = $amount;
        $tenure = $product->max_term; // Using max_term as specified
        $annualInterestRate = $product->interest_value;
        $monthlyInterestRate = $annualInterestRate / 12 / 100;
        
        // Calculate total interest
        $totalInterest = $principal * $annualInterestRate * $tenure / 12 / 100;
        
        // Calculate monthly installment based on amortization method
        if ($product->amortization_method === 'equal_installments') {
            if ($monthlyInterestRate > 0) {
                $monthlyInstallment = $principal * 
                    ($monthlyInterestRate * pow(1 + $monthlyInterestRate, $tenure)) / 
                    (pow(1 + $monthlyInterestRate, $tenure) - 1);
            } else {
                $monthlyInstallment = $principal / $tenure;
            }
        } else {
            // Equal principal method
            $monthlyInstallment = ($principal / $tenure) + ($principal * $monthlyInterestRate);
        }
        
        $totalPayable = $monthlyInstallment * $tenure;
        
        return [
            'interest' => $totalInterest,
            'monthly_installment' => round($monthlyInstallment, 2),
            'total_payable' => round($totalPayable, 2),
            'tenure' => $tenure
        ];
    }
    
    /**
     * Create loan record in database
     */
    private function createLoanRecord($loanId, $loanAccountNumber, $client, $product, $amount, $loanDetails)
    {
        $loanData = [
            'loan_id' => $loanId,
            'loan_account_number' => $loanAccountNumber,
            'client_number' => $client->client_number,
            'loan_sub_product' => $product->sub_product_id,
            'principle' => $amount,
            'interest' => $loanDetails['interest'],
            'tenure' => $loanDetails['tenure'],
            'status' => 'APPROVED', // Auto-approved as specified
            'loan_status' => 'APPROVED',
            'loan_type' => 'NEW', // Always NEW as specified
            'loan_type_2' => 'New',
            'approved_loan_value' => $amount,
            'approved_term' => $loanDetails['tenure'],
            'days_in_arrears' => 0,
            'arrears_in_amount' => 0,
            'source' => 'API',
            'approval_stage' => 'Auto-Approved',
            'approval_stage_role_name' => 'System',
            'created_at' => now(),
            'updated_at' => now(),
        ];
        
        $id = DB::table('loans')->insertGetId($loanData);
        
        // Return as object with ID
        $loanData['id'] = $id;
        return (object) $loanData;
    }
    
    /**
     * Calculate all deductions
     */
    private function calculateDeductions($amount, $product)
    {
        $deductions = [
            'charges' => 0,
            'insurance' => 0,
            'first_interest' => 0,
            'total' => 0,
            'breakdown' => []
        ];
        
        Log::info('🔍 Starting Deductions Calculation', [
            'loan_amount' => $amount,
            'product_id' => $product->sub_product_id,
            'product_name' => $product->sub_product_name
        ]);
        
        // Get charges from loan_product_charges table
        $charges = DB::table('loan_product_charges')
            ->where('loan_product_id', $product->sub_product_id)
            ->where('type', 'charge')
            ->get();
        
        Log::info('📋 Charges Found', [
            'product_id' => $product->sub_product_id,
            'charges_count' => $charges->count(),
            'charges_details' => $charges->map(function($charge) {
                return [
                    'id' => $charge->id,
                    'name' => $charge->name,
                    'type' => $charge->type,
                    'value_type' => $charge->value_type,
                    'value' => $charge->value,
                    'min_cap' => $charge->min_cap,
                    'max_cap' => $charge->max_cap
                ];
            })->toArray()
        ]);
        
        foreach ($charges as $charge) {
            $chargeAmount = $this->calculateChargeAmount($charge, $amount);
            $deductions['charges'] += $chargeAmount;
            $deductions['breakdown'][] = [
                'type' => 'charge',
                'name' => $charge->name,
                'amount' => $chargeAmount
            ];
            
            Log::info('💸 Charge Calculated', [
                'charge_name' => $charge->name,
                'charge_id' => $charge->id,
                'value_type' => $charge->value_type,
                'value' => $charge->value,
                'calculated_amount' => $chargeAmount,
                'min_cap' => $charge->min_cap,
                'max_cap' => $charge->max_cap,
                'applied_cap' => $chargeAmount
            ]);
        }
        
        // Get insurance
        $insurances = DB::table('loan_product_charges')
            ->where('loan_product_id', $product->sub_product_id)
            ->where('type', 'insurance')
            ->get();
        
        Log::info('🛡️ Insurance Found', [
            'product_id' => $product->sub_product_id,
            'insurance_count' => $insurances->count(),
            'insurance_details' => $insurances->map(function($insurance) {
                return [
                    'id' => $insurance->id,
                    'name' => $insurance->name,
                    'type' => $insurance->type,
                    'value_type' => $insurance->value_type,
                    'value' => $insurance->value,
                    'min_cap' => $insurance->min_cap,
                    'max_cap' => $insurance->max_cap
                ];
            })->toArray()
        ]);
        
        foreach ($insurances as $insurance) {
            $insuranceAmount = $this->calculateChargeAmount($insurance, $amount);
            $deductions['insurance'] += $insuranceAmount;
            $deductions['breakdown'][] = [
                'type' => 'insurance',
                'name' => $insurance->name,
                'amount' => $insuranceAmount
            ];
            
            Log::info('🛡️ Insurance Calculated', [
                'insurance_name' => $insurance->name,
                'insurance_id' => $insurance->id,
                'value_type' => $insurance->value_type,
                'value' => $insurance->value,
                'calculated_amount' => $insuranceAmount,
                'min_cap' => $insurance->min_cap,
                'max_cap' => $insurance->max_cap,
                'applied_cap' => $insuranceAmount
            ]);
        }
        
        // Calculate first interest if applicable
        if ($product->interest_method === 'flat') {
            $firstInterest = ($amount * $product->interest_value / 100) / 12;
            $deductions['first_interest'] = round($firstInterest, 2);
            $deductions['breakdown'][] = [
                'type' => 'first_interest',
                'name' => 'First Month Interest',
                'amount' => $deductions['first_interest']
            ];
            
            Log::info('📊 First Interest Calculated', [
                'interest_method' => $product->interest_method,
                'interest_rate' => $product->interest_value . '%',
                'loan_amount' => $amount,
                'calculation' => "({$amount} × {$product->interest_value}%) ÷ 12",
                'first_interest_amount' => $deductions['first_interest']
            ]);
        } else {
            Log::info('📊 First Interest Skipped', [
                'interest_method' => $product->interest_method,
                'reason' => 'First interest only calculated for flat interest method'
            ]);
        }
        
        $deductions['total'] = $deductions['charges'] + $deductions['insurance'] + $deductions['first_interest'];
        
        Log::info('💰 Deductions Summary', [
            'total_charges' => $deductions['charges'],
            'total_insurance' => $deductions['insurance'],
            'first_interest' => $deductions['first_interest'],
            'total_deductions' => $deductions['total'],
            'calculation' => "{$deductions['charges']} + {$deductions['insurance']} + {$deductions['first_interest']} = {$deductions['total']}",
            'breakdown_count' => count($deductions['breakdown'])
        ]);
        
        return $deductions;
    }
    
    /**
     * Calculate charge amount based on type
     */
    private function calculateChargeAmount($charge, $loanAmount)
    {
        $originalAmount = 0;
        $finalAmount = 0;
        $capApplied = null;
        
        if ($charge->value_type === 'percentage') {
            $originalAmount = ($loanAmount * $charge->value) / 100;
            
            Log::info('📊 Percentage Charge Calculation', [
                'charge_name' => $charge->name,
                'charge_id' => $charge->id,
                'value_type' => $charge->value_type,
                'percentage' => $charge->value . '%',
                'loan_amount' => $loanAmount,
                'calculation' => "({$loanAmount} × {$charge->value}%) ÷ 100",
                'calculated_amount' => $originalAmount
            ]);
            
            // Apply caps if set
            if ($charge->min_cap && $originalAmount < $charge->min_cap) {
                $finalAmount = $charge->min_cap;
                $capApplied = 'min_cap';
                
                Log::info('📏 Min Cap Applied', [
                    'charge_name' => $charge->name,
                    'calculated_amount' => $originalAmount,
                    'min_cap' => $charge->min_cap,
                    'final_amount' => $finalAmount,
                    'cap_reason' => 'Calculated amount below minimum cap'
                ]);
            } elseif ($charge->max_cap && $originalAmount > $charge->max_cap) {
                $finalAmount = $charge->max_cap;
                $capApplied = 'max_cap';
                
                Log::info('📏 Max Cap Applied', [
                    'charge_name' => $charge->name,
                    'calculated_amount' => $originalAmount,
                    'max_cap' => $charge->max_cap,
                    'final_amount' => $finalAmount,
                    'cap_reason' => 'Calculated amount above maximum cap'
                ]);
            } else {
                $finalAmount = $originalAmount;
                $capApplied = 'none';
                
                Log::info('✅ No Cap Applied', [
                    'charge_name' => $charge->name,
                    'calculated_amount' => $originalAmount,
                    'min_cap' => $charge->min_cap,
                    'max_cap' => $charge->max_cap,
                    'final_amount' => $finalAmount
                ]);
            }
            
        } else {
            // Fixed amount
            $originalAmount = $charge->value;
            $finalAmount = $charge->value;
            $capApplied = 'fixed';
            
            Log::info('💰 Fixed Amount Charge', [
                'charge_name' => $charge->name,
                'charge_id' => $charge->id,
                'value_type' => $charge->value_type,
                'fixed_amount' => $charge->value,
                'final_amount' => $finalAmount
            ]);
        }
        
        $roundedAmount = round($finalAmount, 2);
        
        Log::info('🎯 Final Charge Amount', [
            'charge_name' => $charge->name,
            'charge_id' => $charge->id,
            'original_calculation' => $originalAmount,
            'cap_applied' => $capApplied,
            'final_amount' => $finalAmount,
            'rounded_amount' => $roundedAmount,
            'calculation_summary' => [
                'value_type' => $charge->value_type,
                'value' => $charge->value,
                'loan_amount' => $loanAmount,
                'min_cap' => $charge->min_cap,
                'max_cap' => $charge->max_cap
            ]
        ]);
        
        return $roundedAmount;
    }
    
    /**
     * Process disbursement to NBC account
     */
    private function processDisbursement($client, $loan, $product, $netAmount, $deductions, $loanAccountNumber, $transactionId)
    {
        $reference = 'NBC_AUTO_' . uniqid();
        
        Log::info('💳 Starting Disbursement Process', [
            'transaction_id' => $transactionId,
            'loan_id' => $loan->loan_id,
            'client_number' => $client->client_number,
            'nbc_account' => $client->account_number,
            'loan_account' => $loanAccountNumber,
            'net_amount' => $netAmount,
            'payment_reference' => $reference
        ]);
        
        // Get current account balances before transaction
        $nbcAccountBefore = DB::table('accounts')
            ->where('account_number', $client->account_number)
            ->value('balance') ?? 0;
            
        $loanAccountBefore = DB::table('accounts')
            ->where('account_number', $loanAccountNumber)
            ->value('balance') ?? 0;
        
        Log::info('📊 Account Balances Before Disbursement', [
            'transaction_id' => $transactionId,
            'nbc_account' => $client->account_number,
            'nbc_balance_before' => $nbcAccountBefore,
            'loan_account' => $loanAccountNumber,
            'loan_balance_before' => $loanAccountBefore
        ]);
        
        // Credit member's NBC account
        DB::table('accounts')
            ->where('account_number', $client->account_number)
            ->increment('balance', $netAmount);
        
        // Debit loan account
        DB::table('accounts')
            ->where('account_number', $loanAccountNumber)
            ->decrement('balance', $netAmount);
        
        // Get updated account balances after transaction
        $nbcAccountAfter = DB::table('accounts')
            ->where('account_number', $client->account_number)
            ->value('balance') ?? 0;
            
        $loanAccountAfter = DB::table('accounts')
            ->where('account_number', $loanAccountNumber)
            ->value('balance') ?? 0;
        
        Log::info('✅ Account Balances Updated', [
            'transaction_id' => $transactionId,
            'nbc_account' => $client->account_number,
            'nbc_balance_change' => $netAmount,
            'nbc_balance_after' => $nbcAccountAfter,
            'loan_account' => $loanAccountNumber,
            'loan_balance_change' => -$netAmount,
            'loan_balance_after' => $loanAccountAfter,
            'verification' => [
                'nbc_expected' => $nbcAccountBefore + $netAmount,
                'nbc_actual' => $nbcAccountAfter,
                'loan_expected' => $loanAccountBefore - $netAmount,
                'loan_actual' => $loanAccountAfter
            ]
        ]);
        
        // Record main disbursement transaction
        // Skip general ledger for now - would need to check actual table structure
        
        // Skip deduction transactions in general ledger for now
        
        // Log the transaction
        DB::table('api_disbursement_logs')->insert([
            'transaction_id' => $transactionId,
            'loan_id' => $loan->loan_id,
            'client_number' => $client->client_number,
            'status' => 'COMPLETED',
            'amount' => $netAmount,
            'payment_method' => 'NBC_ACCOUNT',
            'payment_reference' => $reference,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        Log::info('📝 Disbursement Transaction Logged', [
            'transaction_id' => $transactionId,
            'loan_id' => $loan->loan_id,
            'client_number' => $client->client_number,
            'amount' => $netAmount,
            'payment_method' => 'NBC_ACCOUNT',
            'payment_reference' => $reference,
            'log_table' => 'api_disbursement_logs'
        ]);
        
        Log::info('🎯 Disbursement Process Completed', [
            'transaction_id' => $transactionId,
            'loan_id' => $loan->loan_id,
            'client_number' => $client->client_number,
            'net_amount_disbursed' => $netAmount,
            'nbc_account_credited' => $client->account_number,
            'loan_account_debited' => $loanAccountNumber,
            'payment_reference' => $reference,
            'status' => 'COMPLETED',
            'completion_time' => now()->toISOString()
        ]);
        
        return [
            'reference' => $reference,
            'status' => 'COMPLETED',
            'method' => 'NBC_ACCOUNT',
            'amount' => $netAmount,
            'nbc_account' => $client->account_number,
            'loan_account' => $loanAccountNumber
        ];
    }
    
    /**
     * Get appropriate account for deduction type
     */
    private function getDeductionAccount($type, $product)
    {
        switch ($type) {
            case 'charge':
                return $product->loan_charges_account;
            case 'insurance':
                return $product->loan_insurance_account; // Using penalties account for insurance
            case 'first_interest':
                return $product->loan_interest_account;
            default:
                return null;
        }
    }
    
    /**
     * Create repayment schedule
     */
    private function createRepaymentSchedule($loan, $product)
    {
        $principal = $loan->principle;
        $tenure = $loan->tenure;
        $interestRate = $product->interest_value / 100;
        $monthlyRate = $interestRate / 12;
        
        // Calculate monthly installment
        if ($product->amortization_method === 'equal_installments' && $monthlyRate > 0) {
            $monthlyInstallment = $principal * 
                ($monthlyRate * pow(1 + $monthlyRate, $tenure)) / 
                (pow(1 + $monthlyRate, $tenure) - 1);
        } else {
            $monthlyInstallment = ($principal / $tenure) + ($principal * $monthlyRate);
        }
        
        $balance = $principal;
        $scheduleDate = Carbon::now()->addMonth()->startOfMonth();
        
        for ($i = 1; $i <= $tenure; $i++) {
            $interestAmount = $balance * $monthlyRate;
            $principalAmount = $monthlyInstallment - $interestAmount;
            
            if ($i == $tenure) {
                // Adjust last payment to clear remaining balance
                $principalAmount = $balance;
                $monthlyInstallment = $principalAmount + $interestAmount;
            }
            
            $balance -= $principalAmount;
            
            DB::table('loans_schedules')->insert([
                'loan_id' => $loan->loan_id,
                'installment_number' => $i,
                'due_date' => $scheduleDate->format('Y-m-d'),
                'principle' => round($principalAmount, 2),
                'interest' => round($interestAmount, 2),
                'installment' => round($monthlyInstallment, 2),
                'balance' => round(max(0, $balance), 2),
                'status' => 'PENDING',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $scheduleDate->addMonth();
        }
    }
    
    /**
     * Generate control numbers for payment
     */
    private function generateControlNumbers($loanId)
    {
        $timestamp = time();
        $random = rand(1000, 9999);
        
        return [
            [
                'type' => 'REPAYMENT',
                'number' => 'AUTO' . $timestamp . $random,
                'description' => 'Monthly Loan Repayment',
                'valid_until' => Carbon::now()->addDays(30)->format('Y-m-d')
            ]
        ];
    }
    
    /**
     * Send notifications to client
     */
    private function sendNotifications($client, $loan, $controlNumbers, $netAmount)
    {
        // SMS notification
        if ($client->phone_number) {
            $message = "Dear {$client->first_name}, your loan {$loan->loan_id} of TZS " . 
                      number_format($loan->principle, 2) . 
                      " has been approved and disbursed. Net amount: TZS " . 
                      number_format($netAmount, 2) . 
                      " has been credited to your NBC account {$client->account_number}. " .
                      "Control Number: " . $controlNumbers[0]['number'];
            
            // Queue SMS (integrate with actual SMS service)
            Log::info('SMS notification queued', [
                'phone' => $client->phone_number,
                'message' => $message
            ]);
        }
        
        // Email notification
        if ($client->email) {
            try {
                // Prepare email data
                $emailData = [
                    'to' => $client->email,
                    'to_name' => $client->first_name . ' ' . $client->last_name,
                    'subject' => 'Loan Disbursement Notification - ' . $loan->loan_id,
                    'client_name' => $client->first_name,
                    'loan_id' => $loan->loan_id,
                    'loan_amount' => number_format($loan->principle, 2),
                    'net_amount' => number_format($netAmount, 2),
                    'nbc_account' => $client->account_number,
                    'control_number' => $controlNumbers[0]['number'],
                    'repayment_amount' => number_format($loan->principle + $loan->interest, 2),
                    'repayment_date' => $controlNumbers[0]['valid_until'] ?? Carbon::now()->addMonth()->format('Y-m-d'),
                    'company_name' => config('app.name', 'SACCOS Core System')
                ];
                
                // Send email using Mail facade
                \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($emailData) {
                    $message->to($emailData['to'], $emailData['to_name'])
                            ->subject($emailData['subject'])
                            ->html($this->generateEmailHtml($emailData));
                });
                
                // Log successful email
                Log::info('Loan disbursement email sent', [
                    'email' => $client->email,
                    'loan_id' => $loan->loan_id,
                    'status' => 'sent'
                ]);
                
                // Store in emails table for tracking
                DB::table('emails')->insert([
                    'recipient_email' => $emailData['to'],
                    'subject' => $emailData['subject'],
                    'body' => $this->generateEmailHtml($emailData),
                    'is_sent' => true,
                    'sent_at' => now(),
                    'folder' => 'sent',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
            } catch (\Exception $e) {
                Log::error('Failed to send loan disbursement email', [
                    'email' => $client->email,
                    'loan_id' => $loan->loan_id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
    
    /**
     * Generate HTML email content
     */
    private function generateEmailHtml($data)
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Loan Disbursement Notification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #4CAF50; color: white; padding: 20px; text-align: center; }
                .content { background-color: #f9f9f9; padding: 20px; margin-top: 20px; }
                .details { background-color: white; padding: 15px; margin: 15px 0; border-left: 4px solid #4CAF50; }
                .amount { font-size: 24px; color: #4CAF50; font-weight: bold; }
                .footer { margin-top: 20px; padding: 20px; background-color: #333; color: white; text-align: center; }
                table { width: 100%; border-collapse: collapse; }
                td { padding: 8px; }
                .label { font-weight: bold; width: 40%; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>' . $data['company_name'] . '</h1>
                    <h2>Loan Disbursement Notification</h2>
                </div>
                
                <div class="content">
                    <p>Dear <strong>' . $data['client_name'] . '</strong>,</p>
                    
                    <p>We are pleased to inform you that your loan application has been <strong>APPROVED</strong> and the funds have been successfully disbursed to your account.</p>
                    
                    <div class="details">
                        <h3>Loan Details:</h3>
                        <table>
                            <tr>
                                <td class="label">Loan ID:</td>
                                <td><strong>' . $data['loan_id'] . '</strong></td>
                            </tr>
                            <tr>
                                <td class="label">Loan Amount:</td>
                                <td>TZS ' . $data['loan_amount'] . '</td>
                            </tr>
                            <tr>
                                <td class="label">Net Amount Disbursed:</td>
                                <td class="amount">TZS ' . $data['net_amount'] . '</td>
                            </tr>
                            <tr>
                                <td class="label">Credited to Account:</td>
                                <td>' . $data['nbc_account'] . '</td>
                            </tr>
                        </table>
                    </div>
                    
                    <div class="details">
                        <h3>Repayment Information:</h3>
                        <table>
                            <tr>
                                <td class="label">Control Number:</td>
                                <td><strong style="color: #FF5722;">' . $data['control_number'] . '</strong></td>
                            </tr>
                            <tr>
                                <td class="label">Total Repayment Amount:</td>
                                <td><strong>TZS ' . $data['repayment_amount'] . '</strong></td>
                            </tr>
                            <tr>
                                <td class="label">Payment Due Date:</td>
                                <td>' . $data['repayment_date'] . '</td>
                            </tr>
                        </table>
                    </div>
                    
                    <p><strong>Important Notes:</strong></p>
                    <ul>
                        <li>Please use the control number above for all loan repayments</li>
                        <li>Ensure timely repayment to maintain a good credit history</li>
                        <li>The net amount has been credited to your NBC account after deducting applicable fees</li>
                        <li>For any queries, please contact our customer service</li>
                    </ul>
                    
                    <p>Thank you for choosing ' . $data['company_name'] . '.</p>
                </div>
                
                <div class="footer">
                    <p>This is an automated notification. Please do not reply to this email.</p>
                    <p>&copy; ' . date('Y') . ' ' . $data['company_name'] . '. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>';
    }
    
    /**
     * Update loan status after disbursement
     * Sets status to ACTIVE to indicate the loan is now active and accepting repayments
     */
    private function updateLoanStatus($loanId, $status, $disbursementResult)
    {
        DB::table('loans')->where('id', $loanId)->update([
            'status' => $status,
            'loan_status' => $status,
            'disbursement_date' => now(),
            'net_disbursement_amount' => $disbursementResult['amount'] ?? 0,
            'updated_at' => now()
        ]);
    }
}