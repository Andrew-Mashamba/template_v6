<?php

namespace App\Http\Livewire\Accounting;

use App\Models\LongTermAndShortTerm as ModelsLongTermAndShortTerm;
use Livewire\Component;
use Livewire\WithFileUploads;

class LongTermAndShortTerm extends Component
{
    use WithFileUploads;

    public $show_register_modal = false;
    public $loan_type; // Long or Short
    public $source_account_id;
    public $amount;
    public $organization_name;
    public $address;
    public $phone;
    public $email;
    public $description;
    public $application_form; // File input for application
    public $contract_form; // File input for contract

    public function register()
    {
        // Validate the form fields
        $this->validate([
            'loan_type' => 'required|string|in:Long,Short', // Ensure loan_type is either Long or Short
            'source_account_id' => 'required|exists:accounts,id',
            'amount' => 'required|numeric',
            'organization_name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'email' => 'required|email',
            'description' => 'nullable|string',
            // Check for files only if they are required
            'application_form' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
            'contract_form' => 'nullable|file|mimes:pdf,jpg,png,jpeg|max:2048',
        ]);

        // Initialize file paths as null
        $applicationFormPath = null;
        $contractFormPath = null;

        // Store the uploaded files if they exist
        if ($this->application_form) {
            $applicationFormPath = $this->application_form->store('applications');
        }

        if ($this->contract_form) {
            $contractFormPath = $this->contract_form->store('contracts');
        }

        // Save the data into the loans table
        ModelsLongTermAndShortTerm ::create([
            'loan_type' => $this->loan_type, // Store the loan type
            'source_account_id' => $this->source_account_id,
            'amount' => $this->amount,
            'user_id'=>auth()->user()->id,
            'organization_name' => $this->organization_name,
            'address' => $this->address,
            'phone' => $this->phone,
            'email' => $this->email,
            'status'=>'PENDING',
            'description' => $this->description,
            'application_form' => $applicationFormPath,
            'contract_form' => $contractFormPath,
        ]);

        // Reset form fields
        $this->reset();

        session()->flash('message', 'Loan application submitted successfully.');
        $this->show_register_modal = false; // Hide modal after submission
    }


    function registerModal(){
        $this->show_register_modal=!$this->show_register_modal;
    }





    public function render()
    {
        return view('livewire.accounting.long-term-and-short-term');
    }
}
