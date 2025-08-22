<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="p-6">
        <!-- Enhanced Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4">
                    <div class="p-4 bg-gradient-to-br from-blue-900 to-blue-700 rounded-2xl shadow-xl">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-4xl font-bold text-gray-900 tracking-tight">Cash Management System</h1>
                        <p class="text-gray-600 mt-2 text-lg">Comprehensive vault, till, and cash operations management</p>
                    </div>
                </div>
                
                <!-- Enhanced Quick Stats Dashboard -->
                <div class="flex items-center space-x-4">
                    @if($vault)
                        <div class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100 min-w-[160px]">
                            <div class="flex items-center">
                                <div class="p-3 bg-green-100 rounded-xl">
                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                    </svg>
                                </div>
                                <div class="ml-4">
                                    <p class="text-sm font-medium text-gray-500">Vault Balance</p>
                                    <p class="text-xl font-bold {{ $vaultBalance > 1000000 ? 'text-green-600' : 'text-orange-600' }}">
                                        TZS {{ number_format($vaultBalance / 1000000, 1) }}M
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100 min-w-[160px]">
                        <div class="flex items-center">
                            <div class="p-3 bg-blue-100 rounded-xl">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Active Tills</p>
                                <p class="text-xl font-bold text-blue-600">{{ collect($tillSummary)->where('status', 'open')->count() }}/{{ count($availableTills) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-2xl p-5 shadow-lg border border-gray-100 min-w-[160px]">
                        <div class="flex items-center">
                            <div class="p-3 bg-orange-100 rounded-xl">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-4">
                                <p class="text-sm font-medium text-gray-500">Pending Approvals</p>
                                <p class="text-xl font-bold text-orange-600">{{ count($pendingApprovals) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enhanced Vault Alerts Section -->
        @if(count($vaultAlerts) > 0)
            <div class="mb-8">
                <div class="space-y-4">
                    @foreach($vaultAlerts as $alert)
                        <div class="bg-white rounded-2xl shadow-lg border-l-4 {{ $alert['severity'] === 'critical' ? 'border-red-500' : ($alert['severity'] === 'warning' ? 'border-yellow-500' : 'border-blue-500') }} overflow-hidden">
                            <div class="p-6">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        @if($alert['severity'] === 'critical')
                                            <div class="p-3 bg-red-100 rounded-xl">
                                                <svg class="h-6 w-6 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        @elseif($alert['severity'] === 'warning')
                                            <div class="p-3 bg-yellow-100 rounded-xl">
                                                <svg class="h-6 w-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        @else
                                            <div class="p-3 bg-blue-100 rounded-xl">
                                                <svg class="h-6 w-6 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                                </svg>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="ml-6 flex-1">
                                        <h3 class="text-lg font-semibold {{ $alert['severity'] === 'critical' ? 'text-red-900' : ($alert['severity'] === 'warning' ? 'text-yellow-900' : 'text-blue-900') }}">
                                            {{ ucfirst($alert['severity']) }} Alert
                                        </h3>
                                        <p class="mt-1 {{ $alert['severity'] === 'critical' ? 'text-red-700' : ($alert['severity'] === 'warning' ? 'text-yellow-700' : 'text-blue-700') }}">
                                            {{ $alert['message'] }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Main Layout with Enhanced Sidebar -->
        <div class="flex gap-8">
            <!-- Modern Enhanced Sidebar -->
            <div class="w-80 bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden">
                <!-- Navigation Menu -->
                <div class="p-6">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-6 px-2">Cash Operations</h3>
                    
                    @php
                        $cash_sections = [
                            [
                                'id' => 'vault', 
                                'label' => 'Vault Operations', 
                                'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4',
                                'description' => 'Vault management & transfers',
                                'color' => 'blue',
                                'count' => 0
                            ],
                            [
                                'id' => 'teller-operations', 
                                'label' => 'Teller Operations', 
                                'icon' => 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
                                'description' => 'My till operations & transactions',
                                'color' => 'green',
                                'count' => count($myTills)
                            ],
                            [
                                'id' => 'till-assignment', 
                                'label' => 'Till Assignment', 
                                'icon' => 'M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z',
                                'description' => 'Assign tills to tellers',
                                'color' => 'purple',
                                'count' => count($unassignedTills)
                            ],
                            [
                                'id' => 'approvals', 
                                'label' => 'Pending Approvals', 
                                'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
                                'description' => 'Review & approve requests',
                                'color' => 'orange',
                                'count' => count($pendingApprovals)
                            ],
                            [
                                'id' => 'cit', 
                                'label' => 'CIT Operations', 
                                'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4',
                                'description' => 'Cash-in-transit management',
                                'color' => 'indigo',
                                'count' => 0
                            ],
                            [
                                'id' => 'movements', 
                                'label' => 'Cash Movements', 
                                'icon' => 'M13 10V3L4 14h7v7l9-11h-7z',
                                'description' => 'Transaction history & audit',
                                'color' => 'teal',
                                'count' => 0
                            ],
                            [
                                'id' => 'reports', 
                                'label' => 'Reports & Analytics', 
                                'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                                'description' => 'Generate detailed reports',
                                'color' => 'pink',
                                'count' => 0
                            ],
                            [
                                'id' => 'reconciliation', 
                                'label' => 'Reconciliation', 
                                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                                'description' => 'End-of-day reconciliation',
                                'color' => 'gray',
                                'count' => 0
                            ],
                        ];
                        
                        $colorClasses = [
                            'blue' => ['bg' => 'bg-blue-900', 'text' => 'text-blue-600', 'bg-light' => 'bg-blue-50', 'hover' => 'hover:bg-blue-100'],
                            'green' => ['bg' => 'bg-green-900', 'text' => 'text-green-600', 'bg-light' => 'bg-green-50', 'hover' => 'hover:bg-green-100'],
                            'purple' => ['bg' => 'bg-purple-900', 'text' => 'text-purple-600', 'bg-light' => 'bg-purple-50', 'hover' => 'hover:bg-purple-100'],
                            'orange' => ['bg' => 'bg-orange-900', 'text' => 'text-orange-600', 'bg-light' => 'bg-orange-50', 'hover' => 'hover:bg-orange-100'],
                            'indigo' => ['bg' => 'bg-indigo-900', 'text' => 'text-indigo-600', 'bg-light' => 'bg-indigo-50', 'hover' => 'hover:bg-indigo-100'],
                            'teal' => ['bg' => 'bg-teal-900', 'text' => 'text-teal-600', 'bg-light' => 'bg-teal-50', 'hover' => 'hover:bg-teal-100'],
                            'pink' => ['bg' => 'bg-pink-900', 'text' => 'text-pink-600', 'bg-light' => 'bg-pink-50', 'hover' => 'hover:bg-pink-100'],
                            'gray' => ['bg' => 'bg-gray-900', 'text' => 'text-gray-600', 'bg-light' => 'bg-gray-50', 'hover' => 'hover:bg-gray-100'],
                        ];
                    @endphp

                    <nav class="space-y-3">
                        @foreach ($cash_sections as $section)
                            @php
                                $isActive = $this->activeTab === $section['id'];
                                $colors = $colorClasses[$section['color']];
                            @endphp

                            <button
                                wire:click="setActiveTab('{{ $section['id'] }}')"
                                class="relative w-full group transition-all duration-300 transform hover:scale-105"
                                aria-label="{{ $section['label'] }}"
                            >
                                <div class="flex items-center p-4 rounded-2xl transition-all duration-300 border-2
                                    @if ($isActive) 
                                        {{ $colors['bg'] }} text-white shadow-2xl border-transparent 
                                    @else 
                                        {{ $colors['bg-light'] }} {{ $colors['hover'] }} {{ $colors['text'] }} border-transparent hover:border-gray-200 hover:shadow-lg
                                    @endif">
                                    
                                    <!-- Loading State -->
                                    <div wire:loading wire:target="setActiveTab('{{ $section['id'] }}')" class="mr-4">
                                        <svg class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>

                                    <!-- Icon -->
                                    <div wire:loading.remove wire:target="setActiveTab('{{ $section['id'] }}')" class="mr-4">
                                        <div class="p-2 rounded-xl @if ($isActive) bg-white bg-opacity-20 @else {{ $colors['bg-light'] }} @endif">
                                            <svg class="w-6 h-6 @if ($isActive) text-white @else {{ $colors['text'] }} @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $section['icon'] }}"></path>
                                            </svg>
                                        </div>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 text-left">
                                        <div class="font-bold text-base @if ($isActive) text-white @else text-gray-900 @endif">{{ $section['label'] }}</div>
                                        <div class="text-sm @if ($isActive) text-white text-opacity-90 @else text-gray-600 @endif">{{ $section['description'] }}</div>
                                    </div>

                                    <!-- Notification Badge -->
                                    @if ($section['count'] > 0)
                                        <div class="ml-3">
                                            <span class="inline-flex items-center justify-center px-3 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full min-w-[24px] h-6 shadow-lg">
                                                {{ $section['count'] > 99 ? '99+' : $section['count'] }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </button>
                        @endforeach
                    </nav>
                </div>

                <!-- Enhanced Quick Actions -->
                <div class="p-6 border-t border-gray-100 bg-gradient-to-r from-gray-50 to-blue-50">
                    <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider mb-4 px-2">Quick Actions</h3>
                    <div class="space-y-3">
                        <button wire:click="setActiveTab('teller-operations')" class="w-full flex items-center p-3 text-sm text-gray-700 hover:text-gray-900 bg-white hover:bg-blue-50 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md border border-gray-100">
                            <div class="p-2 bg-green-100 rounded-lg mr-3">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                            <span class="font-medium">My Till Operations</span>
                        </button>
                        <button wire:click="setActiveTab('approvals')" class="w-full flex items-center p-3 text-sm text-gray-700 hover:text-gray-900 bg-white hover:bg-orange-50 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md border border-gray-100">
                            <div class="p-2 bg-orange-100 rounded-lg mr-3">
                                <svg class="w-4 h-4 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <span class="font-medium">Process Approvals</span>
                        </button>
                        <button wire:click="setActiveTab('reports')" class="w-full flex items-center p-3 text-sm text-gray-700 hover:text-gray-900 bg-white hover:bg-pink-50 rounded-xl transition-all duration-200 shadow-sm hover:shadow-md border border-gray-100">
                            <div class="p-2 bg-pink-100 rounded-lg mr-3">
                                <svg class="w-4 h-4 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <span class="font-medium">Generate Reports</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Enhanced Main Content Area -->
            <div class="flex-1">
                <div class="bg-white rounded-3xl shadow-xl border border-gray-100 overflow-hidden min-h-[800px]">
                    <!-- Dynamic Content Header -->
                    <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 via-white to-blue-50">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-2xl font-bold text-gray-900">
                                    @switch($this->activeTab)
                                        @case('vault') 🏛️ Vault Operations @break
                                        @case('teller-operations') 👤 Teller Operations @break
                                        @case('till-assignment') 🏪 Till Assignment @break
                                        @case('approvals') ✅ Pending Approvals @break
                                        @case('cit') 🚛 CIT Operations @break
                                        @case('movements') 💸 Cash Movements @break
                                        @case('reports') 📈 Reports & Analytics @break
                                        @case('reconciliation') ⚖️ Reconciliation @break
                                        @default 🏛️ Vault Operations
                                    @endswitch
                                </h2>
                                <p class="text-gray-600 mt-2 text-base">
                                    @switch($this->activeTab)
                                        @case('vault') Monitor vault status, process transfers, and manage cash reserves @break
                                        @case('teller-operations') Manage your assigned tills and process customer transactions @break
                                        @case('till-assignment') Assign tills to tellers and manage till allocation @break
                                        @case('approvals') Review and approve pending cash management requests @break
                                        @case('cit') Schedule and manage cash-in-transit operations @break
                                        @case('movements') View transaction history and audit cash movements @break
                                        @case('reports') Generate comprehensive cash management reports @break
                                        @case('reconciliation') Perform end-of-day reconciliation and balance checks @break
                                        @default Monitor vault status, process transfers, and manage cash reserves
                                    @endswitch
                                </p>
                            </div>
                            
                            <!-- Enhanced Breadcrumb -->
                            <nav class="flex" aria-label="Breadcrumb">
                                <ol class="inline-flex items-center space-x-2 md:space-x-4">
                                    <li class="inline-flex items-center">
                                        <a href="#" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600 transition-colors">
                                            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                            </svg>
                                            Cash Management
                                        </a>
                                    </li>
                                    <li>
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="ml-2 text-sm font-medium text-gray-500">
                                                @switch($this->activeTab)
                                                    @case('vault') Vault @break
                                                    @case('teller-operations') Teller @break
                                                    @case('till-assignment') Assignment @break
                                                    @case('approvals') Approvals @break
                                                    @case('cit') CIT @break
                                                    @case('movements') Movements @break
                                                    @case('reports') Reports @break
                                                    @case('reconciliation') Reconciliation @break
                                                    @default Vault
                                                @endswitch
                                            </span>
                                        </div>
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <!-- Enhanced Main Content with Loading States -->
                    <div class="p-8">
                        <!-- Enhanced Loading State -->
                        <div wire:loading.delay.longer wire:target="setActiveTab" >
                            <div class="text-center min-h-[400px] flex items-center justify-center">
                                <div class="inline-flex items-center px-6 py-4 bg-blue-50 rounded-2xl">
                                    <svg class="w-8 h-8 text-blue-600 animate-spin mr-4" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span class="text-blue-900 font-medium text-lg">Loading cash management data...</span>
                                </div>
                            </div>
                        </div>

                        <!-- Dynamic Content Area -->
                        <div wire:loading.remove wire:target="setActiveTab" >
                            
                            {{-- Enhanced Teller Operations Tab --}}
                            @if($activeTab === 'teller-operations')
                                <div class="space-y-8 min-h-[400px]">
                                    {{-- Enhanced Hero Section --}}
                                    <div class="bg-gradient-to-br from-blue-500 via-blue-600 to-blue-700 rounded-3xl p-8 text-white shadow-2xl">
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <h3 class="text-3xl font-bold mb-2">My Till Operations</h3>
                                                <p class="text-blue-100 text-lg">Manage your assigned tills and daily operations seamlessly</p>
                                            </div>
                                            <div class="text-right">
                                                <div class="bg-white bg-opacity-20 rounded-2xl p-6 backdrop-blur-sm">
                                                    <h4 class="text-lg font-semibold mb-2">Assigned Tills</h4>
                                                    <p class="text-4xl font-bold">{{ count($myTills) }}</p>
                                                    <p class="text-blue-100 text-sm">Active assignments</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Enhanced Stats Grid --}}
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                        <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-2xl p-6 border border-green-200">
                                            <div class="flex items-center">
                                                <div class="p-3 bg-green-500 rounded-xl shadow-lg">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-green-700">Open Tills</p>
                                                    <p class="text-2xl font-bold text-green-900">{{ collect($myTills)->where('status', 'open')->count() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-2xl p-6 border border-blue-200">
                                            <div class="flex items-center">
                                                <div class="p-3 bg-blue-500 rounded-xl shadow-lg">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-blue-700">Total Balance</p>
                                                    <p class="text-2xl font-bold text-blue-900">TZS {{ number_format(collect($myTills)->sum(function($till) { return $this->getTillBalance($till->id); })) }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-2xl p-6 border border-purple-200">
                                            <div class="flex items-center">
                                                <div class="p-3 bg-purple-500 rounded-xl shadow-lg">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-purple-700">Today's Transactions</p>
                                                    <p class="text-2xl font-bold text-purple-900">{{ $todayTransactionCount ?? 0 }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-2xl p-6 border border-orange-200">
                                            <div class="flex items-center">
                                                <div class="p-3 bg-orange-500 rounded-xl shadow-lg">
                                                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-orange-700">Pending Requests</p>
                                                    <p class="text-2xl font-bold text-orange-900">{{ $pendingTellerRequests ?? 0 }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Enhanced Main Operations Grid --}}
                                    <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
                                        {{-- Enhanced Till Selection Card --}}
                                        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                                            <div class="flex items-center mb-6">
                                                <div class="p-3 bg-gradient-to-br from-blue-500 to-blue-600 rounded-2xl shadow-lg">
                                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                </div>
                                                <h3 class="text-xl font-bold text-gray-900 ml-4">Select Till to Operate</h3>
                                            </div>
                                            
                                            <div class="mb-6">
                                                <label class="block text-sm font-semibold text-gray-700 mb-3">My Assigned Tills</label>
                                                <select wire:model="selectedTillId" wire:change="loadTillStatus" class="w-full rounded-2xl border-2 border-gray-200 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-200 transition-all duration-200 p-4 text-lg">
                                                    <option value="">Choose a Till</option>
                                                    @foreach($myTills as $till)
                                                        <option value="{{ $till->id }}">{{ $till->name }} - {{ ucfirst($till->status) }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            @if($selectedTillId)
                                                <div class="bg-gradient-to-br from-gray-50 to-blue-50 rounded-2xl p-6 border border-blue-200">
                                                    <div class="grid grid-cols-2 gap-4 mb-4">
                                                        <div class="text-center">
                                                            <p class="text-sm font-medium text-gray-600 mb-1">Status</p>
                                                            <div class="inline-flex items-center px-3 py-2 rounded-xl font-bold text-sm {{ $tillStatus === 'open' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    @if($tillStatus === 'open')
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                    @else
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                                    @endif
                                                                </svg>
                                                                {{ ucfirst($tillStatus) }}
                                                            </div>
                                                        </div>
                                                        <div class="text-center">
                                                            <p class="text-sm font-medium text-gray-600 mb-1">Balance</p>
                                                            <p class="text-xl font-bold text-blue-900">TZS {{ number_format($tillCurrentBalance) }}</p>
                                                        </div>
                                                    </div>
                                                    
                                                    @if($tillStatus === 'open')
                                                        <div class="bg-green-100 rounded-xl p-4 text-center">
                                                            <div class="flex items-center justify-center text-green-700 text-sm font-medium">
                                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                                Till is ready for operations
                                                            </div>
                                                        </div>
                                                    @else
                                                        <div class="bg-red-100 rounded-xl p-4 text-center">
                                                            <div class="flex items-center justify-center text-red-700 text-sm font-medium">
                                                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                                </svg>
                                                                Till needs to be opened
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Enhanced Till Operations Card --}}
                                        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                                            <div class="flex items-center mb-6">
                                                <div class="p-3 bg-gradient-to-br from-green-500 to-green-600 rounded-2xl shadow-lg">
                                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path>
                                                    </svg>
                                                </div>
                                                <h3 class="text-xl font-bold text-gray-900 ml-4">Till Operations</h3>
                                            </div>
                                            
                                            @if($selectedTillId)
                                                @if($tillStatus !== 'open')
                                                    {{-- Enhanced Till Opening Request --}}
                                                    <div class="bg-gradient-to-br from-green-50 to-emerald-50 border-2 border-green-200 rounded-2xl p-6">
                                                        <h4 class="text-lg font-bold text-green-800 mb-4 flex items-center">
                                                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                                            </svg>
                                                            Request Till Opening
                                                        </h4>
                                                        <div class="space-y-4">
                                                            <div>
                                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Opening Balance (TZS)</label>
                                                                <input type="number" wire:model="tillOpeningBalance" step="1" min="0" 
                                                                       class="w-full rounded-xl border-2 border-green-200 shadow-sm focus:border-green-500 focus:ring-4 focus:ring-green-200 transition-all duration-200 p-4 text-lg" 
                                                                       placeholder="Enter opening balance">
                                                                @error('tillOpeningBalance') <p class="text-red-500 text-sm mt-2 font-medium">{{ $message }}</p> @enderror
                                                            </div>
                                                            
                                                            <button wire:click="requestTillOpening" class="w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-4 px-6 rounded-xl hover:from-green-600 hover:to-green-700 focus:outline-none focus:ring-4 focus:ring-green-200 transition-all duration-200 font-bold text-lg shadow-lg">
                                                                Request Till Opening
                                                            </button>
                                                        </div>
                                                    </div>
                                                @else
                                                    {{-- Enhanced Till Closing (End of Day) --}}
                                                    <div class="bg-gradient-to-br from-red-50 to-pink-50 border-2 border-red-200 rounded-2xl p-6">
                                                        <h4 class="text-lg font-bold text-red-800 mb-4 flex items-center">
                                                            <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                            </svg>
                                                            End of Day Closure
                                                        </h4>
                                                        <div class="space-y-4">
                                                            <div>
                                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Counted Amount (TZS)</label>
                                                                <input type="number" wire:model="eodCountedAmount" step="0.01" min="0" 
                                                                       class="w-full rounded-xl border-2 border-red-200 shadow-sm focus:border-red-500 focus:ring-4 focus:ring-red-200 transition-all duration-200 p-4 text-lg" 
                                                                       placeholder="Actual cash counted">
                                                                @error('eodCountedAmount') <p class="text-red-500 text-sm mt-2 font-medium">{{ $message }}</p> @enderror
                                                            </div>
                                                            
                                                            <div>
                                                                <label class="block text-sm font-semibold text-gray-700 mb-2">Variance Explanation</label>
                                                                <textarea wire:model="eodVarianceExplanation" rows="3" 
                                                                          class="w-full rounded-xl border-2 border-red-200 shadow-sm focus:border-red-500 focus:ring-4 focus:ring-red-200 transition-all duration-200 p-4" 
                                                                          placeholder="Explain any variance (optional)"></textarea>
                                                            </div>
                                                            
                                                            <button wire:click="initiateTillClosure" class="w-full bg-gradient-to-r from-red-500 to-red-600 text-white py-4 px-6 rounded-xl hover:from-red-600 hover:to-red-700 focus:outline-none focus:ring-4 focus:ring-red-200 transition-all duration-200 font-bold text-lg shadow-lg">
                                                                Close Till (End of Day)
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            @else
                                                <div class="text-center py-12">
                                                    <div class="p-6 bg-gray-50 rounded-2xl inline-block">
                                                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                        </svg>
                                                        <p class="text-gray-600 font-medium">Select a till to manage operations</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Enhanced Fund Requests Card --}}
                                        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                                            <div class="flex items-center mb-6">
                                                <div class="p-3 bg-gradient-to-br from-orange-500 to-orange-600 rounded-2xl shadow-lg">
                                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                    </svg>
                                                </div>
                                                <h3 class="text-xl font-bold text-gray-900 ml-4">Request Funds</h3>
                                            </div>
                                            
                                            @if($selectedTillId && $tillStatus === 'open')
                                                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 border-2 border-blue-200 rounded-2xl p-6">
                                                    <h4 class="text-lg font-bold text-blue-800 mb-4 flex items-center">
                                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                        </svg>
                                                        Request Replenishment
                                                    </h4>
                                                    <div class="space-y-4">
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Amount (TZS)</label>
                                                            <input type="number" wire:model="tillReplenishAmount" step="1" min="1" 
                                                                   class="w-full rounded-xl border-2 border-blue-200 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-200 transition-all duration-200 p-4 text-lg" 
                                                                   placeholder="Amount needed">
                                                            @error('tillReplenishAmount') <p class="text-red-500 text-sm mt-2 font-medium">{{ $message }}</p> @enderror
                                                        </div>
                                                        
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Reason</label>
                                                            <textarea wire:model="tillReplenishReason" rows="3" 
                                                                      class="w-full rounded-xl border-2 border-blue-200 shadow-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-200 transition-all duration-200 p-4" 
                                                                      placeholder="Reason for replenishment"></textarea>
                                                            @error('tillReplenishReason') <p class="text-red-500 text-sm mt-2 font-medium">{{ $message }}</p> @enderror
                                                        </div>
                                                        
                                                        <button wire:click="requestTillReplenishment" class="w-full bg-gradient-to-r from-blue-500 to-blue-600 text-white py-4 px-6 rounded-xl hover:from-blue-600 hover:to-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200 transition-all duration-200 font-bold text-lg shadow-lg">
                                                            Request Replenishment
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center py-12">
                                                    <div class="p-6 bg-gray-50 rounded-2xl inline-block">
                                                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                        </svg>
                                                        <p class="text-gray-600 font-medium text-sm">Select an open till to request funds</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>

                                    {{-- Enhanced Operations Grid - Customer Transactions & Transfers --}}
                                    <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                                        {{-- Enhanced Customer Transactions Card --}}
                                        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                                            <div class="flex items-center mb-6">
                                                <div class="p-3 bg-gradient-to-br from-indigo-500 to-indigo-600 rounded-2xl shadow-lg">
                                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                    </svg>
                                                </div>
                                                <h3 class="text-xl font-bold text-gray-900 ml-4">Process Customer Transactions</h3>
                                            </div>
                                            
                                            @if($selectedTillId && $tillStatus === 'open')
                                                <form wire:submit.prevent="processTillTransaction" class="space-y-6">
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 mb-3">Transaction Type</label>
                                                        <select wire:model="transactionType" class="w-full rounded-xl border-2 border-indigo-200 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200 transition-all duration-200 p-4 text-lg">
                                                            <option value="deposit">Customer Deposit</option>
                                                            <option value="withdrawal">Customer Withdrawal</option>
                                                        </select>
                                                    </div>
                                                    
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Amount (TZS)</label>
                                                            <input type="number" wire:model="transactionAmount" step="0.01" min="0.01" 
                                                                   class="w-full rounded-xl border-2 border-indigo-200 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200 transition-all duration-200 p-4" 
                                                                   placeholder="0.00">
                                                            @error('transactionAmount') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                                                        </div>
                                                        
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Reference</label>
                                                            <input type="text" wire:model="transactionReference" 
                                                                   class="w-full rounded-xl border-2 border-indigo-200 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200 transition-all duration-200 p-4" 
                                                                   placeholder="TXN-REF">
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="grid grid-cols-2 gap-4">
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Member Number</label>
                                                            <input type="text" wire:model="memberNumber" 
                                                                   class="w-full rounded-xl border-2 border-indigo-200 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200 transition-all duration-200 p-4" 
                                                                   placeholder="Optional">
                                                        </div>
                                                        
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Account Number</label>
                                                            <input type="text" wire:model="accountNumber" 
                                                                   class="w-full rounded-xl border-2 border-indigo-200 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200 transition-all duration-200 p-4" 
                                                                   placeholder="Optional">
                                                        </div>
                                                    </div>
                                                    
                                                    <div>
                                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Transaction Narration</label>
                                                        <textarea wire:model="transactionNarration" rows="3" 
                                                                  class="w-full rounded-xl border-2 border-indigo-200 shadow-sm focus:border-indigo-500 focus:ring-4 focus:ring-indigo-200 transition-all duration-200 p-4" 
                                                                  placeholder="Describe the transaction"></textarea>
                                                    </div>
                                                    
                                                    <button type="submit" class="w-full bg-gradient-to-r from-indigo-500 to-indigo-600 text-white py-4 px-6 rounded-xl hover:from-indigo-600 hover:to-indigo-700 focus:outline-none focus:ring-4 focus:ring-indigo-200 transition-all duration-200 font-bold text-lg shadow-lg">
                                                        Process {{ ucfirst($transactionType) }}
                                                    </button>
                                                </form>
                                            @else
                                                <div class="text-center py-12">
                                                    <div class="p-6 bg-gray-50 rounded-2xl inline-block">
                                                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                        </svg>
                                                        <p class="text-gray-600 font-medium text-sm">Select an open till to process transactions</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>

                                        {{-- Enhanced Till-Vault Transfers Card --}}
                                        <div class="bg-white rounded-3xl shadow-xl border border-gray-100 p-8">
                                            <div class="flex items-center mb-6">
                                                <div class="p-3 bg-gradient-to-br from-purple-500 to-purple-600 rounded-2xl shadow-lg">
                                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                    </svg>
                                                </div>
                                                <h3 class="text-xl font-bold text-gray-900 ml-4">Transfer to Vault</h3>
                                            </div>
                                            
                                            @if($selectedTillId && $tillStatus === 'open')
                                                <div class="bg-gradient-to-br from-orange-50 to-red-50 border-2 border-orange-200 rounded-2xl p-6">
                                                    <h4 class="text-lg font-bold text-orange-800 mb-4 flex items-center">
                                                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                                        </svg>
                                                        Till → Vault Transfer
                                                    </h4>
                                                    <div class="space-y-4">
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Amount (TZS)</label>
                                                            <input type="number" wire:model="tillToVaultAmount" step="1" min="1" 
                                                                   class="w-full rounded-xl border-2 border-orange-200 shadow-sm focus:border-orange-500 focus:ring-4 focus:ring-orange-200 transition-all duration-200 p-4 text-lg" 
                                                                   placeholder="Amount to transfer">
                                                            @error('tillToVaultAmount') <p class="text-red-500 text-sm mt-2 font-medium">{{ $message }}</p> @enderror
                                                        </div>
                                                        
                                                        <div>
                                                            <label class="block text-sm font-semibold text-gray-700 mb-2">Transfer Notes</label>
                                                            <textarea wire:model="tillToVaultNotes" rows="3" 
                                                                      class="w-full rounded-xl border-2 border-orange-200 shadow-sm focus:border-orange-500 focus:ring-4 focus:ring-orange-200 transition-all duration-200 p-4" 
                                                                      placeholder="Reason for transfer"></textarea>
                                                        </div>
                                                        
                                                        <button wire:click="transferTillToVault" class="w-full bg-gradient-to-r from-orange-500 to-orange-600 text-white py-4 px-6 rounded-xl hover:from-orange-600 hover:to-orange-700 focus:outline-none focus:ring-4 focus:ring-orange-200 transition-all duration-200 font-bold text-lg shadow-lg">
                                                            Transfer to Vault
                                                        </button>
                                                    </div>
                                                </div>
                                            @else
                                                <div class="text-center py-12">
                                                    <div class="p-6 bg-gray-50 rounded-2xl inline-block">
                                                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                                                        </svg>
                                                        <p class="text-gray-600 font-medium text-sm">Select an open till to transfer funds</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                            
                            {{-- Tab Content using Partials --}}
                            @switch($activeTab)
                                @case('vault')
                                    @include('livewire.cash-management.partials.vault-operations')
                                @break
                                
                                @case('till-assignment')
                                    @include('livewire.cash-management.partials.till-assignment')
                                @break
                                
                                @case('approvals')
                                    @include('livewire.cash-management.partials.pending-approvals')
                                @break
                                
                                @case('cit')
                                    @include('livewire.cash-management.partials.cit-operations')
                                @break
                                
                                @case('movements')
                                    @include('livewire.cash-management.partials.cash-movements')
                                @break
                                
                                @case('reports')
                                    @include('livewire.cash-management.partials.reports-analytics')
                                @break
                                
                                @case('reconciliation')
                                    @include('livewire.cash-management.partials.reconciliation')
                                @break
                                
                                @default
                                    {{-- Default fallback content --}}
                                    <div class="text-center py-20">
                                        <div class="p-8 bg-gray-50 rounded-3xl inline-block">
                                            <svg class="w-20 h-20 text-gray-400 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                            </svg>
                                            <h3 class="text-2xl font-bold text-gray-600 mb-3">{{ ucfirst(str_replace('-', ' ', $activeTab)) }}</h3>
                                            <p class="text-gray-500 text-lg">Content for this section is being loaded</p>
                                        </div>
                                    </div>
                            @endswitch
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
