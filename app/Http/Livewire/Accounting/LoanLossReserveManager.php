<?php

namespace App\Http\Livewire\Accounting;

use App\Models\AccountsModel;
use App\Models\IntangibleAsset;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Models\LoanLossReserve;
use Illuminate\Validation\Rule;
use App\Services\TransactionPostingService;

class LoanLossReserveManager extends Component
{
    public $llr_id;
    public $profits;
    public $percentage = 20; // Default 20%
    public $reserve_amount;
    public $total_llr;
    public $editMode = false;

    // Yearly details
    public $year;
    public $profit;
    public $initial_allocation;
    public $adjustments = 0;
    public $total_allocation;
    public $status;
    public $actualLoanLosses;

    // Validation rules
    protected $rules = [
        'profits' => 'required|numeric|min:0',
        'percentage' => 'required|numeric|min:0|max:100',
        'reserve_amount' => 'required|numeric|min:0',
        'profit' => 'required|numeric', // For initial allocation
        'actualLoanLosses' => 'required|numeric', // For year-end finalization
    ];

    public function mount()
    {
        $this->year = now()->year;
        $this->calculateTotalLLR();
    }

    public function updatedProfits(){
        $this->calculateLLR();
    }
    public function calculateLLR()
    {
        $this->validateOnly('profits'); // Validate profits only
        $this->reserve_amount = ($this->profits * $this->percentage) / 100;
    }

    public function saveLLR()
    {
        $this->validate();
        LoanLossReserve::updateOrCreate(
            ['id' => $this->llr_id],
            [
                'profits' => $this->profits,
                'percentage' => $this->percentage,
                'reserve_amount' => $this->reserve_amount,
            ]
        );

        $this->resetForm();
        $this->calculateTotalLLR();
        session()->flash('message', 'Loan Loss Reserve saved successfully.');
    }

    public function editLLR($id)
    {
        $llr = LoanLossReserve::findOrFail($id);
        $this->llr_id = $llr->id;
        $this->profits = $llr->profits;
        $this->percentage = $llr->percentage;
        $this->reserve_amount = $llr->reserve_amount;
        $this->editMode = true;
    }

    public function deleteLLR($id)
    {
        LoanLossReserve::findOrFail($id)->delete();
        $this->calculateTotalLLR();
        session()->flash('message', 'Loan Loss Reserve deleted successfully.');
    }

    public function resetForm()
    {
        $this->editMode = false;
        $this->llr_id = null;
        $this->profits = null;
        $this->percentage = 20; // Reset to default
        $this->reserve_amount = null;
    }

    public function calculateTotalLLR()
    {
        $this->total_llr = LoanLossReserve::sum('reserve_amount');
    }

