<?php

namespace App\Http\Livewire\Accounting;

use App\Models\AccountsModel;
use App\Models\Loan;
use App\Models\loans_schedules;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;

class LoanLossReserveManager extends Component
{
    // View Controls
    public $viewMode = 'dashboard'; // dashboard, provision, writeoff, history
    
    // Reserve Management
    public $currentYear;
    public $currentMonth;
    public $profits = 0;
    public $percentage = 5; // Default 5% provision rate
    public $reserve_amount = 0;
    public $source = '';
    
    // Portfolio Analysis
    public $loanPortfolioValue = 0;
    public $currentReserveBalance = 0;
    public $requiredReserve = 0;
    public $provisionGap = 0;
    
    // Loan Aging Categories
    public $loanAging = [];
    public $provisionRates = [
        'current' => 1,      // 0-30 days: 1%
        'watch' => 5,        // 31-60 days: 5%
        'substandard' => 25, // 61-90 days: 25%
        'doubtful' => 50,    // 91-180 days: 50%
        'loss' => 100        // >180 days: 100%
    ];
    
    // Dashboard Statistics
    public $stats = [
        'coverage_ratio' => 0,
        'npl_ratio' => 0,
        'provision_coverage' => 0,
        'write_off_ytd' => 0
    ];
    
    // History Collections
    public $provisionHistory = [];
    public $writeOffHistory = [];
    public $adjustmentHistory = [];
    
    // For Write-offs
    public $selectedLoans = [];
    public $writeOffReason = '';
    public $actualLoanLosses = 0;
    public $adjustments = 0;
    
    // GL Account Codes
    public $reserveAccount = '2400';     // Loan Loss Reserve (Liability)
    public $expenseAccount = '5100';     // Loan Loss Expense
    public $recoveryAccount = '4200';    // Recovery Income
    
    // Status tracking
    public $year;
    public $status = 'pending';
    public $editMode = false;
    
    public function mount()
    {
        $this->currentYear = date('Y');
        $this->currentMonth = date('n');
        $this->year = $this->currentYear;
        $this->loadDashboardData();
    }
    
    public function loadDashboardData()
    {
        $this->calculateLoanPortfolio();
        $this->calculateCurrentReserve();
        $this->calculateLoanAging();
        $this->calculateRequiredReserve();
        $this->loadStatistics();
        $this->loadHistory();
    }
    
    private function calculateLoanPortfolio()
    {
        // Get loan account numbers from active loans
        $loanAccountNumbers = Loan::where('status', 'ACTIVE')
            ->pluck('loan_account_number')
            ->filter() // Remove null values
            ->toArray();
        
        // Get total balances from accounts table
        $this->loanPortfolioValue = DB::table('accounts')
            ->whereIn('account_number', $loanAccountNumbers)
            ->sum(DB::raw('CAST(balance AS DECIMAL(20,2))')) ?? 0;
    }
    
    private function calculateCurrentReserve()
    {
        // Get current reserve balance from GL
        $credits = DB::table('general_ledger')
            ->where('record_on_account_number', $this->reserveAccount)
            ->sum(DB::raw('CAST(credit AS DECIMAL(20,2))')) ?? 0;
            
        $debits = DB::table('general_ledger')
            ->where('record_on_account_number', $this->reserveAccount)
            ->sum(DB::raw('CAST(debit AS DECIMAL(20,2))')) ?? 0;
            
        // Reserve is a credit balance account
        $this->currentReserveBalance = $credits - $debits;
    }
    
