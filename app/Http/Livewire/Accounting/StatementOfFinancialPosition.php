<?php

namespace App\Http\Livewire\Accounting;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;

class StatementOfFinancialPosition extends Component
{
    // Period selection
    public $selectedYear;
    public $comparisonYears = [];
    public $reportingDate;
    public $companyName = 'NBC SACCOS LTD';
    
    // Display options
    public $expandedCategories = [];
    public $expandedSubcategories = [];
    public $showAccountDetail = false;
    public $selectedAccountForDetail = null;
    public $accountTransactions = [];
    public $viewLevel = 2; // Default to L2 view
    
    // Financial data
    public $assetsData = [];
    public $liabilitiesData = [];
    public $equityData = [];
    public $summaryData = [];
    
    // Drill-down data
    public $selectedCategory = null;
    public $selectedSubcategory = null;
    public $drillDownData = [];
    
    public function mount()
    {
        // Set current year as default
        $this->selectedYear = date('Y');
        $this->comparisonYears = [
            $this->selectedYear,
            $this->selectedYear - 1
        ];
        $this->reportingDate = Carbon::create($this->selectedYear, 12, 31)->format('d F Y');
        
        $this->loadFinancialData();
    }
    
    public function updatedSelectedYear()
    {
        $this->comparisonYears = [
            $this->selectedYear,
            $this->selectedYear - 1
        ];
        $this->reportingDate = Carbon::create($this->selectedYear, 12, 31)->format('d F Y');
        $this->loadFinancialData();
    }
    
    public function toggleCategory($categoryCode)
    {
        if (in_array($categoryCode, $this->expandedCategories)) {
            $key = array_search($categoryCode, $this->expandedCategories);
            unset($this->expandedCategories[$key]);
            $this->expandedCategories = array_values($this->expandedCategories);
        } else {
            $this->expandedCategories[] = $categoryCode;
        }
        
        $this->loadFinancialData();
    }
    
    public function toggleSubcategory($subcategoryCode)
    {
        if (in_array($subcategoryCode, $this->expandedSubcategories)) {
            $key = array_search($subcategoryCode, $this->expandedSubcategories);
            unset($this->expandedSubcategories[$key]);
            $this->expandedSubcategories = array_values($this->expandedSubcategories);
        } else {
            $this->expandedSubcategories[] = $subcategoryCode;
        }
        
        $this->loadFinancialData();
    }
    
    public function showAccountDetails($accountNumber, $accountName)
    {
        $this->selectedAccountForDetail = [
            'number' => $accountNumber,
            'name' => $accountName
        ];
        
        // Load recent transactions for this account
        $startDate = Carbon::createFromFormat('Y-m-d', "{$this->selectedYear}-01-01")->startOfYear();
        $endDate = Carbon::createFromFormat('Y-m-d', "{$this->selectedYear}-12-31")->endOfYear();
        
        $this->accountTransactions = DB::table('general_ledger')
            ->where('record_on_account_number', $accountNumber)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(function($entry) {
                return [
                    'date' => Carbon::parse($entry->created_at)->format('d/m/Y'),
                    'reference' => $entry->reference_number ?? $entry->transaction_number,
                    'description' => $entry->description ?? $entry->narration ?? 'Transaction',
                    'debit' => $entry->debit,
                    'credit' => $entry->credit,
                ];
            })
            ->toArray();
        
        $this->showAccountDetail = true;
    }
    
    public function closeAccountDetail()
    {
        $this->showAccountDetail = false;
        $this->selectedAccountForDetail = null;
        $this->accountTransactions = [];
    }
    
    public function drillDown($accountNumber, $accountName, $level)
    {
        $this->selectedCategory = [
            'number' => $accountNumber,
            'name' => $accountName,
            'level' => $level
        ];
        
        // Load drill-down data based on level
        if ($level == 2) {
            // Get L3 accounts under this L2
            $this->drillDownData = $this->getChildAccounts($accountNumber, 3);
        } elseif ($level == 3) {
            // Get L4 accounts under this L3
            $this->drillDownData = $this->getChildAccounts($accountNumber, 4);
        }
    }
    
    public function closeDrillDown()
    {
        $this->selectedCategory = null;
        $this->drillDownData = [];
    }
    