    // Initial allocation when profits are declared
    public function allocateInitial()
    {
        //dd('bbbn');
        //$this->validate(['profit']); // Validate profit only
        //dd($this->profits);
        $minAllocation = (float)$this->profits * 20/100;

        LoanLossReserve::create([
            'year' => $this->year,
            'percentage' => $this->percentage,
            'profits' => $this->profits,
            'initial_allocation' => $minAllocation,
            'adjustments' => $this->adjustments,
            'total_allocation' => $minAllocation + $this->adjustments,
            'status' => 'allocated',
        ]);

        $this->name = 'Initial loan loss reserve '.$this->year;
        $this->type = 'loan_loss_reserves';



        // Fetch accounts for the category
        $get_accounts = DB::table('loan_loss_reserves')->get();

        if ($get_accounts->isEmpty()) {
            $next_code =  1301;

        }else{


            $category_code = $get_accounts->first()->category_code;
            $existing_codes = $get_accounts->pluck('category_code')->toArray();
            $range_start = intval($category_code) + 1;
            $range_limit = intval($category_code) + 999;

            // Generate the next unique sub_category_code
            $next_code = $range_start;
            while (in_array(strval($next_code), $existing_codes) && $next_code <= $range_limit) {
                $next_code++;
            }

            // Validate next_code before inserting
            if ($next_code > $range_limit) {
                session()->flash('error', 'Unable to generate a unique code within the range.');
                return;
            }


        }


        // Format the account name
        $formattedAccountName = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/', '', $this->name)));
        $formattedAccountName = str_replace(' ', '_', $formattedAccountName);
$formattedAccountName = strtoupper($formattedAccountName);

        $category_code = DB::table('asset_accounts')->where('category_name',$this->type)->value('category_code');

        // dd($category_code);

        // Create a new account based on input fields
        DB::table($this->type)->insert([
            'category_code' => $category_code,
            'sub_category_code' => $next_code,
            'sub_category_name' => $formattedAccountName,
        ]);

        // Generate account number
        $account_number = $this->generate_account_number(auth()->user()->branch, $next_code);

        // Create a new account entry in the AccountsModel
        $id = AccountsModel::create([
            'account_use' => 'internal',
            'institution_number' => auth()->user()->institution_id,
            'branch_number' => auth()->user()->branch,
            'major_category_code' => 1000,
            'category_code' => $category_code,
            'sub_category_code' => $next_code,
            'account_name' => $this->name,
            'account_number' => $account_number,
            'notes' => $this->name,
            'bank_id' => null,
            'mirror_account' => null,
            'account_level' => '3',
        ])->id;

        // Call the CreditAndDebit method
        //$this->newInitialAmount = 0;

//            $this->creditAndDebitService = new CreditAndDebitService();
//            $this->creditAndDebitService->

        $source_account_number = AccountsModel::where("sub_category_code", $this->source)->value('account_number');

        //dd($source_account_number);

        $this->CreditAndDebit(
            $source_account_number,
            $this->value,
            $account_number,
            'Assets Investment : '.$this->name
        );



        IntangibleAsset::create([
            'name' => $this->name,
            'type' => $this->type,
            'value' => $this->value,
            'acquisition_date' => $this->acquisition_date,
            'source' => $source_account_number,
        ]);

        $this->resetInputFields();

        session()->flash('message', 'Initial allocation set successfully.');
    }






















    // Periodic adjustment (quarterly or semi-annual)
    public function adjustReserve()
    {

        $this->validate(['adjustments' => 'required|numeric']);

        $llr = LoanLossReserve::where('year', $this->year)->first();

        //dd($llr->total_allocation);

        if ($llr) {
            //$llr->setAdjustments($this->adjustments);


            LoanLossReserve::where('year', $this->year)->update(
                [
                    'total_allocation'=>$llr->total_allocation + $this->adjustments
                    ]);

            session()->flash('message', 'LLR adjusted successfully.');
        } else {
            session()->flash('error', 'No allocation found for this year.');
        }
    }

    // Finalize at year-end based on actual loan losses
    public function finalizeYearEnd()
    {
        //$this->validate();

        $llr = LoanLossReserve::where('year', $this->year)->first();
        if ($llr) {
            $llr->finalizeAtYearEnd($this->actualLoanLosses);

            // Step 1: Calculate the total allocation
            $this->total_allocation = $llr->total_allocation + $this->adjustments;

            // Step 2: Compare total allocation with actual loan losses
            if ($this->total_allocation < $this->actualLoanLosses) {
                // If total allocation is less than actual loan losses, adjust the reserve amount
                $difference = $this->actualLoanLosses - $this->total_allocation;
                $this->adjustments += $difference;
                $this->total_allocation += $difference; // Update total allocation
            } elseif ($this->total_allocation > $this->actualLoanLosses) {
                // If total allocation exceeds actual loan losses, consider adjusting downwards
                $difference = $this->total_allocation - $this->actualLoanLosses;
                $this->adjustments -= $difference;
                $this->total_allocation -= $difference; // Update total allocation
            }

            LoanLossReserve::where('year', $this->year)->update(
                [
                    'total_allocation'=>$this->adjustments
                ]);

            session()->flash('message', 'Year-end adjustment completed.');
        } else {
            session()->flash('error', 'No allocation found for this year.');
        }
    }

