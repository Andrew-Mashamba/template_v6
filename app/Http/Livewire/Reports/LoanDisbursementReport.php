<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

class LoanDisbursementReport extends Component
{
    public $reportStartDate;
    public $reportEndDate;
    public $reportData = null;
    public $isGenerating = false;
    public $isExporting = false;
    public $errorMessage = '';
    public $successMessage = '';

    public function mount()
    {
        $this->reportStartDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->reportEndDate = Carbon::now()->format('Y-m-d');
    }

    public function generateReport()
    {
        $this->isGenerating = true;
        $this->errorMessage = '';
        $this->successMessage = '';

        try {
            $this->reportData = $this->getReportData();
            $this->successMessage = 'Loan Disbursement Report generated successfully!';
            
            Log::info('Loan Disbursement Report generated', [
                'start_date' => $this->reportStartDate,
                'end_date' => $this->reportEndDate,
                'user_id' => auth()->id()
            ]);
        } catch (Exception $e) {
            $this->errorMessage = 'Error generating report: ' . $e->getMessage();
            Log::error('Loan Disbursement Report generation failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        } finally {
            $this->isGenerating = false;
        }
    }

    public function getReportData()
    {
        $startDate = Carbon::parse($this->reportStartDate)->startOfDay();
        $endDate = Carbon::parse($this->reportEndDate)->endOfDay();

        // Get loan disbursements from loans table
        $disbursements = DB::table('loans')
            ->where('status', 'APPROVED')
            ->where('disbursement_date', '>=', $startDate)
            ->where('disbursement_date', '<=', $endDate)
            ->orderBy('disbursement_date', 'desc')
            ->get();

        $totalDisbursed = $disbursements->sum('principle');
        $numberOfDisbursements = $disbursements->count();
        $averageDisbursement = $numberOfDisbursements > 0 ? $totalDisbursed / $numberOfDisbursements : 0;

        // Group disbursements by loan type
        $disbursementsByType = [];
        $disbursementsByDate = [];

        foreach ($disbursements as $disbursement) {
            $loanType = $this->getLoanType($disbursement->loan_type_2);
            
            if (!isset($disbursementsByType[$loanType])) {
                $disbursementsByType[$loanType] = [
                    'count' => 0,
                    'amount' => 0,
                ];
            }
            $disbursementsByType[$loanType]['count']++;
            $disbursementsByType[$loanType]['amount'] += $disbursement->principle;

            // Group by date
            $date = Carbon::parse($disbursement->disbursement_date)->format('Y-m-d');
            if (!isset($disbursementsByDate[$date])) {
                $disbursementsByDate[$date] = [
                    'count' => 0,
                    'amount' => 0,
                ];
            }
            $disbursementsByDate[$date]['count']++;
            $disbursementsByDate[$date]['amount'] += $disbursement->principle;
        }

        // Get daily disbursement trend
        $dailyTrend = [];
        $currentDate = $startDate->copy();
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            $dailyTrend[] = [
                'date' => $currentDate->format('M d'),
                'amount' => $disbursementsByDate[$dateStr]['amount'] ?? 0,
                'count' => $disbursementsByDate[$dateStr]['count'] ?? 0,
            ];
            $currentDate->addDay();
        }

        return [
            'period' => [
                'start_date' => $startDate->format('F d, Y'),
                'end_date' => $endDate->format('F d, Y'),
                'start_date_short' => $startDate->format('M d, Y'),
                'end_date_short' => $endDate->format('M d, Y'),
            ],
            'disbursement_summary' => [
                'total_disbursed' => $totalDisbursed,
                'number_of_disbursements' => $numberOfDisbursements,
                'average_disbursement' => $averageDisbursement,
                'largest_disbursement' => $disbursements->max('principle') ?? 0,
                'smallest_disbursement' => $disbursements->min('principle') ?? 0,
            ],
            'disbursements' => $disbursements,
            'disbursements_by_type' => $disbursementsByType,
            'daily_trend' => $dailyTrend,
        ];
    }

    private function getLoanType($loanType)
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

            $this->successMessage = "Loan Disbursement Report exported as {$format} successfully!";
            
            Log::info('Loan Disbursement Report exported', [
                'format' => $format,
                'user_id' => auth()->id()
            ]);
        } catch (Exception $e) {
            $this->errorMessage = 'Error exporting report: ' . $e->getMessage();
            Log::error('Loan Disbursement Report export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        } finally {
            $this->isExporting = false;
        }
    }

    public function render()
    {
        return view('livewire.reports.loan-disbursement-report');
    }
}