<?php

namespace App\Http\Livewire\MembersPortal;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class MembersPortal extends Component
{
    public $activeTab = 'user-management';
    public $loading = false;

    protected $listeners = ['refreshComponent' => '$refresh'];

    public function mount()
    {
        // Initialize component
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function render()
    {
        return view('livewire.members-portal.members-portal');
    }
}
