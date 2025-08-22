<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="p-6">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-blue-900 rounded-xl shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2a2 2 0 00-2 2v7a2 2 0 002 2h10a2 2 0 002-2v-7a2 2 0 00-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Payments Manager</h1>
                        <p class="text-gray-600 mt-1">Manage, track, and analyze all payment activities</p>
                    </div>
                </div>
                <!-- Quick Stats -->
                <div class="flex items-center space-x-4">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Paid</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format(DB::table('payments')->where('status', 'Confirmed')->sum('amount'), 2) }} TZS</p>
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
                                <p class="text-lg font-semibold text-gray-900">{{ DB::table('payments')->where('status', 'Pending')->count() }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Channels</p>
                                <p class="text-lg font-semibold text-gray-900">{{ DB::table('payments')->distinct('payment_channel')->count('payment_channel') }}</p>
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
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            wire:model.debounce.300ms="search" 
                            placeholder="Search payments, billers, or accounts..."
                            class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white focus:bg-white"
                            aria-label="Search payments"
                        />
                    </div>
                </div>
                <div class="p-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4 px-2">Navigation</h3>
                    @php
                        $sections = [
                            ['id' => 1, 'label' => 'Dashboard', 'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z', 'description' => 'Overview and analytics'],
                            ['id' => 2, 'label' => 'New Payment', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6', 'description' => 'Initiate new payment'],
                            ['id' => 3, 'label' => 'Payment List', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'description' => 'View and manage all payment records'],
                            ['id' => 4, 'label' => 'Pending', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', 'description' => 'Pending payments'],
                            ['id' => 5, 'label' => 'Channels', 'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z', 'description' => 'Payment channels'],
                            ['id' => 6, 'label' => 'Reports', 'icon' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'description' => 'Generate reports'],
                        ];
                        $selectedMenuItem = $selectedMenuItem ?? 1;
                    @endphp
                    <nav class="space-y-2">
                        @foreach ($sections as $section)
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
                        @endforeach
                    </nav>
                </div>
            </div>
            <!-- Main Content Area -->
            <div class="flex-1">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">
                                    @switch($selectedMenuItem)
                                        @case(1) Dashboard @break
                                        @case(2) New Payment @break
                                        @case(3) Payment List @break
                                        @case(4) Pending @break
                                        @case(5) Channels @break
                                        @case(6) Reports @break
                                        @default Dashboard
                                    @endswitch
                                </h2>
                                <p class="text-gray-600 mt-1">
                                    @switch($selectedMenuItem)
                                        @case(1) Overview of payment performance and key metrics @break
                                        @case(2) Initiate and process new payments @break
                                        @case(3) View and manage all payment records @break
                                        @case(4) Review and process pending payments @break
                                        @case(5) Manage payment channels and billers @break
                                        @case(6) Generate detailed payment reports and analytics @break
                                        @default Overview of payment performance and key metrics
                                    @endswitch
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="p-8">
                        {{-- Dynamic Content Based on Selected Menu Item --}}
                        @switch($selectedMenuItem)
                            @case(1)
                                {{-- Dashboard Content --}}
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Welcome to Payments Manager</h3>
                                    <p class="text-gray-600">Choose a payment type to get started</p>
                                </div>
                                @break

                            @case(2)
                                {{-- New Payment - Payment Type Navigation --}}
                                @php
                                    $paymentTypes = [
                                        ['id' => 'money_transfer', 'label' => 'Money Transfer', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4', 'description' => 'Bank-to-Bank & Bank-to-Wallet transfers'],
                                        ['id' => 'bill_payment', 'label' => 'Bill Payment', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'description' => 'Pay bills from various service providers'],
                                        ['id' => 'luku_payment', 'label' => 'LUKU Payment', 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z', 'description' => 'Electricity token purchase'],
                                        ['id' => 'gepg_payment', 'label' => 'GEPG Payment', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4', 'description' => 'Government bills and services'],
                                        ['id' => 'tips_lookup', 'label' => 'TIPS Lookup', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z', 'description' => 'Account verification and lookup services']
                                    ];
                                    $selectedPaymentType = $selectedPaymentType ?? 'money_transfer';
                                @endphp

                                {{-- Payment Type Tabs --}}
                                <div class="mb-8">
                                    <div class="border-b border-gray-200">
                                        <nav class="-mb-px flex space-x-8" aria-label="Payment Types">
                                            @foreach($paymentTypes as $type)
                                                @php
                                                    $isActiveType = $selectedPaymentType === $type['id'];
                                                @endphp
                                                <button
                                                    wire:click="$set('selectedPaymentType', '{{ $type['id'] }}')"
                                                    class="@if($isActiveType) border-blue-500 text-blue-600 @else border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 @endif whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200"
                                                    aria-current="@if($isActiveType) page @endif"
                                                >
                                                    <div class="flex items-center space-x-2">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $type['icon'] }}"></path>
                                                        </svg>
                                                        <span>{{ $type['label'] }}</span>
                                                    </div>
                                                </button>
                                            @endforeach
                                        </nav>
                                    </div>

                                    {{-- Payment Type Description --}}
                                    @foreach($paymentTypes as $type)
                                        @if($selectedPaymentType === $type['id'])
                                            <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                                <p class="text-blue-800 text-sm font-medium">{{ $type['description'] }}</p>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>

                                {{-- Payment Content Area --}}
                                <div class="bg-white rounded-lg border border-gray-200 overflow-hidden">
                                    @switch($selectedPaymentType)
                                        @case('money_transfer')
                                            <livewire:payments.money-transfer />
                                            @break

                                        @case('bill_payment')
                                            {{-- PLACEHOLDER: Bill Payment Workflow --}}
                                            <div class="p-8 text-center">
                                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <h3 class="text-lg font-medium text-gray-900 mb-2">Bill Payment</h3>
                                                <p class="text-gray-600">Two-step workflow implementation coming next...</p>
                                            </div>
                                            @break

                                        @case('luku_payment')
                                            <livewire:payments.luku-payment />
                                            @break

                                        @case('gepg_payment')
                                            <livewire:payments.gepg-payment />
                                            @break

                                        @case('tips_lookup')
                                            {{-- PLACEHOLDER: TIPS lookup --}}
                                            <div class="p-8 text-center">
                                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                                </svg>
                                                <h3 class="text-lg font-medium text-gray-900 mb-2">TIPS Lookup</h3>
                                                <p class="text-gray-600">Account verification services coming next...</p>
                                            </div>
                                            @break

                                        @default
                                            <div class="p-8 text-center">
                                                <h3 class="text-lg font-medium text-gray-900 mb-2">Select Payment Type</h3>
                                                <p class="text-gray-600">Choose a payment type from the tabs above to get started</p>
                                            </div>
                                    @endswitch
                                </div>
                                @break

                            @case(3)
                                {{-- Payment List Content --}}
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Payment List</h3>
                                    <p class="text-gray-600">Payment records will be displayed here</p>
                                </div>
                                @break

                            @case(4)
                                {{-- Pending Payments Content --}}
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Pending Payments</h3>
                                    <p class="text-gray-600">Pending payment reviews will be displayed here</p>
                                </div>
                                @break

                            @case(5)
                                {{-- Payment Channels Content --}}
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Payment Channels</h3>
                                    <p class="text-gray-600">Channel management will be displayed here</p>
                                </div>
                                @break

                            @case(6)
                                {{-- Reports Content --}}
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Payment Reports</h3>
                                    <p class="text-gray-600">Payment analytics and reports will be displayed here</p>
                                </div>
                                @break

                            @default
                                {{-- Default Dashboard Content --}}
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Welcome to Payments Manager</h3>
                                    <p class="text-gray-600">Select a section from the sidebar to get started</p>
                                </div>
                        @endswitch
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 