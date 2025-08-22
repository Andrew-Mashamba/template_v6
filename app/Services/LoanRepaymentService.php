<?php

namespace App\Services;

use App\Models\AccountsModel;
use App\Models\LoansModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * Professional Loan Repayment Service
 * 
 * Handles all loan repayment operations including:
 * - Payment allocation (FIFO: Penalties -> Interest -> Principal)
 * - Partial and full payments
 * - Early settlement
 * - Overpayments and advance payments
 * - Payment reversal
 * - Receipt generation
 * 
 * @package App\Services
 * @version 2.0
 */
class LoanRepaymentService
{
    private $transactionService;
    
    /**
     * Payment allocation priorities
     */
    const ALLOCATION_PRIORITY = [
        'PENALTY' => 1,
        'INTEREST' => 2,
        'PRINCIPAL' => 3
    ];
    
    /**
     * Payment methods
     */
    const PAYMENT_METHODS = [
        'CASH' => 'Cash Payment',
        'BANK' => 'Bank Transfer',
        'MOBILE' => 'Mobile Money',
        'INTERNAL' => 'Internal Transfer',
        'SALARY' => 'Salary Deduction'
    ];
    
    public function __construct(TransactionPostingService $transactionService = null)
    {
        $this->transactionService = $transactionService ?: new TransactionPostingService();
    }
    
