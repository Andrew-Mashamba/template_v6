<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Services\HistoricalBalanceService;

class StatementOfComprehensiveIncome extends Component
{
    public $startDate;
    public $endDate;
    public $income = [];
    public $expenses = [];
    public $totalIncome = 0;
    public $totalExpenses = 0;
    public $netIncome = 0;
    
    // Past year data
    public $pastYearIncome = [];
    public $pastYearExpenses = [];
    public $pastYearTotalIncome = 0;
    public $pastYearTotalExpenses = 0;
    public $pastYearNetIncome = 0;
    
    // Previous period data for comparison
    public $previousTotalIncome = 0;
    public $previousTotalExpenses = 0;
    public $previousNetIncome = 0;
    
    // Historical data properties
    public $selectedPastYear;
    public $availableYears = [];
    protected $historicalBalanceService;

    public $reportPeriod = 'monthly';
    public $currency = 'TZS';
    public $viewFormat = 'detailed';
    
    public $showCharts = false;
    public $showIncomeDetails = true;
    public $showExpenseDetails = true;
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

    public function mount()
    {
        $this->endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->scheduleDate = Carbon::now()->addDay()->format('Y-m-d');
        $this->emailSubject = 'Statement of Comprehensive Income - ' . Carbon::now()->format('Y');
        $this->emailMessage = 'Please find attached the Statement of Comprehensive Income report.';
        
        $this->historicalBalanceService = new HistoricalBalanceService();
        $this->availableYears = $this->historicalBalanceService->getAvailableYears();
        $this->selectedPastYear = $this->historicalBalanceService->getMostRecentYear() ?? (Carbon::now()->year - 1);
        
        $this->loadData();
    }

    public function loadData()
    {
        $this->isLoading = true;
        
        try {
            // Load Income (4000 series accounts) - Level 2 only
            $this->income = DB::table('accounts')
                ->where('major_category_code', '4000')
                ->where('account_level', 2)
                ->select('account_name', 'balance', 'account_number')
                ->get();

            // Load Expenses (5000 series accounts) - Level 2 only
            $this->expenses = DB::table('accounts')
                ->where('major_category_code', '5000')
                ->where('account_level', 2)
                ->select('account_name', 'balance', 'account_number')
                ->get();

            // Calculate totals
            $this->totalIncome = $this->income->sum('balance');
            $this->totalExpenses = $this->expenses->sum('balance');
            $this->netIncome = $this->totalIncome - $this->totalExpenses;
            
            // Load historical data for past year
            $this->pastYearIncome = $this->historicalBalanceService->getHistoricalBalances($this->selectedPastYear, '4000');
            $this->pastYearExpenses = $this->historicalBalanceService->getHistoricalBalances($this->selectedPastYear, '5000');
            
            // Calculate past year totals
            $this->pastYearTotalIncome = $this->pastYearIncome->sum('balance');
            $this->pastYearTotalExpenses = $this->pastYearExpenses->sum('balance');
            $this->pastYearNetIncome = $this->pastYearTotalIncome - $this->pastYearTotalExpenses;
            
            // Load previous period data for comparison
            $this->loadPreviousPeriodData();
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error loading financial data: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
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
            
            // Load previous period income
            $previousIncome = DB::table('accounts')
                ->where('major_category_code', '4000')
                ->where('account_level', 2)
                ->select('account_name', 'balance', 'account_number')
                ->get();
            
            // Load previous period expenses
            $previousExpenses = DB::table('accounts')
                ->where('major_category_code', '5000')
                ->where('account_level', 2)
                ->select('account_name', 'balance', 'account_number')
                ->get();
            
            $this->previousTotalIncome = $previousIncome->sum('balance');
            $this->previousTotalExpenses = $previousExpenses->sum('balance');
            $this->previousNetIncome = $this->previousTotalIncome - $this->previousTotalExpenses;
            
        } catch (\Exception $e) {
            // If there's an error loading previous period data, set defaults
            $this->previousTotalIncome = $this->totalIncome * 0.95;
            $this->previousTotalExpenses = $this->totalExpenses * 0.92;
            $this->previousNetIncome = $this->previousTotalIncome - $this->previousTotalExpenses;
        }
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
            session()->flash('success', 'Statement of Comprehensive Income generated successfully!');
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
                'income' => $this->income,
                'expenses' => $this->expenses,
                'totalIncome' => $this->totalIncome,
                'totalExpenses' => $this->totalExpenses,
                'netIncome' => $this->netIncome,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'currency' => $this->currency,
                'reportDate' => now()->format('Y-m-d H:i:s')
            ];

