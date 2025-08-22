<?php

namespace App\Http\Livewire\Loans\Sections;

use Livewire\Component;
use Illuminate\Support\Facades\DB;

class LendingFramework extends Component
{
    public $loanProducts = [];
    public $policyBreaches = [];
    
    public function mount()
    {
        $this->loadLoanProducts();
        $this->loadPolicyBreaches();
    }
    
    private function loadLoanProducts()
    {
        // Fetch loan products from database
        $this->loanProducts = DB::table('loan_sub_products')
            ->select(
                'sub_product_name',
                'ltv',
                'principle_max_value',
                'max_term'
            )
            ->whereIn('sub_product_id', [
                'LSP001', 'LSP002', 'LSP003', 'LSP004', 
                'LSP005', 'LSP006', 'LSP007', 'LSP008'
            ])
            ->orderBy('id')
            ->get()
            ->toArray();
    }
    
    private function loadPolicyBreaches()
    {
        // Define policy breaches status
        // This would typically come from actual loan assessment
        $this->policyBreaches = [
            'gross_income' => 'BREACH',
            'total_savings' => 'PASS',
            'maximum_limit' => 'PASS',
            'qualifying_amount' => 'BREACH',
            'loan_amount_per_product' => 'PASS',
            'policy_ltv' => 'PASS'
        ];
    }

    public function render()
    {
        return view('livewire.loans.sections.lending-framework');
    }
}