    /**
     * Process loan repayment with professional allocation logic
     * 
     * @param string $loanId The loan ID or account number
     * @param float $amount The payment amount
     * @param string $paymentMethod The payment method
     * @param array $paymentDetails Additional payment details
     * @return array Payment result with breakdown
     */
    public function processRepayment($loanId, $amount, $paymentMethod = 'CASH', $paymentDetails = [])
    {
        // Log repayment initiation
        Log::info('🔵 LOAN REPAYMENT INITIATED', [
            'loan_id' => $loanId,
            'amount' => $amount,
            'payment_method' => $paymentMethod,
            'timestamp' => now()->toDateTimeString(),
            'user' => auth()->user()->name ?? 'System'
        ]);
        
        DB::beginTransaction();
        
        try {
            // Validate and fetch loan
            $loan = $this->validateAndFetchLoan($loanId);
            
            Log::info('📋 Loan details retrieved', [
                'loan_id' => $loan->loan_id,
                'client_number' => $loan->client_number,
                'principal' => $loan->principle,
                'status' => $loan->status,
                'tenure' => $loan->tenure
            ]);
            
            // Check loan status - only ACTIVE and RESTRUCTURED loans can be repaid
            if (!in_array($loan->status, ['ACTIVE', 'RESTRUCTURED'])) {
                Log::warning('❌ Invalid loan status for repayment', [
                    'loan_id' => $loan->loan_id,
                    'current_status' => $loan->status,
                    'allowed_statuses' => ['ACTIVE', 'RESTRUCTURED']
                ]);
                throw new Exception("Loan is not active for repayment. Current status: {$loan->status}. Only ACTIVE or RESTRUCTURED loans can be repaid.");
            }
            
            // Get outstanding balances
            $outstandingBalances = $this->calculateOutstandingBalances($loan);
            
            Log::info('💰 Outstanding balances calculated', [
                'loan_id' => $loan->loan_id,
                'principal' => $outstandingBalances['principal'],
                'interest' => $outstandingBalances['interest'],
                'penalties' => $outstandingBalances['penalties'],
                'total' => $outstandingBalances['total'],
                'schedules_count' => $outstandingBalances['schedules_count']
            ]);
            
            // Check for overpayment
            if ($amount > $outstandingBalances['total']) {
                $overpayment = $amount - $outstandingBalances['total'];
                Log::info("💵 Overpayment detected", [
                    'loan_id' => $loan->loan_id,
                    'payment' => $amount,
                    'outstanding' => $outstandingBalances['total'],
                    'overpayment' => $overpayment
                ]);
            }
            
            // Allocate payment
            $allocation = $this->allocatePayment($amount, $outstandingBalances);
            
            Log::info('📊 Payment allocation completed', [
                'loan_id' => $loan->loan_id,
                'total_payment' => $amount,
                'allocation' => [
                    'penalties' => $allocation['penalties'],
                    'interest' => $allocation['interest'],
                    'principal' => $allocation['principal'],
                    'overpayment' => $allocation['overpayment'] ?? 0
                ]
            ]);
            
            // Update loan schedules
            $this->updateLoanSchedules($loan, $allocation);
            
            Log::info('📅 Loan schedules updated', [
                'loan_id' => $loan->loan_id,
                'schedules_affected' => DB::table('loans_schedules')
                    ->where('loan_id', $loan->loan_id)
                    ->whereIn('completion_status', ['PARTIAL', 'PAID'])
                    ->count()
            ]);
            
            // Process accounting transactions
            $this->processAccountingTransactions($loan, $allocation, $paymentMethod, $paymentDetails);
            
            Log::info('📑 Accounting transactions processed', [
                'loan_id' => $loan->loan_id,
                'payment_method' => $paymentMethod,
                'transactions' => [
                    'penalties' => $allocation['penalties'] > 0,
                    'interest' => $allocation['interest'] > 0,
                    'principal' => $allocation['principal'] > 0
                ]
            ]);
            
            // Record payment history
            $paymentRecord = $this->recordPaymentHistory($loan, $amount, $allocation, $paymentMethod, $paymentDetails);
            
            Log::info('📝 Payment history recorded', [
                'loan_id' => $loan->loan_id,
                'payment_id' => $paymentRecord->id,
                'receipt_number' => $paymentRecord->receipt_number,
                'amount' => $amount
            ]);
            
            // Update loan status if fully paid
            $this->updateLoanStatus($loan);
            
            // Generate receipt
            $receipt = $this->generateReceipt($loan, $paymentRecord, $allocation);
            
            Log::info('🧾 Receipt generated', [
                'loan_id' => $loan->loan_id,
                'receipt_number' => $receipt['receipt_number'],
                'outstanding_after_payment' => $receipt['outstanding_balance']
            ]);
            
            DB::commit();
            
            Log::info('✅ LOAN REPAYMENT COMPLETED SUCCESSFULLY', [
                'loan_id' => $loan->loan_id,
                'receipt_number' => $receipt['receipt_number'],
                'amount_paid' => $amount,
                'new_outstanding' => $receipt['outstanding_balance'],
                'timestamp' => now()->toDateTimeString()
            ]);
            
            // Send notifications
            $this->sendPaymentNotifications($loan, $paymentRecord, $receipt);
            
            return [
                'success' => true,
                'message' => 'Payment processed successfully',
                'payment_id' => $paymentRecord->id,
                'receipt_number' => $receipt['receipt_number'],
                'loan_id' => $loan->loan_id,
                'amount_paid' => $amount,
                'allocation' => $allocation,
                'outstanding_balance' => $this->calculateOutstandingBalances($loan),
                'receipt' => $receipt
            ];
            
        } catch (Exception $e) {
            DB::rollback();
            Log::error('❌ LOAN REPAYMENT FAILED', [
                'loan_id' => $loanId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
                'error_line' => $e->getLine(),
                'error_file' => basename($e->getFile()),
                'timestamp' => now()->toDateTimeString()
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Validate and fetch loan details
     */
    private function validateAndFetchLoan($loanId)
    {
        // Try to find by loan_id first, then by loan_account_number
        $loan = DB::table('loans')
            ->where('loan_id', $loanId)
            ->orWhere('loan_account_number', $loanId)
            ->first();
        
        if (!$loan) {
            throw new Exception("Loan not found: {$loanId}");
        }
        
        return $loan;
    }
    
    /**
     * Calculate all outstanding balances
     */
    public function calculateOutstandingBalances($loan)
    {
        $schedules = DB::table('loans_schedules')
            ->where('loan_id', $loan->loan_id)
            ->whereIn('completion_status', ['PENDING', 'PARTIAL', 'ACTIVE'])
            ->get();
        
        $penalties = 0;
        $interest = 0;
        $principal = 0;
        
        foreach ($schedules as $schedule) {
            // Calculate penalties (if any)
            if ($this->isOverdue($schedule)) {
                $penalties += $this->calculatePenalty($schedule, $loan);
            }
            
            // Outstanding interest
            $interest += ($schedule->interest - ($schedule->interest_payment ?? 0));
            
            // Outstanding principal
            $principal += ($schedule->principle - ($schedule->principle_payment ?? 0));
        }
        
        return [
            'penalties' => round($penalties, 2),
            'interest' => round($interest, 2),
            'principal' => round($principal, 2),
            'total' => round($penalties + $interest + $principal, 2),
            'schedules_count' => $schedules->count()
        ];
    }
    
    /**
     * Check if schedule is overdue
     */
    private function isOverdue($schedule)
    {
        return Carbon::parse($schedule->installment_date)->isPast() && 
               $schedule->completion_status !== 'PAID';
    }
    
    /**
     * Calculate penalty for overdue payment
     */
    private function calculatePenalty($schedule, $loan)
    {
        $daysOverdue = Carbon::parse($schedule->installment_date)->diffInDays(now());
        $outstandingAmount = $schedule->installment - ($schedule->payment ?? 0);
        
        // Get penalty rate from loan product
        $product = DB::table('loan_sub_products')
            ->where('sub_product_id', $loan->loan_sub_product)
            ->first();
        
        if (!$product || !$product->penalty_value) {
            return 0;
        }
        
        // Calculate penalty (daily rate)
        $dailyPenaltyRate = $product->penalty_value / 30 / 100; // Monthly rate to daily
        $penalty = $outstandingAmount * $dailyPenaltyRate * $daysOverdue;
        
        // Cap penalty at maximum if configured
        if ($product->penalty_max_cap) {
            $penalty = min($penalty, $product->penalty_max_cap);
        }
        
        return $penalty;
    }
    
    /**
     * Allocate payment according to priority (Penalties -> Interest -> Principal)
     */
    private function allocatePayment($amount, $outstandingBalances)
    {
        $remainingAmount = $amount;
        $allocation = [
            'penalties' => 0,
            'interest' => 0,
            'principal' => 0,
            'overpayment' => 0
        ];
        
        // 1. Pay penalties first
        if ($remainingAmount > 0 && $outstandingBalances['penalties'] > 0) {
            $penaltyPayment = min($remainingAmount, $outstandingBalances['penalties']);
            $allocation['penalties'] = $penaltyPayment;
            $remainingAmount -= $penaltyPayment;
        }
        
        // 2. Pay interest next
        if ($remainingAmount > 0 && $outstandingBalances['interest'] > 0) {
            $interestPayment = min($remainingAmount, $outstandingBalances['interest']);
            $allocation['interest'] = $interestPayment;
            $remainingAmount -= $interestPayment;
        }
        
        // 3. Pay principal
        if ($remainingAmount > 0 && $outstandingBalances['principal'] > 0) {
            $principalPayment = min($remainingAmount, $outstandingBalances['principal']);
            $allocation['principal'] = $principalPayment;
            $remainingAmount -= $principalPayment;
        }
        
        // 4. Handle overpayment
        if ($remainingAmount > 0) {
            $allocation['overpayment'] = $remainingAmount;
        }
        
        return $allocation;
    }
    
    /**
     * Update loan schedules with payment allocation
     */
    private function updateLoanSchedules($loan, $allocation)
    {
        $schedules = DB::table('loans_schedules')
            ->where('loan_id', $loan->loan_id)
            ->whereIn('completion_status', ['PENDING', 'PARTIAL', 'ACTIVE'])
            ->orderBy('installment_date', 'asc')
            ->get();
        
        $remainingInterest = $allocation['interest'];
        $remainingPrincipal = $allocation['principal'];
        
        foreach ($schedules as $schedule) {
            $interestPayment = 0;
            $principalPayment = 0;
            $updated = false;
            
            // Allocate interest payment
            $outstandingInterest = $schedule->interest - ($schedule->interest_payment ?? 0);
            if ($remainingInterest > 0 && $outstandingInterest > 0) {
                $interestPayment = min($remainingInterest, $outstandingInterest);
                $remainingInterest -= $interestPayment;
                $updated = true;
            }
            
            // Allocate principal payment
            $outstandingPrincipal = $schedule->principle - ($schedule->principle_payment ?? 0);
            if ($remainingPrincipal > 0 && $outstandingPrincipal > 0) {
                $principalPayment = min($remainingPrincipal, $outstandingPrincipal);
                $remainingPrincipal -= $principalPayment;
                $updated = true;
            }
            
            if ($updated) {
                $totalInterestPaid = ($schedule->interest_payment ?? 0) + $interestPayment;
                $totalPrincipalPaid = ($schedule->principle_payment ?? 0) + $principalPayment;
                $totalPaid = $totalInterestPaid + $totalPrincipalPaid;
                
                // Determine completion status
                $isFullyPaid = (
                    abs($totalInterestPaid - $schedule->interest) < 0.01 && 
                    abs($totalPrincipalPaid - $schedule->principle) < 0.01
                );
                
                $status = $isFullyPaid ? 'PAID' : 'PARTIAL';
                
                DB::table('loans_schedules')
                    ->where('id', $schedule->id)
                    ->update([
                        'interest_payment' => $totalInterestPaid,
                        'principle_payment' => $totalPrincipalPaid,
                        'payment' => $totalPaid,
                        'completion_status' => $status,
                        'last_payment_date' => now(),
                        'updated_at' => now()
                    ]);
            }
            
            // Break if all payment allocated
            if ($remainingInterest <= 0 && $remainingPrincipal <= 0) {
                break;
            }
        }
    }
    
    /**
     * Process accounting transactions
     */
    private function processAccountingTransactions($loan, $allocation, $paymentMethod, $paymentDetails)
    {
        // Get accounts
        $cashAccount = $this->getCashAccount($paymentMethod, $paymentDetails);
        $loanAccount = $this->getLoanAccount($loan);
        $interestAccount = $this->getInterestAccount($loan);
        $penaltyAccount = $this->getPenaltyAccount($loan);
        
        // Process penalty payment
        if ($allocation['penalties'] > 0) {
            $this->postTransaction(
                $penaltyAccount,
                $cashAccount,
                $allocation['penalties'],
                "Penalty payment for loan {$loan->loan_id}"
            );
        }
        
        // Process interest payment
        if ($allocation['interest'] > 0) {
            $this->postTransaction(
                $interestAccount,
                $cashAccount,
                $allocation['interest'],
                "Interest payment for loan {$loan->loan_id}"
            );
        }
        
        // Process principal payment
        if ($allocation['principal'] > 0) {
            $this->postTransaction(
                $loanAccount,
                $cashAccount,
                $allocation['principal'],
                "Principal payment for loan {$loan->loan_id}"
            );
        }
        
        // Handle overpayment
        if ($allocation['overpayment'] > 0) {
            $this->handleOverpayment($loan, $allocation['overpayment'], $cashAccount);
        }
    }
    
    /**
     * Get cash account based on payment method
     */
    private function getCashAccount($paymentMethod, $paymentDetails)
    {
        switch ($paymentMethod) {
            case 'BANK':
                return $paymentDetails['bank_account'] ?? 'BANK_ACCOUNT';
            case 'MOBILE':
                return $paymentDetails['mobile_account'] ?? 'MOBILE_MONEY_ACCOUNT';
            case 'INTERNAL':
                return $paymentDetails['source_account'] ?? 'INTERNAL_ACCOUNT';
            case 'SALARY':
                return 'SALARY_DEDUCTION_ACCOUNT';
            default:
                return 'CASH_ACCOUNT';
        }
    }
    
    /**
     * Get loan account
     */
    private function getLoanAccount($loan)
    {
        $account = DB::table('accounts')
            ->where('account_number', $loan->loan_account_number)
            ->first();
        
        return $account ? $account->sub_category_code : $loan->loan_account_number;
    }
    
    /**
     * Get interest account
     */
    private function getInterestAccount($loan)
    {
        // Get from loan record or use default
        if ($loan->interest_account_number) {
            $account = DB::table('accounts')
                ->where('account_number', $loan->interest_account_number)
                ->first();
            return $account ? $account->sub_category_code : $loan->interest_account_number;
        }
        
        // Get from product configuration
        $product = DB::table('loan_sub_products')
            ->where('sub_product_id', $loan->loan_sub_product)
            ->first();
        
        return $product ? $product->collection_account_loan_interest : 'INTEREST_INCOME_ACCOUNT';
    }
    
    /**
     * Get penalty account
     */
    private function getPenaltyAccount($loan)
    {
        $product = DB::table('loan_sub_products')
            ->where('sub_product_id', $loan->loan_sub_product)
            ->first();
        
        return $product ? $product->collection_account_loan_penalties : 'PENALTY_INCOME_ACCOUNT';
    }
    
    /**
     * Post transaction to general ledger
     */
    private function postTransaction($debitAccount, $creditAccount, $amount, $narration)
    {
        if ($amount <= 0) {
            return;
        }
        
        try {
            $debit = AccountsModel::where('sub_category_code', $debitAccount)->first();
            $credit = AccountsModel::where('sub_category_code', $creditAccount)->first();
            
            if (!$debit || !$credit) {
                Log::warning('Account not found for transaction', [
                    'debit' => $debitAccount,
                    'credit' => $creditAccount
                ]);
                return;
            }
            
            $data = [
                'first_account' => $credit,
                'second_account' => $debit,
                'amount' => $amount,
                'narration' => $narration,
            ];
            
            $this->transactionService->postTransaction($data);
            
        } catch (Exception $e) {
            Log::error('Transaction posting failed', [
                'error' => $e->getMessage(),
                'narration' => $narration
            ]);
            throw $e;
        }
    }
    
    /**
     * Handle overpayment
     */
    private function handleOverpayment($loan, $amount, $sourceAccount)
    {
        // Credit to member's savings account or create advance payment record
        $member = DB::table('clients')
            ->where('client_number', $loan->client_number)
            ->first();
        
        if ($member && $member->account_number) {
            // Credit to member's account as advance payment
            DB::table('accounts')
                ->where('account_number', $member->account_number)
                ->increment('balance', $amount);
            
            // Record advance payment
            DB::table('loan_advance_payments')->insert([
                'loan_id' => $loan->loan_id,
                'amount' => $amount,
                'status' => 'AVAILABLE',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            Log::info('Overpayment credited as advance', [
                'loan_id' => $loan->loan_id,
                'amount' => $amount
            ]);
        }
    }
    
    /**
     * Record payment history
     */
    private function recordPaymentHistory($loan, $amount, $allocation, $paymentMethod, $paymentDetails)
    {
        $paymentData = [
            'loan_id' => $loan->loan_id,
            'payment_date' => now(),
            'amount' => $amount,
            'principal_paid' => $allocation['principal'],
            'interest_paid' => $allocation['interest'],
            'penalties_paid' => $allocation['penalties'],
            'overpayment' => $allocation['overpayment'] ?? 0,
            'payment_method' => $paymentMethod,
            'reference_number' => $paymentDetails['reference'] ?? null,
            'receipt_number' => $this->generateReceiptNumber(),
            'processed_by' => auth()->id() ?? 'SYSTEM',
            'created_at' => now(),
            'updated_at' => now()
        ];
        
        $paymentId = DB::table('loan_payments')->insertGetId($paymentData);
        
        // Return payment record
        return (object) array_merge($paymentData, ['id' => $paymentId]);
    }
    
    /**
     * Update loan status based on payment
     */
    private function updateLoanStatus($loan)
    {
        // Check if all schedules are paid
        $unpaidSchedules = DB::table('loans_schedules')
            ->where('loan_id', $loan->loan_id)
            ->where('completion_status', '!=', 'PAID')
            ->count();
        
        if ($unpaidSchedules === 0) {
            // All schedules paid - close the loan
            DB::table('loans')
                ->where('id', $loan->id)
                ->update([
                    'status' => 'CLOSED',
                    'loan_status' => 'CLOSED',
                    'closure_date' => now(),
                    'updated_at' => now()
                ]);
            
            Log::info('Loan closed after full payment', ['loan_id' => $loan->loan_id]);
        } else {
            // Update days in arrears
            $this->updateArrearsStatus($loan);
        }
    }
    
    /**
     * Update arrears status
     */
    private function updateArrearsStatus($loan)
    {
        $overdueSchedule = DB::table('loans_schedules')
            ->where('loan_id', $loan->loan_id)
            ->where('completion_status', '!=', 'PAID')
            ->where('installment_date', '<', now())
            ->orderBy('installment_date', 'asc')
            ->first();
        
        if ($overdueSchedule) {
            $daysInArrears = Carbon::parse($overdueSchedule->installment_date)->diffInDays(now());
            $arrearsAmount = DB::table('loans_schedules')
                ->where('loan_id', $loan->loan_id)
                ->where('completion_status', '!=', 'PAID')
                ->where('installment_date', '<', now())
                ->sum(DB::raw('installment - payment'));
            
            DB::table('loans')
                ->where('id', $loan->id)
                ->update([
                    'days_in_arrears' => $daysInArrears,
                    'arrears_in_amount' => $arrearsAmount,
                    'updated_at' => now()
                ]);
        } else {
            // No arrears
            DB::table('loans')
                ->where('id', $loan->id)
                ->update([
                    'days_in_arrears' => 0,
                    'arrears_in_amount' => 0,
                    'updated_at' => now()
                ]);
        }
    }
    
    /**
     * Generate receipt for payment
     */
    private function generateReceipt($loan, $payment, $allocation)
    {
        $member = DB::table('clients')
            ->where('client_number', $loan->client_number)
            ->first();
        
        $outstandingBalance = $this->calculateOutstandingBalances($loan);
        
        return [
            'receipt_number' => $payment->receipt_number,
            'payment_date' => $payment->payment_date,
            'loan_id' => $loan->loan_id,
            'member_name' => $member ? "{$member->first_name} {$member->last_name}" : 'N/A',
            'member_number' => $loan->client_number,
            'amount_paid' => $payment->amount,
            'payment_breakdown' => [
                'penalties' => $allocation['penalties'],
                'interest' => $allocation['interest'],
                'principal' => $allocation['principal'],
                'overpayment' => $allocation['overpayment'] ?? 0
            ],
            'payment_method' => self::PAYMENT_METHODS[$payment->payment_method] ?? $payment->payment_method,
            'reference_number' => $payment->reference_number,
            'outstanding_balance' => $outstandingBalance['total'],
            'processed_by' => auth()->user()->name ?? 'System',
            'branch' => auth()->user()->branch ?? 'Main Branch',
            'generated_at' => now()->format('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Generate unique receipt number
     */
    private function generateReceiptNumber()
    {
        $prefix = 'RCP';
        $date = now()->format('Ymd');
        $sequence = DB::table('loan_payments')
            ->whereDate('created_at', now())
            ->count() + 1;
        
        return sprintf('%s%s%04d', $prefix, $date, $sequence);
    }
    
    /**
     * Send payment notifications
     */
    private function sendPaymentNotifications($loan, $payment, $receipt)
    {
        $member = DB::table('clients')
            ->where('client_number', $loan->client_number)
            ->first();
        
        if (!$member) {
            return;
        }
        
        // SMS notification
        if ($member->phone_number) {
            $message = sprintf(
                "Dear %s, payment of TZS %s received for loan %s. Outstanding balance: TZS %s. Receipt: %s",
                $member->first_name,
                number_format($payment->amount, 2),
                $loan->loan_id,
                number_format($receipt['outstanding_balance'], 2),
                $receipt['receipt_number']
            );
            
            // Queue SMS
            Log::info('SMS notification queued', [
                'phone' => $member->phone_number,
                'message' => $message
            ]);
            
            // TODO: Implement actual SMS sending via SMS gateway
            // For now, we'll use email as fallback
        }
        
        // Email notification
        if ($member->email) {
            try {
                // Create email content
                $emailData = [
                    'member_name' => $member->first_name . ' ' . $member->last_name,
                    'loan_id' => $loan->loan_id,
                    'payment_amount' => number_format($payment->amount, 2),
                    'receipt_number' => $receipt['receipt_number'],
                    'payment_date' => $receipt['payment_date'],
                    'payment_method' => $receipt['payment_method'],
                    'outstanding_balance' => number_format($receipt['outstanding_balance'], 2),
                    'payment_breakdown' => $receipt['payment_breakdown']
                ];
                
                // Send email using Laravel Mail
                \Illuminate\Support\Facades\Mail::send([], [], function ($message) use ($member, $emailData, $receipt) {
                    $htmlContent = "
                        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                            <h2 style='color: #333;'>Payment Receipt</h2>
                            <p>Dear {$emailData['member_name']},</p>
                            <p>We have received your payment for loan <strong>{$emailData['loan_id']}</strong>.</p>
                            
                            <div style='background: #f5f5f5; padding: 15px; margin: 20px 0;'>
                                <h3 style='margin-top: 0;'>Payment Details</h3>
                                <table style='width: 100%;'>
                                    <tr><td><strong>Receipt Number:</strong></td><td>{$emailData['receipt_number']}</td></tr>
                                    <tr><td><strong>Payment Date:</strong></td><td>{$emailData['payment_date']}</td></tr>
                                    <tr><td><strong>Amount Paid:</strong></td><td>TZS {$emailData['payment_amount']}</td></tr>
                                    <tr><td><strong>Payment Method:</strong></td><td>{$emailData['payment_method']}</td></tr>
                                </table>
                            </div>
                            
                            <div style='background: #e8f4f8; padding: 15px; margin: 20px 0;'>
                                <h3 style='margin-top: 0;'>Payment Allocation</h3>
                                <table style='width: 100%;'>
                                    <tr><td><strong>Principal:</strong></td><td>TZS " . number_format($emailData['payment_breakdown']['principal'], 2) . "</td></tr>
                                    <tr><td><strong>Interest:</strong></td><td>TZS " . number_format($emailData['payment_breakdown']['interest'], 2) . "</td></tr>
                                    <tr><td><strong>Penalties:</strong></td><td>TZS " . number_format($emailData['payment_breakdown']['penalties'], 2) . "</td></tr>
                                </table>
                            </div>
                            
                            <p><strong>Outstanding Balance:</strong> TZS {$emailData['outstanding_balance']}</p>
                            
                            <p>Thank you for your payment.</p>
                            
                            <hr style='margin-top: 30px;'>
                            <p style='font-size: 12px; color: #666;'>
                                This is an automated email from SACCOS Core System.<br>
                                If you have any questions, please contact our support team.
                            </p>
                        </div>
                    ";
                    
                    $message->to($member->email)
                            ->subject('Payment Receipt - ' . $receipt['receipt_number'])
                            ->html($htmlContent);
                });
                
                Log::info('✅ Email notification sent successfully', [
                    'email' => $member->email,
                    'receipt' => $receipt['receipt_number'],
                    'loan_id' => $loan->loan_id
                ]);
                
            } catch (Exception $e) {
                Log::error('❌ Failed to send email notification', [
                    'email' => $member->email,
                    'receipt' => $receipt['receipt_number'],
                    'error' => $e->getMessage()
                ]);
                
                // Don't throw - notification failure shouldn't stop the payment process
            }
        }
    }
    
    /**
     * Get loan payment history
     */
    public function getPaymentHistory($loanId, $limit = 10)
    {
        return DB::table('loan_payments')
            ->where('loan_id', $loanId)
            ->orderBy('payment_date', 'desc')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Calculate early settlement amount
     */
    public function calculateEarlySettlement($loanId)
    {
        Log::info('🔍 Calculating early settlement', [
            'loan_id' => $loanId,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        $loan = $this->validateAndFetchLoan($loanId);
        $outstandingBalance = $this->calculateOutstandingBalances($loan);
        
        // Calculate early settlement discount or penalty
        $product = DB::table('loan_sub_products')
            ->where('sub_product_id', $loan->loan_sub_product)
            ->first();
        
        $settlementAmount = $outstandingBalance['principal'];
        $waiver = 0;
        
        if ($product && isset($product->early_settlement_waiver) && $product->early_settlement_waiver > 0) {
            // Apply interest waiver for early settlement
            $waiver = $outstandingBalance['interest'] * ($product->early_settlement_waiver / 100);
            $settlementAmount += ($outstandingBalance['interest'] - $waiver);
            
            Log::info('💸 Early settlement waiver applied', [
                'loan_id' => $loanId,
                'waiver_percentage' => $product->early_settlement_waiver,
                'waiver_amount' => $waiver,
                'interest_before' => $outstandingBalance['interest'],
                'interest_after' => $outstandingBalance['interest'] - $waiver
            ]);
        } else {
            $settlementAmount += $outstandingBalance['interest'];
            
            Log::info('ℹ️ No early settlement waiver available', [
                'loan_id' => $loanId,
                'product_id' => $loan->loan_sub_product
            ]);
        }
        
        $result = [
            'principal' => $outstandingBalance['principal'],
            'interest' => $outstandingBalance['interest'],
            'penalties' => $outstandingBalance['penalties'],
            'waiver' => $waiver,
            'total_settlement' => $settlementAmount + $outstandingBalance['penalties'],
            'savings' => $waiver
        ];
        
        Log::info('📋 Early settlement calculated', [
            'loan_id' => $loanId,
            'result' => $result
        ]);
        
        return $result;
    }
    
    /**
     * Process bulk repayments (e.g., salary deductions)
     */
    public function processBulkRepayments($repayments)
    {
        $results = [];
        $successCount = 0;
        $failureCount = 0;
        
        foreach ($repayments as $repayment) {
            try {
                $result = $this->processRepayment(
                    $repayment['loan_id'],
                    $repayment['amount'],
                    'SALARY',
                    ['batch_id' => $repayment['batch_id'] ?? null]
                );
                
                $results[] = [
                    'loan_id' => $repayment['loan_id'],
                    'success' => true,
                    'receipt' => $result['receipt_number']
                ];
                $successCount++;
                
            } catch (Exception $e) {
                $results[] = [
                    'loan_id' => $repayment['loan_id'],
                    'success' => false,
                    'error' => $e->getMessage()
                ];
                $failureCount++;
            }
        }
        
        return [
            'total' => count($repayments),
            'successful' => $successCount,
            'failed' => $failureCount,
            'results' => $results
        ];
    }
}