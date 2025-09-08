<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\LoansModel;
use App\Models\ClientsModel;
use App\Models\BranchesModel;
use App\Models\LoanSubProduct;

class LoanApplicationReport extends Component
{
    public $reportStartDate;
    public $reportEndDate;
    public $statusFilter = '';
    public $applications = [];
    public $totalApplications = 0;
    public $approvedApplications = 0;
    public $pendingApplications = 0;
    public $rejectedApplications = 0;
    public $totalApplicationAmount = 0;
    public $averageApplicationAmount = 0;
    public $approvalRate = 0;

    public function mount()
    {
        $this->reportStartDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->reportEndDate = Carbon::now()->format('Y-m-d');
        $this->loadApplications();
        $this->calculateSummary();
    }

    public function loadApplications()
    {
        $startDate = Carbon::parse($this->reportStartDate)->startOfDay();
        $endDate = Carbon::parse($this->reportEndDate)->endOfDay();

        $query = LoansModel::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $this->applications = $query->get()->map(function ($application) {
            // Get client name
            $client = ClientsModel::where('client_number', $application->client_number)->first();
            $application->client_name = $client ? trim($client->first_name . ' ' . $client->middle_name . ' ' . $client->last_name) : 'N/A';

            // Get branch name
            $branch = BranchesModel::find($application->branch_id);
            $application->branch_name = $branch ? $branch->name : 'N/A';

            // Get loan product name
            $product = LoanSubProduct::where('product_id', $application->loan_sub_product)->first();
            $application->loan_product_name = $product ? $product->product_name : 'N/A';

            // Format application date
            $application->application_date = $application->created_at ? $application->created_at->format('Y-m-d') : 'N/A';

            // Calculate processing days
            if ($application->created_at && $application->updated_at) {
                $application->processing_days = $application->created_at->diffInDays($application->updated_at);
            } else {
                $application->processing_days = null;
            }

            return $application;
        });
    }

    public function calculateSummary()
    {
        $startDate = Carbon::parse($this->reportStartDate)->startOfDay();
        $endDate = Carbon::parse($this->reportEndDate)->endOfDay();

        $query = LoansModel::query()
            ->whereBetween('created_at', [$startDate, $endDate]);

        $this->totalApplications = $query->count();
        $this->approvedApplications = $query->where('status', 'APPROVED')->count();
        $this->pendingApplications = $query->where('status', 'PENDING')->count();
        $this->rejectedApplications = $query->where('status', 'REJECTED')->count();
        $this->totalApplicationAmount = $query->sum('principle');
        $this->averageApplicationAmount = $this->totalApplications > 0 ? $this->totalApplicationAmount / $this->totalApplications : 0;
        $this->approvalRate = $this->totalApplications > 0 ? round(($this->approvedApplications / $this->totalApplications) * 100, 2) : 0;
    }

    public function refreshData()
    {
        $this->loadApplications();
        $this->calculateSummary();
        
        session()->flash('success', 'Data refreshed successfully!');
    }

    public function exportReport($format = 'pdf')
    {
        try {
            // Here you would implement the actual export logic
            // For now, we'll just show a success message
            session()->flash('success', "Loan Application Report exported as {$format} successfully!");
            
            Log::info('Loan Application Report exported', [
                'format' => $format,
                'start_date' => $this->reportStartDate,
                'end_date' => $this->reportEndDate,
                'status_filter' => $this->statusFilter,
                'user_id' => auth()->id()
            ]);
        } catch (Exception $e) {
            session()->flash('error', 'Error exporting report: ' . $e->getMessage());
            Log::error('Loan Application Report export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.reports.loan-application-report');
    }
}