<?php

namespace App\Http\Livewire\Savings;

use App\Models\sub_products;
use Illuminate\Support\Facades\Config;
use Livewire\Component;

use App\Models\SavingsModel;
use App\Models\approvals;
use App\Models\AccountsModel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\TransactionPostingService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use App\Models\ClientsModel;
use App\Services\AccountCreationService;

use App\Models\TeamUser;

use Livewire\WithFileUploads;
use App\Models\issured_savings;

use App\Models\general_ledger;

use Livewire\WithPagination;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Services\SmsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

use App\Models\Client;

use App\Models\general_ledger as GeneralLedgerModel;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Exception;
use App\Services\MembershipVerificationService;
use App\Models\BankAccount;
use App\Traits\Livewire\WithModulePermissions;

class Savings extends Component
{
    use WithPagination;
    use WithFileUploads;
    use WithModulePermissions;

    public $tab_id = '10';
    public $title = 'Savings Management';

    // Dashboard Properties
    public $totalSavings = 0;
    public $activeAccounts = 0;
    public $inactiveAccounts = 0;
    public $totalProducts = 0;
    public $recentTransactions = [];
    public $monthlySavings = [];
    public $topSavers = [];
    public $savingsByProduct = [];

    // Filter Properties
    public $search = '';
    public $selectedProduct = '';
    public $statusFilter = '';
    public $dateFrom = '';
    public $dateTo = '';

    // Modal Properties
    public $showIssueNewSavings = false;
    public $showEditSavingsAccount = false;
    public $showDeleteSavingsAccount = false;
    public $showPendingTransactions = false;
    public $showAdjustBalance = false;
    public $showAuditLogs = false;

    // Form Properties
    public $member;
    public $product;
    public $number_of_savings;
    public $linked_savings_account;
    public $account_number;

    public $nominal_price;
    public $selected = 10;
    public $accountSelected;
    public $sub_product_number;
    public $savingsAvailable;
    public $amount;
    public $notes;
    public $bank;

    public $memberDetails;
    public $memberName;
    public $reference_number;
    public $availableProducts = [];

    public $activeSavingsCount;
    public $inactiveSavingsCount;

    public $name;
    public $region;
    public $wilaya;
    public $membershipNumber;
    public $parentSavingsAccount;
    public $pendingSavingsAccount;
    public $pendingSavingsAccountname;
    public $SavingsAccount;
    public $showAddSavingsAccount;

    public $email;
    public $Savingsstatus;
    public $permission = 'BLOCKED';
    public $password;

    public $deposit_charge_min_value;


    // Loading States
    public $isLoading = false;
    public $isSubmitting = false;
    public $isProcessing = false;

    // Messages
    public $successMessage = '';
    public $errorMessage = '';
    public $validationErrors = [];

    // Modal States
    public $showCreateNewSavingsAccount = false;

    // Form Inputs
    public $clientNumber;
    public $productId;
    public $accountNumber;
    public $accountName;
    public $balance;

    // Receive Savings Properties
    public $showReceiveSavingsModal = false;
    public $selectedAccount;
    public $paymentMethod = 'cash'; // 'cash' or 'bank'
    public $selectedBank;
    public $referenceNumber;
    public $depositDate;
    public $depositTime;
    public $depositorName;
    public $narration;
    public $verifiedMember = null;
    public $memberAccounts = [];
    public $bankAccounts = [];
    public $selectedBankDetails = null;

    // Withdraw Savings Properties
    public $showWithdrawSavingsModal = false;
    public $withdrawMembershipNumber;
    public $withdrawSelectedAccount;
    public $withdrawAmount;
    public $withdrawPaymentMethod = 'cash'; // 'cash', 'internal_transfer', 'tips_mno', 'tips_bank'
    public $withdrawSelectedBank;
    public $withdrawSourceAccount;
    public $withdrawReferenceNumber;
    public $withdrawDate;
    public $withdrawTime;
    public $withdrawerName;
    public $withdrawNarration;
    public $withdrawVerifiedMember = null;
    public $withdrawMemberAccounts = [];
    public $withdrawBankAccounts = [];
    public $withdrawSelectedBankDetails = null;
    public $withdrawSelectedAccountBalance = 0;

    // Receipt Properties
    public $showReceiptModal = false;
    public $receiptData = null;

    // Additional withdrawal properties for different methods
    public $withdrawNbcAccount;
    public $withdrawAccountHolderName;
    public $withdrawMnoProvider;
    public $withdrawPhoneNumber;
    public $withdrawWalletHolderName;
    public $withdrawBankCode;
    public $withdrawBankAccountNumber;
    public $withdrawBankAccountHolderName;
    
    // OTP Properties
    public $withdrawOtpCode = '';
    public $generatedWithdrawOTP = '';
    public $withdrawOtpSent = false;
    public $withdrawOtpSentTime = null;
    public $withdrawOtpVerified = false;

    protected $rules = [
        'member'=> 'required|min:1',
        'reference_number'=>'required',
        'product'=> 'required|min:1',
        'number_of_savings'=> 'required|min:1',
        'linked_savings_account'=> 'required|min:1',
        'account_number'=> 'required|min:1',
        'membershipNumber' => 'required|min:1',
        'selectedAccount' => 'required|min:1',
        'amount' => 'required|numeric|min:0.01',
        'paymentMethod' => 'required|in:cash,bank',
        'selectedBank' => 'required_if:paymentMethod,bank',
        'referenceNumber' => 'required_if:paymentMethod,bank',
        'depositDate' => 'required_if:paymentMethod,bank|date',
        'depositTime' => 'required_if:paymentMethod,bank',
        'depositorName' => 'required',
        'narration' => 'required|min:3',
        'withdrawMembershipNumber' => 'required|min:1',
        'withdrawSelectedAccount' => 'required|min:1',
        'withdrawAmount' => 'required|numeric|min:0.01',
        'withdrawPaymentMethod' => 'required|in:cash,internal_transfer,tips_mno,tips_bank',
        'withdrawNarration' => 'required|string|max:255',
        // Internal transfer validation
        'withdrawSourceAccount' => 'required_if:withdrawPaymentMethod,internal_transfer|exists:bank_accounts,id',
        // TIPS MNO validation
        'withdrawMnoProvider' => 'required_if:withdrawPaymentMethod,tips_mno|string|max:255',
        'withdrawPhoneNumber' => 'required_if:withdrawPaymentMethod,tips_mno|string|max:255',
        // TIPS Bank validation
        'withdrawBankCode' => 'required_if:withdrawPaymentMethod,tips_bank|string|max:255',
        'withdrawBankAccountNumber' => 'required_if:withdrawPaymentMethod,tips_bank|string|max:255',
        'withdrawBankAccountHolderName' => 'required_if:withdrawPaymentMethod,tips_bank|string|max:255',
        // Common validation for non-cash methods
        'withdrawReferenceNumber' => 'required_if:withdrawPaymentMethod,internal_transfer,tips_mno,tips_bank|string|max:255',
        'withdrawDate' => 'required_if:withdrawPaymentMethod,internal_transfer,tips_mno,tips_bank|date',
        'withdrawTime' => 'required_if:withdrawPaymentMethod,internal_transfer,tips_mno,tips_bank|date_format:H:i'
    ];

    protected $listeners = [
        'showUsersList' => 'showUsersList',
        'blockSavingsAccount' => 'blockSavingsAccountModal',
        'editSavingsAccount' => 'editSavingsAccountModal'
    ];

    public function mount()
    {
        // Initialize the permission system for this module
        $this->initializeWithModulePermissions();
        $this->loadStatistics();
        $this->loadAvailableProducts();
    }

    public function boot()
    {
        //$this->authorize('view-savings');
        $this->loadStatistics();
        $this->loadAvailableProducts();
    }


    public function showSavingsBulkUploadPage()
    {
        if (!$this->authorize('export', 'You do not have permission to upload savings data')) {
            return;
        }
        $this->selected = 12;
    }

