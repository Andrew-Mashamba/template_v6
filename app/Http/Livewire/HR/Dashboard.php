<?php

namespace App\Http\Livewire\HR;

use App\Models\Employee;
use App\Models\Department;
use App\Models\PayRolls as PayRoll;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Carbon\Carbon;
use App\Traits\Livewire\WithModulePermissions;

class Dashboard extends Component
{
    use WithModulePermissions;
    public $menuNumber = 0; // 0=dashboard, 1=employees, 2=payroll, 3=leave, 4=attendance, 5=requests
    public $totalEmployees;
    public $totalDepartments;
    public $activeEmployees;
    public $pendingPayroll;
    public $departmentStats;
    public $monthlyPayrollTotal;

    public function mount()
    {
        // Initialize the permission system for this module
        $this->initializeWithModulePermissions();
        $this->loadDashboardData();
    }

    public function setMenuNumber($number)
    {
        // Check permissions based on the menu being accessed
        $permissionMap = [
            0 => 'canView',         // Dashboard Overview
            1 => 'canEmployees',    // Employee Management
            2 => 'canPayroll',      // Payroll Management
            3 => 'canLeave',        // Leave Management
            4 => 'canAttendance',   // Attendance Tracking
            5 => 'canRequests'      // Request Management
        ];
        
        $requiredPermission = $permissionMap[$number] ?? 'canView';
        
        if (!($this->permissions[$requiredPermission] ?? false)) {
            session()->flash('error', 'You do not have permission to access this HR section');
            return;
        }
        
        $this->menuNumber = $number;
    }

    protected function loadDashboardData()
    {
        $this->totalEmployees = Employee::count();
        $this->activeEmployees = Employee::where('employee_status', 'active')->count();
        $this->totalDepartments = Department::count();
        
        // Get current month payroll stats
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;
        
        $this->pendingPayroll = PayRoll::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->where('status', 'pending')
            ->count();
            
        $this->monthlyPayrollTotal = PayRoll::where('month', $currentMonth)
            ->where('year', $currentYear)
            ->sum('net_salary');

        // Get department statistics
        $departmentStats = DB::table('employees')
            ->leftJoin('departments', 'employees.department_id', '=', 'departments.id')
            ->select('departments.department_name', DB::raw('count(employees.id) as employee_count'))
            ->whereNotNull('departments.id')
            ->groupBy('departments.id', 'departments.department_name')
            ->get();

        $this->departmentStats = $departmentStats->map(function ($dept) {
            return [
                'name' => $dept->department_name ?? 'Unassigned',
                'count' => $dept->employee_count
            ];
        })->toArray();
    }

    public function render()
    {
        return view('livewire.h-r.dashboard', array_merge(
            $this->permissions,
            [
                'permissions' => $this->permissions
            ]
        ));
    }

    /**
     * Override to specify the module name for permissions
     * 
     * @return string
     */
    protected function getModuleName(): string
    {
        return 'hr';
    }
} 