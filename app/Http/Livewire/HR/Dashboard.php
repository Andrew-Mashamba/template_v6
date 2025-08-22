<?php

namespace App\Http\Livewire\HR;

use App\Models\Employee;
use App\Models\Department;
use App\Models\Leave;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class Dashboard extends Component
{
    public $menuNumber = 0; // 0 for dashboard, 1 for recruitment, etc.
    public $totalEmployees;
    public $totalDepartments;
    public $activeLeaves;
    public $departmentStats;

    public function mount()
    {
        $this->loadDashboardData();
    }

    public function setMenuNumber($number)
    {
        $this->menuNumber = $number;
    }

    protected function loadDashboardData()
    {
        $this->totalEmployees = Employee::count();
        $this->totalDepartments = Department::count();
        $this->activeLeaves = Leave::where('status', 'approved')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->count();

        // Get department statistics
        $departmentStats = DB::table('employees')
            ->join('departments', 'employees.department_id', '=', 'departments.id')
            ->select('departments.department_name', DB::raw('count(*) as employee_count'))
            ->groupBy('departments.id', 'departments.department_name')
            ->get();

        $this->departmentStats = $departmentStats->map(function ($dept) {
            return [
                'name' => $dept->department_name,
                'count' => $dept->employee_count,
                'status' => 'active'
            ];
        });
    }

    public function render()
    {
        return view('livewire.h-r.dashboard');
    }
} 