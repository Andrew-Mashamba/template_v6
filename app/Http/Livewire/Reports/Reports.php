<?php

namespace App\Http\Livewire\Reports;

use App\Models\approvals;
use App\Models\LoansModel;
use App\Models\Transactions;
use App\Models\ClientsModel;
use App\Models\AccountsModel;
use App\Models\general_ledger;
use App\Models\BranchesModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MainReport;
use App\Exports\LoanSchedule;
use App\Exports\ContractData;
use Carbon\Carbon;
use Exception;

class Reports extends Component
{
    // Core Properties
    public $endDate;
    public $startDate;
    public $nodes;
    public $services;
    public $channels;
    public $type;

    // Modal Properties
    public $showResolveModal = false;
    public $transactionToReview;
    public $comments;
    public $showReportDetailsModal = false;
    public $showExportOptionsModal = false;
    public $showScheduleReportModal = false;

    // Report Properties
    public $processorNodes;
    public $sortByBranch;
    public $ReportCategory = 1;
    public $tab_id = 37;
    public $loanItems;
    public $reportStartDate;
    public $reportEndDate;
    public $customize = "NO";
    public $custome_client_number;

    // Enhanced Analytics Properties
    public $totalMembers = 0;
    public $activeMembers = 0;
    public $inactiveMembers = 0;
    public $totalLoans = 0;
    public $activeLoans = 0;
    public $overdueLoans = 0;
    public $totalSavings = 0;
    public $totalDeposits = 0;
    public $totalShares = 0;
    public $totalTransactions = 0;
    public $monthlyGrowth = 0;
    public $portfolioAtRisk = 0;
    public $capitalAdequacyRatio = 0;
    public $liquidityRatio = 0;

    // Report Generation Properties
    public $selectedReportType = '';
    public $reportFormat = 'pdf'; // pdf, excel, csv
    public $includeCharts = true;
    public $includeSummary = true;
    public $includeDetails = true;
    public $reportPeriod = 'monthly'; // daily, weekly, monthly, quarterly, annually
    public $reportTitle = '';
    public $reportDescription = '';

    // Scheduling Properties
    public $scheduleFrequency = 'once'; // once, daily, weekly, monthly
    public $scheduleDate = '';
    public $scheduleTime = '09:00';
    public $emailRecipients = [];
    public $emailSubject = '';
    public $emailMessage = '';

    // Filter Properties
    public $changeBranch;
    public $selectedBranches = [];
    public $selectedProducts = [];
    public $selectedStatuses = [];
    public $dateRange = 'this_month'; // this_month, last_month, this_quarter, this_year, custom

    // Loading States
    public $isLoading = false;
    public $isGenerating = false;
    public $isExporting = false;
    public $isScheduling = false;

    // Messages
    public $successMessage = '';
    public $errorMessage = '';
    public $warningMessage = '';

    // Report History
    public $reportHistory = [];
    public $showReportHistory = false;

    protected $listeners = [
        'resolveModal' => 'showResolveModal',
        'refresh' => '$refresh',
        'reportGenerated' => 'handleReportGenerated',
        'exportCompleted' => 'handleExportCompleted'
    ];

    public function mount()
    {
        $this->initializeDates();
        $this->loadAnalytics();
        $this->loadReportHistory();
    }

    public function initializeDates()
    {
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->reportStartDate = $this->startDate;
        $this->reportEndDate = $this->endDate;
        $this->scheduleDate = Carbon::now()->addDay()->format('Y-m-d');
    }

