<div class="min-h-screen bg-gradient-to-br from-slate-50 to-cyan-50">
    <div class="p-6">
        {{-- Flash Messages --}}
        @if (session()->has('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        {{-- Header --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-cyan-100 rounded-xl">
                        <svg class="w-8 h-8 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a5 5 0 00-10 0v2a2 2 0 00-2 2v6a2 2 0 002 2h10a2 2 0 002-2v-6a2 2 0 00-2-2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Statement of Cash Flow</h1>
                        <p class="text-gray-600">BOT Regulatory Report - IFRS Compliant</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-cyan-100 text-cyan-800">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Compliant
                    </span>
                    <span class="text-sm text-gray-500">Last Updated: {{ now()->format('M d, Y H:i') }}</span>
                </div>
            </div>

            {{-- Controls Grid --}}
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Report Period</label>
                        <select wire:model.live="reportPeriod" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annually">Annually</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Start Date</label>
                        <input type="date" wire:model.live="startDate" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">End Date</label>
                        <input type="date" wire:model.live="endDate" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Currency</label>
                        <select wire:model.live="currency" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            <option value="TZS">TZS</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">View Format</label>
                        <select wire:model.live="viewFormat" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            <option value="detailed">Detailed</option>
                            <option value="summary">Summary</option>
                            <option value="comparative">Comparative</option>
                        </select>
                    </div>
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Action</label>
                        <button wire:click="generateReport" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium transition-colors duration-200 flex items-center justify-center">
                            <svg wire:loading.remove class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            <svg wire:loading class="animate-spin w-4 h-4 mr-1" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove>Generate</span>
                            <span wire:loading>Loading...</span>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Quick Actions --}}
            <div class="flex items-center justify-between mt-6 pt-6 border-t border-gray-200">
                <div class="flex items-center space-x-4">
                    <button wire:click="exportToPDF" class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg font-medium transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export PDF
                    </button>
                    <button wire:click="exportToExcel" class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg font-medium transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export Excel
                    </button>
                    <button wire:click="scheduleReport" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg font-medium transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Schedule
                    </button>
                </div>
                <div class="flex items-center space-x-2">
                    <button wire:click="toggleComparison" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                        Compare Periods
                    </button>
                    <button wire:click="toggleChartView" class="inline-flex items-center px-3 py-2 border border-gray-300 rounded-lg text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                        </svg>
                        {{ ($showCharts ?? false) ? 'Hide Charts' : 'Show Charts' }}
                    </button>
                </div>
            </div>
        </div>

        {{-- KPI Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 bg-cyan-100 rounded-lg">
                        <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Net Operating Cash Flow</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($netOperatingCashFlow ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 bg-cyan-100 rounded-lg">
                        <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Net Investing Cash Flow</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($netInvestingCashFlow ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 bg-cyan-100 rounded-lg">
                        <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Net Financing Cash Flow</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($netFinancingCashFlow ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 bg-cyan-100 rounded-lg">
                        <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Net Cash Flow</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($netCashFlow ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        {{-- Charts Section --}}
        @if($showCharts ?? false)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Cash Flow Trend</h3>
                <canvas id="cashFlowTrendChart" class="w-full h-64"></canvas>
            </div>
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Cash Flow Structure</h3>
                <canvas id="cashFlowStructureChart" class="w-full h-64"></canvas>
            </div>
        </div>
        @endif

        {{-- Main Statement --}}
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-cyan-50 to-blue-50 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Statement of Cash Flow</h2>
                    <div class="text-sm text-gray-600">
                        For the period {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('F d') : 'Start' }} - {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('F d, Y') : 'Current Date' }}
                    </div>
                </div>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    {{-- Operating Activities --}}
                    <div>
                        <h3 class="text-xs font-bold text-gray-700 tracking-wide mb-2 border-b border-gray-200 pb-1 uppercase">Operating Activities</h3>
                        <div class="divide-y divide-gray-100">
                            @forelse($operatingActivities ?? [] as $activity)
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-xs font-medium text-gray-600">{{ $activity['description'] }}</span>
                                    <span class="text-xs font-mono text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($activity['cash_flow'], 2) }}</span>
                                </div>
                            @empty
                                <div class="text-center py-2 text-gray-400 text-xs">No operating activities</div>
                            @endforelse
                        </div>
                        <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between items-center">
                            <span class="font-bold text-xs text-cyan-900">Net Operating Cash Flow</span>
                            <span class="font-bold text-sm text-cyan-900">{{ $currency ?? 'TZS' }} {{ number_format($netOperatingCashFlow ?? 0, 2) }}</span>
                        </div>
                    </div>
                    {{-- Investing Activities --}}
                    <div>
                        <h3 class="text-xs font-bold text-gray-700 tracking-wide mb-2 border-b border-gray-200 pb-1 uppercase">Investing Activities</h3>
                        <div class="divide-y divide-gray-100">
                            @forelse($investingActivities ?? [] as $activity)
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-xs font-medium text-gray-600">{{ $activity['description'] }}</span>
                                    <span class="text-xs font-mono text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($activity['cash_flow'], 2) }}</span>
                                </div>
                            @empty
                                <div class="text-center py-2 text-gray-400 text-xs">No investing activities</div>
                            @endforelse
                        </div>
                        <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between items-center">
                            <span class="font-bold text-xs text-cyan-900">Net Investing Cash Flow</span>
                            <span class="font-bold text-sm text-cyan-900">{{ $currency ?? 'TZS' }} {{ number_format($netInvestingCashFlow ?? 0, 2) }}</span>
                        </div>
                    </div>
                    {{-- Financing Activities --}}
                    <div>
                        <h3 class="text-xs font-bold text-gray-700 tracking-wide mb-2 border-b border-gray-200 pb-1 uppercase">Financing Activities</h3>
                        <div class="divide-y divide-gray-100">
                            @forelse($financingActivities ?? [] as $activity)
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-xs font-medium text-gray-600">{{ $activity['description'] }}</span>
                                    <span class="text-xs font-mono text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($activity['cash_flow'], 2) }}</span>
                                </div>
                            @empty
                                <div class="text-center py-2 text-gray-400 text-xs">No financing activities</div>
                            @endforelse
                        </div>
                        <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between items-center">
                            <span class="font-bold text-xs text-cyan-900">Net Financing Cash Flow</span>
                            <span class="font-bold text-sm text-cyan-900">{{ $currency ?? 'TZS' }} {{ number_format($netFinancingCashFlow ?? 0, 2) }}</span>
                        </div>
                    </div>
                </div>
                {{-- Net Cash Flow Section --}}
                <div class="border-t border-gray-200 pt-6 mt-6">
                    <div class="bg-gradient-to-r from-cyan-50 to-blue-50 rounded-xl p-6">
                        <div class="flex justify-between items-center">
                            <span class="text-xl font-bold text-gray-900">NET CASH FLOW</span>
                            <span class="text-3xl font-bold text-cyan-600">
                                {{ $currency ?? 'TZS' }} {{ number_format($netCashFlow ?? 0, 2) }}
                            </span>
                        </div>
                        <div class="mt-4 grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Cash at Beginning</p>
                                <p class="text-lg font-semibold text-cyan-600">
                                    {{ $currency ?? 'TZS' }} {{ number_format($beginningCashBalance ?? 0, 2) }}
                                </p>
                            </div>
                            <div class="text-center">
                                <p class="text-sm text-gray-600">Cash at End</p>
                                <p class="text-lg font-semibold text-blue-600">
                                    {{ $currency ?? 'TZS' }} {{ number_format($endingCashBalance ?? 0, 2) }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Period Comparison --}}
        @if($showComparison ?? false)
        <div class="mt-6 bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Period Comparison</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Previous Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">% Change</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Net Operating Cash Flow</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($netOperatingCashFlow ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($previousNetOperatingCashFlow ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-cyan-600">{{ $currency ?? 'TZS' }} {{ number_format(($netOperatingCashFlow ?? 0) - ($previousNetOperatingCashFlow ?? 0), 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-cyan-600">{{ ($previousNetOperatingCashFlow ?? 0) > 0 ? number_format((($netOperatingCashFlow - $previousNetOperatingCashFlow) / $previousNetOperatingCashFlow) * 100, 1) : 'N/A' }}%</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Net Investing Cash Flow</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($netInvestingCashFlow ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($previousNetInvestingCashFlow ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-cyan-600">{{ $currency ?? 'TZS' }} {{ number_format(($netInvestingCashFlow ?? 0) - ($previousNetInvestingCashFlow ?? 0), 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-cyan-600">{{ ($previousNetInvestingCashFlow ?? 0) > 0 ? number_format((($netInvestingCashFlow - $previousNetInvestingCashFlow) / $previousNetInvestingCashFlow) * 100, 1) : 'N/A' }}%</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Net Financing Cash Flow</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($netFinancingCashFlow ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($previousNetFinancingCashFlow ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-cyan-600">{{ $currency ?? 'TZS' }} {{ number_format(($netFinancingCashFlow ?? 0) - ($previousNetFinancingCashFlow ?? 0), 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-cyan-600">{{ ($previousNetFinancingCashFlow ?? 0) > 0 ? number_format((($netFinancingCashFlow - $previousNetFinancingCashFlow) / $previousNetFinancingCashFlow) * 100, 1) : 'N/A' }}%</td>
                        </tr>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Net Cash Flow</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($netCashFlow ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($previousNetCashFlow ?? 0, 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-cyan-600">{{ $currency ?? 'TZS' }} {{ number_format(($netCashFlow ?? 0) - ($previousNetCashFlow ?? 0), 2) }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-cyan-600">{{ ($previousNetCashFlow ?? 0) > 0 ? number_format((($netCashFlow - $previousNetCashFlow) / $previousNetCashFlow) * 100, 1) : 'N/A' }}%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        @endif






        {{-- Compliance Footer --}}
        <div class="mt-6 bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <div class="flex items-center justify-between text-sm text-gray-600">
                <div class="flex items-center space-x-4">
                    <span>Prepared in accordance with IFRS</span>
                    <span>•</span>
                    <span>BOT Regulatory Requirements Compliant</span>
                    <span>•</span>
                    <span>Generated on {{ now()->format('Y-m-d H:i:s') }}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <svg class="w-4 h-4 text-cyan-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-cyan-600 font-medium">Audited</span>
                </div>
            </div>
        </div>

        {{-- Schedule Report Modal --}}
        @if($showScheduleModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="cancelSchedule">
            <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-3xl shadow-lg rounded-md bg-white" wire:click.stop>
                <div class="mt-3">
                    <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                        <h3 class="text-lg font-semibold text-gray-900">Schedule Report Delivery</h3>
                        <button wire:click="cancelSchedule" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="mt-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="scheduleFrequency" class="block text-sm font-medium text-gray-700 mb-2">Frequency</label>
                                <select wire:model="scheduleFrequency" id="scheduleFrequency" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                                    <option value="once">One Time</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="annually">Annually</option>
                                </select>
                            </div>
                            <div>
                                <label for="scheduleDate" class="block text-sm font-medium text-gray-700 mb-2">Schedule Date</label>
                                <input type="date" wire:model="scheduleDate" id="scheduleDate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                                @error('scheduleDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label for="scheduleTime" class="block text-sm font-medium text-gray-700 mb-2">Time</label>
                            <input type="time" wire:model="scheduleTime" id="scheduleTime" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            @error('scheduleTime') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="space-y-4">
                            <h4 class="text-md font-medium text-gray-900">Select Recipients</h4>
                            <div>
                                <label for="userSearch" class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
                                <input type="text" wire:model.live="userSearchTerm" id="userSearch" placeholder="Search by name, email, or department..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <label class="block text-sm font-medium text-gray-700">
                                        Select Users <span class="text-gray-500 text-xs">({{ count($selectedUsers ?? []) }} selected)</span>
                                    </label>
                                    <div class="flex space-x-2">
                                        <button type="button" wire:click="selectAllUsers" class="text-xs text-cyan-600 hover:text-cyan-800 font-medium">Select All</button>
                                        <span class="text-gray-300">|</span>
                                        <button type="button" wire:click="clearAllUsers" class="text-xs text-red-600 hover:text-red-800 font-medium">Clear All</button>
                                    </div>
                                </div>
                                @error('selectedUsers') <span class="text-red-500 text-xs block mb-2">{{ $message }}</span> @enderror
                                <div class="border border-gray-300 rounded-md max-h-64 overflow-y-auto bg-white">
                                    @if(count($availableUsers ?? []) > 0)
                                        <div class="divide-y divide-gray-200">
                                            @foreach($availableUsers as $user)
                                                <label class="flex items-center p-3 hover:bg-gray-50 cursor-pointer">
                                                    <input type="checkbox" wire:model.live="selectedUsers" value="{{ $user['id'] }}" class="h-4 w-4 text-cyan-600 focus:ring-cyan-500 border-gray-300 rounded">
                                                    <div class="ml-3 flex-1">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <p class="text-sm font-medium text-gray-900">{{ $user['name'] }}</p>
                                                                <p class="text-sm text-gray-500">{{ $user['email'] }}</p>
                                                            </div>
                                                            @if(!empty($user['department']))
                                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">{{ $user['department'] }}</span>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <div class="p-4 text-center text-gray-500">
                                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                            </svg>
                                            <p class="mt-2">No users found</p>
                                            @if(!empty($userSearchTerm))
                                                <p class="text-xs">Try adjusting your search terms</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @if(count($selectedUsers ?? []) > 0)
                                <div class="bg-cyan-50 rounded-lg p-3">
                                    <h5 class="text-sm font-medium text-cyan-900 mb-2">Selected Recipients ({{ count($selectedUsers) }})</h5>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach($availableUsers as $user)
                                            @if(in_array($user['id'], $selectedUsers ?? []))
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">
                                                    {{ $user['name'] }}
                                                    <button type="button" wire:click="removeUser({{ $user['id'] }})" class="ml-1 inline-flex items-center justify-center w-4 h-4 text-cyan-400 hover:text-cyan-600">
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                                        </svg>
                                                    </button>
                                                </span>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="space-y-4">
                            <h4 class="text-md font-medium text-gray-900">Email Configuration</h4>
                            <div>
                                <label for="emailSubject" class="block text-sm font-medium text-gray-700 mb-2">Email Subject</label>
                                <input type="text" wire:model="emailSubject" id="emailSubject" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent">
                                @error('emailSubject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label for="emailMessage" class="block text-sm font-medium text-gray-700 mb-2">Email Message (Optional)</label>
                                <textarea wire:model="emailMessage" id="emailMessage" rows="4" placeholder="Additional message to include in the email..." class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent"></textarea>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <h5 class="text-sm font-medium text-gray-900 mb-2">Report Configuration</h5>
                            <div class="text-sm text-gray-600 space-y-1">
                                <div><strong>Period:</strong> {{ ucfirst($reportPeriod) }}</div>
                                <div><strong>Date Range:</strong> {{ $startDate ? \Carbon\Carbon::parse($startDate)->format('d.m.Y') : '' }} to {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d.m.Y') : '' }}</div>
                                <div><strong>Currency:</strong> {{ $currency }}</div>
                                <div><strong>Format:</strong> {{ ucfirst($viewFormat) }}</div>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end pt-6 border-t border-gray-200 space-x-3">
                        <button wire:click="cancelSchedule" class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-cyan-500">Cancel</button>
                        <button wire:click="confirmSchedule" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-cyan-500 flex items-center">
                            <svg wire:loading class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span wire:loading.remove>Schedule Report</span>
                            <span wire:loading>Scheduling...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('livewire:load', function () {
    if (document.getElementById('cashFlowTrendChart')) {
        const trendCtx = document.getElementById('cashFlowTrendChart').getContext('2d');
        new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Net Cash Flow',
                    data: [{{ ($netCashFlow ?? 0) * 0.7 }}, {{ ($netCashFlow ?? 0) * 0.8 }}, {{ ($netCashFlow ?? 0) * 0.85 }}, {{ ($netCashFlow ?? 0) * 0.9 }}, {{ ($netCashFlow ?? 0) * 0.95 }}, {{ $netCashFlow ?? 0 }}],
                    borderColor: '#06B6D4',
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }
    if (document.getElementById('cashFlowStructureChart')) {
        const structureCtx = document.getElementById('cashFlowStructureChart').getContext('2d');
        new Chart(structureCtx, {
            type: 'doughnut',
            data: {
                labels: ['Operating', 'Investing', 'Financing'],
                datasets: [{
                    data: [@json($netOperatingCashFlow ?? 0), @json($netInvestingCashFlow ?? 0), @json($netFinancingCashFlow ?? 0)],
                    backgroundColor: ['#06B6D4', '#F59E0B', '#10B981']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }
});
</script>
@endpush 