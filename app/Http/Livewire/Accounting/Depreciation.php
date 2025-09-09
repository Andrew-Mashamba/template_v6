<?php

namespace App\Http\Livewire\Accounting;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Concerns\FromArray;

class Depreciation extends Component
{
    // Company details
    public $companyName = 'NBC SACCOS LTD';
    
    // Period selection
    public $selectedYear;
    public $selectedMonth;
    public $depreciationPeriod = 'monthly'; // monthly, quarterly, yearly
    
    // Display options
    public $viewMode = 'summary'; // summary, detailed, journal
    public $assetCategory = 'all'; // all, ppe, intangible, other
    public $searchTerm = '';
    
    // Depreciation data
    public $assets = [];
    public $depreciationSummary = [];
    public $journalEntries = [];
    public $totals = [
        'asset_cost' => 0,
        'accumulated_depreciation' => 0,
        'current_depreciation' => 0,
        'net_book_value' => 0
    ];
    
    // Depreciation methods
    public $depreciationMethods = [
        'straight_line' => 'Straight Line',
        'declining_balance' => 'Declining Balance',
        'sum_of_years' => 'Sum of Years Digits',
        'units_of_production' => 'Units of Production'
    ];
    
    public function mount()
    {
        $this->selectedYear = date('Y');
        $this->selectedMonth = date('n');
        $this->loadDepreciationData();
    }
    
    public function updatedSelectedYear()
    {
        $this->loadDepreciationData();
    }
    
    public function updatedSelectedMonth()
    {
        $this->loadDepreciationData();
    }
    
    public function updatedDepreciationPeriod()
    {
        $this->loadDepreciationData();
    }
    
    public function updatedAssetCategory()
    {
        $this->loadDepreciationData();
    }
    
    public function updatedSearchTerm()
    {
        $this->loadDepreciationData();
    }
    
    public function changeViewMode($mode)
    {
        $this->viewMode = $mode;
        $this->loadDepreciationData();
    }
    
    public function loadDepreciationData()
    {
        $this->assets = [];
        $this->depreciationSummary = [];
        $this->journalEntries = [];
        $this->resetTotals();
        
        // Get assets that need depreciation
        $query = DB::table('ppes');
        
        // Apply category filter
        if ($this->assetCategory !== 'all') {
            $query->where('category', $this->assetCategory);
        }
        
        // Apply search filter
        if (!empty($this->searchTerm)) {
            $query->where(function($q) {
                $q->where('name', 'LIKE', '%' . $this->searchTerm . '%')
                  ->orWhere('account_number', 'LIKE', '%' . $this->searchTerm . '%');
            });
        }
        
        // Get active assets only
        $query->where('status', 'active');
        
        $assets = $query->get();
        
        // Calculate depreciation for each asset
        foreach ($assets as $asset) {
            $depreciationData = $this->calculateDepreciation($asset);
            
            if ($depreciationData) {
                $this->assets[] = $depreciationData;
                
                // Update totals
                $this->totals['asset_cost'] += $depreciationData['initial_value'];
                $this->totals['accumulated_depreciation'] += $depreciationData['accumulated_depreciation'];
                $this->totals['current_depreciation'] += $depreciationData['current_period_depreciation'];
                $this->totals['net_book_value'] += $depreciationData['net_book_value'];
            }
        }
        
        // Prepare journal entries if in journal view
        if ($this->viewMode === 'journal') {
            $this->prepareJournalEntries();
        }
        
        // Prepare summary by category
        $this->prepareSummaryData();
    }
    
