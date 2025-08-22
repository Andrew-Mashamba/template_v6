<?php

namespace App\Http\Livewire\Clients;

use App\Models\ClientsModel;

use App\Models\PendingRegistration;
use App\Models\TeamUser;
use App\Models\User;
use App\Services\AccountCreationService;

use Exception;
use Illuminate\Support\Facades\Mail;

use Livewire\Component;

use App\Models\approvals;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Hash;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Mail\ControlNumberGenerated;
use App\Models\Member;
use App\Services\MembershipVerificationService;
use App\Services\MemberNumberGeneratorService;
use App\Services\BillingService;
use App\Services\PaymentLinkService;
use App\Notifications\NewMemberWelcomeNotification;
use App\Notifications\GuarantorNotification;
use Illuminate\Support\Facades\Bus;
use App\Jobs\ProcessMemberNotifications;


class Clients extends Component
{
    use WithPagination;
    use WithFileUploads;


    public $membership_type = 'Individual';
    public $branch;
    public $phone_number;
    public $first_name;
    public $middle_name;
    public $last_name;
    public $incorporation_number;
    public $email;
    public $place_of_birth;
    public $marital_status;
    public $onboarding_process;
    public $address;
    public $next_of_kin_name;
    public $next_of_kin_phone;
    public $tin_number;
    public $nationarity;
    public $number_of_spouse;
    public $number_of_children;
    public $gender;
    public $date_of_birth;
    public $citizenship;
    public $employer_name;
    public $education;
    public $business_name;
    public $income_available;
    public $hisa;
    public $akiba;
    public $amana;
    public $barua;
    public $uthibitisho;
    public $accept_terms;
    public $application_type;
    public $guarantor_region;
    public $guarantor_ward;
    public $guarantor_district;
    public $guarantor_relationship;
    public $guarantor_membership_number;
    public $guarantor_full_name;
    public $guarantor_email;





    public $tab_id = '1';
    public $title = 'Members list';
    public $selected;
    public $activeClientsCount;
    public $inactiveClientsCount;
    public $showCreateNewMember;
    public $membershipNumber;
    public $parentMember;
    public $showDeleteClient;
    public $clientSelected;
    public $showEditClient;
    public $pendingMember;
    public $MembersList;
    public $pendingClientname;
    public $client;
    public $showAddClient;
    public $Member_status;
    public $permission = 'BLOCKED';
    public $password;

    public $photo;
    public $registering_officer;
    public $supervising_officer;
    public $approving_officer;
    public $membership_number;
    public $registration_date;
    public $street;
    public $notes;
    public $current_team_id;
    public $profile_photo_path;
    public $branch_id;
    public $name;
    public $member;
    public $loan_officer;

    public $confirmingUserDeletion = false;
    public $branches;


    public $sub_product_number_shares ='1199';
    public $sub_product_number_savings='1279';
    public $sub_product_number_deposits='1321';
    public $nida_number;

    public $ref_number;
    public $shares_ref_number;
    public $member_exit_document;

    // vie members modal
    public $viewClientDetails=false;
    public $loanStatus;


    public $account_number;
    public $institution_id;

    public $client_number;


    public $created_at;
    public $updated_at;

    public $end_membership_description;
    public $amount;
    public $national_id;
    public $client_id;
    public $customer_code;
    public $present_surname;
    public $birth_surname;
    public $classification_of_individual;

    public $country_of_birth;
    public $fate_status;
    public $social_status;
    public $residency;
    public $nationality;
    public $monthly_expenses;
    public $negative_status_of_individual;
    public $tax_identification_number;
    public $passport_number;
    public $passport_issuer_country;
    public $driving_license_number;
    public $voters_id;
    public $foreign_unique_id;
    public $custom_id_number_1;
    public $custom_id_number_2;
    public $main_address;
    public $number_of_building;
    public $postal_code;
    public $region;
    public $district;
    public $country;
    public $viewpaid = false;
    public $viewnotpaid = false;
    public $allMembers = true;
    public $mobile_phone;
    public $fixed_line;
    public $web_page;
    public $trade_name;
    public $legal_form;
    public $establishment_date;
    public $registration_country;
    public $industry_sector;
    public $registration_number;
    public $variables;
    public $middle_names;
    public $viewClientLoanData=false;


    public $full_name;

    public $member_number;
    public $contact_number;
    public $occupation;
    public $education_level;
    public $dependent_count;
    public $annual_income;
    public $city;
    public $status;
    public $member_second_phone_number;
    public $religion;
    public $building_number;
    public $ward;
    public $guarantor_id;
    public $income_source;
    public $guarantor_first_name;
    public $guarantor_middle_name;
    public $guarantor_last_name;
    public $errors;
    public $pendingClient;
    public $photo2;
    public $selectedMemberCategory;
    public $memberCategories = [];

    public $activeTab = 'dashboard';

    public $showGenerateControlNumbers = false;
    public $client_type = 'Individual';
    public $showDeleteMember = false;
    public $generatedControlNumbers = [];
    public $showControlNumbersModal = false;
    public $currentStep = 1;

    public $additionalDocuments = [];

    public $guarantor_member_number;
    public $guarantorVerification = null;
    public $guarantorVerificationMessage = '';
    public $guarantorVerificationStatus = '';

    public $nbc_account_number;

    protected $listeners = [
        'showUsersList' => 'showUsersList',
        'blockClient' => 'blockClientModal',
        'editClient' => 'editClientModal',
        'viewClientDetails'=>'viewClientDetails',
        'viewClientLoans'=>'viewClientLoan',
        'verifyMembership' => 'verifyMembership'
    ];

    protected $rules = [
        // Step 1: Personal Information
        'membership_type' => 'required|in:Individual,Group,Business',
        'branch' => 'required|exists:branches,id',
        'photo' => 'nullable|image|max:10240',
        
        // Individual member specific fields
        'first_name' => 'required_if:membership_type,Individual|string|max:100',
        'middle_name' => 'nullable|string|max:100',
        'last_name' => 'required_if:membership_type,Individual|string|max:100',
        'gender' => 'required_if:membership_type,Individual|in:male,female',
        'date_of_birth' => 'required_if:membership_type,Individual|date|before:today',
        'marital_status' => 'required_if:membership_type,Individual|in:single,married,divorced,widowed',
        
        // Business/Group specific fields
        'business_name' => 'required_if:membership_type,Business,Group|string|max:100',
        'incorporation_number' => 'required_if:membership_type,Business,Group|string|max:50',
        
        // Step 2: Contact Details
        'phone_number' => 'required|string|regex:/^0[0-9]{9,10}$/',
        'email' => 'nullable|email|max:100',
        'address' => 'required|string|max:255',
        'nationality' => 'required|string|max:100',
        'citizenship' => 'required|string|max:100',
        'next_of_kin_name' => 'required_if:membership_type,Individual|string|max:100',
        'next_of_kin_phone' => 'required_if:membership_type,Individual|string|regex:/^0[0-9]{9,10}$/',
        
        // Step 3: Financial Information
        'income_available' => 'required|numeric|min:0|max:999999999.99',
        'income_source' => 'required|string|max:100',
        'tin_number' => 'nullable|string|max:100',
        'hisa' => 'required|numeric|min:1000|max:999999999.99',
        'akiba' => 'required|numeric|min:1000|max:999999999.99',
        'amana' => 'required|numeric|min:1000|max:999999999.99',
        'nbc_account_number' => 'nullable|string|max:12',
        
        // Step 4: Guarantor & Documents
        'guarantor_member_number' => 'nullable|string|max:100',
        'guarantor_relationship' => 'required_with:guarantor_member_number|string|max:255',
        'additionalDocuments.*.description' => 'required|string|max:255',
        'additionalDocuments.*.file' => 'required|file|max:10240'
    ];

