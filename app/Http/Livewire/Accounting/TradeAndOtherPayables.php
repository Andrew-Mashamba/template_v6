<?php

namespace App\Http\Livewire\Accounting;

use App\Models\AccountsModel;
use App\Models\general_ledger;
use App\Services\BalanceSheetItemIntegrationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use Carbon\Carbon;

class TradeAndOtherPayables extends Component
{
    use WithPagination, WithFileUploads;

    // View Control
    public $activeTab = 'overview';
    public $showCreateModal = false;
    public $showPaymentModal = false;
    public $showApprovalModal = false;
    public $editMode = false;
    
    // Search and Filters
    public $search = '';
    public $statusFilter = 'all';
    public $priorityFilter = 'all';
    public $vendorFilter = '';
    public $dateFrom;
    public $dateTo;
    
    // Payable Form Data
    public $payableId;
    public $vendor_id;
    public $vendor_name = '';
    public $vendor_email = '';
    public $vendor_phone = '';
    public $vendor_address = '';
    public $vendor_tax_id = '';
    public $vendor_bank_name = '';
    public $vendor_bank_account_number = '';
    public $vendor_bank_branch = '';
    public $vendor_swift_code = '';
    public $invoice_number = '';
    public $invoice_date;
    public $bill_number = '';
    public $purchase_order_number = '';
    public $amount = 0;
    public $vat_amount = 0;
    public $total_amount = 0;
    public $currency = 'TZS';
    public $due_date;
    public $payment_terms = 30;
    public $description = '';
    public $expense_account_id;
    public $payable_account_id;
    public $bank_account_id;
    public $payment_type = 'cash';
    
    // Account selection for proper flow
    public $parent_account_number; // Parent account to create payable account under
    public $other_account_id; // The other account for double-entry (Expense/Inventory - debit side)
    public $created_payable_account_number; // The created payable account number
    public $notes = '';
    public $priority = 'normal';
    public $approval_status = 'pending';
    
    // Payment Form Data
    public $payment_payable_id;
    public $payment_amount = 0;
    public $payment_date;
    public $payment_method = 'bank_transfer';
    public $payment_reference = '';
    public $payment_account_id;
    public $bank_charges = 0;
    public $early_payment_discount = 0;
    public $withholding_tax = 0;
    public $payment_notes = '';
    
    // Batch Payment
    public $selectedPayables = [];
    public $batchPaymentMode = false;
    public $batchPaymentDate;
    public $batchPaymentAccount;
    
    // File Uploads
    public $invoice_attachment;
    public $purchase_order_attachment;
    public $payment_voucher;
    public $supporting_documents = [];
    
    // Statistics
    public $totalPayables = 0;
    public $totalOverdue = 0;
    public $totalPaid = 0;
    public $totalApproved = 0;
    public $upcomingPayments = 0;
    public $averagePaymentPeriod = 0;
    public $cashFlowProjection = [];
    
    // Collections
    public $vendors = [];
    public $payableAccounts = [];
    public $expenseAccounts = [];
    public $bankAccounts = [];
    public $approvers = [];
    
    protected $rules = [
        'vendor_name' => 'required|min:3',
        'bill_number' => 'required',
        'invoice_date' => 'required|date',
        'amount' => 'required|numeric|min:0',
        'due_date' => 'required|date|after_or_equal:invoice_date',
        'currency' => 'nullable|in:TZS,USD,EUR,GBP',
        'payment_terms' => 'required|integer|min:0',
        'vat_amount' => 'nullable|numeric|min:0',
        'invoice_attachment' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        'parent_account_number' => 'required|string',
        'other_account_id' => 'required|integer',
    ];
    
    protected $listeners = [
        'refreshPayables' => 'loadStatistics',
        'deletePayable' => 'delete',
        'approvePayable' => 'approve',
        'processBatchPayment' => 'processBatch',
    ];
    
    public function mount()
    {
        $this->initializeData();
        $this->loadStatistics();
        $this->invoice_date = now()->format('Y-m-d');
        $this->payment_date = now()->format('Y-m-d');
        $this->batchPaymentDate = now()->format('Y-m-d');
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }
    
