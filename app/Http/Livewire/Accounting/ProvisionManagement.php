<?php

namespace App\Http\Livewire\Accounting;

use Livewire\Component;
use Livewire\WithPagination;
use App\Services\LoanProvisionCalculationService;
use App\Models\LoansModel;
use App\Models\LoanProvisionSettings;
use App\Models\LoanLossProvision;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ProvisionManagement extends Component
{
    use WithPagination;
    
    // Tab management
    public $activeTab = 'overview';
    
    // Provision calculation parameters
    public $calculationDate;
    public $provisionMethod = 'ifrs9'; // 'ifrs9', 'regulatory', 'hybrid'
    public $includeForwardLooking = true;
    public $economicScenario = 'base'; // 'optimistic', 'base', 'pessimistic'
    
    // Stage classification thresholds
    public $stage1DaysThreshold = 0;
    public $stage2DaysThreshold = 30;
    public $stage3DaysThreshold = 90;
    
    // Provision rates by stage
    public $stage1Rate = 1.0; // 1%
    public $stage2Rate = 10.0; // 10%
    public $stage3Rate = 100.0; // 100%
    
    // Filter and search
    public $searchTerm = '';
    public $filterStage = 'all';
    public $filterProduct = 'all';
    public $filterBranch = 'all';
    
    // Modal controls
    public $showCalculationModal = false;
    public $showSettingsModal = false;
    public $showPostingModal = false;
    public $showReversalModal = false;
    
    // Selected items
    public $selectedLoans = [];
    public $selectedProvision = null;
    
    // Report parameters
    public $reportFromDate;
    public $reportToDate;
    public $reportType = 'summary'; // 'summary', 'detailed', 'movement', 'staging'
    
    protected $paginationTheme = 'bootstrap';
    
    protected $rules = [
        'calculationDate' => 'required|date',
        'stage1Rate' => 'required|numeric|min:0|max:100',
        'stage2Rate' => 'required|numeric|min:0|max:100',
        'stage3Rate' => 'required|numeric|min:0|max:100',
        'economicScenario' => 'required|in:optimistic,base,pessimistic',
    ];
    
    protected $listeners = [
        'refreshProvisions' => '$refresh',
        'provisionCalculated' => 'handleProvisionCalculated',
        'provisionPosted' => 'handleProvisionPosted',
    ];
    
    public function mount()
    {
        $this->calculationDate = Carbon::now()->format('Y-m-d');
        $this->reportFromDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->reportToDate = Carbon::now()->format('Y-m-d');
        
        // Load current provision settings
        $this->loadProvisionSettings();
    }
    
    /**
     * Load provision settings from database
     */
    private function loadProvisionSettings()
    {
        $settings = DB::table('loan_provision_settings')->first();
        
        if ($settings) {
            $this->stage1DaysThreshold = $settings->stage1_days ?? 0;
            $this->stage2DaysThreshold = $settings->stage2_days ?? 30;
            $this->stage3DaysThreshold = $settings->stage3_days ?? 90;
            
            // Load provision rates based on classification
            $rates = DB::table('loan_provision_settings')
                ->whereIn('provision', ['PERFORMING', 'WATCH', 'SUBSTANDARD', 'DOUBTFUL', 'LOSS'])
                ->pluck('rate', 'provision')
                ->toArray();
            
            // Map to IFRS 9 stages
            $this->stage1Rate = $rates['PERFORMING'] ?? 1.0;
            $this->stage2Rate = $rates['WATCH'] ?? 10.0;
            $this->stage3Rate = $rates['LOSS'] ?? 100.0;
        }
    }
    
    /**
     * Get loans with provision calculation
     */
    public function getLoansWithProvisionsProperty()
    {
        $query = DB::table('loans as l')
            ->leftJoin('clients as c', 'l.client_id', '=', 'c.id')
            ->leftJoin('loan_products as lp', 'l.loan_product_id', '=', 'lp.id')
            ->leftJoin('loan_loss_provisions as llp', function ($join) {
                $join->on('l.loan_id', '=', 'llp.loan_id')
                    ->where('llp.provision_date', '=', $this->calculationDate);
            })
            ->select([
                'l.id',
                'l.loan_id',
                'l.client_number',
                'c.first_name',
                'c.last_name',
                'lp.loan_product_name',
                'l.loan_sub_product',
                'l.principle',
                'l.loan_balance',
                'l.total_arrears',
                'l.days_in_arrears',
                'l.loan_classification',
                'l.loan_status',
                'llp.ecl_stage',
                'llp.provision_amount',
                'llp.provision_rate',
                'llp.pd_rate',
                'llp.lgd_rate',
                'llp.ead_amount',
                DB::raw('CASE 
                    WHEN l.days_in_arrears = 0 THEN "Stage 1"
                    WHEN l.days_in_arrears BETWEEN 1 AND 30 THEN "Stage 2"
                    WHEN l.days_in_arrears > 30 THEN "Stage 3"
                    ELSE "Stage 1"
                END as calculated_stage'),
                DB::raw('l.loan_balance * 
                    CASE 
                        WHEN l.days_in_arrears = 0 THEN ' . ($this->stage1Rate / 100) . '
                        WHEN l.days_in_arrears BETWEEN 1 AND 30 THEN ' . ($this->stage2Rate / 100) . '
                        WHEN l.days_in_arrears > 30 THEN ' . ($this->stage3Rate / 100) . '
                        ELSE ' . ($this->stage1Rate / 100) . '
                    END as calculated_provision')
            ])
            ->where('l.loan_status', 'active');
        
        // Apply filters
        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('l.loan_id', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('l.client_number', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('c.first_name', 'like', '%' . $this->searchTerm . '%')
                    ->orWhere('c.last_name', 'like', '%' . $this->searchTerm . '%');
            });
        }
        
        if ($this->filterStage !== 'all') {
            $query->where('llp.ecl_stage', $this->filterStage);
        }
        
        if ($this->filterProduct !== 'all') {
            $query->where('l.loan_sub_product', $this->filterProduct);
        }
        
        return $query->orderBy('l.days_in_arrears', 'desc')->paginate(20);
    }
    
    /**
     * Get provision summary statistics
     */
    public function getProvisionSummaryProperty()
    {
        $summary = DB::table('loans as l')
            ->leftJoin('loan_loss_provisions as llp', function ($join) {
                $join->on('l.loan_id', '=', 'llp.loan_id')
                    ->where('llp.provision_date', '=', $this->calculationDate);
            })
            ->where('l.loan_status', 'active')
            ->selectRaw('
                COUNT(DISTINCT l.id) as total_loans,
                SUM(l.principle) as total_disbursed,
                SUM(l.loan_balance) as total_outstanding,
                SUM(l.total_arrears) as total_arrears,
                SUM(COALESCE(llp.provision_amount, 0)) as total_provisions,
                SUM(CASE WHEN llp.ecl_stage = 1 THEN l.loan_balance ELSE 0 END) as stage1_exposure,
                SUM(CASE WHEN llp.ecl_stage = 2 THEN l.loan_balance ELSE 0 END) as stage2_exposure,
                SUM(CASE WHEN llp.ecl_stage = 3 THEN l.loan_balance ELSE 0 END) as stage3_exposure,
                SUM(CASE WHEN llp.ecl_stage = 1 THEN llp.provision_amount ELSE 0 END) as stage1_provisions,
                SUM(CASE WHEN llp.ecl_stage = 2 THEN llp.provision_amount ELSE 0 END) as stage2_provisions,
                SUM(CASE WHEN llp.ecl_stage = 3 THEN llp.provision_amount ELSE 0 END) as stage3_provisions,
                COUNT(CASE WHEN llp.ecl_stage = 1 THEN 1 END) as stage1_count,
                COUNT(CASE WHEN llp.ecl_stage = 2 THEN 1 END) as stage2_count,
                COUNT(CASE WHEN llp.ecl_stage = 3 THEN 1 END) as stage3_count
            ')
            ->first();
        
        // Calculate coverage ratios
        $summary->provision_coverage = $summary->total_outstanding > 0 
            ? ($summary->total_provisions / $summary->total_outstanding) * 100 
            : 0;
        
        $summary->stage1_coverage = $summary->stage1_exposure > 0 
            ? ($summary->stage1_provisions / $summary->stage1_exposure) * 100 
            : 0;
        
        $summary->stage2_coverage = $summary->stage2_exposure > 0 
            ? ($summary->stage2_provisions / $summary->stage2_exposure) * 100 
            : 0;
        
        $summary->stage3_coverage = $summary->stage3_exposure > 0 
            ? ($summary->stage3_provisions / $summary->stage3_exposure) * 100 
            : 0;
        
        return $summary;
    }
    
    /**
     * Calculate provisions for all loans
     */
    public function calculateProvisions()
    {
        $this->validate([
            'calculationDate' => 'required|date',
            'provisionMethod' => 'required|in:ifrs9,regulatory,hybrid',
        ]);
        
        try {
            DB::beginTransaction();
            
            // Get provision calculation service
            $service = app(LoanProvisionCalculationService::class);
            
            // Calculate provisions based on selected method
            $result = $service->calculateProvisions(
                $this->calculationDate,
                $this->provisionMethod,
                [
                    'include_forward_looking' => $this->includeForwardLooking,
                    'economic_scenario' => $this->economicScenario,
                    'stage1_rate' => $this->stage1Rate,
                    'stage2_rate' => $this->stage2Rate,
                    'stage3_rate' => $this->stage3Rate,
                ]
            );
            
            DB::commit();
            
            session()->flash('success', 'Provisions calculated successfully. Total provision: TZS ' . 
                number_format($result['total_provisions'], 2));
            
            $this->showCalculationModal = false;
            $this->emit('refreshProvisions');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provision calculation error: ' . $e->getMessage());
            session()->flash('error', 'Error calculating provisions: ' . $e->getMessage());
        }
    }
    
    /**
     * Post provisions to general ledger
     */
    public function postProvisionsToGL()
    {
        try {
            DB::beginTransaction();
            
            $provisions = DB::table('loan_loss_provisions')
                ->where('provision_date', $this->calculationDate)
                ->where('posted_to_gl', false)
                ->sum('provision_amount');
            
            if ($provisions <= 0) {
                throw new \Exception('No unposted provisions found for the selected date.');
            }
            
            // Create journal entry
            $journalId = DB::table('journal_entries')->insertGetId([
                'reference_no' => 'PROV-' . Carbon::parse($this->calculationDate)->format('Ymd'),
                'transaction_date' => $this->calculationDate,
                'description' => 'Loan loss provisions for ' . $this->calculationDate,
                'total_amount' => $provisions,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Debit: Loan Loss Provision Expense
            DB::table('journal_entry_lines')->insert([
                'journal_entry_id' => $journalId,
                'account_code' => '5010', // Loan Loss Provision Expense
                'account_name' => 'Loan Loss Provision Expense',
                'debit' => $provisions,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Credit: Allowance for Loan Losses
            DB::table('journal_entry_lines')->insert([
                'journal_entry_id' => $journalId,
                'account_code' => '1290', // Allowance for Loan Losses (contra asset)
                'account_name' => 'Allowance for Loan Losses',
                'debit' => 0,
                'credit' => $provisions,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Mark provisions as posted
            DB::table('loan_loss_provisions')
                ->where('provision_date', $this->calculationDate)
                ->where('posted_to_gl', false)
                ->update([
                    'posted_to_gl' => true,
                    'gl_posting_date' => now(),
                    'journal_entry_id' => $journalId,
                    'updated_at' => now(),
                ]);
            
            DB::commit();
            
            session()->flash('success', 'Provisions posted to General Ledger successfully. Journal Entry: ' . 
                'PROV-' . Carbon::parse($this->calculationDate)->format('Ymd'));
            
            $this->showPostingModal = false;
            $this->emit('refreshProvisions');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GL posting error: ' . $e->getMessage());
            session()->flash('error', 'Error posting to GL: ' . $e->getMessage());
        }
    }
    
    /**
     * Reverse provisions
     */
    public function reverseProvisions($provisionDate)
    {
        try {
            DB::beginTransaction();
            
            // Check if provisions exist and are posted
            $provisions = DB::table('loan_loss_provisions')
                ->where('provision_date', $provisionDate)
                ->where('posted_to_gl', true)
                ->first();
            
            if (!$provisions) {
                throw new \Exception('No posted provisions found for reversal.');
            }
            
            // Create reversal journal entry
            $originalJournal = DB::table('journal_entries')
                ->where('id', $provisions->journal_entry_id)
                ->first();
            
            $reversalJournalId = DB::table('journal_entries')->insertGetId([
                'reference_no' => 'REV-' . $originalJournal->reference_no,
                'transaction_date' => now()->format('Y-m-d'),
                'description' => 'Reversal of ' . $originalJournal->description,
                'total_amount' => $originalJournal->total_amount,
                'is_reversal' => true,
                'reversed_journal_id' => $originalJournal->id,
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Reverse journal entries (swap debits and credits)
            $originalLines = DB::table('journal_entry_lines')
                ->where('journal_entry_id', $originalJournal->id)
                ->get();
            
            foreach ($originalLines as $line) {
                DB::table('journal_entry_lines')->insert([
                    'journal_entry_id' => $reversalJournalId,
                    'account_code' => $line->account_code,
                    'account_name' => $line->account_name,
                    'debit' => $line->credit, // Swap
                    'credit' => $line->debit, // Swap
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            
            // Mark provisions as reversed
            DB::table('loan_loss_provisions')
                ->where('provision_date', $provisionDate)
                ->update([
                    'is_reversed' => true,
                    'reversal_date' => now(),
                    'reversal_journal_id' => $reversalJournalId,
                    'updated_at' => now(),
                ]);
            
            DB::commit();
            
            session()->flash('success', 'Provisions reversed successfully. Reversal Journal: REV-' . 
                $originalJournal->reference_no);
            
            $this->showReversalModal = false;
            $this->emit('refreshProvisions');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Provision reversal error: ' . $e->getMessage());
            session()->flash('error', 'Error reversing provisions: ' . $e->getMessage());
        }
    }
    
    /**
     * Update provision settings
     */
    public function updateProvisionSettings()
    {
        $this->validate([
            'stage1Rate' => 'required|numeric|min:0|max:100',
            'stage2Rate' => 'required|numeric|min:0|max:100',
            'stage3Rate' => 'required|numeric|min:0|max:100',
            'stage1DaysThreshold' => 'required|integer|min:0',
            'stage2DaysThreshold' => 'required|integer|min:0',
            'stage3DaysThreshold' => 'required|integer|min:0',
        ]);
        
        try {
            // Update provision rates
            DB::table('loan_provision_settings')
                ->where('provision', 'PERFORMING')
                ->update(['rate' => $this->stage1Rate]);
            
            DB::table('loan_provision_settings')
                ->where('provision', 'WATCH')
                ->update(['rate' => $this->stage2Rate]);
            
            DB::table('loan_provision_settings')
                ->where('provision', 'LOSS')
                ->update(['rate' => $this->stage3Rate]);
            
            // Store stage thresholds
            DB::table('loan_provision_settings')
                ->updateOrInsert(
                    ['id' => 1],
                    [
                        'stage1_days' => $this->stage1DaysThreshold,
                        'stage2_days' => $this->stage2DaysThreshold,
                        'stage3_days' => $this->stage3DaysThreshold,
                        'updated_at' => now(),
                    ]
                );
            
            session()->flash('success', 'Provision settings updated successfully.');
            $this->showSettingsModal = false;
            
        } catch (\Exception $e) {
            Log::error('Settings update error: ' . $e->getMessage());
            session()->flash('error', 'Error updating settings: ' . $e->getMessage());
        }
    }
    
    /**
     * Export provisions report
     */
    public function exportProvisionReport()
    {
        $filename = 'loan_provisions_' . $this->calculationDate . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];
        
        $provisions = $this->loansWithProvisions;
        
        $callback = function() use ($provisions) {
            $file = fopen('php://output', 'w');
            
            // Headers
            fputcsv($file, [
                'Loan ID', 'Client Number', 'Client Name', 'Product', 
                'Principal', 'Outstanding', 'Arrears', 'Days in Arrears',
                'Classification', 'ECL Stage', 'Provision Rate (%)', 
                'Provision Amount', 'PD Rate', 'LGD Rate', 'EAD'
            ]);
            
            // Data
            foreach ($provisions as $provision) {
                fputcsv($file, [
                    $provision->loan_id,
                    $provision->client_number,
                    $provision->first_name . ' ' . $provision->last_name,
                    $provision->loan_sub_product,
                    $provision->principle,
                    $provision->loan_balance,
                    $provision->total_arrears,
                    $provision->days_in_arrears,
                    $provision->loan_classification,
                    $provision->ecl_stage ?? $provision->calculated_stage,
                    $provision->provision_rate ?? ($provision->calculated_provision / $provision->loan_balance * 100),
                    $provision->provision_amount ?? $provision->calculated_provision,
                    $provision->pd_rate ?? 'N/A',
                    $provision->lgd_rate ?? 'N/A',
                    $provision->ead_amount ?? $provision->loan_balance,
                ]);
            }
            
            fclose($file);
        };
        
        return response()->stream($callback, 200, $headers);
    }
    
    /**
     * Generate provision movement report
     */
    public function getProvisionMovementProperty()
    {
        return DB::table('loan_loss_provisions')
            ->selectRaw('
                provision_date,
                SUM(CASE WHEN ecl_stage = 1 THEN provision_amount ELSE 0 END) as stage1,
                SUM(CASE WHEN ecl_stage = 2 THEN provision_amount ELSE 0 END) as stage2,
                SUM(CASE WHEN ecl_stage = 3 THEN provision_amount ELSE 0 END) as stage3,
                SUM(provision_amount) as total
            ')
            ->whereBetween('provision_date', [$this->reportFromDate, $this->reportToDate])
            ->groupBy('provision_date')
            ->orderBy('provision_date')
            ->get();
    }
    
    public function render()
    {
        return view('livewire.accounting.provision-management', [
            'loansWithProvisions' => $this->loansWithProvisions,
            'provisionSummary' => $this->provisionSummary,
            'provisionMovement' => $this->activeTab === 'analytics' ? $this->provisionMovement : collect(),
            'loanProducts' => DB::table('loan_products')->pluck('loan_product_name', 'id'),
        ]);
    }
}