    protected $messages = [
        // Step 1: Personal Information
        'membership_type.required' => 'Please select a membership type',
        'membership_type.in' => 'Invalid membership type selected',
        'branch.required' => 'Please select a branch',
        'branch.exists' => 'Selected branch does not exist',
        'photo.image' => 'The file must be an image',
        'photo.max' => 'The photo must not exceed 10MB',
        
        // Individual member specific messages
        'first_name.required_if' => 'First name is required for individual members',
        'first_name.max' => 'First name cannot exceed 100 characters',
        'middle_name.max' => 'Middle name cannot exceed 100 characters',
        'last_name.required_if' => 'Last name is required for individual members',
        'last_name.max' => 'Last name cannot exceed 100 characters',
        'gender.required_if' => 'Gender is required for individual members',
        'gender.in' => 'Invalid gender selected',
        'date_of_birth.required_if' => 'Date of birth is required for individual members',
        'date_of_birth.date' => 'Invalid date format',
        'date_of_birth.before' => 'Date of birth must be before today',
        'marital_status.required_if' => 'Marital status is required for individual members',
        'marital_status.in' => 'Invalid marital status selected',
        
        // Business/Group specific messages
        'business_name.required_if' => 'Business/Group name is required',
        'business_name.max' => 'Business/Group name cannot exceed 100 characters',
        'incorporation_number.required_if' => 'Incorporation number is required',
        'incorporation_number.max' => 'Incorporation number cannot exceed 50 characters',
        
        // Step 2: Contact Details
        'phone_number.required' => 'Phone number is required',
        'phone_number.regex' => 'Phone number must start with 0 and be 10-11 digits',
        'email.email' => 'Please enter a valid email address',
        'email.max' => 'Email cannot exceed 100 characters',
        'address.required' => 'Address is required',
        'address.max' => 'Address cannot exceed 255 characters',
        'nationality.required' => 'Nationality is required',
        'nationality.max' => 'Nationality cannot exceed 100 characters',
        'citizenship.required' => 'Citizenship is required',
        'citizenship.max' => 'Citizenship cannot exceed 100 characters',
        'next_of_kin_name.required_if' => 'Next of kin name is required for individual members',
        'next_of_kin_name.max' => 'Next of kin name cannot exceed 100 characters',
        'next_of_kin_phone.required_if' => 'Next of kin phone number is required for individual members',
        'next_of_kin_phone.regex' => 'Next of kin phone number must start with 0 and be 10-11 digits',
        
        // Step 3: Financial Information
        'income_available.required' => 'Income available is required',
        'income_available.numeric' => 'Income available must be a number',
        'income_available.min' => 'Income available must be greater than or equal to 0',
        'income_available.max' => 'Income available cannot exceed 999,999,999.99',
        'income_source.required' => 'Income source is required',
        'income_source.max' => 'Income source cannot exceed 100 characters',
        'tin_number.max' => 'TIN number cannot exceed 100 characters',
        'hisa.required' => 'Hisa amount is required',
        'hisa.numeric' => 'Hisa amount must be a number',
        'hisa.min' => 'Hisa amount must be at least 1,000',
        'hisa.max' => 'Hisa amount cannot exceed 999,999,999.99',
        'akiba.required' => 'Akiba amount is required',
        'akiba.numeric' => 'Akiba amount must be a number',
        'akiba.min' => 'Akiba amount must be at least 1,000',
        'akiba.max' => 'Akiba amount cannot exceed 999,999,999.99',
        'amana.required' => 'Amana amount is required',
        'amana.numeric' => 'Amana amount must be a number',
        'amana.min' => 'Amana amount must be at least 1,000',
        'amana.max' => 'Amana amount cannot exceed 999,999,999.99',
        'nbc_account_number.max' => 'NBC account number cannot exceed 12 characters',
        'nbc_account_number.string' => 'NBC account number must be a string',


        // Step 4: Guarantor & Documents
        'guarantor_member_number.exists' => 'Invalid guarantor membership number or member is not active',
        'guarantor_relationship.required_with' => 'Relationship with guarantor is required when guarantor is provided',
        'guarantor_relationship.max' => 'Relationship description cannot exceed 255 characters',
        'additionalDocuments.*.description.required' => 'Document description is required',
        'additionalDocuments.*.description.max' => 'Document description cannot exceed 255 characters',
        'additionalDocuments.*.file.required' => 'Document file is required',
        'additionalDocuments.*.file.max' => 'Document file must not exceed 10MB'
    ];

    public function mount()
    {
        // Initialize with one empty document slot
        $this->additionalDocuments = [
            ['file' => null, 'description' => 'Application Letter']
        ];
        
        // Fetch the categories when the component is mounted
        $this->memberCategories = DB::table('member_categories')->get();
    }


    public function viewPaidClients()
    {
        Log::info('Viewing paid clients', [
            'user_id' => auth()->id()
        ]);

        try {
            $this->resetViewClientsFlags();
            $this->viewpaid = true;

            // Load paid clients data
            $this->MembersList = ClientsModel::where('status', 'ACTIVE')
                ->whereHas('loans', function($query) {
                    $query->where('loan_status', 'PAID');
                })
                ->with(['loans', 'branch'])
                ->get();

        } catch (\Exception $e) {
            Log::error('Error loading paid clients', [
                'error' => $e->getMessage()
            ]);
            session()->flash('message', 'Failed to load paid clients: ' . $e->getMessage());
            session()->flash('alert-class', 'alert-danger');
        }
    }

