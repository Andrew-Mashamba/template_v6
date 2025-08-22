<?php

namespace App\Http\Livewire\ActiveLoan;

use Livewire\Component;

class AllLoan extends Component
{
    public $tab_id = 1;
    
    // Sub-tabs for each main section
    public $loanTab = 'summary';
    public $paymentTab = 'new';
    public $arrearsTab = 'days';
    public $portfolioTab = 'par';
    public $collectionTab = 'ongoing';
    public $collateralTab = 'list';
    
    protected $listeners = [
        "displayLoanReport" => "setView",
        "refreshData" => "refreshData"
    ];

    public function boot()
    {
        session()->put('tab_id', 1);
    }

    public function setView($id)
    {
        $this->tab_id = $id;
        session()->put('tab_id', $id);
    }

    // Sub-tab setters for each main section
    public function setLoanTab($tab)
    {
        $this->loanTab = $tab;
    }

    public function setPaymentTab($tab)
    {
        $this->paymentTab = $tab;
    }

    public function setArrearsTab($tab)
    {
        $this->arrearsTab = $tab;
    }

    public function setPortfolioTab($tab)
    {
        $this->portfolioTab = $tab;
    }

    public function setCollectionTab($tab)
    {
        $this->collectionTab = $tab;
    }

    public function setCollateralTab($tab)
    {
        $this->collateralTab = $tab;
    }

    public function refreshData()
    {
        // Refresh data method
        $this->emit('dataRefreshed');
    }

    public function render()
    {
        return view('livewire.active-loan.all-loan');
    }
}