    private function calculateLoanAging()
    {
        $this->loanAging = [];
        
        // Get latest schedule entry for each active loan to get current arrears status
        $latestSchedules = DB::table('loans_schedules as ls1')
            ->select('ls1.*')
            ->join('loans as l', function($join) {
                $join->on(DB::raw('CAST(l.id AS VARCHAR)'), '=', DB::raw('CAST(ls1.loan_id AS VARCHAR)'));
            })
            ->where('l.status', 'ACTIVE')
            ->whereRaw('ls1.id = (
                SELECT MAX(ls2.id) 
                FROM loans_schedules ls2 
                WHERE CAST(ls2.loan_id AS VARCHAR) = CAST(ls1.loan_id AS VARCHAR)
            )')
            ->get();
        
        // Initialize aging buckets
        $agingBuckets = [
            'current' => [],
            'watch' => [],
            'substandard' => [],
            'doubtful' => [],
            'loss' => []
        ];
        
        // Categorize loans based on days_in_arrears from loans_schedules
        foreach ($latestSchedules as $schedule) {
            $daysInArrears = $schedule->days_in_arrears ?? 0;
            $loanId = $schedule->loan_id;
            
            // Get loan account number
            $loan = Loan::find($loanId);
            if (!$loan || !$loan->loan_account_number) continue;
            
            // Get current balance from accounts table
            $balance = DB::table('accounts')
                ->where('account_number', $loan->loan_account_number)
                ->value(DB::raw('CAST(balance AS DECIMAL(20,2))')) ?? 0;
            
            if ($balance <= 0) continue; // Skip loans with no balance
            
            // Categorize based on days in arrears
            if ($daysInArrears <= 30) {
                $agingBuckets['current'][] = ['loan_account_number' => $loan->loan_account_number, 'balance' => $balance, 'days' => $daysInArrears];
            } elseif ($daysInArrears <= 60) {
                $agingBuckets['watch'][] = ['loan_account_number' => $loan->loan_account_number, 'balance' => $balance, 'days' => $daysInArrears];
            } elseif ($daysInArrears <= 90) {
                $agingBuckets['substandard'][] = ['loan_account_number' => $loan->loan_account_number, 'balance' => $balance, 'days' => $daysInArrears];
            } elseif ($daysInArrears <= 180) {
                $agingBuckets['doubtful'][] = ['loan_account_number' => $loan->loan_account_number, 'balance' => $balance, 'days' => $daysInArrears];
            } else {
                $agingBuckets['loss'][] = ['loan_account_number' => $loan->loan_account_number, 'balance' => $balance, 'days' => $daysInArrears];
            }
        }
        
        // Calculate totals for each category
        foreach ($agingBuckets as $category => $loans) {
            $totalAmount = array_sum(array_column($loans, 'balance'));
            $count = count($loans);
            $provisionRate = $this->provisionRates[$category] ?? 0;
            
            $this->loanAging[$category] = [
                'count' => $count,
                'amount' => $totalAmount,
                'provision_rate' => $provisionRate,
                'required_provision' => $totalAmount * $provisionRate / 100
            ];
        }
    }
    
    public function calculateRequiredReserve()
    {
        // Sum up required provisions from all aging categories
        $this->requiredReserve = 0;
        foreach ($this->loanAging as $category) {
            $this->requiredReserve += $category['required_provision'];
        }
        
        // Calculate provision gap
        $this->provisionGap = max(0, $this->requiredReserve - $this->currentReserveBalance);
        
        // Update reserve amount for display
        $this->reserve_amount = $this->provisionGap;
    }
    
    private function loadStatistics()
    {
        // Coverage Ratio: Reserve / Portfolio
        $this->stats['coverage_ratio'] = $this->loanPortfolioValue > 0 
            ? ($this->currentReserveBalance / $this->loanPortfolioValue) * 100 
            : 0;
            
        // NPL Ratio: Non-performing loans / Total loans
        $nplAmount = ($this->loanAging['substandard']['amount'] ?? 0) +
                     ($this->loanAging['doubtful']['amount'] ?? 0) +
                     ($this->loanAging['loss']['amount'] ?? 0);
                     
        $this->stats['npl_ratio'] = $this->loanPortfolioValue > 0
            ? ($nplAmount / $this->loanPortfolioValue) * 100
            : 0;
            
        // Provision Coverage: Current Reserve / Required Reserve
        $this->stats['provision_coverage'] = $this->requiredReserve > 0
            ? ($this->currentReserveBalance / $this->requiredReserve) * 100
            : 100;
            
        // Year-to-date write-offs
        $this->stats['write_off_ytd'] = DB::table('general_ledger')
            ->where('record_on_account_number', $this->reserveAccount)
            ->where('transaction_type', 'WRITE_OFF')
            ->whereYear('created_at', $this->currentYear)
            ->sum(DB::raw('CAST(debit AS DECIMAL(20,2))')) ?? 0;
    }
    
