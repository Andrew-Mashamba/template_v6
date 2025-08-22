<?php

namespace App\Http\Livewire\Accounting;

use Illuminate\Support\Facades\DB;
use Livewire\Component;

class LedgerAccounts extends Component
{

    public $term = "";
    public $showAddUser = false;
    public $memberStatus = 'All';
    public $numberOfProducts;
    public $products;
    public $item;
    public $category;
    public $account_name;
    public $notes;
    public $has_mirror_account=false;
    public $mirror_account;
    public $bank_id;
    public $account_number;
    public $createNewAccount;
    public $banks;


    // new
    public $sub_category_name;
    public $account_code;

    //public $asset_accounts;
    public $liability_accounts;
    public $capital_accounts;
    public $income_accounts;
    public $expense_accounts;



    public $accountName;

    public $asset_accounts = [];
    public $showCreateAccountInputs = false;
    public $editAccountId;
    public $newAccountName;
    public $newInitialAmount;

    public $main_total_amount = 0;

    public $source_account;
    public $amount;
    public $destination_accounts;
    public $narration;

    protected $creditAndDebitService;

    public $showEditModal;
    public $showEditModal_l2;
    public $showCreateAccountInputs_l2;

    public $newAccountName_l2 = [];
    public $newInitialAmount_l2 = [];
    public $accountId_l2;
    public $table_l2;


    public function render()
    {

        $this->asset_accounts = DB::table('asset_accounts')->orderBy('id', 'asc')->get();
        $this->liability_accounts = DB::table('liability_accounts')->orderBy('id', 'asc')->get();
        $this->capital_accounts = DB::table('capital_accounts')->orderBy('id', 'asc')->get();
        $this->income_accounts = DB::table('income_accounts')->orderBy('id', 'asc')->get();
        $this->expense_accounts = DB::table('expense_accounts')->orderBy('id', 'asc')->get();


        return view('livewire.accounting.ledger-accounts');
    }
}
