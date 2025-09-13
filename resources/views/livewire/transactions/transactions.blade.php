<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="p-6">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between flex-wrap gap-4">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-blue-900 rounded-xl shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Transactions Management</h1>
                        <p class="text-gray-600 mt-1">Monitor, manage, and analyze all financial transactions</p>
                    </div>
                </div>
                <!-- Quick Stats -->
                <div class="flex flex-wrap items-center space-x-4 gap-2">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 min-w-[150px]">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Transactions</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format($totalTransactions) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 min-w-[150px]">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Value</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format($totalAmount, 2) }} TZS</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 min-w-[150px]">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Pending</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format($pendingCount) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 min-w-[150px]">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Completed</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format($completedCount) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 min-w-[150px]">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Failed</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format($failedCount) }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 min-w-[150px]">
                        <div class="flex items-center">
                            <div class="p-2 bg-gray-100 rounded-lg">
                                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Unreconciled</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format($unreconciledCount) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @if(!($permissions['canView'] ?? false) && !($permissions['canCreate'] ?? false) && !($permissions['canManage'] ?? false) && !($permissions['canExport'] ?? false))
        {{-- No Access Message for users with no permissions --}}
        <div class="bg-white shadow rounded-lg p-8 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
            <p class="text-gray-500">You don't have permission to access the transactions management module.</p>
        </div>
        @else
        <!-- Sidebar and Main Content -->
        <div class="flex gap-6">
            <!-- Sidebar Navigation -->
            <div class="w-80 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden flex-shrink-0">
                <!-- Search Section -->
                <div class="p-6 border-b border-gray-100">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            wire:model.debounce.300ms="search" 
                            placeholder="Search transactions, accounts, or references..."
                            class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white focus:bg-white"
                            aria-label="Search transactions"
                        />
                    </div>
                </div>
                <!-- Navigation Menu -->
                <div class="p-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4 px-2">Navigation</h3>
                    @php
                        $sections = [
                            ['id' => 1, 'label' => 'Dashboard Overview', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'description' => 'Analytics and insights', 'permission' => 'view'],
                            ['id' => 2, 'label' => 'New Transaction', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6', 'description' => 'Create transaction entry', 'permission' => 'create'],
                            ['id' => 3, 'label' => 'Transaction List', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'description' => 'View all transactions', 'permission' => 'view'],
                            ['id' => 4, 'label' => 'Pending', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'description' => 'Awaiting processing', 'permission' => 'view'],
                            ['id' => 5, 'label' => 'Reconciliation', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'description' => 'Reconcile transactions', 'permission' => 'manage'],
                            ['id' => 6, 'label' => 'Reports', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'description' => 'Generate reports', 'permission' => 'view'],
                        ];
                    @endphp
                    <nav class="space-y-2">
                        @foreach ($sections as $section)
                            @php
                                $permissionKey = 'can' . ucfirst($section['permission']);
                                $hasPermission = $permissions[$permissionKey] ?? false;
                            @endphp
                            @if($hasPermission)
                                @php
                                    $isActive = $selectedMenuItem == $section['id'];
                                @endphp
                                <button
                                    wire:click="selectedMenu({{ $section['id'] }})"
                                    class="relative w-full group transition-all duration-200"
                                    aria-label="{{ $section['label'] }}"
                                >
                                    <div class="flex items-center p-3 rounded-xl transition-all duration-200
                                        @if ($isActive) 
                                            bg-blue-900 text-white shadow-lg 
                                        @else 
                                            bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900 
                                        @endif">
                                        <div wire:loading wire:target="selectedMenu({{ $section['id'] }})" class="mr-3">
                                            <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                        <div wire:loading.remove wire:target="selectedMenu({{ $section['id'] }})" class="mr-3">
                                            <svg class="w-5 h-5 @if ($isActive) text-white @else text-gray-500 group-hover:text-gray-700 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $section['icon'] }}"></path>
                                            </svg>
                                        </div>
                                        <div class="flex-1 text-left">
                                            <div class="font-medium text-sm">{{ $section['label'] }}</div>
                                            <div class="text-xs opacity-75">{{ $section['description'] }}</div>
                                        </div>
                                    </div>
                                </button>
                            @endif
                        @endforeach
                    </nav>
                </div>
                <!-- Quick Actions -->
                @if(($permissions['canCreate'] ?? false) || ($permissions['canView'] ?? false))
                <div class="p-4 border-t border-gray-100 bg-gray-50">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 px-2">Quick Actions</h3>
                    <div class="space-y-2">
                        @if($permissions['canCreate'] ?? false)
                        <button wire:click="selectedMenu(2)" class="w-full flex items-center p-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-white rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            New Transaction
                        </button>
                        @endif
                        @if($permissions['canView'] ?? false)
                        <button wire:click="selectedMenu(6)" class="w-full flex items-center p-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-white rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Reports
                        </button>
                        @endif
                    </div>
                </div>
                @endif
            </div>
            <!-- Main Content Area -->
            <div class="flex-1">
                @if($selectedMenuItem == 1 && ($permissions['canView'] ?? false))
                <!-- Dashboard Overview -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                    <!-- Transaction Volume Card -->
                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-blue-900">Transaction Volume</h3>
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-blue-900 mb-2">{{ number_format($totalTransactions) }}</div>
                        <p class="text-sm text-blue-700">Total transactions processed</p>
                    </div>
                    <!-- Transaction Value Card -->
                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-green-900">Transaction Value</h3>
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-green-900 mb-2">{{ number_format($totalAmount, 2) }} TZS</div>
                        <p class="text-sm text-green-700">Total value processed</p>
                    </div>
                    <!-- Success Rate Card -->
                    <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl p-6 border border-purple-200">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-purple-900">Success Rate</h3>
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="text-3xl font-bold text-purple-900 mb-2">
                            @if($totalTransactions > 0)
                                {{ number_format(($completedCount / $totalTransactions) * 100, 1) }}%
                            @else
                                0%
                            @endif
                        </div>
                        <p class="text-sm text-purple-700">Successful transactions</p>
                    </div>
                </div>
                <!-- Recent Transactions Table -->
                <div class="bg-white rounded-xl p-6 border border-gray-200 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Recent Transactions</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach(\App\Models\Transaction::with(['account'])->latest()->take(5)->get() as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->reference }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->account->account_number ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->type }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($transaction->amount, 2) }} TZS</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            @if($transaction->status === 'COMPLETED')
                                                bg-green-100 text-green-800
                                            @elseif($transaction->status === 'FAILED')
                                                bg-red-100 text-red-800
                                            @elseif($transaction->status === 'PENDING')
                                                bg-yellow-100 text-yellow-800
                                            @elseif($transaction->status === 'PROCESSING')
                                                bg-blue-100 text-blue-800
                                            @else
                                                bg-gray-100 text-gray-800
                                            @endif">
                                            {{ $transaction->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <!-- Charts and Analytics -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Transaction Status Chart -->
                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Transaction Status Distribution</h3>
                        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                            <p class="text-gray-500">Chart visualization would go here</p>
                        </div>
                    </div>
                    <!-- Transaction Volume Trend -->
                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Transaction Volume Trend</h3>
                        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                            <p class="text-gray-500">Chart visualization would go here</p>
                        </div>
                    </div>
                </div>
                @elseif($selectedMenuItem == 1)
                    <!-- No Access Message for Dashboard -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                        <p class="text-gray-500">You don't have permission to access the dashboard overview.</p>
                    </div>
                @endif
                @if($selectedMenuItem == 2 && ($permissions['canCreate'] ?? false))
                <!-- New Transaction Form -->
                <div class="bg-white rounded-xl p-6 border border-gray-200 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-6">Create New Transaction</h3>
                    <form wire:submit.prevent="createTransaction" class="space-y-6">
                        <!-- Account Selection -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="account_id" class="block text-sm font-medium text-gray-700 mb-2">Account</label>
                                <select wire:model="newTransaction.account_id" id="account_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Account</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                                @error('newTransaction.account_id') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 mb-2">Transaction Type</label>
                                <select wire:model="newTransaction.type" id="type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Type</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                                @error('newTransaction.type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Amount and Category -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="amount" class="block text-sm font-medium text-gray-700 mb-2">Amount (TZS)</label>
                                <input type="number" wire:model="newTransaction.amount" id="amount" step="0.01" min="0" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="0.00">
                                @error('newTransaction.amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="category" class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                <select wire:model="newTransaction.transaction_category" id="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">Select Category</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                                @error('newTransaction.transaction_category') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- Description and Reference -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea wire:model="newTransaction.description" id="description" rows="3" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Transaction description"></textarea>
                                @error('newTransaction.description') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="reference" class="block text-sm font-medium text-gray-700 mb-2">Reference</label>
                                <input type="text" wire:model="newTransaction.reference" id="reference" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Transaction reference">
                                @error('newTransaction.reference') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <!-- External System Information -->
                        <div class="border-t border-gray-200 pt-6">
                            <h4 class="text-md font-semibold text-gray-900 mb-4">External System Information (Optional)</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="external_system" class="block text-sm font-medium text-gray-700 mb-2">External System</label>
                                    <select wire:model="newTransaction.external_system" id="external_system" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Select System</option>
                                        @foreach($externalSystems as $system)
                                            <option value="{{ $system }}">{{ $system }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="external_reference" class="block text-sm font-medium text-gray-700 mb-2">External Reference</label>
                                    <input type="text" wire:model="newTransaction.external_reference" id="external_reference" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="External reference">
                                </div>
                            </div>
                        </div>

                        <!-- Transaction Options -->
                        <div class="border-t border-gray-200 pt-6">
                            <h4 class="text-md font-semibold text-gray-900 mb-4">Transaction Options</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="newTransaction.is_manual" id="is_manual" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="is_manual" class="ml-2 block text-sm text-gray-900">Manual Transaction</label>
                                </div>
                                <div class="flex items-center">
                                    <input type="checkbox" wire:model="newTransaction.requires_approval" id="requires_approval" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="requires_approval" class="ml-2 block text-sm text-gray-900">Requires Approval</label>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200">
                            <button type="button" wire:click="resetForm" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                Reset
                            </button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <span wire:loading wire:target="createTransaction" class="inline-flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Creating...
                                </span>
                                <span wire:loading.remove wire:target="createTransaction">Create Transaction</span>
                            </button>
                        </div>
                    </form>
                </div>
                @elseif($selectedMenuItem == 2)
                    <!-- No Access Message for New Transaction -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                        <p class="text-gray-500">You don't have permission to create new transactions.</p>
                    </div>
                @endif
                @if($selectedMenuItem == 3 && ($permissions['canView'] ?? false))
                <!-- Transaction List with Advanced Filters -->
                <div class="bg-white rounded-xl p-6 border border-gray-200 mb-8">
                    <!-- Advanced Filters -->
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Advanced Filters</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                            <!-- Status Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                <select wire:model="status" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Statuses</option>
                                    @foreach($statuses as $status)
                                        <option value="{{ $status }}">{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Type Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                                <select wire:model="type" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Types</option>
                                    @foreach($types as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Category Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <select wire:model="category" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Categories</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category }}">{{ $category }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- External System Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">External System</label>
                                <select wire:model="externalSystem" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Systems</option>
                                    @foreach($externalSystems as $system)
                                        <option value="{{ $system }}">{{ $system }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Reconciliation Status Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Reconciliation</label>
                                <select wire:model="reconciliationStatus" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Statuses</option>
                                    @foreach($reconciliationStatuses as $status)
                                        <option value="{{ $status }}">{{ $status }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Account Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Account</label>
                                <select wire:model="accountId" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All Accounts</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}">{{ $account->account_number }} - {{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <!-- Date From Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date From</label>
                                <input type="date" wire:model="dateFrom" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <!-- Date To Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Date To</label>
                                <input type="date" wire:model="dateTo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <!-- Amount From Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amount From</label>
                                <input type="number" wire:model="amountFrom" placeholder="0.00" step="0.01" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <!-- Amount To Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Amount To</label>
                                <input type="number" wire:model="amountTo" placeholder="0.00" step="0.01" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                            <!-- Manual Transaction Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Manual Transactions</label>
                                <select wire:model="isManual" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All</option>
                                    <option value="1">Manual Only</option>
                                    <option value="0">System Only</option>
                                </select>
                            </div>
                            <!-- Requires Approval Filter -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Requires Approval</label>
                                <select wire:model="requiresApproval" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All</option>
                                    <option value="1">Requires Approval</option>
                                    <option value="0">No Approval Required</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Table Controls -->
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-4 gap-4">
                        <div class="flex items-center space-x-4">
                            <label class="text-sm text-gray-500">Per Page:</label>
                            <select wire:model="perPage" class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <div class="flex items-center space-x-2">
                            @if($permissions['canExport'] ?? false)
                            <button wire:click="exportTransactions" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Export
                            </button>
                            @endif
                        </div>
                    </div>
                    <!-- Transactions Table -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('reference')">
                                        Reference
                                        @if($sortField === 'reference')
                                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                                        @endif
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('account_id')">
                                        Account
                                        @if($sortField === 'account_id')
                                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                                        @endif
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('type')">
                                        Type
                                        @if($sortField === 'type')
                                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                                        @endif
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('amount')">
                                        Amount
                                        @if($sortField === 'amount')
                                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                                        @endif
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('status')">
                                        Status
                                        @if($sortField === 'status')
                                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                                        @endif
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer" wire:click="sortBy('created_at')">
                                        Date
                                        @if($sortField === 'created_at')
                                            @if($sortDirection === 'asc') ↑ @else ↓ @endif
                                        @endif
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($transactions as $transaction)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->reference }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->account->account_number ?? '-' }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->type }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($transaction->amount, 2) }} TZS</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                                @if($transaction->status === 'COMPLETED')
                                                    bg-green-100 text-green-800
                                                @elseif($transaction->status === 'FAILED')
                                                    bg-red-100 text-red-800
                                                @elseif($transaction->status === 'PENDING')
                                                    bg-yellow-100 text-yellow-800
                                                @elseif($transaction->status === 'PROCESSING')
                                                    bg-blue-100 text-blue-800
                                                @else
                                                    bg-gray-100 text-gray-800
                                                @endif">
                                                {{ $transaction->status }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <div class="flex space-x-2">
                                                @if($permissions['canView'] ?? false)
                                                <button wire:click="viewTransaction({{ $transaction->id }})" class="text-indigo-600 hover:text-indigo-900">View</button>
                                                @endif
                                                @if(($permissions['canManage'] ?? false) && $transaction->status === 'FAILED' && $transaction->canBeRetried())
                                                    <button wire:click="retryTransaction({{ $transaction->id }})" class="text-yellow-600 hover:text-yellow-900">Retry</button>
                                                @endif
                                                @if(($permissions['canManage'] ?? false) && $transaction->isReversible())
                                                    <button wire:click="reverseTransaction({{ $transaction->id }})" class="text-red-600 hover:text-red-900">Reverse</button>
                                                @endif
                                                @if(($permissions['canManage'] ?? false) && $transaction->reconciliation_status === 'UNRECONCILED')
                                                    <button wire:click="reconcileTransaction({{ $transaction->id }})" class="text-green-600 hover:text-green-900">Reconcile</button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No transactions found</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <!-- Pagination -->
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $transactions->links() }}
                    </div>
                </div>
                @elseif($selectedMenuItem == 3)
                    <!-- No Access Message for Transaction List -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                        <p class="text-gray-500">You don't have permission to view the transaction list.</p>
                    </div>
                @endif
                @if($selectedMenuItem == 4 && ($permissions['canView'] ?? false))
                <!-- Pending Transactions Section -->
                <div class="bg-white rounded-xl p-6 border border-yellow-200 mb-8">
                    <h3 class="text-lg font-semibold text-yellow-900 mb-4">Pending Transactions</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-yellow-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach(\App\Models\Transaction::with(['account'])->where('status','PENDING')->latest()->paginate(10) as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->reference }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->account->account_number ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->type }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($transaction->amount, 2) }} TZS</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-2">
                                            @if($permissions['canView'] ?? false)
                                            <button wire:click="viewTransaction({{ $transaction->id }})" class="text-indigo-600 hover:text-indigo-900">View</button>
                                            @endif
                                            @if(($permissions['canManage'] ?? false) && $transaction->canBeRetried())
                                                <button wire:click="retryTransaction({{ $transaction->id }})" class="text-yellow-600 hover:text-yellow-900">Retry</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @elseif($selectedMenuItem == 4)
                    <!-- No Access Message for Pending Transactions -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                        <p class="text-gray-500">You don't have permission to view pending transactions.</p>
                    </div>
                @endif
                @if($selectedMenuItem == 5 && ($permissions['canManage'] ?? false))
                <!-- Reconciliation Section -->
                <div class="bg-white rounded-xl p-6 border border-gray-200 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Unreconciled Transactions</h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach(\App\Models\Transaction::with(['account'])->where('reconciliation_status','UNRECONCILED')->latest()->paginate(10) as $transaction)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->reference }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->account->account_number ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($transaction->amount, 2) }} TZS</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            {{ $transaction->status }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $transaction->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <div class="flex space-x-2">
                                            @if($permissions['canView'] ?? false)
                                            <button wire:click="viewTransaction({{ $transaction->id }})" class="text-indigo-600 hover:text-indigo-900">View</button>
                                            @endif
                                            @if($permissions['canManage'] ?? false)
                                            <button wire:click="reconcileTransaction({{ $transaction->id }})" class="text-green-600 hover:text-green-900">Reconcile</button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @elseif($selectedMenuItem == 5)
                    <!-- No Access Message for Reconciliation -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                        <p class="text-gray-500">You don't have permission to access transaction reconciliation.</p>
                    </div>
                @endif
                @if($selectedMenuItem == 6 && ($permissions['canView'] ?? false))
                <!-- Reports Section -->
                <div class="bg-white rounded-xl p-6 border border-gray-200 mb-8">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Transaction Reports & Analytics</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                            <p class="text-gray-500">Transaction volume chart would go here</p>
                        </div>
                        <div class="h-64 flex items-center justify-center bg-gray-50 rounded-lg">
                            <p class="text-gray-500">Success rate chart would go here</p>
                        </div>
                    </div>
                </div>
                @elseif($selectedMenuItem == 6)
                    <!-- No Access Message for Reports -->
                    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center">
                        <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Access</h3>
                        <p class="text-gray-500">You don't have permission to access transaction reports.</p>
                    </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Transaction Details Modal -->
