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
        $this->loadAvailableOptions();
        
        // Initialize services
        $this->externalTransferService = app(ExternalFundsTransferService::class);
        $this->walletTransferService = app(MobileWalletTransferService::class);
        $this->internalTransferService = app(InternalFundsTransferService::class);
        
        // Set default account if available
        $this->debitAccount = config('services.nbc_payments.saccos_account', '');
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
        $this->validate($this->getValidationRules());
        
        $this->isLoading = true;
        $this->errorMessage = '';
        
        try {
            if ($this->transferCategory === 'internal') {
                $this->verifyInternalAccount();
            } elseif ($this->transferCategory === 'external') {
                switch ($this->transferType) {
                    case 'bank':
                        $this->verifyBankAccount();
                        break;
                        
                    case 'wallet':
                        $this->verifyWallet();
                        break;
                }
            }
            
            if (!empty($this->verificationData)) {
                $this->currentPhase = 'verify';
            }
            
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            Log::error('Verification failed', [
                'type' => $this->transferType,
                'error' => $e->getMessage()
            ]);
        } finally {
            $this->isLoading = false;
        }
    }

    protected function verifyBankAccount()
    {
        $result = $this->externalTransferService->lookupAccount(
            $this->beneficiaryAccount,
            $this->bankCode,
            floatval($this->amount)
        );
        
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
        } else {
            throw new Exception($result['error'] ?? 'Account verification failed');
        }
    }

    protected function verifyWallet()
    {
        $result = $this->walletTransferService->lookupWallet(
            $this->phoneNumber,
            $this->walletProvider,
            floatval($this->amount)
        );
        
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
        } else {
            throw new Exception($result['error'] ?? 'Wallet verification failed');
        }
    }

    protected function verifyInternalAccount()
    {
        $result = $this->internalTransferService->lookupAccount($this->internalAccount);
        
        if ($result['success']) {
            $this->verificationData = [
                'type' => 'internal',
                'account_name' => $result['account_name'] ?? 'NBC Account Verified',
                'account_number' => $this->internalAccount,
                'branch' => $result['branch_name'] ?? 'NBC Branch',
                'can_receive' => $result['can_receive'] ?? true
            ];
            
            $this->beneficiaryName = $result['account_name'] ?? '';
        } else {
            throw new Exception($result['error'] ?? 'Account verification failed');
        }
    }

    public function executeTransfer()
    {
        $this->isLoading = true;
        $this->errorMessage = '';
        $this->currentPhase = 'processing';
        
        try {
            $result = [];
            
            if ($this->transferCategory === 'internal') {
                $result = $this->executeInternalTransfer();
            } elseif ($this->transferCategory === 'external') {
                switch ($this->transferType) {
                    case 'bank':
                        $result = $this->executeExternalTransfer();
                        break;
                        
                    case 'wallet':
                        $result = $this->executeWalletTransfer();
                        break;
                }
            }
            
            if ($result['success'] ?? false) {
                $this->successMessage = $result['message'] ?? 'Transfer completed successfully';
                $this->transactionReference = $result['reference'] ?? $result['nbc_reference'] ?? 'REF' . time();
                $this->currentPhase = 'complete';
                
                // Log successful transaction
                $this->logTransaction($result);
                
            } else {
                throw new Exception($result['error'] ?? 'Transfer failed');
            }
            
        } catch (Exception $e) {
            $this->errorMessage = $e->getMessage();
            $this->currentPhase = 'verify'; // Go back to verify phase
            Log::error('Transfer failed', [
                'type' => $this->transferType,
                'error' => $e->getMessage()
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
            'transferType'
        ]);
        
        $this->currentPhase = 'form';
    }

    public function goBack()
    {
        if ($this->currentPhase === 'verify') {
            $this->currentPhase = 'form';
        } elseif ($this->currentPhase === 'complete') {
            $this->resetForm();
        }
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
        return view('livewire.payments.money-transfer');
    }
}