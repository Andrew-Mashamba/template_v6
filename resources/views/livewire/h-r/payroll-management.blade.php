{{-- Payroll Management --}}
<div>
    {{-- Header with Controls --}}
    <div class="flex justify-between items-center mb-4">
        <div class="flex items-center space-x-4">
            {{-- Month Selector --}}
            <select wire:model="month" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @for($i = 1; $i <= 12; $i++)
                    <option value="{{ $i }}">{{ date('F', mktime(0, 0, 0, $i, 1)) }}</option>
                @endfor
            </select>
            
            {{-- Year Selector --}}
            <select wire:model="year" class="px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                @for($i = date('Y') - 2; $i <= date('Y') + 1; $i++)
                    <option value="{{ $i }}">{{ $i }}</option>
                @endfor
            </select>

            {{-- Search --}}
            <input type="text" wire:model.debounce.300ms="search" 
                placeholder="Search employee..." 
                class="px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
        </div>

        {{-- Generate Payroll Button --}}
        <button wire:click="generatePayroll" 
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
            <span class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
                Generate Payroll
            </span>
        </button>
    </div>

    {{-- Success Message --}}
    @if(session()->has('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Payroll Summary Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total Employees</p>
            <p class="text-2xl font-bold text-gray-900">{{ $payrolls->total() }}</p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total Gross</p>
            <p class="text-2xl font-bold text-gray-900">
                TZS {{ number_format($payrolls->sum('gross_salary'), 2) }}
            </p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total Deductions</p>
            <p class="text-2xl font-bold text-red-600">
                TZS {{ number_format($payrolls->sum('total_deductions'), 2) }}
            </p>
        </div>
        
        <div class="bg-white rounded-lg shadow p-4">
            <p class="text-sm text-gray-500">Total Net Pay</p>
            <p class="text-2xl font-bold text-green-600">
                TZS {{ number_format($payrolls->sum('net_salary'), 2) }}
            </p>
        </div>
    </div>

    {{-- Payroll Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Basic Salary</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Allowances</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Gross Salary</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Deductions</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Pay</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($payrolls as $payroll)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $payroll->employee->first_name ?? '' }} {{ $payroll->employee->last_name ?? '' }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $payroll->employee->employee_number ?? '' }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            TZS {{ number_format($payroll->basic_salary, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            TZS {{ number_format(($payroll->house_allowance ?? 0) + ($payroll->transport_allowance ?? 0), 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            TZS {{ number_format($payroll->gross_salary, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                            TZS {{ number_format($payroll->total_deductions, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-green-600">
                            TZS {{ number_format($payroll->net_salary, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($payroll->status === 'paid')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Paid
                                </span>
                            @elseif($payroll->status === 'approved')
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                    Approved
                                </span>
                            @else
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Pending
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button wire:click="viewPayslip({{ $payroll->id }})" class="text-blue-600 hover:text-blue-900 mr-3">
                                View
                            </button>
                            @if($payroll->status === 'pending')
                                <button wire:click="approvePayroll({{ $payroll->id }})" class="text-green-600 hover:text-green-900 mr-3">
                                    Approve
                                </button>
                            @endif
                            @if($payroll->status === 'approved')
                                <button wire:click="processPayment({{ $payroll->id }})" class="text-green-600 hover:text-green-900">
                                    Pay
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                            No payroll records found for {{ date('F', mktime(0, 0, 0, $month, 1)) }} {{ $year }}.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($payrolls->hasPages())
            <div class="px-6 py-3 bg-gray-50 border-t">
                {{ $payrolls->links() }}
            </div>
        @endif
    </div>

    {{-- Payslip Modal --}}
    @if($showPayslipModal && $selectedEmployee)
        <div class="fixed z-50 inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity" wire:click="$set('showPayslipModal', false)">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Payslip Details</h3>
                        
                        <div class="border rounded-lg p-4">
                            {{-- Employee Info --}}
                            <div class="mb-4 pb-4 border-b">
                                <h4 class="font-semibold text-gray-900 mb-2">Employee Information</h4>
                                <div class="grid grid-cols-2 gap-2 text-sm">
                                    <div>
                                        <span class="text-gray-600">Name:</span>
                                        <span class="ml-2 font-medium">{{ $selectedEmployee->employee->first_name ?? '' }} {{ $selectedEmployee->employee->last_name ?? '' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Employee #:</span>
                                        <span class="ml-2 font-medium">{{ $selectedEmployee->employee->employee_number ?? '' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Department:</span>
                                        <span class="ml-2 font-medium">{{ $selectedEmployee->employee->department->department_name ?? 'N/A' }}</span>
                                    </div>
                                    <div>
                                        <span class="text-gray-600">Position:</span>
                                        <span class="ml-2 font-medium">{{ $selectedEmployee->employee->job_title ?? '' }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Earnings --}}
                            <div class="mb-4 pb-4 border-b">
                                <h4 class="font-semibold text-gray-900 mb-2">Earnings</h4>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Basic Salary</span>
                                        <span class="font-medium">TZS {{ number_format($selectedEmployee->basic_salary, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">House Allowance</span>
                                        <span class="font-medium">TZS {{ number_format($selectedEmployee->house_allowance ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Transport Allowance</span>
                                        <span class="font-medium">TZS {{ number_format($selectedEmployee->transport_allowance ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between font-semibold pt-2 border-t">
                                        <span>Gross Salary</span>
                                        <span>TZS {{ number_format($selectedEmployee->gross_salary, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Deductions --}}
                            <div class="mb-4 pb-4 border-b">
                                <h4 class="font-semibold text-gray-900 mb-2">Deductions</h4>
                                <div class="space-y-1 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">PAYE</span>
                                        <span class="font-medium text-red-600">TZS {{ number_format($selectedEmployee->paye ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">NSSF</span>
                                        <span class="font-medium text-red-600">TZS {{ number_format($selectedEmployee->nssf ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">NHIF</span>
                                        <span class="font-medium text-red-600">TZS {{ number_format($selectedEmployee->nhif ?? 0, 2) }}</span>
                                    </div>
                                    <div class="flex justify-between font-semibold pt-2 border-t">
                                        <span>Total Deductions</span>
                                        <span class="text-red-600">TZS {{ number_format($selectedEmployee->total_deductions, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Net Pay --}}
                            <div>
                                <div class="flex justify-between text-lg font-bold">
                                    <span>Net Pay</span>
                                    <span class="text-green-600">TZS {{ number_format($selectedEmployee->net_salary, 2) }}</span>
                                </div>
                                <div class="mt-2 text-sm text-gray-600">
                                    Payment Date: {{ \Carbon\Carbon::parse($selectedEmployee->payment_date)->format('M d, Y') }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-50 px-4 py-3 sm:px-6 flex justify-end">
                        <button type="button" wire:click="$set('showPayslipModal', false)" 
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>