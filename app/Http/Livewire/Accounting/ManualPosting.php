<?php

namespace App\Http\Livewire\Accounting;

use App\Models\AccountsModel;
use App\Models\general_ledger;
use App\Services\TransactionPostingService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class ManualPosting extends Component
{
    public $debit_category;
    public $debit_category_code;
    public $debit_account;
    public $credit_category;
    public $credit_category_code;
    public $credit_account;
    public $amount;
    public $narration;
    public $transactionPosted;

    public $search = ''; // Property to store the search term
    public $results = []; // Property to store the search results
    public $selectedAccount = [];
    public $selectedAccountTwo=[];
    public $showDropdownTwo=false;
    public $resultsTwo;
    public $searchTwo,$source_one,$source_two;
    public $isTableVisible = true;
    public $isTableVisibleTwo=true;

    // Control table visibility
    // Store the selected account details

    public $showDropdown = false;
    public $role;

    protected $rules = [
        'source_one' => 'required',
        'source_two' => 'required',
        'debit_account' => 'required',
        'credit_account' => 'required',
        'amount' => 'required|numeric|min:0.01',
        'narration' => 'required|string|max:255',
    ];



    public function updatedSearchxxx()
    {

        if (strlen($this->search) > 0) {

        $this->results = DB::table('accounts')
            ->select('id', 'account_name', 'account_number', 'balance', 'status', DB::raw("'accounts' as source"))
            ->where('account_name', 'LIKE', '%' . $this->search . '%')
            ->orWhere('account_number', 'LIKE', '%' . $this->search . '%')
//            ->union(
//                DB::table('sub_accounts')
//                    ->select('id', 'account_name', 'account_number', 'balance', 'status', DB::raw("'sub_accounts' as source"))
//                    ->where('account_name', 'LIKE', '%' . $this->search . '%')
//                    ->orWhere('account_number', 'LIKE', '%' . $this->search . '%')
//            )
            ->get()
            ->toArray();

            $this->isTableVisible = true; // Show table on search

            $this->showDropdown = true;
        } else {
            $this->showDropdown = false;
            $this->results = [];
        }

    }


    public function updatedSearch()
    {
        if (strlen($this->search) > 0) {
            $searchTerm = '%' . strtolower($this->search) . '%';

            $accountsQuery = DB::table('accounts')
                ->select('id', 'account_name', 'account_number', 'balance', 'status', DB::raw("'accounts' as source"))
                ->where(function ($query) use ($searchTerm) {
                    $query->where(DB::raw('LOWER(account_name)'), 'LIKE', $searchTerm)
                        ->orWhere(DB::raw('LOWER(account_number)'), 'LIKE', $searchTerm);
                });

            $AccountsModelQuery = DB::table('sub_accounts')
                ->select('id', 'account_name', 'account_number', 'balance', 'status', DB::raw("'sub_accounts' as source"))
                ->where(function ($query) use ($searchTerm) {
                    $query->where(DB::raw('LOWER(account_name)'), 'LIKE', $searchTerm)
                        ->orWhere(DB::raw('LOWER(account_number)'), 'LIKE', $searchTerm);
                });

            // Combine results from both queries with union
            $this->results = $accountsQuery->union($AccountsModelQuery)->get()->toArray();

            $this->isTableVisible = true; // Show table on search
            $this->showDropdown = true;

        } else {
            $this->showDropdown = false;
            $this->results = [];
        }
    }





    public function selectAccount($accountId, $source)
    {

        // Fetch account details based on ID and source table
        $account = DB::table($source)->where('id', $accountId)->first();
        if ($account) {
            $this->selectedAccount = (array) $account;
        }

        $this->source_one=$source;

        $this->showDropdown = false;

    }



    public function updatedSearchTwoxxxxx()
    {

        if (strlen($this->searchTwo) > 0) {

        $this->resultsTwo = DB::table('accounts')
            ->select('id', 'account_name', 'account_number', 'balance', 'status', DB::raw("'accounts' as source"))
            ->where('account_name', 'LIKE', '%' . $this->search . '%')
            ->orWhere('account_number', 'LIKE', '%' . $this->search . '%')
//            ->union(
//                DB::table('sub_accounts')
//                    ->select('id', 'account_name', 'account_number', 'balance', 'status', DB::raw("'sub_accounts' as source"))
//                    ->where('account_name', 'LIKE', '%' . $this->search . '%')
//                    ->orWhere('account_number', 'LIKE', '%' . $this->search . '%')
//            )
            ->get()
            ->toArray();

            $this->isTableVisibleTwo = true; // Show table on search

            $this->showDropdownTwo = true;
        } else {
            $this->showDropdownTwo = false;
            $this->resultsTwo = [];
        }

    }


    public function updatedSearchTwo()
    {
        if (strlen($this->searchTwo) > 0) {
            $searchTerm = '%' . strtolower($this->searchTwo) . '%';

            $accountsQuery = DB::table('accounts')
                ->select('id', 'account_name', 'account_number', 'balance', 'status', DB::raw("'accounts' as source"))
                ->where(function ($query) use ($searchTerm) {
                    $query->where(DB::raw('LOWER(account_name)'), 'LIKE', $searchTerm)
                        ->orWhere(DB::raw('LOWER(account_number)'), 'LIKE', $searchTerm);
                });

            $AccountsModelQuery = DB::table('sub_accounts')
                ->select('id', 'account_name', 'account_number', 'balance', 'status', DB::raw("'sub_accounts' as source"))
                ->where(function ($query) use ($searchTerm) {
                    $query->where(DB::raw('LOWER(account_name)'), 'LIKE', $searchTerm)
                        ->orWhere(DB::raw('LOWER(account_number)'), 'LIKE', $searchTerm);
                });

            // Combine results from both queries with union
            $this->resultsTwo = $accountsQuery->union($AccountsModelQuery)->get()->toArray();

            $this->isTableVisibleTwo = true; // Show table on search
            $this->showDropdownTwo = true;

        } else {
            $this->showDropdownTwo = false;
            $this->resultsTwo = [];
        }
    }


    public function selectAccountTwo($accountId, $source)
    {

        // Fetch account details based on ID and source table
        $account = DB::table($source)->where('id', $accountId)->first();
        if ($account) {
            $this->selectedAccountTwo = (array) $account;
        }
        $this->source_two=$source;

        $this->showDropdownTwo = false;

    }




    public function validateParentAccount($account, $source)
{
    // Check if the source is "accounts"
    if ($source == "accounts") {
        // Fetch the account from the database
        $account = DB::table('accounts')->where('account_number', $account)->first();

        // Check if the account exists
        if ($account) {
            // Check if a related sub-account exists based on the major and category codes
            $sub_account_exists = DB::table('sub_accounts')
                ->where('major_category_code', $account->major_category_code)
                ->where('category_code', $account->category_code)
                ->where('sub_category_code', $account->sub_category_code)
                ->exists();

            return $sub_account_exists; // Return true if exists, false otherwise
        }

        // Return false if the account does not exist
        return false;
    }

    return false;
}


    public function post()
    {
        $this->debit_account =  $this->selectedAccount['account_number'];
        $this->credit_account=$this->selectedAccountTwo['account_number'];
        $source_one=$this->source_one;
        $source_two=$this->source_two;
        $this->validate();
        DB::beginTransaction();

        try {

          $is_debit_valid=  $this->validateParentAccount( $this->debit_account,$source_one);

          $is_credit_valid=  $this->validateParentAccount($this->credit_account,$source_two);


      //    if(!$is_credit_valid && !$is_debit_valid){



            $debited_account_details = DB::table($source_one)->where("account_number", $this->debit_account)->first();
//            if (!$debited_account_details || $debited_account_details->balance < $this->amount) {
//                throw new \Exception('Insufficient funds or invalid debit account.');
//            }

            $credited_account_details = DB::table($source_two)->where("account_number", $this->credit_account)->first();
            if (!$credited_account_details) {
                throw new \Exception('Invalid credit account.');
            }



            $data = [

                'first_account' =>  $debited_account_details,
                'second_account' => $credited_account_details,
                'amount' => $this->amount,
                'narration' =>  $this->narration,

            ];


            $postTransaction= new  TransactionPostingService();

             $out_put= $postTransaction->postTransaction( $data );
            DB::commit();
            session()->flash('message', 'Transaction posted successfully and awaiting approval.');


    // else{

    //     session()->flash('error', 'Invalid Transaction');



        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Transaction failed: ' . $e->getMessage());
        }
    }



    private function postTransaction($reference_number, $debited_account, $credited_account)
    {
        // Use TransactionPostingService for double-entry
        $transactionService = new TransactionPostingService();
        $transactionService->postTransaction([
            'first_account' => $debited_account->account_number,
            'second_account' => $credited_account->account_number,
            'amount' => $this->amount,
            'narration' => $this->narration,
        ]);
        // Debit entry
        $debited_new_balance = $debited_account->balance - $this->amount;
        $this->debit($reference_number, $debited_account, $credited_account, $debited_new_balance);
        // Credit entry
        $credited_new_balance = $credited_account->balance + $this->amount;
        $this->credit($reference_number, $debited_account, $credited_account, $credited_new_balance);
        // Remove direct balance updates
        // AccountsModel::where('account_number', $debited_account->account_number)->update(['balance' => $debited_new_balance]);
        // AccountsModel::where('account_number', $credited_account->account_number)->update(['balance' => $credited_new_balance]);
    }

    private function debit($reference_number, $debited_account, $credited_account, $debited_new_balance)
    {
        general_ledger::create([
            'record_on_account_number' => $debited_account->account_number,
            'record_on_account_number_balance' => $debited_new_balance,
            'major_category_code' => $debited_account->major_category_code,
            'category_code' => $debited_account->category_code,
            'sub_category_code' => $debited_account->sub_category_code,
            'sender_name' => $debited_account->account_name,
            'beneficiary_name' => $credited_account->account_name,
            'sender_account_number' => $debited_account->account_number,
            'beneficiary_account_number' => $credited_account->account_number,
            'narration' => 'MANUAL POSTING: ' . $this->narration,
            'credit' => 0,
            'debit' => $this->amount,
            'reference_number' => $reference_number,
            'trans_status' => 'Pending Approval', // Status updated once approved
            'trans_status_description' => 'Awaiting Approval',
            'payment_status' => 'Pending',
            'recon_status' => 'Pending',
        ]);
    }

    private function credit($reference_number, $debited_account, $credited_account, $credited_new_balance)
    {
        general_ledger::create([
            'record_on_account_number' => $credited_account->account_number,
            'record_on_account_number_balance' => $credited_new_balance,
            'major_category_code' => $credited_account->major_category_code,
            'category_code' => $credited_account->category_code,
            'sub_category_code' => $credited_account->sub_category_code,
            'sender_name' => $debited_account->account_name,
            'beneficiary_name' => $credited_account->account_name,
            'sender_account_number' => $debited_account->account_number,
            'beneficiary_account_number' => $credited_account->account_number,
            'narration' => 'MANUAL POSTING: ' . $this->narration,
            'credit' => $this->amount,
            'debit' => 0,
            'reference_number' => $reference_number,
            'trans_status' => 'Pending Approval',
            'trans_status_description' => 'Awaiting Approval',
            'payment_status' => 'Pending',
            'recon_status' => 'Pending',
        ]);
    }

    private function logAudit($action, $reference_number)
    {
        DB::table('audit_logs')->insert([
            'action' => $action,
            'reference_number' => $reference_number,
            'user_id' => Auth::id(),
            'ip_address' => request()->ip(),
            'created_at' => now(),
        ]);
    }

    private function resetInputFields()
    {
        $this->debit_category = null;
        $this->debit_category_code = null;
        $this->debit_account = null;
        $this->credit_category = null;
        $this->credit_category_code = null;
        $this->credit_account = null;
        $this->amount = null;
        $this->narration = null;
    }



    // Transaction reversal logic
    public function reverseTransaction($reference_number)
    {
        $transaction = general_ledger::where('reference_number', $reference_number)->first();

        if (!$transaction) {
            session()->flash('error', 'Transaction not found.');
            return;
        }

        DB::beginTransaction();
        try {
            // Reverse the debit and credit amounts
            $this->credit($reference_number, $transaction->beneficiary_account_number, $transaction->sender_account_number, -$transaction->credit);
            $this->debit($reference_number, $transaction->beneficiary_account_number, $transaction->sender_account_number, -$transaction->debit);

            // Update audit log for the reversal
            $this->logAudit('Transaction Reversed', $reference_number);

            DB::commit();
            session()->flash('message', 'Transaction reversed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Failed to reverse transaction: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $gl_accounts = DB::table('GL_accounts')->get();
        $debit_sub_categories = [];
        $debit_accounts = [];
        $credit_sub_categories = [];
        $credit_accounts = [];

        if ($this->debit_category) {
            $debit_sub_categories = DB::table($this->debit_category)->get();
        }

        if ($this->debit_category_code) {
            $debit_accounts = DB::table('accounts')->where('category_code', $this->debit_category_code)->get();
        }

        if ($this->credit_category) {
            $credit_sub_categories = DB::table($this->credit_category)->get();
        }

        if ($this->credit_category_code) {
            $credit_accounts = DB::table('accounts')->where('category_code', $this->credit_category_code)->get();
        }

        return view('livewire.accounting.manual-posting', [
            'gl_accounts' => $gl_accounts,
            'debit_sub_categories' => $debit_sub_categories,
            'debit_accounts' => $debit_accounts,
            'credit_sub_categories' => $credit_sub_categories,
            'credit_accounts' => $credit_accounts,
        ]);
    }
}
