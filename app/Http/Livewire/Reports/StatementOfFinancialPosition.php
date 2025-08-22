<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use App\Services\HistoricalBalanceService;

class StatementOfFinancialPosition extends Component
{
    public $startDate;
    public $endDate;
    public $assets = [];
    public $liabilities = [];
    public $equity = [];
    public $totalAssets = 0;
    public $totalLiabilities = 0;
    public $totalEquity = 0;
    
    // Past year data
    public $pastYearAssets = [];
    public $pastYearLiabilities = [];
    public $pastYearEquity = [];
    public $pastYearTotalAssets = 0;
    public $pastYearTotalLiabilities = 0;
    public $pastYearTotalEquity = 0;
    
    // Balance sheet difference
    public $balanceSheetDifference = 0;
    public $isBalanced = false;
    
    // Historical data properties
    public $selectedPastYear;
    public $availableYears = [];
    protected $historicalBalanceService;

    public $reportPeriod = 'monthly';
    public $currency = 'TZS';
    public $viewFormat = 'detailed';
    
    public $showCharts = false;
    public $showAssetDetails = true;
    public $showLiabilityDetails = true;
    public $showEquityDetails = true;
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
        $this->emailSubject = 'Statement of Financial Position - ' . Carbon::now()->format('Y');
        $this->emailMessage = 'Please find attached the Statement of Financial Position report.';
        
        $this->historicalBalanceService = new HistoricalBalanceService();
        $this->availableYears = $this->historicalBalanceService->getAvailableYears();
        $this->selectedPastYear = $this->historicalBalanceService->getMostRecentYear() ?? (Carbon::now()->year - 1);
        
        $this->loadData();
    }

    public function loadData()
    {
        $this->isLoading = true;
        
        try {
            // Load Assets (1000 series accounts) - Level 2 only
            $this->assets = DB::table('accounts')
                ->where('major_category_code', '1000')
                ->where('account_level', 2)
                ->select('account_name', 'balance', 'account_number')
                ->get();

            // Load Liabilities (2000 series accounts) - Level 2 only
            $this->liabilities = DB::table('accounts')
                ->where('major_category_code', '2000')
                ->where('account_level', 2)
                ->select('account_name', 'balance', 'account_number')
                ->get();

            // Load Equity (3000 series accounts) - Level 2 only
            $this->equity = DB::table('accounts')
                ->where('major_category_code', '3000')
                ->where('account_level', 2)
                ->select('account_name', 'balance', 'account_number')
                ->get();

            // Calculate totals
            $this->totalAssets = $this->assets->sum('balance');
            $this->totalLiabilities = $this->liabilities->sum('balance');
            $this->totalEquity = $this->equity->sum('balance');
            
            // Load historical data for past year
            $this->pastYearAssets = $this->historicalBalanceService->getHistoricalBalances($this->selectedPastYear, '1000');
            $this->pastYearLiabilities = $this->historicalBalanceService->getHistoricalBalances($this->selectedPastYear, '2000');
            $this->pastYearEquity = $this->historicalBalanceService->getHistoricalBalances($this->selectedPastYear, '3000');
            
            // Calculate past year totals
            $this->pastYearTotalAssets = $this->pastYearAssets->sum('balance');
            $this->pastYearTotalLiabilities = $this->pastYearLiabilities->sum('balance');
            $this->pastYearTotalEquity = $this->pastYearEquity->sum('balance');
            
            // Calculate balance sheet difference
            $this->balanceSheetDifference = abs($this->totalAssets - ($this->totalLiabilities + $this->totalEquity));
            $this->isBalanced = $this->balanceSheetDifference < 0.01;
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error loading financial data: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
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
            session()->flash('success', 'Statement of Financial Position generated successfully!');
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
                'assets' => $this->assets,
                'liabilities' => $this->liabilities,
                'equity' => $this->equity,
                'totalAssets' => $this->totalAssets,
                'totalLiabilities' => $this->totalLiabilities,
                'totalEquity' => $this->totalEquity,
                'startDate' => $this->startDate,
                'endDate' => $this->endDate,
                'currency' => $this->currency,
                'reportDate' => now()->format('Y-m-d H:i:s')
            ];

            $pdf = PDF::loadView('pdf.statement-of-financial-position', $data);
            
            $filename = 'statement-of-financial-position-' . now()->format('Y-m-d') . '.pdf';
            
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
            
            $filename = 'statement-of-financial-position-' . now()->format('Y-m-d') . '.csv';
            
            return response()->streamDownload(function () use ($csvContent) {
                echo $csvContent;
            }, $filename, ['Content-Type' => 'text/csv']);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error exporting Excel: ' . $e->getMessage());
        }
    }

    private function generateCSVContent()
    {
        $csv = "Statement of Financial Position\n";
        $csv .= "Period: {$this->startDate} to {$this->endDate}\n";
        $csv .= "Currency: {$this->currency}\n\n";
        
        // Assets section
        $csv .= "ASSETS\n";
        $csv .= "Account Name,Balance\n";
        foreach ($this->assets as $asset) {
            $accountName = is_object($asset) ? $asset->account_name : $asset['account_name'];
            $balance = is_object($asset) ? $asset->balance : $asset['balance'];
            $csv .= "\"{$accountName}\",{$balance}\n";
        }
        $csv .= "TOTAL ASSETS,{$this->totalAssets}\n\n";
        
        // Liabilities section
        $csv .= "LIABILITIES\n";
        $csv .= "Account Name,Balance\n";
        foreach ($this->liabilities as $liability) {
            $accountName = is_object($liability) ? $liability->account_name : $liability['account_name'];
            $balance = is_object($liability) ? $liability->balance : $liability['balance'];
            $csv .= "\"{$accountName}\",{$balance}\n";
        }
        $csv .= "TOTAL LIABILITIES,{$this->totalLiabilities}\n\n";
        
        // Equity section
        $csv .= "EQUITY\n";
        $csv .= "Account Name,Balance\n";
        foreach ($this->equity as $equityItem) {
            $accountName = is_object($equityItem) ? $equityItem->account_name : $equityItem['account_name'];
            $balance = is_object($equityItem) ? $equityItem->balance : $equityItem['balance'];
            $csv .= "\"{$accountName}\",{$balance}\n";
        }
        $csv .= "TOTAL EQUITY,{$this->totalEquity}\n\n";
        
        $csv .= "TOTAL LIABILITIES AND EQUITY," . ($this->totalLiabilities + $this->totalEquity) . "\n";
        
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
                'report_type' => 'Statement of Financial Position',
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

    public function toggleAssetDetails()
    {
        $this->showAssetDetails = !$this->showAssetDetails;
    }

    public function toggleLiabilityDetails()
    {
        $this->showLiabilityDetails = !$this->showLiabilityDetails;
    }

    public function toggleEquityDetails()
    {
        $this->showEquityDetails = !$this->showEquityDetails;
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
                'totalAssets' => $this->totalAssets * 0.95, // Mock data
                'totalLiabilities' => $this->totalLiabilities * 0.92,
                'totalEquity' => $this->totalEquity * 1.03,
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

    public function isBalanced()
    {
        return abs($this->totalAssets - ($this->totalLiabilities + $this->totalEquity)) < 0.01;
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
        return view('livewire.reports.statement-of-financial-position');
    }
} 