<?php

namespace App\Http\Livewire\Accounting;

use Livewire\Component;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\AccountsModel;

class Accounting extends Component
{
    public $tab_id = '1';
    public $menuSearch = '';
    public $viewMemberDetails = false;
    
    // Statistics properties
    public $totalInstitutionAccounts;
    public $totalMemberAccounts;
    public $pendingActivities;
    public $menuItems;
    public $menuCategories;
    public $title;

    protected $listeners = [
        'financeViewMember' => 'viewMembersData'
    ];

    public function viewMembersData($id)
    {
        if ($this->viewMemberDetails == false) {
            $this->viewMemberDetails = true;
            session()->put('viewMemberId_details', $id);
        } else if ($this->viewMemberDetails == true) {
            $this->viewMemberDetails = false;
        }
    }

    public function menuItemClicked($tabId)
    {
        $this->tab_id = $tabId;
        if ($tabId == '1') {
            $this->title = 'Internal accounts';
        }
        if ($tabId == '2') {
            $this->title = 'Enter new shares details';
        }
        if ($tabId == '3') {
            $this->title = 'External accounts';
        }
        if ($tabId == '4') {
            $this->title = 'Loan Disbursements';
            Session::put('viewAccountsWithCategory', 'Accounting');
            Session::put('currentloanID', null);
            Session::put('currentloanMember', null);
            Session::put('disableInputs', true);
        }
        if ($tabId == '5') {
            $this->title = 'PO / Invoices';
            Session::put('viewAccountsWithCategory', 'AccountingPO');
            Session::put('currentloanID', null);
            Session::put('currentloanMember', null);
            Session::put('disableInputs', true);
        }
    }

    public function render()
    {
        // Prepare statistics
        $this->totalInstitutionAccounts = AccountsModel::count();
        $this->totalMemberAccounts = AccountsModel::where('account_use', 'internal')->count();
        $this->pendingActivities = AccountsModel::where('status', 'PENDING')->count();

        // Prepare menu items
        $this->menuItems = [
            ['id' => 1, 'label' => 'Chart of accounts'],
            ['id' => 37, 'label' => 'Ledger Accounts'],
            ['id' => 2, 'label' => 'Manual Posting'],
            ['id' => 44, 'label' => 'Internal Transfers'],
            ['id' => 45, 'label' => 'Adjustments'],
            ['id' => 46, 'label' => 'Till and Cash Management'],
            ['id' => 3, 'label' => 'External Bank Accounts'],
            ['id' => 4, 'label' => 'Loan Disbursement'],
            ['id' => 6, 'label' => 'Standing Instructions'],
            ['id' => 12, 'label' => 'GL Statement'],
            ['id' => 10, 'label' => 'Expenditure Control'],
            ['id' => 40, 'label' => 'Income Statement'],
            ['id' => 41, 'label' => 'Jedwali La Mahesabu'],
            ['id' => 42, 'label' => 'Financial Position'],
            ['id' => 28, 'label' => 'Changes In Equity'],
            ['id' => 8, 'label' => 'Cash Flow Statement'],
            ['id' => 5, 'label' => 'Trial Balance'],
            ['id' => 9, 'label' => 'Members'],
            ['id' => 13, 'label' => 'Expenses'],
            ['id' => 14, 'label' => 'Petty Cash'],
            ['id' => 15, 'label' => 'Strong Room'],
            ['id' => 16, 'label' => 'Assets Management'],
            ['id' => 17, 'label' => 'Loan Loss Reserves (LLR)'],
            ['id' => 18, 'label' => 'Trade And Other Receivable'],
            ['id' => 30, 'label' => 'Trade And Other Payable'],
            ['id' => 19, 'label' => 'Insurance'],
            ['id' => 20, 'label' => 'PPE Management'],
            ['id' => 21, 'label' => 'Other Income'],
            ['id' => 22, 'label' => 'Financial Insurance'],
            ['id' => 23, 'label' => 'Bad Loan Writeoffs'],
            ['id' => 24, 'label' => 'Creditors'],
            ['id' => 25, 'label' => 'Interest Payable'],
            ['id' => 26, 'label' => 'Short-term / Long-term Loans'],
            ['id' => 27, 'label' => 'Unearned / deferred revenue'],
            ['id' => 29, 'label' => 'Investments'],
            ['id' => 31, 'label' => 'Provisions'],
            ['id' => 32, 'label' => 'Depreciation'],
            ['id' => 33, 'label' => 'Shares'],
            ['id' => 34, 'label' => 'Savings'],
            ['id' => 35, 'label' => 'Deposits'],
            ['id' => 36, 'label' => 'Loan Outstanding'],
            ['id' => 38, 'label' => 'Loan Charges'],
            ['id' => 39, 'label' => 'Insurance Charges'],
        ];

        $this->menuCategories = [
            'Core Accounting' => [1, 37, 2, 44, 45, 46, 3, 4, 6],
            'Financial Statements' => [12, 40, 41, 42, 28, 8, 5],
            'Member Management' => [9, 33, 34, 35],
            'Asset Management' => [16, 20, 32],
            'Risk Management' => [17, 23, 31],
            'Operations' => [13, 14, 15, 18, 30, 19, 21, 22, 24, 25, 26, 27, 29, 36, 38, 39],
        ];

        return view('livewire.accounting.accounting', [
            'totalInstitutionAccounts' => $this->totalInstitutionAccounts,
            'totalMemberAccounts' => $this->totalMemberAccounts,
            'pendingActivities' => $this->pendingActivities,
            'menuItems' => $this->menuItems,
            'menuCategories' => $this->menuCategories,
        ]);
    }
} 