    public function loadFinancialData()
    {
        // Load Assets (1000 series)
        $this->assetsData = $this->getAccountCategoryData('1000', 'ASSETS');
        
        // Load Liabilities (2000 series)
        $this->liabilitiesData = $this->getAccountCategoryData('2000', 'LIABILITIES');
        
        // Load Equity (3000 series)
        $this->equityData = $this->getAccountCategoryData('3000', 'EQUITY');
        
        // Calculate summary totals
        $this->calculateSummary();
    }
    
    private function getAccountCategoryData($majorCode, $categoryName)
    {
        $data = [
            'current' => [],
            'non_current' => [],
            'total' => []
        ];
        
        // Get L2 accounts for this major category
        $l2Accounts = DB::table('accounts')
            ->where('major_category_code', $majorCode)
            ->where('account_level', '2')
            ->where('status', 'ACTIVE')
            ->whereNull('deleted_at')
            ->orderBy('account_number')
            ->get();
        
        $totalByYear = [];
        foreach ($this->comparisonYears as $year) {
            $totalByYear[$year] = 0;
        }
        
        foreach ($l2Accounts as $l2Account) {
            // Determine if current or non-current based on account name or code
            $isCurrent = $this->isCurrentAccount($l2Account);
            $section = $isCurrent ? 'current' : 'non_current';
            
            $accountData = [
                'account_number' => $l2Account->account_number,
                'account_name' => $l2Account->account_name,
                'account_level' => $l2Account->account_level,
                'years' => [],
                'children' => []
            ];
            
            // Get balances for comparison years
            foreach ($this->comparisonYears as $year) {
                $balance = $this->getAccountBalance($l2Account->account_number, $year);
                $accountData['years'][$year] = $balance;
                $totalByYear[$year] += $balance;
            }
            
            // Get L3 children if category is expanded
            if (in_array($l2Account->account_number, $this->expandedCategories)) {
                $l3Accounts = DB::table('accounts')
                    ->where('parent_account_number', $l2Account->account_number)
                    ->where('account_level', '3')
                    ->where('status', 'ACTIVE')
                    ->whereNull('deleted_at')
                    ->orderBy('account_number')
                    ->get();
                
                foreach ($l3Accounts as $l3Account) {
                    $l3Data = [
                        'account_number' => $l3Account->account_number,
                        'account_name' => $l3Account->account_name,
                        'account_level' => $l3Account->account_level,
                        'years' => [],
                        'children' => []
                    ];
                    
                    foreach ($this->comparisonYears as $year) {
                        $l3Data['years'][$year] = $this->getAccountBalance($l3Account->account_number, $year);
                    }
                    
                    // Get L4 children if subcategory is expanded
                    if (in_array($l3Account->account_number, $this->expandedSubcategories)) {
                        $l4Accounts = DB::table('accounts')
                            ->where('parent_account_number', $l3Account->account_number)
                            ->where('account_level', '4')
                            ->where('status', 'ACTIVE')
                            ->whereNull('deleted_at')
                            ->orderBy('account_number')
                            ->get();
                        
                        foreach ($l4Accounts as $l4Account) {
                            $l4Data = [
                                'account_number' => $l4Account->account_number,
                                'account_name' => $l4Account->account_name,
                                'account_level' => $l4Account->account_level,
                                'years' => []
                            ];
                            
                            foreach ($this->comparisonYears as $year) {
                                $l4Data['years'][$year] = $this->getAccountBalance($l4Account->account_number, $year);
                            }
                            
                            $l3Data['children'][] = $l4Data;
                        }
                    }
                    
                    $accountData['children'][] = $l3Data;
                }
            }
            
            $data[$section][] = $accountData;
        }
        
        $data['total'] = $totalByYear;
        
        return $data;
    }
    
    private function getChildAccounts($parentAccountNumber, $level)
    {
        $accounts = DB::table('accounts')
            ->where('parent_account_number', $parentAccountNumber)
            ->where('account_level', $level)
            ->where('status', 'ACTIVE')
            ->whereNull('deleted_at')
            ->orderBy('account_number')
            ->get();
        
        $data = [];
        foreach ($accounts as $account) {
            $accountData = [
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'account_level' => $account->account_level,
                'years' => []
            ];
            
            foreach ($this->comparisonYears as $year) {
                $accountData['years'][$year] = $this->getAccountBalance($account->account_number, $year);
            }
            
            // Check if has children
            $hasChildren = DB::table('accounts')
                ->where('parent_account_number', $account->account_number)
                ->where('status', 'ACTIVE')
                ->whereNull('deleted_at')
                ->exists();
            
            $accountData['has_children'] = $hasChildren;
            
            $data[] = $accountData;
        }
        
        return $data;
    }
    
