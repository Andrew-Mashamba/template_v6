<?php

namespace App\Http\Livewire\SelfServices;

use Livewire\Component;

class SelfServices extends Component
{
    public $selectedMenu = 'dashboard'; // Default to dashboard

    public function render()
    {
        return view('livewire.self-services.self-services');
    }
}
