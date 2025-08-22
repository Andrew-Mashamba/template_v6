<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Models\approvals;
use App\Models\LoansModel;
use App\Models\Transactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MainReport;
use App\Exports\LoanSchedule;
use App\Exports\ContractData;

class ClientsDetailsReport extends Component
{

    public $client_type="ALL";
    public $custome_client_number;



    public function downloadExcelFile()
    {
        if ($this->client_type == "MULTIPLE") {

            $input = $this->custome_client_number;
            // Remove the trailing comma if it exists
            $input = rtrim($input, ',');

            // Split the input string into an array of numbers using comma as the delimiter
            $numbers = explode(',', $input);

            // Iterate through the array and process each number
            foreach ($numbers as $number) {
                // Trim any whitespace from the number
                $number = trim($number);

                // Convert the number to an integer (optional, depending on your use case)
                $number = intval($number);
                $number =str_pad($number,4,0 ,STR_PAD_LEFT);

                $array[]='';
                // Do something with the individual number, for example, print it
                if (LoansModel::where('client_number', $number)->exists()) {
                    $array[] = ['number' => str_pad($number, 4, 0, STR_PAD_LEFT)];
                } else {

                }


            }

           // $LoanId = LoansModel::whereBetween('created_at', [$this->reportStartDate, $this->reportEndDate])->whereIn('client_number', $array)->pluck('id');

            $LoanId = LoansModel::whereIn('client_number', $array)->pluck('id');

            return Excel::download(new MainReport($LoanId), 'generalReport.xlsx');

        } else {


            $loanId=LoansModel::get()->pluck('id')->toArray();


            return Excel::download(new MainReport($loanId), 'generalReport.xlsx');

              }

    }


    public function render()
    {
        return view('livewire.reports.clients-details-report');
    }
}