    public function loadAnalytics()
    {
        try {
            $this->isLoading = true;

            // Member Analytics
            $this->totalMembers = ClientsModel::count();
            $this->activeMembers = ClientsModel::where('client_status', 'ACTIVE')->count();
            $this->inactiveMembers = $this->totalMembers - $this->activeMembers;

            // Loan Analytics
            $this->totalLoans = LoansModel::count();
            $this->activeLoans = LoansModel::where('loan_status', 'ACTIVE')->count();
            $this->overdueLoans = LoansModel::where('due_date', '<', now())->count();

            // Financial Analytics
            $this->totalSavings = AccountsModel::where('major_category_code', 1000)->sum('balance');
            $this->totalDeposits = AccountsModel::where('major_category_code', 2000)->sum('balance');
            $this->totalShares = DB::table('share_registers')->sum('current_share_balance');
            $this->totalTransactions = general_ledger::count();

            // Performance Metrics
            $this->calculatePerformanceMetrics();

        } catch (Exception $e) {
            Log::error('Error loading analytics: ' . $e->getMessage());
            $this->errorMessage = 'Failed to load analytics data.';
        } finally {
            $this->isLoading = false;
        }
    }

    public function calculatePerformanceMetrics()
    {
        try {
            // Portfolio at Risk (PAR) - Loans overdue by 30+ days
            $overdueAmount = LoansModel::where('due_date', '<', now()->subDays(30))
                ->sum('remaining_amount');
            $totalPortfolio = LoansModel::where('loan_status', 'ACTIVE')->sum('remaining_amount');
            $this->portfolioAtRisk = $totalPortfolio > 0 ? ($overdueAmount / $totalPortfolio) * 100 : 0;

            // Capital Adequacy Ratio (simplified calculation)
            $totalAssets = AccountsModel::where('major_category_code', 1000)->sum('balance');
            $totalLiabilities = AccountsModel::where('major_category_code', 2000)->sum('balance');
            $capital = $totalAssets - $totalLiabilities;
            $this->capitalAdequacyRatio = $totalAssets > 0 ? ($capital / $totalAssets) * 100 : 0;

            // Liquidity Ratio
            $liquidAssets = AccountsModel::where('major_category_code', 1000)
                ->whereIn('account_number', ['1001', '1002', '1003']) // Cash and cash equivalents
                ->sum('balance');
            $shortTermLiabilities = AccountsModel::where('major_category_code', 2000)
                ->whereIn('account_number', ['2001', '2002']) // Short-term deposits
                ->sum('balance');
            $this->liquidityRatio = $shortTermLiabilities > 0 ? ($liquidAssets / $shortTermLiabilities) * 100 : 0;

            // Monthly Growth Rate
            $currentMonth = general_ledger::whereMonth('created_at', now()->month)->sum('debit');
            $lastMonth = general_ledger::whereMonth('created_at', now()->subMonth()->month)->sum('debit');
            $this->monthlyGrowth = $lastMonth > 0 ? (($currentMonth - $lastMonth) / $lastMonth) * 100 : 0;

        } catch (Exception $e) {
            Log::error('Error calculating performance metrics: ' . $e->getMessage());
        }
    }

    public function loadReportHistory()
    {
        try {
            $this->reportHistory = DB::table('scheduled_reports')
                ->where('user_id', auth()->id())
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get();
        } catch (Exception $e) {
            Log::error('Error loading report history: ' . $e->getMessage());
        }
    }

    public function updatedChangeBranch($value)
    {
        $this->emit('changeBranch', $value);
    }

    public function updatedloanItems($value)
    {
        $this->emit('loanItem', $this->loanItems);
    }

    public function menuItemClicked($id)
    {
        $this->tab_id = $id;
        $this->loadReportDetails($id);
    }

    public function loadReportDetails($reportId)
    {
        try {
            $reportTypes = $this->getReportTypes();
            if (isset($reportTypes[$reportId])) {
                $this->selectedReportType = $reportTypes[$reportId]['name'];
                $this->reportTitle = $reportTypes[$reportId]['title'];
                $this->reportDescription = $reportTypes[$reportId]['description'];
            }
        } catch (Exception $e) {
            Log::error('Error loading report details: ' . $e->getMessage());
        }
    }

