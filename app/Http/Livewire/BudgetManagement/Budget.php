<?php

namespace App\Http\Livewire\BudgetManagement;

use Carbon\Carbon;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Budget extends Component
{
    public $tab_id=1;
    public $selectYear;



    public function refreshComponent(){

        $this->emitTo('budget-management.all-budget','updateYear',$this->selectYear);
    }

    public function menuItemClicked($id){
        $this->tab_id=$id;
    }

    public function boot(){
        $this->selectYear=Carbon::now()->year;
    }
    public function render()
    {
        return view('livewire.budget-management.budget');
    }
}
