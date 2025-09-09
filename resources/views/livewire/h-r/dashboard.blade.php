{{-- Simplified HR Dashboard --}}
<div class="min-h-screen bg-gray-50">
    <div class="p-6">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Human Resources Management</h1>
            <p class="text-gray-600 mt-1">Manage employees, payroll, and HR operations</p>
        </div>

        {{-- Quick Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-full">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Total Employees</p>
                        <p class="text-xl font-semibold">{{ $totalEmployees }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Active Employees</p>
                        <p class="text-xl font-semibold">{{ $activeEmployees }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 bg-yellow-100 rounded-full">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Pending Payroll</p>
                        <p class="text-xl font-semibold">{{ $pendingPayroll }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-full">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500">Departments</p>
                        <p class="text-xl font-semibold">{{ $totalDepartments }}</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-6">
            {{-- Sidebar Navigation --}}
            <div class="w-64 bg-white rounded-lg shadow">
                <div class="p-4">
                    <h3 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-3">Navigation</h3>
                    <nav class="space-y-1">
                        <button wire:click="setMenuNumber(0)" 
                            class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $menuNumber === 0 ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                            </svg>
                            Dashboard
                        </button>

                        <button wire:click="setMenuNumber(1)" 
                            class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $menuNumber === 1 ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Employees
                        </button>

                        <button wire:click="setMenuNumber(2)" 
                            class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $menuNumber === 2 ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Payroll
                        </button>

                        <button wire:click="setMenuNumber(3)" 
                            class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $menuNumber === 3 ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Leave Management
                        </button>

                        <button wire:click="setMenuNumber(4)" 
                            class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $menuNumber === 4 ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Attendance
                        </button>

                        <button wire:click="setMenuNumber(5)" 
                            class="w-full flex items-center px-3 py-2 text-sm font-medium rounded-md {{ $menuNumber === 5 ? 'bg-blue-600 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            Request Management
                        </button>
                    </nav>
                </div>
            </div>

            {{-- Main Content Area --}}
            <div class="flex-1">
                <div class="bg-white rounded-lg shadow min-h-[500px]">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">
                            @switch($menuNumber)
                                @case(0) Dashboard Overview @break
                                @case(1) Employee Management @break
                                @case(2) Payroll Management @break
                                @case(3) Leave Management @break
                                @case(4) Attendance Tracking @break
                                @case(5) Request Management @break
                                @default Dashboard Overview
                            @endswitch
                        </h2>
                    </div>

                    <div class="p-6">
                        @switch($menuNumber)
                            @case(0)
                                {{-- Dashboard Content --}}
                                <div class="space-y-6">
                                    {{-- Monthly Payroll Summary --}}
                                    <div class="bg-gray-50 rounded-lg p-4">
                                        <h3 class="text-md font-semibold text-gray-700 mb-2">Monthly Payroll</h3>
                                        <p class="text-2xl font-bold text-gray-900">
                                            TZS {{ number_format($monthlyPayrollTotal, 2) }}
                                        </p>
                                        <p class="text-sm text-gray-500 mt-1">
                                            Current month total
                                        </p>
                                    </div>

                                    {{-- Department Statistics --}}
                                    <div>
                                        <h3 class="text-md font-semibold text-gray-700 mb-3">Employees by Department</h3>
                                        <div class="space-y-2">
                                            @forelse($departmentStats as $dept)
                                                <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded">
                                                    <span class="text-sm text-gray-600">{{ $dept['name'] }}</span>
                                                    <span class="text-sm font-medium text-gray-900">{{ $dept['count'] }} employees</span>
                                                </div>
                                            @empty
                                                <p class="text-sm text-gray-500">No departments found</p>
                                            @endforelse
                                        </div>
                                    </div>

                                    {{-- Quick Actions --}}
                                    <div>
                                        <h3 class="text-md font-semibold text-gray-700 mb-3">Quick Actions</h3>
                                        <div class="grid grid-cols-2 gap-3">
                                            <button wire:click="setMenuNumber(1)" 
                                                class="p-3 bg-blue-50 text-blue-700 rounded-lg hover:bg-blue-100 transition text-sm font-medium">
                                                Add New Employee
                                            </button>
                                            <button wire:click="setMenuNumber(2)" 
                                                class="p-3 bg-green-50 text-green-700 rounded-lg hover:bg-green-100 transition text-sm font-medium">
                                                Process Payroll
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @break

                            @case(1)
                                {{-- Employee Management --}}
                                <livewire:h-r.employee-management />
                                @break

                            @case(2)
                                {{-- Payroll Management --}}
                                <livewire:h-r.payroll-management />
                                @break

                            @case(3)
                                {{-- Leave Management --}}
                                <livewire:h-r.leave-management />
                                @break

                            @case(4)
                                {{-- Attendance --}}
                                <livewire:h-r.attendance />
                                @break

                            @case(5)
                                {{-- Request Management --}}
                                <livewire:h-r.request-management />
                                @break

                            @default
                                <div class="text-center py-12">
                                    <p class="text-gray-500">Select an option from the sidebar</p>
                                </div>
                        @endswitch
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>