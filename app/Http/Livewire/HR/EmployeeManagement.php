<?php

namespace App\Http\Livewire\HR;

use App\Models\Employee;
use App\Models\Department;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

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
    public $employee_status = 'active';
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
        
        Employee::create([
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
            'employee_status' => $this->employee_status,
            'employment_type' => $this->employment_type,
        ]);
        
        session()->flash('success', 'Employee added successfully!');
        $this->closeAddModal();
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