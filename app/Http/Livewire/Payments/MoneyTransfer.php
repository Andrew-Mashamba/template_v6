<?php

namespace App\Http\Livewire\Payments;

use Livewire\Component;
use App\Services\Payments\ExternalFundsTransferService;
use App\Services\Payments\MobileWalletTransferService;
use App\Services\Payments\InternalFundsTransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class MoneyTransfer extends Component
{
    // Transfer type selection
    public $transferCategory = ''; // 'internal' or 'external' - primary choice
    public $transferType = ''; // 'bank' or 'wallet' - for external transfers only
    
    // Common fields
    public $debitAccount = '';
    public $amount = '';
    public $remarks = '';
    public $chargeBearer = 'OUR'; // OUR, BEN, SHA
    
    // Bank transfer fields
    public $beneficiaryAccount = '';
    public $bankCode = '';
    public $beneficiaryName = '';
    
    // Mobile wallet fields
    public $phoneNumber = '';
    public $walletProvider = '';
    
    // Internal transfer fields (NBC to NBC)
    public $internalAccount = '';
    
    // UI State
    public $currentPhase = 'form'; // 'form', 'verify', 'processing', 'complete'
    public $errorMessage = '';
    public $successMessage = '';
    public $isLoading = false;
    
    // Verification data
    public $verificationData = [];
    public $lookupRef = '';
    public $transactionReference = '';
    
    // Available options
    public $availableBanks = [];
    public $availableWallets = [];
    
    // Services
    protected $externalTransferService;
    protected $walletTransferService;
    protected $internalTransferService;

    public function mount()
    {
        Log::info('[MoneyTransfer] Component mounting', [
            'user_id' => auth()->id(),
            'session_id' => session()->getId()
        ]);
        
        try {
            $this->loadAvailableOptions();
            
            // Initialize services
            $this->externalTransferService = app(ExternalFundsTransferService::class);
            $this->walletTransferService = app(MobileWalletTransferService::class);
            $this->internalTransferService = app(InternalFundsTransferService::class);
            
            // Set default account if available
            $this->debitAccount = config('services.nbc_payments.saccos_account', '');
            
            Log::info('[MoneyTransfer] Component mounted successfully', [
                'default_account' => $this->debitAccount,
                'banks_loaded' => count($this->availableBanks),
                'wallets_loaded' => count($this->availableWallets)
            ]);
            
        } catch (Exception $e) {
            Log::error('[MoneyTransfer] Failed to mount component', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->errorMessage = 'Failed to initialize transfer service';
        }
    }

    public function loadAvailableOptions()
    {
        // Available banks for external transfers
        $this->availableBanks = [
            'CRDBTZTZ' => 'CRDB Bank',
            'NMIBTZTZ' => 'NMB Bank',
            'CORUTZTZ' => 'NBC Bank',
            'FBMETZTZ' => 'FBME Bank',
            'STANBICTZTZ' => 'Stanbic Bank',
            'EXIMBANKTZTZ' => 'Exim Bank',
            'DTKETZTZ' => 'DTB Bank',
            'ABTZTZTZ' => 'Absa Bank',
            'STANCHART' => 'Standard Chartered Bank',
            'BARCLAYTZT' => 'Barclays Bank',
        ];
        
        // Available mobile wallet providers
        $this->availableWallets = [
            'MPESA' => 'M-Pesa (Vodacom)',
            'TIGOPESA' => 'TigoPesa',
            'AIRTELMONEY' => 'Airtel Money',
            'HALOPESA' => 'HaloPesa',
            'EZYPESA' => 'EzyPesa'
        ];
    }

    public function verifyBeneficiary()
    {
        Log::info('[MoneyTransfer] Starting beneficiary verification', [
            'transfer_category' => $this->transferCategory,
            'transfer_type' => $this->transferType,
            'amount' => $this->amount,
            'from_account' => $this->debitAccount
        ]);
        
        try {
            $this->validate($this->getValidationRules());
            
            Log::info('[MoneyTransfer] Validation passed', [
                'category' => $this->transferCategory,
                'type' => $this->transferType
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('[MoneyTransfer] Validation failed', [
                'errors' => $e->errors(),
                'category' => $this->transferCategory,
                'type' => $this->transferType
            ]);
            throw $e;
        }
        
        $this->isLoading = true;
        $this->errorMessage = '';
        
        try {
            if ($this->transferCategory === 'internal') {
                Log::info('[MoneyTransfer] Verifying internal NBC account', [
                    'account' => $this->internalAccount
                ]);
                $this->verifyInternalAccount();
                
            } elseif ($this->transferCategory === 'external') {
                switch ($this->transferType) {
                    case 'bank':
                        Log::info('[MoneyTransfer] Verifying external bank account', [
                            'bank_code' => $this->bankCode,
                            'account' => $this->beneficiaryAccount
                        ]);
                        $this->verifyBankAccount();
                        break;
                        
                    case 'wallet':
                        Log::info('[MoneyTransfer] Verifying mobile wallet', [
                            'provider' => $this->walletProvider,
                            'phone' => $this->phoneNumber
                        ]);
                        $this->verifyWallet();
                        break;
                }
            }
            
            if (!empty($this->verificationData)) {
                Log::info('[MoneyTransfer] Verification successful', [
                    'verification_data' => $this->verificationData,
                    'lookup_ref' => $this->lookupRef
                ]);
                $this->currentPhase = 'verify';
            } else {
                Log::warning('[MoneyTransfer] Verification returned empty data');
            }
            
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            Log::error('[MoneyTransfer] Verification failed', [
                'category' => $this->transferCategory,
                'type' => $this->transferType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    protected function verifyBankAccount()
    {
        Log::info('[MoneyTransfer] Calling external transfer service for bank lookup', [
            'account' => $this->beneficiaryAccount,
            'bank_code' => $this->bankCode,
            'amount' => $this->amount
        ]);
        
        $result = $this->externalTransferService->lookupAccount(
            $this->beneficiaryAccount,
            $this->bankCode,
            floatval($this->amount)
        );
        
        Log::info('[MoneyTransfer] Bank lookup response', [
            'success' => $result['success'] ?? false,
            'account_name' => $result['account_name'] ?? null,
            'engine_ref' => $result['engine_ref'] ?? null,
            'error' => $result['error'] ?? null
        ]);
        
        if ($result['success']) {
            $this->verificationData = [
                'type' => 'bank',
                'account_name' => $result['account_name'] ?? 'Account Verified',
                'account_number' => $this->beneficiaryAccount,
                'bank_name' => $this->availableBanks[$this->bankCode] ?? $this->bankCode,
                'bank_code' => $this->bankCode,
                'can_receive' => $result['can_receive'] ?? true,
                'engine_ref' => $result['engine_ref'] ?? null
            ];
            
            $this->beneficiaryName = $result['account_name'] ?? '';
            $this->lookupRef = $result['engine_ref'] ?? '';
            
            Log::info('[MoneyTransfer] Bank account verified successfully', [
                'beneficiary_name' => $this->beneficiaryName,
                'lookup_ref' => $this->lookupRef
            ]);
        } else {
            Log::error('[MoneyTransfer] Bank account verification failed', [
                'error' => $result['error'] ?? 'Unknown error',
                'response' => $result
            ]);
            throw new Exception($result['error'] ?? 'Account verification failed');
        }
    }

    protected function verifyWallet()
    {
        Log::info('[MoneyTransfer] Calling wallet transfer service for lookup', [
            'phone' => $this->phoneNumber,
            'provider' => $this->walletProvider,
            'amount' => $this->amount
        ]);
        
        $result = $this->walletTransferService->lookupWallet(
            $this->phoneNumber,
            $this->walletProvider,
            floatval($this->amount)
        );
        
        Log::info('[MoneyTransfer] Wallet lookup response', [
            'success' => $result['success'] ?? false,
            'account_name' => $result['account_name'] ?? null,
            'engine_ref' => $result['engine_ref'] ?? null,
            'error' => $result['error'] ?? null
        ]);
        
        if ($result['success']) {
            $this->verificationData = [
                'type' => 'wallet',
                'account_name' => $result['account_name'] ?? 'Wallet Verified',
                'phone_number' => $this->phoneNumber,
                'provider' => $this->availableWallets[$this->walletProvider] ?? $this->walletProvider,
                'provider_code' => $this->walletProvider,
                'can_receive' => $result['can_receive'] ?? true,
                'engine_ref' => $result['engine_ref'] ?? null
            ];
            
            $this->beneficiaryName = $result['account_name'] ?? '';
            $this->lookupRef = $result['engine_ref'] ?? '';
            
            Log::info('[MoneyTransfer] Wallet verified successfully', [
                'beneficiary_name' => $this->beneficiaryName,
                'lookup_ref' => $this->lookupRef
            ]);
        } else {
            Log::error('[MoneyTransfer] Wallet verification failed', [
                'error' => $result['error'] ?? 'Unknown error',
                'response' => $result
            ]);
            throw new Exception($result['error'] ?? 'Wallet verification failed');
        }
    }

    protected function verifyInternalAccount()
    {
        Log::info('[MoneyTransfer] Calling internal transfer service for lookup', [
            'account' => $this->internalAccount
        ]);
        
        $result = $this->internalTransferService->lookupAccount($this->internalAccount);
        
        Log::info('[MoneyTransfer] Internal account lookup response', [
            'success' => $result['success'] ?? false,
            'account_name' => $result['account_name'] ?? null,
            'branch' => $result['branch_name'] ?? null,
            'error' => $result['error'] ?? null
        ]);
        
        if ($result['success']) {
            $this->verificationData = [
                'type' => 'internal',
                'account_name' => $result['account_name'] ?? 'NBC Account Verified',
                'account_number' => $this->internalAccount,
                'branch' => $result['branch_name'] ?? 'NBC Branch',
                'can_receive' => $result['can_receive'] ?? true
            ];
            
            $this->beneficiaryName = $result['account_name'] ?? '';
            
            Log::info('[MoneyTransfer] Internal account verified successfully', [
                'beneficiary_name' => $this->beneficiaryName,
                'branch' => $this->verificationData['branch']
            ]);
        } else {
            Log::error('[MoneyTransfer] Internal account verification failed', [
                'error' => $result['error'] ?? 'Unknown error',
                'response' => $result
            ]);
            throw new Exception($result['error'] ?? 'Account verification failed');
        }
    }

    public function executeTransfer()
    {
        Log::info('[MoneyTransfer] Starting transfer execution', [
            'category' => $this->transferCategory,
            'type' => $this->transferType,
            'amount' => $this->amount,
            'from_account' => $this->debitAccount,
            'lookup_ref' => $this->lookupRef
        ]);
        
        $this->isLoading = true;
        $this->errorMessage = '';
        $this->currentPhase = 'processing';
        
        try {
            $result = [];
            
            if ($this->transferCategory === 'internal') {
                Log::info('[MoneyTransfer] Executing internal transfer', [
                    'from' => $this->debitAccount,
                    'to' => $this->internalAccount,
                    'amount' => $this->amount
                ]);
                $result = $this->executeInternalTransfer();
                
            } elseif ($this->transferCategory === 'external') {
                switch ($this->transferType) {
                    case 'bank':
                        Log::info('[MoneyTransfer] Executing external bank transfer', [
                            'from' => $this->debitAccount,
                            'to' => $this->beneficiaryAccount,
                            'bank' => $this->bankCode,
                            'amount' => $this->amount
                        ]);
                        $result = $this->executeExternalTransfer();
                        break;
                        
                    case 'wallet':
                        Log::info('[MoneyTransfer] Executing wallet transfer', [
                            'from' => $this->debitAccount,
                            'to' => $this->phoneNumber,
                            'provider' => $this->walletProvider,
                            'amount' => $this->amount
                        ]);
                        $result = $this->executeWalletTransfer();
                        break;
                }
            }
            
            Log::info('[MoneyTransfer] Transfer execution response', [
                'success' => $result['success'] ?? false,
                'reference' => $result['reference'] ?? null,
                'nbc_reference' => $result['nbc_reference'] ?? null,
                'message' => $result['message'] ?? null,
                'error' => $result['error'] ?? null
            ]);
            
            if ($result['success'] ?? false) {
                $this->successMessage = $result['message'] ?? 'Transfer completed successfully';
                $this->transactionReference = $result['reference'] ?? $result['nbc_reference'] ?? 'REF' . time();
                $this->currentPhase = 'complete';
                
                Log::info('[MoneyTransfer] Transfer completed successfully', [
                    'reference' => $this->transactionReference,
                    'message' => $this->successMessage
                ]);
                
                // Log successful transaction
                $this->logTransaction($result);
                
            } else {
                throw new Exception($result['error'] ?? 'Transfer failed');
            }
            
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->currentPhase = 'verify'; // Go back to verify phase
            
            Log::error('[MoneyTransfer] Transfer execution failed', [
                'category' => $this->transferCategory,
                'type' => $this->transferType,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    protected function executeExternalTransfer()
    {
        return $this->externalTransferService->transfer([
            'from_account' => $this->debitAccount,
            'to_account' => $this->beneficiaryAccount,
            'bank_code' => $this->bankCode,
            'amount' => $this->amount,
            'narration' => $this->remarks,
            'charge_bearer' => $this->chargeBearer,
            'lookup_ref' => $this->lookupRef,
            'payer_phone' => auth()->user()->phone ?? '255715000001'
        ]);
    }

    protected function executeWalletTransfer()
    {
        return $this->walletTransferService->transfer([
            'from_account' => $this->debitAccount,
            'phone_number' => $this->phoneNumber,
            'provider' => $this->walletProvider,
            'amount' => $this->amount,
            'narration' => $this->remarks,
            'charge_bearer' => $this->chargeBearer,
            'payer_phone' => auth()->user()->phone ?? '255715000001'
        ]);
    }

    protected function executeInternalTransfer()
    {
        return $this->internalTransferService->transfer([
            'from_account' => $this->debitAccount,
            'to_account' => $this->internalAccount,
            'amount' => $this->amount,
            'narration' => $this->remarks
        ]);
    }

    public function resetForm()
    {
        Log::info('[MoneyTransfer] Resetting form');
        
        $this->reset([
            'amount',
            'remarks',
            'beneficiaryAccount',
            'bankCode',
            'beneficiaryName',
            'phoneNumber',
            'walletProvider',
            'internalAccount',
            'verificationData',
            'lookupRef',
            'errorMessage',
            'successMessage',
            'transferCategory',
            'transferType',
            'transactionReference'
        ]);
        
        $this->currentPhase = 'form';
    }

    public function goBack()
    {
        Log::info('[MoneyTransfer] Going back from phase', [
            'current_phase' => $this->currentPhase
        ]);
        
        if ($this->currentPhase === 'verify') {
            $this->currentPhase = 'form';
        } elseif ($this->currentPhase === 'complete') {
            $this->resetForm();
        }
    }
    
    public function updated($propertyName)
    {
        Log::debug('[MoneyTransfer] Property updated', [
            'property' => $propertyName,
            'value' => $this->$propertyName ?? null
        ]);
    }

    protected function getValidationRules()
    {
        $rules = [
            'debitAccount' => 'required|string|min:10',
            'amount' => 'required|numeric|min:1000',
            'remarks' => 'required|string|max:50',
            'transferCategory' => 'required|in:internal,external'
        ];
        
        if ($this->transferCategory === 'internal') {
            $rules['internalAccount'] = 'required|string|min:10';
        } elseif ($this->transferCategory === 'external') {
            $rules['chargeBearer'] = 'required|in:OUR,BEN,SHA';
            $rules['transferType'] = 'required|in:bank,wallet';
            
            switch ($this->transferType) {
                case 'bank':
                    $rules['beneficiaryAccount'] = 'required|string|min:10';
                    $rules['bankCode'] = 'required|string';
                    break;
                    
                case 'wallet':
                    $rules['phoneNumber'] = 'required|string|regex:/^(255|0)[0-9]{9}$/';
                    $rules['walletProvider'] = 'required|string';
                    break;
            }
        }
        
        // Amount limits based on transfer type
        if ($this->transferType === 'wallet') {
            $rules['amount'] .= '|max:20000000'; // 20M limit for wallets
        }
        
        return $rules;
    }

    protected function logTransaction($result)
    {
        try {
            DB::table('transfer_logs')->insert([
                'user_id' => auth()->id(),
                'transfer_type' => $this->transferType,
                'from_account' => $this->debitAccount,
                'to_account' => $this->beneficiaryAccount ?? $this->phoneNumber ?? $this->internalAccount,
                'amount' => $this->amount,
                'reference' => $result['reference'] ?? null,
                'nbc_reference' => $result['nbc_reference'] ?? null,
                'status' => 'SUCCESS',
                'remarks' => $this->remarks,
                'response_data' => json_encode($result),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (Exception $e) {
            Log::error('Failed to log transaction', ['error' => $e->getMessage()]);
        }
    }

    public function render()
    {
        Log::debug('[MoneyTransfer] Rendering component', [
            'phase' => $this->currentPhase,
            'category' => $this->transferCategory,
            'type' => $this->transferType,
            'has_error' => !empty($this->errorMessage)
        ]);
        
        return view('livewire.payments.money-transfer');
    }
    
    /**
     * Handle JavaScript errors from the frontend
     */
    public function logJsError($error, $context = [])
    {
        Log::error('[MoneyTransfer] JavaScript error', [
            'error' => $error,
            'context' => $context,
            'user_agent' => request()->header('User-Agent')
        ]);
    }
}