<?php

namespace App\Http\Livewire\Reports;

use App\Models\approvals;
use App\Models\LoansModel;
use App\Models\Transactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\MainReport;
use App\Exports\LoanSchedule;
use App\Exports\ContractData;



class Reports extends Component
{

    public $endDate;
    public $startDate;
    public $nodes;
    public $services;
    public $channels;
    public $type;

    public $showResolveModal = false;
    public $transactionToReview;
    public $comments;

    public $processorNodes;
    public $sortByBranch;
    public $ReportCategory=1;
    public $tab_id=37;
    public $loanItems;
    public $reportStartDate;
    public $reportEndDate;
    public $customize="NO";
    public $custome_client_number;


    // filter
    public $changeBranch;

    protected $listeners = [
        'resolveModal' => 'showResolveModal',
        'refresh'=>'$refresh'
    ];



    public function updatedChangeBranch($value){
        $this->emit('changeBranch',$value);
      //  session()->put('branchId',$value);
    }

    public function updatedloanItems($value){
//        dd($this->loanItems);
        $this->emit('loanItem',$this->LoanItems);
    }

    public function menuItemClicked($id){
        $this->tab_id=$id;
    }

    public function showResolveModal($id){
    $this->transactionToReview = $id;
    $this->showResolveModal = true;

    }

    public function downloadExcelFile(){
          $this->validate(['reportEndDate'=>'required','reportStartDate'=>'required']);
          if($this->customize=="YES"){

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



                  // Do something with the individual number, for example, print it
                  if(LoansModel::where('client_number',$number)->exists()){
                      $array[]=['number'=>str_pad($number,4,0,STR_PAD_LEFT)];
                  }else{

                  }


              }

              $LoanId=LoansModel::whereBetween('created_at',[$this->reportStartDate,$this->reportEndDate])->pluck('id');

              return Excel::download(new MainReport($LoanId), 'generalReport.xlsx');

          }else{

              $LoanId=LoansModel::whereBetween('created_at',[$this->reportStartDate,$this->reportEndDate])->pluck('id');

           return Excel::download(new MainReport($LoanId), 'generalReport.xlsx');

          }
    }

    public function mount(): void
    {
        $this->endDate = "2025-10-31";
         session()->put('startDate',$this->startDate);
        $this->nodes = array();
        $this->services = array();
        $this->channels = array();
        $this->type = array();
    }

    public function updatedStartDate($value): void
    {
        $this->startDate = $value;
        $this->emit('updatedStartDate', $value);
    }

    public function updatedSortByBranch($value): void
    {
        $this->sortByBranch = $value;
        $this->emit('sortByBranchChanged', $value);
    }

    public function updatedEndDate($value): void
    {
        $this->endDate = $value;
        $this->emit('updatedEndDate', $value);
    }

    public function updatedNodes($value): void
    {
        $this->nodes = $value;
        $this->emit('updatedNodes', $value);
    }

    public function updatedServices($value): void
    {
        $this->services = $value;
        $this->emit('updatedServices', $value);
    }
    public function updatedChannels($value): void
    {
        $this->channels = $value;
        $this->emit('updatedChannels', $value);
    }
    public function updatedType($value): void
    {
        $this->type = $value;
        $this->emit('updatedType', $value);
    }

    public function updatedProcessorNodes($value): void
    {

       //dd($value);
        $this->type = $value;
        $this->emit('updatedProcessorType', $value);
    }


    public function render()     {
        return view('livewire.reports.reports');
    }


    public function saveResolution(){

        $rrn = Transactions::where('ID',$this->transactionToReview)->value('DB_TABLE_REFERENCE');

        $this->validate([

            'comments' => 'required|string|max:550'


        ]);

        $data = [

            'RECON_RESULTS'  => 'RESOLVED',
            'ACTION'  => 'RESOLVED',
            'COMMENTS'  => $this->comments

        ];


        $update_value = approvals::updateOrCreate(
            [
                'process_id' => $this->transactionToReview,
                'user_id' => Auth::user()->id

            ],
            [
                'institution' => '',
                'process_name' => 'resolveTransaction',
                'process_description' => Auth::user()->name.' has requested to resolve a Non Matching Transaction with RN - '. $rrn.'. COMMENTS - '.$this->comments,
                'approval_process_description' => '',
                'process_code' => '22',
                'process_id' => $this->transactionToReview,
                'process_status' => 'PENDING',
                'approval_status' => 'PENDING',
                'user_id'  => Auth::user()->id,
                'team_id'  => '',
                'edit_package'=> json_encode($data),

            ]
        );
        Session::flash('message', 'Awaiting approval');
        Session::flash('alert-class', 'alert-success');

        sleep(3);
        $this->showResolveModal = false;

    }

    public function updatedReportCategory($value):void{

        $this->ReportCategory=$value;
        $this->emit('category',$value);
    }


    }
