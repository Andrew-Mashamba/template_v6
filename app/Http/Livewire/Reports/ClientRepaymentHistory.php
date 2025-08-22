<?php

namespace App\Http\Livewire\Reports;

use App\Models\general_ledger;
use App\Models\LoansModel;
use Livewire\Component;
use DateTime;
use Illuminate\Support\Facades\DB;
use Mediconesystems\LivewireDatatables\Column;
use Mediconesystems\LivewireDatatables\Http\Livewire\LivewireDatatable;

class ClientRepaymentHistory extends LivewireDatatable
{

    public $explot=true;
public function builder(){


    $loan_account_number= LoansModel::pluck('loan_account_number')->toArray();

    return LoansModel::leftJoin('general_ledger','loans.loan_account_number','=', 'general_ledger.record_on_account_number') ;

    //general_ledger::query()->whereIn('record_on_account_number',$loan_account_number);
}


public function columns(){

    return [
      //  column::name('id')->label('id'),
        column::name('general_ledger.created_at')->label('date ')->searchable(),

        Column::callback('general_ledger.record_on_account_number',function($record_on_account_number){

            $client_number=LoansModel::where('loan_account_number',$record_on_account_number)->value('client_number');
            $member= DB::table('clients')->where('client_number',$client_number)->first();
            return $member ?->first_name.' '. $member ?->middle_name.'  '. $member ?->last_name ;


        })->label('member name'),

       // column::name('loan_id')->label('loan id'),

        column::name('general_ledger.credit')->label('credit'),

        column::name('general_ledger.debit')->label('debit'),
        column::name('general_ledger.record_on_account_number_balance')->label('balance'),

        column::name('general_ledger.reference_number')->label('reference  number'),

        column::name('general_ledger.trans_status  ')->label('id'),
       // column::name('destination_account_number ')->label('destination_account_number'),
     //   column::name('destination_account_number')->label('id'),


    ];
}
}
