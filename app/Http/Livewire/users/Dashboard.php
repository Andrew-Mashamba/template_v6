<?php

namespace App\Http\Livewire\Users;

use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Permission;
use Livewire\Component;
use Illuminate\Support\Facades\Log;

class Dashboard extends Component
{
    public $section = null;

    public function mount()
    {
        // Set a default section to 'users' for better UX
        $this->section = 'users';
    }

    public function setSection($section)
    {
        //dd($section);
        $this->section = $section;
        Log::info('Section changed to: ' . $section);
    }

    public function render()
    {
        $totalUsers = User::count();
        $totalRoles = Role::count();
        $totalDepartments = Department::count();
        $totalPermissions = Permission::count();

        // Debug: Log the current section
        Log::info('Dashboard section: ' . $this->section);

        return view('livewire.users.dashboard', [
            'totalUsers' => $totalUsers,
            'totalRoles' => $totalRoles,
            'totalDepartments' => $totalDepartments,
            'totalPermissions' => $totalPermissions,
        ]);
    }
} 