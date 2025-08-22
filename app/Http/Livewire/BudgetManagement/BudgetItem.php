<?php

namespace App\Http\Livewire\BudgetManagement;

use App\Models\approvals;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use App\Models\BudgetManagement;
use Livewire\WithPagination;

class BudgetItem extends Component
{
    use WithPagination;

    // Form properties
    public $new_budget_item = false;
    public $annual_budget;
    public $monthly_allocation;
    public $start_date;
    public $end_date;
    public $notes;
    public $budget_name;
    public $expense_account_id;

    // Table properties
    public $search = '';
    public $statusFilter = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Validation rules
    protected $rules = [
        'budget_name' => 'required|string|max:40',
        'annual_budget' => 'required|numeric|min:0',
        'start_date' => 'required|date',
        'end_date' => 'required|date|after:start_date',
        'notes' => 'nullable|string|max:1000',
        'expense_account_id' => 'required|exists:accounts,id',
    ];

    protected $messages = [
        'budget_name.required' => 'Budget name is required.',
        'budget_name.max' => 'Budget name cannot exceed 40 characters.',
        'annual_budget.required' => 'Annual budget amount is required.',
        'annual_budget.numeric' => 'Annual budget must be a valid number.',
        'annual_budget.min' => 'Annual budget cannot be negative.',
        'start_date.required' => 'Budget start date is required.',
        'end_date.required' => 'Budget end date is required.',
        'end_date.after' => 'End date must be after start date.',
        'expense_account_id.required' => 'Please select an expense account.',
        'expense_account_id.exists' => 'Please select a valid expense account.',
    ];

    public function mount()
    {
        $this->resetPage();
    }

    public function menuItemClick()
    {
        Log::channel('budget_management')->info('New budget item modal opened', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'timestamp' => now()
        ]);

        $this->new_budget_item = true;
        $this->resetBudgetData();
        