    public function initializeData()
    {
        // Load vendors (create table if doesn't exist)
        if (Schema::hasTable('vendors')) {
            $this->vendors = DB::table('vendors')
                ->where('status', 'ACTIVE')
                ->orderBy('organization_name')
                ->get();
        } else {
            $this->vendors = collect();
        }
        
        // Load payable accounts (Liability accounts)
        $this->payableAccounts = AccountsModel::where('account_type', 'LIABILITY')
            ->where(function($query) {
                $query->where('account_name', 'like', '%payable%')
                      ->orWhere('account_name', 'like', '%creditor%');
            })
            ->where('status', 'ACTIVE')
            ->orderBy('account_name')
            ->get();
        
        // Load expense accounts
        $this->expenseAccounts = AccountsModel::where('type', 'expense_accounts')
            ->where('account_level', 3)
            ->where('status', 'ACTIVE')
            ->orderBy('account_name')
            ->get();
        
        // Load bank accounts for payments
        // Check for bank accounts in the accounts table with specific account types
        $this->bankAccounts = AccountsModel::where(function($query) {
                $query->where('account_name', 'LIKE', '%BANK%')
                      ->orWhere('account_name', 'LIKE', '%CASH%')
                      ->orWhere('major_category_code', '1000'); // Asset accounts that could be bank/cash
            })
            ->where('status', 'ACTIVE')
            ->orderBy('account_name')
            ->get();
            
        // Load approvers
        $this->approvers = DB::table('users')
            ->select('users.id', 'users.name')
            ->distinct()
            ->get();
    }
    