    public function viewNotPaidClients()
    {
        Log::info('Viewing unpaid clients', [
            'user_id' => auth()->id()
        ]);

        try {
            $this->resetViewClientsFlags();
            $this->viewnotpaid = true;

            // Load unpaid clients data
            $this->MembersList = ClientsModel::where('status', 'ACTIVE')
                ->whereHas('loans', function($query) {
                    $query->where('loan_status', '!=', 'PAID');
                })
                ->with(['loans', 'branch'])
                ->get();

        } catch (\Exception $e) {
            Log::error('Error loading unpaid clients', [
                'error' => $e->getMessage()
            ]);
            session()->flash('message', 'Failed to load unpaid clients: ' . $e->getMessage());
            session()->flash('alert-class', 'alert-danger');
        }
    }

    public function viewAllClients()
    {
        Log::info('Viewing all clients', [
            'user_id' => auth()->id()
        ]);

        try {
            $this->resetViewClientsFlags();
            $this->allMembers = true;

            // Load all clients data
            $this->MembersList = ClientsModel::with(['loans', 'branch'])
                ->get();

        } catch (\Exception $e) {
            Log::error('Error loading all clients', [
                'error' => $e->getMessage()
            ]);
            session()->flash('message', 'Failed to load clients: ' . $e->getMessage());
            session()->flash('alert-class', 'alert-danger');
        }
    }

    public function viewClientLoan($client_number)
    {
        Log::info('Viewing client loans', [
            'user_id' => auth()->id(),
            'client_number' => $client_number
        ]);

        try {
            $this->resetClientLoanFlags();
            $this->viewClientDetails = true;
            $this->viewClientLoanData = true;

            // Load detailed loan data
            $this->loanStatus = DB::table('loans')
                ->where('client_number', $client_number)
                ->select([
                    'id',
                    'loan_number',
                    'loan_amount',
                    'loan_status',
                    'disbursement_date',
                    'due_date',
                    'amount_paid',
                    'remaining_amount',
                    'created_at',
                    'updated_at'
                ])
                ->get();

            // Store in session for persistence
            session()->put('viewMemberLoan', $client_number);

        } catch (\Exception $e) {
            Log::error('Error loading client loans', [
                'error' => $e->getMessage(),
                'client_number' => $client_number
            ]);
            session()->flash('message', 'Failed to load client loans: ' . $e->getMessage());
            session()->flash('alert-class', 'alert-danger');
        }
    }

    public function viewClientDetails($id)
    {
        Log::info('Viewing client details', [
            'user_id' => auth()->id(),
            'client_id' => $id
        ]);

        try {
            $client = ClientsModel::with(['loans', 'branch', 'savings', 'shares'])
                ->findOrFail($id);

            // Store client ID in session
            session()->put('viewClientId', $id);

            // Get client number and load loan status
            $client_number = $client->client_number;
            $this->loanStatus = DB::table('loans')
                ->where('client_number', $client_number)
                ->select([
                    'id',
                    'loan_number',
                    'loan_amount',
                    'loan_status',
                    'disbursement_date',
                    'due_date',
                    'amount_paid',
                    'remaining_amount'
                ])
                ->get();

            // Load client data into component properties
            $this->loadClientData($client);

            $this->toggleViewClientDetails();

        } catch (\Exception $e) {
            Log::error('Error loading client details', [
                'error' => $e->getMessage(),
                'client_id' => $id
            ]);
            session()->flash('message', 'Failed to load client details: ' . $e->getMessage());
            session()->flash('alert-class', 'alert-danger');
        }
    }

    protected function loadClientData($client)
    {
        $this->client = $client->id;
        $this->membership_type = $client->membership_type;
        $this->branch = $client->branch;
        $this->phone_number = $client->phone_number;
        $this->first_name = $client->first_name;
        $this->middle_name = $client->middle_name;
        $this->last_name = $client->last_name;
        $this->place_of_birth = $client->place_of_birth;
        $this->marital_status = $client->marital_status;
        $this->address = $client->address;
        $this->next_of_kin_name = $client->next_of_kin_name;
        $this->next_of_kin_phone = $client->next_of_kin_phone;
        $this->nationarity = $client->nationarity;
        $this->number_of_spouse = $client->number_of_spouse;
        $this->number_of_children = $client->number_of_children;
        $this->gender = $client->gender;
        $this->date_of_birth = $client->date_of_birth;
        $this->citizenship = $client->citizenship;
        $this->nida_number = $client->nida_number;
        $this->status = $client->status;
        $this->email = $client->email;
        $this->client_number = $client->client_number;
    }

    protected function resetViewClientsFlags() {
        $this->viewpaid = false;
        $this->viewnotpaid = false;
        $this->allMembers = false;
    }

    protected function resetClientLoanFlags() {
        $this->viewClientDetails = false;
        $this->viewClientLoanData = false;
    }

    protected function toggleViewClientDetails() {
        $this->viewClientDetails = !$this->viewClientDetails;
    }







    public function showAddClientModal($tabId)
    {
        if ($tabId === 3) {
            $this->showGenerateControlNumbers = true;
            return;
        }
        $randomNumber = rand(9000, 9999);
        $this->membershipNumber = str_pad($randomNumber, 4, '0', STR_PAD_LEFT);
        $this->selected = $tabId;
        $this->showAddClient = true;
        
        // Initialize additionalDocuments with a default document
        $this->additionalDocuments = [
            ['file' => null, 'description' => 'Application Letter']
        ];
    }




    public function updatedMember()
    {
        $memberData = ClientsModel::findOrFail($this->client); // Use findOrFail for better error handling
        $this->fill($memberData->toArray()); // Fill Livewire properties with retrieved model data
    }

    protected function membershipTypeRules()
    {
        // Define the validation rules based on membership type
        if ($this->membership_type === 'Individual') {
            return $this->individual;
        } else {
            return $this->business;
        }
    }

    public function updateClient()
    {
        Log::info('Attempting to update client', [
            'user_id' => auth()->id(),
            'client_id' => $this->client
        ]);

        try {
            DB::beginTransaction();

            $client = ClientsModel::findOrFail($this->client);

            // Validate based on membership type
            $this->validate($this->membershipTypeRules());

            // Prepare update data
            $updateData = [
                'membership_type' => $this->membership_type,
                'branch' => $this->branch,
                'phone_number' => $this->phone_number,
                'first_name' => strtoupper($this->first_name),
                'middle_name' => strtoupper($this->middle_name),
                'last_name' => strtoupper($this->last_name),
                'place_of_birth' => $this->place_of_birth,
                'marital_status' => $this->marital_status,
                'address' => $this->address,
                'next_of_kin_name' => $this->next_of_kin_name,
                'next_of_kin_phone' => $this->next_of_kin_phone,
                'nationarity' => $this->nationarity,
                'number_of_spouse' => $this->number_of_spouse,
                'number_of_children' => $this->number_of_children,
                'gender' => $this->gender,
                'date_of_birth' => $this->date_of_birth,
                'citizenship' => $this->citizenship,
                'nida_number' => $this->nida_number,
                'updated_by' => auth()->id()
            ];

            // Create approval record
            approvals::create([
                'institution' => $client->id,
                'process_name' => 'editClient',
                'process_description' => auth()->user()->name . ' has requested to edit client: ' . $client->first_name . ' ' . $client->last_name,
                'approval_process_description' => 'has approved client information update',
                'process_code' => '03',
                'process_id' => $client->id,
                'process_status' => 'PENDING',
                'user_id' => auth()->user()->id,
                'team_id' => 1,
                'approver_id' => 1,
                'approval_status' => 'PENDING',
                'edit_package' => json_encode($updateData)
            ]);

            DB::commit();

            Log::info('Client update submitted successfully', [
                'client_id' => $client->id,
                'client_number' => $client->client_number
            ]);

            session()->flash('message', 'Client update submitted for approval');
            session()->flash('alert-class', 'alert-success');

            $this->resetData();
            $this->closeModal();
            $this->emit('refreshClientsList');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('message', 'Failed to update client: ' . $e->getMessage());
            session()->flash('alert-class', 'alert-danger');
        }
    }

