<div class="bg-white rounded-lg shadow-sm">
    <!-- Header Section -->
    <div class="border-b border-gray-200 px-6 py-4">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $companyName }}</h2>
                <h3 class="text-lg font-semibold text-gray-700 mt-1">Asset Depreciation Management</h3>
            </div>
            
            <!-- Export Buttons -->
            <div class="flex space-x-2">
                <button wire:click="exportToExcel" 
                        class="px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Export Excel
                </button>
                <button wire:click="exportToPDF" 
                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                              d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Export PDF
                </button>
            </div>
        </div>
    </div>

    <!-- Controls Section -->
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
            <!-- Year Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Year</label>
                <select wire:model="selectedYear" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @for($year = date('Y'); $year >= date('Y') - 10; $year--)
                        <option value="{{ $year }}">{{ $year }}</option>
                    @endfor
                </select>
            </div>

            <!-- Month Selection -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Month</label>
                <select wire:model="selectedMonth" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @foreach(['1' => 'January', '2' => 'February', '3' => 'March', '4' => 'April', '5' => 'May', '6' => 'June', 
                             '7' => 'July', '8' => 'August', '9' => 'September', '10' => 'October', '11' => 'November', '12' => 'December'] as $value => $month)
                        <option value="{{ $value }}">{{ $month }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Period Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                <select wire:model="depreciationPeriod" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="monthly">Monthly</option>
                    <option value="quarterly">Quarterly</option>
                    <option value="yearly">Yearly</option>
                </select>
            </div>

            <!-- Asset Category -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                <select wire:model="assetCategory" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="all">All Categories</option>
                    <option value="ppe">Property, Plant & Equipment</option>
                    <option value="intangible">Intangible Assets</option>
                    <option value="other">Other Assets</option>
                </select>
            </div>

            <!-- Search -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <input type="text" 
                       wire:model.debounce.300ms="searchTerm" 
                       placeholder="Search assets..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <!-- View Mode Tabs -->
        <div class="flex space-x-1 mt-4">
            <button wire:click="changeViewMode('summary')" 
                    class="px-4 py-2 rounded-lg transition-colors {{ $viewMode === 'summary' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                Summary
            </button>
            <button wire:click="changeViewMode('detailed')" 
                    class="px-4 py-2 rounded-lg transition-colors {{ $viewMode === 'detailed' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                Detailed
            </button>
            <button wire:click="changeViewMode('journal')" 
                    class="px-4 py-2 rounded-lg transition-colors {{ $viewMode === 'journal' ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100' }}">
                Journal Entries
            </button>
        </div>
    </div>

    <!-- Content Section -->
    <div class="p-6">
        @if($viewMode === 'summary')
            <!-- Summary View -->
            <div class="space-y-6">
                <!-- Summary Cards -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 rounded-lg p-4">
                        <p class="text-sm text-blue-600 font-medium">Total Asset Cost</p>
                        <p class="text-2xl font-bold text-blue-900">{{ $this->formatNumber($totals['asset_cost']) }}</p>
                    </div>
                    <div class="bg-yellow-50 rounded-lg p-4">
                        <p class="text-sm text-yellow-600 font-medium">Accumulated Depreciation</p>
                        <p class="text-2xl font-bold text-yellow-900">{{ $this->formatNumber($totals['accumulated_depreciation']) }}</p>
                    </div>
                    <div class="bg-red-50 rounded-lg p-4">
                        <p class="text-sm text-red-600 font-medium">Current Period</p>
                        <p class="text-2xl font-bold text-red-900">{{ $this->formatNumber($totals['current_depreciation']) }}</p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4">
                        <p class="text-sm text-green-600 font-medium">Net Book Value</p>
                        <p class="text-2xl font-bold text-green-900">{{ $this->formatNumber($totals['net_book_value']) }}</p>
                    </div>
                </div>

                <!-- Summary Table by Category -->
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Category</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Assets</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Total Cost</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Accum. Depreciation</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Current Period</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Net Book Value</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($depreciationSummary as $summary)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900 border border-gray-300">{{ $summary['category'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-gray-900 border border-gray-300">{{ $summary['asset_count'] }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900 border border-gray-300">{{ $this->formatNumber($summary['total_cost']) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900 border border-gray-300">{{ $this->formatNumber($summary['accumulated_depreciation']) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900 border border-gray-300">{{ $this->formatNumber($summary['current_depreciation']) }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900 border border-gray-300">{{ $this->formatNumber($summary['net_book_value']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-center text-gray-500 border border-gray-300">No depreciation data available</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr class="bg-gray-100 font-bold">
                                <td class="px-4 py-3 text-sm border border-gray-300">TOTAL</td>
                                <td class="px-4 py-3 text-sm text-center border border-gray-300">{{ count($assets) }}</td>
                                <td class="px-4 py-3 text-sm text-right border border-gray-300">{{ $this->formatNumber($totals['asset_cost']) }}</td>
                                <td class="px-4 py-3 text-sm text-right border border-gray-300">{{ $this->formatNumber($totals['accumulated_depreciation']) }}</td>
                                <td class="px-4 py-3 text-sm text-right border border-gray-300">{{ $this->formatNumber($totals['current_depreciation']) }}</td>
                                <td class="px-4 py-3 text-sm text-right border border-gray-300">{{ $this->formatNumber($totals['net_book_value']) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

        @elseif($viewMode === 'detailed')
            <!-- Detailed Asset View -->
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Asset Name</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Account</th>
                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Category</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Purchase Date</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Life (Years)</th>
                            <th class="px-3 py-2 text-center text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Method</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Initial Value</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Salvage Value</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Monthly Depr.</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Accum. Depr.</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Current Period</th>
                            <th class="px-3 py-2 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">NBV</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($assets as $asset)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">{{ $asset['name'] }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">{{ $asset['account_number'] }}</td>
                                <td class="px-3 py-2 text-sm text-gray-900 border border-gray-300">{{ $asset['category'] }}</td>
                                <td class="px-3 py-2 text-sm text-center text-gray-900 border border-gray-300">{{ $asset['purchase_date'] }}</td>
                                <td class="px-3 py-2 text-sm text-center text-gray-900 border border-gray-300">{{ $asset['useful_life'] }}</td>
                                <td class="px-3 py-2 text-sm text-center text-gray-900 border border-gray-300">{{ $asset['depreciation_method'] }}</td>
                                <td class="px-3 py-2 text-sm text-right text-gray-900 border border-gray-300">{{ $this->formatNumber($asset['initial_value']) }}</td>
                                <td class="px-3 py-2 text-sm text-right text-gray-900 border border-gray-300">{{ $this->formatNumber($asset['salvage_value']) }}</td>
                                <td class="px-3 py-2 text-sm text-right text-gray-900 border border-gray-300">{{ $this->formatNumber($asset['monthly_depreciation']) }}</td>
                                <td class="px-3 py-2 text-sm text-right text-gray-900 border border-gray-300">{{ $this->formatNumber($asset['accumulated_depreciation']) }}</td>
                                <td class="px-3 py-2 text-sm text-right text-gray-900 border border-gray-300">{{ $this->formatNumber($asset['current_period_depreciation']) }}</td>
                                <td class="px-3 py-2 text-sm text-right text-gray-900 border border-gray-300">{{ $this->formatNumber($asset['net_book_value']) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-3 text-center text-gray-500 border border-gray-300">No assets found</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="bg-gray-100 font-bold">
                            <td colspan="6" class="px-3 py-2 text-sm border border-gray-300">TOTAL</td>
                            <td class="px-3 py-2 text-sm text-right border border-gray-300">{{ $this->formatNumber($totals['asset_cost']) }}</td>
                            <td class="px-3 py-2 text-sm text-right border border-gray-300">-</td>
                            <td class="px-3 py-2 text-sm text-right border border-gray-300">-</td>
                            <td class="px-3 py-2 text-sm text-right border border-gray-300">{{ $this->formatNumber($totals['accumulated_depreciation']) }}</td>
                            <td class="px-3 py-2 text-sm text-right border border-gray-300">{{ $this->formatNumber($totals['current_depreciation']) }}</td>
                            <td class="px-3 py-2 text-sm text-right border border-gray-300">{{ $this->formatNumber($totals['net_book_value']) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

        @else
            <!-- Journal Entries View -->
            <div class="space-y-6">
                <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-yellow-700">
                                Review the journal entries below before posting. Once posted, these entries will be recorded in the general ledger.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Journal Entries Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Entry #</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Account Name</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Account Code</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Debit</th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Credit</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider border border-gray-300">Description</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($journalEntries as $entry)
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-900 border border-gray-300">{{ $entry['entry_number'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border border-gray-300">{{ $entry['account_name'] }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border border-gray-300">{{ $entry['account_code'] }}</td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900 border border-gray-300">
                                        {{ $entry['debit'] > 0 ? $this->formatNumber($entry['debit']) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right text-gray-900 border border-gray-300">
                                        {{ $entry['credit'] > 0 ? $this->formatNumber($entry['credit']) : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-900 border border-gray-300">{{ $entry['description'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-3 text-center text-gray-500 border border-gray-300">No journal entries to display</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if(count($journalEntries) > 0)
                            <tfoot>
                                <tr class="bg-gray-100 font-bold">
                                    <td colspan="3" class="px-4 py-3 text-sm border border-gray-300">TOTAL</td>
                                    <td class="px-4 py-3 text-sm text-right border border-gray-300">
                                        {{ $this->formatNumber(collect($journalEntries)->sum('debit')) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm text-right border border-gray-300">
                                        {{ $this->formatNumber(collect($journalEntries)->sum('credit')) }}
                                    </td>
                                    <td class="px-4 py-3 text-sm border border-gray-300"></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>

                <!-- Post Depreciation Button -->
                @if(count($journalEntries) > 0)
                    <div class="flex justify-end mt-4">
                        <button wire:click="runDepreciation" 
                                onclick="return confirm('Are you sure you want to post these depreciation entries to the general ledger?')"
                                class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" 
                                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Post Depreciation to General Ledger
                        </button>
                    </div>
                @endif
            </div>
        @endif
    </div>

    <!-- Footer Section -->
    <div class="border-t border-gray-200 px-6 py-4 bg-gray-50">
        <div class="flex justify-between items-center text-sm text-gray-600">
            <div>
                <span class="font-medium">Report Generated:</span> {{ now()->format('d/m/Y H:i') }}
            </div>
            <div>
                <span class="font-medium">Period:</span> {{ ucfirst($depreciationPeriod) }} - 
                @if($depreciationPeriod === 'monthly')
                    {{ date('F Y', mktime(0, 0, 0, $selectedMonth, 1, $selectedYear)) }}
                @elseif($depreciationPeriod === 'quarterly')
                    Q{{ ceil($selectedMonth / 3) }} {{ $selectedYear }}
                @else
                    Year {{ $selectedYear }}
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Notification Messages -->
@if (session()->has('message'))
    <div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg">
        {{ session('message') }}
    </div>
@endif

@if (session()->has('error'))
    <div class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg">
        {{ session('error') }}
    </div>
@endif

