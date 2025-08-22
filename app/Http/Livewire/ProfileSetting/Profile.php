<?php

namespace App\Http\Livewire\ProfileSetting;

use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Http\Livewire\ProfileSetting\Config;

class Profile extends Component
{
    public $teller_tab = 1;
    public $institution_id;

    protected $listeners = ['refreshAccounts' => '$refresh'];

    public function mount()
    {
        // Get the institution ID from the database
        $institution = DB::table('institutions')->first();
        $this->institution_id = $institution ? $institution->id : null;
    }

    public function render()
    {
        return view('livewire.profile-setting.profile');
    }

    public function menu_sub_button($id)
    {
        $this->teller_tab = $id;
    }
}
