<div class="p-6">
    @if($member)
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">Member Exit Calculation</h2>
            <p class="text-gray-600">Member: <span class="font-semibold">{{ $member->first_name }} {{ $member->last_name }}</span> ({{ $member->client_number }})</p>
        </div>

        <!-- Exit Amount Summary Card -->
        <div class="bg-gradient-to-r from-purple-50 to-purple-100 rounded-lg p-6 border border-purple-200 mb-6">
            <div class="text-center">
                <div class="text-sm text-purple-700 mb-2">Final Exit Amount</div>
                <div class="text-4xl font-bold text-purple-900">
                    {{ number_format($exitCalculation['exit_amount'] ?? 0, 2) }}
                </div>
                <div class="text-xs text-purple-600 mt-1">
                    @if(($exitCalculation['exit_amount'] ?? 0) > 0)
                        Member will receive this amount
                    @elseif(($exitCalculation['exit_amount'] ?? 0) < 0)
                        Member owes this amount
                    @else
                        No settlement amount
                    @endif
                </div>
            </div>
        </div>

        <!-- Calculation Breakdown -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Credits Section -->
            <div class="bg-green-50 rounded-lg p-4 border border-green-200">
                <h3 class="text-lg font-semibold text-green-800 mb-4">Credits (+)</h3>
                
                <!-- Dividends -->
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                        <div>
                            <div class="text-sm font-medium text-green-800">Dividends</div>
                            <div class="text-xs text-green-600">{{ $exitCalculation['dividends_count'] ?? 0 }} records</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-green-900">{{ number_format($exitCalculation['total_dividends'] ?? 0, 2) }}</div>
                    </div>
                </div>

                <!-- Interest on Savings -->
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                        <div>
                            <div class="text-sm font-medium text-green-800">Interest on Savings</div>
                            <div class="text-xs text-green-600">{{ $exitCalculation['interest_records_count'] ?? 0 }} records</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-green-900">{{ number_format($exitCalculation['total_interest'] ?? 0, 2) }}</div>
                    </div>
                </div>

                <!-- Accounts Balance -->
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-green-500 rounded-full mr-3"></div>
                        <div>
                            <div class="text-sm font-medium text-green-800">Accounts Balance</div>
                            <div class="text-xs text-green-600">{{ $exitCalculation['accounts_count'] ?? 0 }} accounts</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-green-900">{{ number_format($exitCalculation['total_accounts_balance'] ?? 0, 2) }}</div>
                    </div>
                </div>

                <!-- Total Credits -->
                <div class="border-t border-green-300 pt-3 mt-3">
                    <div class="flex justify-between items-center">
                        <div class="text-sm font-semibold text-green-800">Total Credits</div>
                        <div class="text-lg font-bold text-green-900">
                            {{ number_format(($exitCalculation['total_dividends'] ?? 0) + ($exitCalculation['total_interest'] ?? 0) + ($exitCalculation['total_accounts_balance'] ?? 0), 2) }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Debits Section -->
            <div class="bg-red-50 rounded-lg p-4 border border-red-200">
                <h3 class="text-lg font-semibold text-red-800 mb-4">Debits (-)</h3>
                
                <!-- Loan Balance -->
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-3"></div>
                        <div>
                            <div class="text-sm font-medium text-red-800">Loan Account Balance</div>
                            <div class="text-xs text-red-600">{{ $exitCalculation['loans_count'] ?? 0 }} loans</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-red-900">{{ number_format($exitCalculation['total_loan_balance'] ?? 0, 2) }}</div>
                    </div>
                </div>

                <!-- Unpaid Bills -->
                <div class="flex justify-between items-center mb-3">
                    <div class="flex items-center">
                        <div class="w-3 h-3 bg-red-500 rounded-full mr-3"></div>
                        <div>
                            <div class="text-sm font-medium text-red-800">Unpaid Control Numbers</div>
                            <div class="text-xs text-red-600">{{ $exitCalculation['unpaid_bills_count'] ?? 0 }} bills</div>
                        </div>
                    </div>
                    <div class="text-right">
                        <div class="text-sm font-bold text-red-900">{{ number_format($exitCalculation['total_unpaid_bills'] ?? 0, 2) }}</div>
                    </div>
                </div>

                <!-- Total Debits -->
                <div class="border-t border-red-300 pt-3 mt-3">
                    <div class="flex justify-between items-center">
                        <div class="text-sm font-semibold text-red-800">Total Debits</div>
                        <div class="text-lg font-bold text-red-900">
                            {{ number_format(($exitCalculation['total_loan_balance'] ?? 0) + ($exitCalculation['total_unpaid_bills'] ?? 0), 2) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Formula Display -->
        <div class="bg-gray-50 rounded-lg p-4 border border-gray-200 mb-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-3">Calculation Formula</h3>
            <div class="text-sm text-gray-700 font-mono">
                Exit Amount = Dividends + Interest on Savings + Accounts Balance - Loan Balance - Unpaid Bills
            </div>
            <div class="text-sm text-gray-600 mt-2">
                {{ number_format($exitCalculation['total_dividends'] ?? 0, 2) }} + 
                {{ number_format($exitCalculation['total_interest'] ?? 0, 2) }} + 
                {{ number_format($exitCalculation['total_accounts_balance'] ?? 0, 2) }} - 
                {{ number_format($exitCalculation['total_loan_balance'] ?? 0, 2) }} - 
                {{ number_format($exitCalculation['total_unpaid_bills'] ?? 0, 2) }} = 
                <span class="font-bold">{{ number_format($exitCalculation['exit_amount'] ?? 0, 2) }}</span>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <button type="button" wire:click="download()" class="hoverable text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-4 py-2 text-center inline-flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="white" viewBox="0 0 24 24" stroke-width="1.5" stroke="white" class="w-5 h-5 mr-2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" />
                    </svg>
                    Download Exit Document
                </button>
            </div>
            
            <div class="text-sm text-gray-500">
                Calculated on: {{ now()->format('M d, Y H:i:s') }}
            </div>
        </div>
    @else
        <div class="text-center py-8">
            <div class="text-gray-500 text-lg">No member selected for exit calculation</div>
            <div class="text-gray-400 text-sm mt-2">Please select a member to view their exit calculation</div>
        </div>
    @endif
</div>
