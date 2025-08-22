<?php

namespace App\Http\Livewire\Reports;

use App\Models\general_ledger;
use App\Models\ClientsModel;
use App\Models\LoansModel;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class GeneralReport extends LivewireDatatable
{
    public $exportable = true;
    public $start_date_value;
    public $title;
    public $value;

    public $listeners=[
        'category'=>'reportCategory',
        'get-start-date'=>'builder',
        'sortByBranchChanged'=>'sortByBranchChanged'
    ];

//    public function startDate($startDate){
//      $this->start_date_value= $startDate;
//    }
    public $sortByBranch;


    public function sortByBranchChanged($value)
    {
        $this->sortByBranch = $value;
        session()->put('sortingBranch', $this->sortByBranch);

    }


  public function reportCategory($selectedNumber){
    $this->value=$selectedNumber;
  }

    public function builder()
    {

       $query=array();


            if(DB::table('branches')->where('id', auth()->user()->branch)->value('name') == 'HQ'){
                $query= general_ledger::query()->where('branch_id', $this->sortByBranch);
            }else{
                $query= general_ledger::query()->where('branch_id', auth()->user()->branch);
            }

        return $query;

    }


    public function columns(): array
    {

        $table_data=[
            Column::name('record_on_account_number')
                ->label('Record on account')->searchable(),
            Column::callback('record_on_account_number_balance',function ($balance){
                return number_format($balance);
            })
                ->label('balance')->searchable(),
            Column::name('sender_branch_id')
                ->label('sender branch')->searchable(),
            Column::name('sender_name')
                ->label(' name')->searchable(),
            Column::name('sender_account_number')
                ->label('sender acc number'),
            Column::name('beneficiary_account_number')
                ->label('beneficiary acc number'),
            Column::name('beneficiary_name')
                ->label('beneficiary name'),
            Column::name('transaction_type')
                ->label('transaction type'),
            Column::name('narration')
                ->label('description'),
            Column::callback('credit',function ($credit){
                return number_format($credit);
            })
                ->label('credit'),
            Column::callback('debit',function ($debit){
                return number_format($debit);
            })
                ->label('debit'),
            Column::name('trans_status')
                ->label('transaction status'),
            Column::name('reference_number')
                ->label('ref number'),
        ];

        return $table_data;



    }

}
