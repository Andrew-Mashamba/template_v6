<?php

namespace App\Http\Livewire\Reports;

use Livewire\Component;
use App\Models\LoansModel;
use App\Models\ClientsModel;
use App\Models\BranchesModel;
use App\Models\general_ledger;
use App\Models\loans_schedules;
use App\Models\LoanSubProduct;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class ClientRepaymentHistory extends Component
{
    public $selectedClient = '';
    public $clientNumber = '';
    public $startDate;
    public $endDate;
    public $clients = [];
    public $repaymentHistory = [];
    public $paymentSummary = [];
    public $totalPayments = 0;
    public $totalPrincipalPaid = 0;
    public $totalInterestPaid = 0;
    public $averagePaymentAmount = 0;
    public $paymentFrequency = [];
    public $latePayments = 0;
    public $onTimePayments = 0;

    public function mount()
    {
        $this->startDate = Carbon::now()->subMonths(6)->format('Y-m-d');
        $this->endDate = Carbon::now()->format('Y-m-d');
        $this->loadClients();
    }

    public function loadClients()
    {
        $this->clients = ClientsModel::whereHas('loans')->get()->map(function ($client) {
            $client->full_name = trim($client->first_name . ' ' . $client->middle_name . ' ' . $client->last_name);
            return $client;
        });
    }

    public function updatedSelectedClient()
    {
        if ($this->selectedClient) {
            $client = ClientsModel::find($this->selectedClient);
            $this->clientNumber = $client->client_number;
            $this->loadRepaymentHistory();
        }
    }

    public function updatedClientNumber()
    {
        if ($this->clientNumber) {
            $this->loadRepaymentHistory();
        }
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['startDate', 'endDate'])) {
            $this->loadRepaymentHistory();
        }
    }

    public function loadRepaymentHistory()
    {
        if (empty($this->clientNumber)) {
            $this->repaymentHistory = [];
            $this->calculatePaymentSummary();
            return;
        }

        $startDate = Carbon::parse($this->startDate)->startOfDay();
        $endDate = Carbon::parse($this->endDate)->endOfDay();

        // Get loan account numbers for the client
        $loanAccountNumbers = LoansModel::where('client_number', $this->clientNumber)
            ->pluck('loan_account_number')
            ->toArray();

        if (empty($loanAccountNumbers)) {
            $this->repaymentHistory = [];
            $this->calculatePaymentSummary();
            return;
        }

        // Get repayment history from general ledger
        $this->repaymentHistory = general_ledger::whereIn('record_on_account_number', $loanAccountNumbers)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('credit', '>', 0) // Only credit transactions (payments)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($transaction) {
                // Get loan details
                $loan = LoansModel::where('loan_account_number', $transaction->record_on_account_number)->first();
                if ($loan) {
                    $transaction->loan_id = $loan->loan_id;
                    $transaction->loan_principal = $loan->principle;
                    
                    // Get client details
                    $client = ClientsModel::where('client_number', $loan->client_number)->first();
                    $transaction->client_name = $client ? trim($client->first_name . ' ' . $client->middle_name . ' ' . $client->last_name) : 'N/A';
                    
                    // Get product details
                    $product = LoanSubProduct::where('product_id', $loan->loan_sub_product)->first();
                    $transaction->product_name = $product ? $product->product_name : 'N/A';
                    
                    // Get branch details
                    $branch = BranchesModel::find($loan->branch_id);
                    $transaction->branch_name = $branch ? $branch->name : 'N/A';
                }

                // Format transaction date
                $transaction->payment_date = $transaction->created_at ? $transaction->created_at->format('Y-m-d') : 'N/A';
                
                // Determine payment type
                $transaction->payment_type = $this->determinePaymentType($transaction);
                
                return $transaction;
            });

        $this->calculatePaymentSummary();
    }

    public function determinePaymentType($transaction)
    {
        // This is a simplified logic - you might want to enhance this based on your business rules
        if ($transaction->credit > 10000) {
            return 'Full Payment';
        } elseif ($transaction->credit > 1000) {
            return 'Partial Payment';
        } else {
            return 'Interest Only';
        }
    }

    public function calculatePaymentSummary()
    {
        if (empty($this->repaymentHistory)) {
            $this->totalPayments = 0;
            $this->totalPrincipalPaid = 0;
            $this->totalInterestPaid = 0;
            $this->averagePaymentAmount = 0;
            $this->latePayments = 0;
            $this->onTimePayments = 0;
            $this->paymentFrequency = [];
            return;
        }

        $this->totalPayments = $this->repaymentHistory->count();
        $this->totalPrincipalPaid = $this->repaymentHistory->sum('credit');
        
        // Calculate average payment amount
        $this->averagePaymentAmount = $this->totalPayments > 0 ? $this->totalPrincipalPaid / $this->totalPayments : 0;

        // Analyze payment patterns
        $this->analyzePaymentPatterns();
        
        // Calculate payment frequency by month
        $this->calculatePaymentFrequency();
    }

    public function analyzePaymentPatterns()
    {
        $latePayments = 0;
        $onTimePayments = 0;

        foreach ($this->repaymentHistory as $payment) {
            // Get the corresponding loan schedule to check if payment was on time
            $loan = LoansModel::where('loan_account_number', $payment->record_on_account_number)->first();
            if ($loan) {
                $schedule = loans_schedules::where('loan_id', $loan->id)
                    ->where('installment_date', '<=', $payment->created_at)
                    ->orderBy('installment_date', 'desc')
                    ->first();
                
                if ($schedule) {
                    $daysDifference = Carbon::parse($payment->created_at)->diffInDays(Carbon::parse($schedule->installment_date));
                    if ($daysDifference > 7) { // More than 7 days late
                        $latePayments++;
                    } else {
                        $onTimePayments++;
                    }
                }
            }
        }

        $this->latePayments = $latePayments;
        $this->onTimePayments = $onTimePayments;
    }

    public function calculatePaymentFrequency()
    {
        $frequency = [];
        
        foreach ($this->repaymentHistory as $payment) {
            $month = Carbon::parse($payment->created_at)->format('Y-m');
            if (!isset($frequency[$month])) {
                $frequency[$month] = 0;
            }
            $frequency[$month]++;
        }
        
        $this->paymentFrequency = $frequency;
    }

    public function exportReport($format = 'pdf')
    {
        try {
            session()->flash('success', "Client Repayment History Report exported as {$format} successfully!");
            
            Log::info('Client Repayment History Report exported', [
                'format' => $format,
                'client_number' => $this->clientNumber,
                'start_date' => $this->startDate,
                'end_date' => $this->endDate,
                'user_id' => auth()->id()
            ]);
        } catch (Exception $e) {
            session()->flash('error', 'Error exporting report: ' . $e->getMessage());
            Log::error('Client Repayment History Report export failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id()
            ]);
        }
    }

    public function render()
    {
        return view('livewire.reports.client-repayment-history');
    }
}