    protected function loadStatistics()
    {
        try {
            $this->isLoading = true;

            // Total Savings
            $this->totalSavings = DB::table('accounts')
                ->whereNotNull('client_number')
                ->where('product_number', 2000)
                ->where('client_number', '!=', '0000')
                ->sum(DB::raw('CAST(balance AS DECIMAL(15,2))'));

            // Active Accounts
            $this->activeAccounts = DB::table('accounts')
                ->whereNotNull('client_number')
                ->where('product_number', 2000)
                ->where('status', 'ACTIVE')
                ->where('client_number', '!=', '0000')
                ->count();

            // Inactive Accounts
            $this->inactiveAccounts = DB::table('accounts')
                ->whereNotNull('client_number')
                ->where('product_number', 2000)
                ->where('status', '!=', 'ACTIVE')
                ->where('client_number', '!=', '0000')
                ->count();

            // Total Products
            $this->totalProducts = DB::table('sub_products')
                ->where('product_type', 2000)
                ->where('status', 'ACTIVE')
                ->count();

            // Recent Transactions
            $this->recentTransactions = general_ledger::with(['account.client'])
                ->whereHas('account', function ($query) {
                    $query->where('product_number', 2000);
                })
                ->latest()
                ->take(5)
                ->get();

            // Monthly Savings - Fixed: Use credit instead of amount
            $this->monthlySavings = general_ledger::whereHas('account', function ($query) {
                    $query->where('product_number', 2000);
                })
                //->where('transaction_type', 'CREDIT')
                ->whereYear('created_at', Carbon::now()->year)
                ->selectRaw('EXTRACT(MONTH FROM created_at) as month, SUM(credit) as total')
                ->groupBy('month')
                ->get();

            // Top Savers
            $this->topSavers = AccountsModel::with('client')
                ->whereNotNull('client_number')
                ->where('product_number', 2000)
                ->where('status', 'ACTIVE')
                ->where('client_number', '!=', '0000')
                ->where('balance', '>=', 0)
                ->orderBy('balance', 'desc')
                ->take(5)
                ->get();

            // Savings by Product - Fixed: Get product info without join since sub_product fields are NULL
            $this->savingsByProduct = DB::table('sub_products')
                ->where('product_type', 2000)
                ->where('status', 'ACTIVE')
                ->select('product_name', DB::raw('(SELECT SUM(CAST(balance AS DECIMAL(15,2))) FROM accounts WHERE product_number = \'2000\') as total_balance'))
                ->get();
           

        } catch (Exception $e) {
            Log::error('Error loading savings statistics: ' . $e->getMessage());
            $this->errorMessage = 'Failed to load statistics. Please try again.';
        } finally {
            $this->isLoading = false;
        }
    }

    public function showSavingsFullReportPage()
    {
        if (!$this->authorize('view', 'You do not have permission to view savings reports')) {
            return;
        }
        $this->selected = 11;
    }

