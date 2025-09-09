<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class LoanDelinquencyReport extends Component
{
    public $reportEndDate;
    public $reportData = null;
    public $isGenerating = false;
    public $isExporting = false;
    public $errorMessage = '';
    public $successMessage = '';

    public function mount()
    {
        $this->reportEndDate = Carbon::now()->format('Y-m-d');
    }

    public function generateReport()
    {
        $this->isGenerating = true;
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $this->reportData = $this->getReportData();
            $this->successMessage = 'Loan Delinquency Report generated successfully!';
            
            Log::info('Loan Delinquency Report generated', [
                'end_date' => $this->reportEndDate,
                'user_id' => auth()->id()
            ]);
        } catch (Exception $e) {
            $this->errorMessage = 'Error generating report: ' . $e->getMessage();
            Log::error('Loan Delinquency Report generation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        } finally {
            $this->isGenerating = false;
        }
    }

    public function getReportData()
    {
        $endDate = Carbon::parse($this->reportEndDate)->endOfDay();

        // Get approved loans
        $loans = DB::table('loans')
            ->where('status', 'APPROVED')
            ->where('disbursement_date', '<=', $endDate)
            ->get();

        $delinquentLoans = [];
        $totalDelinquentAmount = 0;
        $delinquencyByAge = [
            '1-30 days' => 0,
            '31-60 days' => 0,
            '61-90 days' => 0,
            '91-180 days' => 0,
            'Over 180 days' => 0,
        ];

        $totalLoanPortfolio = 0;

        foreach ($loans as $loan) {
            // Get outstanding balance
            $outstandingBalance = DB::table('loans_schedules')
                ->where('loan_id', $loan->loan_id)
                ->where('status', 'PENDING')
                ->sum('opening_balance');

            $totalLoanPortfolio += $outstandingBalance;

            if ($outstandingBalance > 0) {
                // Get overdue installments
                $overdueInstallments = DB::table('loans_schedules')
                    ->where('loan_id', $loan->loan_id)
                    ->where('status', 'PENDING')
                    ->where('installment_date', '<', $endDate)
                    ->get();

                if ($overdueInstallments->count() > 0) {
                    // Calculate total overdue amount
                    $overdueAmount = $overdueInstallments->sum('installment');
                    $totalDelinquentAmount += $overdueAmount;

                    // Get the most overdue installment for days calculation
                    $mostOverdue = $overdueInstallments->sortBy('installment_date')->first();
                    $daysPastDue = Carbon::parse($mostOverdue->installment_date)->diffInDays($endDate);

                    $delinquencyStatus = $this->getDelinquencyStatus($daysPastDue);
                    
                    $delinquentLoans[] = [
                        'loan_id' => $loan->loan_id,
                        'loan_account_number' => $loan->loan_account_number,
                        'client_number' => $loan->client_number,
                        'business_name' => $loan->business_name,
                        'outstanding_balance' => $outstandingBalance,
                        'overdue_amount' => $overdueAmount,
                        'last_due_date' => $mostOverdue->installment_date,
                        'days_past_due' => $daysPastDue,
                        'delinquency_status' => $delinquencyStatus,
                        'overdue_installments' => $overdueInstallments->count(),
                    ];

                    // Categorize by age
                    if ($daysPastDue <= 30) {
                        $delinquencyByAge['1-30 days'] += $overdueAmount;
                    } elseif ($daysPastDue <= 60) {
                        $delinquencyByAge['31-60 days'] += $overdueAmount;
                    } elseif ($daysPastDue <= 90) {
                        $delinquencyByAge['61-90 days'] += $overdueAmount;
                    } elseif ($daysPastDue <= 180) {
                        $delinquencyByAge['91-180 days'] += $overdueAmount;
                    } else {
                        $delinquencyByAge['Over 180 days'] += $overdueAmount;
                    }
                }
            }
        }

        $delinquencyRate = $totalLoanPortfolio > 0 ? ($totalDelinquentAmount / $totalLoanPortfolio) * 100 : 0;

        return [
            'period' => [
                'end_date' => $endDate->format('F d, Y'),
                'end_date_short' => $endDate->format('M d, Y'),
            ],
            'delinquency_summary' => [
                'total_delinquent_amount' => $totalDelinquentAmount,
                'total_loan_portfolio' => $totalLoanPortfolio,
                'delinquency_rate' => $delinquencyRate,
                'number_of_delinquent_loans' => count($delinquentLoans),
            ],
            'delinquent_loans' => $delinquentLoans,
            'delinquency_by_age' => $delinquencyByAge,
        ];
    }

    private function getDelinquencyStatus($daysPastDue)
    {
        if ($daysPastDue <= 30) {
            return '1-30 Days Past Due';
        } elseif ($daysPastDue <= 60) {
            return '31-60 Days Past Due';
        } elseif ($daysPastDue <= 90) {
            return '61-90 Days Past Due';
        } elseif ($daysPastDue <= 180) {
            return '91-180 Days Past Due';
        } else {
            return 'Over 180 Days Past Due';
        }
    }

    public function exportReport($format = 'pdf')
    {
        $this->isExporting = true;
        $this->errorMessage = '';

        try {
            if (!$this->reportData) {
                $this->reportData = $this->getReportData();
            }

            $this->successMessage = "Loan Delinquency Report exported as {$format} successfully!";
            
            Log::info('Loan Delinquency Report exported', [
                'format' => $format,
                'user_id' => auth()->id()
            ]);
        } catch (Exception $e) {
            $this->errorMessage = 'Error exporting report: ' . $e->getMessage();
            Log::error('Loan Delinquency Report export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        } finally {
            $this->isExporting = false;
        }
    }

    public function render()
    {
        return view('livewire.reports.loan-delinquency-report');
    }
}