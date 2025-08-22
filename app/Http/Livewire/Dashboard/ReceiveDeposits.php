<?php

namespace App\Http\Livewire\Dashboard;

use Livewire\Component;
use App\Models\ClientsModel;
use App\Models\AccountsModel;
use App\Models\BankAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\TransactionPostingService;
use App\Services\MembershipVerificationService;
use Illuminate\Support\Facades\Log;
use Exception;

class ReceiveDeposits extends Component
{
    // Modal states
    public $showReceiveDepositsModal = false;
    public $showReceiveSavingsModal = false;
    public $transactionType = 'deposits'; // 'deposits' or 'savings'

    // Member verification
    public $membershipNumber = '';
    public $verifiedMember = null;
    public $memberAccounts = [];
    public $bankAccounts = [];
    public $selectedBankDetails = null;

    // Transaction details
    public $selectedAccount = '';
    public $amount = '';
    public $paymentMethod = 'cash';
    public $selectedBank = '';
    public $referenceNumber = '';
    public $depositDate = '';
    public $depositTime = '';
    public $depositorName = '';
    public $narration = '';

    // Statistics
    public $totalDeposits = 0;
    public $totalSavings = 0;
    public $todayDeposits = 0;
    public $todaySavings = 0;
    public $activeAccounts = 0;

    // Loading states
    public $isLoading = false;
    public $isVerifying = false;
    public $isSubmitting = false;

    // Messages
    public $successMessage = '';
    public $errorMessage = '';

    protected $rules = [
        'membershipNumber' => 'required|min:3',
        'selectedAccount' => 'required',
        'amount' => 'required|numeric|min:0.01',
        'paymentMethod' => 'required|in:cash,bank',
        'depositorName' => 'required|min:2',
        'narration' => 'nullable|max:500',
        'selectedBank' => 'required_if:paymentMethod,bank',
        'referenceNumber' => 'required_if:paymentMethod,bank',
        'depositDate' => 'required_if:paymentMethod,bank|date',
        'depositTime' => 'required_if:paymentMethod,bank',
    ];

    protected $messages = [
        'membershipNumber.required' => 'Membership number is required.',
        'selectedAccount.required' => 'Please select an account.',
        'amount.required' => 'Amount is required.',
        'amount.numeric' => 'Amount must be a valid number.',
        'amount.min' => 'Amount must be greater than 0.',
        'depositorName.required' => 'Depositor name is required.',
        'selectedBank.required_if' => 'Please select a bank for bank deposits.',
        'referenceNumber.required_if' => 'Reference number is required for bank deposits.',
        'depositDate.required_if' => 'Deposit date is required for bank deposits.',
        'depositTime.required_if' => 'Deposit time is required for bank deposits.',
    ];

    public function mount()
    {
        $this->loadStatistics();
        $this->depositDate = now()->format('Y-m-d');
        $this->depositTime = now()->format('H:i');
    }

    public function loadStatistics()
    {
        try {
            // Total deposits and savings
            $this->totalDeposits = AccountsModel::where('product_number', '3000')
                ->where('status', 'ACTIVE')
                ->sum('balance');

            $this->totalSavings = AccountsModel::where('product_number', '2000')
                ->where('status', 'ACTIVE')
                ->sum('balance');

            // Today's transactions from general_ledger table
            $today = now()->format('Y-m-d');
            $this->todayDeposits = DB::table('general_ledger')
                ->join('accounts', 'general_ledger.record_on_account_number', '=', 'accounts.account_number')
                ->where('accounts.product_number', '3000')
                ->where('general_ledger.credit', '>', 0)
                ->whereDate('general_ledger.created_at', $today)
                ->sum('general_ledger.credit');

            $this->todaySavings = DB::table('general_ledger')
                ->join('accounts', 'general_ledger.record_on_account_number', '=', 'accounts.account_number')
                ->where('accounts.product_number', '2000')
                ->where('general_ledger.credit', '>', 0)
                ->whereDate('general_ledger.created_at', $today)
                ->sum('general_ledger.credit');

            // Active accounts
            $this->activeAccounts = AccountsModel::where('status', 'ACTIVE')->count();

        } catch (\Exception $e) {
            $this->errorMessage = 'Error loading statistics: ' . $e->getMessage();
        }
    }

    public function showReceiveDepositsModal()
    {
        $this->transactionType = 'deposits';
        $this->resetForm();
        $this->showReceiveDepositsModal = true;
    }

    public function showReceiveSavingsModal()
    {
        $this->transactionType = 'savings';
        $this->resetForm();
        $this->showReceiveSavingsModal = true;
    }

    public function closeModal()
    {
        $this->showReceiveDepositsModal = false;
        $this->showReceiveSavingsModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->membershipNumber = '';
        $this->verifiedMember = null;
        $this->memberAccounts = [];
        $this->bankAccounts = [];
        $this->selectedAccount = '';
        $this->amount = '';
        $this->paymentMethod = 'cash';
        $this->selectedBank = '';
        $this->selectedBankDetails = null;
        $this->referenceNumber = '';
        $this->depositDate = now()->format('Y-m-d');
        $this->depositTime = now()->format('H:i');
        $this->depositorName = '';
        $this->narration = '';
        $this->successMessage = '';
        $this->errorMessage = '';
        $this->resetValidation();
    }

