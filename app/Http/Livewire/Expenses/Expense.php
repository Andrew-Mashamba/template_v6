<?php

namespace App\Http\Livewire\Expenses;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Expense extends Component
{
    public $selected;
    public $unusedBudget;
    public $selectedMenuItem = 1; // Default to Dashboard Overview
    public $search = '';
    public $showDropdown = false;
    public $results = [];

    public function mount()
    {
        // Initialize component
    }

    public function selectedMenu($menuId)
    {
        $this->selectedMenuItem = $menuId;
        $this->showDropdown = false;
        $this->results = [];
    }

    public function updatedSearch()
    {
        if (strlen($this->search) >= 2) {
            $this->results = DB::table('expenses')
                ->where('description', 'like', '%' . $this->search . '%')
                ->orWhere('category', 'like', '%' . $this->search . '%')
                ->limit(10)
                ->get();
            $this->showDropdown = true;
        } else {
            $this->showDropdown = false;
            $this->results = [];
        }
    }

    public function render()
    {
        return view('livewire.expenses.expense');
    }
}