@if($showTransactionModal && $selectedTransaction)
    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium">Transaction Details</h3>
                <button wire:click="$set('showTransactionModal', false)" class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <div class="space-y-6">
                <!-- Basic Information -->
                <div>
                    <h4 class="text-md font-semibold text-gray-900 mb-3">Basic Information</h4>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Reference</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->reference }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Transaction UUID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->transaction_uuid }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Account</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->account->account_number ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Type</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->type }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Category</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->transaction_category ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Amount</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($selectedTransaction->amount, 2) }} TZS</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Status</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    @if($selectedTransaction->status === 'COMPLETED')
                                        bg-green-100 text-green-800
                                    @elseif($selectedTransaction->status === 'FAILED')
                                        bg-red-100 text-red-800
                                    @elseif($selectedTransaction->status === 'PENDING')
                                        bg-yellow-100 text-yellow-800
                                    @else
                                        bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $selectedTransaction->status }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Reconciliation Status</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->reconciliation_status }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Timing Information -->
                <div>
                    <h4 class="text-md font-semibold text-gray-900 mb-3">Timing Information</h4>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Created At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->created_at->format('Y-m-d H:i:s') }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Initiated At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->initiated_at ? $selectedTransaction->initiated_at->format('Y-m-d H:i:s') : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Processed At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->processed_at ? $selectedTransaction->processed_at->format('Y-m-d H:i:s') : '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Completed At</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->completed_at ? $selectedTransaction->completed_at->format('Y-m-d H:i:s') : '-' }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- External System Information -->
                @if($selectedTransaction->external_system)
                <div>
                    <h4 class="text-md font-semibold text-gray-900 mb-3">External System Information</h4>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">External System</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->external_system }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">External Reference</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->external_reference ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">External Transaction ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->external_transaction_id ?? '-' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">External Status</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->external_status_code ?? '-' }}</dd>
                        </div>
                    </dl>
                </div>
                @endif

                <!-- Error Information -->
                @if($selectedTransaction->error_message)
                <div>
                    <h4 class="text-md font-semibold text-gray-900 mb-3">Error Information</h4>
                    <dl class="grid grid-cols-1 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Error Message</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->error_message }}</dd>
                        </div>
                        @if($selectedTransaction->error_code)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Error Code</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->error_code }}</dd>
                        </div>
                        @endif
                        @if($selectedTransaction->failure_reason)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Failure Reason</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $selectedTransaction->failure_reason }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>
                @endif

                <!-- Audit Logs -->
                @if($selectedTransaction->auditLogs->count() > 0)
                <div>
                    <h4 class="text-md font-semibold text-gray-900 mb-3">Audit Logs</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Action</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($selectedTransaction->auditLogs as $log)
                                    <tr>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $log->action }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $log->description }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
@endif
