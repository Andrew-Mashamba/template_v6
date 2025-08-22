<?php

namespace App\Http\Livewire\Accounting;
use App\Models\loans_schedules;
use App\Models\LoansModel;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Provision extends Component
{
    public function render()
    {
        $loan=$this->getLoanOnProvision();

        foreach($loan as $data){
            $data['name']=$this->getUserName($data->client_number);
            $data['out_standing_amount']= $this->getBalance($data->id);
            $data['loan_amount']=$this->loanAmount($data->loan_id);
            $data['date']=$this->getLastRepaymentDate($data->id);
            $data['provision_rate']=$this->provisionPercent($data->id);
        }
        return view('livewire.accounting.provision',['loan'=> $loan,'summary'=>$this->provisionSummary()]);
    }



    public function getUserName($member_number){

        $member=  DB::table('clients')->where('client_number',$member_number)->first();

        return $member->first_name.' '. $member->middle_name.' '.$member->last_name;
      }

      function getLastRepaymentDate($loan_id){


        $repayment_date = loans_schedules::where('loan_id', $loan_id)
        ->where(function($query) {
            $query->where('completion_status', 'CLOSED')
                  ->orWhere('completion_status', 'PARTIAL');
        })
        ->latest()
        ->value('installment_date');

        if (!$repayment_date) {
            $repayment_date = loans_schedules::where('loan_id', $loan_id)
                ->where('completion_status', 'ACTIVE')
                ->oldest()
                ->value('installment_date');
        }

        return $repayment_date;
      }


    public function getLoanOnProvision(){
        // provision limit
        $loans= LoansModel::where('status','ACTIVE')->get();

        return $loans;

    }

    public function getBalance($loan_id){
        $loans= loans_schedules::query()->where('loan_id',$loan_id);
        return $loans->sum('installment') - $loans->sum('payment');


    }


    public function provisionSummary(){

        $loan_id =  LoansModel::where('status','ACTIVE')->pluck('id')->toArray();
        $loans= loans_schedules::query()->whereIn( 'loan_id',$loan_id);
        return $loans->sum('installment') - $loans->sum('payment');


    }


    function calculateLoanArrears($loan_id){


        return loans_schedules::where('loan_id',$loan_id)
             ->where('completion_status','!=','CLOSED')
               ->sum('days_in_arrears');
    }

    function provisionPercent($loan_id){

        $days= $this->calculateLoanArrears($loan_id);

        $array_data = DB::table('loan_provision_settings')
    ->where('per', '>', $days) // Filter for values greater than $days
    ->get();

// Step 3: Find the smallest difference
$smallest_difference = null;
$closest_per_value = null; // Variable to store the closest per value

foreach ($array_data as $item) {
    $difference = $item->per - $days; // Calculate the difference
    if ($difference > 0) { // Ensure the difference is positive
        // Check if this is the first difference we're comparing
        if ($smallest_difference === null || $difference < $smallest_difference) {
            $smallest_difference = $difference; // Update the smallest difference
            $closest_per_value = $item->per; // Store the current closest per value
        }
    }
}

// Now, $closest_per_value contains the `per` value with the smallest positive difference
return $closest_per_value;


    }

    public function loanAmount($loan_id){
        return LoansModel::where('loan_id',$loan_id)->value('principle');
        // $loans= loans_schedules::query()->where('loan_id',$loan_id);
        // return $loans->sum('installment') ;


    }


}