    public function addClient()
    {
        Log::info('Attempting to add new client', [
            'user_id' => auth()->id(),
            'client_type' => $this->membership_type
        ]);

        try {
            DB::beginTransaction();

            // Validate based on membership type
            $this->validate($this->membershipTypeRules());

            // Generate client number
            $latestClient = ClientsModel::latest()->first();
            $this->client_number = $latestClient ? $latestClient->client_number + 1 : 1001;

            // Generate account number (initially client number as base as base)
            //now if nbc account number is provided, use it as base
            $this->account_number = $this->nbc_account_number ?? $this->client_number;

            // Prepare client data
            $clientData = [
                'client_number' => $this->client_number,
                'account_number' => $this->account_number,
                'membership_type' => $this->membership_type,
                'branch' => $this->branch,
                'phone_number' => $this->phone_number,
                'first_name' => strtoupper($this->first_name),
                'middle_name' => strtoupper($this->middle_name),
                'last_name' => strtoupper($this->last_name),
                'place_of_birth' => $this->place_of_birth,
                'marital_status' => $this->marital_status,
                'address' => $this->address,
                'next_of_kin_name' => $this->next_of_kin_name,
                'next_of_kin_phone' => $this->next_of_kin_phone,
                'nationarity' => $this->nationarity,
                'number_of_spouse' => $this->number_of_spouse,
                'number_of_children' => $this->number_of_children,
                'gender' => $this->gender,
                'date_of_birth' => $this->date_of_birth,
                'citizenship' => $this->citizenship,
                'nida_number' => $this->nida_number,
                'status' => 'PENDING',
                'created_by' => auth()->id(),
                'branch_id' => $this->branch
            ];

            // Create client record
            $client = ClientsModel::create($clientData);

            // Create approval record
            approvals::create([
                'institution' => $client->id,
                'process_name' => 'addClient',
                'process_description' => auth()->user()->name . ' has requested to add new client: ' . $client->first_name . ' ' . $client->last_name,
                'approval_process_description' => 'has approved new client registration',
                'process_code' => '03',
                'process_id' => $client->id,
                'process_status' => 'PENDING',
                'user_id' => auth()->user()->id,
                'team_id' => 1,
                'approver_id' => 1,
                'approval_status' => 'PENDING',
                'edit_package' => json_encode($clientData)
            ]);

            DB::commit();

            Log::info('Client registration submitted successfully', [
                'client_id' => $client->id,
                'client_number' => $client->client_number
            ]);

            session()->flash('message', 'Client registration submitted for approval');
            session()->flash('alert-class', 'alert-success');

            $this->resetData();
            $this->closeModal();
            $this->emit('refreshClientsList');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error adding client', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('message', 'Failed to add client: ' . $e->getMessage());
            session()->flash('alert-class', 'alert-danger');
        }
    }

    public function delete()
    {
        Log::info('Attempting to delete/change client status', [
            'user_id' => auth()->id(),
            'client_id' => $this->clientSelected,
            'action' => $this->permission
        ]);

        try {
            DB::beginTransaction();

            $client = ClientsModel::findOrFail($this->clientSelected);

            // Check for dependencies
            $dependencies = [
                'loans' => DB::table('loans')->where('client_number', $client->client_number)->exists(),
                'savings' => DB::table('savings')->where('client_number', $client->client_number)->exists(),
                'shares' => false // Removed shares table reference
            ];

            $hasDependencies = collect($dependencies)->contains(true);

            if ($hasDependencies) {
                $dependencyList = collect($dependencies)
                    ->filter()
                    ->keys()
                    ->implode(', ');
                throw new \Exception("Cannot delete client: Has associated records in: {$dependencyList}");
            }

            $action = '';
            $description = '';

            switch ($this->permission) {
                case 'BLOCKED':
                    $action = 'blockClient';
                    $description = 'Block client';
                    break;
                case 'ACTIVE':
                    $action = 'activateClient';
                    $description = 'Activate client';
                    break;
                case 'DELETED':
                    $action = 'deleteClient';
                    $description = 'Delete client';
                    break;
                default:
                    throw new \Exception('Invalid permission status');
            }

            // Create approval record
            approvals::create([
                'institution' => $client->id,
                'process_name' => $action,
                'process_description' => auth()->user()->name . ' has requested to ' . strtolower($description) . ': ' . $client->first_name . ' ' . $client->last_name,
                'approval_process_description' => 'has approved ' . strtolower($description) . ' for client: ' . $client->first_name . ' ' . $client->last_name,
                'process_code' => '03',
                'process_id' => $client->id,
                'process_status' => 'PENDING',
                'user_id' => auth()->user()->id,
                'team_id' => 1,
                'approver_id' => 1,
                'approval_status' => 'PENDING',
                'edit_package' => json_encode([
                    'client_id' => $client->id,
                    'client_number' => $client->client_number,
                    'client_name' => $client->first_name . ' ' . $client->last_name,
                    'current_status' => $client->status,
                    'requested_status' => $this->permission,
                    'action' => $action,
                    'requested_by' => auth()->user()->name,
                    'requested_at' => now()->toDateTimeString()
                ])
            ]);

            DB::commit();

            Log::info('Client status change submitted successfully', [
                'client_id' => $client->id,
                'action' => $action
            ]);

            session()->flash('message', 'Client status change submitted for approval');
            session()->flash('alert-class', 'alert-success');

            $this->closeModal();
            $this->emit('refreshClientsList');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error changing client status', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('message', 'Failed to change client status: ' . $e->getMessage());
            session()->flash('alert-class', 'alert-danger');
        }
    }

