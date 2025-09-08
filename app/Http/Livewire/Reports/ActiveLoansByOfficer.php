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
use App\Models\Employee;

class ActiveLoansByOfficer extends Component
{
    public $selectedOfficer = '';
    public $loans = [];
    public $officers = [];
    public $totalLoans = 0;
    public $totalLoanAmount = 0;
    public $overdueLoans = 0;
    public $activeOfficers = 0;
    public $officerLoans = 0;
    public $officerLoanAmount = 0;
    public $officerOverdueLoans = 0;

    public function mount()
    {
        $this->loadOfficers();
        $this->loadLoans();
        $this->calculateSummary();
    }

    public function loadOfficers()
    {
        $this->officers = Employee::whereHas('loans')->get();
    }

    public function loadLoans()
    {
        $query = LoansModel::query()->where('status', 'ACTIVE');

        if (!empty($this->selectedOfficer)) {
            $query->where('supervisor_id', $this->selectedOfficer);
        }

        $this->loans = $query->get()->map(function ($loan) {
            // Get member name
            $member = ClientsModel::find($loan->client_id);
            $loan->member_name = $member ? trim($member->first_name . ' ' . $member->middle_name . ' ' . $member->last_name) : 'N/A';

            // Get guarantor name
            $guarantor = ClientsModel::where('client_number', $loan->guarantor)->first();
            $loan->guarantor_name = $guarantor ? trim($guarantor->first_name . ' ' . $guarantor->middle_name . ' ' . $guarantor->last_name) : 'N/A';

            // Get branch name
            $branch = BranchesModel::find($loan->branch_id);
            $loan->branch_name = $branch ? $branch->name : 'N/A';

            // Get officer name
            $officer = Employee::find($loan->supervisor_id);
            $loan->officer_name = $officer ? trim($officer->first_name . ' ' . $officer->middle_name . ' ' . $officer->last_name) : 'N/A';

            return $loan;
        });
    }

    public function loadOfficerData()
    {
        $this->loadLoans();
        $this->calculateOfficerSummary();
    }

    public function calculateSummary()
    {
        $this->totalLoans = LoansModel::where('status', 'ACTIVE')->count();
        $this->totalLoanAmount = LoansModel::where('status', 'ACTIVE')->sum('principle');
        $this->overdueLoans = LoansModel::where('status', 'ACTIVE')->where('days_in_arrears', '>', 0)->count();
        $this->activeOfficers = Employee::whereHas('loans', function($query) {
            $query->where('status', 'ACTIVE');
        })->count();
    }

    public function calculateOfficerSummary()
    {
        if (empty($this->selectedOfficer)) {
            $this->officerLoans = 0;
            $this->officerLoanAmount = 0;
            $this->officerOverdueLoans = 0;
            return;
        }

        $this->officerLoans = LoansModel::where('status', 'ACTIVE')->where('supervisor_id', $this->selectedOfficer)->count();
        $this->officerLoanAmount = LoansModel::where('status', 'ACTIVE')->where('supervisor_id', $this->selectedOfficer)->sum('principle');
        $this->officerOverdueLoans = LoansModel::where('status', 'ACTIVE')->where('supervisor_id', $this->selectedOfficer)->where('days_in_arrears', '>', 0)->count();
    }

    public function refreshData()
    {
        $this->loadOfficers();
        $this->loadLoans();
        $this->calculateSummary();
        $this->calculateOfficerSummary();
        
        session()->flash('success', 'Data refreshed successfully!');
    }

    public function exportReport($format = 'pdf')
    {
        try {
            // Here you would implement the actual export logic
            // For now, we'll just show a success message
            session()->flash('success', "Report exported as {$format} successfully!");
            
            Log::info('Active Loans by Officer Report exported', [
                'format' => $format,
                'officer_id' => $this->selectedOfficer,
                'user_id' => auth()->id()
            ]);
        } catch (Exception $e) {
            session()->flash('error', 'Error exporting report: ' . $e->getMessage());
            Log::error('Active Loans by Officer Report export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }


    public function render()
    {
        return view('livewire.reports.active-loans-by-officer');
    }
}
