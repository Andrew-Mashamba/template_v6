<?php

namespace App\Http\Livewire\ActiveLoan;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Services\LoanLossProvisionService;
use Carbon\Carbon;

class WriteOffs extends Component
{
    use WithPagination;
    
    public $showWriteOffModal = false;
    public $selectedLoan = null;
    public $writeOffReason = '';
    public $writeOffAmount = 0;
    public $searchTerm = '';
    public $filterStatus = 'pending';
    public $dateFrom;
    public $dateTo;
    
    protected $paginationTheme = 'bootstrap';
    
    protected $rules = [
        'writeOffReason' => 'required|min:10',
        'writeOffAmount' => 'required|numeric|min:0',
    ];

    public function mount()
    {
        $this->dateFrom = Carbon::now()->subMonths(3)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }
    
    public function getEligibleLoansProperty()
    {
        return DB::table('loans')
            ->select([
                'loans.id',
                'loans.loan_id',
                'loans.client_number',
                'loans.loan_sub_product',
                'loans.principle',
                DB::raw('loans.principle - COALESCE(loans.total_principal_paid, 0) as outstanding'),
                'loans.total_arrears',
                'loans.days_in_arrears',
                'loans.loan_classification',
                'loans.disbursement_date',
                'clients.first_name',
                'clients.last_name',
                'clients.mobile_phone_number'
            ])
            ->leftJoin('clients', 'loans.client_id', '=', 'clients.id')
            ->where('loans.loan_status', 'active')
            ->where('loans.loan_classification', 'LOSS')
            ->where('loans.days_in_arrears', '>', 180)
            ->when($this->searchTerm, function($query) {
                $query->where(function($q) {
                    $q->where('loans.loan_id', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('loans.client_number', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('clients.first_name', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('clients.last_name', 'like', '%' . $this->searchTerm . '%');
                });
            })
            ->orderBy('loans.days_in_arrears', 'desc')
            ->paginate(10);
    }
    
    public function getWrittenOffLoansProperty()
    {
        return DB::table('loan_write_offs')
            ->select([
                'loan_write_offs.*',
                'loans.loan_id',
                'loans.client_number',
                'loans.loan_sub_product',
                'clients.first_name',
                'clients.last_name',
                'users.name as approved_by_name'
            ])
            ->join('loans', 'loan_write_offs.loan_id', '=', 'loans.loan_id')
            ->leftJoin('clients', 'loans.client_id', '=', 'clients.id')
            ->leftJoin('users', 'loan_write_offs.approved_by', '=', 'users.id')
            ->whereBetween('loan_write_offs.write_off_date', [$this->dateFrom, $this->dateTo])
            ->when($this->searchTerm, function($query) {
                $query->where(function($q) {
                    $q->where('loans.loan_id', 'like', '%' . $this->searchTerm . '%')
                      ->orWhere('loans.client_number', 'like', '%' . $this->searchTerm . '%');
                });
            })
            ->orderBy('loan_write_offs.write_off_date', 'desc')
            ->paginate(10);
    }
    
    public function initiateWriteOff($loanId)
    {
        $this->selectedLoan = DB::table('loans')
            ->select([
                'loans.*',
                'clients.first_name',
                'clients.last_name',
                DB::raw('loans.principle - COALESCE(loans.total_principal_paid, 0) as outstanding')
            ])
            ->leftJoin('clients', 'loans.client_id', '=', 'clients.id')
            ->where('loans.id', $loanId)
            ->first();
            
        if ($this->selectedLoan) {
            $this->writeOffAmount = $this->selectedLoan->outstanding;
            $this->showWriteOffModal = true;
        }
    }
    
    public function processWriteOff()
    {
        $this->validate();
        
        try {
            DB::beginTransaction();
            
            // Create write-off record
            DB::table('loan_write_offs')->insert([
                'loan_id' => $this->selectedLoan->loan_id,
                'write_off_date' => now(),
                'amount' => $this->writeOffAmount,
                'reason' => $this->writeOffReason,
                'status' => 'pending_approval',
                'created_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Update loan status
            DB::table('loans')
                ->where('id', $this->selectedLoan->id)
                ->update([
                    'loan_status' => 'written_off_pending',
                    'updated_at' => now()
                ]);
            
            // Use provisions if available
            $provisionService = new LoanLossProvisionService();
            $provisionService->writeOffLoan($this->selectedLoan->loan_id, $this->writeOffAmount);
            
            DB::commit();
            
            session()->flash('success', 'Write-off initiated successfully. Pending approval.');
            $this->showWriteOffModal = false;
            $this->reset(['selectedLoan', 'writeOffReason', 'writeOffAmount']);
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error processing write-off: ' . $e->getMessage());
        }
    }
    
    public function approveWriteOff($writeOffId)
    {
        try {
            DB::beginTransaction();
            
            $writeOff = DB::table('loan_write_offs')->where('id', $writeOffId)->first();
            
            // Update write-off record
            DB::table('loan_write_offs')
                ->where('id', $writeOffId)
                ->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_date' => now(),
                    'updated_at' => now()
                ]);
            
            // Update loan status
            DB::table('loans')
                ->where('loan_id', $writeOff->loan_id)
                ->update([
                    'loan_status' => 'written_off',
                    'closure_date' => now(),
                    'updated_at' => now()
                ]);
            
            DB::commit();
            
            session()->flash('success', 'Write-off approved successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error approving write-off: ' . $e->getMessage());
        }
    }
    
    public function render()
    {
        return view('livewire.active-loan.write-offs', [
            'eligibleLoans' => $this->eligibleLoans,
            'writtenOffLoans' => $this->writtenOffLoans
        ]);
    }
}