    public function getReportTypes()
    {
        return [
            37 => [
                'name' => 'Statement of Financial Position',
                'title' => 'Statement of Financial Position for the Month Ended',
                'description' => 'Comprehensive balance sheet showing assets, liabilities, and equity positions',
                'category' => 'regulatory',
                'compliance' => 'BOT, IFRS'
            ],
            38 => [
                'name' => 'Statement of Comprehensive Income',
                'title' => 'Statement of Comprehensive Income for the Month Ended',
                'description' => 'Detailed income statement showing revenue, expenses, and net income',
                'category' => 'regulatory',
                'compliance' => 'BOT, IFRS'
            ],
            39 => [
                'name' => 'Statement of Cash Flow',
                'title' => 'Statement of Cash Flow for the Month Ended',
                'description' => 'Cash flow analysis showing operating, investing, and financing activities',
                'category' => 'regulatory',
                'compliance' => 'BOT, IFRS'
            ],
            2 => [
                'name' => 'Members Details Report',
                'title' => 'Comprehensive Member Information Report',
                'description' => 'Detailed member information including personal data, accounts, and status',
                'category' => 'operational',
                'compliance' => 'Internal'
            ],
            5 => [
                'name' => 'Loan General Report',
                'title' => 'Loan Portfolio Analysis Report',
                'description' => 'Comprehensive loan portfolio analysis including disbursements, collections, and performance',
                'category' => 'operational',
                'compliance' => 'Internal'
            ],
            9 => [
                'name' => 'Financial Ratios and Metrics',
                'title' => 'Financial Performance Indicators Report',
                'description' => 'Key financial ratios and performance metrics for decision making',
                'category' => 'analytical',
                'compliance' => 'Internal'
            ],
            13 => [
                'name' => 'Compliance Report',
                'title' => 'Regulatory Compliance Status Report',
                'description' => 'Comprehensive compliance status across all regulatory requirements',
                'category' => 'regulatory',
                'compliance' => 'BOT, TCDC'
            ]
        ];
    }

    public function showResolveModal($id)
    {
        $this->transactionToReview = $id;
        $this->showResolveModal = true;
    }

    public function showExportOptions()
    {
        $this->showExportOptionsModal = true;
    }

    public function showScheduleReport()
    {
        $this->showScheduleReportModal = true;
    }

    public function downloadExcelFile()
    {
        $this->validate([
            'reportEndDate' => 'required|date',
            'reportStartDate' => 'required|date|before_or_equal:reportEndDate'
        ]);

        try {
            $this->isExporting = true;

            if ($this->customize == "YES") {
                $input = $this->custome_client_number;
                $input = rtrim($input, ',');
                $numbers = explode(',', $input);
                $array = [];

                foreach ($numbers as $number) {
                    $number = trim($number);
                    $number = intval($number);

                    if (LoansModel::where('client_number', $number)->exists()) {
                        $array[] = ['number' => str_pad($number, 4, 0, STR_PAD_LEFT)];
                    }
                }

                $LoanId = LoansModel::whereIn('client_number', $array)->pluck('id');
            } else {
                $LoanId = LoansModel::get()->pluck('id')->toArray();
            }

            $filename = 'comprehensive_report_' . now()->format('Y-m-d_H-i-s') . '.xlsx';
            
            return Excel::download(new MainReport($LoanId), $filename);

        } catch (Exception $e) {
            Log::error('Error downloading Excel file: ' . $e->getMessage());
            $this->errorMessage = 'Failed to generate Excel report.';
        } finally {
            $this->isExporting = false;
        }
    }

    public function generateReport()
    {
        try {
            $this->isGenerating = true;
            $this->validate([
                'reportStartDate' => 'required|date',
                'reportEndDate' => 'required|date|after_or_equal:reportStartDate',
                'reportFormat' => 'required|in:pdf,excel,csv'
            ]);

            // Generate report based on selected type
            $reportData = $this->prepareReportData();
            
            // Log report generation
            $this->logReportGeneration($reportData);

            $this->successMessage = 'Report generated successfully!';
            $this->emit('reportGenerated', $reportData);

        } catch (Exception $e) {
            Log::error('Error generating report: ' . $e->getMessage());
            $this->errorMessage = 'Failed to generate report: ' . $e->getMessage();
        } finally {
            $this->isGenerating = false;
        }
    }

