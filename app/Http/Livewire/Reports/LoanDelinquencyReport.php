<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;

class LoanDelinquencyReport extends Component
{
    public $startDate;
    public $endDate;
    public $PAR;
    public $startRange;
    public $endRange;

    public $branch;



    function UpdatedStartRange($value){
        $this->startRange=$value;

        $this->emit('startRange',$value);

    }



    function UpdatedStartDate($value){
        $this->startRange=$value;

        $this->emit('setStartDate',$value);

    }


    function UpdatedEndDate($value){
        $this->startRange=$value;

        $this->emit('setEndDate',$value);

    }


    function UpdatedEndRange($value){
        $this->endRange=$value;
        // dd($value);
        $this->emit( 'endRange',$value);
    }

    function UpdatedBranch($value){

        $this->branch=$value;

        $this->emit( 'setBranch',$value);
    }





    public function render()
    {


        return view('livewire.reports.loan-delinquency-report');
    }
}
