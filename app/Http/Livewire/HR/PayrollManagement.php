<?php

namespace App\Http\Livewire\HR;

use App\Models\Employee;
use App\Models\PayRolls as PayRoll;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class PayrollManagement extends Component
{
    use WithPagination;

    public $month;
    public $year;
    public $showPayslipModal = false;
    public $selectedEmployee = null;
    public $search = '';

    public function mount()
    {
        $this->month = Carbon::now()->month;
        $this->year = Carbon::now()->year;
    }

    public function generatePayroll()
    {
        $employees = Employee::where('employee_status', 'active')->get();
        $generated = 0;
        
        foreach ($employees as $employee) {
            // Check if payroll already exists for this month
            $exists = PayRoll::where('employee_id', $employee->id)
                ->where('month', $this->month)
                ->where('year', $this->year)
                ->exists();
                
            if (!$exists) {
                $this->createPayrollEntry($employee);
                $generated++;
            }
        }
        
        session()->flash('success', "Payroll generated for {$generated} employees!");
    }

    private function createPayrollEntry($employee)
    {
        $basicSalary = $employee->basic_salary ?? 0;
        
        // Calculate deductions
        $paye = $this->calculatePAYE($basicSalary);
        $nssf = $basicSalary * 0.10; // 10% NSSF
        $nhif = $this->calculateNHIF($basicSalary);
        
        // Calculate allowances (can be customized)
        $houseAllowance = $basicSalary * 0.15;
        $transportAllowance = $basicSalary * 0.10;
        
        $grossSalary = $basicSalary + $houseAllowance + $transportAllowance;
        $totalDeductions = $paye + $nssf + $nhif;
        $netSalary = $grossSalary - $totalDeductions;
        
        PayRoll::create([
            'employee_id' => $employee->id,
            'month' => $this->month,
            'year' => $this->year,
            'basic_salary' => $basicSalary,
            'house_allowance' => $houseAllowance,
            'transport_allowance' => $transportAllowance,
            'gross_salary' => $grossSalary,
            'paye' => $paye,
            'nssf' => $nssf,
            'nhif' => $nhif,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'status' => 'pending',
            'payment_date' => Carbon::create($this->year, $this->month, 25)
        ]);
    }

    private function calculatePAYE($salary)
    {
        // Simplified PAYE calculation (customize based on your tax laws)
        if ($salary <= 270000) {
            return 0;
        } elseif ($salary <= 520000) {
            return ($salary - 270000) * 0.08;
        } elseif ($salary <= 760000) {
            return 20000 + (($salary - 520000) * 0.20);
        } elseif ($salary <= 1000000) {
            return 68000 + (($salary - 760000) * 0.25);
        } else {
            return 128000 + (($salary - 1000000) * 0.30);
        }
    }

    private function calculateNHIF($salary)
    {
        // Simplified NHIF calculation
        if ($salary <= 5999) return 150;
        if ($salary <= 7999) return 300;
        if ($salary <= 11999) return 400;
        if ($salary <= 14999) return 500;
        if ($salary <= 19999) return 600;
        if ($salary <= 24999) return 750;
        if ($salary <= 29999) return 850;
        if ($salary <= 34999) return 900;
        if ($salary <= 39999) return 950;
        if ($salary <= 44999) return 1000;
        if ($salary <= 49999) return 1100;
        if ($salary <= 59999) return 1200;
        if ($salary <= 69999) return 1300;
        if ($salary <= 79999) return 1400;
        if ($salary <= 89999) return 1500;
        if ($salary <= 99999) return 1600;
        return 1700;
    }

    public function approvePayroll($id)
    {
        $payroll = PayRoll::find($id);
        $payroll->update(['status' => 'approved']);
        session()->flash('success', 'Payroll approved successfully!');
    }

    public function processPayment($id)
    {
        $payroll = PayRoll::find($id);
        $payroll->update([
            'status' => 'paid',
            'payment_date' => now()
        ]);
        session()->flash('success', 'Payment processed successfully!');
    }

    public function viewPayslip($id)
    {
        $this->selectedEmployee = PayRoll::with('employee')->find($id);
        $this->showPayslipModal = true;
    }

    public function render()
    {
        $payrolls = PayRoll::with('employee')
            ->where('month', $this->month)
            ->where('year', $this->year)
            ->when($this->search, function($query) {
                $query->whereHas('employee', function($q) {
                    $q->where('first_name', 'like', '%' . $this->search . '%')
                      ->orWhere('last_name', 'like', '%' . $this->search . '%')
                      ->orWhere('employee_number', 'like', '%' . $this->search . '%');
                });
            })
            ->paginate(10);
            
        return view('livewire.h-r.payroll-management', [
            'payrolls' => $payrolls
        ]);
    }
}