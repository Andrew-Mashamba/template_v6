<?php

namespace App\Http\Livewire\Loans;

use App\Models\LoansModel;
use App\Models\ClientsModel;
use App\Models\loan_sub_products;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;
use Exception;

class Liquidation extends Component
{
    use WithPagination;

    // Search and Filters
    public $searchType = 'loan_id';
    public $searchValue = '';
    public $branchFilter = '';
    public $productFilter = '';
    public $statusFilter = 'ACTIVE';
    
    // Pagination
    public $perPage = 20;
    
    // Selected Loan
    public $selectedLoan = null;
    public $showLiquidationModal = false;
    
    // Liquidation Details
    public $liquidationAmount = 0;
    public $liquidationPenalty = 0;
    public $totalLiquidationAmount = 0;
    public $paymentMethod = 'CASH';
    public $paymentReference = '';
    public $liquidationReason = '';
    public $liquidationNotes = '';
    
    // Bank/Mobile Payment Fields
    public $bankName = '';
    public $bankReference = '';
    public $mobileProvider = '';
    public $mobileNumber = '';
    
    // Early Settlement
    public $earlySettlementAmount = 0;
    public $penaltyWaived = false;
    public $waiverReason = '';
    
    // OTP Confirmation
    public $confirmLiquidation = false;
    public $confirmationCode = '';
    public $generatedOTP = '';
    public $otpSent = false;
    public $otpSentTime = null;
    public $memberPhone = '';
    public $memberEmail = '';
    
    protected $listeners = [
        'refreshLiquidationTable' => '$refresh',
        'liquidateLoan' => 'openLiquidationModal'
    ];

    protected $rules = [
        'liquidationAmount' => 'required|numeric|min:0',
        'paymentMethod' => 'required|in:CASH,BANK,MOBILE,INTERNAL',
        'liquidationReason' => 'required|string|max:500',
        'confirmationCode' => 'required_if:confirmLiquidation,true'
    ];

    public function mount()
    {
        $this->loadStatistics();
    }

    public function loadStatistics()
    {
        // Load any initial statistics if needed
    }

    public function updatedSearchValue()
    {
        $this->resetPage();
    }

    public function searchLoan()
    {
        $this->resetPage();
    }

