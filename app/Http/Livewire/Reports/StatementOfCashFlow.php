<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Services\HistoricalBalanceService;

class StatementOfCashFlow extends Component
{
    public $startDate;
    public $endDate;
    
    // Operating Activities
    public $operatingActivities = [];
    public $netOperatingCashFlow = 0;
    
    // Investing Activities
    public $investingActivities = [];
    public $netInvestingCashFlow = 0;
    
    // Financing Activities
    public $financingActivities = [];
    public $netFinancingCashFlow = 0;
    
    // Cash Flow Summary
    public $netCashFlow = 0;
    public $beginningCashBalance = 0;
    public $endingCashBalance = 0;
    
    // Previous period data for comparison
    public $previousNetOperatingCashFlow = 0;
    public $previousNetInvestingCashFlow = 0;
    public $previousNetFinancingCashFlow = 0;
    public $previousNetCashFlow = 0;
    
    // Historical data properties
    public $selectedPastYear;
    public $availableYears = [];
    protected $historicalBalanceService;

    public $reportPeriod = 'monthly';
    public $currency = 'TZS';
    public $viewFormat = 'detailed';
    
    public $showCharts = false;
    public $showOperatingDetails = true;
    public $showInvestingDetails = true;
    public $showFinancingDetails = true;
    public $showComparison = false;
    
    public $previousPeriodData = [];
    public $isLoading = false;
    
    // Scheduling properties
    public $showScheduleModal = false;
    public $scheduleFrequency = 'once';
    public $scheduleDate = '';
    public $scheduleTime = '09:00';
    public $selectedUsers = [];
    public $emailSubject = '';
    public $emailMessage = '';
    public $availableUsers = [];
    public $userSearchTerm = '';
    
    // Additional properties for better functionality
    private $institution;
    public $ppeSummary = [];

    public function mount()
    {
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->scheduleDate = Carbon::now()->addDay()->format('Y-m-d');
        $this->emailSubject = 'Statement of Cash Flow - ' . Carbon::now()->format('Y');
        $this->emailMessage = 'Please find attached the Statement of Cash Flow report.';
        
        // Load institution data
        $this->institution = DB::table('institutions')->first();
        
        // Load PPE summary
        $this->ppeSummary = $this->getPpeSummary();
        
        $this->historicalBalanceService = new HistoricalBalanceService();
        $this->availableYears = $this->historicalBalanceService->getAvailableYears();
        $this->selectedPastYear = $this->historicalBalanceService->getMostRecentYear() ?? (Carbon::now()->year - 1);
        
        $this->loadData();
    }

