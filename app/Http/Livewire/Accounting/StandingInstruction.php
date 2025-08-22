<?php

namespace App\Http\Livewire\Accounting;

use App\Models\approvals;
use App\Services\StangingOrder\RegisterService;
use App\Services\SystemReferenceNumberService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Models\Role;


class StandingInstruction extends Component
{
    public $tab_id=1;
    public $member_id;
    public $source_bank_id;
    public $destination_account_name;
    public $bank = "NBC";
    public $destination_account_id;
    public $saccos_branch_id;
    public $frequency;
    public $start_date;
    public $reference_number;

public $new_stannding_order=false;
public $source_account_number;
public $destination_type;
public $amount;
public $destination_account_number;
public $action_date;
public $end_date,$service;
public $description;
public $member_number,$organisation,$selected_member_number;
public $bank_list,$branch_id,$account_id;
public $bank_id ,$selectedMemberId ,$showDropdown ,$search ,$branch_list;

public $members,$RNN, $institution_name ,$bank_name="NBC",$saccoss_account_list=[];

protected $rules=[
   'amount'=>'required|numeric',
   'source_account_number'=>'required|string',
   'destination_account_number'=>'required|string',
    'end_date'=>'required|date',
    'action_date'=>'required|date',
    'bank_id'=>'required'
];


public function showAllMembers()
{
    $this->showDropdown = true;
    $this->members=DB::table('clients')->get();
}

public function menuItemClicked(){
    if($this->new_stannding_order==false){
        $this->new_stannding_order=true;
    }
}


function boot(){

    // $this->saccoss_account_list=DB::table('accounts')->where('category_code',1000)->get();

    $organization= DB::table('institutions')->find(1);
    $this->destination_account_name= $organization->institution_name;
    $this->organisation =$organization->institution_id;


    // $this->branch_list= DB::table('branches')->where('status',"ACTIVE")->get();
}

public function selectMember($memberId)
{
    $this->selectedMemberId = $memberId;
    $this->member_id=$memberId;

    // Optional: Set the selected member's name in the search input
    $selectedMember = DB::table('clients')->find($memberId);
    $this->search = $selectedMember->first_name . ' ' . $selectedMember->middle_name . ' ' . $selectedMember->last_name;
    $this->selected_member_number = $selectedMember->client_number;

    // Clear the dropdown options after selecting
    $this->showDropdown = false;
}

function getRefereceNumber(){

    $reference= new SystemReferenceNumberService();

     $organization_id=$this->organisation;
     $memberNumber=$this->selected_member_number;
     $service=$this->service;

     if( $organization_id && $memberNumber && $service  ){
        return $reference->generateReferenceNumber($organization_id, $memberNumber,$service,1234456);

     }
     return null;
}

function register(){

    $this->validate([
        'member_id' => 'required|integer',
        'source_account_number' => 'required|string|max:255',
        'source_bank_id' => 'required|integer',
        'destination_account_name' => 'required|string|max:255',
        'bank' => 'string|max:255',
        'destination_account_id' => 'required|integer',
        'saccos_branch_id' => 'required|integer',
        'amount' => 'required|numeric|min:0',
        'frequency' => 'required|string|max:50',
        'start_date' => 'required|date',
        'end_date' => 'nullable|date|after_or_equal:start_date',
        'reference_number' => 'required|string|max:255',
        'service' => 'nullable|string|max:255',
        'description'=>'nullable'
    ]);

    $register= new RegisterService();
    $output=$register->register(
        $this->member_id,
        $this->source_account_number,
        $this->source_bank_id,
        $this->destination_account_name,
        $this->bank,
        $this->destination_account_id,
        $this->saccos_branch_id,
        $this->amount,
        $this->frequency,
        $this->start_date,
        $this->end_date ? :null,
        $this->reference_number,
        $this->service ,
        "PENDING",
          null,
          $this->description ?:null
    );

    if($output==true){

        session()->flash('message',"successfully saved");

    }else{

        session()->flash('message_fail',$output);
    }

}



public function save(){


  $this->validate();

   $id=DB::table('standing_instructions')->insertGetId([
      'amount'=>$this->amount,
      'destination_account_number'=>$this->destination_account_number,
      'source_account_number'=>$this->source_account_number,
      'action_date'=>$this->action_date,
      'status'=>"PENDING",
      'end_date'=>$this->end_date,
      'member_number'=>$this->member_number,
      'description'=>$this->description
  ]);


  $approval= new approvals();
  $approval->sendApproval($id,'createStandingOrder','has created standing order','new standing order','12','');

  session()->flash('message',"successfully saved");
  $this->emit('refresh');
  $this->reset();

}




    public function render()
    {
        $this->bank_list=DB::table('banks')->get();

        if($this->service){
            $this->reference_number=$this->getRefereceNumber();

        }

        return view('livewire.accounting.standing-instruction');
    }
}
