<?php

namespace App\Http\Livewire\ProductsManagement;

use Livewire\Component;
use App\Models\AccountsModel;
use App\Models\LoansModel;
use Illuminate\Support\Facades\DB;

class Products extends Component
{
    public $tab_id = 1;
    public $totalShares = 0;
    public $totalDeposits = 0;
    public $activeLoans = 0;



    public function mount()
    {
        $this->refreshStatistics();
    }

    public function menuItemClicked($id)
    {
        $this->tab_id = $id;
    }

    public function refreshStatistics()
    {
        // Get total shares count
        $this->totalShares = AccountsModel::where('product_number', '1000')->count();

        // Get total deposits count
        $this->totalDeposits = AccountsModel::where('product_number', '2000')->count();

        // Get active loans count
        $this->activeLoans = LoansModel::count();
    }

    public function render()
    {
        return view('livewire.products-management.products');
    }
}