    public function openLiquidationModal($loanId)
    {
        try {
            // Fetch loan details with client information
            $this->selectedLoan = LoansModel::with(['client'])
                ->where('loan_id', $loanId)
                ->where('status', 'ACTIVE')
                ->first();

            if (!$this->selectedLoan) {
                session()->flash('error', 'Loan not found or not active.');
                return;
            }

            // Get product information
            if ($this->selectedLoan->loan_sub_product) {
                $product = loan_sub_products::where('sub_product_id', $this->selectedLoan->loan_sub_product)->first();
                $this->selectedLoan->product_name = $product ? $product->sub_product_name : 'N/A';
            } else {
                $this->selectedLoan->product_name = 'N/A';
            }

            // Calculate outstanding balance
            $this->calculateOutstandingBalance();
            
            // Get member contact information
            if ($this->selectedLoan->client) {
                $this->memberPhone = $this->selectedLoan->client->phone_number ?? '';
                $this->memberEmail = $this->selectedLoan->client->email ?? '';
            }
            
            // Reset OTP status
            $this->otpSent = false;
            $this->generatedOTP = '';
            $this->confirmationCode = '';
            
            // Reset form fields
            $this->resetLiquidationForm();
            
            $this->showLiquidationModal = true;
            
        } catch (Exception $e) {
            Log::error('Error opening liquidation modal', [
                'loan_id' => $loanId,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Error loading loan details: ' . $e->getMessage());
        }
    }

    private function calculateOutstandingBalance()
    {
        if (!$this->selectedLoan) {
            return;
        }

        try {
            // Get loan account balance (Outstanding Balance = loan account balance)
            $loanAccount = DB::table('accounts')
                ->where('account_number', $this->selectedLoan->loan_account_number)
                ->first();
            
            $outstandingBalance = $loanAccount ? abs($loanAccount->balance ?? 0) : 0;
            
            // Get loan repayment schedule for other calculations
            $schedule = DB::table('loans_schedules')
                ->where('loan_id', $this->selectedLoan->loan_id)
                ->get();

            // Calculate total expected
            $totalExpected = $schedule->sum('installment');
            
            // Calculate total paid
            $totalPaid = DB::table('loan_repayments')
                ->where('loan_id', $this->selectedLoan->loan_id)
                ->where('status', 'COMPLETED')
                ->sum('amount');
            
            // Calculate early settlement if applicable
            $remainingMonths = $schedule->where('completion_status', '!=', 'PAID')->count();
            $monthlyInterestRate = ($this->selectedLoan->interest_rate ?? 0) / 100 / 12;
            
            // Early settlement calculation (principal + current month interest only)
            $outstandingPrincipal = $this->selectedLoan->principle - ($totalPaid - ($totalExpected - $this->selectedLoan->principle));
            $currentMonthInterest = $outstandingPrincipal * $monthlyInterestRate;
            
            $this->earlySettlementAmount = $outstandingPrincipal + $currentMonthInterest;
            
            // Calculate 5% liquidation penalty
            $this->liquidationAmount = $outstandingBalance;
            $this->liquidationPenalty = $outstandingBalance * 0.05; // 5% penalty
            $this->totalLiquidationAmount = $this->liquidationAmount + $this->liquidationPenalty;
            
            // Store in selectedLoan for display
            $this->selectedLoan->outstanding_balance = $outstandingBalance;
            $this->selectedLoan->total_paid = $totalPaid;
            $this->selectedLoan->total_expected = $totalExpected;
            $this->selectedLoan->outstanding_principal = $outstandingPrincipal;
            $this->selectedLoan->remaining_installments = $remainingMonths;
            
        } catch (Exception $e) {
            Log::error('Error calculating outstanding balance', [
                'loan_id' => $this->selectedLoan->loan_id,
                'error' => $e->getMessage()
            ]);
            $this->liquidationAmount = 0;
        }
    }

    public function applyEarlySettlement()
    {
        if ($this->earlySettlementAmount > 0) {
            $this->liquidationAmount = $this->earlySettlementAmount;
            session()->flash('info', 'Early settlement amount applied. This includes outstanding principal and current month interest only.');
        }
    }

    public function sendOTP()
    {
        if (!$this->selectedLoan || !$this->selectedLoan->client) {
            session()->flash('error', 'Unable to send OTP. Client information not found.');
            return;
        }

        try {
            // Generate 6-digit OTP
            $this->generatedOTP = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store OTP in cache with 5 minute expiry
            $cacheKey = 'liquidation_otp_' . $this->selectedLoan->loan_id;
            Cache::put($cacheKey, $this->generatedOTP, Carbon::now()->addMinutes(5));
            
            // Send OTP via SMS
            $message = "Your loan liquidation OTP is: {$this->generatedOTP}. Valid for 5 minutes. Do not share with anyone.";
            
            if ($this->memberPhone) {
                try {
                    $smsService = app(SmsService::class);
                    $smsService->sendSMS($this->memberPhone, $message);
                    $this->otpSent = true;
                    $this->otpSentTime = now();
                    session()->flash('success', 'OTP has been sent to ' . substr($this->memberPhone, 0, 3) . '****' . substr($this->memberPhone, -2));
                } catch (Exception $e) {
                    Log::error('Failed to send OTP via SMS', [
                        'loan_id' => $this->selectedLoan->loan_id,
                        'error' => $e->getMessage()
                    ]);
                    session()->flash('error', 'Failed to send OTP. Please try again.');
                }
            } else {
                session()->flash('error', 'No phone number found for this member.');
            }
            
        } catch (Exception $e) {
            Log::error('Error generating OTP', [
                'loan_id' => $this->selectedLoan->loan_id,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Error generating OTP: ' . $e->getMessage());
        }
    }

    public function verifyOTP()
    {
        if (!$this->selectedLoan) {
            return false;
        }

        $cacheKey = 'liquidation_otp_' . $this->selectedLoan->loan_id;
        $storedOTP = Cache::get($cacheKey);
        
        if (!$storedOTP) {
            session()->flash('error', 'OTP has expired. Please request a new one.');
            return false;
        }
        
        if ($this->confirmationCode !== $storedOTP) {
            session()->flash('error', 'Invalid OTP. Please try again.');
            return false;
        }
        
        // Clear OTP after successful verification
        Cache::forget($cacheKey);
        return true;
    }

    public function processLiquidation()
    {
        $rules = [
            'paymentMethod' => 'required',
            'liquidationReason' => 'required|string',
            'confirmationCode' => 'required|digits:6'
        ];

        // Add waiver reason validation if penalty is being waived
        if ($this->penaltyWaived) {
            $rules['waiverReason'] = 'required|string|min:10';
        }

        $this->validate($rules);

        // Verify OTP
        if (!$this->verifyOTP()) {
            return;
        }

        // Calculate final amount based on waiver status
        $finalAmount = $this->penaltyWaived ? $this->liquidationAmount : $this->totalLiquidationAmount;

        if (!$this->selectedLoan) {
            session()->flash('error', 'No loan selected for liquidation.');
            return;
        }

        try {
            DB::beginTransaction();

            // Generate receipt number
            $receiptNumber = 'LIQ-' . date('YmdHis') . '-' . $this->selectedLoan->loan_id;
            
            // Record the liquidation payment with penalty
            $noteText = $this->liquidationNotes;
            if (!$this->penaltyWaived) {
                $noteText .= ' (Includes 5% liquidation penalty)';
            } else {
                $noteText .= ' (Penalty waived - Reason: ' . $this->waiverReason . ')';
            }

            $paymentId = DB::table('loan_repayments')->insertGetId([
                'loan_id' => $this->selectedLoan->loan_id,
                'client_number' => $this->selectedLoan->client_number,
                'receipt_number' => $receiptNumber,
                'amount' => $finalAmount,
                'payment_date' => now(),
                'payment_method' => $this->paymentMethod,
                'payment_reference' => $this->getPaymentReference(),
                'payment_type' => 'LIQUIDATION',
                'status' => 'COMPLETED',
                'processed_by' => auth()->id(),
                'notes' => $noteText,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Update all unpaid schedules to PAID
            DB::table('loans_schedules')
                ->where('loan_id', $this->selectedLoan->loan_id)
                ->where('completion_status', '!=', 'PAID')
                ->update([
                    'completion_status' => 'PAID',
                    'payment' => DB::raw('installment'),
                    'last_payment_date' => now(),
                    'updated_at' => now()
                ]);

            // Update loan status to CLOSED
            DB::table('loans')
                ->where('loan_id', $this->selectedLoan->loan_id)
                ->update([
                    'status' => 'CLOSED',
                    'closure_date' => now(),
                    'closure_reason' => 'LIQUIDATION',
                    'closure_notes' => $this->liquidationReason,
                    'outstanding_balance' => 0,
                    'days_in_arrears' => 0,
                    'updated_at' => now()
                ]);

            // Record liquidation transaction with penalty details
            DB::table('loan_liquidations')->insert([
                'loan_id' => $this->selectedLoan->loan_id,
                'client_number' => $this->selectedLoan->client_number,
                'liquidation_date' => now(),
                'original_balance' => $this->selectedLoan->outstanding_balance,
                'liquidation_amount' => $this->liquidationAmount,
                'penalty_amount' => $this->liquidationPenalty,
                'total_amount' => $finalAmount,
                'penalty_waived' => $this->penaltyWaived,
                'waiver_amount' => $this->penaltyWaived ? $this->liquidationPenalty : 0,
                'waiver_reason' => $this->waiverReason,
                'payment_method' => $this->paymentMethod,
                'payment_reference' => $this->getPaymentReference(),
                'receipt_number' => $receiptNumber,
                'reason' => $this->liquidationReason,
                'notes' => $this->liquidationNotes,
                'processed_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // Log the liquidation
            Log::info('Loan liquidated successfully', [
                'loan_id' => $this->selectedLoan->loan_id,
                'client' => $this->selectedLoan->client_number,
                'amount' => $this->liquidationAmount,
                'receipt' => $receiptNumber,
                'processed_by' => auth()->id()
            ]);

            DB::commit();

            session()->flash('success', "Loan {$this->selectedLoan->loan_id} has been successfully liquidated. Receipt: {$receiptNumber}");
            
            $this->closeLiquidationModal();
            $this->emit('refreshLiquidationTable');
            
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Liquidation failed', [
                'loan_id' => $this->selectedLoan->loan_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'Liquidation failed: ' . $e->getMessage());
        }
    }

    private function getPaymentReference()
    {
        switch ($this->paymentMethod) {
            case 'BANK':
                return $this->bankName . ' - ' . $this->bankReference;
            case 'MOBILE':
                return $this->mobileProvider . ' - ' . $this->mobileNumber;
            case 'INTERNAL':
                return 'Internal Transfer - ' . $this->paymentReference;
            default:
                return $this->paymentReference ?: 'CASH';
        }
    }

    public function closeLiquidationModal()
    {
        $this->showLiquidationModal = false;
        $this->resetLiquidationForm();
    }

    private function resetLiquidationForm()
    {
        $this->liquidationAmount = 0;
        $this->liquidationPenalty = 0;
        $this->totalLiquidationAmount = 0;
        $this->paymentMethod = 'CASH';
        $this->paymentReference = '';
        $this->liquidationReason = '';
        $this->liquidationNotes = '';
        $this->bankName = '';
        $this->bankReference = '';
        $this->mobileProvider = '';
        $this->mobileNumber = '';
        $this->penaltyWaived = false;
        $this->waiverReason = '';
        $this->confirmLiquidation = false;
        $this->confirmationCode = '';
        $this->otpSent = false;
        $this->generatedOTP = '';
        $this->otpSentTime = null;
        $this->resetValidation();
    }

    public function getLoansProperty()
    {
        $query = LoansModel::query()
            ->with(['client'])
            ->where('status', $this->statusFilter ?: 'ACTIVE');

        // Apply search
        if (!empty($this->searchValue)) {
            $query->where(function ($q) {
                switch ($this->searchType) {
                    case 'loan_id':
                        $q->where('loan_id', 'like', '%' . $this->searchValue . '%');
                        break;
                    case 'account_number':
                        $q->where('loan_account_number', 'like', '%' . $this->searchValue . '%');
                        break;
                    case 'member_number':
                        $q->where('client_number', 'like', '%' . $this->searchValue . '%');
                        break;
                    case 'member_name':
                        $q->whereHas('client', function ($q) {
                            $q->where('first_name', 'like', '%' . $this->searchValue . '%')
                                ->orWhere('last_name', 'like', '%' . $this->searchValue . '%');
                        });
                        break;
                }
            });
        }

        // Apply filters
        if (!empty($this->branchFilter)) {
            $query->where('branch_id', $this->branchFilter);
        }

        if (!empty($this->productFilter)) {
            $query->where('loan_sub_product', $this->productFilter);
        }

        return $query->orderBy('created_at', 'desc')->paginate($this->perPage);
    }

    public function render()
    {
        $loans = $this->loans;
        
        // Get filter options
        $branches = DB::table('branches')->get();
        $products = loan_sub_products::get();
        
        // Banks and Mobile Providers
        $banks = [
            'CRDB' => 'CRDB Bank',
            'NMB' => 'NMB Bank',
            'NBC' => 'NBC Bank',
            'STANBIC' => 'Stanbic Bank',
            'EXIM' => 'EXIM Bank',
            'AZANIA' => 'Azania Bank'
        ];
        
        $mobileProviders = [
            'MPESA' => 'M-Pesa',
            'TIGOPESA' => 'Tigo Pesa',
            'AIRTELMONEY' => 'Airtel Money',
            'HALOPESA' => 'Halo Pesa'
        ];

        return view('livewire.loans.liquidation', [
            'loans' => $loans,
            'branches' => $branches,
            'products' => $products,
            'banks' => $banks,
            'mobileProviders' => $mobileProviders
        ]);
    }
}