    public function blockClientModal($id)
    {
        Log::info('Opening block client modal', [
            'user_id' => auth()->id(),
            'client_id' => $id
        ]);

        $this->clientSelected = $id;
        $this->showDeleteClient = true;
    }

    public function editClientModal($id)
    {
        Log::info('Opening edit client modal', [
            'user_id' => auth()->id(),
            'client_id' => $id
        ]);

        try {
            $client = ClientsModel::findOrFail($id);

            $this->client = $id;
            $this->membership_type = $client->membership_type;
            $this->branch = $client->branch;
            $this->phone_number = $client->phone_number;
            $this->first_name = $client->first_name;
            $this->middle_name = $client->middle_name;
            $this->last_name = $client->last_name;
            $this->place_of_birth = $client->place_of_birth;
            $this->marital_status = $client->marital_status;
            $this->address = $client->address;
            $this->next_of_kin_name = $client->next_of_kin_name;
            $this->next_of_kin_phone = $client->next_of_kin_phone;
            $this->nationarity = $client->nationarity;
            $this->number_of_spouse = $client->number_of_spouse;
            $this->number_of_children = $client->number_of_children;
            $this->gender = $client->gender;
            $this->date_of_birth = $client->date_of_birth;
            $this->citizenship = $client->citizenship;
            $this->nida_number = $client->nida_number;

            $this->showEditClient = true;

        } catch (\Exception $e) {
            Log::error('Error loading client data for edit', [
                'error' => $e->getMessage(),
                'client_id' => $id
            ]);
            session()->flash('message', 'Failed to load client data: ' . $e->getMessage());
            session()->flash('alert-class', 'alert-danger');
        }
    }

    public function generateControlNumber()
    {
        Log::info('Generating control number', [
            'user_id' => auth()->id()
        ]);

        try {
            $controlNumber = strtoupper(uniqid('CN'));

            // Store control number
            $this->generatedControlNumbers[] = [
                'number' => $controlNumber,
                'generated_at' => now()->toDateTimeString()
            ];

            // Create member object for notification
            $member = (object)[
                'name' => $this->first_name . ' ' . $this->last_name,
                'email' => $this->email,
                'phone_number' => $this->phone_number
            ];

            // Send notifications
            if ($this->phone_number) {
                $this->sendSMS($this->phone_number, "Welcome to NBC SACCOS! Your control number is: {$controlNumber}. Please make payment to complete your registration.");
            }

            if ($this->email) {
                // Get account information from session
                $sharesAccount = session()->get('saved_credit_account');
                
                // Send welcome notification with control number and account information
                $member->notify(new NewMemberWelcomeNotification(
                    $member, 
                    $controlNumber,
                    $sharesAccount,
                    $savingsAccount ?? null,
                    $depositsAccount ?? null
                ));
            }

            $this->showControlNumbersModal = true;

        } catch (\Exception $e) {
            Log::error('Error generating control number', [
                'error' => $e->getMessage()
            ]);
            session()->flash('message', 'Failed to generate control number: ' . $e->getMessage());
            session()->flash('alert-class', 'alert-danger');
        }
    }

    private function sendSMS($phoneNumber, $message)
    {
        try {
            // Implement your SMS sending logic here
            // Example using a hypothetical SMS service:
            $response = Http::post('your-sms-service-url', [
                'phone' => $phoneNumber,
                'message' => $message
            ]);

            if (!$response->successful()) {
                throw new \Exception('SMS sending failed: ' . $response->body());
            }

            Log::info('SMS sent successfully', [
                'phone' => $phoneNumber
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending SMS', [
                'error' => $e->getMessage(),
                'phone' => $phoneNumber
            ]);
            throw $e;
        }
    }

    private function sendEmail($email, $subject, $message)
    {
        try {
            Mail::to($email)->send(new ControlNumberGenerated($subject, $message));

            Log::info('Email sent successfully', [
                'email' => $email
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending email', [
                'error' => $e->getMessage(),
                'email' => $email
            ]);
            throw $e;
        }
    }

    public function closeControlNumbersModal()
    {
        $this->showControlNumbersModal = false;
        $this->generatedControlNumbers = [];
        $this->client_type = 'Individual';
        $this->first_name = null;
        $this->middle_name = null;
        $this->last_name = null;
        $this->full_name = null;
        $this->phone_number = null;
        $this->email = null;
    }

