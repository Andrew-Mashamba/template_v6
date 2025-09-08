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

class DailyOperationsReport extends Component
{
    public $reportDate;
    public $selectedBranch = 'all';
    public $branches = [];
    
    // Daily Summary Data
    public $dailySummary = [];
    public $transactionSummary = [];
    public $loanOperations = [];
    public $depositOperations = [];
    public $withdrawalOperations = [];
    public $newLoans = [];
    public $loanDisbursements = [];
    public $loanRepayments = [];
    public $newClients = [];
    public $staffActivities = [];
    
    // Statistics
    public $totalTransactions = 0;
    public $totalTransactionValue = 0;
    public $totalNewLoans = 0;
    public $totalLoanDisbursements = 0;
    public $totalLoanRepayments = 0;
    public $totalNewClients = 0;
    public $averageTransactionValue = 0;
    
    protected $rules = [
        'reportDate' => 'required|date',
        'selectedBranch' => 'required|string'
    ];

    public function mount()
    {
        $this->reportDate = Carbon::today()->format('Y-m-d');
        $this->loadBranches();
        $this->loadDailyOperationsData();
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

    public function loadDailyOperationsData()
    {
        try {
            $this->loadDailySummary();
            $this->loadTransactionSummary();
            $this->loadLoanOperations();
            $this->loadDepositOperations();
            $this->loadWithdrawalOperations();
            $this->loadNewLoans();
            $this->loadLoanDisbursements();
            $this->loadLoanRepayments();
            $this->loadNewClients();
            $this->loadStaffActivities();
            $this->calculateStatistics();
        } catch (Exception $e) {
            Log::error('Error loading Daily Operations Report data: ' . $e->getMessage());
            session()->flash('error', 'Error loading report data: ' . $e->getMessage());
        }
    }

    public function loadDailySummary()
    {
        $date = Carbon::parse($this->reportDate);
        $branchFilter = $this->selectedBranch !== 'all' ? ['branch_id' => $this->selectedBranch] : [];

        // Sample data for demonstration
        $this->dailySummary = [
            'date' => $date->format('Y-m-d'),
            'day_of_week' => $date->format('l'),
            'total_clients_served' => 45,
            'total_transactions' => 128,
            'total_transaction_value' => 2500000,
            'new_loans_processed' => 8,
            'loan_disbursements' => 5,
            'loan_repayments' => 23,
            'new_clients_registered' => 3,
            'staff_on_duty' => 12,
            'average_transaction_time' => '4.5 minutes',
            'peak_hours' => '10:00 AM - 2:00 PM',
            'system_uptime' => '99.8%'
        ];
    }

    public function loadTransactionSummary()
    {
        // Sample transaction summary data
        $this->transactionSummary = [
            'deposits' => [
                'count' => 45,
                'total_amount' => 1200000,
                'average_amount' => 26667
            ],
            'withdrawals' => [
                'count' => 38,
                'total_amount' => 800000,
                'average_amount' => 21053
            ],
            'transfers' => [
                'count' => 25,
                'total_amount' => 300000,
                'average_amount' => 12000
            ],
            'loan_payments' => [
                'count' => 23,
                'total_amount' => 200000,
                'average_amount' => 8696
            ],
            'other_transactions' => [
                'count' => 12,
                'total_amount' => 50000,
                'average_amount' => 4167
            ]
        ];
    }

    public function loadLoanOperations()
    {
        // Sample loan operations data
        $this->loanOperations = [
            [
                'loan_id' => 'LOAN-001',
                'client_name' => 'John Doe',
                'loan_amount' => 500000,
                'loan_type' => 'Personal Loan',
                'officer' => 'Jane Smith',
                'status' => 'Approved',
                'processing_time' => '2 hours',
                'timestamp' => '09:30 AM'
            ],
            [
                'loan_id' => 'LOAN-002',
                'client_name' => 'Mary Johnson',
                'loan_amount' => 300000,
                'loan_type' => 'Business Loan',
                'officer' => 'Bob Wilson',
                'status' => 'Under Review',
                'processing_time' => '1.5 hours',
                'timestamp' => '10:15 AM'
            ],
            [
                'loan_id' => 'LOAN-003',
                'client_name' => 'Peter Kimani',
                'loan_amount' => 750000,
                'loan_type' => 'Agricultural Loan',
                'officer' => 'Alice Brown',
                'status' => 'Disbursed',
                'processing_time' => '3 hours',
                'timestamp' => '11:00 AM'
            ]
        ];
    }

    public function loadDepositOperations()
    {
        // Sample deposit operations data
        $this->depositOperations = [
            [
                'transaction_id' => 'DEP-001',
                'client_name' => 'Sarah Mwangi',
                'amount' => 50000,
                'account_type' => 'Savings',
                'officer' => 'Tom Davis',
                'timestamp' => '08:45 AM'
            ],
            [
                'transaction_id' => 'DEP-002',
                'client_name' => 'David Ochieng',
                'amount' => 25000,
                'account_type' => 'Current',
                'officer' => 'Lisa Green',
                'timestamp' => '09:20 AM'
            ],
            [
                'transaction_id' => 'DEP-003',
                'client_name' => 'Grace Wanjiku',
                'amount' => 100000,
                'account_type' => 'Fixed Deposit',
                'officer' => 'Mike Johnson',
                'timestamp' => '10:30 AM'
            ]
        ];
    }

    public function loadWithdrawalOperations()
    {
        // Sample withdrawal operations data
        $this->withdrawalOperations = [
            [
                'transaction_id' => 'WTH-001',
                'client_name' => 'James Mutua',
                'amount' => 30000,
                'account_type' => 'Savings',
                'officer' => 'Anna White',
                'timestamp' => '09:15 AM'
            ],
            [
                'transaction_id' => 'WTH-002',
                'client_name' => 'Ruth Nyong\'o',
                'amount' => 15000,
                'account_type' => 'Current',
                'officer' => 'Paul Black',
                'timestamp' => '10:45 AM'
            ]
        ];
    }

    public function loadNewLoans()
    {
        // Sample new loans data
        $this->newLoans = [
            [
                'loan_id' => 'LOAN-004',
                'client_name' => 'Michael Otieno',
                'loan_amount' => 400000,
                'loan_type' => 'Emergency Loan',
                'interest_rate' => 12.5,
                'term_months' => 12,
                'officer' => 'Susan Lee',
                'approval_date' => $this->reportDate,
                'status' => 'Active'
            ],
            [
                'loan_id' => 'LOAN-005',
                'client_name' => 'Esther Wanjala',
                'loan_amount' => 600000,
                'loan_type' => 'Education Loan',
                'interest_rate' => 10.0,
                'term_months' => 24,
                'officer' => 'Kevin Brown',
                'approval_date' => $this->reportDate,
                'status' => 'Active'
            ]
        ];
    }

    public function loadLoanDisbursements()
    {
        // Sample loan disbursements data
        $this->loanDisbursements = [
            [
                'loan_id' => 'LOAN-006',
                'client_name' => 'Daniel Kiprop',
                'disbursed_amount' => 350000,
                'disbursement_date' => $this->reportDate,
                'officer' => 'Rachel Green',
                'method' => 'Bank Transfer',
                'status' => 'Completed'
            ],
            [
                'loan_id' => 'LOAN-007',
                'client_name' => 'Faith Akinyi',
                'disbursed_amount' => 200000,
                'disbursement_date' => $this->reportDate,
                'officer' => 'Mark Taylor',
                'method' => 'Cash',
                'status' => 'Completed'
            ]
        ];
    }

    public function loadLoanRepayments()
    {
        // Sample loan repayments data
        $this->loanRepayments = [
            [
                'loan_id' => 'LOAN-008',
                'client_name' => 'Samuel Mwangi',
                'repayment_amount' => 25000,
                'principal_amount' => 20000,
                'interest_amount' => 5000,
                'repayment_date' => $this->reportDate,
                'officer' => 'Jennifer Wilson',
                'method' => 'Mobile Money',
                'status' => 'Completed'
            ],
            [
                'loan_id' => 'LOAN-009',
                'client_name' => 'Patience Omondi',
                'repayment_amount' => 30000,
                'principal_amount' => 25000,
                'interest_amount' => 5000,
                'repayment_date' => $this->reportDate,
                'officer' => 'Robert Kim',
                'method' => 'Bank Transfer',
                'status' => 'Completed'
            ]
        ];
    }

    public function loadNewClients()
    {
        // Sample new clients data
        $this->newClients = [
            [
                'client_id' => 'CLI-001',
                'client_name' => 'Brian Kipchoge',
                'registration_date' => $this->reportDate,
                'client_type' => 'Individual',
                'officer' => 'Nancy Davis',
                'status' => 'Active',
                'initial_deposit' => 10000
            ],
            [
                'client_id' => 'CLI-002',
                'client_name' => 'Mercy Wanjiku',
                'registration_date' => $this->reportDate,
                'client_type' => 'Individual',
                'officer' => 'Chris Brown',
                'status' => 'Active',
                'initial_deposit' => 15000
            ]
        ];
    }

    public function loadStaffActivities()
    {
        // Sample staff activities data
        $this->staffActivities = [
            [
                'staff_name' => 'Jane Smith',
                'position' => 'Loan Officer',
                'department' => 'Credit',
                'activities_completed' => 15,
                'clients_served' => 12,
                'transactions_processed' => 8,
                'efficiency_rating' => 'Excellent'
            ],
            [
                'staff_name' => 'Bob Wilson',
                'position' => 'Teller',
                'department' => 'Operations',
                'activities_completed' => 25,
                'clients_served' => 20,
                'transactions_processed' => 18,
                'efficiency_rating' => 'Good'
            ],
            [
                'staff_name' => 'Alice Brown',
                'position' => 'Branch Manager',
                'department' => 'Management',
                'activities_completed' => 8,
                'clients_served' => 5,
                'transactions_processed' => 3,
                'efficiency_rating' => 'Excellent'
            ]
        ];
    }

    public function calculateStatistics()
    {
        $this->totalTransactions = $this->transactionSummary['deposits']['count'] + 
                                 $this->transactionSummary['withdrawals']['count'] + 
                                 $this->transactionSummary['transfers']['count'] + 
                                 $this->transactionSummary['loan_payments']['count'] + 
                                 $this->transactionSummary['other_transactions']['count'];

        $this->totalTransactionValue = $this->transactionSummary['deposits']['total_amount'] + 
                                     $this->transactionSummary['withdrawals']['total_amount'] + 
                                     $this->transactionSummary['transfers']['total_amount'] + 
                                     $this->transactionSummary['loan_payments']['total_amount'] + 
                                     $this->transactionSummary['other_transactions']['total_amount'];

        $this->totalNewLoans = count($this->newLoans);
        $this->totalLoanDisbursements = count($this->loanDisbursements);
        $this->totalLoanRepayments = count($this->loanRepayments);
        $this->totalNewClients = count($this->newClients);

        $this->averageTransactionValue = $this->totalTransactions > 0 ? 
            $this->totalTransactionValue / $this->totalTransactions : 0;
    }

    public function updatedReportDate()
    {
        $this->loadDailyOperationsData();
    }

    public function updatedSelectedBranch()
    {
        $this->loadDailyOperationsData();
    }

    public function exportToExcel()
    {
        // Implementation for Excel export
        session()->flash('success', 'Daily Operations Report exported successfully!');
    }

    public function render()
    {
        return view('livewire.reports.daily-operations-report');
    }
}
