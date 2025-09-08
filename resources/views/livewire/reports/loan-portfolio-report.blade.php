<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Loan Portfolio Report</h1>
                    <p class="mt-1 text-sm text-gray-500">Comprehensive analysis of loan portfolio performance and risk</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                        Operational
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Selection -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="endDate" class="block text-sm font-medium text-gray-700 mb-2">Report Date</label>
                    <input type="date" 
                           wire:model="reportEndDate" 
                           id="endDate"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="flex items-end">
                    <button wire:click="generateReport" 
                            wire:loading.attr="disabled"
                            wire:target="generateReport"
                            class="w-full bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                        <span wire:loading.remove wire:target="generateReport">Generate Report</span>
                        <span wire:loading wire:target="generateReport">Generating...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error/Success Messages -->
    @if($errorMessage)
        <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-800">{{ $errorMessage }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($successMessage)
        <div class="bg-green-50 border border-green-200 rounded-md p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-800">{{ $successMessage }}</p>
                </div>
            </div>
        </div>
    @endif

    <!-- Report Content -->
    @if($reportData)
        <div class="bg-white shadow rounded-lg">
            <!-- Report Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="text-center">
                    <h2 class="text-xl font-bold text-gray-900">LOAN PORTFOLIO REPORT</h2>
                    <p class="text-sm text-gray-600 mt-1">
                        As at {{ $reportData['period']['end_date'] }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">(All amounts in Tanzanian Shillings)</p>
                </div>
            </div>

            <!-- Portfolio Summary -->
            <div class="px-6 py-4">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Portfolio Summary</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-blue-600">Total Portfolio</div>
                        <div class="text-2xl font-bold text-blue-900">{{ number_format($reportData['portfolio_summary']['total_portfolio'], 2) }}</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-green-600">Number of Loans</div>
                        <div class="text-2xl font-bold text-green-900">{{ number_format($reportData['portfolio_summary']['number_of_loans']) }}</div>
                    </div>
                    <div class="bg-purple-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-purple-600">Average Loan Size</div>
                        <div class="text-2xl font-bold text-purple-900">{{ number_format($reportData['portfolio_summary']['average_loan_size'], 2) }}</div>
                    </div>
                    <div class="bg-orange-50 p-4 rounded-lg">
                        <div class="text-sm font-medium text-orange-600">Largest Loan</div>
                        <div class="text-2xl font-bold text-orange-900">{{ number_format($reportData['portfolio_summary']['largest_loan'], 2) }}</div>
                    </div>
                </div>

                <!-- Portfolio by Type -->
                @if(!empty($reportData['portfolio_by_type']))
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Portfolio by Loan Type</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Percentage</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($reportData['portfolio_by_type'] as $type => $amount)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $type }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($amount, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $reportData['portfolio_summary']['total_portfolio'] > 0 ? number_format(($amount / $reportData['portfolio_summary']['total_portfolio']) * 100, 1) : 0 }}%
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                <!-- Detailed Loan List -->
                @if(!empty($reportData['loan_details']))
                    <div class="mb-8">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Detailed Loan Portfolio</h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client Number</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding Balance</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Interest Rate</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($reportData['loan_details'] as $loan)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $loan['loan_id'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $loan['client_number'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $loan['business_name'] }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $loan['category'] }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($loan['outstanding_balance'], 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ number_format($loan['interest_rate'], 2) }}%</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    {{ $loan['status'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Export Actions -->
            <div class="px-6 py-4 border-t border-gray-200 bg-gray-50">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-500">
                        Generated on {{ now()->format('F d, Y \a\t g:i A') }}
                    </div>
                    <div class="flex space-x-3">
                        <button wire:click="exportReport('pdf')" 
                                wire:loading.attr="disabled"
                                wire:target="exportReport"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="exportReport">Export PDF</span>
                            <span wire:loading wire:target="exportReport">Exporting...</span>
                        </button>
                        <button wire:click="exportReport('excel')" 
                                wire:loading.attr="disabled"
                                wire:target="exportReport"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <span wire:loading.remove wire:target="exportReport">Export Excel</span>
                            <span wire:loading wire:target="exportReport">Exporting...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @else
        <!-- Empty State -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-12 text-center">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">No Report Generated</h3>
                <p class="mt-1 text-sm text-gray-500">Select a date and click "Generate Report" to create the Loan Portfolio Report.</p>
            </div>
        </div>
    @endif
</div>
