<?php

namespace App\Http\Livewire\Branches;

use App\Models\BranchesModel;
use App\Traits\HasRoles;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;

class BranchesTable extends Component
{
    use WithPagination, HasRoles;

    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    public $canView = false;
    public $canEdit = false;
    public $canDelete = false;
    public $userPermissions = [];

    private $actionPermissions = [
        'view' => 'view',
        'edit' => 'edit',
        'delete' => 'delete'
    ];

    public function mount()
    {
        $this->loadUserPermissions();
        $this->setPermissions();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
    }

    private function loadUserPermissions()
    {
        $user = Auth::user();
        if (!$user) return;

        $userRole = DB::table('user_roles')->where('user_id', $user->id)->first();
        if (!$userRole) return;

        $subRole = DB::table('sub_roles')->where('role_id', $userRole->role_id)->first();
        if (!$subRole) return;

        $rolePermissions = DB::table('role_menu_actions')
            ->where('sub_role', $subRole->name)
            ->get();

        $this->userPermissions = $rolePermissions
            ->pluck('allowed_actions')
            ->map(fn($actions) => json_decode($actions, true))
            ->flatten()
            ->unique()
            ->values()
            ->toArray();
    }

    private function setPermissions()
    {
        $this->canView = $this->hasPermission($this->actionPermissions['view']);
        $this->canEdit = $this->hasPermission($this->actionPermissions['edit']);
        $this->canDelete = $this->hasPermission($this->actionPermissions['delete']);
    }

    private function hasPermission($permission)
    {
        return in_array($permission, $this->userPermissions);
    }

    public function render()
    {
        $branches = BranchesModel::query()
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('branch_number', 'like', "%{$this->search}%")
                      ->orWhere('name', 'ilike', "%{$this->search}%")
                      ->orWhere('region', 'ilike', "%{$this->search}%")
                      ->orWhere('wilaya', 'ilike', "%{$this->search}%")
                      ->orWhere('status', 'ilike', "%{$this->search}%")
                      ->orWhere('branch_type', 'ilike', "%{$this->search}%")
                      ->orWhere('branch_manager', 'ilike', "%{$this->search}%");
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.branches.branches-table', [
            'branches' => $branches
        ]);
    }
}
