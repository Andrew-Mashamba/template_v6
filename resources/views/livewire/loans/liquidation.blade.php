<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold text-gray-900">Loan Liquidation</h2>
            <p class="mt-1 text-sm text-gray-600">Process full loan clearance and early settlements</p>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <!-- Search Type -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search By</label>
                <select wire:model="searchType" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="loan_id">Loan ID</option>
                    <option value="account_number">Account Number</option>
                    <option value="member_number">Member Number</option>
                    <option value="member_name">Member Name</option>
                </select>
            </div>
            
            <!-- Search Value -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    {{ $searchType == 'loan_id' ? 'Loan ID' : ($searchType == 'account_number' ? 'Account Number' : ($searchType == 'member_number' ? 'Member Number' : 'Member Name')) }}
                </label>
                <input type="text" wire:model.debounce.300ms="searchValue" 
                    placeholder="Enter search value..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
            </div>
            
            <!-- Branch Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Branch</label>
                <select wire:model="branchFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">All Branches</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <!-- Product Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Product</label>
                <select wire:model="productFilter" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                    <option value="">All Products</option>
                    @foreach($products as $product)
                        <option value="{{ $product->sub_product_id }}">{{ $product->sub_product_name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        
        <div class="mt-4 flex justify-end">
            <button wire:click="searchLoan" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                <svg class="h-4 w-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Search
            </button>
        </div>
    </div>

    <!-- Active Loans Table -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <!-- Table Header -->
        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
            <h3 class="text-lg font-semibold text-gray-900">Active Loans for Liquidation</h3>
        </div>
        
        <!-- Table -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Principal</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Arrears</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($loans as $loan)
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $loan->loan_id ?? 'N/A' }}</div>
                                <div class="text-xs text-gray-500">{{ $loan->loan_account_number ?? '' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($loan->client)
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $loan->client->first_name }} {{ $loan->client->last_name }}
                                    </div>
                                    <div class="text-xs text-gray-500">{{ $loan->client->client_number ?? '' }}</div>
                                @else
                                    <span class="text-sm text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $product = \App\Models\loan_sub_products::where('sub_product_id', $loan->loan_sub_product)->first();
                                @endphp
                                @if($product)
                                    <div class="text-sm text-gray-900">{{ $product->sub_product_name }}</div>
                                @else
                                    <span class="text-sm text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-gray-900">TZS {{ number_format($loan->principle ?? 0, 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-semibold text-blue-600">TZS {{ number_format($loan->outstanding_balance ?? 0, 2) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($loan->days_in_arrears > 0)
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                        {{ $loan->days_in_arrears }} days
                                    </span>
                                @else
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Current
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($loan->status == 'ACTIVE') bg-green-100 text-green-800
                                    @elseif($loan->status == 'PENDING') bg-yellow-100 text-yellow-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ $loan->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button wire:click="openLiquidationModal('{{ $loan->loan_id }}')" 
                                    class="inline-flex items-center px-3 py-1.5 bg-red-600 text-white text-xs font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                    <svg class="h-4 w-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l3-3m0 0l3 3m-3-3v12m0-12a9 9 0 110 18 9 9 0 010-18z"></path>
                                    </svg>
                                    Liquidate
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="text-gray-500 text-lg font-medium">No active loans found</p>
                                    <p class="text-gray-400 text-sm mt-1">Try adjusting your search or filters</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        @if($loans->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $loans->links() }}
            </div>
        @endif
    </div>

    <!-- Liquidation Modal -->
    @if($showLiquidationModal && $selectedLoan)
        <div class="fixed inset-0 z-50 overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity " wire:click="closeLiquidationModal"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all max-w-7xl mx-auto">
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                                    Loan Liquidation - {{ $selectedLoan->loan_id }}
                                </h3>
                                
                                <!-- Loan Details -->
                                <div class="bg-gray-50 rounded-lg p-4 mb-6">
                                    <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Client Name</p>
                                            <p class="mt-1 text-sm text-gray-900">
                                                {{ $selectedLoan->client ? $selectedLoan->client->first_name . ' ' . $selectedLoan->client->last_name : 'N/A' }}
                                            </p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Product</p>
                                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLoan->product_name ?? 'N/A' }}</p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Principal Amount</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-900">TZS {{ number_format($selectedLoan->principle ?? 0, 2) }}</p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Total Expected</p>
                                            <p class="mt-1 text-sm font-semibold text-gray-900">TZS {{ number_format($selectedLoan->total_expected ?? 0, 2) }}</p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Total Paid</p>
                                            <p class="mt-1 text-sm font-semibold text-green-600">TZS {{ number_format($selectedLoan->total_paid ?? 0, 2) }}</p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Outstanding Balance</p>
                                            <p class="mt-1 text-sm font-semibold text-red-600">TZS {{ number_format($selectedLoan->outstanding_balance ?? 0, 2) }}</p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Remaining Installments</p>
                                            <p class="mt-1 text-sm text-gray-900">{{ $selectedLoan->remaining_installments ?? 0 }} months</p>
                                        </div>
                                        
                                        <div>
                                            <p class="text-xs font-medium text-gray-500">Early Settlement Amount</p>
                                            <p class="mt-1 text-sm font-semibold text-blue-600">TZS {{ number_format($earlySettlementAmount, 2) }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Liquidation Form -->
                                <div class="space-y-4">
                                    <!-- Liquidation Amount -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Liquidation Amount *</label>
                                        <div class="mt-1 relative">
                                            <span class="absolute left-3 top-2 text-sm text-gray-500">TZS</span>
                                            <input type="number" wire:model="liquidationAmount" 
                                                class="pl-12 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                placeholder="0.00" step="0.01" min="0">
                                        </div>
                                        @error('liquidationAmount') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                        
                                        @if($earlySettlementAmount > 0 && $earlySettlementAmount < $selectedLoan->outstanding_balance)
                                            <button type="button" wire:click="applyEarlySettlement" 
                                                class="mt-2 text-xs text-blue-600 hover:text-blue-800 underline">
                                                Apply Early Settlement Amount (TZS {{ number_format($earlySettlementAmount, 2) }})
                                            </button>
                                        @endif
                                    </div>

                                    <!-- Payment Method -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Payment Method *</label>
                                        <select wire:model="paymentMethod" 
                                            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <option value="CASH">Cash</option>
                                            <option value="BANK">Bank Transfer</option>
                                            <option value="MOBILE">Mobile Money</option>
                                            <option value="INTERNAL">Internal Transfer</option>
                                        </select>
                                        @error('paymentMethod') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Dynamic Payment Fields -->
                                    @if($paymentMethod == 'BANK')
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Bank Name *</label>
                                                <select wire:model="bankName" 
                                                    class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                                    <option value="">Select Bank</option>
                                                    @foreach($banks as $code => $name)
                                                        <option value="{{ $code }}">{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Reference Number *</label>
                                                <input type="text" wire:model="bankReference" 
                                                    class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                    placeholder="Enter bank reference">
                                            </div>
                                        </div>
                                    @elseif($paymentMethod == 'MOBILE')
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Mobile Provider *</label>
                                                <select wire:model="mobileProvider" 
                                                    class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                                    <option value="">Select Provider</option>
                                                    @foreach($mobileProviders as $code => $name)
                                                        <option value="{{ $code }}">{{ $name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Mobile Number *</label>
                                                <input type="text" wire:model="mobileNumber" 
                                                    class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                    placeholder="0712345678">
                                            </div>
                                        </div>
                                    @elseif($paymentMethod == 'INTERNAL')
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Reference/Account</label>
                                            <input type="text" wire:model="paymentReference" 
                                                class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                placeholder="Enter reference or account">
                                        </div>
                                    @endif

                                    <!-- Liquidation Reason -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Liquidation Reason *</label>
                                        <select wire:model="liquidationReason" 
                                            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm">
                                            <option value="">Select Reason</option>
                                            <option value="Full Payment">Full Payment - Regular Completion</option>
                                            <option value="Early Settlement">Early Settlement</option>
                                            <option value="Refinancing">Refinancing</option>
                                            <option value="Write Off">Write Off</option>
                                            <option value="Legal Settlement">Legal Settlement</option>
                                            <option value="Insurance Claim">Insurance Claim</option>
                                            <option value="Other">Other</option>
                                        </select>
                                        @error('liquidationReason') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Penalty Waiver -->
                                    @if($liquidationAmount < ($selectedLoan->outstanding_balance ?? 0))
                                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                            <div class="flex items-start">
                                                <input type="checkbox" wire:model="penaltyWaived" 
                                                    class="mt-1 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                <div class="ml-3">
                                                    <label class="text-sm font-medium text-gray-700">Waive Penalty/Interest</label>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        Amount to be waived: TZS {{ number_format(($selectedLoan->outstanding_balance ?? 0) - $liquidationAmount, 2) }}
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            @if($penaltyWaived)
                                                <div class="mt-3">
                                                    <label class="block text-xs font-medium text-gray-700">Waiver Reason</label>
                                                    <textarea wire:model="waiverReason" rows="2"
                                                        class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                                        placeholder="Enter reason for waiver..."></textarea>
                                                </div>
                                            @endif
                                        </div>
                                    @endif

                                    <!-- Additional Notes -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Additional Notes</label>
                                        <textarea wire:model="liquidationNotes" rows="3"
                                            class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm"
                                            placeholder="Enter any additional notes..."></textarea>
                                    </div>

                                    <!-- Confirmation -->
                                    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <h4 class="text-sm font-medium text-red-900 mb-2">Confirm Liquidation</h4>
                                        <p class="text-xs text-red-700 mb-3">
                                            This action will close the loan permanently. Please enter the confirmation code to proceed.
                                        </p>
                                        <div class="flex items-center space-x-4">
                                            <div>
                                                <label class="block text-xs font-medium text-gray-700">Confirmation Code</label>
                                                <p class="mt-1 text-lg font-bold text-red-600">{{ $generatedCode }}</p>
                                            </div>
                                            <div class="flex-1">
                                                <label class="block text-xs font-medium text-gray-700">Enter Code *</label>
                                                <input type="text" wire:model="confirmationCode" 
                                                    class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 text-sm uppercase"
                                                    placeholder="Enter confirmation code">
                                                @error('confirmationCode') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="button" wire:click="processLiquidation" 
                            @if($confirmationCode !== $generatedCode) disabled @endif
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 @if($confirmationCode === $generatedCode) bg-red-600 hover:bg-red-700 @else bg-gray-400 cursor-not-allowed @endif text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <svg class="h-5 w-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l3-3m0 0l3 3m-3-3v12m0-12a9 9 0 110 18 9 9 0 010-18z"></path>
                            </svg>
                            Process Liquidation
                        </button>
                        <button type="button" wire:click="closeLiquidationModal" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>