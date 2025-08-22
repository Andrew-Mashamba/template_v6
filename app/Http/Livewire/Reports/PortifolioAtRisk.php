<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Models\LoansModel;

class PortifolioAtRisk extends Component
{
    public $selected=10;

    public function visit($id){

        switch($id){

            case(10): $this->emit('setRangeBetween',[1,9]); break;

            case(30) : $this->emit('setRangeBetween',[10,29]); break;

            case(40) : $this->emit('setRangeBetween',[30,89]); break;

            case(50) : $this->emit('setAbove',90); break;

        }


        $this->selected=$id;

    }

    public $below10, $count10;
    public $below30, $count30;
    public $below60, $count60;
    public $below90, $count90;


     function summary(){
        $query =LoansModel::query()->where('status','ACTIVE');

        $this->below10=LoansModel::whereBetween('days_in_arrears',[1,9])->where('status','ACTIVE')->sum('principle');
        $this->count10=LoansModel::whereBetween('days_in_arrears',[1,9])->where('status','ACTIVE')->count();


      $this->below30=LoansModel::whereBetween('days_in_arrears',[10,29])->where('status','ACTIVE')->sum('principle');
      $this->count30=LoansModel::whereBetween('days_in_arrears',[10,29])->where('status','ACTIVE')->count();


      $this->below60=LoansModel::whereBetween('days_in_arrears',[30,89])->where('status','ACTIVE')->sum('principle');
      $this->count60=LoansModel::whereBetween('days_in_arrears',[30,89])->where('status','ACTIVE')->count();


      $this->below90=LoansModel::where('days_in_arrears','>=',90)->where('status','ACTIVE')->sum('principle');
      $this->count90=LoansModel::where('days_in_arrears','>=',90)->where('status','ACTIVE')->count();


     }



    public function render()
    {
        $this->summary();
        return view('livewire.reports.portifolio-at-risk');
    }
}
