<?php

namespace App\Services;

use App\Models\Expense;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\BudgetAllocation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ExpensePaymentService
{
    /**
     * Process payment for an approved expense
     *
     * @param int $expenseId
     * @return array
     */
    public function processPayment(int $expenseId): array
    {
        try {
            DB::beginTransaction();
            
            // Find the expense with its approval
            $expense = Expense::findOrFail($expenseId);
            
            // Load approval manually using process_id
            $expense->approval = \App\Models\Approval::where('process_code', 'EXPENSE_REG')
                ->where('process_id', $expenseId)
                ->first();
            
            // Check if expense is already paid
            if ($expense->status === 'PAID') {
                throw new \Exception('This expense has already been paid.');
            }
            
            // Validate expense can be paid - check approval status instead of expense status
            if (!$expense->approval) {
                throw new \Exception('This expense has no approval record.');
            }
            
            if ($expense->approval->approval_status !== 'APPROVED') {
                throw new \Exception('Only expenses with approved approval status can be paid. Current approval status: ' . $expense->approval->approval_status);
            }
            
            // Log the payment attempt
            Log::channel('budget_management')->info('════════════════════════════════════════════════════════', []);
            Log::channel('budget_management')->info('💳 STARTING EXPENSE PAYMENT PROCESS', [
                'expense_id' => $expenseId,
                'amount' => number_format($expense->amount, 2),
                'account_id' => $expense->account_id,
                'user_id' => Auth::id() ?? 1,
                'processor' => Auth::user() ? Auth::user()->name : 'System'
            ]);
            
            // Get the expense account details
            $expenseAccount = Account::find($expense->account_id);
            if (!$expenseAccount) {
                throw new \Exception('Expense account not found.');
            }
            
            // Get the cash/bank account to pay from (typically a cash or bank account)
            $paymentAccount = $this->getPaymentAccount($expense->payment_type);
            
            // Create the payment transaction
            $transaction = $this->createPaymentTransaction($expense, $expenseAccount, $paymentAccount);
            
            // Update expense status to PAID
            // Remove the dynamically loaded approval property to prevent it from being saved
            unset($expense->approval);
            
            $expense->update([
                'status' => 'PAID',
                'payment_date' => now(),
                'payment_transaction_id' => $transaction->id,
                'paid_by_user_id' => Auth::id() ?? 1,
                'payment_reference' => $this->generatePaymentReference($expense)
            ]);
            
            // Update budget allocation if expense has budget tracking
            if ($expense->budget_item_id || $expense->budget_allocation_id) {
                $this->updateBudgetAfterPayment($expense);
            }
            
            // Log success
            Log::channel('budget_management')->info('✅ EXPENSE PAYMENT SUCCESSFUL', [
                'expense_id' => $expenseId,
                'transaction_id' => $transaction->id,
                'payment_reference' => $expense->payment_reference,
                'amount' => number_format($expense->amount, 2),
                'payment_account' => $paymentAccount->account_name,
                'expense_account' => $expenseAccount->account_name
            ]);
            
            Log::channel('budget_management')->info('════════════════════════════════════════════════════════', []);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Expense payment processed successfully',
                'transaction_id' => $transaction->id,
                'payment_reference' => $expense->payment_reference,
                'amount' => $expense->amount
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::channel('budget_management')->error('❌ EXPENSE PAYMENT FAILED', [
                'expense_id' => $expenseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Create the payment transaction
     */
    private function createPaymentTransaction($expense, $expenseAccount, $paymentAccount)
    {
        // Create double-entry bookkeeping transaction
        // Debit: Expense Account (increase expense)
        // Credit: Cash/Bank Account (decrease cash)
        
        $transaction = Transaction::create([
            'transaction_uuid' => \Str::uuid(),
            'type' => 'EXPENSE_PAYMENT',
            'amount' => $expense->amount,
            'account_id' => $paymentAccount->id, // Payment account used
            'description' => 'Payment for expense: ' . $expense->description,
            'reference' => 'EXP-PAY-' . $expense->id,
            'narration' => 'Expense payment from ' . $paymentAccount->account_name . ' to ' . $expenseAccount->account_name,
            'status' => 'COMPLETED',
            'processed_at' => now(),
            'completed_at' => now(),
            'initiated_by' => Auth::id() ?? 1,
            'processed_by' => Auth::id() ?? 1,
            'metadata' => json_encode([
                'expense_id' => $expense->id,
                'debit_account_id' => $expenseAccount->id,
                'credit_account_id' => $paymentAccount->id,
                'payment_type' => 'expense_payment'
            ])
        ]);
        
        // Update account balances
        $this->updateAccountBalances($expenseAccount, $paymentAccount, $expense->amount);
        
        return $transaction;
    }
    
    /**
     * Get the payment account based on payment type
     */
    private function getPaymentAccount($paymentType)
    {
        // Map payment types to account names
        $paymentAccountMapping = [
            'money_transfer' => '%Bank%',     // Bank account
            'bank_transfer' => '%Bank%',      // Bank account
            'bill_payment' => '%Bank%',       // Bank account
            'luku_payment' => '%Bank%',       // Bank account
            'gepg_payment' => '%Bank%',       // Bank account
            'cash' => '%Cash%'                // Cash account
        ];
        
        $accountPattern = $paymentAccountMapping[$paymentType] ?? '%Cash%';
        
        // Try to find the account by name pattern
        $account = Account::where('major_category_code', 1000) // Asset accounts
            ->where('account_name', 'LIKE', $accountPattern)
            ->where('status', 'ACTIVE')
            ->first();
        
        // If not found, get any cash or bank account
        if (!$account) {
            $account = Account::whereIn('major_category_code', [1000, 1100]) // Asset accounts
                ->where(function($query) {
                    $query->where('account_name', 'LIKE', '%Cash%')
                        ->orWhere('account_name', 'LIKE', '%Bank%');
                })
                ->where('status', 'ACTIVE')
                ->first();
        }
        
        if (!$account) {
            throw new \Exception('No payment account found. Please configure cash/bank accounts.');
        }
        
        return $account;
    }
    
    /**
     * Update account balances after payment
     */
    private function updateAccountBalances($expenseAccount, $paymentAccount, $amount)
    {
        // Increase expense account balance (debit)
        $expenseAccount->increment('balance', $amount);
        
        // Decrease payment account balance (credit)
        $paymentAccount->decrement('balance', $amount);
    }
    
    /**
     * Generate payment reference
     */
    private function generatePaymentReference($expense)
    {
        return 'PAY-' . date('Ymd') . '-' . str_pad($expense->id, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Generate transaction number
     */
    private function generateTransactionNumber()
    {
        $lastTransaction = Transaction::whereDate('created_at', today())
            ->orderBy('id', 'desc')
            ->first();
        
        $sequence = $lastTransaction ? ($lastTransaction->id + 1) : 1;
        
        return 'TRX-' . date('Ymd') . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
    
    /**
     * Update budget allocation after payment
     */
    private function updateBudgetAfterPayment($expense)
    {
        if ($expense->budget_allocation_id) {
            $allocation = BudgetAllocation::find($expense->budget_allocation_id);
            if ($allocation) {
                // Update utilized amount and available amount
                $newUtilized = ($allocation->utilized_amount ?? 0) + $expense->amount;
                $newAvailable = $allocation->allocated_amount - $newUtilized;
                
                $allocation->update([
                    'utilized_amount' => $newUtilized,
                    'available_amount' => $newAvailable
                ]);
                
                Log::channel('budget_management')->info('💰 BUDGET UPDATED AFTER PAYMENT', [
                    'allocation_id' => $allocation->id,
                    'expense_amount' => $expense->amount,
                    'new_utilized' => $newUtilized,
                    'new_available' => $newAvailable,
                    'allocated_amount' => $allocation->allocated_amount
                ]);
            }
        }
    }
    
    /**
     * Process payment with additional payment details
     */
    public function processPaymentWithDetails(int $expenseId, array $paymentData): array
    {
        try {
            DB::beginTransaction();
            
            // Find the expense with its approval
            $expense = Expense::findOrFail($expenseId);
            
            // Load approval manually using process_id
            $expense->approval = \App\Models\Approval::where('process_code', 'EXPENSE_REG')
                ->where('process_id', $expenseId)
                ->first();
            
            // Check if expense is already paid
            if ($expense->status === 'PAID') {
                throw new \Exception('This expense has already been paid.');
            }
            
            // Validate expense can be paid
            if (!$expense->approval) {
                throw new \Exception('This expense has no approval record.');
            }
            
            if ($expense->approval->approval_status !== 'APPROVED') {
                throw new \Exception('Only expenses with approved approval status can be paid.');
            }
            
            // Log the payment attempt
            Log::channel('budget_management')->info('════════════════════════════════════════════════════════', []);
            Log::channel('budget_management')->info('💳 STARTING EXPENSE PAYMENT PROCESS (WITH DETAILS)', [
                'expense_id' => $expenseId,
                'amount' => number_format($expense->amount, 2),
                'payment_method' => $paymentData['payment_method'],
                'processor' => Auth::user() ? Auth::user()->name : 'System'
            ]);
            
            // Get the expense account details
            $expenseAccount = Account::find($expense->account_id);
            if (!$expenseAccount) {
                throw new \Exception('Expense account not found.');
            }
            
            // Get the payment account based on payment data
            $paymentAccount = $this->getPaymentAccountFromData($paymentData);
            
            // Create the payment transaction
            $transaction = $this->createPaymentTransaction($expense, $expenseAccount, $paymentAccount);
            
            // Update expense with payment details  
            // Remove the dynamically loaded approval property to prevent it from being saved
            unset($expense->approval);
            
            $expense->update([
                'status' => 'PAID',
                'payment_date' => now(),
                'payment_transaction_id' => $transaction->id,
                'payment_method' => $paymentData['payment_method'],
                'paid_by_user_id' => Auth::id() ?? 1,
                'payment_reference' => $this->generatePaymentReference($expense),
                'payment_notes' => $paymentData['payment_notes'] ?? null
            ]);
            
            // Store payment method specific details if needed
            $this->storePaymentMethodDetails($expense, $paymentData);
            
            // Update budget allocation if expense has budget tracking
            if ($expense->budget_item_id || $expense->budget_allocation_id) {
                $this->updateBudgetAfterPayment($expense);
            }
            
            // Log success
            Log::channel('budget_management')->info('✅ EXPENSE PAYMENT SUCCESSFUL', [
                'expense_id' => $expenseId,
                'transaction_id' => $transaction->id,
                'payment_reference' => $expense->payment_reference,
                'payment_method' => $paymentData['payment_method']
            ]);
            
            DB::commit();
            
            return [
                'success' => true,
                'message' => 'Expense payment processed successfully',
                'transaction_id' => $transaction->id,
                'payment_reference' => $expense->payment_reference,
                'amount' => $expense->amount
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::channel('budget_management')->error('❌ EXPENSE PAYMENT FAILED', [
                'expense_id' => $expenseId,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => 'Payment failed: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get payment account from payment data
     */
    private function getPaymentAccountFromData($paymentData)
    {
        if (!empty($paymentData['bank_account_id'])) {
            $account = Account::find($paymentData['bank_account_id']);
            if ($account) {
                return $account;
            }
        }
        
        // Fall back to default payment account
        return $this->getPaymentAccount($paymentData['payment_method'] ?? 'cash');
    }
    
    /**
     * Store payment method specific details
     */
    private function storePaymentMethodDetails($expense, $paymentData)
    {
        // Store additional payment details in a separate table or JSON field if needed
        // For now, we can log them
        if ($paymentData['payment_method'] === 'bank_transfer') {
            Log::channel('budget_management')->info('Bank Transfer Details', [
                'expense_id' => $expense->id,
                'account_holder' => $paymentData['account_holder_name'] ?? null
            ]);
        } elseif ($paymentData['payment_method'] === 'mobile_money') {
            Log::channel('budget_management')->info('Mobile Money Details', [
                'expense_id' => $expense->id,
                'phone' => $paymentData['phone_number'] ?? null,
                'provider' => $paymentData['mno_provider'] ?? null
            ]);
        }
    }
    
    /**
     * Process batch payment for multiple expenses
     */
    public function processBatchPayment(array $expenseIds): array
    {
        $results = [
            'successful' => [],
            'failed' => [],
            'total_amount' => 0
        ];
        
        foreach ($expenseIds as $expenseId) {
            $result = $this->processPayment($expenseId);
            
            if ($result['success']) {
                $results['successful'][] = $expenseId;
                $results['total_amount'] += $result['amount'];
            } else {
                $results['failed'][$expenseId] = $result['message'];
            }
        }
        
        return $results;
    }
}