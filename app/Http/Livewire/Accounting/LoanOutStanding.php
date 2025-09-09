<?php

namespace App\Http\Livewire\Accounting;

use App\Models\loans_schedules;
use App\Models\LoansModel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Services\BalanceSheetItemIntegrationService;

class LoanOutStanding extends Component
{


    public function render()
    {
        $loan_out_standings= LoansModel::query()->where('status','ACTIVE')->get();

        foreach( $loan_out_standings as $data){
          $data['name']=$this->getUserName($data->client_number);
          $data['balance']=$this->getBalance($data->id);

        }

        $loan_ids=LoansModel::query()->where('status','ACTIVE')->pluck('id')->toArray();
        $loan_summary=$this->loanOutstandingSummary($loan_ids);

       // dd($loan_out_standings, $loan_summary);

        return view('livewire.accounting.loan-out-standing',['loans'=>$loan_out_standings,'loan_summary'=>$loan_summary]);
    }


    public function getUserName($member_number){

      $member=  DB::table('clients')->where('client_number',$member_number)->first();

      return $member->first_name.' '. $member->middle_name.' '.$member->last_name;
    }


    public function getBalance($loan_id){

        $loans= loans_schedules::query()->where('loan_id',$loan_id);

        return $loans->sum('installment') - $loans->sum('payment');
    }


    function loanOutstandingSummary($loan_id){

        $loans= loans_schedules::query()->whereIn('loan_id',$loan_id);

        return $loans->sum('installment') - $loans->sum('payment');
    }
}
