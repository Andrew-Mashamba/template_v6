<?php

namespace App\Http\Livewire\SelfServices;

use App\Models\Employee;
use App\Models\User;
use App\Models\Leave;
use App\Models\PayRolls as PayRoll;
use App\Models\EmployeeRequest;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SelfServices extends Component
{
    public $selectedMenu = 'dashboard'; // Default to dashboard
    
    // Employee data
    public $employee;
    public $user;
    
    // Dashboard stats
    public $pendingLeaves;
    public $approvedLeaves;
    public $totalLeaveDays;
    public $remainingLeaveDays;
    public $lastPayroll;
    public $pendingRequests;
    
    // Leave request form
    public $leaveType = '';
    public $leaveStartDate = '';
    public $leaveEndDate = '';
    public $leaveReason = '';
    public $showLeaveModal = false;
    
    // Request forms
    public $requestType = '';
    public $requestSubject = '';
    public $requestDetails = '';
    public $requestDepartment = '';
    public $showRequestModal = false;
    
    // Specific request fields
    public $materialItems = '';
    public $materialPurpose = '';
    public $resignationDate = '';
    public $resignationReason = '';
    public $travelDestination = '';
    public $travelStartDate = '';
    public $travelEndDate = '';
    public $travelPurpose = '';
    public $advanceAmount = '';
    public $advanceReason = '';
    public $trainingTitle = '';
    public $trainingStartDate = '';
    public $trainingEndDate = '';
    public $trainingLocation = '';
    public $overtimeDate = '';
    public $overtimeHours = '';
    public $overtimeReason = '';
    public $documentType = '';

    public function mount()
    {
        $this->loadEmployeeData();
        $this->loadDashboardStats();
    }

    protected function loadEmployeeData()
    {
        $this->user = Auth::user();
        
        if ($this->user && $this->user->employeeId) {
            $this->employee = Employee::find($this->user->employeeId);
        }
    }

    protected function loadDashboardStats()
    {
        if (!$this->employee) return;
        
        $currentYear = Carbon::now()->year;
        
        // Leave statistics
        $this->pendingLeaves = Leave::where('employee_id', $this->employee->id)
            ->where('status', 'pending')
            ->count();
            
        $this->approvedLeaves = Leave::where('employee_id', $this->employee->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $currentYear)
            ->count();
            
        // Calculate total leave days from approved leaves
        $approvedLeaves = Leave::where('employee_id', $this->employee->id)
            ->where('status', 'approved')
            ->whereYear('start_date', $currentYear)
            ->get();
            
        $this->totalLeaveDays = 0;
        foreach ($approvedLeaves as $leave) {
            $startDate = Carbon::parse($leave->start_date);
            $endDate = Carbon::parse($leave->end_date);
            $this->totalLeaveDays += $startDate->diffInDays($endDate) + 1;
        }
            
        $this->remainingLeaveDays = 21 - $this->totalLeaveDays; // Assuming 21 days annual leave
        
        // Latest payroll
        $this->lastPayroll = PayRoll::where('employee_id', $this->employee->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->first();
            
        // Pending requests
        $this->pendingRequests = EmployeeRequest::where('employee_id', $this->employee->id)
            ->where('status', 'pending')
            ->count();
    }

    public function submitLeaveRequest()
    {
        $this->validate([
            'leaveType' => 'required',
            'leaveStartDate' => 'required|date|after_or_equal:today',
            'leaveEndDate' => 'required|date|after_or_equal:leaveStartDate',
            'leaveReason' => 'required|min:10'
        ]);
        
        if (!$this->employee) {
            session()->flash('error', 'Employee profile not found. Please contact HR.');
            return;
        }
        
        Leave::create([
            'employee_id' => $this->employee->id,
            'leave_type' => $this->leaveType,
            'start_date' => $this->leaveStartDate,
            'end_date' => $this->leaveEndDate,
            'reason' => $this->leaveReason,
            'status' => 'pending'
        ]);
        
        $this->resetLeaveForm();
        $this->showLeaveModal = false;
        $this->loadDashboardStats();
        
        session()->flash('success', 'Leave request submitted successfully!');
    }

    public function submitMaterialsRequest()
    {
        $this->validate([
            'materialItems' => 'required|min:5',
            'materialPurpose' => 'required|min:10'
        ]);
        
        if (!$this->employee) {
            session()->flash('error', 'Employee profile not found. Please contact HR.');
            return;
        }
        
        EmployeeRequest::create([
            'employee_id' => $this->employee->id,
            'type' => EmployeeRequest::TYPE_MATERIALS,
            'subject' => 'Working Materials Request',
            'details' => "Items Needed: " . $this->materialItems . "\n\nPurpose: " . $this->materialPurpose,
            'status' => EmployeeRequest::STATUS_PENDING
        ]);
        
        $this->resetRequestForm();
        session()->flash('success', 'Materials request submitted successfully!');
    }
    
    public function submitResignationRequest()
    {
        $this->validate([
            'resignationDate' => 'required|date|after:today',
            'resignationReason' => 'required|min:20'
        ]);
        
        if (!$this->employee) {
            session()->flash('error', 'Employee profile not found. Please contact HR.');
            return;
        }
        
        EmployeeRequest::create([
            'employee_id' => $this->employee->id,
            'type' => EmployeeRequest::TYPE_RESIGNATION,
            'subject' => 'Resignation Request - Effective ' . $this->resignationDate,
            'details' => "Resignation Date: " . $this->resignationDate . "\n\nReason: " . $this->resignationReason,
            'status' => EmployeeRequest::STATUS_PENDING
        ]);
        
        $this->resetRequestForm();
        session()->flash('success', 'Resignation request submitted. HR will contact you soon.');
    }
    
    public function submitTravelRequest()
    {
        $this->validate([
            'travelDestination' => 'required|min:3',
            'travelStartDate' => 'required|date|after_or_equal:today',
            'travelEndDate' => 'required|date|after_or_equal:travelStartDate',
            'travelPurpose' => 'required|min:10'
        ]);
        
        if (!$this->employee) {
            session()->flash('error', 'Employee profile not found. Please contact HR.');
            return;
        }
        
        EmployeeRequest::create([
            'employee_id' => $this->employee->id,
            'type' => EmployeeRequest::TYPE_TRAVEL,
            'subject' => 'Travel Request to ' . $this->travelDestination,
            'details' => "Destination: " . $this->travelDestination . 
                        "\nDates: " . $this->travelStartDate . " to " . $this->travelEndDate .
                        "\nPurpose: " . $this->travelPurpose,
            'status' => EmployeeRequest::STATUS_PENDING
        ]);
        
        $this->resetRequestForm();
        session()->flash('success', 'Travel request submitted successfully!');
    }
    
    public function submitAdvanceRequest()
    {
        $this->validate([
            'advanceAmount' => 'required|numeric|min:10000',
            'advanceReason' => 'required|min:10'
        ]);
        
        if (!$this->employee) {
            session()->flash('error', 'Employee profile not found. Please contact HR.');
            return;
        }
        
        EmployeeRequest::create([
            'employee_id' => $this->employee->id,
            'type' => EmployeeRequest::TYPE_ADVANCE,
            'subject' => 'Salary Advance Request - TZS ' . number_format($this->advanceAmount),
            'details' => "Amount Requested: TZS " . number_format($this->advanceAmount) . 
                        "\nReason: " . $this->advanceReason,
            'status' => EmployeeRequest::STATUS_PENDING
        ]);
        
        $this->resetRequestForm();
        session()->flash('success', 'Advance request submitted successfully!');
    }
    
    public function submitTrainingRequest()
    {
        $this->validate([
            'trainingTitle' => 'required|min:5',
            'trainingStartDate' => 'required|date|after_or_equal:today',
            'trainingEndDate' => 'required|date|after_or_equal:trainingStartDate',
            'trainingLocation' => 'required|min:3'
        ]);
        
        if (!$this->employee) {
            session()->flash('error', 'Employee profile not found. Please contact HR.');
            return;
        }
        
        EmployeeRequest::create([
            'employee_id' => $this->employee->id,
            'type' => EmployeeRequest::TYPE_TRAINING,
            'subject' => 'Training Request - ' . $this->trainingTitle,
            'details' => "Training: " . $this->trainingTitle . 
                        "\nDates: " . $this->trainingStartDate . " to " . $this->trainingEndDate .
                        "\nLocation: " . $this->trainingLocation,
            'status' => EmployeeRequest::STATUS_PENDING
        ]);
        
        $this->resetRequestForm();
        session()->flash('success', 'Training request submitted successfully!');
    }
    
    public function submitOvertimeRequest()
    {
        $this->validate([
            'overtimeDate' => 'required|date',
            'overtimeHours' => 'required|numeric|min:1|max:12',
            'overtimeReason' => 'required|min:10'
        ]);
        
        if (!$this->employee) {
            session()->flash('error', 'Employee profile not found. Please contact HR.');
            return;
        }
        
        EmployeeRequest::create([
            'employee_id' => $this->employee->id,
            'type' => EmployeeRequest::TYPE_OVERTIME,
            'subject' => 'Overtime Request - ' . $this->overtimeDate,
            'details' => "Date: " . $this->overtimeDate . 
                        "\nHours: " . $this->overtimeHours .
                        "\nReason: " . $this->overtimeReason,
            'status' => EmployeeRequest::STATUS_PENDING
        ]);
        
        $this->resetRequestForm();
        session()->flash('success', 'Overtime request submitted successfully!');
    }
    
    public function submitPayslipRequest()
    {
        $this->validate([
            'documentType' => 'required'
        ]);
        
        if (!$this->employee) {
            session()->flash('error', 'Employee profile not found. Please contact HR.');
            return;
        }
        
        $type = $this->documentType == 'payslip' ? EmployeeRequest::TYPE_PAYSLIP : EmployeeRequest::TYPE_HR_DOCS;
        
        EmployeeRequest::create([
            'employee_id' => $this->employee->id,
            'type' => $type,
            'subject' => ucfirst($this->documentType) . ' Request',
            'details' => "Document requested: " . ucfirst($this->documentType),
            'status' => EmployeeRequest::STATUS_PENDING
        ]);
        
        $this->resetRequestForm();
        session()->flash('success', ucfirst($this->documentType) . ' request submitted successfully!');
    }
    
    public function submitGeneralRequest()
    {
        $this->validate([
            'requestSubject' => 'required|min:5',
            'requestDetails' => 'required|min:20'
        ]);
        
        if (!$this->employee) {
            session()->flash('error', 'Employee profile not found. Please contact HR.');
            return;
        }
        
        EmployeeRequest::create([
            'employee_id' => $this->employee->id,
            'type' => EmployeeRequest::TYPE_GENERAL,
            'subject' => $this->requestSubject,
            'details' => $this->requestDetails,
            'department' => $this->requestDepartment,
            'status' => EmployeeRequest::STATUS_PENDING
        ]);
        
        $this->resetRequestForm();
        $this->loadDashboardStats();
        
        session()->flash('success', 'Request submitted successfully!');
    }

    public function resetLeaveForm()
    {
        $this->leaveType = '';
        $this->leaveStartDate = '';
        $this->leaveEndDate = '';
        $this->leaveReason = '';
    }

    public function resetRequestForm()
    {
        $this->requestType = '';
        $this->requestSubject = '';
        $this->requestDetails = '';
        $this->requestDepartment = '';
        $this->materialItems = '';
        $this->materialPurpose = '';
        $this->resignationDate = '';
        $this->resignationReason = '';
        $this->travelDestination = '';
        $this->travelStartDate = '';
        $this->travelEndDate = '';
        $this->travelPurpose = '';
        $this->advanceAmount = '';
        $this->advanceReason = '';
        $this->trainingTitle = '';
        $this->trainingStartDate = '';
        $this->trainingEndDate = '';
        $this->trainingLocation = '';
        $this->overtimeDate = '';
        $this->overtimeHours = '';
        $this->overtimeReason = '';
        $this->documentType = '';
    }

    public function getMyLeaves()
    {
        if (!$this->employee) return collect();
        
        return Leave::where('employee_id', $this->employee->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }

    public function getMyPayrolls()
    {
        if (!$this->employee) return collect();
        
        return PayRoll::where('employee_id', $this->employee->id)
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->take(6)
            ->get();
    }

    public function getMyRequests()
    {
        if (!$this->employee) return collect();
        
        return EmployeeRequest::where('employee_id', $this->employee->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();
    }

    public function viewPayslip($payrollId)
    {
        $payroll = PayRoll::where('id', $payrollId)
            ->where('employee_id', $this->employee->id)
            ->first();
            
        if ($payroll) {
            session()->flash('info', 'Payslip view feature coming soon. Your net salary for ' . $payroll->month . '/' . $payroll->year . ' is TZS ' . number_format($payroll->net_salary, 2));
        }
    }

    public function render()
    {
        return view('livewire.self-services.self-services', [
            'leaves' => $this->getMyLeaves(),
            'payrolls' => $this->getMyPayrolls(),
            'requests' => $this->getMyRequests()
        ]);
    }
}