    private function loadHistory()
    {
        // Load provision history
        $this->provisionHistory = DB::table('general_ledger')
            ->where('record_on_account_number', $this->reserveAccount)
            ->where('transaction_type', 'PROVISION')
            ->whereYear('created_at', $this->currentYear)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
            
        // Load write-off history
        $this->writeOffHistory = DB::table('general_ledger')
            ->where('record_on_account_number', $this->reserveAccount)
            ->where('transaction_type', 'WRITE_OFF')
            ->whereYear('created_at', $this->currentYear)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();
    }
    
    public function updatedProfits()
    {
        $this->calculateLLR();
    }
    
    public function updatedPercentage()
    {
        $this->calculateLLR();
    }
    
    public function calculateLLR()
    {
        // Simple percentage calculation for display
        if ($this->profits > 0 && $this->percentage > 0) {
            $this->reserve_amount = ($this->profits * $this->percentage) / 100;
        } else {
            // Use provision gap if no manual profit entered
            $this->reserve_amount = $this->provisionGap;
        }
    }

    public function makeProvision()
    {
        // Validate inputs
        if ($this->reserve_amount <= 0) {
            session()->flash('error', 'Please calculate reserve amount first.');
            return;
        }
        
        if (empty($this->source)) {
            session()->flash('error', 'Please select a source account for the provision.');
            return;
        }
        
        DB::beginTransaction();
        
        try {
            $transactionId = 'LLR-' . time();
            $description = "Loan Loss Provision - " . Carbon::now()->format('F Y');
            
            // 1. Debit: Loan Loss Expense Account (increases expense)
            DB::table('general_ledger')->insert([
                'record_on_account_number' => $this->expenseAccount,
                'debit' => $this->reserve_amount,
                'credit' => 0,
                'description' => $description,
                'transaction_type' => 'PROVISION',
                'transaction_id' => $transactionId,
                'narration' => 'Loan loss provision expense',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // 2. Credit: Loan Loss Reserve Account (increases liability/reserve)
            DB::table('general_ledger')->insert([
                'record_on_account_number' => $this->reserveAccount,
                'debit' => 0,
                'credit' => $this->reserve_amount,
                'description' => $description,
                'transaction_type' => 'PROVISION',
                'transaction_id' => $transactionId,
                'narration' => 'Increase in loan loss reserve',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // 3. Record in loan_loss_reserves table for tracking
            DB::table('loan_loss_reserves')->insert([
                'year' => $this->currentYear,
                'profits' => $this->profits ?: 0,
                'percentage' => $this->percentage,
                'reserve_amount' => $this->reserve_amount,
                'initial_allocation' => $this->reserve_amount,
                'total_allocation' => $this->currentReserveBalance + $this->reserve_amount,
                'status' => 'allocated',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            DB::commit();
            
            session()->flash('message', 'Loan loss provision of ' . number_format($this->reserve_amount, 2) . ' TZS has been recorded successfully.');
            $this->loadDashboardData();
            $this->resetForm();
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to record provision: ' . $e->getMessage());
        }
    }

    public function editLLR($id)
    {
        // Not needed with new structure
        $this->editMode = true;
    }

    public function deleteLLR($id)
    {
        // This would require proper authorization and audit trail
        session()->flash('error', 'Deletion of reserves requires authorization.');
    }

    public function resetForm()
    {
        $this->editMode = false;
        $this->profits = 0;
        $this->percentage = 5; // Reset to default 5%
        $this->reserve_amount = 0;
        $this->source = '';
    }

    public function processWriteOff()
    {
        if (empty($this->selectedLoans)) {
            session()->flash('error', 'Please select loans to write off.');
            return;
        }
        
        if (empty($this->writeOffReason)) {
            session()->flash('error', 'Please provide a reason for the write-off.');
            return;
        }
        
        DB::beginTransaction();
        
        try {
            $transactionId = 'WO-' . time();
            $totalWriteOff = 0;
            
            foreach ($this->selectedLoans as $loanId) {
                $loan = Loan::find($loanId);
                if ($loan && $loan->status === 'ACTIVE') {
                    // Get current balance from accounts table
                    $writeOffAmount = DB::table('accounts')
                        ->where('account_number', $loan->loan_account_number)
                        ->value(DB::raw('CAST(balance AS DECIMAL(20,2))')) ?? 0;
                    
                    if ($writeOffAmount > 0) {
                        $totalWriteOff += $writeOffAmount;
                    
                    // 1. Debit: Loan Loss Reserve (reduce reserve)
                    DB::table('general_ledger')->insert([
                        'record_on_account_number' => $this->reserveAccount,
                        'debit' => $writeOffAmount,
                        'credit' => 0,
                        'description' => "Write-off loan: " . $loan->loan_account_number,
                        'transaction_type' => 'WRITE_OFF',
                        'transaction_id' => $transactionId,
                        'narration' => $this->writeOffReason,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                    // 2. Credit: Loan Account (reduce loan balance to zero)
                    DB::table('general_ledger')->insert([
                        'record_on_account_number' => $loan->loan_account_number,
                        'debit' => 0,
                        'credit' => $writeOffAmount,
                        'description' => "Loan written off",
                        'transaction_type' => 'WRITE_OFF',
                        'transaction_id' => $transactionId,
                        'narration' => $this->writeOffReason,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    
                        // 3. Update loan status
                        $loan->update([
                            'status' => 'WRITTEN_OFF'
                        ]);
                        
                        // 4. Update account balance to zero
                        DB::table('accounts')
                            ->where('account_number', $loan->loan_account_number)
                            ->update(['balance' => 0]);
                    }
                }
            }
            
            DB::commit();
            
            session()->flash('message', 'Successfully written off ' . count($this->selectedLoans) . ' loans totaling ' . number_format($totalWriteOff, 2) . ' TZS');
            $this->reset(['selectedLoans', 'writeOffReason']);
            $this->loadDashboardData();
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to process write-off: ' . $e->getMessage());
        }
    }

    public $writeOffCandidates = [];
    
    // Simplified initial allocation
    public function allocateInitial()
    {
        // This is now handled by makeProvision() method
        $this->makeProvision();
    }






















    // Periodic adjustment to reserves
    public function adjustReserve()
    {

        if (!is_numeric($this->adjustments) || $this->adjustments == 0) {
            session()->flash('error', 'Please enter a valid adjustment amount.');
            return;
        }
        
        DB::beginTransaction();
        
        try {
            $transactionId = 'ADJ-' . time();
            $description = $this->adjustments > 0 ? 'Increase in loan loss reserve' : 'Decrease in loan loss reserve';
            
            if ($this->adjustments > 0) {
                // Increase reserve
                DB::table('general_ledger')->insert([
                    'record_on_account_number' => $this->expenseAccount,
                    'debit' => abs($this->adjustments),
                    'credit' => 0,
                    'description' => $description,
                    'transaction_type' => 'ADJUSTMENT',
                    'transaction_id' => $transactionId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                DB::table('general_ledger')->insert([
                    'record_on_account_number' => $this->reserveAccount,
                    'debit' => 0,
                    'credit' => abs($this->adjustments),
                    'description' => $description,
                    'transaction_type' => 'ADJUSTMENT',
                    'transaction_id' => $transactionId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            } else {
                // Decrease reserve (reverse provision)
                DB::table('general_ledger')->insert([
                    'record_on_account_number' => $this->reserveAccount,
                    'debit' => abs($this->adjustments),
                    'credit' => 0,
                    'description' => $description,
                    'transaction_type' => 'ADJUSTMENT',
                    'transaction_id' => $transactionId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                
                DB::table('general_ledger')->insert([
                    'record_on_account_number' => $this->expenseAccount,
                    'debit' => 0,
                    'credit' => abs($this->adjustments),
                    'description' => $description,
                    'transaction_type' => 'ADJUSTMENT',
                    'transaction_id' => $transactionId,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            
            DB::commit();
            
            session()->flash('message', 'Reserve adjusted by ' . number_format(abs($this->adjustments), 2) . ' TZS');
            $this->adjustments = 0;
            $this->loadDashboardData();
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to adjust reserve: ' . $e->getMessage());
        }
    }

    // Finalize at year-end based on actual loan losses
    public function finalizeYearEnd()
    {
        if (!is_numeric($this->actualLoanLosses) || $this->actualLoanLosses < 0) {
            session()->flash('error', 'Please enter valid actual loan losses.');
            return;
        }
        
        // Compare actual losses with current reserve
        $shortfall = $this->actualLoanLosses - $this->currentReserveBalance;
        
        if ($shortfall > 0) {
            // Need to increase reserve to cover actual losses
            $this->adjustments = $shortfall;
            $this->adjustReserve();
            session()->flash('message', 'Year-end finalization: Reserve increased by ' . number_format($shortfall, 2) . ' TZS to cover actual losses.');
        } elseif ($shortfall < 0) {
            // Reserve exceeds actual losses
            session()->flash('message', 'Year-end finalization: Reserve adequate. Excess of ' . number_format(abs($shortfall), 2) . ' TZS can be reversed if needed.');
        } else {
            session()->flash('message', 'Year-end finalization: Reserve exactly matches actual losses.');
        }
        
        // Update status
        DB::table('loan_loss_reserves')
            ->where('year', $this->year)
            ->update(['status' => 'finalized', 'updated_at' => now()]);
    }

    public function changeViewMode($mode)
    {
        $this->viewMode = $mode;
        if ($mode === 'writeoff') {
            // Load loans eligible for write-off
            $this->loadWriteOffCandidates();
        }
    }
    
    private function loadWriteOffCandidates()
    {
        // Get loans that are severely delinquent (>180 days) based on loans_schedules
        $candidates = [];
        
        // Get latest schedule entry for each active loan where days_in_arrears > 180
        $severelyDelinquentSchedules = DB::table('loans_schedules as ls1')
            ->select('ls1.*', 'l.loan_account_number', 'l.client_number')
            ->join('loans as l', function($join) {
                $join->on(DB::raw('CAST(l.id AS VARCHAR)'), '=', DB::raw('CAST(ls1.loan_id AS VARCHAR)'));
            })
            ->where('l.status', 'ACTIVE')
            ->where('ls1.days_in_arrears', '>', 180)
            ->whereRaw('ls1.id = (
                SELECT MAX(ls2.id) 
                FROM loans_schedules ls2 
                WHERE CAST(ls2.loan_id AS VARCHAR) = CAST(ls1.loan_id AS VARCHAR)
            )')
            ->get();
        
        foreach ($severelyDelinquentSchedules as $schedule) {
            // Get current balance from accounts table
            $balance = DB::table('accounts')
                ->where('account_number', $schedule->loan_account_number)
                ->value(DB::raw('CAST(balance AS DECIMAL(20,2))')) ?? 0;
            
            if ($balance > 0) { // Only include loans with outstanding balance
                $candidates[] = (object)[
                    'id' => $schedule->loan_id,
                    'loan_account_number' => $schedule->loan_account_number,
                    'client_number' => $schedule->client_number,
                    'balance' => $balance,
                    'days_in_arrears' => $schedule->days_in_arrears,
                    'amount_in_arrears' => $schedule->amount_in_arrears ?? 0,
                    'installment_date' => $schedule->installment_date,
                    'next_payment_date' => $schedule->installment_date // Use installment_date as reference
                ];
            }
        }
        
        $this->writeOffCandidates = collect($candidates);
    }
    
    public function formatNumber($number)
    {
        return number_format($number, 2);
    }
    
    public function render()
    {
        return view('livewire.accounting.loan-loss-reserve-manager');
    }




}
