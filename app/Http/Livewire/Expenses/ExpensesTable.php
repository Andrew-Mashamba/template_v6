<?php

namespace App\Http\Livewire\Expenses;

use App\Models\Expense as ExpensesModel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ExpensesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = '';
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    // Payment Modal Properties
    public $showPaymentModal = false;
    public $selectedExpense = null;
    public $paymentMethod = '';
    public $bankAccount = '';
    public $accountHolderName = '';
    public $phoneNumber = '';
    public $mnoProvider = '';
    public $paymentReference = '';
    public $paymentNotes = '';
    public $isProcessingPayment = false;
    public $paymentErrors = [];
    public $availableBankAccounts = [];
    
    protected $listeners = ['refreshExpenses' => '$refresh'];
    
    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => ''],
        'perPage' => ['except' => 10]
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
    
    public function updatingPerPage()
    {
        $this->resetPage();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        
        $this->sortField = $field;
    }
    
    public function deleteExpenses($id)
    {
        try {
            $expense = ExpensesModel::find($id);
            
            if (!$expense) {
                session()->flash('error', 'Expense not found.');
                return;
            }
            
            // Check if user can delete (only owner of pending expenses)
            if ($expense->status !== 'PENDING_APPROVAL' || $expense->user_id !== auth()->user()->id) {
                session()->flash('error', 'You cannot delete this expense.');
                return;
            }
            
            $expense->delete();
            session()->flash('success', 'Expense deleted successfully.');
            $this->emit('refreshExpenses');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to delete expense: ' . $e->getMessage());
        }
    }
    
    public function openPaymentModal($id)
    {
        $this->resetPaymentForm();
        $this->selectedExpense = ExpensesModel::with(['account', 'user'])->find($id);
        
        // Load approval manually
        if ($this->selectedExpense) {
            $this->selectedExpense->approval = \App\Models\Approval::where('process_code', 'EXPENSE_REG')
                ->where('process_id', $id)
                ->first();
        }
        
        if (!$this->selectedExpense) {
            session()->flash('error', 'Expense not found.');
            return;
        }
        
        // Load available bank accounts for payment
        $this->loadBankAccounts();
        
        $this->showPaymentModal = true;
    }
    
    public function closePaymentModal()
    {
        $this->showPaymentModal = false;
        $this->resetPaymentForm();
    }
    
    private function resetPaymentForm()
    {
        $this->paymentMethod = '';
        $this->bankAccount = '';
        $this->accountHolderName = '';
        $this->phoneNumber = '';
        $this->mnoProvider = '';
        $this->paymentReference = '';
        $this->paymentNotes = '';
        $this->paymentErrors = [];
        $this->selectedExpense = null;
    }
    
    private function loadBankAccounts()
    {
        // Load organization bank accounts for disbursement
        $this->availableBankAccounts = \App\Models\Account::where('major_category_code', 1000)
            ->where(function($query) {
                $query->where('account_name', 'LIKE', '%Bank%')
                      ->orWhere('account_name', 'LIKE', '%Cash%');
            })
            ->where('status', 'ACTIVE')
            ->get();
    }
    
    public function validatePaymentForm()
    {
        $this->paymentErrors = [];
        
        if (empty($this->paymentMethod)) {
            $this->paymentErrors[] = 'Please select a payment method';
        }
        
        if ($this->paymentMethod === 'bank_transfer') {
            if (empty($this->bankAccount)) {
                $this->paymentErrors[] = 'Please select a bank account';
            }
            if (empty($this->accountHolderName)) {
                $this->paymentErrors[] = 'Please enter account holder name';
            }
        }
        
        if ($this->paymentMethod === 'mobile_money') {
            if (empty($this->phoneNumber)) {
                $this->paymentErrors[] = 'Please enter phone number';
            }
            if (empty($this->mnoProvider)) {
                $this->paymentErrors[] = 'Please select mobile network operator';
            }
        }
        
        if ($this->paymentMethod === 'cash' && empty($this->bankAccount)) {
            $this->paymentErrors[] = 'Please select cash account for disbursement';
        }
        
        return count($this->paymentErrors) === 0;
    }
    
    public function processPayment()
    {
        if (!$this->validatePaymentForm()) {
            return;
        }
        
        $this->isProcessingPayment = true;
        
        try {
            // Prepare payment data
            $paymentData = [
                'payment_method' => $this->paymentMethod,
                'bank_account_id' => $this->bankAccount,
                'account_holder_name' => $this->accountHolderName,
                'phone_number' => $this->phoneNumber,
                'mno_provider' => $this->mnoProvider,
                'payment_notes' => $this->paymentNotes,
            ];
            
            // Import the payment service
            $paymentService = new \App\Services\ExpensePaymentService();
            
            // Process the payment with additional data
            $result = $paymentService->processPaymentWithDetails($this->selectedExpense->id, $paymentData);
            
            if ($result['success']) {
                session()->flash('success', $result['message'] . ' Reference: ' . $result['payment_reference']);
                $this->closePaymentModal();
                $this->emit('refreshExpenses'); // Refresh the table
            } else {
                $this->paymentErrors[] = $result['message'];
            }
        } catch (\Exception $e) {
            $this->paymentErrors[] = 'Payment processing failed: ' . $e->getMessage();
        } finally {
            $this->isProcessingPayment = false;
        }
    }
    
    public function viewDetails($id)
    {
        $this->emit('viewExpenseDetails', $id);
    }
    
    public function exportToExcel()
    {
        // Implementation for Excel export
        session()->flash('info', 'Export functionality will be implemented soon.');
    }
    
    public function render()
    {
        $expenses = ExpensesModel::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('description', 'like', '%' . $this->search . '%')
                      ->orWhere('payment_reference', 'like', '%' . $this->search . '%')
                      ->orWhereHas('account', function ($q2) {
                          $q2->where('account_name', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->statusFilter, function ($query) {
                $query->where('status', $this->statusFilter);
            })
            ->with(['account', 'user']) // Load basic relationships
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
            
        // Load approvals manually for each expense
        $expenses->each(function($expense) {
            $expense->approval = \App\Models\Approval::where('process_code', 'EXPENSE_REG')
                ->where('process_id', $expense->id)
                ->first();
        });
            
        $totalExpenses = ExpensesModel::count();
        $totalAmount = ExpensesModel::sum('amount');
        
        // Count based on approval status for pending and approved
        $pendingCount = ExpensesModel::whereHas('approval', function($q) {
            $q->where('approval_status', 'PENDING');
        })->count();
        
        // Count expenses that have been approved through approval workflow but not yet paid
        $approvedCount = ExpensesModel::whereHas('approval', function($q) {
            $q->where('approval_status', 'APPROVED');
        })->where('status', '!=', 'PAID')->count();
        
        $paidCount = ExpensesModel::where('status', 'PAID')->count();
        
        return view('livewire.expenses.expenses-table', [
            'expenses' => $expenses,
            'totalExpenses' => $totalExpenses,
            'totalAmount' => $totalAmount,
            'pendingCount' => $pendingCount,
            'approvedCount' => $approvedCount,
            'paidCount' => $paidCount
        ]);
    }
}