    protected function loadAvailableProducts()
    {
        try {
            $this->availableProducts = sub_products::where('product_type', 2000)
            //->where('status', 'ACTIVE')
            ->get();
//dd($this->availableProducts);

        } catch (Exception $e) {
            Log::error('Error loading available products: ' . $e->getMessage());
            $this->errorMessage = 'Failed to load available products. Please try again.';
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedSelectedProduct()
    {
        $this->resetPage();
    }

    public function updatedStatusFilter()
    {
        $this->resetPage();
    }

    public function setAccount($id){
        $account_number = AccountsModel::where('id',$id)->value('account_number');
        $this->accountSelected = $account_number;

        $this->product = AccountsModel::where('account_number', $account_number)->value('sub_category_code');
    }

    public function showAddSavingsAccountModal($selected){
        $randomNumber = rand(9000, 9999);
        $this->membershipNumber= str_pad($randomNumber, 4, '0', STR_PAD_LEFT);
        $this->selected = $selected;
        $this->showAddSavingsAccount = true;
    }

    public function closeShowAddSavingsAccount(){
        $this->resetData();
        $this->showAddSavingsAccount = false;
    }

    public function generate_account_number_two($branch_code, $product_code) {
        do {
            // Generate a 5-digit random number for the unique account identifier
            $unique_identifier = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

            // Concatenate branch code, unique identifier, and product code
            $partial_account_number = $branch_code . $unique_identifier . $product_code;

            // Calculate the checksum digit
            $checksum = (10 - $this->luhn_checksum($partial_account_number . '0')) % 10;

            // Form the final 12-digit account number
            $full_account_number = $partial_account_number . $checksum;

            // Check for uniqueness using Laravel's Eloquent model
            $is_unique = !AccountsModel::where('account_number', $full_account_number)->exists();

        } while (!$is_unique);

        return $full_account_number;
    }

    public function createNewAccount($major_category_code,$category_code,$sub_category_code,$account_name,$client_number )
    {
        //dd();

        // Generate account number
        $account_number = $this->generate_account_number(auth()->user()->branch, $sub_category_code);

        // Create a new account entry in the AccountsModel
        $account_number =

            [
            'account_use' => 'external',
            'institution_number' => auth()->user()->institution_id,
            'branch_number' => auth()->user()->branch,
            'major_category_code' => $major_category_code,
            'category_code' => $category_code,
            'sub_category_code' => $this->product,
            'account_name' => $account_name,
            'client_number'=>$client_number,
            'account_number' => $account_number,
            'notes' => 'account on member on boarding ',
            'bank_id' => null,
            'mirror_account' => null,
            'account_level' => '3',
        ];

        return $account_number;
    }

    public function showIssueNewSavingsModal($selected){        
        if($selected == 1) {
            $randomNumber = rand(9000, 9999);
            $this->membershipNumber = str_pad($randomNumber, 4, '0', STR_PAD_LEFT);
            $this->selected = $selected;
            $this->showCreateNewSavingsAccount = true;
            $this->resetData();
        } elseif($selected == 2) {
            $randomNumber = rand(9000, 9999);
            $this->membershipNumber = str_pad($randomNumber, 4, '0', STR_PAD_LEFT);
            $this->selected = $selected;
            $this->showIssueNewSavings = true;
            $this->resetData();
        } else {
            $this->selected = $selected;
        }
    }

    public function closeShowIssueNewSavings(){
        $this->resetData();
        $this->showIssueNewSavings = false;
    }

    public function updatedSavingsAccount(){

        $SavingsAccountData = SavingsModel::select('membershipNumber', 'name', 'region', 'wilaya', 'email')
        ->where('id', '=', $this->SavingsAccount)
        ->get();

    foreach ($SavingsAccountData as $SavingsAccount){
        $this->membershipNumber=$SavingsAccount->membershipNumber;
        $this->name=$SavingsAccount->name;
        $this->region=$SavingsAccount->region;
        $this->wilaya=$SavingsAccount->wilaya;
        $this->email=$SavingsAccount->email;
        $this->status=$SavingsAccount->status;
    }

    }




    public function updateSavingsAccount(){

        $user = auth()->user();


        $data = [
            'membershipNumber' =>$this->membershipNumber,
            'name' =>$this->name,
            'region' =>$this->region,
            'wilaya' =>$this->wilaya,
            'email' =>$this->email
        ];

        $update_value = approvals::updateOrCreate(
            [
                'process_id' => $this->SavingsAccount,
                'user_id' => Auth::user()->id

                ],
            [
                'institution' => $this->SavingsAccount,
                'process_name' => 'editSavingsAccount',
                'process_description' => 'has edited a SavingsAccount',
                'approval_process_description' => 'has approved changes to a SavingsAccount',
                'process_code' => '02',
                'process_id' => $this->SavingsAccount,
                'process_status' => 'Pending',
                'user_id'  => Auth::user()->id,
                'team_id'  => $this->SavingsAccount,
                'edit_package'=> json_encode($data)
            ]
        );
        Session::flash('message', 'Awaiting approval');
        Session::flash('alert-class', 'alert-success');
        $this->resetData();
        $this->showAddSavingsAccount = false;
    }
    function luhn_checksum($number) {
        $digits = str_split($number);
        $sum = 0;
        $alt = false;
        for ($i = count($digits) - 1; $i >= 0; $i--) {
            $n = $digits[$i];
            if ($alt) {
                $n *= 2;
                if ($n > 9) {
                    $n -= 9;
                }
            }
            $sum += $n;
            $alt = !$alt;
        }
        return $sum % 10;
    }
    function generate_account_number($branch_code, $product_code) {
        do {
            // Generate a 5-digit random number for the unique account identifier
            $unique_identifier = str_pad(rand(0, 99999), 5, '0', STR_PAD_LEFT);

            // Concatenate branch code, unique identifier, and product code
            $partial_account_number = $branch_code . $unique_identifier . $product_code;

            // Calculate the checksum digit
            $checksum = (10 - $this->luhn_checksum($partial_account_number . '0')) % 10;

            // Form the final 12-digit account number
            $full_account_number = $partial_account_number . $checksum;

            // Check for uniqueness using Laravel's Eloquent model
            $is_unique = !AccountsModel::where('account_number', $full_account_number)->exists();

        } while (!$is_unique);

        return $full_account_number;
    }

    public function addSavingsAccount()
    {
        // Check if the savings account already exists
        $existingAccount = AccountsModel::where('client_number', $this->member)
                            ->where('product_number', 2000)
                               ->where('sub_category_code', $this->product)
                                      ->exists();



        if ($existingAccount) {
            Session::flash('message_fail', 'Your account already exists!');
            Session::flash('alert-class', 'alert-success');
            return;
        }

        // Fetch the branch code, padded to 2 digits
        $branchCode = Auth::user()->branch;

        // Generate the new account number
        $accountNumber = $this->generate_account_number($branchCode, $this->product);

        // Get the full member name
        $memberName = ClientsModel::where('client_number', $this->member)
                                  ->selectRaw("CONCAT(first_name, ' ', middle_name, ' ', last_name) AS full_name")
                                  ->value('full_name');

        $parent=AccountsModel::where('sub_category_code',$this->product)->first();

        $category = 'liability_accounts';

        //dd($this->member);

        // Prepare the data package for saving the new account
        $newAccountData = [
            'account_use' => 'external',
            'institution_number' => '1000',
            'branch_number' => Auth::user()->branch,
            'client_number' => $this->member,
            'product_number' => '2000',
            'sub_product_number'=>  $this->product,
            'major_category_code'=> $parent->major_category_code,
            'category_code'=>  $parent->category_code,
            'sub_category_code'=>  $parent->sub_category_code,
            'balance'=>  0,
            'account_name'=> $memberName,
            'account_number'=>$accountNumber,
            'account_level' => '3',
            'parent_account_number' => $parent->account_number,
            'type' => $category
        ];

        // Encode the data for approvals
        $editPackage = json_encode($newAccountData);

        // Create an approval record
        approvals::create([
            'institution' => '',
            'process_name' => 'createSavingAccount',
            'process_description' => Auth::user()->name . ' has added a new saving account for ' . $memberName,
            'approval_process_description' => 'has approved a new account',
            'process_code' => '04',
            'process_status' => 'Pending',
            'user_id' => Auth::user()->id,
            'team_id' => "",
            'edit_package' => $editPackage
        ]);

        // Reset the form data and close the modal
        $this->resetData2();
        $this->closeShowAddSavingsAccount();

        // Display success message
        Session::flash('message', 'The process has been completed!');
        Session::flash('alert-class', 'alert-success');
    }

    public function resetData2()
    {
        // Code to reset the data goes here
        // You can define the logic to reset the desired data properties or perform any necessary actions
        // For example:
        $this->member = null;
        $this->product = null;
        // Reset other data properties as needed
    }

    public function save()
    {
        // Start database transaction
        DB::beginTransaction();

        try {
            // Ensure all operations are within the transaction scope
            $bankAccountNumber = $this->bank;
            $selectedAccount = $this->accountSelected;
            $amount = (double) $this->amount;


            $institutionId = 1;
            $referenceNumber = time();


           //dd($bankAccountNumber, $selectedAccount );



            $debited_account = AccountsModel::where('account_number', $bankAccountNumber)->first();
            //$debited_account ='27186310028';
            $credited_account  =AccountsModel::where('account_number', $selectedAccount)->first();

            //dd($debited_account,$credited_account);


                   // debit suspense account
                   $data = [
                    'first_account' => $debited_account,
                    'second_account' => $credited_account,
                    'amount' => $amount,
                    'narration' => $this->notes,
                ];
              // dd($data);
              //  Ensure $this->transactionService is initialized
               $transactionServicex = new TransactionPostingService();

                $response = $transactionServicex->postTransaction($data);



            // Reset data after successful operation
            $this->resetData();

            // Commit the transaction
            DB::commit();

            // Flash success message
            Session::flash('message', 'Savings has been successfully issued!');
            Session::flash('alert-class', 'alert-success');

            // Close modal or redirect
            $this->closeShowIssueNewSavings();
        } catch (\Exception $e) {
            DB::rollBack();

            Session::flash('message', 'Transaction failed! Please try again.');
            Session::flash('alert-class', 'alert-danger');

        }
    }

    public function sendApproval($id,$msg,$code){



        approvals::create([
            'institution' => "",
            'process_name' => 'Deposit',
            'process_description' => $msg,
            'approval_process_description' => 'has approved a transaction',
            'process_code' => $code,
            'process_id' => $id,
            'process_status' => 'Pending',
            'user_id'  => Auth::user()->id,
            'team_id'  => ""
        ]);

    }

    public function resetData()
    {
        $this->member = '';
        $this->product = '';
        $this->accountSelected = '';
        $this->amount = '';
        $this->account_number = '';
        $this->notes = '';
        $this->bank = '';
        $this->reference_number = '';
    }

    public function menuItemClicked($tabId){
        $this->tab_id = $tabId;
        if($tabId == '1'){
            $this->title = 'Savings list';
        }
        if($tabId == '2'){
            $this->title = 'Enter new SavingsAccount details';
        }
    }

    public function createNewSavingsAccount()
    {
        $this->showCreateNewSavingsAccount = true;
    }

    public function blockSavingsAccountModal($id)
    {
        $this->showDeleteSavingsAccount = true;
        $this->SavingsAccountSelected = $id;
    }

    public function editSavingsAccountModal($id)
    {
        $this->showEditSavingsAccount = true;
        $this->pendingSavingsAccount = $id;
        $this->SavingsAccount = $id;
        $this->pendingSavingsAccountname = SavingsModel::where('id',$id)->value('name');
        $this->updatedSavingsAccount();
    }

    public function closeModal(){
        $this->showCreateNewSavingsAccount = false;
        $this->showDeleteSavingsAccount = false;
        $this->showEditSavingsAccount = false;
    }

    public function confirmPassword(): void
    {
        // Check if password matches for logged-in user
        if (Hash::check($this->password, auth()->user()->password)) {
            //dd('password matches');
            $this->delete();
        } else {
            //dd('password does not match');
            Session::flash('message', 'This password does not match our records');
            Session::flash('alert-class', 'alert-warning');
        }
        $this->resetPassword();
    }

    public function resetPassword(): void
    {
        $this->password = null;
    }

    public function delete(): void
    {
        $user = User::where('id',$this->userSelected)->first();
        $action = '';
        if ($user) {

            if($this->permission == 'BLOCKED'){
                $action = 'blockUser';
            }
            if($this->permission == 'ACTIVE'){
                $action = 'activateUser';
            }
            if($this->permission == 'DELETED'){
                $action = 'deleteUser';
            }

            $update_value = approvals::updateOrCreate(
                [
                    'process_id' => $this->userSelected,
                    'user_id' => Auth::user()->id

                ],
                [
                    'institution' => null,
                    'process_name' => $action,
                    'process_description' => $this->permission.' user - '.$user->name,
                    'approval_process_description' => null,
                    'process_code' => '29',
                    'process_id' => $this->userSelected,
                    'process_status' => $this->permission,
                    'approval_status' => 'PENDING',
                    'user_id'  => Auth::user()->id,
                    'team_id'  => null,
                    'edit_package'=> null
                ]
            );


            // Delete the record
            //$node->delete();
            // Add your logic here for successful deletion
            Session::flash('message', 'Awaiting approval');
            Session::flash('alert-class', 'alert-success');

            $this->closeModal();
            $this->render();


        } else {
            // Handle case where record was not found
            // Add your logic here
            Session::flash('message', 'Node error');
            Session::flash('alert-class', 'alert-warning');
        }
    }

    public function render()
    {      
        $this->loadAvailableProducts();  
        return view('livewire.savings.savings', array_merge(
            $this->permissions,
            [
                'accounts' => $this->getFilteredAccounts(),
                'products' => $this->availableProducts,
                'permissions' => $this->permissions
            ]
        ));
    }

    protected function getFilteredAccounts()
    {
        try {
            $query = AccountsModel::with(['client', 'shareProduct'])
                ->whereNotNull('client_number')
                ->where('product_number', 2000)
                ->where('client_number', '!=', '0000');

            if ($this->search) {
                $query->where(function ($q) {
                    $q->where('account_number', 'like', '%' . $this->search . '%')
                        ->orWhere('account_name', 'like', '%' . $this->search . '%')
                        ->orWhereHas('client', function ($q) {
                            $q->where('client_number', 'like', '%' . $this->search . '%')
                                ->orWhere('first_name', 'like', '%' . $this->search . '%')
                                ->orWhere('last_name', 'like', '%' . $this->search . '%');
                        });
                });
            }

            if ($this->selectedProduct) {
                $query->where('product_number', $this->selectedProduct);
            }

            if ($this->statusFilter) {
                $query->where('status', $this->statusFilter);
            }

            return $query->paginate(10);

        } catch (Exception $e) {
            Log::error('Error filtering accounts: ' . $e->getMessage());
            $this->errorMessage = 'Failed to filter accounts. Please try again.';
            return collect();
        }
    }

    // public function createSavingsAccount()
    // {
    //     $this->validate([
    //         'clientNumber' => 'required|exists:clients,client_number',
    //         'productId' => 'required|exists:sub_products,sub_product_id',
    //         'accountNumber' => 'required|unique:accounts,account_number',
    //         'accountName' => 'required|string|max:255',
    //         'balance' => 'required|numeric|min:0'
    //     ]);

    //     try {
    //         DB::beginTransaction();

    //         // Create the account
    //         $account = AccountsModel::create([
    //             'account_number' => $this->accountNumber,
    //             'account_name' => $this->accountName,
    //             'client_number' => $this->clientNumber,
    //             'product_number' => $this->productId,
    //             'balance' => $this->balance,
    //             'status' => 'ACTIVE'
    //         ]);

    //         // If there's an initial balance, create a transaction
    //         if ($this->balance > 0) {
    //             $creditService = new \App\Services\CreditService();
    //             $creditService->credit(
    //                 'INIT-' . time(), // reference
    //                 '0000', // source_account_number (system account)
    //                 $this->accountNumber, // destination_account_number
    //                 $this->balance, // credit amount
    //                 'Initial deposit for account ' . $this->accountNumber, // narration
    //                 $this->balance, // running_balance
    //                 'System Account', // source_account_name
    //                 $this->accountName // destination_account_name
    //             );
    //         }

    //         DB::commit();

    //         $this->showCreateNewSavingsAccount = false;
    //         $this->resetForm();
    //         session()->flash('success', 'Savings account created successfully.');

    //     } catch (Exception $e) {
    //         DB::rollBack();
    //         Log::error('Error creating savings account: ' . $e->getMessage());
    //         session()->flash('error', 'Failed to create savings account. Please try again.');
    //     }
    // }


    public function validateMemberNumber()
    {
        
        $this->resetErrorBag('member');
        
        //Remove any non-numeric characters
        //$this->member = preg_replace('/[^0-9]/', '', $this->member);
        
        if (empty($this->member)) {
            $this->memberDetails = null;
            $this->memberName = null;
            return;
        }
        
        if (strlen($this->member) > 5) {
            $this->addError('member', 'Member number must be exactly 5 digits');
            $this->memberDetails = null;
            $this->memberName = null;
            return;
        }

      
        
        try {
            // dd($this->member);
            $member = ClientsModel::where('client_number', $this->member)
                //->where('status', 'ACTIVE')
                ->first();

                // dd($member);
            
            if (!$member) {
                $this->addError('member', 'Member not found or not active');
                $this->memberDetails = null;
                $this->memberName = null;
                return;
            }
            
            $this->memberDetails = $member;
            $this->memberName = trim($member->first_name . ' ' . ($member->middle_name ?? '') . ' ' . $member->last_name);
            
            Log::info('Member validated successfully', [
                'client_number' => $this->member,
                'member_id' => $member->id,
                'member_name' => $this->memberName
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error validating member number', [
                'client_number' => $this->member,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->addError('member', 'Error validating member number');
            $this->memberDetails = null;
            $this->memberName = null;
        }
    }
    public function createSavingsAccount(){ 
        if (!$this->authorize('create', 'You do not have permission to create savings accounts')) {
            return;
        }

       $productAccount = sub_products::where('id',$this->productId)->first();
        try{
            DB::beginTransaction();
            $min_code=AccountsModel::where('account_number',$productAccount->product_account)->value('sub_category_code');
            $existingAccount = AccountsModel::where('client_number', $this->member)
            ->where('sub_category_code', $min_code)
            ->exists();
           
            if ($existingAccount || is_null($min_code)) {
            Session::flash('message_fail', 'Your account already exists!');
            Session::flash('alert-class', 'alert-success');
            return;
            }
            $branch_number = Auth::user()->branch;
            $branch_code = $branch_number;
            $product_code =  $min_code;
            $account_number = $this->generate_account_number($branch_code, $product_code);
            $parent=AccountsModel::where('account_number',$productAccount->product_account)->first();
            $memberName = ClientsModel::where('client_number', $this->member)->value('first_name').' '.ClientsModel::where('client_number', $this->member)->value('middle_name').' '.ClientsModel::where('client_number', $this->member)->value('last_name');

            $accountService = new AccountCreationService();
            $sharesAccount = $accountService->createAccount([
                'account_use' => 'external',
                'account_name' => $parent->account_name.':'.$memberName,
                'type' => 'capital_accounts',
                'product_number' => '2000',
                'member_number' => $this->member,
                'branch_number' => auth()->user()->branch
            ], $parent->account_number);


            $newAccountData = [
                'account_use' => 'external',
                'institution_number'=> '1000',
                'branch_number'=> Auth::user()->branch,
                'client_number'=> $this->member,
                'product_number'=> '2000',
                'sub_product_number'=>  $this->productId,
                'major_category_code'=> $parent->major_category_code,
                'category_code'=>  $parent->category_code,
                'sub_category_code'=>  $parent->sub_category_code,
                'balance'=>  0,
                'account_name'=> $memberName,
                'account_number'=>$account_number,
                'parent_account_number'=>$productAccount->product_account,
            ];
            $editPackage = json_encode($newAccountData);
            approvals::create([
                'process_name' => 'create_savings_account',
                'process_description' => Auth::user()->name .  ' has added a new savings account ' .$memberName,
                'approval_process_description' => 'Savings issuance approval required',
                'process_code' => 'ACC_CREATE',
                'process_id' => $sharesAccount->id,
                'process_status' => 'PENDING',
                'user_id' => auth()->user()->id,
                'approver_id' => null,
                'approval_status' => 'PENDING',
                'edit_package' => $editPackage
            ]);
            
            Session::flash('message', 'The process has been completed! Awaiting approval');
            DB::commit();            
            $this->showCreateNewSavingsAccount = false;
            $this->resetData();
        }catch(\Exception $e){
            DB::rollBack();
            Log::error('Error creating savings account: ' . $e->getMessage());
            Session::flash('message', 'Error creating savings account: ' . $e->getMessage());
            Session::flash('alert-class', 'alert-warning');
            //dd($e->getMessage());
            return ;
        }
    }


    public function resetForm()
    {
        $this->reset([
            'clientNumber',
            'productId',
            'accountNumber',
            'accountName',
            'balance'
        ]);
        $this->resetErrorBag();
    }

    public function showCreateNewSavingsAccount()
    {
        if (!$this->authorize('create', 'You do not have permission to create savings accounts')) {
            return;
        }
        $this->reset([
            'member',
            'productId',
            'accountNumber',
            'accountName',
            'balance'
        ]);
        $this->resetErrorBag();
        $this->memberDetails = null;
        $this->memberName = null;        
        $this->showCreateNewSavingsAccount = true;
    }

    public function showReceiveSavingsModal()
    {
        if (!$this->authorize('deposit', 'You do not have permission to process savings deposits')) {
            return;
        }
        $this->reset([
            'membershipNumber',
            'selectedAccount',
            'amount',
            'paymentMethod',
            'selectedBank',
            'referenceNumber',
            'depositDate',
            'depositTime',
            'depositorName',
            'narration',
            'verifiedMember',
            'memberAccounts',
            'selectedBankDetails'
        ]);
        $this->resetErrorBag();
        $this->showReceiveSavingsModal = true;
    }

    public function verifyMembership()
    {
        $this->validate([
            'membershipNumber' => 'required|min:1'
        ]);

        try {
            $verificationService = app(MembershipVerificationService::class);
            $result = $verificationService->verifyMembership($this->membershipNumber);

            if ($result['exists'] === true) {
                $this->verifiedMember = $result['member'];
                $this->memberAccounts = AccountsModel::where('client_number', $this->membershipNumber)
                    ->where('product_number', '2000')
                    ->where('status', 'ACTIVE')
                    ->get();

                    //dd($this->memberAccounts);
                $this->bankAccounts = BankAccount::where('status', 'ACTIVE')->get();
                
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'success',
                    'message' => $result['message']
                ]);
            } else {
                $this->addError('membershipNumber', $result['message']);
                $this->verifiedMember = null;
                $this->memberAccounts = [];
            }
        } catch (Exception $e) {
            $this->addError('membershipNumber', 'Failed to verify membership. Please try again.');
            Log::error('Membership verification error: ' . $e->getMessage());
            $this->verifiedMember = null;
            $this->memberAccounts = [];
        }
    }

    public function updatedPaymentMethod()
    {
       
        if ($this->paymentMethod === 'cash') {
            $this->referenceNumber = 'CASH-' . strtoupper(uniqid());
            $this->depositDate = now()->format('Y-m-d');
            $this->depositTime = now()->format('H:i');
        }
    }

    public function updatedSelectedBank()
    {
        if ($this->selectedBank) {
            $this->selectedBankDetails = BankAccount::find($this->selectedBank);
        }
    }

    public function submitReceiveSavings()
    {
        if (!$this->authorize('deposit', 'You do not have permission to process savings deposits')) {
            return;
        }
        DB::beginTransaction();
        try{
            $memberAccount = AccountsModel::where('account_number', $this->selectedAccount)->first();
            if(!$memberAccount){
                throw new \Exception('Member account not found.');
            }

            $this->paymentMethod === 'bank'
            ? $this->handleBankDeposit($memberAccount)
            : $this->handleCashDeposit($memberAccount);
            
            // Generate receipt after successful transaction
            $this->receiptData = $this->generateReceiptData($memberAccount);
            
            session()->flash('successMessage', 'Savings received successfully.');  
            DB::commit();   
            $this->showReceiveSavingsModal = false;
            $this->showReceiptModal = true;
            
        }catch(\Exception $e){
            DB::rollBack();           
            Log::error('Error receiving savings: ' . $e->getMessage());
            Session::flash('errorMessage', 'Error receiving savings');
            Session::flash('alert-class', 'alert-warning');
            return;
        }
    }
    
    private function generateReceiptData($memberAccount)
    {
        $receiptNumber = 'RCP-' . strtoupper(uniqid());
        $transactionDate = now();
        
        return [
            'receipt_number' => $receiptNumber,
            'transaction_date' => $transactionDate->format('d/m/Y H:i:s'),
            'member_name' => $this->verifiedMember['name'] ?? 'N/A',
            'member_number' => $this->membershipNumber,
            'account_number' => $this->selectedAccount,
            'account_name' => $memberAccount->account_name,
            'amount' => number_format($this->amount, 2),
            'payment_method' => ucfirst($this->paymentMethod),
            'depositor_name' => $this->depositorName,
            'narration' => $this->narration,
            'reference_number' => $this->referenceNumber,
            'bank_name' => $this->selectedBankDetails->bank_name ?? 'Cash',
            'processed_by' => auth()->user()->name,
            'branch' => auth()->user()->branch_id ?? 1,
            'currency' => 'TZS',
            'transaction_type' => 'Savings Deposit',
            'balance_after' => number_format($memberAccount->balance + $this->amount, 2)
        ];
    }
    
    public function closeReceiptModal()
    {
        $this->showReceiptModal = false;
        $this->receiptData = null;
        $this->resetForm();
    }
    
    public function printReceipt()
    {
        if ($this->receiptData) {
            $this->dispatchBrowserEvent('printReceipt', [
                'receiptData' => $this->receiptData
            ]);
        }
    }


    private function handleBankDeposit($memberAccount)
    {
        // TODO: Implement bank deposit logic
          
             $this->validate([
                 'depositDate' => 'required|date',
                 'depositTime' => 'required|date_format:H:i',
                 'selectedBank' => 'required',
                 'referenceNumber' => 'required|string|max:255',
                 'narration' => 'required|string|max:255',
                 'amount' => 'required|numeric|min:0',
                 'depositorName' => 'required|string|max:255',              
                 'paymentMethod' => 'required|string|in:bank'
             ]);

             if (!empty($this->selectedBankDetails->internal_mirror_account_number) && !empty($this->selectedAccount)) {

                 $totalAmount = $this->amount;                 
                 // Post the transaction using TransactionPostingService
                 $transactionService = new TransactionPostingService();
                 $transactionData = [
                     'first_account' => $this->selectedBankDetails->internal_mirror_account_number, // Debit account 
                     'second_account' => $this->selectedAccount, // Credit account 
                     'amount' => $totalAmount,
                     'narration' => 'Savings deposit : ' . $this->amount . ' : ' . $this->depositorName . ' : ' . $this->selectedBankDetails->bank_name . ' : ' . $this->referenceNumber,
                     'action' => 'savings deposit by bank'
                 ];

                 Log::info('Posting savings deposit transaction', [
                     'transaction_data' => $transactionData
                 ]);

                 $result = $transactionService->postTransaction($transactionData);
                 
                 if ($result['status'] !== 'success') {
                     Log::error('Transaction posting failed', [
                         'error' => $result['message'] ?? 'Unknown error',
                         'transaction_data' => $transactionData
                     ]);
                     throw new \Exception('Failed to post transaction: ' . ($result['message'] ?? 'Unknown error'));
                 }

                 // Create transaction record for bank deposit
                 $this->createTransactionRecord(
                     $memberAccount,
                     $totalAmount,
                     'bank', //bank deposit
                     'savings_deposit',
                     'bank_deposit',
                     $this->narration,
                     $result['reference_number'] ?? null,                       
                     $this->selectedBankDetails->bank_name,
                     $this->referenceNumber
                 );

                 Log::info('Transaction posted successfully', [
                     'transaction_reference' => $result['reference_number'] ?? null,
                     'amount' => $totalAmount
                 ]);
             }
         
    }
    
    private function handleCashDeposit($memberAccount)
    {
        // TODO: Implement cash deposit logic
        
            $this->referenceNumber = 'CASH-' . strtoupper(uniqid());
            $this->depositDate = now()->format('Y-m-d');
            $this->depositTime = now()->format('H:i');

              $this->validate([
                  'depositDate' => 'required|date',
                  'depositTime' => 'required|date_format:H:i',
                  'referenceNumber' => 'required|string|max:255',
                  'narration' => 'required|string|max:255',
                  'amount' => 'required|numeric|min:0',
                  'depositorName' => 'required|string|max:255',
                  'paymentMethod' => 'required|string|in:cash'
              ]);
          
                      
        //TODO: get tellers' linked account
          // get tellers' linked account 
          // $tellerDetails = Teller::where('teller_id', Auth::user()->id)->first();            
          // if(!$tellerDetails){
          //     throw new \Exception('Teller details not found. Please contact administrator.');
          // }          

          //     // Get cash in safe account
          // $cashInSafeAccount = AccountsModel::where('id', $tellerDetails->account_id)                    
          //     ->where('status', 'ACTIVE')
          //     ->first();

          // if (!$cashInSafeAccount) {
          //     throw new \Exception('Cash in safe account not found. Please contact administrator.');
          // }

          // Post cash transaction
          $transactionService = new TransactionPostingService();
          $transactionData = [
              // 'first_account' => $cashInSafeAccount->account_number, // Debit cash account
              'first_account' => '010000010024',
              'second_account' => $this->selectedAccount, // Credit member's account
              'amount' => $this->amount,
              'narration' => 'Cash savings deposit: ' . $this->amount . ' : ' . $this->depositorName . ' : ' . $this->referenceNumber,
              'action' => 'savings deposit by cash'
          ];

          $result = $transactionService->postTransaction($transactionData);
          
          
          if ($result['status'] !== 'success') {
              throw new \Exception('Failed to post cash transaction: ' . ($result['message'] ?? 'Unknown error'));
          }

              // Create transaction record for cash deposit
              $this->createTransactionRecord(
                  $memberAccount,
                  $this->amount,
                  'cash', //cash deposit
                  'savings_deposit',
                  'cash_deposit',
                  $this->narration,
                  $result['reference_number'] ?? null,
                  'Cash',
                  $this->referenceNumber,
              );

          
    }

    /**
     * Create a transaction record in the transactions table
     */
    private function createTransactionRecord($account, $amount, $type, $category, $subcategory, $narration, $reference, $externalSystem = null, $externalReference = null)
    {
        // Get the current balance before the transaction
        $balanceBefore = $account->balance;
        $balanceAfter = $balanceBefore + $amount;

        $transaction = \App\Models\Transaction::create([
            'account_id' => $account->id,
            'amount' => $amount,
            'currency' => 'TZS',
            'type' => $type,
            'transaction_category' => $category,
            'transaction_subcategory' => $subcategory,
            'narration' => $narration,
            'description' => 'Savings deposit processed by ' . auth()->user()->name,
            'reference' => $reference,
            'external_reference' => $externalReference,
            'status' => 'COMPLETED',
            'reconciliation_status' => 'UNRECONCILED',
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'running_balance' => $balanceAfter,
            'external_system' => $externalSystem,
            'external_transaction_id' => $externalReference,
            'initiated_at' => now(),
            'processed_at' => now(),
            'completed_at' => now(),
            'initiated_by' => auth()->id(),
            'processed_by' => auth()->id(),
            'client_ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'is_manual' => true,
            'is_system_generated' => false,
            'requires_approval' => false,
            'is_approved' => true,
            'approved_at' => now(),
            'metadata' => [
                'member_number' => $this->membershipNumber,
                'depositor_name' => $this->depositorName,
                'payment_method' => $this->paymentMethod,
                'deposit_date' => $this->depositDate ?? now()->format('Y-m-d'),
                'deposit_time' => $this->depositTime ?? now()->format('H:i'),
                'bank_name' => $this->selectedBankDetails->bank_name ?? null,
                'bank_account' => $this->selectedBankDetails->account_number ?? null,
            ],
            'tags' => ['savings', 'deposit', $this->paymentMethod],
            'batch_id' => 'SAVINGS_DEPOSIT_' . date('Y-m-d'),
            'process_id' => 'SAVINGS_' . $this->membershipNumber . '_' . time(),
            'regulatory_category' => 'savings_deposit',
            'reporting_period' => date('Y-m'),
            'risk_level' => 'low'
        ]);

        // Create receipt record
        $this->createReceiptRecord($transaction, $account, $amount);

        // Log the audit trail
        $transaction->logAudit(
            'created',
            null,
            'completed',
            'Savings deposit transaction created',
            [
                'amount' => $amount,
                'payment_method' => $this->paymentMethod,
                'member' => $this->membershipNumber,
                'depositor' => $this->depositorName
            ]
        );

        Log::info('Transaction record created', [
            'transaction_id' => $transaction->id,
            'transaction_uuid' => $transaction->transaction_uuid,
            'account' => $account->account_number,
            'amount' => $amount,
            'reference' => $externalReference,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter
        ]);

        return $transaction;
    }
    
    private function createReceiptRecord($transaction, $account, $amount)
    {
        $receiptNumber = 'RCP-' . strtoupper(uniqid());
        
        \App\Models\Receipt::create([
            'receipt_number' => $receiptNumber,
            'transaction_id' => $transaction->id,
            'account_id' => $account->id,
            'member_number' => $this->membershipNumber,
            'member_name' => $this->verifiedMember['name'] ?? 'N/A',
            'amount' => $amount,
            'currency' => 'TZS',
            'payment_method' => $this->paymentMethod,
            'depositor_name' => $this->depositorName,
            'narration' => $this->narration,
            'reference_number' => $this->referenceNumber,
            'bank_name' => $this->selectedBankDetails->bank_name ?? 'Cash',
            'processed_by' => auth()->id(),
            'branch' => auth()->user()->branch_id ?? 1,
            'transaction_type' => 'Savings Deposit',
            'status' => 'GENERATED',
            'generated_at' => now(),
            'printed_at' => null,
            'metadata' => [
                'balance_before' => $account->balance,
                'balance_after' => $account->balance + $amount,
                'deposit_date' => $this->depositDate ?? now()->format('Y-m-d'),
                'deposit_time' => $this->depositTime ?? now()->format('H:i'),
            ]
        ]);
        
        // Update the receipt number in the receipt data
        $this->receiptData['receipt_number'] = $receiptNumber;
    }

    public function showWithdrawSavingsModal()
    {
        if (!$this->authorize('withdraw', 'You do not have permission to process savings withdrawals')) {
            return;
        }
        $this->reset([
            'withdrawMembershipNumber',
            'withdrawSelectedAccount',
            'withdrawAmount',
            'withdrawPaymentMethod',
            'withdrawSelectedBank',
            'withdrawReferenceNumber',
            'withdrawDate',
            'withdrawTime',
            'withdrawerName',
            'withdrawNarration',
            'withdrawVerifiedMember',
            'withdrawMemberAccounts',
            'withdrawSelectedBankDetails',
            'withdrawSelectedAccountBalance'
        ]);
        $this->resetErrorBag();
        $this->showWithdrawSavingsModal = true;
    }

    public function verifyWithdrawMembership()
    {
        $this->validate([
            'withdrawMembershipNumber' => 'required|min:1'
        ]);

        try {
            $verificationService = app(MembershipVerificationService::class);
            $result = $verificationService->verifyMembership($this->withdrawMembershipNumber);

            if ($result['exists'] === true) {
                $this->withdrawVerifiedMember = $result['member'];
                $this->withdrawMemberAccounts = AccountsModel::where('client_number', $this->withdrawMembershipNumber)
                    ->where('product_number', '2000')
                    ->where('status', 'ACTIVE')
                    ->get();
                $this->withdrawBankAccounts = BankAccount::where('status', 'ACTIVE')->get();
                
                $this->dispatchBrowserEvent('notify', [
                    'type' => 'success',
                    'message' => $result['message']
                ]);
            } else {
                $this->addError('withdrawMembershipNumber', $result['message']);
                $this->withdrawVerifiedMember = null;
                $this->withdrawMemberAccounts = [];
            }
        } catch (Exception $e) {
            $this->addError('withdrawMembershipNumber', 'Failed to verify membership. Please try again.');
            Log::error('Withdrawal membership verification error: ' . $e->getMessage());
            $this->withdrawVerifiedMember = null;
            $this->withdrawMemberAccounts = [];
        }
    }

    public function updatedWithdrawPaymentMethod()
    {
        // Reset OTP verification when payment method changes
        $this->withdrawOtpCode = '';
        $this->withdrawOtpSent = false;
        $this->withdrawOtpSentTime = null;
        $this->withdrawOtpVerified = false;
        $this->generatedWithdrawOTP = null;
        
        if ($this->withdrawPaymentMethod === 'cash') {
            $this->withdrawReferenceNumber = 'CASH-' . strtoupper(uniqid());
            $this->withdrawDate = now()->format('Y-m-d');
            $this->withdrawTime = now()->format('H:i');
            
            // Automatically send OTP when cash withdrawal is selected
            if ($this->withdrawVerifiedMember) {
                $this->sendWithdrawOTP();
            }
        } elseif ($this->withdrawPaymentMethod === 'internal_transfer') {
            $this->withdrawReferenceNumber = 'IFT-' . strtoupper(uniqid());
            $this->withdrawDate = now()->format('Y-m-d');
            $this->withdrawTime = now()->format('H:i');
        } elseif ($this->withdrawPaymentMethod === 'tips_mno') {
            $this->withdrawReferenceNumber = 'TIPS-MNO-' . strtoupper(uniqid());
            $this->withdrawDate = now()->format('Y-m-d');
            $this->withdrawTime = now()->format('H:i');
        } elseif ($this->withdrawPaymentMethod === 'tips_bank') {
            $this->withdrawReferenceNumber = 'TIPS-BANK-' . strtoupper(uniqid());
            $this->withdrawDate = now()->format('Y-m-d');
            $this->withdrawTime = now()->format('H:i');
        }
    }

    public function updatedWithdrawSelectedBank()
    {
        if ($this->withdrawSelectedBank) {
            $this->withdrawSelectedBankDetails = BankAccount::find($this->withdrawSelectedBank);
        }
    }

    public function updatedWithdrawSelectedAccount()
    {
        if ($this->withdrawSelectedAccount) {
            $account = AccountsModel::where('account_number', $this->withdrawSelectedAccount)->first();
            if ($account) {
                $this->withdrawSelectedAccountBalance = $account->balance;
            }
        }
    }

    public function submitWithdrawSavings()
    {
        if (!$this->authorize('withdraw', 'You do not have permission to process savings withdrawals')) {
            return;
        }
        try {
            // Validate basic withdrawal requirements
            $this->validate([
                'withdrawSelectedAccount' => 'required|min:1',
                'withdrawAmount' => 'required|numeric|min:0.01',
                'withdrawPaymentMethod' => 'required|in:cash,internal_transfer,tips_mno,tips_bank',
                'withdrawNarration' => 'required|string|max:255'
            ]);

            // Check if account has sufficient balance
            $account = AccountsModel::where('account_number', $this->withdrawSelectedAccount)->first();
            if (!$account) {
                throw new \Exception('Account not found.');
            }

            if ($account->balance < $this->withdrawAmount) {
                $this->addError('withdrawAmount', 'Insufficient balance. Available balance: ' . number_format($account->balance, 2));
                return;
            }

            // For cash withdrawals, verify OTP first
            if ($this->withdrawPaymentMethod === 'cash' && !$this->withdrawOtpVerified) {
                $this->addError('withdrawOtpCode', 'Please verify OTP before processing cash withdrawal.');
                return;
            }

            // Process withdrawal based on payment method
            switch ($this->withdrawPaymentMethod) {
                case 'cash':
                    $this->processCashWithdrawal();
                    break;
                case 'internal_transfer':
                    $this->processInternalTransferWithdrawal();
                    break;
                case 'tips_mno':
                    $this->processTipsMnoWithdrawal();
                    break;
                case 'tips_bank':
                    $this->processTipsBankWithdrawal();
                    break;
                default:
                    throw new \Exception('Invalid withdrawal method.');
            }

            $this->showWithdrawSavingsModal = false;
            $this->resetWithdrawForm();
            session()->flash('success', 'Savings withdrawal processed successfully.');

        } catch (Exception $e) {
            Log::error('Error processing savings withdrawal: ' . $e->getMessage());
            session()->flash('error', 'Failed to process withdrawal. Please try again.');
        }
    }

    public function sendWithdrawOTP()
    {
        if (!$this->withdrawVerifiedMember) {
            session()->flash('error', 'Please verify member first.');
            return;
        }

        try {
            // Generate 6-digit OTP
            $this->generatedWithdrawOTP = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
            
            // Store OTP in cache with 5 minute expiry
            $cacheKey = 'withdrawal_otp_' . $this->withdrawMembershipNumber . '_' . date('YmdHis');
            Cache::put($cacheKey, $this->generatedWithdrawOTP, Carbon::now()->addMinutes(5));
            
            // Get member's details
            $member = ClientsModel::where('client_number', $this->withdrawMembershipNumber)->first();
            $memberPhone = $member->phone_number ?? null;
            $memberEmail = $member->email ?? null;
            $memberName = $member->first_name . ' ' . $member->last_name;
            
            $otpSentVia = [];
            
            // Send OTP via SMS
            if ($memberPhone) {
                $smsMessage = "Dear {$memberName}, your savings withdrawal OTP is: {$this->generatedWithdrawOTP}. Valid for 5 minutes. Do not share with anyone. - SACCOS";
                
                try {
                    $smsService = app(SmsService::class);
                    $smsService->send($memberPhone, $smsMessage, $member);
                    $otpSentVia[] = 'SMS (' . substr($memberPhone, 0, 3) . '****' . substr($memberPhone, -2) . ')';
                } catch (\Exception $e) {
                    Log::error('Failed to send withdrawal OTP via SMS', [
                        'member' => $this->withdrawMembershipNumber,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            // Send OTP via Email
            if ($memberEmail) {
                try {
                    $otp = $this->generatedWithdrawOTP;
                    Mail::send([], [], function ($message) use ($memberEmail, $memberName, $otp) {
                        $emailBody = "
                        <h3>Savings Withdrawal OTP</h3>
                        <p>Dear {$memberName},</p>
                        <p>Your OTP for savings withdrawal is:</p>
                        <h1 style='color: #2563eb; font-size: 32px; letter-spacing: 5px;'>{$otp}</h1>
                        <p>This OTP is valid for 5 minutes.</p>
                        <p><strong>Security Notice:</strong> Do not share this OTP with anyone. SACCOS staff will never ask for your OTP.</p>
                        <br>
                        <p>Best regards,<br>SACCOS Core System</p>
                        ";
                        
                        $message->to($memberEmail, $memberName)
                            ->subject('Savings Withdrawal OTP - SACCOS')
                            ->html($emailBody);
                    });
                    $otpSentVia[] = 'Email (' . substr($memberEmail, 0, 3) . '****' . substr($memberEmail, strpos($memberEmail, '@')) . ')';
                } catch (\Exception $e) {
                    Log::error('Failed to send withdrawal OTP via Email', [
                        'member' => $this->withdrawMembershipNumber,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            $this->withdrawOtpSent = true;
            $this->withdrawOtpSentTime = now();
            
            if (!empty($otpSentVia)) {
                session()->flash('success', 'OTP has been sent via ' . implode(' and ', $otpSentVia));
            } else {
                // If no phone or email, show OTP on screen (for testing)
                session()->flash('info', 'No contact details found. OTP for testing: ' . $this->generatedWithdrawOTP);
            }
            
        } catch (\Exception $e) {
            Log::error('Error generating withdrawal OTP', [
                'member' => $this->withdrawMembershipNumber,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Error generating OTP: ' . $e->getMessage());
        }
    }

    public function verifyWithdrawOTP()
    {
        if (!$this->withdrawOtpCode) {
            session()->flash('error', 'Please enter the OTP code.');
            return false;
        }

        // Check all possible cache keys (last 5 minutes)
        $found = false;
        for ($i = 0; $i < 300; $i++) { // Check last 5 minutes
            $time = Carbon::now()->subSeconds($i);
            $cacheKey = 'withdrawal_otp_' . $this->withdrawMembershipNumber . '_' . $time->format('YmdHis');
            $storedOTP = Cache::get($cacheKey);
            
            if ($storedOTP && $storedOTP === $this->withdrawOtpCode) {
                Cache::forget($cacheKey);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            session()->flash('error', 'Invalid or expired OTP. Please request a new one.');
            return false;
        }
        
        $this->withdrawOtpVerified = true;
        session()->flash('success', 'OTP verified successfully.');
        return true;
    }

    private function processCashWithdrawal()
    {
        // Verify OTP first
        if (!$this->withdrawOtpVerified) {
            if (!$this->verifyWithdrawOTP()) {
                throw new \Exception('OTP verification failed. Please enter valid OTP.');
            }
        }
        
        // Auto-generate reference number if not provided
        if (empty($this->withdrawReferenceNumber)) {
            $this->withdrawReferenceNumber = 'CASH-' . date('YmdHis') . '-' . rand(1000, 9999);
        }
        
        // Set current date and time automatically
        $this->withdrawDate = date('Y-m-d');
        $this->withdrawTime = date('H:i');

        // Get cash account from institution settings
        $institution = \App\Models\institutions::find(1);
        if (!$institution || !$institution->main_till_account) {
            throw new \Exception('Institution cash account not configured. Please contact administrator.');
        }

        $cashInSafeAccount = AccountsModel::where('account_number', $institution->main_till_account)
            ->where('status', 'ACTIVE')
            ->first();

        if (!$cashInSafeAccount) {
            // Fallback to vault cash or petty cash account
            $cashInSafeAccount = AccountsModel::where('account_number', $institution->main_petty_cash_account)
                ->where('status', 'ACTIVE')
                ->first();
        }

        if (!$cashInSafeAccount) {
            // Last fallback to searching by name
            $cashInSafeAccount = AccountsModel::where('account_name', 'LIKE', '%VAULT CASH%')
                ->orWhere('account_name', 'LIKE', '%TILL%')
                ->orWhere('account_name', 'LIKE', '%PETTY CASH%')
                ->where('status', 'ACTIVE')
                ->first();
        }

        if (!$cashInSafeAccount) {
            throw new \Exception('Cash account not found. Please contact administrator to configure cash accounts.');
        }

        // Post the cash withdrawal transaction
        $transactionService = new TransactionPostingService();
        $transactionData = [
            'first_account' => $this->withdrawSelectedAccount, // Debit member's account
            'second_account' => $cashInSafeAccount->account_number, // Credit cash in safe account
            'amount' => $this->withdrawAmount,
            'narration' => 'Cash withdrawal: ' . $this->withdrawAmount . ' : ' . ($this->withdrawVerifiedMember['name'] ?? 'Member') . ' : ' . $this->withdrawReferenceNumber,
            'action' => 'cash_withdrawal'
        ];

        $result = $transactionService->postTransaction($transactionData);
        
        if ($result['status'] !== 'success') {
            throw new \Exception('Failed to post cash withdrawal transaction: ' . ($result['message'] ?? 'Unknown error'));
        }

        Log::info('Cash withdrawal processed successfully', [
            'account' => $this->withdrawSelectedAccount,
            'amount' => $this->withdrawAmount,
            'withdrawer' => $this->withdrawVerifiedMember['name'] ?? 'Member',
            'reference' => $this->withdrawReferenceNumber
        ]);
    }

    private function processInternalTransferWithdrawal()
    {
        $this->validate([
            'withdrawSourceAccount' => 'required|exists:bank_accounts,id'
        ]);
        
        // Auto-generate reference number if not provided
        if (empty($this->withdrawReferenceNumber)) {
            $this->withdrawReferenceNumber = 'INT-' . date('YmdHis') . '-' . rand(1000, 9999);
        }
        
        // Set current date and time automatically
        $this->withdrawDate = date('Y-m-d');
        $this->withdrawTime = date('H:i');

        // Get the source bank account from the selected bank account
        $sourceBankAccount = \App\Models\BankAccount::find($this->withdrawSourceAccount);
        if (!$sourceBankAccount) {
            throw new \Exception('Source bank account not found.');
        }

        // Process internal fund transfer using NBC API
        $internalTransferService = new \App\Services\NbcPayments\InternalFundTransferService();
        
        $transferData = [
            'debitAccount' => $sourceBankAccount->account_number, // SACCO's NBC account from selected source
            'creditAccount' => $this->withdrawVerifiedMember['account_number'] ?? '', // Member's NBC account from client record
            'amount' => $this->withdrawAmount,
            'debitCurrency' => 'TZS',
            'creditCurrency' => 'TZS',
            'narration' => 'Internal transfer: ' . $this->withdrawNarration,
            'channelId' => config('services.nbc_internal_fund_transfer.channel_id'),
            'channelRef' => $this->withdrawReferenceNumber,
            'pyrName' => $this->withdrawVerifiedMember['name'] ?? 'Member'
        ];

        $result = $internalTransferService->processInternalTransfer($transferData);

        if (!$result['success']) {
            throw new \Exception('Internal transfer failed: ' . ($result['message'] ?? 'Unknown error'));
        }

        // Post the internal transaction in our system
        $transactionService = new TransactionPostingService();
        $transactionData = [
            'first_account' => $this->withdrawSelectedAccount, // Debit member's savings account
            'second_account' => $sourceBankAccount->internal_mirror_account_number ?? $sourceBankAccount->account_number, // Credit source bank account
            'amount' => $this->withdrawAmount,
            'narration' => 'Internal transfer withdrawal: ' . $this->withdrawAmount . ' to ' . ($this->withdrawVerifiedMember['account_number'] ?? '') . ' : ' . $this->withdrawReferenceNumber,
            'action' => 'internal_transfer_withdrawal'
        ];

        $transactionResult = $transactionService->postTransaction($transactionData);
        
        if ($transactionResult['status'] !== 'success') {
            throw new \Exception('Failed to post internal transfer transaction: ' . ($transactionResult['message'] ?? 'Unknown error'));
        }

        Log::info('Internal transfer withdrawal processed successfully', [
            'member_account' => $this->withdrawSelectedAccount,
            'nbc_account' => $this->withdrawVerifiedMember['account_number'] ?? '',
            'source_bank_account' => $sourceBankAccount->account_number,
            'amount' => $this->withdrawAmount,
            'reference' => $this->withdrawReferenceNumber,
            'nbc_reference' => $result['data']['hostReferenceCbs'] ?? null
        ]);
    }

    private function processTipsMnoWithdrawal()
    {
        $this->validate([
            'withdrawMnoProvider' => 'required|string|max:255',
            'withdrawPhoneNumber' => 'required|string|max:255'
        ]);
        
        // Auto-generate reference number if not provided
        if (empty($this->withdrawReferenceNumber)) {
            $this->withdrawReferenceNumber = 'MNO-' . date('YmdHis') . '-' . rand(1000, 9999);
        }
        
        // Set current date and time automatically
        $this->withdrawDate = date('Y-m-d');
        $this->withdrawTime = date('H:i');

        // Get cash at NBC account
        $cashAtNbcAccount = AccountsModel::where('account_name', 'LIKE', '%cash at NBC%')
            ->orWhere('account_name', 'LIKE', '%NBC%')
            ->where('status', 'ACTIVE')
            ->first();

        if (!$cashAtNbcAccount) {
            throw new \Exception('Cash at NBC account not found. Please contact administrator.');
        }

        // Process TIPS MNO transfer using NBC API
        $nbcPaymentService = new \App\Services\NbcPayments\NbcPaymentService();
        $nbcLookupService = new \App\Services\NbcPayments\NbcLookupService();

        // First, perform lookup
        $lookupResult = $nbcLookupService->bankToWalletLookup(
            $this->withdrawPhoneNumber,
            $this->withdrawMnoProvider,
            $cashAtNbcAccount->account_number,
            $this->withdrawAmount,
            'PERSON'
        );

        if (!$lookupResult['success']) {
            throw new \Exception('TIPS lookup failed: ' . ($lookupResult['message'] ?? 'Unknown error'));
        }

        // Then process the transfer
        $transferResult = $nbcPaymentService->processBankToWalletTransfer(
            $lookupResult['data'],
            $cashAtNbcAccount->account_number,
            $this->withdrawAmount,
            $this->withdrawPhoneNumber,
            time(), // initiatorId
            'TIPS MNO transfer: ' . $this->withdrawNarration
        );

        if (!$transferResult['success']) {
            throw new \Exception('TIPS MNO transfer failed: ' . ($transferResult['message'] ?? 'Unknown error'));
        }

        // Post the transaction in our system
        $transactionService = new TransactionPostingService();
        $transactionData = [
            'first_account' => $this->withdrawSelectedAccount, // Debit member's savings account
            'second_account' => $cashAtNbcAccount->account_number, // Credit cash at NBC account
            'amount' => $this->withdrawAmount,
            'narration' => 'TIPS MNO withdrawal: ' . $this->withdrawAmount . ' to ' . $this->withdrawPhoneNumber . ' (' . $this->withdrawMnoProvider . ') : ' . $this->withdrawReferenceNumber,
            'action' => 'tips_mno_withdrawal'
        ];

        $transactionResult = $transactionService->postTransaction($transactionData);
        
        if ($transactionResult['status'] !== 'success') {
            throw new \Exception('Failed to post TIPS MNO transaction: ' . ($transactionResult['message'] ?? 'Unknown error'));
        }

        Log::info('TIPS MNO withdrawal processed successfully', [
            'member_account' => $this->withdrawSelectedAccount,
            'phone_number' => $this->withdrawPhoneNumber,
            'mno_provider' => $this->withdrawMnoProvider,
            'amount' => $this->withdrawAmount,
            'reference' => $this->withdrawReferenceNumber,
            'tips_reference' => $transferResult['engineRef'] ?? null
        ]);
    }

    private function processTipsBankWithdrawal()
    {
        $this->validate([
            'withdrawBankCode' => 'required|string|max:255',
            'withdrawBankAccountNumber' => 'required|string|max:255',
            'withdrawBankAccountHolderName' => 'required|string|max:255'
        ]);
        
        // Auto-generate reference number if not provided
        if (empty($this->withdrawReferenceNumber)) {
            $this->withdrawReferenceNumber = 'TIPS-' . date('YmdHis') . '-' . rand(1000, 9999);
        }
        
        // Set current date and time automatically
        $this->withdrawDate = date('Y-m-d');
        $this->withdrawTime = date('H:i');

        // Get cash at NBC account
        $cashAtNbcAccount = AccountsModel::where('account_name', 'LIKE', '%cash at NBC%')
            ->orWhere('account_name', 'LIKE', '%NBC%')
            ->where('status', 'ACTIVE')
            ->first();

        if (!$cashAtNbcAccount) {
            throw new \Exception('Cash at NBC account not found. Please contact administrator.');
        }

        // Process TIPS Bank transfer using NBC API
        $nbcPaymentService = new \App\Services\NbcPayments\NbcPaymentService();
        $nbcLookupService = new \App\Services\NbcPayments\NbcLookupService();

        // First, perform lookup
        $lookupResult = $nbcLookupService->bankToBankLookup(
            $this->withdrawBankAccountNumber,
            $this->withdrawBankCode,
            $cashAtNbcAccount->account_number,
            $this->withdrawAmount,
            'PERSON'
        );

        if (!$lookupResult['success']) {
            throw new \Exception('TIPS bank lookup failed: ' . ($lookupResult['message'] ?? 'Unknown error'));
        }

        // Then process the transfer
        $transferResult = $nbcPaymentService->processBankToBankTransfer(
            $lookupResult['data'],
            $cashAtNbcAccount->account_number,
            $this->withdrawAmount,
            $this->withdrawPhoneNumber ?? '255000000000', // Default phone number if not provided
            time(), // initiatorId
            'TIPS Bank transfer: ' . $this->withdrawNarration,
            'FTLC'
        );

        if (!$transferResult['success']) {
            throw new \Exception('TIPS bank transfer failed: ' . ($transferResult['message'] ?? 'Unknown error'));
        }

        // Post the transaction in our system
        $transactionService = new TransactionPostingService();
        $transactionData = [
            'first_account' => $this->withdrawSelectedAccount, // Debit member's savings account
            'second_account' => $cashAtNbcAccount->account_number, // Credit cash at NBC account
            'amount' => $this->withdrawAmount,
            'narration' => 'TIPS Bank withdrawal: ' . $this->withdrawAmount . ' to ' . $this->withdrawBankAccountNumber . ' (' . $this->withdrawBankCode . ') : ' . $this->withdrawReferenceNumber,
            'action' => 'tips_bank_withdrawal'
        ];

        $transactionResult = $transactionService->postTransaction($transactionData);
        
        if ($transactionResult['status'] !== 'success') {
            throw new \Exception('Failed to post TIPS bank transaction: ' . ($transactionResult['message'] ?? 'Unknown error'));
        }

        Log::info('TIPS Bank withdrawal processed successfully', [
            'member_account' => $this->withdrawSelectedAccount,
            'bank_account' => $this->withdrawBankAccountNumber,
            'bank_code' => $this->withdrawBankCode,
            'amount' => $this->withdrawAmount,
            'reference' => $this->withdrawReferenceNumber,
            'tips_reference' => $transferResult['engineRef'] ?? null
        ]);
    }

    public function resetWithdrawForm()
    {
        $this->reset([
            'withdrawMembershipNumber',
            'withdrawSelectedAccount',
            'withdrawAmount',
            'withdrawPaymentMethod',
            'withdrawSelectedBank',
            'withdrawSourceAccount',
            'withdrawReferenceNumber',
            'withdrawDate',
            'withdrawTime',
            'withdrawerName',
            'withdrawNarration',
            'withdrawVerifiedMember',
            'withdrawMemberAccounts',
            'withdrawSelectedBankDetails',
            'withdrawSelectedAccountBalance',
            'withdrawNbcAccount',
            'withdrawAccountHolderName',
            'withdrawMnoProvider',
            'withdrawPhoneNumber',
            'withdrawWalletHolderName',
            'withdrawBankCode',
            'withdrawBankAccountNumber',
            'withdrawBankAccountHolderName',
            'withdrawOtpCode',
            'generatedWithdrawOTP',
            'withdrawOtpSent',
            'withdrawOtpSentTime',
            'withdrawOtpVerified'
        ]);
        $this->resetErrorBag();
    }

    /**
     * Override to specify the module name for permissions
     * 
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'savings';
    }
}
