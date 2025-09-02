<?php

namespace App\Http\Livewire\Accounting;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class LedgerAccounts extends Component
{
    use WithPagination;

    // Filter properties
    public $searchTerm = '';
    public $selectedType = '';
    public $selectedLevel = '';
    public $selectedCategory = '';
    public $expandedAccounts = [];
    
    // Modal properties
    public $showLedgerModal = false;
    public $showDetailsModal = false;
    public $showCreateModal = false;
    public $selectedAccount = null;
    public $selectedAccountData = null;
    public $ledgerEntries = [];
    
    // Create account modal properties
    public $parentAccountNumber = null;
    public $parentAccountData = null;
    
    // New account properties
    public $newAccount = [
        'account_name' => '',
        'account_number' => '',
        'parent_account_number' => '',
        'account_level' => '',
        'account_use' => 'internal',
        'notes' => '',
        'type' => '',
        'major_category_code' => '',
        'category_code' => '',
        'sub_category_code' => '',
        'status' => 'ACTIVE'
    ];
    
    // Account types based on IAS standards
    public $accountTypes = [
        'ASSET' => 'Assets',
        'LIABILITY' => 'Liabilities', 
        'EQUITY' => 'Equity',
        'INCOME' => 'Income',
        'EXPENSE' => 'Expenses'
    ];
    
    // Account levels for hierarchy
    public $accountLevels = [
        1 => 'Main Account',
        2 => 'Sub Account',
        3 => 'Detail Account',
        4 => 'Transaction Account'
    ];

    public function mount()
    {
        // Initialize expanded accounts for better UX
        $this->expandedAccounts = [];
    }

    public function toggleAccount($accountNumber)
    {
        if (in_array($accountNumber, $this->expandedAccounts)) {
            $this->expandedAccounts = array_diff($this->expandedAccounts, [$accountNumber]);
        } else {
            $this->expandedAccounts[] = $accountNumber;
        }
    }

