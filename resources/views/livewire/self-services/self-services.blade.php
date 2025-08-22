<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="p-6">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-blue-900 rounded-xl shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Employee Self-Services</h1>
                        <p class="text-gray-600 mt-1">Request HR and admin services from any department</p>
                    </div>
                </div>
                <!-- Quick Stats (optional) -->
                <div class="flex items-center space-x-4">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">My Requests</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $myRequestsCount ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $pendingRequestsCount ?? 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-6">
            <!-- Sidebar Navigation -->
            <div class="w-80 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <div class="relative">
                        <input type="text" placeholder="Search services..." class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white focus:bg-white" aria-label="Search services" />
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                            </svg>
                        </div>
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4 px-2">Self-Service Menu</h3>
                    <nav class="space-y-2">
                        <button wire:click="$set('selectedMenu', 'dashboard')" class="relative w-full group transition-all duration-200">
                            <div class="flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenu === 'dashboard' ? 'bg-blue-900 text-white shadow-lg' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" /></svg>
                                <span>Dashboard</span>
                            </div>
                        </button>
                        <button wire:click="$set('selectedMenu', 'leave')" class="relative w-full group transition-all duration-200">
                            <div class="flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenu === 'leave' ? 'bg-blue-900 text-white shadow-lg' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                <span>Leave Request</span>
                            </div>
                        </button>
                        <button wire:click="$set('selectedMenu', 'materials')" class="relative w-full group transition-all duration-200">
                            <div class="flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenu === 'materials' ? 'bg-blue-900 text-white shadow-lg' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span>Working Materials</span>
                            </div>
                        </button>
                        <button wire:click="$set('selectedMenu', 'resignation')" class="relative w-full group transition-all duration-200">
                            <div class="flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenu === 'resignation' ? 'bg-blue-900 text-white shadow-lg' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7" /></svg>
                                <span>Resignation</span>
                            </div>
                        </button>
                        <button wire:click="$set('selectedMenu', 'travel')" class="relative w-full group transition-all duration-200">
                            <div class="flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenu === 'travel' ? 'bg-blue-900 text-white shadow-lg' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2v-7a2 2 0 00-2-2z" /></svg>
                                <span>Travel/Advance</span>
                            </div>
                        </button>
                        <button wire:click="$set('selectedMenu', 'training')" class="relative w-full group transition-all duration-200">
                            <div class="flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenu === 'training' ? 'bg-blue-900 text-white shadow-lg' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0v6m0 0H3m9 0h9" /></svg>
                                <span>Training/Workshop</span>
                            </div>
                        </button>
                        <button wire:click="$set('selectedMenu', 'overtime')" class="relative w-full group transition-all duration-200">
                            <div class="flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenu === 'overtime' ? 'bg-blue-900 text-white shadow-lg' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span>Overtime Request</span>
                            </div>
                        </button>
                        <button wire:click="$set('selectedMenu', 'payslip')" class="relative w-full group transition-all duration-200">
                            <div class="flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenu === 'payslip' ? 'bg-blue-900 text-white shadow-lg' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                                <span>Payslip/HR Docs</span>
                            </div>
                        </button>
                        <button wire:click="$set('selectedMenu', 'general')" class="relative w-full group transition-all duration-200">
                            <div class="flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenu === 'general' ? 'bg-blue-900 text-white shadow-lg' : 'bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900' }}">
                                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" /></svg>
                                <span>General Request</span>
                            </div>
                        </button>
                    </nav>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="flex-1">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white flex items-center justify-between">
<div>
                            <h2 class="text-xl font-semibold text-gray-900">
                                @switch($selectedMenu)
                                    @case('dashboard') Dashboard @break
                                    @case('leave') Leave Request @break
                                    @case('materials') Working Materials @break
                                    @case('resignation') Resignation @break
                                    @case('travel') Travel/Advance @break
                                    @case('training') Training/Workshop @break
                                    @case('overtime') Overtime Request @break
                                    @case('payslip') Payslip/HR Docs @break
                                    @case('general') General Request @break
                                    @default Dashboard
                                @endswitch
                            </h2>
                            <p class="text-gray-600 mt-1">
                                @switch($selectedMenu)
                                    @case('dashboard') Overview of your requests and status @break
                                    @case('leave') Request for annual, sick, or emergency leave @break
                                    @case('materials') Request for work materials or equipment @break
                                    @case('resignation') Submit resignation or exit request @break
                                    @case('travel') Request travel or advance funds @break
                                    @case('training') Request for training or workshop attendance @break
                                    @case('overtime') Request approval for overtime/extra hours @break
                                    @case('payslip') Request payslip or HR documents @break
                                    @case('general') Send a general request to any department @break
                                    @default Overview of your requests and status
                                @endswitch
                            </p>
                        </div>
                    </div>
                    <div class="p-8 min-h-[400px]">
                        @switch($selectedMenu)
                            @case('dashboard')
                                {{-- Dashboard summary: show recent requests, status, etc. --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                                        <h3 class="text-lg font-semibold text-blue-900 mb-2">Total Requests</h3>
                                        <div class="text-3xl font-bold text-blue-900">{{ $myRequestsCount ?? 0 }}</div>
                                    </div>
                                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-6 border border-yellow-200">
                                        <h3 class="text-lg font-semibold text-yellow-900 mb-2">Pending</h3>
                                        <div class="text-3xl font-bold text-yellow-900">{{ $pendingRequestsCount ?? 0 }}</div>
                                    </div>
                                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
                                        <h3 class="text-lg font-semibold text-green-900 mb-2">Approved</h3>
                                        <div class="text-3xl font-bold text-green-900">{{ $approvedRequestsCount ?? 0 }}</div>
                                    </div>
                                </div>
                                {{-- Recent requests table (example) --}}
                                <div class="bg-white rounded-xl p-6 border border-gray-200">
                                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Requests</h3>
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Action</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @forelse($recentRequests ?? [] as $req)
                                                    <tr>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $req['date'] }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $req['type'] }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <span class="px-2 py-1 text-xs font-medium rounded-full
                                                                @if($req['status'] == 'APPROVED') bg-green-100 text-green-800
                                                                @elseif($req['status'] == 'PENDING') bg-yellow-100 text-yellow-800
                                                                @else bg-gray-100 text-gray-800 @endif">
                                                                {{ $req['status'] }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <button class="text-blue-600 hover:underline text-xs">View</button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center text-gray-500 py-8">No recent requests</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                @break
                            @case('leave')
                                {{-- Leave Request Form --}}
                                <livewire:self-services.leave-request />
                                @break
                            @case('materials')
                                {{-- Working Materials Request Form --}}
                                <livewire:self-services.materials-request />
                                @break
                            @case('resignation')
                                {{-- Resignation Request Form --}}
                                <livewire:self-services.resignation-request />
                                @break
                            @case('travel')
                                {{-- Travel/Advance Request Form --}}
                                <livewire:self-services.travel-request />
                                @break
                            @case('training')
                                {{-- Training/Workshop Request Form --}}
                                <livewire:self-services.training-request />
                                @break
                            @case('overtime')
                                {{-- Overtime Request Form --}}
                                <livewire:self-services.overtime-request />
                                @break
                            @case('payslip')
                                {{-- Payslip/HR Docs Request Form --}}
                                <livewire:self-services.payslip-request />
                                @break
                            @case('general')
                                {{-- General Request Form --}}
                                <livewire:self-services.general-request />
                                @break
                            @default
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Welcome to Self-Services</h3>
                                    <p class="text-gray-600">Select a service from the sidebar to get started</p>
                                </div>
                        @endswitch
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