    private function calculateDepreciation($asset)
    {
        // Basic depreciation calculation (straight-line method)
        $purchaseDate = Carbon::parse($asset->purchase_date);
        $currentDate = Carbon::now();
        
        // Check if asset should be depreciated
        if ($purchaseDate->isFuture()) {
            return null;
        }
        
        // Calculate initial value (including capitalized costs)
        $initialValue = $asset->purchase_price + 
                       ($asset->legal_fees ?? 0) + 
                       ($asset->registration_fees ?? 0) + 
                       ($asset->renovation_costs ?? 0) + 
                       ($asset->transportation_costs ?? 0) + 
                       ($asset->installation_costs ?? 0) + 
                       ($asset->other_costs ?? 0);
        
        // Calculate annual depreciation
        $usefulLife = $asset->useful_life ?? 5; // Default 5 years
        $salvageValue = $asset->salvage_value ?? 0;
        $depreciableAmount = $initialValue - $salvageValue;
        $annualDepreciation = $usefulLife > 0 ? $depreciableAmount / $usefulLife : 0;
        
        // Calculate monthly depreciation
        $monthlyDepreciation = $annualDepreciation / 12;
        
        // Calculate accumulated depreciation
        $monthsOwned = $purchaseDate->diffInMonths($currentDate);
        $accumulatedDepreciation = min($monthsOwned * $monthlyDepreciation, $depreciableAmount);
        
        // Calculate current period depreciation
        $currentPeriodDepreciation = 0;
        switch ($this->depreciationPeriod) {
            case 'monthly':
                $currentPeriodDepreciation = $monthlyDepreciation;
                break;
            case 'quarterly':
                $currentPeriodDepreciation = $monthlyDepreciation * 3;
                break;
            case 'yearly':
                $currentPeriodDepreciation = $annualDepreciation;
                break;
        }
        
        // Ensure we don't exceed depreciable amount
        if ($accumulatedDepreciation + $currentPeriodDepreciation > $depreciableAmount) {
            $currentPeriodDepreciation = $depreciableAmount - $accumulatedDepreciation;
        }
        
        // Calculate net book value
        $netBookValue = $initialValue - $accumulatedDepreciation;
        
        return [
            'id' => $asset->id,
            'name' => $asset->name,
            'account_number' => $asset->account_number ?? 'N/A',
            'category' => $asset->category ?? 'PPE',
            'purchase_date' => $purchaseDate->format('d/m/Y'),
            'useful_life' => $usefulLife,
            'initial_value' => $initialValue,
            'salvage_value' => $salvageValue,
            'depreciable_amount' => $depreciableAmount,
            'annual_depreciation' => $annualDepreciation,
            'monthly_depreciation' => $monthlyDepreciation,
            'accumulated_depreciation' => $accumulatedDepreciation,
            'current_period_depreciation' => $currentPeriodDepreciation,
            'net_book_value' => $netBookValue,
            'depreciation_method' => 'Straight Line',
            'months_owned' => $monthsOwned
        ];
    }
    
    private function prepareJournalEntries()
    {
        $this->journalEntries = [];
        
        // Group depreciation by category
        $depreciationByCategory = [];
        foreach ($this->assets as $asset) {
            $category = $asset['category'];
            if (!isset($depreciationByCategory[$category])) {
                $depreciationByCategory[$category] = 0;
            }
            $depreciationByCategory[$category] += $asset['current_period_depreciation'];
        }
        
        // Create journal entries
        $entryNumber = 1;
        foreach ($depreciationByCategory as $category => $amount) {
            if ($amount > 0) {
                // Debit: Depreciation Expense
                $this->journalEntries[] = [
                    'entry_number' => $entryNumber,
                    'account_name' => "Depreciation Expense - $category",
                    'account_code' => $this->getDepreciationExpenseAccount($category),
                    'debit' => $amount,
                    'credit' => 0,
                    'description' => "Depreciation for $category - " . $this->getPeriodDescription()
                ];
                
                // Credit: Accumulated Depreciation
                $this->journalEntries[] = [
                    'entry_number' => $entryNumber,
                    'account_name' => "Accumulated Depreciation - $category",
                    'account_code' => $this->getAccumulatedDepreciationAccount($category),
                    'debit' => 0,
                    'credit' => $amount,
                    'description' => "Accumulated depreciation for $category"
                ];
                
                $entryNumber++;
            }
        }
    }
    
    private function prepareSummaryData()
    {
        $this->depreciationSummary = [];
        
        // Group by category
        $summaryByCategory = [];
        foreach ($this->assets as $asset) {
            $category = $asset['category'];
            if (!isset($summaryByCategory[$category])) {
                $summaryByCategory[$category] = [
                    'category' => $category,
                    'asset_count' => 0,
                    'total_cost' => 0,
                    'accumulated_depreciation' => 0,
                    'current_depreciation' => 0,
                    'net_book_value' => 0
                ];
            }
            
            $summaryByCategory[$category]['asset_count']++;
            $summaryByCategory[$category]['total_cost'] += $asset['initial_value'];
            $summaryByCategory[$category]['accumulated_depreciation'] += $asset['accumulated_depreciation'];
            $summaryByCategory[$category]['current_depreciation'] += $asset['current_period_depreciation'];
            $summaryByCategory[$category]['net_book_value'] += $asset['net_book_value'];
        }
        
        $this->depreciationSummary = array_values($summaryByCategory);
    }
    
    private function getDepreciationExpenseAccount($category)
    {
        // Map categories to expense accounts
        $accounts = [
            'PPE' => '5200',
            'intangible' => '5201',
            'other' => '5202'
        ];
        
        return $accounts[$category] ?? '5200';
    }
    