    public function getAccountsHierarchy()
    {
        // Build hierarchical structure following IAS/IFRS standards
        $query = DB::table('accounts')
            ->select(
                'accounts.*',
                DB::raw('COALESCE(CAST(accounts.balance AS DECIMAL(20,2)), 0) as current_balance'),
                DB::raw("
                    CASE 
                        WHEN UPPER(accounts.account_name) LIKE '%ASSET%' OR accounts.major_category_code = '1000' THEN 1
                        WHEN UPPER(accounts.account_name) LIKE '%LIABILIT%' OR accounts.major_category_code = '2000' THEN 2
                        WHEN UPPER(accounts.account_name) LIKE '%EQUITY%' OR UPPER(accounts.account_name) LIKE '%CAPITAL%' OR accounts.major_category_code = '3000' THEN 3
                        WHEN UPPER(accounts.account_name) LIKE '%REVENUE%' OR UPPER(accounts.account_name) LIKE '%INCOME%' OR accounts.major_category_code = '4000' THEN 4
                        WHEN UPPER(accounts.account_name) LIKE '%EXPENSE%' OR accounts.major_category_code = '5000' THEN 5
                        ELSE 6
                    END as type_order
                "),
                DB::raw("
                    CASE 
                        WHEN UPPER(accounts.account_name) LIKE '%ASSET%' OR accounts.major_category_code = '1000' THEN 'ASSET'
                        WHEN UPPER(accounts.account_name) LIKE '%LIABILIT%' OR accounts.major_category_code = '2000' THEN 'LIABILITY'
                        WHEN UPPER(accounts.account_name) LIKE '%EQUITY%' OR UPPER(accounts.account_name) LIKE '%CAPITAL%' OR accounts.major_category_code = '3000' THEN 'EQUITY'
                        WHEN UPPER(accounts.account_name) LIKE '%REVENUE%' OR UPPER(accounts.account_name) LIKE '%INCOME%' OR accounts.major_category_code = '4000' THEN 'INCOME'
                        WHEN UPPER(accounts.account_name) LIKE '%EXPENSE%' OR accounts.major_category_code = '5000' THEN 'EXPENSE'
                        ELSE COALESCE(accounts.type, 'OTHER')
                    END as display_type
                ")
            )
            ->where('status', 'ACTIVE')
            ->whereNull('deleted_at');

        // Apply filters
        if ($this->searchTerm) {
            $query->where(function($q) {
                $q->where('account_name', 'ILIKE', '%' . $this->searchTerm . '%')
                  ->orWhere('account_number', 'ILIKE', '%' . $this->searchTerm . '%')
                  ->orWhere('major_category_code', 'ILIKE', '%' . $this->searchTerm . '%')
                  ->orWhere('category_code', 'ILIKE', '%' . $this->searchTerm . '%')
                  ->orWhere('sub_category_code', 'ILIKE', '%' . $this->searchTerm . '%');
            });
        }

        if ($this->selectedType) {
            $query->where('type', $this->selectedType);
        }

        if ($this->selectedLevel) {
            $query->where('account_level', $this->selectedLevel);
        }

        if ($this->selectedCategory) {
            $query->where('major_category_code', $this->selectedCategory);
        }

        $accounts = $query
            ->orderBy('type_order')
            ->orderBy('major_category_code')
            ->orderBy('category_code')
            ->orderBy('sub_category_code')
            ->orderBy('account_number')
            ->get();

        // Build hierarchical structure
        return $this->buildHierarchy($accounts);
    }

    private function buildHierarchy($accounts)
    {
        $hierarchy = [];
        $accountMap = [];

        // First pass: create account map
        foreach ($accounts as $account) {
            $accountMap[$account->account_number] = $account;
            $account->children = [];
            $account->total_balance = floatval($account->current_balance);
        }

        // Second pass: build hierarchy
        foreach ($accounts as $account) {
            if ($account->parent_account_number && isset($accountMap[$account->parent_account_number])) {
                $parent = $accountMap[$account->parent_account_number];
                $parent->children[] = $account;
                // Roll up balances to parent
                $parent->total_balance += floatval($account->current_balance);
            } elseif (!$account->parent_account_number || $account->account_level == 1) {
                // Top level account
                $hierarchy[] = $account;
            }
        }

        return $hierarchy;
    }

    public function getAccountSummary()
    {
        // Get summary totals by type following IAS 1 presentation requirements
        $summary = DB::table('accounts')
            ->select(
                DB::raw("
                    CASE 
                        WHEN UPPER(accounts.account_name) LIKE '%ASSET%' OR accounts.major_category_code = '1000' THEN 'ASSET'
                        WHEN UPPER(accounts.account_name) LIKE '%LIABILIT%' OR accounts.major_category_code = '2000' THEN 'LIABILITY'
                        WHEN UPPER(accounts.account_name) LIKE '%EQUITY%' OR UPPER(accounts.account_name) LIKE '%CAPITAL%' OR accounts.major_category_code = '3000' THEN 'EQUITY'
                        WHEN UPPER(accounts.account_name) LIKE '%REVENUE%' OR UPPER(accounts.account_name) LIKE '%INCOME%' OR accounts.major_category_code = '4000' THEN 'INCOME'
                        WHEN UPPER(accounts.account_name) LIKE '%EXPENSE%' OR accounts.major_category_code = '5000' THEN 'EXPENSE'
                        ELSE 'OTHER'
                    END as type
                "),
                DB::raw('COUNT(*) as account_count'),
                DB::raw('SUM(CAST(balance AS DECIMAL(20,2))) as total_balance')
            )
            ->where('status', 'ACTIVE')
            ->whereNull('deleted_at')
            ->groupBy(DB::raw("
                CASE 
                    WHEN UPPER(accounts.account_name) LIKE '%ASSET%' OR accounts.major_category_code = '1000' THEN 'ASSET'
                    WHEN UPPER(accounts.account_name) LIKE '%LIABILIT%' OR accounts.major_category_code = '2000' THEN 'LIABILITY'
                    WHEN UPPER(accounts.account_name) LIKE '%EQUITY%' OR UPPER(accounts.account_name) LIKE '%CAPITAL%' OR accounts.major_category_code = '3000' THEN 'EQUITY'
                    WHEN UPPER(accounts.account_name) LIKE '%REVENUE%' OR UPPER(accounts.account_name) LIKE '%INCOME%' OR accounts.major_category_code = '4000' THEN 'INCOME'
                    WHEN UPPER(accounts.account_name) LIKE '%EXPENSE%' OR accounts.major_category_code = '5000' THEN 'EXPENSE'
                    ELSE 'OTHER'
                END
            "))
            ->get();

        $formattedSummary = [];
        foreach ($summary as $item) {
            $formattedSummary[$item->type] = [
                'count' => $item->account_count,
                'balance' => $item->total_balance ?? 0
            ];
        }

        // Calculate key financial metrics per IAS 1
        $assets = $formattedSummary['ASSET']['balance'] ?? 0;
        $liabilities = $formattedSummary['LIABILITY']['balance'] ?? 0;
        $equity = $formattedSummary['EQUITY']['balance'] ?? 0;
        $income = $formattedSummary['INCOME']['balance'] ?? 0;
        $expenses = $formattedSummary['EXPENSE']['balance'] ?? 0;

        return [
            'summary' => $formattedSummary,
            'metrics' => [
                'total_assets' => $assets,
                'total_liabilities' => $liabilities,
                'total_equity' => $equity,
                'net_income' => $income - $expenses,
                'accounting_equation_check' => abs(($assets) - ($liabilities + $equity)) < 0.01
            ]
        ];
    }

    public function getTrialBalance()
    {
        // Get trial balance data following double-entry principles
        $accounts = DB::table('accounts')
            ->select(
                'account_number',
                'account_name',
                'type',
                DB::raw('CAST(debit AS DECIMAL(20,2)) as debit_balance'),
                DB::raw('CAST(credit AS DECIMAL(20,2)) as credit_balance'),
                DB::raw('CAST(balance AS DECIMAL(20,2)) as net_balance')
            )
            ->where('status', 'ACTIVE')
            ->whereNull('deleted_at')
            ->where(function($q) {
                $q->whereRaw('CAST(debit AS DECIMAL(20,2)) != 0')
                  ->orWhereRaw('CAST(credit AS DECIMAL(20,2)) != 0');
            })
            ->orderBy('account_number')
            ->get();

        $totalDebits = $accounts->sum('debit_balance');
        $totalCredits = $accounts->sum('credit_balance');

        return [
            'accounts' => $accounts,
            'totals' => [
                'debits' => $totalDebits,
                'credits' => $totalCredits,
                'difference' => abs($totalDebits - $totalCredits),
                'is_balanced' => abs($totalDebits - $totalCredits) < 0.01
            ]
        ];
    }

    public function resetFilters()
    {
        $this->searchTerm = '';
        $this->selectedType = '';
        $this->selectedLevel = '';
        $this->selectedCategory = '';
        $this->resetPage();
    }

    public function viewLedger($accountNumber)
    {
        $this->selectedAccount = $accountNumber;
        
        // Get account details and convert to array
        $accountData = DB::table('accounts')
            ->where('account_number', $accountNumber)
            ->first();
        
        $this->selectedAccountData = $accountData ? (array) $accountData : null;
        
        // For parent accounts, also look for transactions on child accounts
        // Account numbers in general_ledger might be longer versions
        $accountPattern = $accountNumber . '%';
        
        // Get ledger entries from general_ledger table
        // Check both exact match and pattern match for child accounts
        $entries = DB::table('general_ledger')
            ->where(function($query) use ($accountNumber, $accountPattern) {
                $query->where('record_on_account_number', $accountNumber)
                      ->orWhere('record_on_account_number', 'LIKE', $accountPattern);
            })
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();
        
        $this->ledgerEntries = $entries->map(function($entry) {
            return (array) $entry;
        })->toArray();
        
        $this->showLedgerModal = true;
    }

    public function viewAccountDetails($accountNumber)
    {
        // Get account details with related information
        $accountData = DB::table('accounts')
            ->where('account_number', $accountNumber)
            ->first();
            
        if ($accountData) {
            $this->selectedAccountData = (array) $accountData;
            
            // Get parent account if exists
            if ($accountData->parent_account_number) {
                $parent = DB::table('accounts')
                    ->where('account_number', $accountData->parent_account_number)
                    ->first();
                $this->selectedAccountData['parent'] = $parent ? (array) $parent : null;
            }
            
            // Get child accounts
            $children = DB::table('accounts')
                ->where('parent_account_number', $accountNumber)
                ->get();
            
            $this->selectedAccountData['children'] = $children->map(function($child) {
                return (array) $child;
            })->toArray();
            
            $this->showDetailsModal = true;
        }
    }
    
    public function closeLedgerModal()
    {
        $this->showLedgerModal = false;
        $this->selectedAccount = null;
        $this->selectedAccountData = null;
        $this->ledgerEntries = [];
    }
    
    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->selectedAccountData = null;
    }
    
    public function openCreateModal($parentAccountNumber = null)
    {
        $this->resetNewAccount();
        
        if ($parentAccountNumber) {
            // Get parent account data
            $parent = DB::table('accounts')
                ->where('account_number', $parentAccountNumber)
                ->first();
            
            if ($parent) {
                $this->parentAccountData = (array) $parent;
                
                // Inherit properties from parent
                $this->newAccount['parent_account_number'] = $parentAccountNumber;
                $this->newAccount['type'] = $parent->type;
                $this->newAccount['major_category_code'] = $parent->major_category_code;
                $this->newAccount['category_code'] = $parent->category_code;
                $this->newAccount['sub_category_code'] = $parent->sub_category_code;
                $this->newAccount['account_use'] = $parent->account_use;
                
                // Set account level (parent level + 1)
                $parentLevel = intval($parent->account_level ?? 1);
                $this->newAccount['account_level'] = (string)($parentLevel + 1);
                
                // Generate suggested account number (parent number + next sequence)
                $this->newAccount['account_number'] = $this->generateAccountNumber($parentAccountNumber);
            }
        } else {
            // Creating a top-level account
            $this->newAccount['account_level'] = '1';
            $this->parentAccountData = null;
        }
        
        $this->showCreateModal = true;
    }
    
    public function closeCreateModal()
    {
        $this->showCreateModal = false;
        $this->resetNewAccount();
        $this->parentAccountData = null;
    }
    
    private function resetNewAccount()
    {
        $this->newAccount = [
            'account_name' => '',
            'account_number' => '',
            'parent_account_number' => '',
            'account_level' => '',
            'account_use' => 'internal',
            'notes' => '',
            'type' => '',
            'major_category_code' => '',
            'category_code' => '',
            'sub_category_code' => '',
            'status' => 'ACTIVE'
        ];
    }
    
    private function generateAccountNumber($parentNumber)
    {
        // Get the last child account number for this parent
        $lastChild = DB::table('accounts')
            ->where('parent_account_number', $parentNumber)
            ->orderBy('account_number', 'desc')
            ->first();
        
        if ($lastChild) {
            // Increment the last number
            $lastNum = $lastChild->account_number;
            // Extract the numeric part and increment
            if (preg_match('/(\d+)$/', $lastNum, $matches)) {
                $num = intval($matches[1]) + 1;
                $baseLength = strlen($parentNumber);
                // Ensure the new number is longer than parent
                return $parentNumber . str_pad($num, 4, '0', STR_PAD_LEFT);
            }
        }
        
        // Default: parent number + 0001
        return $parentNumber . '0001';
    }
    
    public function createAccount()
    {
        // Validate
        $this->validate([
            'newAccount.account_name' => 'required|string|max:200',
            'newAccount.account_number' => 'required|string|unique:accounts,account_number|max:50',
            'newAccount.type' => 'required|string',
            'newAccount.account_level' => 'required|string',
            'newAccount.major_category_code' => 'required|string|max:20',
            'newAccount.category_code' => 'required|string|max:20',
            'newAccount.sub_category_code' => 'required|string|max:20'
        ], [
            'newAccount.account_name.required' => 'Account name is required',
            'newAccount.account_number.required' => 'Account number is required',
            'newAccount.account_number.unique' => 'This account number already exists',
            'newAccount.type.required' => 'Account type is required'
        ]);
        
        try {
            // Create the account
            DB::table('accounts')->insert([
                'account_name' => $this->newAccount['account_name'],
                'account_number' => $this->newAccount['account_number'],
                'parent_account_number' => $this->newAccount['parent_account_number'] ?: null,
                'account_level' => $this->newAccount['account_level'],
                'account_use' => $this->newAccount['account_use'],
                'notes' => $this->newAccount['notes'],
                'type' => $this->newAccount['type'],
                'major_category_code' => $this->newAccount['major_category_code'],
                'category_code' => $this->newAccount['category_code'],
                'sub_category_code' => $this->newAccount['sub_category_code'],
                'status' => $this->newAccount['status'],
                'balance' => '0',
                'debit' => '0',
                'credit' => '0',
                'client_number' => '0000', // Default for GL accounts
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            // Close modal and refresh
            $this->closeCreateModal();
            
            // Expand parent account to show new account
            if ($this->newAccount['parent_account_number'] && !in_array($this->newAccount['parent_account_number'], $this->expandedAccounts)) {
                $this->expandedAccounts[] = $this->newAccount['parent_account_number'];
            }
            
            session()->flash('message', 'Account created successfully!');
            
        } catch (\Exception $e) {
            session()->flash('error', 'Failed to create account: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.accounting.ledger-accounts', [
            'accountsHierarchy' => $this->getAccountsHierarchy(),
            'accountSummary' => $this->getAccountSummary(),
            'trialBalance' => $this->getTrialBalance(),
            'selectedAccountData' => $this->selectedAccountData,
            'ledgerEntries' => $this->ledgerEntries,
            'parentAccountData' => $this->parentAccountData
        ]);
    }
}