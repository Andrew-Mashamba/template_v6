<?php

namespace App\Http\Livewire\HR;

use Livewire\Component;
use App\Models\User;
use App\Models\LeaveManagement as LeaveModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveManagement extends Component
{
    public $leaveRequests = [];
    public $leaveTypes = [];
    public $totalPending = 0;
    public $totalApproved = 0;
    public $totalRejected = 0;
    public $totalOnLeave = 0;

    public function mount()
    {
        $this->loadLeaveData();
    }

    public function loadLeaveData()
    {
        // Leave types
        $this->leaveTypes = [
            'annual' => 'Annual Leave',
            'sick' => 'Sick Leave',
            'maternity' => 'Maternity Leave',
            'paternity' => 'Paternity Leave',
            'compassionate' => 'Compassionate Leave',
            'study' => 'Study Leave'
        ];

        // Get leave statistics
        $this->totalPending = 5;  // Dummy data
        $this->totalApproved = 12;  // Dummy data
        $this->totalRejected = 2;  // Dummy data
        $this->totalOnLeave = 3;  // Dummy data

        // Sample leave requests data
        $this->leaveRequests = [
            [
                'id' => 1,
                'employee' => 'Sarah Johnson',
                'type' => 'Annual Leave',
                'start_date' => Carbon::parse('2025-08-01'),
                'end_date' => Carbon::parse('2025-08-05'),
                'days' => 5,
                'status' => 'pending',
                'reason' => 'Family vacation'
            ],
            [
                'id' => 2,
                'employee' => 'Michael Chen',
                'type' => 'Sick Leave',
                'start_date' => Carbon::parse('2025-07-30'),
                'end_date' => Carbon::parse('2025-07-31'),
                'days' => 2,
                'status' => 'approved',
                'reason' => 'Medical appointment'
            ],
            [
                'id' => 3,
                'employee' => 'Emily Brown',
                'type' => 'Study Leave',
                'start_date' => Carbon::parse('2025-08-10'),
                'end_date' => Carbon::parse('2025-08-14'),
                'days' => 5,
                'status' => 'pending',
                'reason' => 'Professional certification exam'
            ],
            [
                'id' => 4,
                'employee' => 'David Wilson',
                'type' => 'Compassionate Leave',
                'start_date' => Carbon::parse('2025-07-28'),
                'end_date' => Carbon::parse('2025-07-29'),
                'days' => 2,
                'status' => 'approved',
                'reason' => 'Family emergency'
            ]
        ];
    }

    public function approveLeave($leaveId)
    {
        // Handle leave approval
        session()->flash('message', 'Leave request approved successfully!');
        $this->loadLeaveData();
    }

    public function rejectLeave($leaveId)
    {
        // Handle leave rejection
        session()->flash('message', 'Leave request rejected.');
        $this->loadLeaveData();
    }

    public function render()
    {
        return view('livewire.h-r.leave-management');
    }
}