    public function nextStep()
    {
        Log::info('Starting nextStep', [
            'current_step' => $this->currentStep,
            'membership_type' => $this->membership_type,
            'user_id' => auth()->id()
        ]);

        try {
            Log::info('Validating current step', ['step' => $this->currentStep]);
            $this->validateStep();
            
            if ($this->currentStep === 4) {
                Log::info('Generating member number for step 4');
                // Generate member number first
                $memberNumberGenerator = new MemberNumberGeneratorService();
                $this->client_number = $memberNumberGenerator->generate();
                Log::info('Member number generated', ['client_number' => $this->client_number]);
                
                // Then generate control numbers
                Log::info('Generating control numbers');
                $this->generateControlNumbers();
                Log::info('Control numbers generated', ['count' => count($this->generatedControlNumbers)]);
                
                session()->flash('success', 'Member number and control numbers generated successfully!');
            }
            
            $this->currentStep++;
            Log::info('Moving to next step', ['new_step' => $this->currentStep]);
            
            // Scroll to top of form
            $this->dispatchBrowserEvent('scrollToTop');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error in nextStep', [
                'step' => $this->currentStep,
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            session()->flash('error', 'Please check the form for errors: ' . $e->getMessage());
            throw $e;
        } catch (\Exception $e) {
            Log::error('Unexpected error in nextStep', [
                'step' => $this->currentStep,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            session()->flash('error', 'An error occurred: ' . $e->getMessage());
            throw $e;
        }
    }

    public function previousStep()
    {
        Log::info('Moving to previous step', [
            'current_step' => $this->currentStep,
            'new_step' => $this->currentStep - 1,
            'user_id' => auth()->id()
        ]);

        $this->currentStep--;
        
        // Scroll to top of form
        $this->dispatchBrowserEvent('scrollToTop');
    }

    /**
     * Validates the current step of the multi-step form based on the current step number.
     * Each step has its own set of validation rules for different form fields.
     *
     * @return void
     */
    public function validateStep()
    {
        Log::info('Starting step validation', [
            'step' => $this->currentStep,
            'membership_type' => $this->membership_type
        ]);

        $rules = [];
        $messages = [];

        switch ($this->currentStep) {
            case 1:
                Log::info('Validating step 1 - Personal Information');
                $rules = [
                    'membership_type' => 'required|in:Individual,Group,Business',
                    'branch' => 'required|exists:branches,id',
                ];

                if ($this->membership_type === 'Individual') {
                    Log::info('Adding individual member validation rules');
                    $rules = array_merge($rules, [
                        'first_name' => 'required|string|max:100',
                        'middle_name' => 'nullable|string|max:100',
                        'last_name' => 'required|string|max:100',
                        'gender' => 'required|in:male,female',
                        'date_of_birth' => 'required|date|before:today',
                        'marital_status' => 'required|in:single,married,divorced,widowed',
                    ]);
                } else {
                    Log::info('Adding business/group validation rules');
                    $rules = array_merge($rules, [
                        'business_name' => 'required|string|max:100',
                        'incorporation_number' => 'required|string|max:50',
                    ]);
                }
                break;

            case 2:
                Log::info('Validating step 2 - Contact Details');
                $rules = [
                    'phone_number' => 'required|string|regex:/^0[0-9]{9,10}$/',
                    'email' => 'nullable|email|max:100',
                    'address' => 'required|string|max:255',
                    'nationality' => 'required|string|max:100',
                    'citizenship' => 'required|string|max:100',
                ];

                if ($this->membership_type === 'Individual') {
                    Log::info('Adding individual member contact validation rules');
                    $rules = array_merge($rules, [
                        'next_of_kin_name' => 'required|string|max:100',
                        'next_of_kin_phone' => 'required|string|regex:/^0[0-9]{9,10}$/',
                    ]);
                }
                break;

            case 3:
                Log::info('Validating step 3 - Financial Information');
                $rules = [
                    'income_available' => 'required|numeric|min:0|max:999999999.99',
                    'income_source' => 'required|string|max:100',
                    'tin_number' => 'nullable|string|max:100',
                    'hisa' => 'required|numeric|min:1000|max:999999999.99',
                    'akiba' => 'required|numeric|min:1000|max:999999999.99',
                    'amana' => 'required|numeric|min:1000|max:999999999.99',
                ];
                break;

            case 4:
                Log::info('Validating step 4 - Documents and Guarantor');
                if ($this->photo) {
                    Log::info('Validating photo upload');
                    $rules['photo'] = 'required|image|max:10240';
                }

                Log::info('Validating guarantor information', [
                    'guarantor_number' => $this->guarantor_member_number
                ]);

                // Check if guarantor exists and is active
                if ($this->guarantor_member_number) {
                    $guarantor = ClientsModel::where('client_number', $this->guarantor_member_number)
                        ->where('status', 'ACTIVE')
                        ->first();

                    Log::info('Guarantor validation check', [
                        'exists' => (bool)$guarantor,
                        'status' => $guarantor ? $guarantor->status : null
                    ]);
                }

                $rules = array_merge($rules, [
                    'guarantor_member_number' => 'required|exists:clients,client_number,status,ACTIVE',
                    'guarantor_relationship' => 'required|string|max:255',
                ]);

                // Validate at least one document
                Log::info('Checking document uploads', [
                    'document_count' => count($this->additionalDocuments),
                    'has_first_document' => isset($this->additionalDocuments[0]['file'])
                ]);
                if (empty($this->additionalDocuments) || !isset($this->additionalDocuments[0]['file'])) {
                    Log::error('Document validation failed - no documents uploaded');
                    throw new \Exception('At least one document is required.');
                }
                break;
        }

        Log::info('Validation rules prepared', [
            'step' => $this->currentStep,
            'rule_count' => count($rules)
        ]);

        try {
            $this->validate($rules, $this->messages);
            Log::info('Step validation successful', ['step' => $this->currentStep]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation failed', [
                'step' => $this->currentStep,
                'errors' => $e->errors(),
                'message' => $e->getMessage()
            ]);
            session()->flash('error', 'Please check the form for errors.');
            throw $e;
        }
    }

    public function resetForm()
    {
        $this->reset([
            'membership_type', 'branch', 'phone_number', 'email', 'address', 'nationarity',
            'citizenship', 'first_name', 'middle_name', 'last_name', 'gender', 'date_of_birth',
            'marital_status', 'next_of_kin_name', 'next_of_kin_phone', 'business_name',
            'incorporation_number', 'income_available', 'income_source', 'tin_number',
            'hisa', 'akiba', 'amana', 'photo', 'guarantor_first_name',
            'guarantor_middle_name', 'guarantor_last_name', 'guarantor_full_name',
            'guarantor_email'
        ]);
        
        // Initialize additionalDocuments with a default document
        $this->additionalDocuments = [
            ['file' => null, 'description' => 'Application Letter']
        ];
        
        $this->currentStep = 1;
    }

    public function updatedMembershipType($value)
    {
        // Reset fields when membership type changes
        if ($value === 'Individual') {
            $this->reset(['business_name', 'incorporation_number']);
        } else {
            $this->reset(['first_name', 'middle_name', 'last_name', 'gender', 'date_of_birth', 'marital_status']);
        }
    }

    public function save()
    {
        Log::info('Starting member registration process', [
            'user_id' => auth()->id(),
            'membership_type' => $this->membership_type,
            'current_step' => $this->currentStep,
            'guarantor_number' => $this->guarantor_member_number
        ]);

        try {
            DB::beginTransaction();
            Log::info('Database transaction started');
            
            // Generate client number using the service
            Log::info('Generating member number');
            $memberNumberGenerator = new MemberNumberGeneratorService();
            $this->client_number = $memberNumberGenerator->generate();
            Log::info('Member number generated', ['client_number' => $this->client_number]);
            
            // Generate account number (initially client number as base as base)
            //now if nbc account number is provided, use it as base
            $this->account_number = $this->nbc_account_number ?? $this->client_number;
            
            // Prepare client data
            Log::info('Preparing client data', ['membership_type' => $this->membership_type]);
            $clientData = [
                'client_number' => $this->client_number,
                'account_number' => $this->account_number,
                'membership_type' => $this->membership_type,
                'branch' => $this->branch,
                'phone_number' => $this->phone_number,
                'email' => $this->email,
                'address' => $this->address,
                'nationarity' => $this->nationarity,
                'citizenship' => $this->citizenship,
                'income_available' => $this->income_available,
                'income_source' => $this->income_source,
                'tin_number' => $this->tin_number,
                'hisa' => $this->hisa,
                'akiba' => $this->akiba,
                'amana' => $this->amana,
                'status' => 'PENDING',
                'branch_id' => $this->branch,
                'created_by' => auth()->id()
            ];
            
            // Add type-specific data
            if ($this->membership_type === 'Individual') {
                Log::info('Adding individual member specific data');
                $clientData = array_merge($clientData, [
                    'first_name' => strtoupper($this->first_name),
                    'middle_name' => strtoupper($this->middle_name),
                    'last_name' => strtoupper($this->last_name),
                    'gender' => $this->gender,
                    'date_of_birth' => $this->date_of_birth,
                    'marital_status' => $this->marital_status,
                    'next_of_kin_name' => $this->next_of_kin_name,
                    'next_of_kin_phone' => $this->next_of_kin_phone,
                ]);
            } else {
                Log::info('Adding business/group specific data');
                $clientData = array_merge($clientData, [
                    'business_name' => $this->business_name,
                    'incorporation_number' => $this->incorporation_number,
                ]);
            }
            
            // Create client record
            Log::info('Creating client record');
            $client = ClientsModel::create($clientData);
            Log::info('Client record created', ['client_id' => $client->id]);
            
            // Handle photo upload if exists
            if ($this->photo) {
                Log::info('Processing profile photo upload');
                $photoPath = $this->photo->store('profile-photos', 'public');
                DB::table('client_documents')->insert([
                    'client_id' => $client->id,
                    'document_type' => 'profile_photo',
                    'file_path' => $photoPath,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
                Log::info('Profile photo uploaded', ['photo_path' => $photoPath]);
            }
            
            // Handle all documents
            Log::info('Processing additional documents', ['document_count' => count($this->additionalDocuments)]);
            foreach ($this->additionalDocuments as $index => $document) {
                if ($document['file']) {
                    Log::info('Processing document', ['index' => $index, 'description' => $document['description']]);
                    $filePath = $document['file']->store('client-documents', 'public');
                    DB::table('client_documents')->insert([
                        'client_id' => $client->id,
                        'document_type' => strtolower($document['description']),
                        'file_path' => $filePath,
                        'description' => $document['description'],
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                    Log::info('Document uploaded', ['file_path' => $filePath]);
                }
            }
            
            // Create guarantor record if exists
            $guarantorMember = null;
            if ($this->guarantor_member_number) {
                Log::info('Processing guarantor information', [
                    'guarantor_number' => $this->guarantor_member_number
                ]);

                $guarantorMember = ClientsModel::where('client_number', $this->guarantor_member_number)
                    ->where('status', 'ACTIVE')
                    ->first();

                Log::info('Guarantor lookup result', [
                    'found' => (bool)$guarantorMember,
                    'status' => $guarantorMember ? $guarantorMember->status : null
                ]);

                if (!$guarantorMember) {
                    Log::error('Invalid guarantor membership number', [
                        'guarantor_number' => $this->guarantor_member_number,
                        'error' => 'Guarantor not found or not active'
                    ]);
                    throw new \Exception('Invalid guarantor membership number. Please provide a valid active member number.');
                }
                
                DB::table('guarantors')->insert([
                    'client_id' => $client->id,
                    'guarantor_member_id' => $guarantorMember->id,
                    'relationship' => $this->guarantor_relationship,
                    'notes' => null,
                    'is_active' => true,
                    'guarantee_start_date' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                Log::info('Guarantor record created', ['guarantor_id' => $guarantorMember->id]);
            }
            
            // Create approval request
            Log::info('Creating approval request');
            $approvalData = [
             
                'process_name' => 'new_member_registration',
                'process_description' => auth()->user()->name . ' has requested to register a new member: ' . 
                    ($this->membership_type === 'Individual' ? 
                        $this->first_name . ' ' . $this->last_name : 
                        $this->business_name),
                'approval_process_description' => 'New member registration approval required',
                'process_code' => 'MEMBER_REG',
                'process_id' => $client->id,
                'process_status' => 'PENDING',
                'user_id' => auth()->user()->id,
                'team_id' => auth()->user()->current_team_id,
                'approver_id' => null,
                'approval_status' => 'PENDING',
                'edit_package' => null
            ];
            
            $approval = approvals::create($approvalData);
            Log::info('Approval request created', ['approval_id' => $approval->id]);

            $institution = DB::table('institutions')->where('id',1)->first();


            $accountService = new AccountCreationService();

      

            $mandatorySharesAccount = $institution->mandatory_shares_account;
            $mandatorySavingsAccount = $institution->mandatory_savings_account;
            $mandatoryDepositsAccount = $institution->mandatory_deposits_account;

            //dd($this->client_number );

            $account_name = $this->membership_type === 'Individual' ?  $this->first_name . ' ' . $this->last_name : $this->business_name;


            $sharesAccount = $accountService->createAccount([
                'account_use' => 'external',
                'account_name' => 'MANDATORY SHARES: '.$this->first_name.' '.$this->middle_name.' '.$this->last_name,
                'type' => 'capital_accounts',
                'product_number' => '1000',
                'member_number' => $this->client_number,
                'branch_number' => auth()->user()->branch ?? '1'
            ], $mandatorySharesAccount);

            // Log before storing in session
            Log::info('Storing shares account in session', [
                'account_number' => $sharesAccount->account_number,
                'account_name' => $sharesAccount->account_name,
                'type' => $sharesAccount->type,
                'user_id' => auth()->id()
            ]);

            // Store shares account in session
            session()->put('saved_credit_account', $sharesAccount);

            // Log after storing in session
            Log::info('Shares account stored in session successfully', [
                'account_number' => $sharesAccount->account_number,
                'session_key' => 'saved_credit_account',
                'user_id' => auth()->id()
            ]);

            $savingsAccount = $accountService->createAccount([
                'account_use' => 'external',
                'account_name' => 'MANDATORY SAVINGS: '.$this->first_name.' '.$this->middle_name.' '.$this->last_name,
                'type' => 'liability_accounts',
                'product_number' => '2000',
                'member_number' => $this->client_number,
                'branch_number' => auth()->user()->branch ?? '1'
            ], $mandatorySavingsAccount);

            $depositsAccount = $accountService->createAccount([
                'account_use' => 'external',
                'account_name' => 'MANDATORY DEPOSITS: '.$this->first_name.' '.$this->middle_name.' '.$this->last_name,
                'type' => 'liability_accounts',
                'product_number' => '3000',
                'member_number' => $this->client_number,
                'branch_number' => auth()->user()->branch ?? '1'
            ], $mandatoryDepositsAccount);
            
            // Create bills for each service
            Log::info('Creating service bills', ['control_numbers_count' => count($this->generatedControlNumbers)]);
            foreach ($this->generatedControlNumbers as $control) {
                Log::info('Processing service bill', ['service_code' => $control['service_code']]);
                $service = DB::table('services')
                    ->where('code', $control['service_code'])
                    ->first();

                if ($service) {
                    $billingService = new BillingService();
                    $bill = $billingService->createBill(
                        $this->client_number,
                        $service->id,
                        $service->is_recurring,
                        $service->payment_mode,
                        $control['control_number'],
                        $service->lower_limit
                    );

                    Log::info('Service bill created', [
                        'service_id' => $service->id,
                        'control_number' => $control['control_number']
                    ]);
                } else {
                    Log::warning('Service not found', ['service_code' => $control['service_code']]);
                }
            }
            
            DB::commit();
            Log::info('Database transaction committed successfully');

            // Dispatch background job for notifications
            Log::info('Dispatching member notifications', [
                'client' => [
                    'id' => $client->id,
                    'name' => $client->first_name . ' ' . $client->last_name,
                    'email' => $client->email,
                    'phone' => $client->phone_number
                ],
                'guarantor' => $guarantorMember ? [
                    'id' => $guarantorMember->id,
                    'name' => $guarantorMember->first_name . ' ' . $guarantorMember->last_name,
                    'email' => $guarantorMember->email,
                    'phone' => $guarantorMember->phone_number
                ] : null,
                'control_numbers' => $this->generatedControlNumbers
            ]);

            $institution_id = DB::table('institutions')->where('id',1)->value('institution_id');
            $saccos = preg_replace('/[^0-9]/', '', $institution_id); // Remove non-numeric characters



            ////////////////LINK GENERATION////////////////////
            
            // Generate payment link for member registration fees
            try {
                Log::info('Generating payment link for member registration', [
                    'client_number' => $this->client_number,
                    'member_name' => $account_name
                ]);
                
                $paymentService = new PaymentLinkService();
                
                // Get all bills for this client
                $bills = DB::table('bills')
                    ->join('services', 'bills.service_id', '=', 'services.id')
                    ->where('bills.client_number', $this->client_number)
                    ->where('bills.payment_status', '!=', 'PAID') // Only unpaid bills
                    ->select(
                        'bills.id as bill_id',
                        'bills.control_number',
                        'bills.bill_amount',
                        'bills.payment_mode',
                        'services.code as service_code',
                        'services.name as service_name'
                    )
                    ->get();
                
                // Prepare payment items from bills
                $items = [];
                foreach ($bills as $bill) {
                    $items[] = [
                        'type' => 'service',
                        'product_service_reference' => $bill->id,
                        'product_service_name' => $bill->service_name,
                        'amount' => $bill->bill_amount,
                        'is_required' => true,
                        'allow_partial' => $bill->payment_mode === 'partial'
                    ];
                }
                
                Log::info('Bills found for payment link', [
                    'client_number' => $this->client_number,
                    'bills_count' => count($bills),
                    'total_amount' => $bills->sum('bill_amount')
                ]);
                
                if (!empty($items)) {
                    $paymentData = [
                        'description' => 'SACCOS Member Registration - ' . $account_name,
                        'target' => 'individual',
                        'customer_reference' => $this->client_number,
                        'customer_name' => $account_name,
                        'customer_phone' => $this->phone_number,
                        'customer_email' => $this->email,
                        'expires_at' => now()->addDays(7)->toIso8601String(),
                        'items' => $items
                    ];
                    
                    $paymentResponse = $paymentService->generateUniversalPaymentLink($paymentData);
                    $paymentUrl = $paymentResponse['data']['payment_url'] ?? null;
                    
                    if ($paymentUrl) {
                        Log::info('Payment link generated successfully', [
                            'payment_url' => $paymentUrl,
                            'link_id' => $paymentResponse['data']['link_id'] ?? null,
                            'total_amount' => $paymentResponse['data']['total_amount'] ?? null
                        ]);
                        
                        // Store payment link in bills table for all bills included in this payment
                        $billIds = $bills->pluck('bill_id')->toArray();
                        DB::table('bills')->whereIn('id', $billIds)->update([
                            'payment_link' => $paymentUrl,
                            'payment_link_id' => $paymentResponse['data']['link_id'] ?? null,
                            'payment_link_generated_at' => now(),
                            'payment_link_items' => json_encode($paymentResponse['data']['items'] ?? [])
                        ]);
                        
                      
                    } else {
                        Log::warning('Payment link generation did not return URL', [
                            'response' => $paymentResponse
                        ]);
                        $paymentUrl = env('PAYMENT_LINK') . '/' . $saccos . '/' . $this->client_number;
                    }
                } else {
                    Log::info('No payment items to generate link for', [
                        'client_number' => $this->client_number
                    ]);
                    $paymentUrl = env('PAYMENT_LINK') . '/' . $saccos . '/' . $this->client_number;
                }
            } catch (\Exception $e) {
                Log::error('Failed to generate payment link', [
                    'error' => $e->getMessage(),
                    'client_number' => $this->client_number
                ]);
                // Fallback to legacy URL format
                $paymentUrl = env('PAYMENT_LINK') . '/' . $saccos . '/' . $this->client_number;
            }








            ProcessMemberNotifications::dispatch($client, $this->generatedControlNumbers, $paymentUrl)
                ->onQueue('notifications');
            
            session()->flash('success', 'Member registration submitted successfully! Your member number is: ' . $this->client_number);
           $this->resetForm();
           $this->emit('refreshClientsList');
            
            Log::info('Member registration completed successfully', [
                'client_id' => $client->id,
                'client_number' => $this->client_number
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Member registration failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
            session()->flash('error', 'Failed to save member: ' . $e->getMessage());
        }
    }

    public function addDocument()
    {
        $this->additionalDocuments[] = ['file' => null, 'description' => ''];
    }

    public function removeDocument($index)
    {
        unset($this->additionalDocuments[$index]);
        $this->additionalDocuments = array_values($this->additionalDocuments);
    }

    public function verifyMembership($membershipNumber)
    {
        Log::info('Verifying guarantor membership', [
            'membership_number' => $membershipNumber,
            'user_id' => auth()->id()
        ]);

        $verificationService = new MembershipVerificationService();
        $result = $verificationService->verifyMembership($membershipNumber);

        Log::info('Guarantor verification result', [
            'exists' => $result,
            
        ]);

        $this->guarantorVerification = $result['member'];
        $this->guarantorVerificationMessage = $result['message'];
        $this->guarantorVerificationStatus = $result['exists'] ? 'success' : 'error';

        if ($result['exists']) {
            $this->guarantor_member_number = $membershipNumber;
        }
    }

    protected function generateControlNumbers()
    {
        $billingService = new BillingService();
        
        // Get all required services in a single query
        $services = DB::table('services')
            ->whereIn('code', ['REG', 'SHC'])
            ->select('id', 'code', 'name', 'is_recurring', 'payment_mode', 'lower_limit')
            ->get()
            ->keyBy('code');

        $this->generatedControlNumbers = [];

        // Generate control numbers for each service
        foreach (['REG', 'SHC'] as $serviceCode) {
            $service = $services[$serviceCode];
            $controlNumber = $billingService->generateControlNumber(
                $this->client_number,
                $service->id,
                $service->is_recurring,
                $service->payment_mode
            );

            $this->generatedControlNumbers[] = [
                'service_code' => $service->code,
                'control_number' => $controlNumber,
                'amount' => $service->lower_limit
            ];
        }
    }

    // Add this method to handle real-time validation
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }
}