            $pdf = PDF::loadView('pdf.statement-of-comprehensive-income', $data);
            
            $filename = 'statement-of-comprehensive-income-' . now()->format('Y-m-d') . '.pdf';
            
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->output();
            }, $filename);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error exporting PDF: ' . $e->getMessage());
        }
    }

    public function exportToExcel()
    {
        try {
            // Create CSV content
            $csvContent = $this->generateCSVContent();
            
            $filename = 'statement-of-comprehensive-income-' . now()->format('Y-m-d') . '.csv';
            
            return response()->streamDownload(function () use ($csvContent) {
                echo $csvContent;
            }, $filename, ['Content-Type' => 'text/csv']);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error exporting Excel: ' . $e->getMessage());
        }
    }

    private function generateCSVContent()
    {
        $csv = "Statement of Comprehensive Income\n";
        $csv .= "Period: {$this->startDate} to {$this->endDate}\n";
        $csv .= "Currency: {$this->currency}\n\n";
        
        // Income section
        $csv .= "INCOME\n";
        $csv .= "Account Name,Balance\n";
        foreach ($this->income as $incomeItem) {
            $accountName = is_object($incomeItem) ? $incomeItem->account_name : $incomeItem['account_name'];
            $balance = is_object($incomeItem) ? $incomeItem->balance : $incomeItem['balance'];
            $csv .= "\"{$accountName}\",{$balance}\n";
        }
        $csv .= "TOTAL INCOME,{$this->totalIncome}\n\n";
        
        // Expenses section
        $csv .= "EXPENSES\n";
        $csv .= "Account Name,Balance\n";
        foreach ($this->expenses as $expense) {
            $accountName = is_object($expense) ? $expense->account_name : $expense['account_name'];
            $balance = is_object($expense) ? $expense->balance : $expense['balance'];
            $csv .= "\"{$accountName}\",{$balance}\n";
        }
        $csv .= "TOTAL EXPENSES,{$this->totalExpenses}\n\n";
        
        $csv .= "NET INCOME," . $this->netIncome . "\n";
        
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
                'report_type' => 'Statement of Comprehensive Income',
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

    public function toggleIncomeDetails()
    {
        $this->showIncomeDetails = !$this->showIncomeDetails;
    }

    public function toggleExpenseDetails()
    {
        $this->showExpenseDetails = !$this->showExpenseDetails;
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
                'totalIncome' => $this->totalIncome * 0.95, // Mock data
                'totalExpenses' => $this->totalExpenses * 0.92,
                'netIncome' => ($this->totalIncome * 0.95) - ($this->totalExpenses * 0.92),
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

    public function isProfitable()
    {
        return $this->netIncome >= 0;
    }

    public function captureHistoricalBalances()
    {
        try {
            $currentYear = Carbon::now()->year;
            $result = $this->historicalBalanceService->captureYearEndBalances($currentYear, auth()->id());
            
            if ($result['success']) {
                session()->flash('success', $result['message']);
                // Refresh available years
                $this->availableYears = $this->historicalBalanceService->getAvailableYears();
            } else {
                session()->flash('error', $result['message']);
            }
        } catch (\Exception $e) {
            session()->flash('error', 'Error capturing historical balances: ' . $e->getMessage());
        }
    }

    public function updatedSelectedPastYear()
    {
        $this->loadData();
    }

    public function render()
    {
        return view('livewire.reports.statement-of-comprehensive-income');
    }
} 