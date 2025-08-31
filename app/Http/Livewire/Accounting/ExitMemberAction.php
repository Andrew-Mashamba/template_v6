<?php

namespace App\Http\Livewire\Accounting;

use App\Models\ClientsModel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class ExitMemberAction extends Component
{
    public $member;
    public $exitCalculation = [];

    public function mount()
    {
        $memberId = session()->get('viewMemberId_details');
        if ($memberId) {
            $this->member = ClientsModel::with([
                'accounts',
                'loans.loanAccount',
                'bills.service',
                'dividends',
                'interestPayables'
            ])->find($memberId);
            
            if ($this->member) {
                $this->calculateExitAmount();
            }
        }
    }

    public function calculateExitAmount()
    {
        if (!$this->member) {
            return;
        }

        // 1. Total Dividends
        $totalDividends = $this->member->dividends->sum('amount');

        // 2. Total Interest on Savings
        $totalInterest = $this->member->interestPayables->sum('interest_payable');

        // 3. Total Accounts Balances
        $totalAccountsBalance = $this->member->accounts->sum('balance');

        // 4. Total Loan Account Balance
        $totalLoanBalance = $this->member->loans->sum(function($loan) {
            return $loan->loanAccount->balance ?? 0;
        });

        // 5. Sum of Unpaid Control Numbers
        $unpaidBills = $this->member->bills->where('status', '!=', 'PAID');
        $totalUnpaidBills = $unpaidBills->sum('amount_due');

        // 6. Calculate Exit Amount
        $exitAmount = $totalDividends + $totalInterest + $totalAccountsBalance - $totalLoanBalance - $totalUnpaidBills;

        $this->exitCalculation = [
            'total_dividends' => $totalDividends,
            'total_interest' => $totalInterest,
            'total_accounts_balance' => $totalAccountsBalance,
            'total_loan_balance' => $totalLoanBalance,
            'total_unpaid_bills' => $totalUnpaidBills,
            'exit_amount' => $exitAmount,
            'unpaid_bills_count' => $unpaidBills->count(),
            'loans_count' => $this->member->loans->count(),
            'accounts_count' => $this->member->accounts->count(),
            'dividends_count' => $this->member->dividends->count(),
            'interest_records_count' => $this->member->interestPayables->count(),
        ];
    }

    public function render()
    {
        return view('livewire.accounting.exit-member-action');
    }

    public function download(){
        $member_exit_document=ClientsModel::where('id',session()->get('viewMemberId_details'))->value('member_exit_document');
        $filePath = storage_path('app/public/' .$member_exit_document);
        return response()->download($filePath);
    }
}
