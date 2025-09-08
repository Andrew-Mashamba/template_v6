<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Report Header -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Statement of Financial Position</h1>
                    <p class="mt-1 text-sm text-gray-500">Comprehensive balance sheet showing assets, liabilities, and equity positions</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        BOT, IFRS
                    </span>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                        Regulatory
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Actions -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Report Actions</h3>
        </div>
        <div class="px-6 py-4">
            <div class="flex flex-wrap gap-4">
                <button wire:click="generateStatement"
                        wire:loading.attr="disabled"
                        wire:target="generateStatement"
                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                    <svg wire:loading.remove wire:target="generateStatement" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <svg wire:loading wire:target="generateStatement" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span wire:loading.remove wire:target="generateStatement">Generate Report</span>
                    <span wire:loading wire:target="generateStatement">Generating...</span>
                </button>

                @if($showStatementView && $statementData)
                    <button wire:click="exportStatement('pdf')"
                            wire:loading.attr="disabled"
                            wire:target="exportStatement"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="exportStatement" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <svg wire:loading wire:target="exportStatement" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="exportStatement">Export PDF</span>
                        <span wire:loading wire:target="exportStatement">Exporting...</span>
                    </button>

                    <button wire:click="exportStatement('excel')"
                            wire:loading.attr="disabled"
                            wire:target="exportStatement"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="exportStatement" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <svg wire:loading wire:target="exportStatement" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span wire:loading.remove wire:target="exportStatement">Export Excel</span>
                        <span wire:loading wire:target="exportStatement">Exporting...</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Report Content -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Report Content</h3>
        </div>
        <div class="px-6 py-8">
            @if($showStatementView && $statementData)
                <!-- Statement of Financial Position -->
                <div class="space-y-6">
                    <!-- Statement Header -->
                    <div class="text-center border-b pb-4">
                        <h2 class="text-2xl font-bold text-gray-900">STATEMENT OF FINANCIAL POSITION</h2>
                        <p class="text-lg text-gray-600 mt-2">As at {{ \Carbon\Carbon::parse($statementData['as_of_date'])->format('F d, Y') }}</p>
                        <p class="text-sm text-gray-500 mt-1">(All amounts in Tanzanian Shillings)</p>
                        @if(DB::table('general_ledger')->count() > 0 && DB::table('general_ledger')->count() <= 10)
                            <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                <p class="text-sm text-blue-800">
                                    <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                                    </svg>
                                    <strong>Demo Mode:</strong> This statement includes sample data for demonstration purposes. In a live system, this would show actual transaction data.
                                </p>
                            </div>
                        @endif
                    </div>

                    <!-- Statement Content -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                        <!-- Assets Section -->
                        <div class="space-y-4">
                            <h3 class="text-xl font-bold text-gray-900 border-b-2 border-blue-600 pb-2">ASSETS</h3>
                            
                            @foreach($statementData['assets']['categories'] as $categoryCode => $category)
                                <div class="space-y-2">
                                    <h4 class="text-lg font-semibold text-gray-800">{{ $category['name'] }}</h4>
                                    <div class="ml-4 space-y-1">
                                        @foreach($category['accounts'] as $account)
                                            <div class="flex justify-between items-center py-1">
                                                <span class="text-sm text-gray-700">{{ $account->account_name }}</span>
                                                <span class="text-sm font-mono text-gray-900">{{ number_format($account->current_balance, 2) }}</span>
                                            </div>
                                        @endforeach
                                        <div class="flex justify-between items-center py-2 border-t border-gray-200 font-semibold">
                                            <span class="text-sm text-gray-800">Subtotal</span>
                                            <span class="text-sm font-mono text-gray-900">{{ number_format($category['subtotal'], 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            
                            <div class="flex justify-between items-center py-3 border-t-2 border-blue-600 font-bold text-lg">
                                <span class="text-gray-900">TOTAL ASSETS</span>
                                <span class="font-mono text-gray-900">{{ number_format($statementData['assets']['total'], 2) }}</span>
                            </div>
                        </div>

                        <!-- Liabilities and Equity Section -->
                        <div class="space-y-4">
                            <!-- Liabilities -->
                            <div class="space-y-4">
                                <h3 class="text-xl font-bold text-gray-900 border-b-2 border-red-600 pb-2">LIABILITIES</h3>
                                
                                @foreach($statementData['liabilities']['categories'] as $categoryCode => $category)
                                    <div class="space-y-2">
                                        <h4 class="text-lg font-semibold text-gray-800">{{ $category['name'] }}</h4>
                                        <div class="ml-4 space-y-1">
                                            @foreach($category['accounts'] as $account)
                                                <div class="flex justify-between items-center py-1">
                                                    <span class="text-sm text-gray-700">{{ $account->account_name }}</span>
                                                    <span class="text-sm font-mono text-gray-900">{{ number_format($account->current_balance, 2) }}</span>
                                                </div>
                                            @endforeach
                                            <div class="flex justify-between items-center py-2 border-t border-gray-200 font-semibold">
                                                <span class="text-sm text-gray-800">Subtotal</span>
                                                <span class="text-sm font-mono text-gray-900">{{ number_format($category['subtotal'], 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                
                                <div class="flex justify-between items-center py-3 border-t-2 border-red-600 font-bold text-lg">
                                    <span class="text-gray-900">TOTAL LIABILITIES</span>
                                    <span class="font-mono text-gray-900">{{ number_format($statementData['liabilities']['total'], 2) }}</span>
                                </div>
                            </div>

                            <!-- Equity -->
                            <div class="space-y-4">
                                <h3 class="text-xl font-bold text-gray-900 border-b-2 border-green-600 pb-2">EQUITY</h3>
                                
                                @foreach($statementData['equity']['categories'] as $categoryCode => $category)
                                    <div class="space-y-2">
                                        <h4 class="text-lg font-semibold text-gray-800">{{ $category['name'] }}</h4>
                                        <div class="ml-4 space-y-1">
                                            @foreach($category['accounts'] as $account)
                                                <div class="flex justify-between items-center py-1">
                                                    <span class="text-sm text-gray-700">{{ $account->account_name }}</span>
                                                    <span class="text-sm font-mono text-gray-900">{{ number_format($account->current_balance, 2) }}</span>
                                                </div>
                                            @endforeach
                                            <div class="flex justify-between items-center py-2 border-t border-gray-200 font-semibold">
                                                <span class="text-sm text-gray-800">Subtotal</span>
                                                <span class="text-sm font-mono text-gray-900">{{ number_format($category['subtotal'], 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                                
                                <div class="flex justify-between items-center py-3 border-t-2 border-green-600 font-bold text-lg">
                                    <span class="text-gray-900">TOTAL EQUITY</span>
                                    <span class="font-mono text-gray-900">{{ number_format($statementData['equity']['total'], 2) }}</span>
                                </div>
                            </div>

                            <!-- Total Liabilities and Equity -->
                            <div class="flex justify-between items-center py-3 border-t-2 border-gray-400 font-bold text-lg">
                                <span class="text-gray-900">TOTAL LIABILITIES & EQUITY</span>
                                <span class="font-mono text-gray-900">{{ number_format($statementData['totals']['total_liabilities_and_equity'], 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Balance Check -->
                    <div class="mt-8 p-4 rounded-lg {{ $statementData['totals']['is_balanced'] ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }}">
                        <div class="flex items-center justify-center">
                            @if($statementData['totals']['is_balanced'])
                                <svg class="w-6 h-6 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-green-800 font-semibold">Statement is Balanced: Assets = Liabilities + Equity</span>
                            @else
                                <svg class="w-6 h-6 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                                <span class="text-red-800 font-semibold">Statement is NOT Balanced - Difference: {{ number_format($statementData['totals']['difference'], 2) }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <!-- Default Report Preview -->
                <div class="text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Report Preview</h3>
                    <p class="mt-1 text-sm text-gray-500">Click "Generate Report" to view the report content.</p>
                    <div class="mt-6">
                        <button wire:click="generateStatement" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Generate Report
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <!-- Success/Error Messages -->
    @if($successMessage)
        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <p class="text-sm text-green-800">{{ $successMessage }}</p>
        </div>
    @endif

    @if($errorMessage)
        <div class="mt-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <p class="text-sm text-red-800">{{ $errorMessage }}</p>
        </div>
    @endif
</div>