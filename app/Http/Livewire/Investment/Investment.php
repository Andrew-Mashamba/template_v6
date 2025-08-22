<?php

namespace App\Http\Livewire\Investment;

use App\Models\AccountsModel;
use App\Models\approvals;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Investment extends Component
{
    public $investment_name;
    public $investment_type;
    public $investment_amount;
    public $expense_account;
    public $showProjectDetails;

    protected $rules=['investment_name'=>'required|string',
                       'investment_type'=>'required|int',
                         'investment_amount'=>'required|numeric'
        ];

    public $selected=1;
    public $showCreateInvestmentModal=false;


    public function createInvestmentModal(){
        if($this->showCreateInvestmentModal==false){
            $this->showCreateInvestmentModal=true;}
        else if($this->showCreateInvestmentModal==true){
            $this->showCreateInvestmentModal=false;
            $this->selected=1;
        }  }

    public function  showAddClientModal($id){
        $this->selected=$id;
        $this->createInvestmentModal();
    }


    public function createInvestment(){
        $this->validate();
        // investment instance

        $investment= new \App\Models\Investment();
        $investment->investment_name=$this->investment_name;
        $investment->investment_type=$this->investment_type;
        $investment->investment_amount=$this->investment_amount;
       $investment->save();


       // CREATE ACCOUNT



        // update account
//        AccountsModel::where('account_number',$account_number)->update(['balance'=>$this->investment_amount]);



        $data=json_encode(['amount'=>$this->investment_amount,'name'=>$this->investment_name,'account_code'=>$this->expense_account]);
       session()->flash('message','New investment has been created successfully');

       //approval
        $approvals=new approvals();
        $approvals->sendApproval($investment->id,'createInvestment','has created new investment','new investment has been created','102',$data);

        // reset data
        $this->resetInvestmentData();
    }




    public function resetInvestmentData(){
        $this->investment_name=null;
        $this->investment_type=null;
        $this->investment_amount=null;
    }

    public function render()
    {
        return view('livewire.investment.investment');
    }
}
