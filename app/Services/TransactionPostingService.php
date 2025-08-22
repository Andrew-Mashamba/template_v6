<?php

namespace App\Services;

use App\Models\AccountsModel;
use App\Models\general_ledger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Exception;
use Illuminate\Support\Facades\Log;

class TransactionPostingService
{
    private $validator;
    private $balanceManager;
    private $transactionTypes;
    private $logger;
    public $credit_account_level, $debit_account_level;
    public $action;

    public function __construct()
    {
        $this->validator = new TransactionValidator();
        $this->balanceManager = new BalanceManager();
        $this->transactionTypes = new TransactionTypes();
        $this->logger = new TransactionLogger();
    }

    /**
     * Post a transaction with automatic debit/credit determination.
     *
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function postTransaction(array $data)
    {
        $this->logger->logTransactionStart($data);

        try {
            // Validate transaction data
            $validations = $this->validator->validateTransaction($data);
            $this->logger->logValidationResults($validations);
            
            $referenceNumber = time();
            DB::beginTransaction();

            $firstAccountDetails = $data['first_account'];
            $secondAccountDetails = $data['second_account'];
            $amount = $data['amount'];
            $narration = $data['narration'];
            $this->action = $data['action'] ?? 'none';

            // Fetch account objects
            $firstAccount = AccountsModel::where('account_number', $firstAccountDetails)->first();
            $secondAccount = AccountsModel::where('account_number', $secondAccountDetails)->first();

            if (!$firstAccount || !$secondAccount) {
                throw new Exception('One or both accounts not found');
            }

            // Determine which account to debit and credit
            $debitAccountDetails = $this->determineDebitAccount($firstAccount, $secondAccount) === 'first' 
                ? $firstAccount 
                : $secondAccount;
            $creditAccountDetails = $debitAccountDetails === $firstAccount 
                ? $secondAccount 
                : $firstAccount;

            // Process the transaction
            $this->processTransaction($referenceNumber, $debitAccountDetails, $creditAccountDetails, $amount, $narration);

            DB::commit();
            $this->logger->logTransactionCompletion($referenceNumber, 'success');
            return ['status' => 'success', 'reference_number' => $referenceNumber];
        } catch (Exception $e) {
            DB::rollBack();
            $this->logger->logError($e, [
                'transaction_data' => $data,
                'reference_number' => $referenceNumber ?? null
            ]);
            throw $e;
        }
    }

    private function determineDebitAccount($firstAccount, $secondAccount)
    {
        if (in_array($firstAccount->type, ['asset_accounts', 'expense_accounts'])) {
            return 'first';
        }
        return 'second';
    }

    private function processTransaction($referenceNumber, $debitAccountDetails, $creditAccountDetails, $amount, $narration)
    {
        $this->credit_account_level = 2;
        $this->debit_account_level = 2;

        // Update balances using BalanceManager
        $transaction = [
            'debit_account' => $debitAccountDetails,
            'credit_account' => $creditAccountDetails,
            'amount' => $amount,
            'type' => $this->determineTransactionType($debitAccountDetails, $creditAccountDetails)
        ];

        $balanceUpdate = $this->balanceManager->updateBalances($transaction);
        $postBalances = $balanceUpdate['post_transaction_balances'];
        $this->logger->logBalanceChanges(
            $balanceUpdate['pre_transaction_balances'],
            $balanceUpdate['balance_changes'],
            $postBalances
        );
        
        // Record transaction entries
        $this->recordTransaction(
            $referenceNumber, 
            $debitAccountDetails, 
            $creditAccountDetails, 
            $postBalances['debit_account']['new_balance'], 
            'debit', 
            $amount, 
            $narration
        );
        
        $this->recordTransaction(
            $referenceNumber, 
            $creditAccountDetails, 
            $debitAccountDetails, 
            $postBalances['credit_account']['new_balance'], 
            'credit', 
            $amount, 
            $narration
        );

        // Update account balances using only values from BalanceManager
        $this->updateAccountBalance(
            $debitAccountDetails, 
            $postBalances['debit_account']['new_balance'],
            $amount, 
            'debit',
            $postBalances['debit_account']['sub_category_account']['new_balance'] ?? null,
            $postBalances['debit_account']['category_account']['new_balance'] ?? null,
            $postBalances['debit_account']['major_account']['new_balance'] ?? null
        );
        
        $this->updateAccountBalance(
            $creditAccountDetails, 
            $postBalances['credit_account']['new_balance'],
            $amount, 
            'credit',
            $postBalances['credit_account']['sub_category_account']['new_balance'] ?? null,
            $postBalances['credit_account']['category_account']['new_balance'] ?? null,
            $postBalances['credit_account']['major_account']['new_balance'] ?? null
        );
    }

    private function determineTransactionType($debitAccount, $creditAccount)
    {
        $debitType = $debitAccount->type;
        $creditType = $creditAccount->type;

        $typeMap = [
            'asset_accounts' => [
                'asset_accounts' => TransactionTypes::ASSET_PURCHASE,
                'liability_accounts' => TransactionTypes::LIABILITY_SETTLEMENT,
                'capital_accounts' => TransactionTypes::CAPITAL_CONTRIBUTION,
                'income_accounts' => TransactionTypes::REVENUE_RECOGNITION,
                'expense_accounts' => TransactionTypes::EXPENSE_PAYMENT
            ],
            'liability_accounts' => [
                'asset_accounts' => TransactionTypes::LIABILITY_RECOGNITION,
                'liability_accounts' => TransactionTypes::LIABILITY_ADJUSTMENT,
                'capital_accounts' => TransactionTypes::CAPITAL_ADJUSTMENT,
                'expense_accounts' => TransactionTypes::EXPENSE_ACCRUAL
            ],
            'capital_accounts' => [
                'asset_accounts' => TransactionTypes::CAPITAL_WITHDRAWAL,
                'liability_accounts' => TransactionTypes::LIABILITY_ADJUSTMENT,
                'capital_accounts' => TransactionTypes::CAPITAL_ADJUSTMENT,
                'expense_accounts' => TransactionTypes::EXPENSE_PAYMENT
            ],
            'income_accounts' => [
                'asset_accounts' => TransactionTypes::REVENUE_RECOGNITION,
                'liability_accounts' => TransactionTypes::LIABILITY_SETTLEMENT,
                'capital_accounts' => TransactionTypes::CAPITAL_CONTRIBUTION,
                'expense_accounts' => TransactionTypes::EXPENSE_PAYMENT,
                'income_accounts' => TransactionTypes::REVENUE_ADJUSTMENT
            ],
            'expense_accounts' => [
                'asset_accounts' => TransactionTypes::EXPENSE_PAYMENT,
                'liability_accounts' => TransactionTypes::EXPENSE_ACCRUAL,
                'capital_accounts' => TransactionTypes::EXPENSE_PAYMENT,
                'income_accounts' => TransactionTypes::EXPENSE_PAYMENT
            ]
        ];

        return $typeMap[$debitType][$creditType] ?? 'unknown';
    }

    private function updateAccountBalance(
        $accountDetails,      
        $memberAccountNewBalance,  
        $amount,             
        $action,             
        $subCategoryNewBalance = null,    
        $categoryNewBalance = null,       
        $majorNewBalance = null          
    ) {
        // Validate balance is not null
        if ($memberAccountNewBalance === null) {
            Log::error('Attempting to update account with null balance', [
                'account_number' => $accountDetails->account_number,
                'new_balance' => $memberAccountNewBalance,
                'action' => $action,
                'amount' => $amount
            ]);
            throw new \InvalidArgumentException('Cannot update account with null balance');
        }

        $accountData = [
            'account_number' => $accountDetails->account_number,
            'previous_balance' => $accountDetails->balance,
            'new_balance' => $memberAccountNewBalance,
            'amount' => $amount,
            'account_level' => $accountDetails->account_level
        ];

        $this->logger->logAccountUpdate($accountData, $action);

        // Update the current account
        AccountsModel::where('account_number', $accountDetails->account_number)
            ->update([
                'balance' => (float)$memberAccountNewBalance,
                $action => ($accountDetails->{$action} ?? 0) + (float)$amount
            ]);

        // Function to update parent accounts recursively
        $updateParentAccounts = function($account, $amount, $action) use (&$updateParentAccounts) {
            if (!$account->parent_account_number) {
                return;
            }

            $parentAccount = AccountsModel::where('account_number', $account->parent_account_number)->first();
            if (!$parentAccount) {
                return;
            }

            // Calculate new balance for parent
            $newParentBalance = ($parentAccount->balance ?? 0) + (float)$amount;
            
            // Update parent account
            AccountsModel::where('account_number', $parentAccount->account_number)
                ->update([
                    'balance' => (float)$newParentBalance,
                    $action => ($parentAccount->{$action} ?? 0) + (float)$amount
                ]);

            // Log parent account update
            Log::info('Parent account updated', [
                'parent_account_number' => $parentAccount->account_number,
                'parent_account_level' => $parentAccount->account_level,
                'previous_balance' => $parentAccount->balance,
                'new_balance' => $newParentBalance,
                'amount' => $amount,
                'action' => $action
            ]);

            // Recursively update higher level parents
            $updateParentAccounts($parentAccount, $amount, $action);
        };

        // Update all parent accounts
        $updateParentAccounts($accountDetails, $amount, $action);
    }

    private function parentAccountUpdate($account_number, $amount, $action)
    {
        if (!$account_number) {
            return; // Skip if no parent account number provided
        }

        DB::beginTransaction();
        try {
            $parentAccount = AccountsModel::where('account_number', $account_number)->first();
            
            if (!$parentAccount) {
                Log::warning('Parent account not found', ['account_number' => $account_number]);
                DB::rollBack();
                return;
            }

            AccountsModel::where('account_number', $account_number)
                ->update([
                    'balance' => $parentAccount->balance + ($action === 'credit' ? $amount : -$amount),
                    'credit' => $action === 'credit' ? $parentAccount->credit + $amount : $parentAccount->credit,
                    'debit' => $action === 'debit' ? $parentAccount->debit + $amount : $parentAccount->debit,
                ]);
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Parent account update failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function recordTransaction($referenceNumber, $account, $counterparty, $newBalance, $transactionType, $amount, $narration)
    {
        //Log the transaction data
        Log::info('Transaction data xxxxxxxxxxxxxx', ['reference_number' => $referenceNumber, 'account' => $account, 'counterparty' => $counterparty, 'new_balance' => $newBalance, 'transaction_type' => $transactionType, 'amount' => $amount, 'narration' => $narration]);

        try {
            DB::beginTransaction();

            // Validate inputs
            $missingParams = [];
            if (!$account) $missingParams[] = 'account';
            if (!$counterparty) $missingParams[] = 'counterparty';
            if ($newBalance === null || $newBalance === '') $missingParams[] = 'new_balance';
            if (!$amount) $missingParams[] = 'amount';

            if (!empty($missingParams)) {
                Log::error('Missing transaction parameters', [
                    'missing_parameters' => $missingParams,
                    'reference_number' => $referenceNumber,
                    'account' => $account ? 'present' : 'missing',
                    'counterparty' => $counterparty ? 'present' : 'missing',
                    'new_balance' => $newBalance,
                    'amount' => $amount
                ]);
                throw new \InvalidArgumentException('Missing required transaction parameters: ' . implode(', ', $missingParams));
            }

            // Validate balance consistency
            $this->validateBalanceConsistency($account, $newBalance, $amount, $transactionType);

            // Prepare detailed ledger entry
            $ledgerData = [
                //'institution_id' => isset($account->institution_number) && $account->institution_number !== '' ? (int)$account->institution_number : null,
                'record_on_account_number' => $account->account_number,
                'record_on_account_number_balance' => $newBalance,
                'sender_branch_id' => isset($account->branch_number) && $account->branch_number !== '' ? (int)$account->branch_number : null,
                'beneficiary_branch_id' => isset($counterparty->branch_number) && $counterparty->branch_number !== '' ? (int)$counterparty->branch_number : null,
                'sender_product_id' => isset($account->product_number) && is_numeric($account->product_number) ? (int)$account->product_number : null,
                'sender_sub_product_id' => isset($account->sub_product_number) && is_numeric($account->sub_product_number) ? (int)$account->sub_product_number : null,
                'beneficiary_product_id' => isset($counterparty->product_number) && is_numeric($counterparty->product_number) ? (int)$counterparty->product_number : null,
                'beneficiary_sub_product_id' => isset($counterparty->sub_product_number) && is_numeric($counterparty->sub_product_number) ? (int)$counterparty->sub_product_number : null,
                'sender_id' => isset($account->client_number) && is_numeric($account->client_number) ? (int)$account->client_number : null,
                'beneficiary_id' => isset($counterparty->client_number) && is_numeric($counterparty->client_number) ? (int)$counterparty->client_number : null,
                'sender_name' => $transactionType === 'debit' ? $account->account_name : $counterparty->account_name,
                'beneficiary_name' => $transactionType === 'debit' ? $counterparty->account_name : $account->account_name,
                'sender_account_number' => $transactionType === 'debit' ? $account->account_number : $counterparty->account_number,
                'beneficiary_account_number' => $transactionType === 'debit' ? $counterparty->account_number : $account->account_number,
                'transaction_type' => $transactionType,
                'sender_account_currency_type' => $account->currency_type ?? 'TZS',
                'beneficiary_account_currency_type' => $counterparty->currency_type ?? 'TZS',
                'narration' => $narration,
                'branch_id' => $account->branch_number ?? null,
                'credit' => $transactionType === 'credit' ? $amount : 0,
                'debit' => $transactionType === 'debit' ? $amount : 0,
                'reference_number' => $referenceNumber,
                'trans_status' => 'System initiated',
                'trans_status_description' => 'System initiated',
                'swift_code' => $account->swift_code ?? null,
                'destination_bank_name' => $counterparty->bank_name ?? null,
                'destination_bank_number' => $counterparty->bank_number ?? null,
                'partner_bank' => $counterparty->partner_bank ?? null,
                'partner_bank_name' => $counterparty->partner_bank_name ?? null,
                'partner_bank_account_number' => $counterparty->partner_bank_account_number ?? null,
                'partner_bank_transaction_reference_number' => $counterparty->partner_bank_transaction_reference_number ?? null,
                'payment_status' => 'Done',
                'recon_status' => 'Pending',
                'loan_id' => $account->loan_id ?? null,
                'bank_reference_number' => $account->bank_reference_number ?? null,
                'product_number' => $account->product_number ?? null,
                'major_category_code' => $account->major_category_code ?? null,
                'category_code' => $account->category_code ?? null,
                'sub_category_code' => $account->sub_category_code ?? null,
                'gl_balance' => $newBalance,
                'account_level' => $account->account_level ?? null,
                'created_at' => now(),
                'updated_at' => now()
            ];

            // Log the start of the ledger entry creation
            Log::info('Attempting to create general ledger entry', $ledgerData);

            $ledgerEntry = general_ledger::create($ledgerData);

            if (!$ledgerEntry) {
                Log::error('Failed to create general ledger entry', $ledgerData);
                throw new \Exception('Failed to create general ledger entry');
            }

            // Log successful creation with the new ledger entry ID
            Log::info('General ledger entry created successfully', array_merge($ledgerData, ['ledger_entry_id' => $ledgerEntry->id]));

            DB::commit();
            
            // Log transaction record with correct field mapping for TransactionLogger
            $this->logger->logTransactionRecord([
                'reference_number' => $referenceNumber,
                'transaction_type' => $transactionType,
                'amount' => $amount,
                'debit_account' => $transactionType === 'debit' ? $account->account_number : $counterparty->account_number,
                'credit_account' => $transactionType === 'credit' ? $account->account_number : $counterparty->account_number,
                'status' => 'success',
                'ledger_entry_id' => $ledgerEntry->id,
                'narration' => $narration,
                'new_balance' => $newBalance
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error during general ledger entry creation', [
                'error_message' => $e->getMessage(),
                'ledger_data' => $ledgerData ?? null
            ]);
            
            // Log error with correct field mapping
            $this->logger->logTransactionRecord([
                'reference_number' => $referenceNumber ?? 'N/A',
                'transaction_type' => $transactionType ?? 'N/A',
                'amount' => $amount ?? 0,
                'debit_account' => $transactionType === 'debit' ? ($account->account_number ?? 'N/A') : ($counterparty->account_number ?? 'N/A'),
                'credit_account' => $transactionType === 'credit' ? ($account->account_number ?? 'N/A') : ($counterparty->account_number ?? 'N/A'),
                'status' => 'error',
                'error_message' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function validateBalanceConsistency($account, $newBalance, $amount, $transactionType)
    {
        $currentBalance = $account->balance;
        $type = $account->type;

        if ($transactionType === 'debit') {
            if (in_array($type, ['asset_accounts', 'expense_accounts'])) {
                $expectedBalance = $currentBalance + $amount;
            } else { // liability, capital, income
                $expectedBalance = $currentBalance - $amount;
            }
        } else { // credit
            if (in_array($type, ['asset_accounts', 'expense_accounts'])) {
                $expectedBalance = $currentBalance - $amount;
            } else { // liability, capital, income
                $expectedBalance = $currentBalance + $amount;
            }
        }

        // Log the check
        Log::info('Validating balance consistency', [
            'account_number' => $account->account_number,
            'account_type' => $type,
            'transaction_type' => $transactionType,
            'current_balance' => $currentBalance,
            'amount' => $amount,
            'expected_balance' => $expectedBalance,
            'new_balance' => $newBalance
        ]);

        if (abs($newBalance - $expectedBalance) > 0.01) {
            Log::error('Balance inconsistency detected', [
                'account_number' => $account->account_number,
                'account_type' => $type,
                'transaction_type' => $transactionType,
                'current_balance' => $currentBalance,
                'amount' => $amount,
                'expected_balance' => $expectedBalance,
                'new_balance' => $newBalance
            ]);
            throw new \Exception(sprintf(
                'Balance inconsistency detected. Expected: %f, Got: %f',
                $expectedBalance,
                $newBalance
            ));
        }
    }
}
