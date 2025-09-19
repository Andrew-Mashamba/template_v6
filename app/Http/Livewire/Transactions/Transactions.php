<?php

namespace App\Http\Livewire\Transactions;

use Livewire\Component;
use App\Models\Transaction;
use App\Models\AccountsModel;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Carbon\Carbon;
use App\Traits\Livewire\WithModulePermissions;

class Transactions extends Component
{
    use WithPagination, WithModulePermissions;

    // Navigation
    public $selectedMenuItem = 1;

    // Search and Filter Properties
    public $search = '';
    public $status = '';
    public $type = '';
    public $category = '';
    public $externalSystem = '';
    public $reconciliationStatus = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $amountFrom = '';
    public $amountTo = '';
    public $accountId = '';
    public $isManual = '';
    public $requiresApproval = '';

    // Sorting
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 25;

    // Transaction Details
    public $selectedTransaction = null;
    public $showTransactionModal = false;
    public $showReversalModal = false;
    public $showRetryModal = false;
    public $showReconciliationModal = false;

    // Modal Properties
    public $reversalReason = '';
    public $retryReason = '';
    public $reconciliationNotes = '';

    // Statistics
    public $totalTransactions = 0;
    public $totalAmount = 0;
    public $pendingCount = 0;
    public $completedCount = 0;
    public $failedCount = 0;
    public $unreconciledCount = 0;

    protected $listeners = ['refreshTransactions' => '$refresh'];

    public function mount()
    {
        // Initialize the permission system for this module
        $this->initializeWithModulePermissions();
        $this->loadStatistics();
    }

    public function selectedMenu($menuId)
    {
        // Check permissions based on the section being accessed
        $requiredPermission = $this->getRequiredPermissionForSection($menuId);
        $permissionKey = 'can' . ucfirst($requiredPermission);
        
        if (!($this->permissions[$permissionKey] ?? false)) {
            session()->flash('error', 'You do not have permission to access this transactions section');
            return;
        }
        
        $this->selectedMenuItem = $menuId;
        $this->resetPage();
    }