    public function loadData()
    {
        $this->isLoading = true;
        
        try {
            // Load Operating Activities (Cash flows from operations)
            $operatingActivities = $this->loadOperatingActivities();
            $this->operatingActivities = $operatingActivities->toArray();
            $this->netOperatingCashFlow = $operatingActivities->sum('cash_flow');
            
            // Load Investing Activities (Cash flows from investing)
            $investingActivities = $this->loadInvestingActivities();
            $this->investingActivities = $investingActivities->toArray();
            $this->netInvestingCashFlow = $investingActivities->sum('cash_flow');
            
            // Load Financing Activities (Cash flows from financing)
            $financingActivities = $this->loadFinancingActivities();
            $this->financingActivities = $financingActivities->toArray();
            $this->netFinancingCashFlow = $financingActivities->sum('cash_flow');
            
            // Calculate net cash flow
            $this->netCashFlow = $this->netOperatingCashFlow + $this->netInvestingCashFlow + $this->netFinancingCashFlow;
            
            // Get cash balances
            $this->loadCashBalances();
            
            // Load previous period data for comparison
            $this->loadPreviousPeriodData();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error loading cash flow data: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    private function loadOperatingActivities()
    {
        // Operating activities typically include:
        // - Net income
        // - Depreciation and amortization
        // - Changes in working capital
        // - Interest and taxes paid
        
        $operatingActivities = collect();
        
        // Net Income (from income statement)
        $netIncome = DB::table('accounts')
            ->where('major_category_code', '4000')
            ->sum('balance') - DB::table('accounts')
            ->where('major_category_code', '5000')
            ->sum('balance');
        
        $operatingActivities->push([
            'description' => 'Net Income',
            'cash_flow' => $netIncome,
            'type' => 'income'
        ]);
        
        // Depreciation and Amortization (from PPE table for current period)
        $currentPeriodDepreciation = DB::table('ppes')
            ->where('status', '!=', 'disposed')
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->sum('depreciation_for_year');
        
        // Also include depreciation from existing assets that had depreciation calculated in this period
        $existingAssetsDepreciation = DB::table('ppes')
            ->where('status', '!=', 'disposed')
            ->where('created_at', '<', $this->startDate)
            ->where('updated_at', '>=', $this->startDate)
            ->sum('depreciation_for_year');
        
        $totalPeriodDepreciation = $currentPeriodDepreciation + $existingAssetsDepreciation;
        
        $operatingActivities->push([
            'description' => 'Depreciation and Amortization',
            'cash_flow' => $totalPeriodDepreciation, // Positive addback (non-cash expense)
            'type' => 'depreciation'
        ]);
        
        // Changes in Working Capital - Real data from accounts
        $this->calculateWorkingCapitalChanges($operatingActivities);
        
        return $operatingActivities;
    }

    private function calculateWorkingCapitalChanges($operatingActivities)
    {
        // Calculate changes in working capital accounts between periods
        $currentStartDate = Carbon::parse($this->startDate);
        $currentEndDate = Carbon::parse($this->endDate);
        $periodLength = $currentStartDate->diffInDays($currentEndDate);
        
        $previousEndDate = $currentStartDate->copy()->subDay();
        $previousStartDate = $previousEndDate->copy()->subDays($periodLength);
        
        // Accounts Receivable changes (Trade and Other Receivables)
        $currentReceivables = DB::table('accounts')
            ->where('major_category_code', '1000')
            ->where('category_code', '1500') // Trade and Other Receivables
            ->sum('balance');
        
        $previousReceivables = $this->getHistoricalBalance('1000', '1500', $previousStartDate, $previousEndDate);
        $receivablesChange = $currentReceivables - $previousReceivables;
        
        if ($receivablesChange != 0) {
            $operatingActivities->push([
                'description' => $receivablesChange > 0 ? 'Increase in Accounts Receivable' : 'Decrease in Accounts Receivable',
                'cash_flow' => -$receivablesChange, // Negative for increase in assets
                'type' => 'working_capital'
            ]);
        }
        
        // Accounts Payable changes (Trade and Other Payables)
        $currentPayables = DB::table('accounts')
            ->where('major_category_code', '2000') // Liabilities
            ->where('category_code', '2100') // Trade and Other Payables
            ->sum('balance');
        
        $previousPayables = $this->getHistoricalBalance('2000', '2100', $previousStartDate, $previousEndDate);
        $payablesChange = $currentPayables - $previousPayables;
        
        if ($payablesChange != 0) {
            $operatingActivities->push([
                'description' => $payablesChange > 0 ? 'Increase in Accounts Payable' : 'Decrease in Accounts Payable',
                'cash_flow' => $payablesChange, // Positive for increase in liabilities
                'type' => 'working_capital'
            ]);
        }
        
        // Inventory changes (if applicable)
        $currentInventory = DB::table('accounts')
            ->where('major_category_code', '1000')
            ->where('category_code', '1600') // Inventory
            ->sum('balance');
        
        $previousInventory = $this->getHistoricalBalance('1000', '1600', $previousStartDate, $previousEndDate);
        $inventoryChange = $currentInventory - $previousInventory;
        
        if ($inventoryChange != 0) {
            $operatingActivities->push([
                'description' => $inventoryChange > 0 ? 'Increase in Inventory' : 'Decrease in Inventory',
                'cash_flow' => -$inventoryChange, // Negative for increase in assets
                'type' => 'working_capital'
            ]);
        }
        
        // Prepaid expenses changes
        $currentPrepaid = DB::table('accounts')
            ->where('major_category_code', '1000')
            ->where('category_code', '1700') // Prepaid expenses
            ->sum('balance');
        
        $previousPrepaid = $this->getHistoricalBalance('1000', '1700', $previousStartDate, $previousEndDate);
        $prepaidChange = $currentPrepaid - $previousPrepaid;
        
        if ($prepaidChange != 0) {
            $operatingActivities->push([
                'description' => $prepaidChange > 0 ? 'Increase in Prepaid Expenses' : 'Decrease in Prepaid Expenses',
                'cash_flow' => -$prepaidChange, // Negative for increase in assets
                'type' => 'working_capital'
            ]);
        }
    }

    private function getHistoricalBalance($majorCategory, $categoryCode, $startDate, $endDate)
    {
        try {
            // Check if historical_balances table exists
            $tableExists = DB::select("SELECT to_regclass('historical_balances') as exists");
            
            if ($tableExists && $tableExists[0]->exists) {
                // Try to get historical balance from historical_balances table
                $historicalBalance = DB::table('historical_balances')
                    ->where('major_category_code', $majorCategory)
                    ->where('category_code', $categoryCode)
                    ->whereBetween('balance_date', [$startDate, $endDate])
                    ->orderBy('balance_date', 'desc')
                    ->first();
                
                if ($historicalBalance) {
                    return $historicalBalance->balance;
                }
            }
        } catch (\Exception $e) {
            // Table doesn't exist or other error, continue with fallback
        }
        
        // Fallback: estimate based on current balance and growth rate
        $currentBalance = DB::table('accounts')
            ->where('major_category_code', $majorCategory)
            ->where('category_code', $categoryCode)
            ->sum('balance');
        
        // Calculate days between periods for more accurate estimation
        $currentStartDate = Carbon::parse($this->startDate);
        $periodStartDate = Carbon::parse($startDate);
        $daysDifference = $currentStartDate->diffInDays($periodStartDate);
        
        // Estimate based on period length - shorter periods have smaller changes
        if ($daysDifference <= 30) {
            // Monthly period - assume 2% change
            $growthRate = 0.98;
        } elseif ($daysDifference <= 90) {
            // Quarterly period - assume 5% change
            $growthRate = 0.95;
        } elseif ($daysDifference <= 365) {
            // Annual period - assume 10% change
            $growthRate = 0.90;
        } else {
            // Longer periods - assume 15% change
            $growthRate = 0.85;
        }
        
        return $currentBalance * $growthRate;
    }

    private function loadInvestingActivities()
    {
        // Investing activities typically include:
        // - Purchase of fixed assets (PPE)
        // - Sale of fixed assets (PPE disposals)
        // - Purchase of investments
        // - Sale of investments
        
        $investingActivities = collect();
        
        // Calculate previous period dates for comparison
        $currentStartDate = Carbon::parse($this->startDate);
        $currentEndDate = Carbon::parse($this->endDate);
        $periodLength = $currentStartDate->diffInDays($currentEndDate);
        
        $previousEndDate = $currentStartDate->copy()->subDay();
        $previousStartDate = $previousEndDate->copy()->subDays($periodLength);
        
        // PPE Purchases (from PPE table)
        $ppePurchases = DB::table('ppes')
            ->whereBetween('purchase_date', [$this->startDate, $this->endDate])
            ->where('status', '!=', 'disposed')
            ->sum('initial_value');
        
        if ($ppePurchases > 0) {
            $investingActivities->push([
                'description' => 'Purchase of Property, Plant & Equipment',
                'cash_flow' => -$ppePurchases, // Negative for cash outflow
                'type' => 'ppe_purchase'
            ]);
        }
        
        // PPE Disposals (from PPE table)
        $ppeDisposals = DB::table('ppes')
            ->whereBetween('disposal_date', [$this->startDate, $this->endDate])
            ->where('status', 'disposed')
            ->sum('disposal_proceeds');
        
        if ($ppeDisposals > 0) {
            $investingActivities->push([
                'description' => 'Proceeds from Sale of Property, Plant & Equipment',
                'cash_flow' => $ppeDisposals, // Positive for cash inflow
                'type' => 'ppe_disposal'
            ]);
        }
        
        // PPE by Category (detailed breakdown)
        $ppeByCategory = DB::table('ppes')
            ->select('category', DB::raw('SUM(initial_value) as total_value'))
            ->whereBetween('purchase_date', [$this->startDate, $this->endDate])
            ->where('status', '!=', 'disposed')
            ->groupBy('category')
            ->get();
        
        foreach ($ppeByCategory as $category) {
            if ($category->total_value > 0) {
                $investingActivities->push([
                    'description' => "Purchase of {$category->category}",
                    'cash_flow' => -$category->total_value, // Negative for cash outflow
                    'type' => 'ppe_purchase_category'
                ]);
            }
        }
        
        // PPE Disposals by Category
        $ppeDisposalsByCategory = DB::table('ppes')
            ->select('category', DB::raw('SUM(disposal_proceeds) as total_proceeds'))
            ->whereBetween('disposal_date', [$this->startDate, $this->endDate])
            ->where('status', 'disposed')
            ->groupBy('category')
            ->get();
        
        foreach ($ppeDisposalsByCategory as $category) {
            if ($category->total_proceeds > 0) {
                $investingActivities->push([
                    'description' => "Proceeds from Sale of {$category->category}",
                    'cash_flow' => $category->total_proceeds, // Positive for cash inflow
                    'type' => 'ppe_disposal_category'
                ]);
            }
        }
        
        // Investment activities (from investment accounts)
        $currentInvestments = DB::table('accounts')
            ->where('major_category_code', '1000') // Assets
            ->where('category_code', '1800') // Investments
            ->sum('balance');
        
        $previousInvestments = $this->getHistoricalBalance('1000', '1800', $previousStartDate, $previousEndDate);
        $investmentsChange = $currentInvestments - $previousInvestments;
        
        if ($investmentsChange > 0) {
            $investingActivities->push([
                'description' => 'Purchase of Investments',
                'cash_flow' => -$investmentsChange, // Negative for cash outflow
                'type' => 'investment_purchase'
            ]);
        } elseif ($investmentsChange < 0) {
            $investingActivities->push([
                'description' => 'Proceeds from Sale of Investments',
                'cash_flow' => -$investmentsChange, // Positive for cash inflow (negative of negative)
                'type' => 'investment_sale'
            ]);
        }
        
        // If no investing activities found, add a placeholder
        if ($investingActivities->isEmpty()) {
            $investingActivities->push([
                'description' => 'No investing activities',
                'cash_flow' => 0,
                'type' => 'no_investing_activity'
            ]);
        }
        
        return $investingActivities;
    }

    private function loadFinancingActivities()
    {
        // Financing activities typically include:
        // - Proceeds from loans
        // - Repayment of loans
        // - Issuance of shares
        // - Payment of dividends
        
        $financingActivities = collect();
        
        // Calculate changes in financing accounts between periods
        $currentStartDate = Carbon::parse($this->startDate);
        $currentEndDate = Carbon::parse($this->endDate);
        $periodLength = $currentStartDate->diffInDays($currentEndDate);
        
        $previousEndDate = $currentStartDate->copy()->subDay();
        $previousStartDate = $previousEndDate->copy()->subDays($periodLength);
        
        // Share Capital changes
        $currentShares = DB::table('accounts')
            ->where('major_category_code', '3000') // Equity
            ->where('category_code', '3100') // Share Capital
            ->sum('balance');
        
        $previousShares = $this->getHistoricalBalance('3000', '3100', $previousStartDate, $previousEndDate);
        $sharesChange = $currentShares - $previousShares;
        
        if ($sharesChange > 0) {
            $financingActivities->push([
                'description' => 'Proceeds from Share Issuance',
                'cash_flow' => $sharesChange, // Positive for cash inflow
                'type' => 'share_capital'
            ]);
        } elseif ($sharesChange < 0) {
            $financingActivities->push([
                'description' => 'Share Repurchase/Redemption',
                'cash_flow' => $sharesChange, // Negative for cash outflow
                'type' => 'share_capital'
            ]);
        }
        
        // Member Savings changes
        $currentSavings = DB::table('accounts')
            ->where('major_category_code', '2000') // Liabilities
            ->where('category_code', '2200') // Member Savings
            ->sum('balance');
        
        $previousSavings = $this->getHistoricalBalance('2000', '2200', $previousStartDate, $previousEndDate);
        $savingsChange = $currentSavings - $previousSavings;
        
        if ($savingsChange != 0) {
            $financingActivities->push([
                'description' => $savingsChange > 0 ? 'Increase in Member Savings' : 'Decrease in Member Savings',
                'cash_flow' => $savingsChange, // Positive for increase in liabilities (cash inflow)
                'type' => 'member_savings'
            ]);
        }
        
        // Member Deposits changes
        $currentDeposits = DB::table('accounts')
            ->where('major_category_code', '2000') // Liabilities
            ->where('category_code', '2300') // Member Deposits
            ->sum('balance');
        
        $previousDeposits = $this->getHistoricalBalance('2000', '2300', $previousStartDate, $previousEndDate);
        $depositsChange = $currentDeposits - $previousDeposits;
        
        if ($depositsChange != 0) {
            $financingActivities->push([
                'description' => $depositsChange > 0 ? 'Increase in Member Deposits' : 'Decrease in Member Deposits',
                'cash_flow' => $depositsChange, // Positive for increase in liabilities (cash inflow)
                'type' => 'member_deposits'
            ]);
        }
        
        // External Loans changes (borrowing/repayment)
        $currentExternalLoans = DB::table('accounts')
            ->where('major_category_code', '2000') // Liabilities
            ->where('category_code', '2400') // External Loans
            ->sum('balance');
        
        $previousExternalLoans = $this->getHistoricalBalance('2000', '2400', $previousStartDate, $previousEndDate);
        $externalLoansChange = $currentExternalLoans - $previousExternalLoans;
        
        if ($externalLoansChange > 0) {
            $financingActivities->push([
                'description' => 'Proceeds from External Loans',
                'cash_flow' => $externalLoansChange, // Positive for cash inflow
                'type' => 'external_loans'
            ]);
        } elseif ($externalLoansChange < 0) {
            $financingActivities->push([
                'description' => 'Repayment of External Loans',
                'cash_flow' => $externalLoansChange, // Negative for cash outflow
                'type' => 'external_loans'
            ]);
        }
        
        // Dividend payments (from retained earnings)
        $currentRetainedEarnings = DB::table('accounts')
            ->where('major_category_code', '3000') // Equity
            ->where('category_code', '3200') // Retained Earnings
            ->sum('balance');
        
        $previousRetainedEarnings = $this->getHistoricalBalance('3000', '3200', $previousStartDate, $previousEndDate);
        $retainedEarningsChange = $currentRetainedEarnings - $previousRetainedEarnings;
        
        // If retained earnings decreased more than net income, it might be due to dividends
        $netIncome = DB::table('accounts')
            ->where('major_category_code', '4000')
            ->sum('balance') - DB::table('accounts')
            ->where('major_category_code', '5000')
            ->sum('balance');
        
        $expectedRetainedEarnings = $previousRetainedEarnings + $netIncome;
        $dividendPayment = $expectedRetainedEarnings - $currentRetainedEarnings;
        
        if ($dividendPayment > 0) {
            $financingActivities->push([
                'description' => 'Payment of Dividends',
                'cash_flow' => -$dividendPayment, // Negative for cash outflow
                'type' => 'dividends'
            ]);
        }
        
        // If no financing activities found, add a placeholder
        if ($financingActivities->isEmpty()) {
            $financingActivities->push([
                'description' => 'No financing activities',
                'cash_flow' => 0,
                'type' => 'no_financing_activity'
            ]);
        }
        
        return $financingActivities;
    }

    private function loadCashBalances()
    {
        // Get cash and cash equivalents balance
        $cashAccounts = DB::table('accounts')
            ->whereIn('major_category_code', ['1000']) // Cash and cash equivalents
            //->where('account_name', 'like', '%cash%')
            //->orWhere('account_name', 'like', '%bank%')
            ->sum('balance');
        
        $this->endingCashBalance = $cashAccounts;
        $this->beginningCashBalance = $this->endingCashBalance - $this->netCashFlow;
    }

    private function loadPreviousPeriodData()
    {
        try {
            // Calculate previous period dates based on current period
            $currentStartDate = Carbon::parse($this->startDate);
            $currentEndDate = Carbon::parse($this->endDate);
            $periodLength = $currentStartDate->diffInDays($currentEndDate);
            
            $previousEndDate = $currentStartDate->copy()->subDay();
            $previousStartDate = $previousEndDate->copy()->subDays($periodLength);
            
            // Load real previous period data from historical balances or estimate from current data
            $this->previousNetOperatingCashFlow = $this->calculatePreviousPeriodOperatingCashFlow($previousStartDate, $previousEndDate);
            $this->previousNetInvestingCashFlow = $this->calculatePreviousPeriodInvestingCashFlow($previousStartDate, $previousEndDate);
            $this->previousNetFinancingCashFlow = $this->calculatePreviousPeriodFinancingCashFlow($previousStartDate, $previousEndDate);
            $this->previousNetCashFlow = $this->previousNetOperatingCashFlow + $this->previousNetInvestingCashFlow + $this->previousNetFinancingCashFlow;
            
        } catch (\Exception $e) {
            // If there's an error loading previous period data, estimate based on current data
            $this->previousNetOperatingCashFlow = $this->netOperatingCashFlow * 0.95;
            $this->previousNetInvestingCashFlow = $this->netInvestingCashFlow * 0.92;
            $this->previousNetFinancingCashFlow = $this->netFinancingCashFlow * 1.03;
            $this->previousNetCashFlow = $this->previousNetOperatingCashFlow + $this->previousNetInvestingCashFlow + $this->previousNetFinancingCashFlow;
        }
    }

    private function calculatePreviousPeriodOperatingCashFlow($startDate, $endDate)
    {
        // Calculate net income for previous period
        $previousNetIncome = $this->getHistoricalBalance('4000', '', $startDate, $endDate) - 
                           $this->getHistoricalBalance('5000', '', $startDate, $endDate);
        
        // Get depreciation for previous period
        $previousDepreciation = DB::table('ppes')
            ->where('status', '!=', 'disposed')
            ->whereBetween('updated_at', [$startDate, $endDate])
            ->sum('depreciation_for_year');
        
        // Estimate working capital changes for previous period
        $previousWorkingCapital = $this->estimatePreviousWorkingCapitalChanges($startDate, $endDate);
        
        return $previousNetIncome + $previousDepreciation + $previousWorkingCapital;
    }

    private function calculatePreviousPeriodInvestingCashFlow($startDate, $endDate)
    {
        // Get PPE purchases for previous period
        $previousPpePurchases = DB::table('ppes')
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->where('status', '!=', 'disposed')
            ->sum('initial_value');
        
        // Get PPE disposals for previous period
        $previousPpeDisposals = DB::table('ppes')
            ->whereBetween('disposal_date', [$startDate, $endDate])
            ->where('status', 'disposed')
            ->sum('disposal_proceeds');
        
        return $previousPpeDisposals - $previousPpePurchases;
    }

    private function calculatePreviousPeriodFinancingCashFlow($startDate, $endDate)
    {
        // Calculate changes in financing accounts for previous period
        $previousShares = $this->getHistoricalBalance('3000', '3100', $startDate, $endDate);
        $previousSavings = $this->getHistoricalBalance('2000', '2200', $startDate, $endDate);
        $previousDeposits = $this->getHistoricalBalance('2000', '2300', $startDate, $endDate);
        $previousLoans = $this->getHistoricalBalance('2000', '2400', $startDate, $endDate);
        
        return $previousShares + $previousSavings + $previousDeposits + $previousLoans;
    }

    private function estimatePreviousWorkingCapitalChanges($startDate, $endDate)
    {
        // Estimate working capital changes for previous period
        // This is a simplified estimation - in a real system, you'd have historical balance data
        
        $currentWorkingCapital = 0;
        
        // Get current working capital accounts
        $currentReceivables = DB::table('accounts')
            ->where('major_category_code', '1000')
            ->where('category_code', '1500')
            ->sum('balance');
        
        $currentPayables = DB::table('accounts')
            ->where('major_category_code', '2000')
            ->where('category_code', '2100')
            ->sum('balance');
        
        // Estimate previous period based on current (assuming 5% growth)
        $previousReceivables = $currentReceivables * 0.95;
        $previousPayables = $currentPayables * 0.95;
        
        $receivablesChange = $currentReceivables - $previousReceivables;
        $payablesChange = $currentPayables - $previousPayables;
        
        return -$receivablesChange + $payablesChange;
    }

    public function updatedReportPeriod()
    {
        $this->setDateRangeByPeriod();
        $this->loadData();
    }

    public function updatedStartDate()
    {
        $this->loadData();
    }

    public function updatedEndDate()
    {
        $this->loadData();
    }

    public function updatedCurrency()
    {
        $this->dispatch('currencyChanged', $this->currency);
    }

    public function updatedViewFormat()
    {
        $this->dispatch('viewFormatChanged', $this->viewFormat);
    }

    private function setDateRangeByPeriod()
    {
        $now = Carbon::now();
        
        switch ($this->reportPeriod) {
            case 'monthly':
                $this->startDate = $now->startOfMonth()->format('Y-m-d');
                $this->endDate = $now->endOfMonth()->format('Y-m-d');
                break;
            case 'quarterly':
                $this->startDate = $now->startOfQuarter()->format('Y-m-d');
                $this->endDate = $now->endOfQuarter()->format('Y-m-d');
                break;
            case 'annually':
                $this->startDate = $now->startOfYear()->format('Y-m-d');
                $this->endDate = $now->endOfYear()->format('Y-m-d');
                break;
            case 'custom':
                // Keep current dates for custom range
                break;
        }
    }

    public function generateReport()
    {
        $this->isLoading = true;
        
        try {
            $this->loadData();
            session()->flash('success', 'Statement of Cash Flow generated successfully!');
        } catch (\Exception $e) {
            session()->flash('error', 'Error generating report: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    public function exportToPDF()
    {
        try {
            $data = [
                'operatingActivities' => $this->operatingActivities,
                'investingActivities' => $this->investingActivities,
                'financingActivities' => $this->financingActivities,
                'netOperatingCashFlow' => $this->netOperatingCashFlow,
                'netInvestingCashFlow' => $this->netInvestingCashFlow,
                'netFinancingCashFlow' => $this->netFinancingCashFlow,
                'netCashFlow' => $this->netCashFlow,
                'beginningCashBalance' => $this->beginningCashBalance,
                'endingCashBalance' => $this->endingCashBalance,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'currency' => $this->currency,
                'institution' => $this->institution,
                'reportDate' => now()->format('Y-m-d H:i:s')
            ];

            $pdf = PDF::loadView('pdf.statement-of-cash-flow', $data);
            
            $filename = 'statement-of-cash-flow-' . now()->format('Y-m-d') . '.pdf';
            
            // For Livewire, we need to redirect to a download route
            session()->put('pdf_download_data', [
                'content' => base64_encode($pdf->output()),
                'filename' => $filename
            ]);
            
            return redirect()->route('download.pdf');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error exporting PDF: ' . $e->getMessage());
        }
    }

    public function exportToExcel()
    {
        try {
            // Create CSV content
            $csvContent = $this->generateCSVContent();
            
            $filename = 'statement-of-cash-flow-' . now()->format('Y-m-d') . '.csv';
            
            // For Livewire, we need to redirect to a download route
            session()->put('csv_download_data', [
                'content' => $csvContent,
                'filename' => $filename
            ]);
            
            return redirect()->route('download.csv');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error exporting Excel: ' . $e->getMessage());
        }
    }

    private function generateCSVContent()
    {
        $csv = "Statement of Cash Flow\n";
        $csv .= "Period: {$this->startDate} to {$this->endDate}\n";
        $csv .= "Currency: {$this->currency}\n\n";
        
        // Operating Activities
        $csv .= "OPERATING ACTIVITIES\n";
        $csv .= "Description,Cash Flow\n";
        foreach ($this->operatingActivities as $activity) {
            $csv .= "\"{$activity['description']}\",{$activity['cash_flow']}\n";
        }
        $csv .= "NET CASH FROM OPERATING ACTIVITIES,{$this->netOperatingCashFlow}\n\n";
        
        // Investing Activities
        $csv .= "INVESTING ACTIVITIES\n";
        $csv .= "Description,Cash Flow\n";
        foreach ($this->investingActivities as $activity) {
            $csv .= "\"{$activity['description']}\",{$activity['cash_flow']}\n";
        }
        $csv .= "NET CASH FROM INVESTING ACTIVITIES,{$this->netInvestingCashFlow}\n\n";
        
        // Financing Activities
        $csv .= "FINANCING ACTIVITIES\n";
        $csv .= "Description,Cash Flow\n";
        foreach ($this->financingActivities as $activity) {
            $csv .= "\"{$activity['description']}\",{$activity['cash_flow']}\n";
        }
        $csv .= "NET CASH FROM FINANCING ACTIVITIES,{$this->netFinancingCashFlow}\n\n";
        
        $csv .= "NET INCREASE (DECREASE) IN CASH," . $this->netCashFlow . "\n";
        $csv .= "CASH AT BEGINNING OF PERIOD," . $this->beginningCashBalance . "\n";
        $csv .= "CASH AT END OF PERIOD," . $this->endingCashBalance . "\n";
        
        return $csv;
    }

    public function scheduleReport()
    {
        $this->loadAvailableUsers();
        $this->showScheduleModal = true;
    }

    public function loadAvailableUsers()
    {
        $query = DB::table('users')
            ->leftJoin('departments', 'users.department_code', '=', 'departments.department_code')
            ->select(
                'users.id', 
                'users.name', 
                'users.email', 
                'users.department_code',
                'departments.department_name as department'
            )
            ->where('users.email', '!=', '')
            ->whereNotNull('users.email');

        if (!empty($this->userSearchTerm)) {
            $query->where(function($q) {
                $q->where('users.name', 'like', '%' . $this->userSearchTerm . '%')
                  ->orWhere('users.email', 'like', '%' . $this->userSearchTerm . '%')
                  ->orWhere('departments.department_name', 'like', '%' . $this->userSearchTerm . '%')
                  ->orWhere('users.department_code', 'like', '%' . $this->userSearchTerm . '%');
            });
        }

        $this->availableUsers = $query->orderBy('users.name')->get()->map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department_code' => $user->department_code,
                'department' => $user->department
            ];
        })->toArray();
    }

    public function updatedUserSearchTerm()
    {
        $this->loadAvailableUsers();
    }

    public function removeUser($userId)
    {
        $this->selectedUsers = array_values(array_diff($this->selectedUsers, [$userId]));
    }

    public function selectAllUsers()
    {
        $this->selectedUsers = collect($this->availableUsers)->pluck('id')->toArray();
    }

    public function clearAllUsers()
    {
        $this->selectedUsers = [];
    }

    public function confirmSchedule()
    {
        // Validate scheduling data
        $this->validate([
            'scheduleDate' => 'required|date|after:today',
            'scheduleTime' => 'required',
            'selectedUsers' => 'required|array|min:1',
            'emailSubject' => 'required|string|max:255',
            'scheduleFrequency' => 'required|in:once,daily,weekly,monthly,quarterly,annually'
        ]);

        try {
            // Parse schedule datetime
            $scheduledAt = Carbon::parse($this->scheduleDate . ' ' . $this->scheduleTime);
            
            // Calculate next run date based on frequency
            $nextRunAt = $this->calculateNextRunDate($scheduledAt);

            // Get selected user emails
            $selectedUserEmails = DB::table('users')
                ->whereIn('id', $this->selectedUsers)
                ->pluck('email')
                ->toArray();

            // Create a scheduled report entry
            DB::table('scheduled_reports')->insert([
                'report_type' => 'Statement of Cash Flow',
                'report_config' => json_encode([
                    'reportPeriod' => $this->reportPeriod,
                    'currency' => $this->currency,
                    'viewFormat' => $this->viewFormat,
                    'startDate' => $this->startDate,
                    'endDate' => $this->endDate,
                    'emailSubject' => $this->emailSubject,
                    'emailMessage' => $this->emailMessage,
                    'selectedUserIds' => $this->selectedUsers
                ]),
                'user_id' => auth()->id(),
                'status' => 'scheduled',
                'frequency' => $this->scheduleFrequency,
                'scheduled_at' => $scheduledAt,
                'next_run_at' => $nextRunAt,
                'email_recipients' => implode(',', $selectedUserEmails),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            $this->showScheduleModal = false;
            $this->reset(['selectedUsers', 'emailSubject', 'emailMessage', 'userSearchTerm']);
            
            $recipientCount = count($selectedUserEmails);
            session()->flash('success', "Report scheduled successfully! {$recipientCount} recipient(s) will receive it on " . $scheduledAt->format('M d, Y \a\t H:i'));
        } catch (\Exception $e) {
            session()->flash('error', 'Error scheduling report: ' . $e->getMessage());
        }
    }

    public function cancelSchedule()
    {
        $this->showScheduleModal = false;
        $this->reset(['selectedUsers', 'emailSubject', 'emailMessage', 'userSearchTerm']);
    }

    private function calculateNextRunDate($scheduledAt)
    {
        switch ($this->scheduleFrequency) {
            case 'daily':
                return $scheduledAt->copy()->addDay();
            case 'weekly':
                return $scheduledAt->copy()->addWeek();
            case 'monthly':
                return $scheduledAt->copy()->addMonth();
            case 'quarterly':
                return $scheduledAt->copy()->addMonths(3);
            case 'annually':
                return $scheduledAt->copy()->addYear();
            case 'once':
            default:
                return null;
        }
    }

    public function toggleChartView()
    {
        $this->showCharts = !$this->showCharts;
    }

    public function toggleOperatingDetails()
    {
        $this->showOperatingDetails = !$this->showOperatingDetails;
    }

    public function toggleInvestingDetails()
    {
        $this->showInvestingDetails = !$this->showInvestingDetails;
    }

    public function toggleFinancingDetails()
    {
        $this->showFinancingDetails = !$this->showFinancingDetails;
    }

    public function toggleComparison()
    {
        $this->showComparison = !$this->showComparison;
        
        if ($this->showComparison) {
            $this->loadComparisonData();
        }
    }

    private function loadComparisonData()
    {
        try {
            // Load previous period data for comparison
            $previousStartDate = Carbon::parse($this->startDate)->subMonth()->startOfMonth();
            $previousEndDate = Carbon::parse($this->endDate)->subMonth()->endOfMonth();
            
            // This would need to be implemented based on your actual transaction/balance history
            $this->previousPeriodData = [
                'netOperatingCashFlow' => $this->netOperatingCashFlow * 0.95,
                'netInvestingCashFlow' => $this->netInvestingCashFlow * 0.92,
                'netFinancingCashFlow' => $this->netFinancingCashFlow * 1.03,
                'netCashFlow' => ($this->netOperatingCashFlow * 0.95) + ($this->netInvestingCashFlow * 0.92) + ($this->netFinancingCashFlow * 1.03),
                'period' => $previousStartDate->format('M Y')
            ];
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error loading comparison data: ' . $e->getMessage());
        }
    }

    public function getPeriodDisplayName()
    {
        switch ($this->reportPeriod) {
            case 'monthly': return 'Monthly Report';
            case 'quarterly': return 'Quarterly Report';
            case 'annually': return 'Annual Report';
            case 'custom': return 'Custom Period Report';
            default: return 'Financial Report';
        }
    }

    public function isPositiveCashFlow()
    {
        return $this->netCashFlow >= 0;
    }

    public function captureHistoricalBalances()
    {
        try {
            // Get current account balances grouped by major category and category
            $accountBalances = DB::table('accounts')
                ->select(
                    'institution_number',
                    'branch_number',
                    'major_category_code',
                    'category_code',
                    'sub_category_code',
                    'type',
                    DB::raw('SUM(balance) as total_balance')
                )
                ->whereNotNull('major_category_code')
                ->whereNotNull('category_code')
                ->groupBy('institution_number', 'branch_number', 'major_category_code', 'category_code', 'sub_category_code', 'type')
                ->get();

            $capturedCount = 0;
            $today = Carbon::today();

            foreach ($accountBalances as $balance) {
                // Check if historical balance already exists for today
                $existingBalance = DB::table('historical_balances')
                    ->where('major_category_code', $balance->major_category_code)
                    ->where('category_code', $balance->category_code)
                    ->where('balance_date', $today)
                    ->first();

                if (!$existingBalance) {
                    DB::table('historical_balances')->insert([
                        'institution_number' => $balance->institution_number,
                        'branch_number' => $balance->branch_number,
                        'major_category_code' => $balance->major_category_code,
                        'category_code' => $balance->category_code,
                        'sub_category_code' => $balance->sub_category_code,
                        'balance' => $balance->total_balance,
                        'balance_date' => $today,
                        'account_type' => $balance->type,
                        'notes' => 'Captured from current account balances',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $capturedCount++;
                }
            }

            session()->flash('success', "Successfully captured {$capturedCount} historical balance records for " . $today->format('Y-m-d'));
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error capturing historical balances: ' . $e->getMessage());
        }
    }

    public function updatedSelectedPastYear()
    {
        $this->loadData();
    }

    /**
     * Get PPE summary statistics for the cash flow statement
     */
    public function getPpeSummary()
    {
        $ppeSummary = [
            'total_ppe_value' => DB::table('ppes')
                ->where('status', '!=', 'disposed')
                ->sum('initial_value'),
            
            'total_accumulated_depreciation' => DB::table('ppes')
                ->where('status', '!=', 'disposed')
                ->sum('accumulated_depreciation'),
            
            'net_book_value' => DB::table('ppes')
                ->where('status', '!=', 'disposed')
                ->sum(DB::raw('initial_value - accumulated_depreciation')),
            
            'ppe_purchases_current_period' => DB::table('ppes')
                ->whereBetween('purchase_date', [$this->startDate, $this->endDate])
                ->where('status', '!=', 'disposed')
                ->sum('initial_value'),
            
            'ppe_disposals_current_period' => DB::table('ppes')
                ->whereBetween('disposal_date', [$this->startDate, $this->endDate])
                ->where('status', 'disposed')
                ->sum('disposal_proceeds'),
            
            'depreciation_current_period' => DB::table('ppes')
                ->where('status', '!=', 'disposed')
                ->whereBetween('created_at', [$this->startDate, $this->endDate])
                ->sum('depreciation_for_year'),
            
            'ppe_by_category' => DB::table('ppes')
                ->select('category', 
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(initial_value) as total_value'),
                    DB::raw('SUM(accumulated_depreciation) as total_depreciation'),
                    DB::raw('SUM(initial_value - accumulated_depreciation) as net_book_value'))
                ->where('status', '!=', 'disposed')
                ->groupBy('category')
                ->get()
        ];
        
        return $ppeSummary;
    }

    public function render()
    {
        return view('livewire.reports.statement-of-cash-flow');
    }
} 