    public function render()
    {
        $llr = LoanLossReserve::where('year', $this->year)->first();
        $this->total_allocation = $llr ? $llr->calculateTotalAllocation() : 0;

        return view('livewire.accounting.loan-loss-reserve-manager', [
            'llr' => $llr,
        ]);
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

    public function generate_account_number($branch_code, $product_code): string
    {

        //sub_category_code
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


    public function CreditAndDebit($source_account, $amount, $destination_accounts, $narration)
    {
        $reference_number = time();
        $transactionService = new TransactionPostingService();
        $source_account_details = AccountsModel::where("account_number", $source_account)->first();
        $destination_account_details = AccountsModel::where("account_number", $destination_accounts)->first();

        // Case 1: Both source_account and destination_account are provided
        if ($source_account_details && $destination_account_details) {
            // Use transaction posting service for double-entry
            $transactionService->postTransaction([
                'first_account' => $source_account,
                'second_account' => $destination_accounts,
                'amount' => $amount,
                'narration' => $narration,
            ]);
            $source_account_name = $source_account_details->account_name;
            $destination_account_name = $destination_account_details->account_name;
            // Record on debit
            $this->debit($reference_number, $source_account, $destination_accounts, $amount, $narration, $source_account_details->balance, $source_account_name, $destination_account_name);
            // Record on credit
            $this->credit($reference_number, $source_account, $destination_accounts, $amount, $narration, $destination_account_details->balance, $source_account_name, $destination_account_name);
        }
        // Case 2: Only source_account is provided (credit only)
        elseif ($source_account_details && !$destination_account_details) {
            // Only perform credit action
            $source_account_name = $source_account_details->account_name;

            // Record credit transaction
            $this->credit($reference_number, $source_account, null, $amount, $narration, $source_account_details->balance + $amount, $source_account_name, null);
        }
        // Case 3: Only destination_account is provided (debit only)
        elseif (!$source_account_details && $destination_account_details) {
            // Only perform debit action
            $destination_account_name = $destination_account_details->account_name;

            // Record debit transaction
            $this->debit($reference_number, null, $destination_accounts, $amount, $narration, $destination_account_details->balance - $amount, null, $destination_account_name);
        }
        // Case 4: Both accounts are null
        else {
            throw new \Exception('Both source and destination accounts cannot be null.');
        }
    }




    public function debit($reference, $source_account_number, $destination_account_number, $credit, $narration, $running_balance, $source_account_name, $destinantion_account_name)
    {


        /**
         * @var mixed prepare sender data
         */

        $sender_branch_id='';
        $sender_product_id='';
        $sender_sub_product_id='';
        $sender_id='';
        $sender_name='';


        $senderInfo=  DB::table('clients')->where('client_number', DB::table('accounts')
            ->where('account_number', $destinantion_account_name)->value('client_number'))->first();
        if($senderInfo){
            $accounts=DB::table('accounts')->where('account_number',$source_account_number)->first();
            $sender_branch_id=$senderInfo->branch_id;
            $sender_product_id=$accounts->category_code;
            $sender_sub_product_id=$accounts->sub_category_code;
            $sender_id=$senderInfo->client_number;
            $sender_name=$senderInfo->first_name.' '.$senderInfo->middle_name.' .'.$senderInfo->last_name;

        }

        //DEBIT RECORD MEMBER
        $beneficiary_branch_id='';
        $beneficiary_product_id='';
        $beneficiary_sub_product_id='';
        $beneficiary_id='';
        $beneficiary_name='';

        $receiverInfo= DB::table('clients')->where('client_number', DB::table('accounts')
            ->where('account_number', $destinantion_account_name)->value('client_number'))->first();
        if($receiverInfo){

//            $accounts=DB::table('accounts')->where('account_number',$source_account_number)->first();
//            $beneficiary_branch_id=$senderInfo->branch_id;
//            $beneficiary_product_id=$accounts->category_code;
//            $beneficiary_sub_product_id=$accounts->sub_category_code;
//            $beneficiary_id=$senderInfo->client_number;
//            $beneficiary_name=$senderInfo->first_name.' '.$senderInfo->middle_name.' '.$senderInfo->last_name;


            $beneficiary_id=$senderInfo->client_number;
            $beneficiary_name=$senderInfo->first_name.' '.$senderInfo->middle_name.' '.$senderInfo->last_name;
        }

        $accounts=DB::table('accounts')->where('account_number',$source_account_number)->first();

        $major_category_code=$accounts->major_category_code;
        $category_code=$accounts->category_code;
        $sub_category_code=$accounts->sub_category_code;


        general_ledger::create([
            'record_on_account_number' => $source_account_number  ? :0,
            'record_on_account_number_balance' => $running_balance  ? :0,
            'major_category_code' =>$major_category_code  ? :0,
            'category_code' => $category_code  ? :0,
            'sub_category_code' => $sub_category_code  ? :0,
            'sender_sub_product_id' =>  null,
            'beneficiary_product_id' => null,
            'beneficiary_sub_product_id' => null,
            'sender_id' =>  $sender_id  ?:1,
            'beneficiary_id' => $beneficiary_id  ?:1,
            'sender_name' => $sender_name,
            'beneficiary_name' => $beneficiary_name,
            'sender_account_number' => $source_account_number,
            'beneficiary_account_number' => $destination_account_number,
            'transaction_type' => 'IFT',
            'sender_account_currency_type' => 'TZS',
            'beneficiary_account_currency_type' => 'TZS',
            'narration' => $narration,
            'credit'  => 0,
            'debit' => (double)$credit,
            'reference_number' => $reference,
            'trans_status' => 'Successful',
            'trans_status_description' => 'Successful',
            'swift_code' => '',
            'destination_bank_name' => '',
            'destination_bank_number' => '',
            'payment_status' => 'Successful',
            'recon_status' => 'Pending',
            // 'partner_bank' => AccountsModel::where('account_number', $this->bank1)->value('institution_number'),
            // 'partner_bank_name' => AccountsModel::where('account_number', $this->bank1)->value('account_name'),
            // 'partner_bank_account_number' => $this->bank1,
            'partner_bank_transaction_reference_number' => '0000',

        ]);



    }


    public function credit($reference, $source_account_number, $destination_account_number, $credit, $narration, $running_balance, $source_account_name, $destinantion_account_name)
    {


        /**
         * @var mixed prepare sender data
         */

        $sender_branch_id='';
        $sender_product_id='';
        $sender_sub_product_id='';
        $sender_id='';
        $sender_name='';


        $senderInfo=  DB::table('clients')->where('client_number', DB::table('accounts')
            ->where('account_number', $destinantion_account_name)->value('client_number'))->first();
        if($senderInfo){
            $accounts=DB::table('accounts')->where('account_number',$source_account_number)->first();
            $sender_branch_id=$senderInfo->branch_id;
            $sender_product_id=$accounts->category_code;
            $sender_sub_product_id=$accounts->sub_category_code;
            $sender_id=$senderInfo->client_number;
            $sender_name=$senderInfo->first_name.' '.$senderInfo->middle_name.' .'.$senderInfo->last_name;

        }

        //DEBIT RECORD MEMBER
        $beneficiary_branch_id='';
        $beneficiary_product_id='';
        $beneficiary_sub_product_id='';
        $beneficiary_id='';
        $beneficiary_name='';

        $receiverInfo= DB::table('clients')->where('client_number', DB::table('accounts')
            ->where('account_number', $destinantion_account_name)->value('client_number'))->first();
        if($receiverInfo){


            $beneficiary_id=$senderInfo->client_number;
            $beneficiary_name=$senderInfo->first_name.' '.$senderInfo->middle_name.' '.$senderInfo->last_name;
        }

        $accounts=DB::table('accounts')->where('account_number',$destination_account_number)->first();

        $major_category_code=$accounts->major_category_code;
        $category_code=$accounts->category_code;
        $sub_category_code=$accounts->sub_category_code;


        general_ledger::create([
            'record_on_account_number' => $destination_account_number ? :0,
            'record_on_account_number_balance' => $running_balance ? :0,
            'major_category_code' =>$major_category_code  ? :0,
            'category_code' => $category_code  ? :0,
            'sub_category_code' => $sub_category_code  ? :0,
            'sender_sub_product_id' =>  null,
            'beneficiary_product_id' => null,
            'beneficiary_sub_product_id' => null,
            'sender_id' =>  $sender_id  ?:1,
            'beneficiary_id' => $beneficiary_id  ?:1,
            'sender_name' => $sender_name,
            'beneficiary_name' => $beneficiary_name,
            'sender_account_number' => $source_account_number,
            'beneficiary_account_number' => $destination_account_number,
            'transaction_type' => 'IFT',
            'sender_account_currency_type' => 'TZS',
            'beneficiary_account_currency_type' => 'TZS',
            'narration' => $narration,
            'credit'  => (double)$credit,
            'debit' => 0,
            'reference_number' => $reference,
            'trans_status' => 'Successful',
            'trans_status_description' => 'Successful',
            'swift_code' => '',
            'destination_bank_name' => '',
            'destination_bank_number' => '',
            'payment_status' => 'Successful',
            'recon_status' => 'Pending',
            'partner_bank_transaction_reference_number' => '0000',

        ]);



    }
}
