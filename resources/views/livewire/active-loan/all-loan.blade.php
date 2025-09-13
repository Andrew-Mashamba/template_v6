{{-- Active Loans Management View --}}
<div class="container-fluid">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="fixed bottom-4 right-4 z-50" x-data="{ show: true }" x-show="show" x-transition>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" @click="show = false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </span>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed bottom-4 right-4 z-50" x-data="{ show: true }" x-show="show" x-transition>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" @click="show = false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </span>
            </div>
        </div>
    @endif

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #2563eb;
        }
    </style>

    <div class="min-h-screen bg-gray-50 p-6">
        <!-- Dashboard Header -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Active Loans Management</h1>
                <p class="text-gray-600 mt-1">Comprehensive loan portfolio management and monitoring</p>
            </div>
            <div class="flex gap-3 w-full sm:w-auto">
                <button wire:click="refreshData" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <!-- Total Active Loans -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Active Loans</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">{{ App\Models\LoansModel::where('status', 'ACTIVE')->count() }}</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-green-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <span class="flex items-center text-green-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-1">Performing well</span>
                    </span>
                </div>
            </div>

            <!-- Portfolio Value -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Portfolio Value</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">TZS {{ number_format(App\Models\LoansModel::where('status', 'ACTIVE')->sum('principle'), 2) }}</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-blue-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <span>Total outstanding principal</span>
                </div>
            </div>

            <!-- Loans in Arrears -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">In Arrears</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">{{ App\Models\LoansModel::whereIn('status', ['IN_ARREAR', 'DELINQUENT'])->count() }}</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-red-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <span class="flex items-center text-red-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                        <span class="ml-1">Requires attention</span>
                    </span>
                </div>
            </div>

            <!-- Recovery Rate -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Recovery Rate</p>
                        <p class="mt-1 text-3xl font-semibold text-gray-900">95.2%</p>
                    </div>
                    <div class="h-12 w-12 rounded-full bg-purple-50 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <span>Monthly collection efficiency</span>
                </div>
            </div>
        </div>

        @if(!($permissions['canView'] ?? false) && !($permissions['canCreate'] ?? false) && !($permissions['canManage'] ?? false))
        {{-- No Access Message for users with no permissions --}}
        <div class="bg-white shadow rounded-lg p-8 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
            <p class="text-gray-500">You don't have permission to access the active loan management module.</p>
        </div>
        @else
        <!-- Main Content -->
        <div class="flex flex-col lg:flex-row gap-6 w-full">
            <!-- Sidebar Navigation -->
            <div class="w-full max-w-xs shrink-0">
                <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900 mb-4">Loan Management</h2>
                    <nav class="space-y-2">
                        <!-- 1. Loans > Loan Accounts -->
                        @if($permissions['canView'] ?? false)
                        <button wire:click="setView(1)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 1 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                            Loan Accounts
                        </button>
                        @endif

                        <!-- 2. Payments > Record Payment -->
                        @if($permissions['canCreate'] ?? false)
                        <button wire:click="setView(2)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 2 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Record Payment
                        </button>
                        @endif

                        <!-- 3. Arrears Management > Arrears Dashboard -->
                        @if($permissions['canView'] ?? false)
                        <button wire:click="setView(3)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 3 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            Arrears Dashboard
                        </button>
                        @endif

                        <!-- 4. Risk & Portfolio > Portfolio Quality Metrics -->
                        @if($permissions['canView'] ?? false)
                        <button wire:click="setView(4)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 4 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Portfolio Quality
                        </button>
                        @endif

                        <!-- 5. Collections & Recovery > Collection Dashboard -->
                        @if($permissions['canView'] ?? false)
                        <button wire:click="setView(5)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 5 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            Collection Dashboard
                        </button>
                        @endif

                        <!-- 6. Collateral > Collateral Register -->
                        @if($permissions['canView'] ?? false)
                        <button wire:click="setView(6)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 6 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Collateral Register
                        </button>
                        @endif

                        <!-- 7. Provisions > Loss Provisions -->
                        @if($permissions['canView'] ?? false)
                        <button wire:click="setView(7)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 7 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                            </svg>
                            Loss Provisions
                        </button>
                        @endif

                        <!-- 8. Write-offs & Recovery -->
                        @if($permissions['canManage'] ?? false)
                        <button wire:click="setView(8)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 8 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                            Write-offs
                        </button>
                        @endif

                        <!-- 9. Restructuring & Rescheduling -->
                        @if($permissions['canManage'] ?? false)
                        <button wire:click="setView(9)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 9 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                            </svg>
                            Restructuring
                        </button>
                        @endif

                        <!-- 10. Early Settlement -->
                        @if($permissions['canManage'] ?? false)
                        <button wire:click="setView(10)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 10 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Early Settlement
                        </button>
                        @endif

                        <!-- 11. Guarantors -->
                        @if($permissions['canView'] ?? false)
                        <button wire:click="setView(11)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 11 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Guarantors
                        </button>
                        @endif

                        <!-- 12. Insurance -->
                        @if($permissions['canView'] ?? false)
                        <button wire:click="setView(12)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 12 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                            Loan Insurance
                        </button>
                        @endif

                        <!-- 13. Legal Actions -->
                        @if($permissions['canManage'] ?? false)
                        <button wire:click="setView(13)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 13 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3" />
                            </svg>
                            Legal Actions
                        </button>
                        @endif

                        <!-- 14. Performance Analytics -->
                        @if($permissions['canView'] ?? false)
                        <button wire:click="setView(14)" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium {{ $this->tab_id == 14 ? 'text-blue-900 bg-indigo-50' : 'text-gray-700 bg-gray-50 hover:bg-indigo-50 hover:text-blue-900' }} rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                            Analytics
                        </button>
                        @endif
                    </nav>
                </div>
            </div>

            <!-- Main Content Area -->
            <div class="flex-1 w-3/4">
       
                <!-- Content Based on Selected Tab -->
                <div wire:loading.remove wire:target="setView">
                    @switch($this->tab_id)
                        @case(1)
                            <!-- 1. Loans > Loan Accounts -->
                            @if($permissions['canView'] ?? false)
                                <livewire:active-loan.all-table />
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access loan accounts.</p>
                                </div>
                            @endif
                            @break

                        @case(2)
                            <!-- 2. Payments > Record Payment -->
                            @if($permissions['canCreate'] ?? false)
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Record Payment</h3>
                                    <p class="text-gray-500">Payment processing functionality will be implemented here</p>
                                </div>
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to record payments.</p>
                                </div>
                            @endif
                            @break

                        @case(3)
                            <!-- 3. Arrears Management > Arrears Dashboard -->
                            @if($permissions['canView'] ?? false)
                                <livewire:active-loan.arrears />
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access arrears dashboard.</p>
                                </div>
                            @endif
                            @break

                        @case(4)
                            <!-- 4. Risk & Portfolio > Portfolio Quality Metrics -->
                            @if($permissions['canView'] ?? false)
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Portfolio Quality Metrics</h3>
                                    <p class="text-gray-500">Portfolio analysis and risk metrics will be displayed here</p>
                                </div>
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access portfolio quality metrics.</p>
                                </div>
                            @endif
                            @break

                        @case(5)
                            <!-- 5. Collections & Recovery > Collection Dashboard -->
                            @if($permissions['canView'] ?? false)
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Collection Dashboard</h3>
                                    <p class="text-gray-500">Collection activities and recovery processes will be managed here</p>
                                </div>
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access collection dashboard.</p>
                                </div>
                            @endif
                            @break

                        @case(6)
                            <!-- 6. Collateral > Collateral Register -->
                            @if($permissions['canView'] ?? false)
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Collateral Register</h3>
                                    <p class="text-gray-500">Collateral management and security tracking will be implemented here</p>
                                </div>
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access collateral register.</p>
                                </div>
                            @endif
                            @break

                        @case(7)
                            <!-- 7. Provisions > Loss Provisions -->
                            @if($permissions['canView'] ?? false)
                                <livewire:active-loan.provisions />
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access loss provisions.</p>
                                </div>
                            @endif
                            @break

                        @case(8)
                            <!-- 8. Write-offs & Recovery -->
                            @if($permissions['canManage'] ?? false)
                                <livewire:active-loan.write-offs />
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access write-offs management.</p>
                                </div>
                            @endif
                            @break

                        @case(9)
                            <!-- 9. Restructuring & Rescheduling -->
                            @if($permissions['canManage'] ?? false)
                                <livewire:active-loan.restructuring />
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access loan restructuring.</p>
                                </div>
                            @endif
                            @break

                        @case(10)
                            <!-- 10. Early Settlement -->
                            @if($permissions['canManage'] ?? false)
                                <livewire:active-loan.early-settlement />
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access early settlement.</p>
                                </div>
                            @endif
                            @break

                        @case(11)
                            <!-- 11. Guarantors -->
                            @if($permissions['canView'] ?? false)
                                <livewire:active-loan.guarantors />
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access guarantors.</p>
                                </div>
                            @endif
                            @break

                        @case(12)
                            <!-- 12. Insurance -->
                            @if($permissions['canView'] ?? false)
                                <livewire:active-loan.insurance />
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access loan insurance.</p>
                                </div>
                            @endif
                            @break

                        @case(13)
                            <!-- 13. Legal Actions -->
                            @if($permissions['canManage'] ?? false)
                                <livewire:active-loan.legal-actions />
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access legal actions.</p>
                                </div>
                            @endif
                            @break

                        @case(14)
                            <!-- 14. Performance Analytics -->
                            @if($permissions['canView'] ?? false)
                                <livewire:active-loan.analytics />
                            @else
                                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                    <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                                        <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                                    <p class="text-gray-500">You don't have permission to access performance analytics.</p>
                                </div>
                            @endif
                            @break

                        @default
                            <!-- Default View -->
                            <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 text-gray-400 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Select a Management Option</h3>
                                <p class="text-gray-500">Choose from the sidebar to manage different aspects of loan operations</p>
                            </div>
                    @endswitch
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