    private function isCurrentAccount($account)
    {
        $currentKeywords = ['current', 'cash', 'bank', 'receivable', 'inventory', 'prepaid', 'short-term'];
        $accountNameLower = strtolower($account->account_name);
        
        foreach ($currentKeywords as $keyword) {
            if (str_contains($accountNameLower, $keyword)) {
                return true;
            }
        }
        
        // Check specific account codes if you have a coding convention
        // For example, 1100-1499 might be current assets
        $accountNumber = intval($account->account_number);
        if ($account->major_category_code == '1000') {
            return $accountNumber >= 1100 && $accountNumber < 1500;
        } elseif ($account->major_category_code == '2000') {
            return $accountNumber >= 2100 && $accountNumber < 2500;
        }
        
        return false;
    }
    
    private function getAccountBalance($accountNumber, $year)
    {
        $startDate = Carbon::createFromFormat('Y-m-d', "$year-01-01")->startOfYear();
        $endDate = Carbon::createFromFormat('Y-m-d', "$year-12-31")->endOfYear();
        
        // Get all child accounts
        $allAccounts = [$accountNumber];
        $childAccounts = DB::table('accounts')
            ->where('parent_account_number', 'LIKE', $accountNumber . '%')
            ->where('status', 'ACTIVE')
            ->whereNull('deleted_at')
            ->pluck('account_number');
        
        $allAccounts = array_merge($allAccounts, $childAccounts->toArray());
        
        // Get total from general ledger
        $result = DB::table('general_ledger')
            ->whereIn('record_on_account_number', $allAccounts)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('SUM(CAST(credit AS DECIMAL(20,2))) as total_credit'),
                DB::raw('SUM(CAST(debit AS DECIMAL(20,2))) as total_debit')
            )
            ->first();
        
        // Get account info to determine type
        $accountInfo = DB::table('accounts')
            ->where('account_number', $accountNumber)
            ->first();
        
        if (!$accountInfo) {
            return 0;
        }
        
        // Determine balance based on account type
        $majorCode = $accountInfo->major_category_code;
        
        // Assets: Debit balance (debit - credit)
        // Liabilities and Equity: Credit balance (credit - debit)
        if ($majorCode == '1000') {
            return ($result->total_debit ?? 0) - ($result->total_credit ?? 0);
        } else {
            return ($result->total_credit ?? 0) - ($result->total_debit ?? 0);
        }
    }
    
    private function calculateSummary()
    {
        $this->summaryData = [];
        
        foreach ($this->comparisonYears as $year) {
            $totalAssets = $this->assetsData['total'][$year] ?? 0;
            $totalLiabilities = $this->liabilitiesData['total'][$year] ?? 0;
            $totalEquity = $this->equityData['total'][$year] ?? 0;
            
            $this->summaryData[$year] = [
                'total_assets' => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity' => $totalEquity,
                'total_liabilities_equity' => $totalLiabilities + $totalEquity,
                'balance_check' => $totalAssets - ($totalLiabilities + $totalEquity)
            ];
        }
    }
    
    public function formatNumber($number)
    {
        if ($number < 0) {
            return '(' . number_format(abs($number), 2) . ')';
        }
        return number_format($number, 2);
    }
    
    public function calculateVariance($currentValue, $previousValue)
    {
        if ($previousValue == 0) {
            return $currentValue > 0 ? 100 : 0;
        }
        return (($currentValue - $previousValue) / abs($previousValue)) * 100;
    }
    
    public function exportToExcel()
    {
        // Implementation for Excel export
        session()->flash('message', 'Export functionality will be implemented');
    }
    
    public function exportToPDF()
    {
        // Implementation for PDF export
        session()->flash('message', 'Export functionality will be implemented');
    }
    
    public function render()
    {
        return view('livewire.accounting.statement-of-financial-position', [
            'assetsData' => $this->assetsData,
            'liabilitiesData' => $this->liabilitiesData,
            'equityData' => $this->equityData,
            'summaryData' => $this->summaryData,
            'comparisonYears' => $this->comparisonYears
        ]);
    }
}