    public function loadStatistics()
    {
        // Check if payables table exists
        if (!Schema::hasTable('trade_payables')) {
            $this->totalPayables = 0;
            $this->totalOverdue = 0;
            $this->totalPaid = 0;
            $this->totalApproved = 0;
            $this->upcomingPayments = 0;
            $this->averagePaymentPeriod = 0;
            $this->cashFlowProjection = [];
            return;
        }
        
        $query = DB::table('trade_payables');
        
        // Apply date filters
        if ($this->dateFrom) {
            $query->where('bill_date', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->where('bill_date', '<=', $this->dateTo);
        }
        
        // Calculate totals
        $this->totalPayables = $query->sum('amount') ?? 0;
        
        $this->totalOverdue = DB::table('trade_payables')
            ->where('due_date', '<', now())
            ->where('status', '!=', 'paid')
            ->sum('balance') ?? 0;
        
        $this->totalPaid = DB::table('trade_payables')
            ->where('status', 'paid')
            ->sum('paid_amount') ?? 0;
        
        $this->totalApproved = DB::table('trade_payables')
            ->where('status', '!=', 'paid')
            ->sum('balance') ?? 0;  // No approval_status column in trade_payables
        
        // Calculate upcoming payments (next 7 days)
        $this->upcomingPayments = DB::table('trade_payables')
            ->whereBetween('due_date', [now(), now()->addDays(7)])
            ->where('status', '!=', 'paid')
            ->sum('balance') ?? 0;
        
        // Calculate average payment period (PostgreSQL syntax)
        $avgDays = DB::table('trade_payables')
            ->where('status', 'paid')
            ->whereNotNull('updated_at')
            ->selectRaw("AVG(DATE_PART('day', updated_at - bill_date)) as avg_days")
            ->value('avg_days');
        
        $this->averagePaymentPeriod = round($avgDays ?? 0);
        
        // Calculate cash flow projection
        $this->calculateCashFlowProjection();
    }
    
    public function calculateCashFlowProjection()
    {
        $this->cashFlowProjection = [];
        
        if (!Schema::hasTable('trade_payables')) {
            return;
        }
        
        for ($i = 0; $i < 6; $i++) {
            $startDate = now()->addMonths($i)->startOfMonth();
            $endDate = now()->addMonths($i)->endOfMonth();
            
            $amount = DB::table('trade_payables')
                ->whereBetween('due_date', [$startDate, $endDate])
                ->where('status', '!=', 'paid')
                ->sum('balance') ?? 0;
            
            $this->cashFlowProjection[] = [
                'month' => $startDate->format('M Y'),
                'amount' => $amount,
            ];
        }
    }
    
    public function updatedAmount()
    {
        $this->calculateTotal();
    }
    
    public function updatedVatAmount()
    {
        $this->calculateTotal();
    }
    
    public function calculateTotal()
    {
        $this->total_amount = (float)($this->amount ?? 0) + (float)($this->vat_amount ?? 0);
    }
    
    public function updatedPaymentTerms()
    {
        if ($this->invoice_date && $this->payment_terms) {
            $this->due_date = Carbon::parse($this->invoice_date)
                ->addDays($this->payment_terms)
                ->format('Y-m-d');
        }
    }
    
    public function updatedInvoiceDate()
    {
        $this->updatedPaymentTerms();
    }
    
    public function updatedVendorId()
    {
        if ($this->vendor_id && Schema::hasTable('vendors')) {
            $vendor = DB::table('vendors')->find($this->vendor_id);
            if ($vendor) {
                $this->vendor_name = $vendor->organization_name;
                $this->vendor_email = $vendor->email ?? '';
                $this->vendor_phone = $vendor->phone ?? '';
                $this->vendor_address = $vendor->address ?? '';
                $this->vendor_tax_id = $vendor->tax_id ?? '';
                $this->payment_terms = $vendor->payment_terms ?? 30;
            }
        }
    }
    
    public function openCreateModal()
    {
        $this->reset(['payableId', 'vendor_id', 'vendor_name', 'vendor_email', 
                     'vendor_phone', 'vendor_address', 'vendor_tax_id',
                     'vendor_bank_name', 'vendor_bank_account_number', 
                     'vendor_bank_branch', 'vendor_swift_code',
                     'invoice_number', 'bill_number', 'purchase_order_number',
                     'amount', 'vat_amount', 'total_amount', 'description', 'notes',
                     'expense_account_id', 'payable_account_id', 'bank_account_id',
                     'parent_account_number', 'other_account_id', 'created_payable_account_number']);
        
        $this->editMode = false;
        $this->invoice_date = now()->format('Y-m-d');
        $this->payment_type = 'cash';
        $this->priority = 'normal';
        $this->approval_status = 'pending';
        $this->generateBillNumber();
        $this->showCreateModal = true;
    }
    
    public function generateBillNumber()
    {
        $prefix = 'BILL';
        $year = date('Y');
        $month = date('m');
        
        if (Schema::hasTable('trade_payables')) {
            $lastBill = DB::table('trade_payables')
                ->where('bill_number', 'like', "$prefix-$year$month-%")
                ->orderBy('bill_number', 'desc')
                ->first();
            
            if ($lastBill) {
                $lastNumber = intval(substr($lastBill->bill_number, -4));
                $newNumber = str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
            } else {
                $newNumber = '0001';
            }
        } else {
            $newNumber = '0001';
        }
        
        $this->bill_number = "$prefix-$year$month-$newNumber";
    }
    
    public function save()
    {
        $this->validate();
        
        DB::beginTransaction();
        try {
            // Prepare data - match trade_payables table structure
            $data = [
                'vendor_id' => $this->vendor_id,
                'vendor_name' => $this->vendor_name,
                'vendor_bank_name' => $this->vendor_bank_name,
                'vendor_bank_account_number' => $this->vendor_bank_account_number,
                'vendor_bank_branch' => $this->vendor_bank_branch,
                'vendor_swift_code' => $this->vendor_swift_code,
                'bill_number' => $this->bill_number ?: $this->invoice_number,
                'purchase_order_number' => $this->purchase_order_number,
                'bill_date' => $this->invoice_date,
                'due_date' => $this->due_date,
                'amount' => $this->total_amount,  // total amount
                'paid_amount' => 0,
                'balance' => $this->total_amount,  // initial balance equals total
                'payment_terms' => $this->payment_terms,
                'description' => $this->description,
                'account_number' => null,  // can be set if needed
                'parent_account_number' => $this->parent_account_number,  // Store parent account reference
                'other_account_id' => $this->other_account_id,  // Store expense account reference
                'payable_account_id' => null,  // Will be updated after account creation
                'created_payable_account_number' => null,  // Will be updated after account creation
                'status' => 'pending',
                'created_by' => auth()->id(),
                'updated_by' => auth()->id(),
                'created_at' => now(),
                'updated_at' => now(),
            ];
            
            // Handle file uploads
            if ($this->invoice_attachment) {
                $path = $this->invoice_attachment->store('payables/invoices', 'public');
                $data['invoice_attachment'] = $path;
            }
            
            if ($this->purchase_order_attachment) {
                $path = $this->purchase_order_attachment->store('payables/purchase_orders', 'public');
                $data['purchase_order_attachment'] = $path;
            }
            
            if ($this->editMode && $this->payableId) {
                // Update existing payable
                unset($data['created_at'], $data['created_by']);
                DB::table('trade_payables')
                    ->where('id', $this->payableId)
                    ->update($data);
                
                $message = 'Payable updated successfully!';
            } else {
                // Create new payable
                $payableId = DB::table('trade_payables')->insertGetId($data);
                
                // Use Balance Sheet Integration Service to create accounts and post to GL
                $integrationService = new BalanceSheetItemIntegrationService();
                
                try {
                    $payable = (object) array_merge($data, ['id' => $payableId]);
                    $createdAccount = $integrationService->createTradePayableAccount(
                        $payable,
                        $this->parent_account_number,  // Parent account to create payable account under
                        $this->other_account_id        // The other account for double-entry (Expense/Inventory - debit side)
                    );
                    
                    // Update the trade_payables record with the created payable account ID and account number
                    if ($createdAccount && isset($createdAccount->id)) {
                        DB::table('trade_payables')
                            ->where('id', $payableId)
                            ->update([
                                'payable_account_id' => $createdAccount->id,
                                'created_payable_account_number' => $createdAccount->account_number
                            ]);
                    }
                    
                    Log::info('Trade payable integrated with accounts table', [
                        'payable_id' => $payableId,
                        'vendor' => $this->vendor_name,
                        'amount' => $this->total_amount,
                        'payable_account_id' => $createdAccount->id ?? null,
                        'created_account_number' => $createdAccount->account_number ?? null,
                        'created_account_name' => 'Payable - ' . $this->vendor_name
                    ]);
                } catch (\Exception $e) {
                    Log::error('Failed to integrate payable with accounts table: ' . $e->getMessage());
                }
                
                $message = 'Payable created successfully!';
            }
            
            DB::commit();
            
            $this->showCreateModal = false;
            $this->loadStatistics();
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => $message
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving payable: ' . $e->getMessage());
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Error saving payable: ' . $e->getMessage()
            ]);
        }
    }
    
    private function createGLEntries($payableId, $data)
    {
        $reference = 'PAY-' . $data['invoice_number'];
        $description = 'Bill ' . $data['invoice_number'] . ' - ' . $data['vendor_name'];
        
        // Debit Expense Account
        general_ledger::create([
            'reference_number' => $reference,
            'transaction_type' => 'PAYABLE',
            'transaction_date' => $data['invoice_date'],
            'account_id' => $data['expense_account_id'],
            'debit_amount' => $data['amount'],
            'credit_amount' => 0,
            'description' => $description,
            'created_by' => auth()->id(),
            'status' => 'POSTED',
            'source_id' => $payableId,
            'source_type' => 'payables'
        ]);
        
        // Debit VAT if applicable
        if ($data['vat_amount'] > 0) {
            $vatAccount = AccountsModel::where('account_name', 'like', '%VAT%Input%')
                ->where('account_type', 'ASSET')
                ->first();
            
            if (!$vatAccount) {
                $vatAccount = AccountsModel::where('account_name', 'like', '%VAT%')
                    ->where('account_type', 'ASSET')
                    ->first();
            }
            
            if ($vatAccount) {
                general_ledger::create([
                    'reference_number' => $reference,
                    'transaction_type' => 'PAYABLE',
                    'transaction_date' => $data['invoice_date'],
                    'account_id' => $vatAccount->id,
                    'debit_amount' => $data['vat_amount'],
                    'credit_amount' => 0,
                    'description' => 'Input VAT on ' . $description,
                    'created_by' => auth()->id(),
                    'status' => 'POSTED',
                    'source_id' => $payableId,
                    'source_type' => 'payables'
                ]);
            }
        }
        
        // Credit Accounts Payable
        general_ledger::create([
            'reference_number' => $reference,
            'transaction_type' => 'PAYABLE',
            'transaction_date' => $data['invoice_date'],
            'account_id' => $data['payable_account_id'],
            'debit_amount' => 0,
            'credit_amount' => $data['total_amount'],
            'description' => $description,
            'created_by' => auth()->id(),
            'status' => 'POSTED',
            'source_id' => $payableId,
            'source_type' => 'payables'
        ]);
    }
    
    public function openPaymentModal($payableId)
    {
        $payable = DB::table('trade_payables')->find($payableId);
        
        if ($payable) {
            // Skip approval check as approval_status doesn't exist in trade_payables
            // Check if already paid instead
            if ($payable->status === 'paid') {
                $this->dispatchBrowserEvent('alert', [
                    'type' => 'warning',
                    'message' => 'This payable has already been paid.'
                ]);
                return;
            }
            
            $this->payment_payable_id = $payableId;
            $this->payment_amount = $payable->balance;
            $this->payment_date = now()->format('Y-m-d');
            $this->payment_method = 'bank_transfer';
            
            // Calculate early payment discount if applicable
            $daysEarly = Carbon::parse($payable->due_date)->diffInDays(now(), false);
            if ($daysEarly > 0 && $payable->payment_terms > 0) {
                // 2% discount if paid 10 days early
                if ($daysEarly >= 10) {
                    $this->early_payment_discount = $payable->balance * 0.02;
                }
            }
            
            $this->showPaymentModal = true;
        }
    }
    
    public function processPayment()
    {
        $this->validate([
            'payment_amount' => 'required|numeric|min:0',
            'payment_date' => 'required|date',
            'payment_method' => 'required',
            'payment_account_id' => 'required|exists:accounts,id',
        ]);
        
        DB::beginTransaction();
        try {
            $payable = DB::table('trade_payables')->find($this->payment_payable_id);
            
            if (!$payable) {
                throw new \Exception('Payable not found');
            }
            
            // Get payment source account details
            $sourceAccount = AccountsModel::find($this->payment_account_id);
            if (!$sourceAccount) {
                throw new \Exception('Payment source account not found');
            }
            
            // Check if payable has created account number for double-entry
            if (!$payable->created_payable_account_number) {
                throw new \Exception('Payable account not properly configured. Please edit and save the payable first.');
            }
            
            // Get the payable account for double-entry
            $payableAccount = AccountsModel::where('account_number', $payable->created_payable_account_number)->first();
            if (!$payableAccount) {
                throw new \Exception('Payable account not found: ' . $payable->created_payable_account_number);
            }
            
            // Calculate net payment
            $netPayment = (float)$this->payment_amount - (float)$this->early_payment_discount - (float)$this->withholding_tax + (float)$this->bank_charges;
            
            // Calculate new balance
            $newBalance = (float)$payable->balance - (float)$this->payment_amount - (float)$this->early_payment_discount;
            $totalPaid = (float)$payable->paid_amount + (float)$this->payment_amount + (float)$this->early_payment_discount;
            
            // Determine if it's internal or external transfer
            // Internal: if vendor has an account in our system (check vendor_bank_account_number in accounts table)
            // External: if vendor is external with bank details
            
            $isInternalTransfer = false;
            $vendorAccount = null;
            
            // Check if vendor has an internal account
            if ($payable->vendor_bank_account_number) {
                $vendorAccount = AccountsModel::where('account_number', $payable->vendor_bank_account_number)
                    ->where('status', 'ACTIVE')
                    ->first();
                    
                if ($vendorAccount) {
                    $isInternalTransfer = true;
                }
            }
            
            // Prepare transfer data
            $transferData = [
                'source_account' => $sourceAccount->account_number,  // Payment account (Cash/Bank)
                'destination_account' => $payableAccount->account_number,  // Created payable account for double-entry
                'amount' => $netPayment,
                'description' => 'Payment for Bill: ' . $payable->bill_number . ' - ' . $payable->vendor_name,
                'reference_number' => 'PAY-' . $payable->bill_number . '-' . date('YmdHis'),
                'transaction_date' => $this->payment_date,
            ];
            
            // Process the transfer using appropriate service
            if ($isInternalTransfer && $vendorAccount) {
                // Use Internal Funds Transfer Service
                $internalTransferService = new \App\Services\Payments\InternalFundsTransferService();
                
                // Add vendor account as the final destination
                $transferData['vendor_account'] = $vendorAccount->account_number;
                $transferData['transfer_type'] = 'vendor_payment';
                
                $transferResult = $internalTransferService->processTransfer($transferData);
                
                if (!$transferResult['success']) {
                    throw new \Exception('Internal transfer failed: ' . ($transferResult['message'] ?? 'Unknown error'));
                }
                
                Log::info('Internal vendor payment processed', [
                    'payable_id' => $this->payment_payable_id,
                    'amount' => $netPayment,
                    'vendor_account' => $vendorAccount->account_number
                ]);
                
            } else {
                // Use External Funds Transfer Service or standard posting
                // For now, use TransactionPostingService for external payments
                $postingService = new \App\Services\TransactionPostingService();
                
                // Post the payment transaction
                // Debit: Payable Account (liability decreases)
                // Credit: Payment Source Account (asset decreases)
                $postingData = [
                    'first_account' => $payableAccount->account_number,  // Debit - Payable Account
                    'second_account' => $sourceAccount->account_number,   // Credit - Cash/Bank Account
                    'amount' => $netPayment,
                    'narration' => $transferData['description'],
                    'reference_number' => $transferData['reference_number'],
                    'transaction_date' => $this->payment_date,
                    'action' => 'vendor_payment'
                ];
                
                $postingResult = $postingService->postTransaction($postingData);
                
                if (!$postingResult) {
                    throw new \Exception('Transaction posting failed');
                }
                
                // Record external payment details if vendor has bank details
                if ($payable->vendor_bank_name && $payable->vendor_bank_account_number) {
                    Log::info('External vendor payment processed', [
                        'payable_id' => $this->payment_payable_id,
                        'amount' => $netPayment,
                        'vendor_bank' => $payable->vendor_bank_name,
                        'vendor_account' => $payable->vendor_bank_account_number,
                        'vendor_branch' => $payable->vendor_bank_branch
                    ]);
                }
            }
            
            // Update payable status
            DB::table('trade_payables')
                ->where('id', $this->payment_payable_id)
                ->update([
                    'balance' => max(0, $newBalance),
                    'paid_amount' => $totalPaid,
                    'last_payment_date' => $this->payment_date,
                    'payment_date' => $newBalance <= 0 ? $this->payment_date : null,
                    'status' => $newBalance <= 0 ? 'paid' : 'partial',
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
            
            // Record payment in payable_payments table for audit trail
            $paymentId = DB::table('payable_payments')->insertGetId([
                'payable_id' => $this->payment_payable_id,
                'payment_date' => $this->payment_date,
                'amount' => $this->payment_amount,
                'payment_method' => $this->payment_method,
                'reference_number' => $transferData['reference_number'],
                'bank_charges' => $this->bank_charges,
                'early_payment_discount' => $this->early_payment_discount,
                'withholding_tax' => $this->withholding_tax,
                'notes' => $this->payment_notes,
                'transfer_type' => $isInternalTransfer ? 'internal' : 'external',
                'created_by' => auth()->id(),
                'created_at' => now(),
            ]);
            
            // Handle file upload
            if ($this->payment_voucher) {
                $path = $this->payment_voucher->store('payables/payments', 'public');
                DB::table('payable_payments')
                    ->where('id', $paymentId)
                    ->update(['payment_voucher' => $path]);
            }
            
            DB::commit();
            
            $this->showPaymentModal = false;
            $this->reset(['payment_amount', 'payment_reference', 'payment_notes', 
                         'bank_charges', 'early_payment_discount', 'withholding_tax']);
            $this->loadStatistics();
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => 'Payment processed successfully!'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error processing payment: ' . $e->getMessage());
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Error processing payment: ' . $e->getMessage()
            ]);
        }
    }
    
    private function createPaymentGLEntries($payable, $paymentId)
    {
        $reference = 'PAYMENT-' . str_pad($paymentId, 6, '0', STR_PAD_LEFT);
        $description = 'Payment for Bill ' . $payable->bill_number;
        
        // Get default accounts payable account
        $payableAccount = AccountsModel::where('account_type', 'LIABILITY')
            ->where('account_name', 'like', '%payable%')
            ->first();
            
        if (!$payableAccount) {
            Log::warning('No accounts payable account found, skipping GL entries');
            return;
        }
        
        // Debit Accounts Payable
        general_ledger::create([
            'reference_number' => $reference,
            'transaction_type' => 'PAYMENT',
            'transaction_date' => $this->payment_date,
            'account_id' => $payableAccount->id,
            'debit_amount' => (float)$this->payment_amount + (float)$this->early_payment_discount,
            'credit_amount' => 0,
            'description' => $description,
            'created_by' => auth()->id(),
            'status' => 'POSTED',
            'source_id' => $paymentId,
            'source_type' => 'payable_payments'
        ]);
        
        // Credit Bank/Cash Account
        $creditAmount = (float)$this->payment_amount - (float)$this->withholding_tax + (float)$this->bank_charges;
        general_ledger::create([
            'reference_number' => $reference,
            'transaction_type' => 'PAYMENT',
            'transaction_date' => $this->payment_date,
            'account_id' => $this->payment_account_id,
            'debit_amount' => 0,
            'credit_amount' => $creditAmount,
            'description' => $description,
            'created_by' => auth()->id(),
            'status' => 'POSTED',
            'source_id' => $paymentId,
            'source_type' => 'payable_payments'
        ]);
        
        // Record bank charges if any
        if ($this->bank_charges > 0) {
            $bankChargesAccount = AccountsModel::where('account_name', 'like', '%bank charges%')
                ->where('account_type', 'EXPENSE')
                ->first();
            
            if ($bankChargesAccount) {
                general_ledger::create([
                    'reference_number' => $reference,
                    'transaction_type' => 'PAYMENT',
                    'transaction_date' => $this->payment_date,
                    'account_id' => $bankChargesAccount->id,
                    'debit_amount' => $this->bank_charges,
                    'credit_amount' => 0,
                    'description' => 'Bank charges for ' . $description,
                    'created_by' => auth()->id(),
                    'status' => 'POSTED',
                    'source_id' => $paymentId,
                    'source_type' => 'payable_payments'
                ]);
            }
        }
        
        // Record early payment discount if any
        if ($this->early_payment_discount > 0) {
            $discountAccount = AccountsModel::where('account_name', 'like', '%discount%received%')
                ->orWhere('account_name', 'like', '%discount%')
                ->where('account_type', 'REVENUE')
                ->first();
            
            if ($discountAccount) {
                general_ledger::create([
                    'reference_number' => $reference,
                    'transaction_type' => 'PAYMENT',
                    'transaction_date' => $this->payment_date,
                    'account_id' => $discountAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $this->early_payment_discount,
                    'description' => 'Early payment discount on ' . $description,
                    'created_by' => auth()->id(),
                    'status' => 'POSTED',
                    'source_id' => $paymentId,
                    'source_type' => 'payable_payments'
                ]);
            }
        }
        
        // Record withholding tax if any
        if ($this->withholding_tax > 0) {
            $withholdingAccount = AccountsModel::where('account_name', 'like', '%withholding%tax%')
                ->where('account_type', 'LIABILITY')
                ->first();
            
            if ($withholdingAccount) {
                general_ledger::create([
                    'reference_number' => $reference,
                    'transaction_type' => 'PAYMENT',
                    'transaction_date' => $this->payment_date,
                    'account_id' => $withholdingAccount->id,
                    'debit_amount' => 0,
                    'credit_amount' => $this->withholding_tax,
                    'description' => 'Withholding tax on ' . $description,
                    'created_by' => auth()->id(),
                    'status' => 'POSTED',
                    'source_id' => $paymentId,
                    'source_type' => 'payable_payments'
                ]);
            }
        }
    }
    
    public function approve($id)
    {
        DB::beginTransaction();
        try {
            $payable = DB::table('trade_payables')->find($id);
            
            if (!$payable) {
                throw new \Exception('Payable not found');
            }
            
            // Update approval status (Note: approval_status columns don't exist in trade_payables)
            DB::table('trade_payables')
                ->where('id', $id)
                ->update([
                    'status' => 'approved',  // Using status column instead
                    'updated_by' => auth()->id(),
                    'updated_at' => now(),
                ]);
            
            // Skip GL entries on approval as required columns don't exist in trade_payables
            // GL entries will be created when payment is processed
            
            // Log approval
            if (Schema::hasTable('payable_approvals')) {
                DB::table('payable_approvals')->insert([
                    'payable_id' => $id,
                    'action' => 'approved',
                    'comments' => 'Approved for payment',
                    'performed_by' => auth()->id(),
                    'performed_at' => now(),
                ]);
            }
            
            DB::commit();
            
            $this->loadStatistics();
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => 'Payable approved successfully!'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => 'Error approving payable: ' . $e->getMessage()
            ]);
        }
    }
    
    public function edit($id)
    {
        $payable = DB::table('trade_payables')->find($id);
        
        if ($payable) {
            $this->payableId = $id;
            $this->vendor_id = $payable->vendor_id;
            $this->vendor_name = $payable->vendor_name;
            
            // Load vendor details if vendor_id exists
            if ($payable->vendor_id && Schema::hasTable('vendors')) {
                $vendor = DB::table('vendors')->find($payable->vendor_id);
                if ($vendor) {
                    $this->vendor_email = $vendor->email ?? '';
                    $this->vendor_phone = $vendor->phone ?? '';
                    $this->vendor_address = $vendor->address ?? '';
                    $this->vendor_tax_id = $vendor->organization_tin_number ?? '';
                }
            } else {
                $this->vendor_email = '';
                $this->vendor_phone = '';
                $this->vendor_address = '';
                $this->vendor_tax_id = '';
            }
            
            // Load vendor bank details
            $this->vendor_bank_name = $payable->vendor_bank_name ?? '';
            $this->vendor_bank_account_number = $payable->vendor_bank_account_number ?? '';
            $this->vendor_bank_branch = $payable->vendor_bank_branch ?? '';
            $this->vendor_swift_code = $payable->vendor_swift_code ?? '';
            
            $this->invoice_number = $payable->bill_number;
            $this->bill_number = $payable->bill_number;
            $this->purchase_order_number = $payable->purchase_order_number;
            $this->invoice_date = $payable->bill_date;
            $this->due_date = $payable->due_date;
            $this->amount = (float)$payable->amount;
            $this->vat_amount = 0;  // Not in trade_payables
            $this->total_amount = (float)$payable->amount;
            $this->currency = 'TZS';  // Default currency as not in trade_payables
            $this->payment_terms = $payable->payment_terms;
            $this->description = $payable->description;
            
            // Load the saved account references
            $this->parent_account_number = $payable->parent_account_number ?? null;
            $this->other_account_id = $payable->other_account_id ?? null;
            $this->payable_account_id = $payable->payable_account_id ?? null;
            $this->created_payable_account_number = $payable->created_payable_account_number ?? null;
            $this->expense_account_id = $payable->other_account_id ?? null;  // For backward compatibility
            
            $this->notes = '';  // Not in trade_payables
            $this->priority = 'normal';  // Default as not in trade_payables
            
            $this->editMode = true;
            $this->showCreateModal = true;
        }
    }
    
    public function delete($id)
    {
        DB::beginTransaction();
        try {
            // Check if payable has payments
            if (Schema::hasTable('payable_payments')) {
                $hasPayments = DB::table('payable_payments')
                    ->where('payable_id', $id)
                    ->exists();
                
                if ($hasPayments) {
                    throw new \Exception('Cannot delete payable with payments. Please reverse payments first.');
                }
            }
            
            // Delete GL entries
            general_ledger::where('source_type', 'payables')
                ->where('source_id', $id)
                ->delete();
            
            // Delete payable
            DB::table('trade_payables')->where('id', $id)->delete();
            
            DB::commit();
            
            $this->loadStatistics();
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'success',
                'message' => 'Payable deleted successfully!'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            $this->dispatchBrowserEvent('alert', [
                'type' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
    
    public function render()
    {
        $payables = collect();
        
        // Get accounts for GL posting
        $expenseAccounts = DB::table('accounts')
            ->where('type', 'expense_accounts') // Expense accounts
            ->where('account_level', 3) // Level 3 expense accounts
            ->where('status', 'ACTIVE')
            ->orderBy('account_name')
            ->get();
            
        $parentAccounts = DB::table('accounts')
            ->where('type', 'liability_accounts') // Liability accounts
            ->where('account_level', 3) // Level 3 liability accounts
            ->where('status', 'ACTIVE')
            ->orderBy('account_name')
            ->get();
        
        if (Schema::hasTable('trade_payables')) {
            $query = DB::table('trade_payables')
                ->select([
                    'trade_payables.*',
                    DB::raw("CASE 
                        WHEN trade_payables.due_date IS NOT NULL 
                        THEN (trade_payables.due_date::date - CURRENT_DATE)
                        ELSE 0 
                    END as days_until_due")
                ]);
            
            // Apply filters
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('trade_payables.vendor_name', 'like', '%' . $this->search . '%')
                      ->orWhere('trade_payables.bill_number', 'like', '%' . $this->search . '%')
                      ->orWhere('trade_payables.purchase_order_number', 'like', '%' . $this->search . '%');
                });
            }
            
            if ($this->statusFilter && $this->statusFilter !== 'all') {
                $query->where('trade_payables.status', $this->statusFilter);
            }
            
            if ($this->priorityFilter && $this->priorityFilter !== 'all') {
                // Priority column doesn't exist in trade_payables
                // Removed priority filter
            }
            
            if ($this->dateFrom) {
                $query->where('trade_payables.bill_date', '>=', $this->dateFrom);
            }
            
            if ($this->dateTo) {
                $query->where('trade_payables.bill_date', '<=', $this->dateTo);
            }
            
            $payables = $query->orderBy('trade_payables.due_date', 'asc')
                ->paginate(10);
        } else {
            $payables = new \Illuminate\Pagination\LengthAwarePaginator(
                collect(),
                0,
                10
            );
        }
        
        return view('livewire.accounting.trade-payables', [
            'payables' => $payables,
            'accountsPayable' => $payables,  // For compatibility with existing view
            'expenseAccounts' => $expenseAccounts,
            'parentAccounts' => $parentAccounts
        ]);
    }
}