        Log::channel('budget_management')->info('Budget form data reset', [
            'user_id' => auth()->id(),
            'modal_opened' => $this->new_budget_item
        ]);
    }

    public function save()
    {
        // Log the start of budget creation process
        Log::channel('budget_management')->info('Budget item creation started', [
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'budget_name' => $this->budget_name,
            'expense_account_id' => $this->expense_account_id,
            'annual_budget' => $this->annual_budget,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'timestamp' => now()
        ]);

        try {
            // Validate the input data
            $this->validate();
            Log::channel('budget_management')->info('Budget item validation passed', [
                'user_id' => auth()->id(),
                'budget_name' => $this->budget_name
            ]);

            // Get expense account details for logging
            $expenseAccount = DB::table('accounts')
                ->where('id', $this->expense_account_id)
                ->select('account_name', 'account_number')
                ->first();

            Log::channel('budget_management')->info('Expense account details retrieved', [
                'account_id' => $this->expense_account_id,
                'account_name' => $expenseAccount->account_name ?? 'Unknown',
                'account_number' => $expenseAccount->account_number ?? 'Unknown'
            ]);

            // Create the budget item
            $budgetData = [
                'revenue' => $this->annual_budget ?? 0,
                'capital_expenditure' => $this->monthly_allocation ?? 0,
                'budget_name' => $this->budget_name,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'approval_status' => "PENDING",
                'notes' => $this->notes,
                'status' => "PENDING",
                'expense_account_id' => $this->expense_account_id,
            ];

            Log::channel('budget_management')->info('Creating budget item with data', [
                'budget_data' => $budgetData,
                'user_id' => auth()->id()
            ]);

            $budgetItem = BudgetManagement::create($budgetData);
            $id = $budgetItem->id;

            Log::channel('budget_management')->info('Budget item created successfully', [
                'budget_id' => $id,
                'budget_name' => $this->budget_name,
                'annual_budget' => $this->annual_budget,
                'monthly_allocation' => $this->monthly_allocation,
                'user_id' => auth()->id()
            ]);

            // Create approval request
            try {
                $approval = new approvals();
                $approvalMessage = auth()->user()->name . ' has created new budget: ' . $this->budget_name;
                $approval->sendApproval($id, 'BUDGET_CREATE', $approvalMessage, 'has created new budget', '102', '');
                
                Log::channel('budget_management')->info('Approval request created successfully', [
                    'budget_id' => $id,
                    'approval_type' => 'BUDGET_CREATE',
                    'approval_message' => $approvalMessage,
                    'user_id' => auth()->id()
                ]);
            } catch (\Exception $approvalException) {
                Log::channel('budget_management')->error('Failed to create approval request', [
                    'budget_id' => $id,
                    'error' => $approvalException->getMessage(),
                    'error_trace' => $approvalException->getTraceAsString(),
                    'user_id' => auth()->id()
                ]);
                
                // Continue with the process even if approval fails
                session()->flash('warning', 'Budget item created but approval request failed. Please contact administrator.');
            }
            
            // Log successful completion
            Log::channel('budget_management')->info('Budget item creation process completed successfully', [
                'budget_id' => $id,
                'budget_name' => $this->budget_name,
                'user_id' => auth()->id(),
                'total_process_time' => now()->diffInMilliseconds(now()->subSeconds(1))
            ]);

            session()->flash('message', 'Budget item created successfully and sent for approval.');
            $this->resetBudgetData();
            $this->new_budget_item = false;
            $this->resetPage();

        } catch (\Illuminate\Validation\ValidationException $validationException) {
            Log::channel('budget_management')->error('Budget item validation failed', [
                'user_id' => auth()->id(),
                'validation_errors' => $validationException->errors(),
                'input_data' => [
                    'budget_name' => $this->budget_name,
                    'annual_budget' => $this->annual_budget,
                    'start_date' => $this->start_date,
                    'end_date' => $this->end_date,
                    'expense_account_id' => $this->expense_account_id
                ]
            ]);
            
            session()->flash('error', 'Validation failed. Please check your input and try again.');
            throw $validationException;

        } catch (\Exception $e) {
            Log::channel('budget_management')->error('Budget item creation failed', [
                'user_id' => auth()->id(),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString(),
                'input_data' => [
                    'budget_name' => $this->budget_name ?? 'null',
                    'annual_budget' => $this->annual_budget ?? 'null',
                    'start_date' => $this->start_date ?? 'null',
                    'end_date' => $this->end_date ?? 'null',
                    'expense_account_id' => $this->expense_account_id ?? 'null'
                ]
            ]);
            
            session()->flash('error', 'Failed to create budget item: ' . $e->getMessage());
        }
    }

    public function resetBudgetData()
    {
        Log::channel('budget_management')->info('Budget form data reset', [
            'user_id' => auth()->id(),
            'previous_data' => [
                'annual_budget' => $this->annual_budget,
                'monthly_allocation' => $this->monthly_allocation,
                'start_date' => $this->start_date,
                'end_date' => $this->end_date,
                'budget_name' => $this->budget_name,
                'expense_account_id' => $this->expense_account_id
            ],
            'timestamp' => now()
        ]);

        $this->annual_budget = null;
        $this->monthly_allocation = null;
        $this->start_date = null;
        $this->end_date = null;
        $this->notes = null;
        $this->budget_name = null;
        $this->expense_account_id = null;
    }

    // Table methods
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function updatedExpenseAccountId()
    {
        Log::channel('budget_management')->info('Expense account selection changed', [
            'user_id' => auth()->id(),
            'expense_account_id' => $this->expense_account_id,
            'timestamp' => now()
        ]);

        if ($this->expense_account_id) {
            try {
                $account = DB::table('accounts')
                    ->where('id', $this->expense_account_id)
                    ->select('account_name', 'account_number')
                    ->first();
                
                if ($account) {
                    $this->budget_name = $account->account_name;
                    
                    Log::channel('budget_management')->info('Budget name automatically set from expense account', [
                        'user_id' => auth()->id(),
                        'expense_account_id' => $this->expense_account_id,
                        'account_name' => $account->account_name,
                        'account_number' => $account->account_number ?? 'Unknown',
                        'budget_name_set' => $this->budget_name
                    ]);
                } else {
                    Log::channel('budget_management')->warning('Expense account not found', [
                        'user_id' => auth()->id(),
                        'expense_account_id' => $this->expense_account_id
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('budget_management')->error('Failed to retrieve expense account details', [
                    'user_id' => auth()->id(),
                    'expense_account_id' => $this->expense_account_id,
                    'error_message' => $e->getMessage(),
                    'error_trace' => $e->getTraceAsString()
                ]);
            }
        } else {
            $this->budget_name = null;
            Log::channel('budget_management')->info('Budget name cleared - no expense account selected', [
                'user_id' => auth()->id()
            ]);
        }
    }

    public function updatedAnnualBudget()
    {
        Log::channel('budget_management')->info('Annual budget amount changed', [
            'user_id' => auth()->id(),
            'annual_budget' => $this->annual_budget,
            'timestamp' => now()
        ]);

        if ($this->annual_budget && is_numeric($this->annual_budget)) {
            $this->monthly_allocation = round($this->annual_budget / 12, 2);
            
            Log::channel('budget_management')->info('Monthly allocation calculated', [
                'user_id' => auth()->id(),
                'annual_budget' => $this->annual_budget,
                'monthly_allocation' => $this->monthly_allocation,
                'calculation' => $this->annual_budget . ' ÷ 12 = ' . $this->monthly_allocation
            ]);
        } else {
            $this->monthly_allocation = null;
            Log::channel('budget_management')->info('Monthly allocation cleared - invalid annual budget', [
                'user_id' => auth()->id(),
                'annual_budget' => $this->annual_budget
            ]);
        }
    }

    public function exportToExcel()
    {
        // Implementation for Excel export
        session()->flash('message', 'Export functionality will be implemented soon.');
    }

    // CRUD methods
    public function viewItem($id)
    {
        $this->emit('viewBudgetItem', $id);
    }

    public function editItem($id)
    {
        $this->emit('editBudgetItem', $id);
    }

    public function approveItem($id)
    {
        Log::channel('budget_management')->info('Budget item approval started', [
            'budget_id' => $id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'timestamp' => now()
        ]);

        try {
            $budget = BudgetManagement::findOrFail($id);
            
            Log::channel('budget_management')->info('Budget item found for approval', [
                'budget_id' => $id,
                'budget_name' => $budget->budget_name,
                'current_status' => $budget->status,
                'current_approval_status' => $budget->approval_status,
                'user_id' => auth()->id()
            ]);

            $budget->update([
                'approval_status' => 'APPROVED',
                'status' => 'ACTIVE'
            ]);
            
            Log::channel('budget_management')->info('Budget item approved successfully', [
                'budget_id' => $id,
                'budget_name' => $budget->budget_name,
                'approved_by' => auth()->id(),
                'approved_by_name' => auth()->user()->name,
                'timestamp' => now()
            ]);
            
            session()->flash('message', 'Budget item approved successfully.');
        } catch (\Exception $e) {
            Log::channel('budget_management')->error('Budget item approval failed', [
                'budget_id' => $id,
                'user_id' => auth()->id(),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Failed to approve budget item: ' . $e->getMessage());
        }
    }

    public function rejectItem($id)
    {
        Log::channel('budget_management')->info('Budget item rejection started', [
            'budget_id' => $id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'timestamp' => now()
        ]);

        try {
            $budget = BudgetManagement::findOrFail($id);
            
            Log::channel('budget_management')->info('Budget item found for rejection', [
                'budget_id' => $id,
                'budget_name' => $budget->budget_name,
                'current_status' => $budget->status,
                'current_approval_status' => $budget->approval_status,
                'user_id' => auth()->id()
            ]);

            $budget->update([
                'approval_status' => 'REJECTED',
                'status' => 'INACTIVE'
            ]);
            
            Log::channel('budget_management')->info('Budget item rejected successfully', [
                'budget_id' => $id,
                'budget_name' => $budget->budget_name,
                'rejected_by' => auth()->id(),
                'rejected_by_name' => auth()->user()->name,
                'timestamp' => now()
            ]);
            
            session()->flash('message', 'Budget item rejected successfully.');
        } catch (\Exception $e) {
            Log::channel('budget_management')->error('Budget item rejection failed', [
                'budget_id' => $id,
                'user_id' => auth()->id(),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Failed to reject budget item: ' . $e->getMessage());
        }
    }

    public function deleteItem($id)
    {
        Log::channel('budget_management')->info('Budget item deletion started', [
            'budget_id' => $id,
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name,
            'timestamp' => now()
        ]);

        try {
            $budget = BudgetManagement::findOrFail($id);
            
            Log::channel('budget_management')->info('Budget item found for deletion', [
                'budget_id' => $id,
                'budget_name' => $budget->budget_name,
                'budget_status' => $budget->status,
                'budget_approval_status' => $budget->approval_status,
                'annual_budget' => $budget->revenue,
                'monthly_allocation' => $budget->capital_expenditure,
                'expense_account_id' => $budget->expense_account_id,
                'user_id' => auth()->id()
            ]);

            $budget->delete();
            
            Log::channel('budget_management')->info('Budget item deleted successfully', [
                'budget_id' => $id,
                'budget_name' => $budget->budget_name,
                'deleted_by' => auth()->id(),
                'deleted_by_name' => auth()->user()->name,
                'timestamp' => now()
            ]);
            
            session()->flash('message', 'Budget item deleted successfully.');
        } catch (\Exception $e) {
            Log::channel('budget_management')->error('Budget item deletion failed', [
                'budget_id' => $id,
                'user_id' => auth()->id(),
                'error_message' => $e->getMessage(),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'error_trace' => $e->getTraceAsString()
            ]);
            
            session()->flash('error', 'Failed to delete budget item: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $query = BudgetManagement::query();

        // Apply search filter
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('budget_name', 'like', '%' . $this->search . '%')
                  ->orWhere('notes', 'like', '%' . $this->search . '%');
            });
        }

        // Apply status filter
        if (!empty($this->statusFilter)) {
            $query->where('approval_status', $this->statusFilter);
        }

        // Apply sorting
        $query->orderBy($this->sortField, $this->sortDirection);

        $budgetItems = $query->paginate($this->perPage);

        // Get expense accounts for dropdown
        $expenseAccounts = DB::table('accounts')
            ->where('major_category_code', '5000')
            ->select('id', 'account_name', 'account_number')
            ->orderBy('account_name')
            ->get();

        return view('livewire.budget-management.budget-item', [
            'budgetItems' => $budgetItems,
            'expenseAccounts' => $expenseAccounts
        ]);
    }
}