    public function loadStatistics()
    {
        $this->totalTransactions = Transaction::count();
        $this->totalAmount = Transaction::where('status', 'COMPLETED')->sum('amount');
        $this->pendingCount = Transaction::where('status', 'PENDING')->count();
        $this->completedCount = Transaction::where('status', 'COMPLETED')->count();
        $this->failedCount = Transaction::where('status', 'FAILED')->count();
        $this->unreconciledCount = Transaction::where('reconciliation_status', 'UNRECONCILED')->count();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingType()
    {
        $this->resetPage();
    }

    public function updatingCategory()
    {
        $this->resetPage();
    }

    public function updatingExternalSystem()
    {
        $this->resetPage();
    }

    public function updatingReconciliationStatus()
    {
        $this->resetPage();
    }

    public function updatingDateFrom()
    {
        $this->resetPage();
    }

    public function updatingDateTo()
    {
        $this->resetPage();
    }

    public function updatingAmountFrom()
    {
        $this->resetPage();
    }

    public function updatingAmountTo()
    {
        $this->resetPage();
    }

    public function updatingAccountId()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function viewTransaction($transactionId)
    {
        // Check permission to view transactions
        if (!($this->permissions['canView'] ?? false)) {
            session()->flash('error', 'You do not have permission to view transaction details');
            return;
        }
        
        $this->selectedTransaction = Transaction::with([
            'account',
            'auditLogs',
            'retryLogs',
            'reconciliations',
            'originalTransaction',
            'reversalTransaction'
        ])->find($transactionId);
        $this->showTransactionModal = true;
    }

    public function reverseTransaction($transactionId)
    {
        // Check permission to reverse transactions
        if (!($this->permissions['canManage'] ?? false)) {
            session()->flash('error', 'You do not have permission to reverse transactions');
            return;
        }
        
        $this->selectedTransaction = Transaction::find($transactionId);
        $this->showReversalModal = true;
    }

    public function confirmReversal()
    {
        // Check permission to reverse transactions
        if (!($this->permissions['canManage'] ?? false)) {
            session()->flash('error', 'You do not have permission to reverse transactions');
            return;
        }
        
        $this->validate([
            'reversalReason' => 'required|string|min:10'
        ]);

        try {
            $this->selectedTransaction->reverse($this->reversalReason, auth()->id());
            $this->showReversalModal = false;
            $this->reversalReason = '';
            $this->loadStatistics();
            session()->flash('message', 'Transaction reversed successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reverse transaction: ' . $e->getMessage());
        }
    }

    public function retryTransaction($transactionId)
    {
        // Check permission to retry transactions
        if (!($this->permissions['canManage'] ?? false)) {
            session()->flash('error', 'You do not have permission to retry transactions');
            return;
        }
        
        $this->selectedTransaction = Transaction::find($transactionId);
        $this->showRetryModal = true;
    }

    public function confirmRetry()
    {
        // Check permission to retry transactions
        if (!($this->permissions['canManage'] ?? false)) {
            session()->flash('error', 'You do not have permission to retry transactions');
            return;
        }
        
        $this->validate([
            'retryReason' => 'required|string|min:10'
        ]);

        try {
            $this->selectedTransaction->markForRetry($this->retryReason);
            $this->showRetryModal = false;
            $this->retryReason = '';
            $this->loadStatistics();
            session()->flash('message', 'Transaction marked for retry');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to mark transaction for retry: ' . $e->getMessage());
        }
    }

    public function reconcileTransaction($transactionId)
    {
        // Check permission to reconcile transactions
        if (!($this->permissions['canManage'] ?? false)) {
            session()->flash('error', 'You do not have permission to reconcile transactions');
            return;
        }
        
        $this->selectedTransaction = Transaction::find($transactionId);
        $this->showReconciliationModal = true;
    }

    public function confirmReconciliation()
    {
        // Check permission to reconcile transactions
        if (!($this->permissions['canManage'] ?? false)) {
            session()->flash('error', 'You do not have permission to reconcile transactions');
            return;
        }
        
        try {
            $this->selectedTransaction->reconcile([
                'notes' => $this->reconciliationNotes,
                'reconciled_by' => auth()->id()
            ]);
            $this->showReconciliationModal = false;
            $this->reconciliationNotes = '';
            $this->loadStatistics();
            session()->flash('message', 'Transaction reconciled successfully');
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to reconcile transaction: ' . $e->getMessage());
        }
    }

    public function exportTransactions()
    {
        // Check permission to export transactions
        if (!($this->permissions['canExport'] ?? false)) {
            session()->flash('error', 'You do not have permission to export transactions');
            return;
        }
        
        // Implementation for CSV/Excel export
        session()->flash('message', 'Export feature coming soon');
    }

    public function render()
    {
        $query = Transaction::with(['account'])
            ->when($this->search, function($query) {
                $query->where(function($q) {
                    $q->where('reference', 'like', '%' . $this->search . '%')
                      ->orWhere('external_reference', 'like', '%' . $this->search . '%')
                      ->orWhere('correlation_id', 'like', '%' . $this->search . '%')
                      ->orWhere('narration', 'like', '%' . $this->search . '%')
                      ->orWhere('payer_name', 'like', '%' . $this->search . '%')
                      ->orWhere('payer_phone', 'like', '%' . $this->search . '%')
                      ->orWhereHas('account', function($q) {
                          $q->where('account_number', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->when($this->status, function($query) {
                $query->where('status', $this->status);
            })
            ->when($this->type, function($query) {
                $query->where('type', $this->type);
            })
            ->when($this->category, function($query) {
                $query->where('transaction_category', $this->category);
            })
            ->when($this->externalSystem, function($query) {
                $query->where('external_system', $this->externalSystem);
            })
            ->when($this->reconciliationStatus, function($query) {
                $query->where('reconciliation_status', $this->reconciliationStatus);
            })
            ->when($this->dateFrom, function($query) {
                $query->where('created_at', '>=', $this->dateFrom);
            })
            ->when($this->dateTo, function($query) {
                $query->where('created_at', '<=', $this->dateTo . ' 23:59:59');
            })
            ->when($this->amountFrom, function($query) {
                $query->where('amount', '>=', $this->amountFrom);
            })
            ->when($this->amountTo, function($query) {
                $query->where('amount', '<=', $this->amountTo);
            })
            ->when($this->accountId, function($query) {
                $query->where('account_id', $this->accountId);
            })
            ->when($this->isManual !== '', function($query) {
                $query->where('is_manual', $this->isManual);
            })
            ->when($this->requiresApproval !== '', function($query) {
                $query->where('requires_approval', $this->requiresApproval);
            })
            ->orderBy($this->sortField, $this->sortDirection);

        $transactions = $query->paginate($this->perPage);

        // Get filter options
        $accounts = AccountsModel::select('id', 'account_number', 'account_name')->get();
        $statuses = Transaction::distinct()->pluck('status')->filter();
        $types = Transaction::distinct()->pluck('type')->filter();
        $categories = Transaction::distinct()->pluck('transaction_category')->filter();
        $externalSystems = Transaction::distinct()->pluck('external_system')->filter();
        $reconciliationStatuses = Transaction::distinct()->pluck('reconciliation_status')->filter();

        return view('livewire.transactions.transactions', array_merge(
            $this->permissions,
            [
                'transactions' => $transactions,
                'accounts' => $accounts,
                'statuses' => $statuses,
                'types' => $types,
                'categories' => $categories,
                'externalSystems' => $externalSystems,
                'reconciliationStatuses' => $reconciliationStatuses,
                'permissions' => $this->permissions
            ]
        ));
    }

    /**
     * Get the required permission for a specific transactions section
     */
    private function getRequiredPermissionForSection($sectionId)
    {
        $sectionPermissionMap = [
            1 => 'view',      // Dashboard Overview
            2 => 'create',    // New Transaction
            3 => 'view',      // Transaction List
            4 => 'view',      // Pending
            5 => 'manage',    // Reconciliation
            6 => 'view',      // Reports
        ];
        
        return $sectionPermissionMap[$sectionId] ?? 'view';
    }

    /**
     * Override to specify the module name for permissions
     * 
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'transactions';
    }
}