    public function verifyMembership()
    {
        $this->validate([
            'membershipNumber' => 'required|min:1'
        ]);

        try {
            $verificationService = app(MembershipVerificationService::class);
            $result = $verificationService->verifyMembership($this->membershipNumber);

            if ($result['exists'] === true) {
                $this->verifiedMember = $result['member'];
                $this->memberAccounts = AccountsModel::where('client_number', $this->membershipNumber)
                    ->where('product_number', '2000')
                    ->where('status', 'ACTIVE')
                    ->get();
                $this->bankAccounts = BankAccount::where('status', 'ACTIVE')->get();
                
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'success',
                    'message' => $result['message']
                ]);
            } else {
                $this->addError('membershipNumber', $result['message']);
                $this->verifiedMember = null;
                $this->memberAccounts = [];
            }
        } catch (Exception $e) {
            $this->addError('membershipNumber', 'Failed to verify membership. Please try again.');
            Log::error('Membership verification error: ' . $e->getMessage());
            $this->verifiedMember = null;
            $this->memberAccounts = [];
        }
    }

    public function updatedSelectedBank()
    {
        if ($this->selectedBank) {
            $this->selectedBankDetails = BankAccount::find($this->selectedBank);
        } else {
            $this->selectedBankDetails = null;
        }
    }

    public function updatedPaymentMethod()
    {
        if ($this->paymentMethod === 'cash') {
            $this->referenceNumber = 'CASH-' . strtoupper(uniqid());
            $this->depositDate = now()->format('Y-m-d');
            $this->depositTime = now()->format('H:i');
        }
    }

    public function submitReceiveDeposits()
    {
        $this->isSubmitting = true;
        $this->errorMessage = '';

        try {
            if($this->paymentMethod === 'bank'){
                $this->validate([
                    'depositDate' => 'required|date',
                    'depositTime' => 'required|date_format:H:i',
                    'selectedBank' => 'required',
                    'referenceNumber' => 'required|string|max:255',
                    'narration' => 'required|string|max:255',
                    'amount' => 'required|numeric|min:0',
                    'depositorName' => 'required|string|max:255',
                    'paymentMethod' => 'required|string|in:bank,cash'
                ]);

                if (!empty($this->selectedBankDetails->internal_mirror_account_number) && !empty($this->selectedAccount)) {
                    $totalAmount = $this->amount;
                    
                    // Post the transaction using TransactionPostingService
                    $transactionService = new TransactionPostingService();
                    $transactionData = [
                        'first_account' => $this->selectedBankDetails->internal_mirror_account_number, // Debit account 
                        'second_account' => $this->selectedAccount, // Credit account 
                        'amount' => $totalAmount,
                        'narration' => ucfirst($this->transactionType) . ' deposit : ' . $this->amount . ' : ' . $this->depositorName . ' : ' . $this->selectedBankDetails->bank_name . ' : ' . $this->referenceNumber,
                        'action' => $this->transactionType . '_deposit'
                    ];

                    Log::info('Posting ' . $this->transactionType . ' deposit transaction', [
                        'transaction_data' => $transactionData
                    ]);

                    $result = $transactionService->postTransaction($transactionData);
                    
                    if ($result['status'] !== 'success') {
                        Log::error('Transaction posting failed', [
                            'error' => $result['message'] ?? 'Unknown error',
                            'transaction_data' => $transactionData
                        ]);
                        throw new \Exception('Failed to post transaction: ' . ($result['message'] ?? 'Unknown error'));
                    }

                    Log::info('Transaction posted successfully', [
                        'transaction_reference' => $result['reference'] ?? null,
                        'amount' => $totalAmount
                    ]);
                }
            }

            if($this->paymentMethod === 'cash'){
                $this->validate([
                    'depositDate' => 'required|date',
                    'depositTime' => 'required|date_format:H:i',
                    'referenceNumber' => 'required|string|max:255',
                    'narration' => 'required|string|max:255',
                    'amount' => 'required|numeric|min:0',
                    'depositorName' => 'required|string|max:255',
                    'paymentMethod' => 'required|string|in:bank,cash'
                ]);
            }

            $this->successMessage = ucfirst($this->transactionType) . ' received successfully!';
            $this->loadStatistics();
            
            // Reset form after successful submission
            $this->resetForm();
            $this->closeModal();

        } catch (\Exception $e) {
            $this->errorMessage = 'Error processing transaction: ' . $e->getMessage();
            Log::error('Error in submitReceiveDeposits: ' . $e->getMessage());
        } finally {
            $this->isSubmitting = false;
        }
    }

    public function submitReceiveSavings()
    {
        // Same logic as deposits, just different account type
        $this->submitReceiveDeposits();
    }

    public function getBankAccountsProperty()
    {
        return BankAccount::where('status', 'ACTIVE')->get();
    }

    public function render()
    {
        return view('livewire.dashboard.receive-deposits', [
            'bankAccounts' => $this->bankAccounts
        ]);
    }
}
