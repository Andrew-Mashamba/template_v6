{{-- Employee Management --}}
<div>
    {{-- Header with Add Button --}}
    <div class="flex justify-between items-center mb-4">
        <div class="flex-1 max-w-md">
            <input type="text" wire:model.debounce.300ms="search" 
                placeholder="Search employees..." 
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
        </div>
        {{--<button wire:click="openAddModal" 
            class="ml-4 px-4 py-2 bg-blue-900 text-white rounded-lg hover:bg-blue-700 transition">
            <span class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                </svg>
                Add Employee
            </span>
        </button>--}}
    </div>

    {{-- Success Message --}}
    @if(session()->has('success'))
        <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    {{-- Employee List Table --}}
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Employee #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Hire Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($employees as $employee)
                    <tr>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div>
                                <div class="text-sm font-medium text-gray-900">
                                    {{ $employee->first_name }} {{ $employee->last_name }}
                                </div>
                                <div class="text-sm text-gray-500">
                                    {{ $employee->email }}
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $employee->employee_number }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $employee->department->department_name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ $employee->job_title }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                {{ $employee->employee_status === 'ACTIVE' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ ucfirst($employee->employee_status ?? 'N/A') }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                            {{ \Carbon\Carbon::parse($employee->hire_date)->format('M d, Y') }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <button wire:click="editEmployee({{ $employee->id }})" class="text-blue-600 hover:text-blue-900 mr-3">
                                Edit
                            </button>
                            {{--<button wire:click="$emit('confirmDelete', {{ $employee->id }})" class="text-red-600 hover:text-red-900">
                                Delete
                            </button>--}}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                            No employees found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($employees->hasPages())
            <div class="px-6 py-3 bg-gray-50 border-t">
                {{ $employees->links() }}
            </div>
        @endif
    </div>

    {{-- Add Employee Modal --}}
    @if($showAddModal)
        <div class="fixed z-50 inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity" wire:click="closeAddModal">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit.prevent="saveEmployee">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Employee</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                {{-- First Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input type="text" wire:model="first_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Last Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input type="text" wire:model="last_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Email --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input type="email" wire:model="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Phone --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                                    <input type="text" wire:model="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Employee Number --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee Number *</label>
                                    <input type="text" wire:model="employee_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('employee_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Department --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                                    <select wire:model="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('department_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Job Title --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Job Title *</label>
                                    <input type="text" wire:model="job_title" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('job_title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Hire Date --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hire Date *</label>
                                    <input type="date" wire:model="hire_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('hire_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Basic Salary --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Basic Salary *</label>
                                    <input type="number" step="0.01" wire:model="basic_salary" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('basic_salary') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Gender --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                                    <select wire:model="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                    @error('gender') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Employment Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type *</label>
                                    <select wire:model="employment_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        <option value="full-time">Full Time</option>
                                        <option value="part-time">Part Time</option>
                                        <option value="contract">Contract</option>
                                    </select>
                                    @error('employment_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Date of Birth --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="date" wire:model="date_of_birth" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                {{-- Address --}}
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <textarea wire:model="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-900 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Save Employee
                            </button>
                            <button type="button" wire:click="closeAddModal" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Edit Employee Modal --}}
    @if($showEditModal)
        <div class="fixed z-50 inset-0 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity" wire:click="$set('showEditModal', false)">
                    <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                </div>

                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                    <form wire:submit.prevent="updateEmployee">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Employee</h3>
                            
                            <div class="grid grid-cols-2 gap-4">
                                {{-- Same fields as Add Modal --}}
                                {{-- First Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                    <input type="text" wire:model="first_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('first_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Last Name --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                    <input type="text" wire:model="last_name" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('last_name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Email --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                    <input type="email" wire:model="email" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Phone --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone *</label>
                                    <input type="text" wire:model="phone" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('phone') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Employee Number --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employee Number *</label>
                                    <input type="text" wire:model="employee_number" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('employee_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Department --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Department *</label>
                                    <select wire:model="department_id" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Department</option>
                                        @foreach($departments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->department_name }}</option>
                                        @endforeach
                                    </select>
                                    @error('department_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Job Title --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Job Title *</label>
                                    <input type="text" wire:model="job_title" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('job_title') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Hire Date --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Hire Date *</label>
                                    <input type="date" wire:model="hire_date" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('hire_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Basic Salary --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Basic Salary *</label>
                                    <input type="number" step="0.01" wire:model="basic_salary" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                    @error('basic_salary') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Gender --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Gender *</label>
                                    <select wire:model="gender" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                    </select>
                                    @error('gender') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Employment Type --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Employment Type *</label>
                                    <select wire:model="employment_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        <option value="full-time">Full Time</option>
                                        <option value="part-time">Part Time</option>
                                        <option value="contract">Contract</option>
                                    </select>
                                    @error('employment_type') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                </div>

                                {{-- Employee Status --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select wire:model="employee_status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                        <option value="ACTIVE">Active</option>
                                        <option value="INACTIVE">Inactive</option>
                                        <option value="SUSPENDED">Suspended</option>
                                    </select>
                                </div>

                                {{-- Date of Birth --}}
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="date" wire:model="date_of_birth" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                                </div>

                                {{-- Address --}}
                                <div class="col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <textarea wire:model="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-900 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                                Update Employee
                            </button>
                            <button type="button" wire:click="$set('showEditModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Delete Confirmation --}}
    <script>
        window.addEventListener('confirmDelete', event => {
            if (confirm('Are you sure you want to delete this employee?')) {
                @this.deleteEmployee(event.detail);
            }
        });
    </script>
</div>