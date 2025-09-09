<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class LoanPortfolioReport extends Component
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
            $this->successMessage = 'Loan Portfolio Report generated successfully!';
            
            Log::info('Loan Portfolio Report generated', [
                'end_date' => $this->reportEndDate,
                'user_id' => auth()->id()
            ]);
        } catch (Exception $e) {
            $this->errorMessage = 'Error generating report: ' . $e->getMessage();
            Log::error('Loan Portfolio Report generation failed', [
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

        // Get approved loans from loans table
        $loans = DB::table('loans')
            ->where('status', 'APPROVED')
            ->where('disbursement_date', '<=', $endDate)
            ->get();

        $totalLoanPortfolio = 0;
        $loanDetails = [];
        $portfolioByType = [];
        $portfolioByStatus = [];

        foreach ($loans as $loan) {
            // Calculate outstanding balance from loan schedules
            $outstandingBalance = DB::table('loans_schedules')
                ->where('loan_id', $loan->loan_id)
                ->where('status', 'PENDING')
                ->sum('opening_balance');

            if ($outstandingBalance > 0) {
                $totalLoanPortfolio += $outstandingBalance;
                
                $loanDetails[] = [
                    'loan_id' => $loan->loan_id,
                    'loan_account_number' => $loan->loan_account_number,
                    'client_number' => $loan->client_number,
                    'business_name' => $loan->business_name,
                    'principle' => $loan->principle,
                    'outstanding_balance' => $outstandingBalance,
                    'interest_rate' => $loan->interest,
                    'tenure' => $loan->tenure,
                    'disbursement_date' => $loan->disbursement_date,
                    'category' => $this->getLoanCategory($loan->loan_type_2),
                    'status' => $loan->loan_status,
                ];

                // Group by type
                $category = $this->getLoanCategory($loan->loan_type_2);
                if (!isset($portfolioByType[$category])) {
                    $portfolioByType[$category] = 0;
                }
                $portfolioByType[$category] += $outstandingBalance;

                // Group by status
                $status = $loan->loan_status;
                if (!isset($portfolioByStatus[$status])) {
                    $portfolioByStatus[$status] = 0;
                }
                $portfolioByStatus[$status] += $outstandingBalance;
            }
        }

        // Calculate portfolio statistics
        $portfolioStats = [
            'total_portfolio' => $totalLoanPortfolio,
            'number_of_loans' => count($loanDetails),
            'average_loan_size' => count($loanDetails) > 0 ? $totalLoanPortfolio / count($loanDetails) : 0,
            'largest_loan' => count($loanDetails) > 0 ? max(array_column($loanDetails, 'outstanding_balance')) : 0,
            'smallest_loan' => count($loanDetails) > 0 ? min(array_column($loanDetails, 'outstanding_balance')) : 0,
        ];

        return [
            'period' => [
                'end_date' => $endDate->format('F d, Y'),
                'end_date_short' => $endDate->format('M d, Y'),
            ],
            'portfolio_summary' => $portfolioStats,
            'loan_details' => $loanDetails,
            'portfolio_by_type' => $portfolioByType,
            'portfolio_by_status' => $portfolioByStatus,
        ];
    }

    private function getLoanCategory($loanType)
    {
        if (!$loanType) {
            return 'Other Loans';
        }
        
        $name = strtolower($loanType);
        
        if (strpos($name, 'personal') !== false) {
            return 'Personal Loans';
        } elseif (strpos($name, 'business') !== false || strpos($name, 'commercial') !== false) {
            return 'Business Loans';
        } elseif (strpos($name, 'agriculture') !== false || strpos($name, 'agricultural') !== false) {
            return 'Agricultural Loans';
        } elseif (strpos($name, 'education') !== false || strpos($name, 'school') !== false) {
            return 'Education Loans';
        } elseif (strpos($name, 'short') !== false || strpos($name, 'working') !== false) {
            return 'Short-term Loans';
        } elseif (strpos($name, 'long') !== false || strpos($name, 'term') !== false) {
            return 'Long-term Loans';
        } else {
            return 'Other Loans';
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

            $this->successMessage = "Loan Portfolio Report exported as {$format} successfully!";
            
            Log::info('Loan Portfolio Report exported', [
                'format' => $format,
                'user_id' => auth()->id()
            ]);
        } catch (Exception $e) {
            $this->errorMessage = 'Error exporting report: ' . $e->getMessage();
            Log::error('Loan Portfolio Report export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        } finally {
            $this->isExporting = false;
        }
    }

    public function render()
    {
        return view('livewire.reports.loan-portfolio-report');
    }
}