    public function prepareReportData()
    {
        $data = [
            'report_type' => $this->selectedReportType,
            'period' => [
                'start' => $this->reportStartDate,
                'end' => $this->reportEndDate
            ],
            'analytics' => [
                'total_members' => $this->totalMembers,
                'active_members' => $this->activeMembers,
                'total_loans' => $this->totalLoans,
                'active_loans' => $this->activeLoans,
                'portfolio_at_risk' => $this->portfolioAtRisk,
                'capital_adequacy_ratio' => $this->capitalAdequacyRatio,
                'liquidity_ratio' => $this->liquidityRatio,
                'monthly_growth' => $this->monthlyGrowth
            ],
            'generated_at' => now()->format('Y-m-d H:i:s'),
            'generated_by' => auth()->user()->name,
            'format' => $this->reportFormat
        ];

        return $data;
    }

    public function logReportGeneration($reportData)
    {
        try {
            DB::table('scheduled_reports')->insert([
                'report_type' => $reportData['report_type'],
                'report_config' => json_encode($reportData),
                'user_id' => auth()->id(),
                'status' => 'completed',
                'generated_at' => now(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
        } catch (Exception $e) {
            Log::error('Error logging report generation: ' . $e->getMessage());
        }
    }

    public function scheduleReport()
    {
        try {
            $this->isScheduling = true;
            $this->validate([
                'scheduleDate' => 'required|date|after:today',
                'scheduleTime' => 'required|date_format:H:i',
                'emailRecipients' => 'required|array|min:1',
                'emailSubject' => 'required|string|max:255',
                'emailMessage' => 'required|string'
            ]);

            // Schedule the report
            $scheduledReport = DB::table('scheduled_reports')->insert([
                'report_type' => $this->selectedReportType,
                'report_config' => json_encode($this->prepareReportData()),
                'user_id' => auth()->id(),
                'status' => 'scheduled',
                'frequency' => $this->scheduleFrequency,
                'scheduled_at' => $this->scheduleDate . ' ' . $this->scheduleTime,
                'email_recipients' => json_encode($this->emailRecipients),
                'email_subject' => $this->emailSubject,
                'email_message' => $this->emailMessage,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            $this->successMessage = 'Report scheduled successfully!';
            $this->showScheduleReportModal = false;
            $this->loadReportHistory();

        } catch (Exception $e) {
            Log::error('Error scheduling report: ' . $e->getMessage());
            $this->errorMessage = 'Failed to schedule report: ' . $e->getMessage();
        } finally {
            $this->isScheduling = false;
        }
    }

    public function handleReportGenerated($data)
    {
        $this->successMessage = 'Report generated successfully!';
        $this->loadReportHistory();
    }

    public function handleExportCompleted($data)
    {
        $this->successMessage = 'Export completed successfully!';
    }

    public function saveResolution()
    {
        try {
            $rrn = Transactions::where('ID', $this->transactionToReview)->value('DB_TABLE_REFERENCE');
            
            if ($rrn) {
                DB::table('transactions')->where('DB_TABLE_REFERENCE', $rrn)->update([
                    'status' => 'RESOLVED',
                    'resolution_comments' => $this->comments,
                    'resolved_by' => auth()->id(),
                    'resolved_at' => now()
                ]);

                $this->successMessage = 'Transaction resolved successfully!';
                $this->showResolveModal = false;
                $this->comments = '';
            } else {
                $this->errorMessage = 'Transaction not found.';
            }
        } catch (Exception $e) {
            Log::error('Error saving resolution: ' . $e->getMessage());
            $this->errorMessage = 'Failed to save resolution.';
        }
    }

    public function render()
    {
        return view('livewire.reports.reports');
    }
}
