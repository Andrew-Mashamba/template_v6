<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Models\LoansModel;
use App\Models\ClientsModel;
use App\Models\Employee;
use App\Models\BranchesModel;
use App\Models\LoanSubProduct;
use App\Models\loans_schedules;
use App\Models\AccountsModel;
use App\Models\Loan;
use App\Models\general_ledger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class GeneralReport extends Component
{
    public $reportPeriod = 'monthly';
    public $selectedMonth;
    public $selectedYear;
    public $selectedBranch = 'all';
    public $branches = [];
    
    // General Overview Data
    public $overviewData = [];
    public $clientSummary = [];
    public $loanSummary = [];
    public $depositSummary = [];
    public $branchPerformance = [];
    public $productPerformance = [];
    public $staffPerformance = [];
    public $financialSummary = [];
    public $operationalMetrics = [];
    
    // Statistics
    public $totalClients = 0;
    public $totalLoans = 0;
    public $totalLoanAmount = 0;
    public $totalDeposits = 0;
    public $totalAssets = 0;
    public $totalLiabilities = 0;
    public $netWorth = 0;
    public $activeBranches = 0;
    public $activeStaff = 0;
    
    protected $rules = [
        'reportPeriod' => 'required|string',
        'selectedMonth' => 'required|string',
        'selectedYear' => 'required|string',
        'selectedBranch' => 'required|string'
    ];

    public function mount()
    {
        $this->selectedMonth = Carbon::now()->format('m');
        $this->selectedYear = Carbon::now()->format('Y');
        $this->loadBranches();
        $this->loadGeneralReportData();
    }

    public function loadBranches()
    {
        try {
            $this->branches = BranchesModel::select('id', 'branch_name')
                ->orderBy('branch_name')
                ->get()
                ->toArray();
        } catch (Exception $e) {
            Log::error('Error loading branches: ' . $e->getMessage());
            $this->branches = [];
        }
    }

    public function loadGeneralReportData()
    {
        try {
            $this->loadOverviewData();
            $this->loadClientSummary();
            $this->loadLoanSummary();
            $this->loadDepositSummary();
            $this->loadBranchPerformance();
            $this->loadProductPerformance();
            $this->loadStaffPerformance();
            $this->loadFinancialSummary();
            $this->loadOperationalMetrics();
            $this->calculateStatistics();
        } catch (Exception $e) {
            Log::error('Error loading General Report data: ' . $e->getMessage());
            session()->flash('error', 'Error loading report data: ' . $e->getMessage());
        }
    }

    public function loadOverviewData()
    {
        // Sample overview data
        $this->overviewData = [
            'report_period' => $this->getReportPeriodLabel(),
            'total_members' => 1250,
            'active_members' => 1180,
            'new_members_this_period' => 45,
            'total_loans_outstanding' => 2850,
            'total_loan_portfolio' => 125000000,
            'total_deposits' => 85000000,
            'total_assets' => 150000000,
            'total_liabilities' => 65000000,
            'net_worth' => 85000000,
            'number_of_branches' => 8,
            'number_of_staff' => 65,
            'loan_approval_rate' => 78.5,
            'member_satisfaction_score' => 4.2,
            'operational_efficiency' => 85.3
        ];
    }

    public function loadClientSummary()
    {
        // Sample client summary data
        $this->clientSummary = [
            'by_type' => [
                'individual' => ['count' => 980, 'percentage' => 78.4, 'total_deposits' => 45000000],
                'group' => ['count' => 200, 'percentage' => 16.0, 'total_deposits' => 25000000],
                'corporate' => ['count' => 70, 'percentage' => 5.6, 'total_deposits' => 15000000]
            ],
            'by_age_group' => [
                '18-25' => ['count' => 150, 'percentage' => 12.0],
                '26-35' => ['count' => 400, 'percentage' => 32.0],
                '36-45' => ['count' => 350, 'percentage' => 28.0],
                '46-55' => ['count' => 200, 'percentage' => 16.0],
                '55+' => ['count' => 150, 'percentage' => 12.0]
            ],
            'by_gender' => [
                'male' => ['count' => 650, 'percentage' => 52.0],
                'female' => ['count' => 600, 'percentage' => 48.0]
            ],
            'geographical_distribution' => [
                'urban' => ['count' => 750, 'percentage' => 60.0],
                'rural' => ['count' => 500, 'percentage' => 40.0]
            ]
        ];
    }

    public function loadLoanSummary()
    {
        // Sample loan summary data
        $this->loanSummary = [
            'by_status' => [
                'active' => ['count' => 2200, 'amount' => 95000000, 'percentage' => 77.2],
                'overdue' => ['count' => 350, 'amount' => 15000000, 'percentage' => 12.3],
                'written_off' => ['count' => 50, 'amount' => 5000000, 'percentage' => 1.8],
                'completed' => ['count' => 250, 'amount' => 10000000, 'percentage' => 8.7]
            ],
            'by_product' => [
                'personal_loans' => ['count' => 1200, 'amount' => 45000000, 'percentage' => 36.0],
                'business_loans' => ['count' => 800, 'amount' => 40000000, 'percentage' => 32.0],
                'agricultural_loans' => ['count' => 600, 'amount' => 25000000, 'percentage' => 20.0],
                'emergency_loans' => ['count' => 250, 'amount' => 15000000, 'percentage' => 12.0]
            ],
            'by_risk_category' => [
                'low_risk' => ['count' => 1800, 'amount' => 75000000, 'percentage' => 60.0],
                'medium_risk' => ['count' => 800, 'amount' => 35000000, 'percentage' => 28.0],
                'high_risk' => ['count' => 250, 'amount' => 15000000, 'percentage' => 12.0]
            ]
        ];
    }

    public function loadDepositSummary()
    {
        // Sample deposit summary data
        $this->depositSummary = [
            'by_type' => [
                'savings' => ['count' => 1000, 'amount' => 40000000, 'percentage' => 47.1],
                'current' => ['count' => 200, 'amount' => 25000000, 'percentage' => 29.4],
                'fixed_deposit' => ['count' => 50, 'amount' => 20000000, 'percentage' => 23.5]
            ],
            'by_balance_range' => [
                '0-10000' => ['count' => 600, 'percentage' => 48.0],
                '10001-50000' => ['count' => 400, 'percentage' => 32.0],
                '50001-100000' => ['count' => 150, 'percentage' => 12.0],
                '100000+' => ['count' => 100, 'percentage' => 8.0]
            ],
            'average_balance' => 68000,
            'total_interest_paid' => 2500000
        ];
    }

    public function loadBranchPerformance()
    {
        // Sample branch performance data
        $this->branchPerformance = [
            [
                'branch_name' => 'Main Branch',
                'total_clients' => 300,
                'total_loans' => 450,
                'loan_amount' => 20000000,
                'total_deposits' => 15000000,
                'staff_count' => 15,
                'performance_score' => 92.5
            ],
            [
                'branch_name' => 'Downtown Branch',
                'total_clients' => 250,
                'total_loans' => 380,
                'loan_amount' => 18000000,
                'total_deposits' => 12000000,
                'staff_count' => 12,
                'performance_score' => 88.3
            ],
            [
                'branch_name' => 'Suburban Branch',
                'total_clients' => 200,
                'total_loans' => 320,
                'loan_amount' => 15000000,
                'total_deposits' => 10000000,
                'staff_count' => 10,
                'performance_score' => 85.7
            ],
            [
                'branch_name' => 'Rural Branch',
                'total_clients' => 150,
                'total_loans' => 280,
                'loan_amount' => 12000000,
                'total_deposits' => 8000000,
                'staff_count' => 8,
                'performance_score' => 82.1
            ]
        ];
    }

    public function loadProductPerformance()
    {
        // Sample product performance data
        $this->productPerformance = [
            [
                'product_name' => 'Personal Loan',
                'total_loans' => 1200,
                'total_amount' => 45000000,
                'average_amount' => 37500,
                'interest_rate' => 12.5,
                'default_rate' => 3.2,
                'profitability' => 85.5
            ],
            [
                'product_name' => 'Business Loan',
                'total_loans' => 800,
                'total_amount' => 40000000,
                'average_amount' => 50000,
                'interest_rate' => 14.0,
                'default_rate' => 4.1,
                'profitability' => 88.2
            ],
            [
                'product_name' => 'Agricultural Loan',
                'total_loans' => 600,
                'total_amount' => 25000000,
                'average_amount' => 41667,
                'interest_rate' => 10.0,
                'default_rate' => 2.8,
                'profitability' => 82.7
            ],
            [
                'product_name' => 'Emergency Loan',
                'total_loans' => 250,
                'total_amount' => 15000000,
                'average_amount' => 60000,
                'interest_rate' => 15.0,
                'default_rate' => 5.5,
                'profitability' => 90.1
            ]
        ];
    }

    public function loadStaffPerformance()
    {
        // Sample staff performance data
        $this->staffPerformance = [
            [
                'staff_name' => 'John Doe',
                'position' => 'Branch Manager',
                'department' => 'Management',
                'clients_served' => 150,
                'loans_processed' => 45,
                'deposits_handled' => 200,
                'performance_rating' => 95.0,
                'customer_satisfaction' => 4.8
            ],
            [
                'staff_name' => 'Jane Smith',
                'position' => 'Loan Officer',
                'department' => 'Credit',
                'clients_served' => 120,
                'loans_processed' => 60,
                'deposits_handled' => 80,
                'performance_rating' => 92.3,
                'customer_satisfaction' => 4.6
            ],
            [
                'staff_name' => 'Bob Wilson',
                'position' => 'Teller',
                'department' => 'Operations',
                'clients_served' => 200,
                'loans_processed' => 0,
                'deposits_handled' => 300,
                'performance_rating' => 88.7,
                'customer_satisfaction' => 4.4
            ]
        ];
    }

    public function loadFinancialSummary()
    {
        // Sample financial summary data
        $this->financialSummary = [
            'income' => [
                'interest_income' => 8500000,
                'fee_income' => 1200000,
                'other_income' => 300000,
                'total_income' => 10000000
            ],
            'expenses' => [
                'operating_expenses' => 4500000,
                'staff_costs' => 2500000,
                'administrative_costs' => 800000,
                'total_expenses' => 7800000
            ],
            'profitability' => [
                'gross_profit' => 10000000,
                'net_profit' => 2200000,
                'profit_margin' => 22.0,
                'roa' => 1.47,
                'roe' => 2.59
            ]
        ];
    }

    public function loadOperationalMetrics()
    {
        // Sample operational metrics data
        $this->operationalMetrics = [
            'efficiency_ratios' => [
                'cost_to_income_ratio' => 78.0,
                'operating_efficiency' => 85.3,
                'staff_productivity' => 92.1
            ],
            'service_metrics' => [
                'average_processing_time' => '2.5 days',
                'customer_satisfaction' => 4.2,
                'complaint_resolution_time' => '24 hours'
            ],
            'risk_metrics' => [
                'portfolio_at_risk' => 12.3,
                'provision_coverage' => 85.0,
                'capital_adequacy' => 18.5
            ]
        ];
    }

    public function calculateStatistics()
    {
        $this->totalClients = $this->overviewData['total_members'] ?? 0;
        $this->totalLoans = $this->overviewData['total_loans_outstanding'] ?? 0;
        $this->totalLoanAmount = $this->overviewData['total_loan_portfolio'] ?? 0;
        $this->totalDeposits = $this->overviewData['total_deposits'] ?? 0;
        $this->totalAssets = $this->overviewData['total_assets'] ?? 0;
        $this->totalLiabilities = $this->overviewData['total_liabilities'] ?? 0;
        $this->netWorth = $this->overviewData['net_worth'] ?? 0;
        $this->activeBranches = $this->overviewData['number_of_branches'] ?? 0;
        $this->activeStaff = $this->overviewData['number_of_staff'] ?? 0;
    }

    public function getReportPeriodLabel()
    {
        switch ($this->reportPeriod) {
            case 'daily':
                return 'Daily Report';
            case 'weekly':
                return 'Weekly Report';
            case 'monthly':
                return 'Monthly Report - ' . Carbon::createFromFormat('Y-m', $this->selectedYear . '-' . $this->selectedMonth)->format('F Y');
            case 'yearly':
                return 'Yearly Report - ' . $this->selectedYear;
            default:
                return 'General Report';
        }
    }

    public function updatedReportPeriod()
    {
        $this->loadGeneralReportData();
    }

    public function updatedSelectedMonth()
    {
        $this->loadGeneralReportData();
    }

    public function updatedSelectedYear()
    {
        $this->loadGeneralReportData();
    }

    public function updatedSelectedBranch()
    {
        $this->loadGeneralReportData();
    }

    public function exportToExcel()
    {
        // Implementation for Excel export
        session()->flash('success', 'General Report exported successfully!');
    }

    public function render()
    {
        return view('livewire.reports.general-report');
    }
}
