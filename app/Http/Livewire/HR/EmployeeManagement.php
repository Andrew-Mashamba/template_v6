<?php

namespace App\Http\Livewire\HR;

use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
use App\Jobs\SendEmployeeCredentials;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class EmployeeManagement extends Component
{
    use WithPagination;

    public $showAddModal = false;
    public $showEditModal = false;
    public $editingEmployee = null;
    public $search = '';
    
    // Form fields
    public $first_name = '';
    public $last_name = '';
    public $middle_name = '';
    public $email = '';
    public $phone = '';
    public $employee_number = '';
    public $department_id = '';
    public $job_title = '';
    public $hire_date = '';
    public $basic_salary = '';
    public $gender = '';
    public $date_of_birth = '';
    public $address = '';
    public $employee_status = 'ACTIVE';
    public $employment_type = 'full-time';

    protected $rules = [
        'first_name' => 'required|string|max:50',
        'last_name' => 'required|string|max:50',
        'email' => 'required|email|unique:employees,email',
        'phone' => 'required|string|max:20',
        'employee_number' => 'required|unique:employees,employee_number',
        'department_id' => 'required|exists:departments,id',
        'job_title' => 'required|string|max:100',
        'hire_date' => 'required|date',
        'basic_salary' => 'required|numeric|min:0',
        'gender' => 'required|in:male,female',
        'employment_type' => 'required|in:full-time,part-time,contract',
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function openAddModal()
    {
        $this->reset(['first_name', 'last_name', 'middle_name', 'email', 'phone', 
                     'employee_number', 'department_id', 'job_title', 'hire_date', 
                     'basic_salary', 'gender', 'date_of_birth', 'address']);
        $this->showAddModal = true;
    }

    public function closeAddModal()
    {
        $this->showAddModal = false;
        $this->resetErrorBag();
    }

    public function saveEmployee()
    {
        $validatedData = $this->validate();
        
        // Generate employee number if not provided
        if (empty($this->employee_number)) {
            $this->employee_number = 'EMP' . str_pad(Employee::count() + 1, 5, '0', STR_PAD_LEFT);
        }
        
        \DB::beginTransaction();
        try {
            // Create the employee
            $employee = Employee::create([
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'middle_name' => $this->middle_name,
                'email' => $this->email,
                'phone' => $this->phone,
                'employee_number' => $this->employee_number,
                'department_id' => $this->department_id,
                'job_title' => $this->job_title,
                'hire_date' => $this->hire_date,
                'basic_salary' => $this->basic_salary,
                'gross_salary' => $this->basic_salary, // Can be calculated with allowances
                'gender' => $this->gender,
                'date_of_birth' => $this->date_of_birth,
                'address' => $this->address,
                'employee_status' => 'ACTIVE',
                'employment_type' => $this->employment_type,
            ]);
            
            // Create user account for the employee
            $this->createUserAccountForEmployee($employee);
            
            \DB::commit();
            
            session()->flash('success', 'Employee added successfully! Login credentials are being sent in the background.');
            $this->closeAddModal();
            
        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error creating employee: ' . $e->getMessage());
            session()->flash('error', 'Error creating employee: ' . $e->getMessage());
        }
    }

    public function editEmployee($id)
    {
        $employee = Employee::find($id);
        $this->editingEmployee = $id;
        
        $this->first_name = $employee->first_name;
        $this->last_name = $employee->last_name;
        $this->middle_name = $employee->middle_name;
        $this->email = $employee->email;
        $this->phone = $employee->phone;
        $this->employee_number = $employee->employee_number;
        $this->department_id = $employee->department_id;
        $this->job_title = $employee->job_title;
        $this->hire_date = $employee->hire_date;
        $this->basic_salary = $employee->basic_salary;
        $this->gender = $employee->gender;
        $this->date_of_birth = $employee->date_of_birth;
        $this->address = $employee->address;
        $this->employee_status = $employee->employee_status;
        $this->employment_type = $employee->employment_type;
        
        $this->showEditModal = true;
    }

    public function updateEmployee()
    {
        $rules = $this->rules;
        $rules['email'] = 'required|email|unique:employees,email,' . $this->editingEmployee;
        $rules['employee_number'] = 'required|unique:employees,employee_number,' . $this->editingEmployee;
        
        $validatedData = $this->validate($rules);
        
        Employee::find($this->editingEmployee)->update([
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'middle_name' => $this->middle_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'employee_number' => $this->employee_number,
            'department_id' => $this->department_id,
            'job_title' => $this->job_title,
            'hire_date' => $this->hire_date,
            'basic_salary' => $this->basic_salary,
            'gross_salary' => $this->basic_salary,
            'gender' => $this->gender,
            'date_of_birth' => $this->date_of_birth,
            'address' => $this->address,
            'employee_status' => $this->employee_status,
            'employment_type' => $this->employment_type,
        ]);
        
        User::where('employeeId', $this->editingEmployee)->update([
            'email' => $this->email,
            'phone_number' => $this->phone,
        ]);
        
        session()->flash('success', 'Employee updated successfully!');
        $this->showEditModal = false;
        $this->editingEmployee = null;
        $this->resetErrorBag();
    }

    public function deleteEmployee($id)
    {
        Employee::find($id)->delete();
        session()->flash('success', 'Employee deleted successfully!');
    }

    protected function createUserAccountForEmployee($employee)
    {
        try {
            // Generate a random password
            $password = $this->generateSecurePassword();
            
            // Get department
            $department = Department::find($employee->department_id);
            
            // Create user account
            $user = User::create([
                'name' => $employee->first_name . ' ' . $employee->last_name,
                'email' => $employee->email,
                'password' => Hash::make($password),
                'phone_number' => $employee->phone,
                'employeeId' => $employee->id,
                'department_code' => $department ? $department->code : null,
                'branch_id' => 1, // Default branch, can be modified as needed
                'status' => 'ACTIVE',
                'verification_status' => 1,
                'email_verified_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Assign default role (Employee role - adjust as needed)
            // You may want to assign role based on job title or department
            \DB::table('user_roles')->insert([
                'user_id' => $user->id,
                'role_id' => 3, // Default employee role ID - adjust as needed
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Dispatch job to send credentials in background
            SendEmployeeCredentials::dispatch($employee, $password, $user);
            
            \Log::info('User account created for employee and credentials job dispatched', [
                'employee_id' => $employee->id,
                'user_id' => $user->id,
                'email' => $employee->email
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error creating user account for employee', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
    
    protected function generateSecurePassword($length = 10)
    {
        // Generate a secure random password
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*';
        
        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];
        
        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }
        
        return str_shuffle($password);
    }
    

    public function render()
    {
        $employees = Employee::where(function($query) {
                $query->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('employee_number', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        $departments = Department::all();
        
        return view('livewire.h-r.employee-management', [
            'employees' => $employees,
            'departments' => $departments
        ]);
    }
}