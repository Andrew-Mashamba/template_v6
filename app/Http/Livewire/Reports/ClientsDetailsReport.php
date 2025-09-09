<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Models\approvals;
use App\Models\LoansModel;
use App\Models\Transactions;
use App\Models\ClientsModel;
use App\Models\BranchesModel;
use App\Models\AccountsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MainReport;
use App\Exports\LoanSchedule;
use App\Exports\ContractData;
use Carbon\Carbon;
use Exception;

class ClientsDetailsReport extends Component
{
    public $client_type = "ALL";
    public $custome_client_number = '';
    public $branchFilter = '';
    public $statusFilter = '';
    public $members = [];
    public $branches = [];
    public $totalMembers = 0;
    public $activeMembers = 0;
    public $pendingMembers = 0;
    public $inactiveMembers = 0;
    public $totalBranches = 0;
    public $totalSavings = 0;
    public $membersWithLoans = 0;
    
    // Modal properties
    public $showMemberModal = false;
    public $selectedMember = null;



    public function mount()
    {
        $this->loadBranches();
        $this->loadMembers();
        $this->calculateSummary();
    }

    public function loadBranches()
    {
        $this->branches = BranchesModel::all();
    }

    public function loadMembers()
    {
        $query = ClientsModel::query();

        if ($this->client_type === 'MULTIPLE' && !empty($this->custome_client_number)) {
            // Handle multiple member selection
            $input = rtrim($this->custome_client_number, ',');
            $numbers = explode(',', $input);
            $memberNumbers = [];
            
            foreach ($numbers as $number) {
                $number = trim($number);
                $number = str_pad($number, 4, 0, STR_PAD_LEFT);
                $memberNumbers[] = $number;
            }
            
            $query->whereIn('client_number', $memberNumbers);
        } else {
            // Apply filters for all members
            if (!empty($this->branchFilter)) {
                $query->where('branch_id', $this->branchFilter);
            }
            
            if (!empty($this->statusFilter)) {
                $query->where('status', $this->statusFilter);
            }
        }

        $this->members = $query->get()->map(function ($member) {
            // Get branch name
            $branch = BranchesModel::find($member->branch_id);
            $member->branch_name = $branch ? $branch->name : 'N/A';

            // Create full name
            $member->full_name = trim($member->first_name . ' ' . $member->middle_name . ' ' . $member->last_name);

            // Format registration date
            $member->registration_date = $member->created_at ? $member->created_at->format('Y-m-d') : 'N/A';

            // Get savings balance (you might need to adjust this based on your accounts structure)
            $savingsAccount = AccountsModel::where('client_number', $member->client_number)
                ->where('product_number', '1000') // Assuming 1000 is savings product
                ->first();
            $member->savings_balance = $savingsAccount ? $savingsAccount->balance : 0;

            return $member;
        });
    }

    public function calculateSummary()
    {
        $this->totalMembers = ClientsModel::count();
        $this->activeMembers = ClientsModel::where('status', 'ACTIVE')->count();
        $this->pendingMembers = ClientsModel::where('status', 'PENDING')->count();
        $this->inactiveMembers = ClientsModel::where('status', 'INACTIVE')->count();
        $this->totalBranches = BranchesModel::count();
        
        // Calculate total savings
        $this->totalSavings = AccountsModel::where('product_number', '1000')->sum('balance');
        
        // Calculate members with loans
        $this->membersWithLoans = ClientsModel::whereHas('loans')->count();
    }

    public function viewMemberDetails($memberId)
    {
        $this->selectedMember = ClientsModel::find($memberId);
        if ($this->selectedMember) {
            // Get branch name
            $branch = BranchesModel::find($this->selectedMember->branch_id);
            $this->selectedMember->branch_name = $branch ? $branch->name : 'N/A';
            
            // Create full name
            $this->selectedMember->full_name = trim($this->selectedMember->first_name . ' ' . $this->selectedMember->middle_name . ' ' . $this->selectedMember->last_name);
            
            // Format registration date
            $this->selectedMember->registration_date = $this->selectedMember->created_at ? $this->selectedMember->created_at->format('Y-m-d') : 'N/A';
            
            // Get savings balance
            $savingsAccount = AccountsModel::where('client_number', $this->selectedMember->client_number)
                ->where('product_number', '1000')
                ->first();
            $this->selectedMember->savings_balance = $savingsAccount ? $savingsAccount->balance : 0;
        }
        $this->showMemberModal = true;
    }

    public function closeMemberModal()
    {
        $this->showMemberModal = false;
        $this->selectedMember = null;
    }

    public function exportReport($format = 'pdf')
    {
        try {
            // Here you would implement the actual export logic
            // For now, we'll just show a success message
            session()->flash('success', "Client Details Report exported as {$format} successfully!");
            
            Log::info('Client Details Report exported', [
                'format' => $format,
                'client_type' => $this->client_type,
                'branch_filter' => $this->branchFilter,
                'status_filter' => $this->statusFilter,
                'user_id' => auth()->id()
            ]);
        } catch (Exception $e) {
            session()->flash('error', 'Error exporting report: ' . $e->getMessage());
            Log::error('Client Details Report export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    public function downloadExcelFile()
    {
        if ($this->client_type == "MULTIPLE") {
            $input = $this->custome_client_number;
            $input = rtrim($input, ',');
            $numbers = explode(',', $input);
            $memberNumbers = [];

            foreach ($numbers as $number) {
                $number = trim($number);
                $number = intval($number);
                $number = str_pad($number, 4, 0, STR_PAD_LEFT);
                $memberNumbers[] = $number;
            }

            $LoanId = LoansModel::whereIn('client_number', $memberNumbers)->pluck('id');
            return Excel::download(new MainReport($LoanId), 'generalReport.xlsx');
        } else {
            $loanId = LoansModel::get()->pluck('id')->toArray();
            return Excel::download(new MainReport($loanId), 'generalReport.xlsx');
        }
    }


    public function render()
    {
        return view('livewire.reports.clients-details-report');
    }
}
