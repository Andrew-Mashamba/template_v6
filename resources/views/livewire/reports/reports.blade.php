{{-- Enhanced Reports Manager with Modern UI --}}
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="p-6">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-blue-900 rounded-xl shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Reports Management</h1>
                        <p class="text-gray-600 mt-1">Generate, analyze, and export comprehensive reports</p>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Transactions</p>
                                <p class="text-lg font-semibold text-gray-900">{{number_format(DB::table('general_ledger')->sum('debit'))}} TZS</p>
                                <div class="text-xs text-gray-400">({{DB::table('general_ledger')->count()}} records)</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Clients</p>
                                <p class="text-lg font-semibold text-gray-900">{{ App\Models\ClientsModel::count() }}</p>
                                <div class="text-xs text-green-600">Active accounts</div>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Available Reports</p>
                                <p class="text-lg font-semibold text-gray-900">{{ count($menuItems ?? []) }}</p>
                                <div class="text-xs text-yellow-600">Ready to generate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-6">
            <!-- Enhanced Sidebar -->
            <div class="w-80 shrink-0 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden" style="max-height: calc(100vh - 200px);">
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
                            placeholder="Search reports..."
                            class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white focus:bg-white"
                            aria-label="Search reports" />
                    </div>
                </div>

                <!-- Reports Navigation -->
                <div class="p-4 overflow-y-auto" style="max-height: calc(100vh - 400px); scrollbar-width: none; -ms-overflow-style: none;">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4 px-2">Report Categories</h3>

                    @php
                    $menuItems = [
                    //regulatory reports
                    ['id' => 37, 'label' => 'Statement of Financial Position'],
                    ['id' => 38, 'label' => 'Statement of Comprehensive Income'],
                    ['id' => 39, 'label' => 'Statement of Cash Flow'],
                    ['id' => 40, 'label' => 'Sectoral Classification of Loans'],
                    ['id' => 41, 'label' => 'Interest Rates Structure for Loans'],
                    ['id' => 42, 'label' => 'Loans Disbursed for the Month Ended'],
                    ['id' => 43, 'label' => 'Loans to Insiders and Related Parties'],
                    ['id' => 44, 'label' => 'Geographical Distribution of Branches, Employees and Loans by Age for the Month Ended'],
                    ['id' => 45, 'label' => 'Computation of Liquid Assets '],
                    ['id' => 46, 'label' => 'Deposits and Loans in Banks and Financial Institutions'],
                    ['id' => 47, 'label' => 'Complaint Report for the Month Ended'],
                    ['id' => 48, 'label' => 'Computation of Capital Adequacy for the Month Ended'],

                    //general reports
                    //['id' => 1, 'label' => 'Daily Report'],
                    ['id' => 2, 'label' => 'Members Details Report'],
                    //['id' => 3, 'label' => 'Member Loan Accounts'],
                    //['id' => 4, 'label' => 'Member Repayment History'],
                    ['id' => 5, 'label' => 'Loan General Report'],
                    //['id' => 6, 'label' => 'Loan Application Report'],
                    //['id' => 7, 'label' => 'Loan Delinquency Report'],
                    ['id' => 9, 'label' => 'Financial Ratios and Metrics'],
                    //['id' => 10, 'label' => 'Loan Approval and Rejection Report'],
                    //['id' => 11, 'label' => 'Loan Committee Meeting Minutes'],
                    ['id' => 13, 'label' => 'Compliance Report'],
                   // ['id' => 14, 'label' => 'Loan Repayment Schedules'],
                    //['id' => 15, 'label' => 'Loan Disbursement Report'],
                    //['id' => 20, 'label' => 'Active Loans By Loan Officer'],
                    //['id' => 23, 'label' => 'Portfolio At Risk Report'],
                  //  ['id' => 24, 'label' => 'Loan Arrears Report'],
                   // ['id' => 25, 'label' => 'Loan Arrears By Age Report'],
                  //  ['id' => 26, 'label' => 'Portfolio At Risk by Age Report'],
                    //['id' => 27, 'label' => 'Written-off Loans Report'],
                   // ['id' => 28, 'label' => 'Written-off Loans Repayment Report'],
                  //  ['id' => 29, 'label' => 'Rescheduled Loans Report'],
                   // ['id' => 30, 'label' => 'Portfolio Concentration Report'],
                    //['id' => 31, 'label' => 'Loan Officers Analysis Report'],
                   // ['id' => 32, 'label' => 'Paid-Off Loans Report'],
                   // ['id' => 33, 'label' => 'Loan Guarantors Report'],
                   // ['id' => 34, 'label' => 'Member Saving & Loan Status Report'],
                    //['id' => 35, 'label' => 'Loan Dues & Arrears During a Period Report'],
                    //['id' => 36, 'label' => 'Loan Arrears, Due & Repayments During a period'],
                    ];
                    @endphp

                    <!-- Regulatory Reports Section -->
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-3 px-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Regulatory Reports
                        </h4>
                        @foreach($menuItems as $menuItem)
                        @if($menuItem['id'] >= 37 && $menuItem['id'] <= 47)
                            <button
                            wire:click="menuItemClicked({{ $menuItem['id'] }})"
                            class="relative w-full group transition-all duration-200 mb-1">
                            <div class="flex items-center p-3 rounded-xl transition-all duration-200
                                        @if ($this->tab_id == $menuItem['id']) 
                                            bg-purple-100 text-purple-900 shadow-md border border-purple-200
                                        @else 
                                            bg-gray-50 hover:bg-purple-50 text-gray-700 hover:text-purple-700 
                                        @endif">

                                <!-- Loading State -->
                                <div wire:loading wire:target="menuItemClicked({{ $menuItem['id'] }})" class="mr-3">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>

                                <!-- Icon -->
                                <div wire:loading.remove wire:target="menuItemClicked({{ $menuItem['id'] }})" class="mr-3">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 text-left">
                                    <div class="font-medium text-xs leading-tight">{{ Str::limit($menuItem['label'], 40) }}</div>
                                </div>
                            </div>
                            </button>
                            @endif
                            @endforeach
                    </div>

                    <!-- General Reports Section -->
                    <div class="mb-6">
                        <h4 class="text-sm font-medium text-gray-700 mb-3 px-2 flex items-center">
                            <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            General Reports
                        </h4>
                        @foreach($menuItems as $menuItem)
                        @if($menuItem['id'] < 37)
                            <button
                            wire:click="menuItemClicked({{ $menuItem['id'] }})"
                            class="relative w-full group transition-all duration-200 mb-1">
                            <div class="flex items-center p-3 rounded-xl transition-all duration-200
                                        @if ($this->tab_id == $menuItem['id']) 
                                            bg-blue-100 text-blue-900 shadow-md border border-blue-200
                                        @else 
                                            bg-gray-50 hover:bg-blue-50 text-gray-700 hover:text-blue-700 
                                        @endif">

                                <!-- Loading State -->
                                <div wire:loading wire:target="menuItemClicked({{ $menuItem['id'] }})" class="mr-3">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>

                                <!-- Icon -->
                                <div wire:loading.remove wire:target="menuItemClicked({{ $menuItem['id'] }})" class="mr-3">
                                    @php
                                    $iconPaths = [
                                    1 => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z', // Daily Report
                                    2 => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z', // Members
                                    'default' => 'M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'
                                    ];
                                    $iconPath = $iconPaths[$menuItem['id']] ?? $iconPaths['default'];
                                    @endphp
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $iconPath }}"></path>
                                    </svg>
                                </div>

                                <!-- Content -->
                                <div class="flex-1 text-left">
                                    <div class="font-medium text-xs leading-tight">{{ Str::limit($menuItem['label'], 40) }}</div>
                                </div>
                            </div>
                            </button>
                            @endif
                            @endforeach
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="p-4 border-t border-gray-100 bg-gray-50">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3 px-2">Quick Actions</h3>
                    <div class="space-y-2">
                        <button class="w-full flex items-center p-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-white rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path>
                            </svg>
                            Export All Reports
                        </button>
                        <button class="w-full flex items-center p-2 text-sm text-gray-600 hover:text-gray-900 hover:bg-white rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V6a2 2 0 012-2h4a2 2 0 012 2v1m-6 0h6m-6 0H6a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V9a2 2 0 00-2-2h-1"></path>
                            </svg>
                            Schedule Reports
                        </button>
                    </div>
                </div>
            </div>

            <!-- Enhanced Main Content Area -->
            <div class="flex-1">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <!-- Content Header -->
                    <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center justify-between">
                            <div>
                                @php
                                $currentReport = collect($menuItems)->firstWhere('id', $this->tab_id);
                                @endphp
                                <h2 class="text-xl font-semibold text-gray-900">
                                    {{ $currentReport['label'] ?? 'Select a Report' }}
                                </h2>
                                <p class="text-gray-600 mt-1">
                                    @if($currentReport)
                                    Generate and analyze {{ strtolower($currentReport['label']) }}
                                    @else
                                    Choose a report from the sidebar to get started
                                    @endif
                                </p>
                            </div>

                            <!-- Breadcrumb -->
                            <nav class="flex" aria-label="Breadcrumb">
                                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                    <li class="inline-flex items-center">
                                        <span class="inline-flex items-center text-sm font-medium text-gray-700">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                            </svg>
                                            Reports
                                        </span>
                                    </li>
                                    @if($currentReport)
                                    <li>
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">
                                                {{ Str::limit($currentReport['label'], 30) }}
                                            </span>
                                        </div>
                                    </li>
                                    @endif
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div class="w-full flex items-center justify-center">
                        <div wire:loading wire:target="setView">
                            <div class="h-96 m-auto flex items-center justify-center">
                                <div class="animate-spin rounded-full h-16 w-16 border-t-4 border-blue-500"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="p-8">
                        <div wire:loading.remove wire:target="menuItemClicked" class="min-h-[400px]">
                            @switch($this->tab_id)

                            @case('1')
                            <livewire:reports.daily-report />
                            @break

                            @case('2')
                            <livewire:reports.clients-details-report />
                            @break
                            @case('3')
                            <livewire:reports.client-loan-account />
                            @break

                            @case('4')
                            <livewire:reports.client-repayment-history />
                            @break

                            @case('5')
                            <livewire:reports.loan-report />

                            @break

                            @case('9')
                            <livewire:reports.financial-ratio />
                            @break


                            @case('10')
                            <livewire:reports.loan-status />
                            @break

                            @case('11')
                            <livewire:reports.commitee />
                            @break

                            @case('6')
                            <livewire:reports.loan-application-report />

                            {{-- <livewire:reports.loan-portifolio-report /> --}}
                            @break

                            @case('7')

                            <livewire:reports.loan-delinquency-report />
                            @break

                            @case('13')
                            <div class="bg-gray-200 p-1 rounded-2xl">


                                <div class="bg-white p-4 rounded-2xl mb-1">

                                    <div class="flex gap-4 w-full ">

                                        <div>
                                            <div>
                                                From Date
                                                <input wire:model="reportStartDate" type="date" class="mb-2 w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-red-500 focus:border-red-500   :text-white  ">
                                                @error('reportStartDate')
                                                <div class="text-red-500 text-xs"> start date is required</div>
                                                @enderror
                                                To
                                                <input wire:model="reportEndDate" type="date" class="mb-2 w-full text-sm text-gray-900 border border-gray-300 rounded-lg bg-gray-50 focus:ring-red-500 focus:border-red-500   :text-white  ">


                                                @error('reportEndDate')
                                                <div class="text-red-500 text-xs"> end date is required</div>
                                                @enderror
                                            </div>


                                        </div>

                                        <div class=" mt-10">
                                            <div class="inline-flex rounded-md shadow-sm mb-4" role="group">
                                                <button wire:click="downloadExcelFile" type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-l-lg hover:bg-gray-100 hover:text-red-700 focus:z-10 focus:ring-2 focus:ring-red-700 focus:text-red-700  :text-white :hover:text-white :hover:bg-gray-600  :focus:text-white">
                                                    <svg class="w-3 h-3 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"></path>
                                                        <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path>
                                                    </svg>
                                                    Download Excel
                                                </button>

                                                <button type="button" class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-900 bg-white border border-gray-200 rounded-r-md hover:bg-gray-100 hover:text-red-700 focus:z-10 focus:ring-2 focus:ring-red-700 focus:text-red-700  :text-white :hover:text-white :hover:bg-gray-600  :focus:text-white">
                                                    <svg class="w-3 h-3 mr-2" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M14.707 7.793a1 1 0 0 0-1.414 0L11 10.086V1.5a1 1 0 0 0-2 0v8.586L6.707 7.793a1 1 0 1 0-1.414 1.414l4 4a1 1 0 0 0 1.416 0l4-4a1 1 0 0 0-.002-1.414Z"></path>
                                                        <path d="M18 12h-2.55l-2.975 2.975a3.5 3.5 0 0 1-4.95 0L4.55 12H2a2 2 0 0 0-2 2v4a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2v-4a2 2 0 0 0-2-2Zm-3 5a1 1 0 1 1 0-2 1 1 0 0 1 0 2Z"></path>
                                                    </svg>
                                                    Downloads PDF
                                                </button>

                                            </div>
                                        </div>

                                        <div class=" mt-10">


                                            <div class="flex items-center mb-4">
                                                <input wire:model="customize" id="default-checkbox" type="radio" name="radion" value="NO" class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 :focus:ring-red-600 :ring-offset-gray-800 focus:ring-2  :border-gray-600">
                                                <label for="default-checkbox" class="ml-2 text-sm font-medium text-gray-900 :text-gray-300">Default settings</label>
                                            </div>
                                            <div class="flex items-center">
                                                <input wire:model="customize" checked id="checked-checkbox" name="radion" type="radio" value="YES" class="w-4 h-4 text-red-600 bg-gray-100 border-gray-300 rounded focus:ring-red-500 :focus:ring-red-600 :ring-offset-gray-800 focus:ring-2  :border-gray-600">
                                                <label for="checked-checkbox" class="ml-2 text-sm font-medium text-gray-900 :text-gray-300">Custom settings</label>
                                            </div>

                                        </div>
                                        <div class=" flex ">
                                            @if($this->customize=="YES")
                                            @else

                                            @endif
                                        </div>

                                    </div>



                                </div>

                            </div>

                            @break

                            @case(14)
                            <livewire:reports.loan-repayment-schedule />

                            @break



                            @case(15)
                            <livewire:reports.loan-disbursement-report />

                            @break


                            @case(20)

                            <livewire:reports.active-loan-by-officer />

                            @break


                            @case(23)

                            <livewire:reports.portifolio-at-risk />

                            @break


                            @case(37)
                            <livewire:reports.statement-of-financial-position />
                            @break

                            @case(38)
                            <livewire:reports.statement-of-comprehensive-income />
                            @break

                            @case(39)
                            <livewire:reports.statement-of-cash-flow /> 
                            @break

                            @case(40)
                            <livewire:reports.sectoral-classification-of-loans />
                            @break                           


                            @case(41)
                            <livewire:reports.interest-rates-structure-for-loans />
                            @break

                            @case(42)
                            <livewire:reports.loans-disbursed-for-the-month-ended />
                            @break

                            @case(43)
                            <livewire:reports.loans-to-insiders-and-related-parties />
                            @break

                            @case(44)
                            <livewire:reports.geographical-distribution-of-branches-employees-and-loans-by-age-for-the-month-ended />
                            @break

                            @case(45)
                            <livewire:reports.computation-of-liquid-assets-for-the-month-ended />
                            @break

                            @case(46)
                            <livewire:reports.deposits-and-loans-in-banks-and-financial-institutions-for-the-month-ended />
                            @break

                            @case(47)
                            <livewire:reports.complaint-report-for-the-month-ended />
                            @break

                            @case(48)
                            <livewire:reports.computation-of-capital-adequacy-for-the-month-ended />
                            @break

                           


                            

                            
                            

                            @default
                            <div class="text-center py-12">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Select a Report</h3>
                                <p class="text-gray-600">Choose a report from the sidebar to get started</p>
                            </div>
                            @endswitch
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>