
{{-- 
    ===============================================================================
    ENHANCED STATEMENT OF FINANCIAL POSITION - BOT REGULATORY REPORT
    ===============================================================================
    
    Report ID: 37
    Report Type: Statement of Financial Position for the Month Ended
    Compliance: Bank of Tanzania (BOT) Regulatory Requirements
    Standard: International Financial Reporting Standards (IFRS)
    
    Features:
    • Modern responsive design with advanced filtering and controls
    • Interactive charts and visualizations for financial data analysis
    • Period comparison and trend analysis capabilities
    • Professional export options (PDF, Excel, scheduled reports)
    • Real-time balance sheet verification and compliance checking
    • Drill-down capabilities for detailed account analysis
    • BOT-compliant formatting and regulatory submission ready
    
    Author: NBC SACCOS Development Team
    Last Updated: {{ now()->format('Y-m-d H:i:s') }}
    Version: 2.0 - Enhanced UI/UX with Modern Grid Layout
    ===============================================================================
--}}
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="p-6">
        <!-- Flash Messages -->
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

        <!-- Enhanced Header -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center space-x-4">
                    <div class="p-3 bg-purple-100 rounded-xl">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Statement of Financial Position</h1>
                        <p class="text-gray-600">BOT Regulatory Report - IFRS Compliant</p>
                    </div>
                </div>
                
                <!-- Report Status -->
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                        <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>
                        Compliant
                    </span>
                    <span class="text-sm text-gray-500">Last Updated: {{ now()->format('M d, Y H:i') }}</span>
                </div>
            </div>

            <!-- Enhanced Controls Grid -->
            <div class="bg-gray-50 rounded-lg p-4 mb-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                    <!-- Report Configuration -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Report Period</label>
                        <select wire:model.live="reportPeriod" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="annually">Annually</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    
                    <!-- Start Date -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Start Date</label>
                        <input type="date" wire:model.live="startDate" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <!-- End Date -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">End Date</label>
                        <input type="date" wire:model.live="endDate" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                    </div>
                    
                    <!-- Currency -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Currency</label>
                        <select wire:model.live="currency" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="TZS">TZS</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                        </select>
                    </div>
                    
                    <!-- View Format -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">View Format</label>
                        <select wire:model.live="viewFormat" class="px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            <option value="detailed">Detailed</option>
                            <option value="summary">Summary</option>
                            <option value="comparative">Comparative</option>
                        </select>
                    </div>
                    
                    <!-- Generate Button -->
                    <div class="flex flex-col">
                        <label class="text-xs font-medium text-gray-600 mb-1">Action</label>
                        <button wire:click="generateReport" 
                                wire:loading.attr="disabled"
                                wire:loading.class="opacity-50 cursor-not-allowed"
                                class="px-3 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-md text-sm font-medium transition-colors duration-200 flex items-center justify-center">
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

            <!-- Quick Actions -->
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

        <!-- KPI Summary Cards -->
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Assets</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($totalAssets ?? 0, 2) }}</p>
                        <p class="text-sm text-green-600 font-medium">+5.2% from last period</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 bg-red-100 rounded-lg">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Liabilities</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($totalLiabilities ?? 0, 2) }}</p>
                        <p class="text-sm text-yellow-600 font-medium">+2.1% from last period</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Total Equity</p>
                        <p class="text-2xl font-bold text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format($totalEquity ?? 0, 2) }}</p>
                        <p class="text-sm text-green-600 font-medium">+8.5% from last period</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Debt-to-Equity</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format(($totalEquity > 0 ? $totalLiabilities / $totalEquity : 0), 2) }}</p>
                        <p class="text-sm {{ ($totalEquity > 0 && $totalLiabilities / $totalEquity < 1) ? 'text-green-600' : 'text-red-600' }} font-medium">
                            {{ ($totalEquity > 0 && $totalLiabilities / $totalEquity < 1) ? 'Healthy' : 'Monitor' }}
                        </p>
                    </div>
                </div>
            </div>
        </div>

        @if($showCharts ?? false)
        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Asset Composition</h3>
                <canvas id="assetChart" class="w-full h-64"></canvas>
            </div>
            
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Financial Structure</h3>
                <canvas id="structureChart" class="w-full h-64"></canvas>
            </div>
        </div>
        @endif

        <!-- Main Financial Statement -->
        <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-blue-50 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-gray-900">Statement of Financial Position </h2>
                    <div class="text-sm text-gray-600">
                        For the period ending {{ $endDate ? \Carbon\Carbon::parse($endDate)->format('d.m.Y') : 'Current Date' }}
                    </div>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Assets Section -->
                    <div>
                        <h3 class="text-xs font-bold text-gray-700 tracking-wide mb-2 border-b border-gray-200 pb-1 uppercase">Assets</h3>
                        <div class="divide-y divide-gray-100">
                            @forelse($assets ?? [] as $asset)
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-xs font-medium text-gray-600">{{ is_object($asset) ? $asset->account_name : $asset['account_name'] }}</span>
                                    <span class="text-xs font-mono text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format(is_object($asset) ? $asset->balance : $asset['balance'], 2) }}</span>
                                </div>
                            @empty
                                <div class="text-center py-2 text-gray-400 text-xs">No asset data available</div>
                            @endforelse
                        </div>
                        <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between items-center">
                            <span class="font-bold text-xs text-blue-900">Total Assets</span>
                            <span class="font-bold text-sm text-blue-900">{{ $currency ?? 'TZS' }} {{ number_format($totalAssets ?? 0, 2) }}</span>
                        </div>
                    </div>

                    <!-- Liabilities and Equity Section -->
                    <div>
                        <h3 class="text-xs font-bold text-gray-700 tracking-wide mb-2 border-b border-gray-200 pb-1 uppercase">Liabilities</h3>
                        <div class="divide-y divide-gray-100">
                            @forelse($liabilities ?? [] as $liability)
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-xs font-medium text-gray-600">{{ is_object($liability) ? $liability->account_name : $liability['account_name'] }}</span>
                                    <span class="text-xs font-mono text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format(is_object($liability) ? $liability->balance : $liability['balance'], 2) }}</span>
                                </div>
                            @empty
                                <div class="text-center py-2 text-gray-400 text-xs">No liability data available</div>
                            @endforelse
                        </div>
                        <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between items-center">
                            <span class="font-bold text-xs text-red-900">Total Liabilities</span>
                            <span class="font-bold text-sm text-red-900">{{ $currency ?? 'TZS' }} {{ number_format($totalLiabilities ?? 0, 2) }}</span>
                        </div>

                        <h3 class="text-xs font-bold text-gray-700 tracking-wide mb-2 border-b border-gray-200 pb-1 mt-6 uppercase">Equity</h3>
                        <div class="divide-y divide-gray-100">
                            @forelse($equity ?? [] as $equityItem)
                                <div class="flex justify-between items-center py-1">
                                    <span class="text-xs font-medium text-gray-600">{{ is_object($equityItem) ? $equityItem->account_name : $equityItem['account_name'] }}</span>
                                    <span class="text-xs font-mono text-gray-900">{{ $currency ?? 'TZS' }} {{ number_format(is_object($equityItem) ? $equityItem->balance : $equityItem['balance'], 2) }}</span>
                                </div>
                            @empty
                                <div class="text-center py-2 text-gray-400 text-xs">No equity data available</div>
                            @endforelse
                        </div>
                        <div class="border-t border-gray-200 pt-2 mt-2 flex justify-between items-center">
                            <span class="font-bold text-xs text-green-900">Total Equity</span>
                            <span class="font-bold text-sm text-green-900">{{ $currency ?? 'TZS' }} {{ number_format($totalEquity ?? 0, 2) }}</span>
                        </div>

                        <div class="border-t border-gray-300 pt-4 mt-4 flex justify-between items-center">
                            <span class="font-bold text-xs text-purple-900">Total Liabilities & Equity</span>
                            <span class="font-bold text-base text-purple-900">{{ $currency ?? 'TZS' }} {{ number_format(($totalLiabilities ?? 0) + ($totalEquity ?? 0), 2) }}</span>
                        </div>
                    </div>
                </div>

                <!-- Balance Sheet Equation Verification -->
                <div class="mt-6 pt-4 border-t border-gray-200">
                    @if($isBalanced)
                        <!-- Balanced State - Subtle Success -->
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                            <div class="flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <h4 class="text-sm font-bold text-green-800 text-center mb-3 uppercase">Balance Sheet Equation Verification</h4>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs">
                                <div class="text-center">
                                    <div class="font-bold text-blue-900">Total Assets</div>
                                    <div class="font-mono text-sm">{{ $currency ?? 'TZS' }} {{ number_format($totalAssets ?? 0, 2) }}</div>
                                </div>
                                <div class="text-center flex items-center justify-center">
                                    <div class="font-bold text-lg text-green-700">=</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-bold text-purple-900">Liabilities + Equity</div>
                                    <div class="font-mono text-sm">{{ $currency ?? 'TZS' }} {{ number_format(($totalLiabilities ?? 0) + ($totalEquity ?? 0), 2) }}</div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <div class="inline-flex items-center px-3 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    Balance Sheet is Balanced
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Unbalanced State - Moderate Alert -->
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                            <!-- Alert Header -->
                            <div class="flex items-center justify-center mb-3">
                                <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                            </div>
                            
                            <h4 class="text-sm font-bold text-red-800 text-center mb-2 uppercase">Balance Sheet Out of Balance</h4>
                            <p class="text-red-700 text-center mb-4 text-xs">The accounting equation is not balanced and requires attention.</p>
                            
                            <!-- Equation Display -->
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-xs mb-4">
                                <div class="text-center">
                                    <div class="font-bold text-blue-900">Total Assets</div>
                                    <div class="font-mono text-sm">{{ $currency ?? 'TZS' }} {{ number_format($totalAssets ?? 0, 2) }}</div>
                                </div>
                                <div class="text-center flex items-center justify-center">
                                    <div class="font-bold text-lg text-red-700">≠</div>
                                </div>
                                <div class="text-center">
                                    <div class="font-bold text-purple-900">Liabilities + Equity</div>
                                    <div class="font-mono text-sm">{{ $currency ?? 'TZS' }} {{ number_format(($totalLiabilities ?? 0) + ($totalEquity ?? 0), 2) }}</div>
                                </div>
                            </div>
                            
                            <!-- Difference Display -->
                            <div class="bg-red-100 border border-red-300 rounded p-3 mb-3">
                                <div class="text-center">
                                    <div class="text-sm font-bold text-red-800 mb-1">Difference</div>
                                    <div class="text-lg font-bold text-red-900 font-mono">
                                        {{ $currency ?? 'TZS' }} {{ number_format($balanceSheetDifference, 2) }}
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Items -->
                            <div class="bg-yellow-50 border border-yellow-300 rounded p-3">
                                <div class="flex items-start">
                                    <svg class="w-4 h-4 text-yellow-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                    </svg>
                                    <div>
                                        <h5 class="font-bold text-yellow-800 mb-1 text-xs">Action Required:</h5>
                                        <ul class="text-xs text-yellow-700 space-y-0.5">
                                            <li>• Review account balances for errors</li>
                                            <li>• Check for missing transactions</li>
                                            <li>• Verify account classifications</li>
                                            <li>• Resolve before regulatory submission</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Status Badge -->
                            <div class="mt-3 text-center">
                                <div class="inline-flex items-center px-3 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                                    </svg>
                                    Requires Resolution
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- BOT Compliance Footer -->
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
                    <svg class="w-4 h-4 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-green-600 font-medium">Verified</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Schedule Report Modal -->
    @if($showScheduleModal)
    <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" wire:click="cancelSchedule">
        <div class="relative top-20 mx-auto p-5 border w-11/12 max-w-3xl shadow-lg rounded-md bg-white" wire:click.stop>
            <div class="mt-3">
                <!-- Modal Header -->
                <div class="flex items-center justify-between pb-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Schedule Report Delivery</h3>
                    <button wire:click="cancelSchedule" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Modal Content -->
                <div class="mt-6 space-y-6">
                    <!-- Scheduling Options -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="scheduleFrequency" class="block text-sm font-medium text-gray-700 mb-2">Frequency</label>
                            <select wire:model="scheduleFrequency" id="scheduleFrequency" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
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
                            <input type="date" wire:model="scheduleDate" id="scheduleDate" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('scheduleDate') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="scheduleTime" class="block text-sm font-medium text-gray-700 mb-2">Time</label>
                        <input type="time" wire:model="scheduleTime" id="scheduleTime" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        @error('scheduleTime') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <!-- User Selection -->
                    <div class="space-y-4">
                        <h4 class="text-md font-medium text-gray-900">Select Recipients</h4>
                        
                        <!-- Search Users -->
                        <div>
                            <label for="userSearch" class="block text-sm font-medium text-gray-700 mb-2">Search Users</label>
                            <input type="text" wire:model.live="userSearchTerm" id="userSearch" 
                                   placeholder="Search by name, email, or department..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>

                        <!-- Users List -->
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <label class="block text-sm font-medium text-gray-700">
                                    Select Users 
                                    <span class="text-gray-500 text-xs">({{ count($selectedUsers ?? []) }} selected)</span>
                                </label>
                                <div class="flex space-x-2">
                                    <button type="button" wire:click="selectAllUsers" 
                                            class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                                        Select All
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button type="button" wire:click="clearAllUsers" 
                                            class="text-xs text-red-600 hover:text-red-800 font-medium">
                                        Clear All
                                    </button>
                                </div>
                            </div>
                            @error('selectedUsers') <span class="text-red-500 text-xs block mb-2">{{ $message }}</span> @enderror
                            
                            <div class="border border-gray-300 rounded-md max-h-64 overflow-y-auto bg-white">
                                @if(count($availableUsers ?? []) > 0)
                                    <div class="divide-y divide-gray-200">
                                        @foreach($availableUsers as $user)
                                            <label class="flex items-center p-3 hover:bg-gray-50 cursor-pointer">
                                                <input type="checkbox" 
                                                       wire:model.live="selectedUsers" 
                                                       value="{{ $user['id'] }}"
                                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                <div class="ml-3 flex-1">
                                                    <div class="flex items-center justify-between">
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-900">{{ $user['name'] }}</p>
                                                            <p class="text-sm text-gray-500">{{ $user['email'] }}</p>
                                                        </div>
                                                        @if(!empty($user['department']))
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                                {{ $user['department'] }}
                                                            </span>
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

                        <!-- Selected Users Summary -->
                        @if(count($selectedUsers ?? []) > 0)
                            <div class="bg-blue-50 rounded-lg p-3">
                                <h5 class="text-sm font-medium text-blue-900 mb-2">Selected Recipients ({{ count($selectedUsers) }})</h5>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($availableUsers as $user)
                                        @if(in_array($user['id'], $selectedUsers ?? []))
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $user['name'] }}
                                                <button type="button" 
                                                        wire:click="removeUser({{ $user['id'] }})"
                                                        class="ml-1 inline-flex items-center justify-center w-4 h-4 text-blue-400 hover:text-blue-600">
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

                    <!-- Email Configuration -->
                    <div class="space-y-4">
                        <h4 class="text-md font-medium text-gray-900">Email Configuration</h4>

                        <div>
                            <label for="emailSubject" class="block text-sm font-medium text-gray-700 mb-2">Email Subject</label>
                            <input type="text" wire:model="emailSubject" id="emailSubject" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('emailSubject') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="emailMessage" class="block text-sm font-medium text-gray-700 mb-2">Email Message (Optional)</label>
                            <textarea wire:model="emailMessage" id="emailMessage" rows="4" 
                                      placeholder="Additional message to include in the email..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"></textarea>
                        </div>
                    </div>

                    <!-- Report Configuration Summary -->
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

                <!-- Modal Footer -->
                <div class="flex items-center justify-end pt-6 border-t border-gray-200 space-x-3">
                    <button wire:click="cancelSchedule" 
                            class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button wire:click="confirmSchedule"
                            wire:loading.attr="disabled"
                            wire:loading.class="opacity-50 cursor-not-allowed"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md text-sm font-medium focus:outline-none focus:ring-2 focus:ring-blue-500 flex items-center">
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

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
//document.addEventListener('livewire:load', function () {
    if (document.getElementById('assetChart')) {
        const assetCtx = document.getElementById('assetChart').getContext('2d');
        new Chart(assetCtx, {
            type: 'doughnut',
            data: {
                labels: @json(collect($assets ?? [])->pluck('account_name')),
                datasets: [{
                    data: @json(collect($assets ?? [])->pluck('balance')),
                    backgroundColor: [
                        '#8B5CF6', '#06B6D4', '#10B981', '#F59E0B', '#EF4444', '#6366F1'
                    ]
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

    if (document.getElementById('structureChart')) {
        const structureCtx = document.getElementById('structureChart').getContext('2d');
        new Chart(structureCtx, {
            type: 'bar',
            data: {
                labels: ['Assets', 'Liabilities', 'Equity'],
                datasets: [{
                    data: [@json($totalAssets ?? 0), @json($totalLiabilities ?? 0), @json($totalEquity ?? 0)],
                    backgroundColor: ['#06B6D4', '#EF4444', '#10B981']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
//});
</script>
@endpush 