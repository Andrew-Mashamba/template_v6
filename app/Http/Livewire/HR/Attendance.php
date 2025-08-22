<?php

namespace App\Http\Livewire\HR;

use Livewire\Component;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class Attendance extends Component
{
    public $attendanceData = [];
    public $todayPresent = 0;
    public $todayAbsent = 0;
    public $todayLate = 0;
    public $monthlyAttendanceRate = 0;

    public function mount()
    {
        $this->loadAttendanceData();
    }

    public function loadAttendanceData()
    {
        // Get today's date
        $today = Carbon::today();
        
        // Get total employees
        $totalEmployees = User::where('status', 'active')->count();
        
        // For now, we'll use dummy data - you can replace this with actual attendance data
        // from your attendance tracking system
        $this->todayPresent = intval($totalEmployees * 0.85); // 85% present
        $this->todayAbsent = intval($totalEmployees * 0.10); // 10% absent
        $this->todayLate = intval($totalEmployees * 0.05); // 5% late
        
        // Calculate monthly attendance rate
        $this->monthlyAttendanceRate = 87.5; // Dummy value - replace with actual calculation
        
        // Get recent attendance records (dummy data for now)
        $this->attendanceData = [
            [
                'employee' => 'John Doe',
                'status' => 'Present',
                'check_in' => '08:00 AM',
                'check_out' => '05:00 PM',
            ],
            [
                'employee' => 'Jane Smith',
                'status' => 'Late',
                'check_in' => '09:15 AM',
                'check_out' => '05:30 PM',
            ],
            [
                'employee' => 'Mike Johnson',
                'status' => 'Absent',
                'check_in' => '-',
                'check_out' => '-',
            ],
        ];
    }

    public function render()
    {
        return view('livewire.h-r.attendance');
    }
}