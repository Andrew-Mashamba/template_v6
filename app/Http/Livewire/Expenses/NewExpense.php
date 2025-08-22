<?php

namespace App\Http\Livewire\Expenses;

use Livewire\Component;
use App\Models\Account;
use App\Models\Approvals;
use App\Services\BudgetCheckingService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class NewExpense extends Component
{
    public $expense_type_id = null;
    public $payment_type = null;
    public $amount = null;
    public $description = null;
    public $expense_types = [];
    
    // Budget checking properties
    public $showBudgetModal = false;
    public $budgetCheckResult = null;
    public $budgetResolution = 'NONE';
    public $budgetNotes = '';
    public $expense_month = null;
    
    // Payment type constants
    const PAYMENT_TYPES = [
        'money_transfer' => 'Money Transfer',
        'bill_payment' => 'Bill Payment',
        'luku_payment' => 'LUKU Payment',
        'gepg_payment' => 'GEPG Payment'
    ];

    public function mount()
    {
        // Fetch expense types from accounts where major_category_code = 5000 and account_level = 3
        $this->expense_types = Account::where('major_category_code', 5000)
            ->where('account_level', 3)
            ->orderBy('account_name')
            ->get();
            
        // Set default month to current month
        $this->expense_month = now()->format('Y-m');
    }

    public function rules()
    {
        return [
            'expense_type_id' => 'required|exists:accounts,id',
            'payment_type' => 'required|in:' . implode(',', array_keys(self::PAYMENT_TYPES)),
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:500',
            'expense_month' => 'nullable|date_format:Y-m'
        ];
    }

    public function messages()
    {
        return [
            'expense_type_id.required' => 'Please select an expense account.',
            'payment_type.required' => 'Please select a payment type.',
            'amount.required' => 'Please enter the expense amount.',
            'amount.min' => 'Amount must be greater than 0.',
            'description.required' => 'Please provide a description for this expense.',
            'description.max' => 'Description cannot exceed 500 characters.',
            'expense_month.date_format' => 'Please select a valid month.'
        ];
    }

    public function checkBudget()
    {
        $this->validate([
            'expense_type_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric|min:0',
        ]);

        try {
            $budgetService = new BudgetCheckingService();
            $this->budgetCheckResult = $budgetService->checkBudgetForExpense(
                $this->expense_type_id, 
                $this->amount, 
                $this->expense_month
            );

            // Log the budget check
            $budgetService->logBudgetCheck(
                $this->expense_type_id, 
                $this->amount, 
                $this->budgetCheckResult
            );

            // If no budget exists, show a warning but allow submission
            if (!$this->budgetCheckResult['has_budget']) {
                session()->flash('warning', 'No budget found for this expense account. The expense will be submitted without budget validation.');
                $this->submitExpense();
                return;
            }

            // If budget would be exceeded, show modal
            if ($this->budgetCheckResult['would_exceed']) {
                $this->showBudgetModal = true;
            } else {
                // If within budget, proceed with submission
                $this->submitExpense();
            }

        } catch (\Exception $e) {
            Log::channel('budget_management')->error('Budget check failed', [
                'account_id' => $this->expense_type_id,
                'amount' => $this->amount,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            session()->flash('error', 'Failed to check budget: ' . $e->getMessage());
        }
    }

    public function submitExpense()
    {
        $this->validate();

        try {
            // Prepare expense data with budget information
            $expenseData = [
                'account_id' => $this->expense_type_id,
                'amount' => $this->amount,
                'description' => $this->description,
                'payment_type' => $this->payment_type,
                'user_id' => Auth::id(),
                'status' => 'PENDING_APPROVAL',
                'expense_month' => $this->expense_month ? $this->expense_month . '-01' : now()->format('Y-m-d')
            ];

            // Add budget information if available
            if ($this->budgetCheckResult && $this->budgetCheckResult['has_budget']) {
                $expenseData = array_merge($expenseData, [
                    'budget_item_id' => $this->budgetCheckResult['budget_item_id'],
                    'monthly_budget_amount' => $this->budgetCheckResult['monthly_budget'],
                    'monthly_spent_amount' => $this->budgetCheckResult['monthly_spent'],
                    'budget_utilization_percentage' => $this->budgetCheckResult['new_utilization_percentage'],
                    'budget_status' => $this->budgetCheckResult['budget_status'],
                    'budget_resolution' => $this->budgetResolution,
                    'budget_notes' => $this->budgetNotes
                ]);
            }

            // Create expense
            $expense = \App\Models\Expense::create($expenseData);

            // Create approval request
            $approvalData = [
                'process_name' => 'new_expense_request',
                'process_description' => Auth::user()->name . ' has registered an expense: ' . $this->description,
                'approval_process_description' => $this->getApprovalDescription(),
                'process_code' => 'EXPENSE_REG',
                'process_id' => $expense->id,
                'process_status' => 'PENDING',
                'user_id' => Auth::id(),
                'team_id' => null,
                'approver_id' => null,
                'approval_status' => 'PENDING',
                'edit_package' => null
            ];
            
            $approval = Approvals::create($approvalData);

            // Update expense with approval ID
            $expense->update(['approval_id' => $approval->id]);

            // Reset form
            $this->expense_type_id = null;
            $this->payment_type = null;
            $this->amount = null;
            $this->description = null;
            $this->budgetResolution = 'NONE';
            $this->budgetNotes = '';
            $this->expense_month = now()->format('Y-m');
            $this->showBudgetModal = false;
            $this->budgetCheckResult = null;

            session()->flash('success', 'Expense submitted for approval successfully!');
        } catch (\Exception $e) {
            Log::channel('budget_management')->error('Expense submission failed', [
                'account_id' => $this->expense_type_id,
                'amount' => $this->amount,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);
            
            session()->flash('error', 'Failed to submit expense: ' . $e->getMessage());
        }
    }

    private function getApprovalDescription()
    {
        if (!$this->budgetCheckResult || !$this->budgetCheckResult['would_exceed']) {
            return 'Expense approval required';
        }

        $description = 'Expense approval required - ';
        
        switch ($this->budgetResolution) {
            case 'USE_PREVIOUS_MONTHS':
                $description .= 'Using previous months\' unused budget';
                break;
            case 'REQUEST_ADDITIONAL_FUNDS':
                $description .= 'Requesting additional funds';
                break;
            case 'APPROVED_OVERRIDE':
                $description .= 'Budget override approved';
                break;
            default:
                $description .= 'Budget exceeded - manual review required';
        }

        return $description;
    }

    public function proceedWithBudgetOverride()
    {
        $this->budgetResolution = 'APPROVED_OVERRIDE';
        $this->submitExpense();
    }

    public function usePreviousMonthsBudget()
    {
        $this->budgetResolution = 'USE_PREVIOUS_MONTHS';
        $this->submitExpense();
    }

    public function requestAdditionalFunds()
    {
        $this->budgetResolution = 'REQUEST_ADDITIONAL_FUNDS';
        $this->submitExpense();
    }

    public function cancelExpense()
    {
        $this->showBudgetModal = false;
        $this->budgetCheckResult = null;
        $this->budgetResolution = 'NONE';
        $this->budgetNotes = '';
    }

    public function resetForm()
    {
        $this->expense_type_id = null;
        $this->payment_type = null;
        $this->amount = null;
        $this->description = null;
        $this->budgetResolution = 'NONE';
        $this->budgetNotes = '';
        $this->expense_month = now()->format('Y-m');
        $this->showBudgetModal = false;
        $this->budgetCheckResult = null;
    }

    public function getSelectedAccountName()
    {
        if (!$this->expense_type_id) {
            return null;
        }
        
        $account = $this->expense_types->firstWhere('id', $this->expense_type_id);
        return $account ? $account->account_name : null;
    }

    public function getFormattedAmount()
    {
        return $this->amount ? number_format($this->amount, 2) : '0.00';
    }

    public function getFormattedMonth()
    {
        if (!$this->expense_month) {
            return 'Current Month';
        }
        
        try {
            return \Carbon\Carbon::parse($this->expense_month . '-01')->format('F Y');
        } catch (\Exception $e) {
            return 'Current Month';
        }
    }

    public function render()
    {
        return view('livewire.expenses.new-expense', [
            'payment_types' => self::PAYMENT_TYPES
        ]);
    }
}
