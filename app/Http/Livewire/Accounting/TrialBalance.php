<?php

namespace App\Http\Livewire\Accounting;

use Livewire\Component;

use Barryvdh\DomPDF\PDF;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class TrialBalance extends Component
{
    public $income_accounts;
    public $expense_accounts;
    public $assets_accounts;
    public $liabilities_accounts;
    public $equity_accounts;

    public function render()
    {
        $this->income_accounts = DB::table('income_accounts')->get();
        $this->expense_accounts = DB::table('expense_accounts')->get();
        $this->assets_accounts = DB::table('asset_accounts')->get();
        $this->liabilities_accounts = DB::table('liability_accounts')->get();
        $this->equity_accounts = DB::table('capital_accounts')->get();

        return view('livewire.accounting.trial-balance');

    }
}
