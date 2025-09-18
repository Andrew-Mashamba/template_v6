<?php

namespace App\Http\Livewire\Users;

use App\Models\User;
use App\Models\UserRole;
use App\Models\Employee;
use App\Models\departmentsList;
use App\Models\Role;
use App\Models\SubRole;
use App\Models\Branch;
use App\Notifications\UserCredentialsNotification;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class SimpleUsers extends Component
{
    use WithPagination;

    // Search and filters
    public $search = '';
    public $departmentFilter = '';
    public $roleFilter = '';
    public $statusFilter = '';
    
    // Modal states
    public $showCreateUser = false;
    public $showEditUser = false;
    public $showDeleteUser = false;
    public $editingUserId = null;
    public $deletingUserId = null;
    
    // User form fields
    public $name;
    public $email;
    public $phone_number;
    public $employee_id;
    public $password;
    public $password_confirmation;
    public $sendCredentials = true;
    
    // Department/Role/SubRole fields
    public $selectedDepartment = null;
    public $selectedRole = null;
    public $selectedSubRoles = [];
    
    // Available options
    public $departments = [];
    public $availableRoles = [];
    public $availableSubRoles = [];
    public $branches = [];
    public $selectedBranch = null;
    
    // Sorting
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    
    protected $listeners = ['refreshUsers' => '$refresh'];
    
    protected function rules()
    {
        $rules = [
            'name' => 'required|string|min:3|max:255',
            'email' => 'required|email|unique:users,email',
            'phone_number' => 'nullable|string|max:20',
            'employee_id' => 'nullable|string|max:50|unique:users,employeeId',
            'selectedDepartment' => 'required|exists:departments,id',
            'selectedRole' => 'required|exists:roles,id',
            'selectedBranch' => 'nullable|exists:branches,id',
            'selectedSubRoles' => 'nullable|array',
            'selectedSubRoles.*' => 'exists:sub_roles,id',
        ];
        
        if (!$this->editingUserId) {
            // $rules['password'] = 'required|string|min:8|confirmed';
        } else {
            // $rules['password'] = 'nullable|string|min:8|confirmed';
            $rules['email'] = ['required', 'email', Rule::unique('users')->ignore($this->editingUserId)];
            $rules['employee_id'] = ['nullable', 'string', 'max:50', Rule::unique('users', 'employeeId')->ignore($this->editingUserId)];
        }
        
        return $rules;
    }
    
    protected $messages = [
        'selectedDepartment.required' => 'Please select a department.',
        'selectedDepartment.exists' => 'The selected department is invalid.',
        'selectedRole.required' => 'Please select a role.',
        'selectedRole.exists' => 'The selected role is invalid.',
        'selectedSubRoles.*.exists' => 'One or more selected sub-roles are invalid.',
    ];
    
    public function mount()
    {
        $this->loadDepartments();
        $this->loadBranches();
    }
    
    public function loadDepartments()
    {
        $this->departments = departmentsList::where('status', true)
            ->orderBy('department_name')
            ->get();
    }
    
    public function loadBranches()
    {
        $this->branches = Branch::where('status', 'ACTIVE')
            ->orderBy('name')
            ->get();
    }
    
    public function updatedSelectedDepartment($value)
    {
        $this->selectedRole = null;
        $this->selectedSubRoles = [];
        $this->availableSubRoles = [];
        
        if ($value) {
            $department = departmentsList::find($value);
            if ($department) {
                $this->availableRoles = $department->roles()
                    ->where('status', 'ACTIVE')
                    ->orderBy('name')
                    ->get();
            }
        } else {
            $this->availableRoles = [];
        }
    }
    
    public function updatedSelectedRole($value)
    {
        $this->selectedSubRoles = [];
        
        if ($value) {
            $role = Role::find($value);
            if ($role) {
                $this->availableSubRoles = $role->subRoles()
                    ->orderBy('name')
                    ->get();
            }
        } else {
            $this->availableSubRoles = [];
        }
    }
    
    public function openCreateModal()
    {
        $this->resetForm();
        $this->showCreateUser = true;
    }
    
    public function openEditModal($userId)
    {
        $this->resetForm();
        $this->editingUserId = $userId;
        
        $user = User::with(['roles.department', 'roles.subRoles'])->find($userId);
        if ($user) {
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone_number = $user->phone_number;
            $this->employee_id = $user->employeeId;
            
            // Load the user's department, role, and sub-roles
            if ($user->roles->first()) {
                $role = $user->roles->first();
                $this->selectedRole = $role->id;
                
                if ($role->department) {
                    $this->selectedDepartment = $role->department->id;
                    $this->updatedSelectedDepartment($role->department->id);
                    $this->selectedRole = $role->id; // Re-set after loading department
                    $this->updatedSelectedRole($role->id);
                }
                
                // Load user's sub-roles
                $this->selectedSubRoles = $user->subRoles->pluck('id')->toArray();
            }
            
            $this->selectedBranch = $user->branch;
            $this->showEditUser = true;
        }
    }
    
    public function openDeleteModal($userId)
    {
        $this->deletingUserId = $userId;
        $this->showDeleteUser = true;
    }
    
    public function saveUser()
    {
        $this->validate();
        
        try {
            DB::beginTransaction();
            
            if ($this->editingUserId) {
                $this->updateUser();
            } else {
                $this->createUser();
            }
            
            DB::commit();
            
            session()->flash('message', $this->editingUserId ? 'User updated successfully.' : 'User created successfully.');
            
            $this->showCreateUser = false;
            $this->showEditUser = false;
            $this->resetForm();
            $this->emit('refreshUsers');
            
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error saving user: ' . $e->getMessage());
            session()->flash('error', 'Error saving user: ' . $e->getMessage());
        }
    }
    
    private function createUser()
    {
        // Get the selected role and department
        $role = Role::find($this->selectedRole);
        $department = departmentsList::find($this->selectedDepartment);
        $generatedPassword = Str::random(12);
        
        // Create the user
        $user = User::create([
            'name' => $this->name,
            'email' => $this->email,
            'password' => Hash::make($generatedPassword),
            'phone_number' => $this->phone_number,
            'employeeId' => $this->employee_id,
            'department_code' => $department ? $department->department_code : null,
            'branch' => $this->selectedBranch,
            'status' => 'ACTIVE',
            'password_changed_at' => now(),
        ]);

        //Add to employees
        $employee = Employee::create([
            'institution_user_id' => $user->id,
            'branch_id' => $this->selectedBranch ?? null,
            'user_id' => $user->id ?? null,
            'first_name' => $this->name ?? null,
            'email' => $this->email ?? null,
            'phone' => '255' . substr($this->phone_number, -9),
            'employee_number' => $this->employee_id,
            'department_id' => $department->id,
            'job_title' => $this->job_title ?? null,
            'hire_date' => $this->hire_date ?? null,
            'basic_salary' => $this->basic_salary ?? null,
            'gross_salary' => $this->basic_salary ?? null,
            'gender' => $this->gender ?? null,
            'date_of_birth' => $this->date_of_birth ?? null,
            'address' => $this->address ?? null,
            'employee_status' => $this->employee_status ?? null,
            'employment_type' => $this->employment_type ?? null,
        ]);
        
        // Assign the role
        $user->roles()->attach($this->selectedRole);
        
        // Assign sub-roles if any
        if (!empty($this->selectedSubRoles)) {
            $user->subRoles()->attach($this->selectedSubRoles);
        }
        
        // Sync permissions from role and sub-roles
        $this->syncUserPermissions($user, $role);
        
        // Send welcome email with credentials if enabled
        if ($this->sendCredentials) {
            $this->sendWelcomeEmail($user, $generatedPassword);
        }
    }
    
    private function updateUser()
    {
        $user = User::find($this->editingUserId);
        
        if (!$user) {
            throw new \Exception('User not found');
        }
        
        // Get the selected department
        $department = departmentsList::find($this->selectedDepartment);
        
        // Update user data
        $userData = [
            'name' => $this->name,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'employeeId' => $this->employee_id,
            'department_code' => $department ? $department->department_code : null,
            'branch' => $this->selectedBranch,
        ];
        
        // if ($this->password) {
        //     $userData['password'] = Hash::make($this->password);
        //     $userData['password_changed_at'] = now();
        // }
        
        $user->update($userData);
        
        // Update role
        $user->roles()->sync([$this->selectedRole]);
        
        // Update sub-roles
        $user->subRoles()->sync($this->selectedSubRoles ?: []);
        
        // Sync permissions
        $role = Role::find($this->selectedRole);
        $this->syncUserPermissions($user, $role);
    }
    
    private function syncUserPermissions($user, $role)
    {
        // Get all permissions from the role
        $permissions = collect();
        
        if ($role) {
            $permissions = $permissions->merge($role->permissions);
        }
        
        // Add permissions from sub-roles
        foreach ($this->selectedSubRoles as $subRoleId) {
            $subRole = SubRole::find($subRoleId);
            if ($subRole) {
                $permissions = $permissions->merge($subRole->permissions);
            }
        }
        
        // Sync unique permissions to user
        $user->permissions()->sync($permissions->pluck('id')->unique());
    }
    
    private function sendWelcomeEmail($user, $password)
    {
        try {
            // Get department and role names for the email
            $department = departmentsList::find($this->selectedDepartment);
            $role = Role::find($this->selectedRole);
            
            $departmentName = $department ? $department->department_name : null;
            $roleName = $role ? $role->name : null;
            
            // Send the notification with user credentials
            $user->notify(new UserCredentialsNotification(
                $user,
                $password,
                $departmentName,
                $roleName
            ));

            Log::info('Welcome email sent successfully', [
                'user_id' => $user->id,
                'email' => $user->email
            ]);
            
            session()->flash('info', 'Login credentials have been sent to ' . $user->email);
        } catch (\Exception $e) {
            Log::error('Failed to send welcome email', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            
            session()->flash('warning', 'User created but failed to send credentials. Password: ' . $password);
        }
    }
    
    public function deleteUser()
    {
        try {
            $user = User::find($this->deletingUserId);
            
            if ($user) {
                // Check if this is the super admin
                if ($user->id === 1) {
                    session()->flash('error', 'Cannot delete the super admin user.');
                    $this->showDeleteUser = false;
                    return;
                }
                
                // Soft delete or update status
                $user->update(['status' => 'INACTIVE']);
                
                session()->flash('message', 'User deactivated successfully.');
            }
            
            $this->showDeleteUser = false;
            $this->deletingUserId = null;
            $this->emit('refreshUsers');
            
        } catch (\Exception $e) {
            Log::error('Error deleting user: ' . $e->getMessage());
            session()->flash('error', 'Error deleting user: ' . $e->getMessage());
        }
    }
    
    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->phone_number = '';
        $this->employee_id = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedDepartment = null;
        $this->selectedRole = null;
        $this->selectedSubRoles = [];
        $this->selectedBranch = null;
        $this->availableRoles = [];
        $this->availableSubRoles = [];
        $this->editingUserId = null;
        $this->sendCredentials = true;
        $this->resetValidation();
    }
    
    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }
    
    public function render()
    {
        $query = User::with(['roles.department', 'roles.subRoles'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%')
                        ->orWhere('employeeId', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->departmentFilter, function ($q) {
                $q->whereHas('roles.department', function ($query) {
                    $query->where('id', $this->departmentFilter);
                });
            })
            ->when($this->roleFilter, function ($q) {
                $q->whereHas('roles', function ($query) {
                    $query->where('id', $this->roleFilter);
                });
            })
            ->when($this->statusFilter, function ($q) {
                $q->where('status', $this->statusFilter);
            })
            ->orderBy($this->sortField, $this->sortDirection);
        
        return view('livewire.users.simple-users', [
            'users' => $query->paginate(10),
            'totalUsers' => User::count(),
            'activeUsers' => User::where('status', 'ACTIVE')->count(),
            'inactiveUsers' => User::where('status', 'INACTIVE')->count(),
        ]);
    }
}