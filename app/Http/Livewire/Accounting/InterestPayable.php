<?php

namespace App\Http\Livewire\Accounting;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class InterestPayable extends Component
{

    public $show_register_modal = false;

    public $member_id, $account_type, $category_type = 'Savings'; // Default to Savings
    public $deposit_amount, $interest_rate, $deposit_date, $maturity_date, $payment_frequency;
    public $loan_provider, $amount, $loan_interest_rate, $loan_term, $loan_start_date, $interest_payment_schedule;

    protected $rules = [
        'member_id' => 'required|integer',
        'account_type' => 'required|string|max:50',
        'category_type' => 'required|string',
        // Savings fields
        'interest_rate' => 'required_if:category_type,Savings|numeric',
        'deposit_date' => 'required_if:category_type,Savings|date',
        'maturity_date' => 'required_if:category_type,Savings|date',
        'payment_frequency' => 'required_if:category_type,Savings|string',
        // Loan fields
        'loan_provider' => 'required_if:category_type,Loan|string|max:100',
        'amount' => 'required|numeric',
        'loan_interest_rate' => 'required_if:category_type,Loan|numeric',
        'loan_term' => 'required_if:category_type,Loan|string|max:50',
        'loan_start_date' => 'required_if:category_type,Loan|date',
        //'interest_payment_schedule' => 'required_if:category_type,Loan|string',
    ];

    public function register()
    {




      //  $this->validate();



        // Store the appropriate fields in the DB
        DB::table('interest_payables')->insert([
            'member_id' => $this->member_id,
            'created_by'=>auth()->user()->id,
            'account_type' => $this->account_type,
            'amount' =>   $this->amount ,
            'interest_rate' => $this->category_type === 'Savings' ? $this->interest_rate : null,
            'deposit_date' => $this->category_type === 'Savings' ? $this->deposit_date : null,
            'maturity_date' => $this->category_type === 'Savings' ? $this->maturity_date : null,
            'payment_frequency' => $this->category_type === 'Savings' ? $this->payment_frequency : null,
            'loan_provider' => $this->category_type === 'Loan' ? $this->loan_provider : null,
            'loan_interest_rate' => $this->category_type === 'Loan' ? $this->loan_interest_rate : null,
            'loan_term' => $this->category_type === 'Loan' ? $this->loan_term : null,
            'loan_start_date' => $this->category_type === 'Loan' ? $this->loan_start_date : null,
            'interest_payment_schedule' => $this->category_type === 'Loan' ? $this->interest_payment_schedule : null,
            'created_at' => now(),
        ]);

        session()->flash('message', 'Record successfully stored.');
        $this->reset();

    }


    function registerModal(){
        $this->show_register_modal=!$this->show_register_modal;
    }

    public function render()
    {
        return view('livewire.accounting.interest-payable');
    }
}