    private function getAccumulatedDepreciationAccount($category)
    {
        // Map categories to accumulated depreciation accounts
        $accounts = [
            'PPE' => '1700',
            'intangible' => '1701',
            'other' => '1702'
        ];
        
        return $accounts[$category] ?? '1700';
    }
    
    private function getPeriodDescription()
    {
        switch ($this->depreciationPeriod) {
            case 'monthly':
                return Carbon::createFromDate($this->selectedYear, $this->selectedMonth, 1)->format('F Y');
            case 'quarterly':
                $quarter = ceil($this->selectedMonth / 3);
                return "Q$quarter " . $this->selectedYear;
            case 'yearly':
                return "Year " . $this->selectedYear;
            default:
                return Carbon::now()->format('F Y');
        }
    }
    
    private function resetTotals()
    {
        $this->totals = [
            'asset_cost' => 0,
            'accumulated_depreciation' => 0,
            'current_depreciation' => 0,
            'net_book_value' => 0
        ];
    }
    
    public function runDepreciation()
    {
        try {
            // Post depreciation to general ledger
            foreach ($this->journalEntries as $entry) {
                if ($entry['debit'] > 0) {
                    DB::table('general_ledger')->insert([
                        'record_on_account_number' => $entry['account_code'],
                        'debit' => $entry['debit'],
                        'credit' => 0,
                        'description' => $entry['description'],
                        'transaction_type' => 'DEPRECIATION',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                } elseif ($entry['credit'] > 0) {
                    DB::table('general_ledger')->insert([
                        'record_on_account_number' => $entry['account_code'],
                        'debit' => 0,
                        'credit' => $entry['credit'],
                        'description' => $entry['description'],
                        'transaction_type' => 'DEPRECIATION',
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
            }
            
            // Update PPE records with new accumulated depreciation
            foreach ($this->assets as $asset) {
                DB::table('ppes')
                    ->where('id', $asset['id'])
                    ->update([
                        'accumulated_depreciation' => $asset['accumulated_depreciation'],
                        'depreciation_for_year' => $asset['current_period_depreciation'],
                        'closing_value' => $asset['net_book_value'],
                        'updated_at' => now()
                    ]);
            }
            
            session()->flash('message', 'Depreciation has been successfully posted to the general ledger.');
            $this->emit('showNotification', 'Depreciation posted successfully', 'success');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to post depreciation: ' . $e->getMessage());
            $this->emit('showNotification', 'Failed to post depreciation', 'error');
        }
    }
    
    public function exportToExcel()
    {
        $exportData = $this->prepareExportData();
        
        $export = new class($exportData) implements FromArray {
            private $data;
            
            public function __construct(array $data)
            {
                $this->data = $data;
            }
            
            public function array(): array
            {
                return $this->data;
            }
        };
        
        return Excel::download($export, 'depreciation_' . $this->selectedYear . '_' . $this->selectedMonth . '.xlsx');
    }
    
    public function exportToPDF()
    {
        session()->flash('message', 'PDF export will be implemented');
    }
    
    private function prepareExportData()
    {
        $data = [];
        
        // Header
        $data[] = [$this->companyName];
        $data[] = ['DEPRECIATION SCHEDULE'];
        $data[] = ['Period: ' . $this->getPeriodDescription()];
        $data[] = [];
        
        // Column headers
        $data[] = [
            'Asset Name',
            'Category',
            'Purchase Date',
            'Initial Value',
            'Salvage Value',
            'Useful Life',
            'Monthly Depreciation',
            'Accumulated Depreciation',
            'Current Period',
            'Net Book Value'
        ];
        
        // Add asset data
        foreach ($this->assets as $asset) {
            $data[] = [
                $asset['name'],
                $asset['category'],
                $asset['purchase_date'],
                $asset['initial_value'],
                $asset['salvage_value'],
                $asset['useful_life'] . ' years',
                $asset['monthly_depreciation'],
                $asset['accumulated_depreciation'],
                $asset['current_period_depreciation'],
                $asset['net_book_value']
            ];
        }
        
        // Add totals
        $data[] = [];
        $data[] = [
            'TOTALS',
            '',
            '',
            $this->totals['asset_cost'],
            '',
            '',
            '',
            $this->totals['accumulated_depreciation'],
            $this->totals['current_depreciation'],
            $this->totals['net_book_value']
        ];
        
        return $data;
    }
    
    public function formatNumber($number)
    {
        if ($number < 0) {
            return '(' . number_format(abs($number), 2) . ')';
        }
        return number_format($number, 2);
    }
    
    public function render()
    {
        return view('livewire.accounting.depreciation', [
            'assets' => $this->assets,
            'depreciationSummary' => $this->depreciationSummary,
            'journalEntries' => $this->journalEntries,
            'totals' => $this->totals
        ]);
    }
}