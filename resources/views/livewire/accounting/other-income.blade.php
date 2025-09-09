<div>
    {{-- Header Section --}}
    <div class="p-4">
        <div class="flex justify-between items-center mb-4">
            <h2 class="text-2xl font-bold text-gray-800">Other Income Management</h2>
            <button wire:click="openCreateModal" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                <i class="fas fa-plus mr-2"></i> Record New Income
            </button>
        </div>

        {{-- Statistics Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Income</p>
                        <p class="text-2xl font-bold text-gray-800">{{ number_format($totalIncome, 2) }}</p>
                    </div>
                    <div class="text-green-500">
                        <i class="fas fa-dollar-sign text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">This Month</p>
                        <p class="text-2xl font-bold text-gray-800">{{ number_format($totalThisMonth, 2) }}</p>
                    </div>
                    <div class="text-blue-500">
                        <i class="fas fa-calendar-alt text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">This Year</p>
                        <p class="text-2xl font-bold text-gray-800">{{ number_format($totalThisYear, 2) }}</p>
                    </div>
                    <div class="text-purple-500">
                        <i class="fas fa-chart-line text-3xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-4">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Avg Monthly</p>
                        <p class="text-2xl font-bold text-gray-800">{{ number_format($averageMonthlyIncome, 2) }}</p>
                    </div>
                    <div class="text-orange-500">
                        <i class="fas fa-chart-bar text-3xl"></i>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tabs --}}
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8">
                <button wire:click="$set('activeTab', 'overview')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'overview' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Overview
                </button>
                <button wire:click="$set('activeTab', 'income_list')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'income_list' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Income Records
                </button>
                <button wire:click="$set('activeTab', 'categories')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'categories' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Categories
                </button>
                <button wire:click="$set('activeTab', 'recurring')" 
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'recurring' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                    Recurring Income
                </button>
            </nav>
        </div>

        {{-- Tab Content --}}
        <div class="mt-6">
            @if($activeTab === 'overview')
                {{-- Overview Tab --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Income by Category</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($incomeByCategory as $category => $amount)
                            <div class="flex justify-between items-center p-3 bg-gray-50 rounded">
                                <span class="font-medium">{{ ucwords(str_replace('_', ' ', $category)) }}</span>
                                <span class="font-bold">{{ number_format($amount, 2) }}</span>
                            </div>
                        @endforeach
                    </div>

                    @if($incomeGrowthRate != 0)
                        <div class="mt-6 p-4 bg-blue-50 rounded">
                            <p class="text-sm text-gray-600">Income Growth Rate</p>
                            <p class="text-2xl font-bold {{ $incomeGrowthRate > 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ $incomeGrowthRate > 0 ? '+' : '' }}{{ number_format($incomeGrowthRate, 2) }}%
                            </p>
                        </div>
                    @endif
                </div>

            @elseif($activeTab === 'income_list')
                {{-- Income Records Tab --}}
                <div class="bg-white rounded-lg shadow">
                    {{-- Filters --}}
                    <div class="p-4 border-b">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" wire:model.debounce.300ms="search" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Search income...">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                                <select wire:model="categoryFilter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="all">All Categories</option>
                                    @foreach($incomeCategories as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">From Date</label>
                                <input type="date" wire:model="dateFrom" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">To Date</label>
                                <input type="date" wire:model="dateTo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        </div>
                    </div>

                    {{-- Table --}}
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receipt #</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($incomeRecords as $income)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ \Carbon\Carbon::parse($income->income_date)->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            {{ $income->receipt_number }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            {{ ucwords(str_replace('_', ' ', $income->income_category)) }}
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            {{ $income->income_source }}
                                        </td>
                                        <td class="px-6 py-4 text-sm">
                                            {{ Str::limit($income->description, 50) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-medium">
                                            {{ number_format($income->net_amount, 2) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                {{ $income->status === 'received' ? 'bg-green-100 text-green-800' : 
                                                   ($income->status === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst($income->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                            <button wire:click="edit({{ $income->id }})" class="text-blue-600 hover:text-blue-900 mr-2">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button wire:click="showDetails({{ $income->id }})" class="text-green-600 hover:text-green-900 mr-2">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button wire:click="delete({{ $income->id }})" 
                                                onclick="return confirm('Are you sure you want to delete this income record?')"
                                                class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                            No income records found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{-- Pagination --}}
                    <div class="px-6 py-4">
                        {{ $incomeRecords->links() }}
                    </div>
                </div>

            @elseif($activeTab === 'categories')
                {{-- Categories Tab --}}
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-4">Income Categories</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($incomeCategories as $key => $category)
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <h4 class="font-semibold text-gray-800">{{ $category }}</h4>
                                <p class="text-sm text-gray-600 mt-1">Code: {{ $key }}</p>
                                <div class="mt-2">
                                    <span class="text-xs text-gray-500">Total this year:</span>
                                    <p class="font-bold text-lg">{{ number_format($incomeByCategory[$key] ?? 0, 2) }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

            @elseif($activeTab === 'recurring')
                {{-- Recurring Income Tab --}}
                <div class="bg-white rounded-lg shadow">
                    <div class="p-4 border-b">
                        <h3 class="text-lg font-semibold">Recurring Income Setup</h3>
                    </div>
                    <div class="p-6">
                        @php
                            $recurringIncome = \DB::table('other_income')
                                ->where('recurring', true)
                                ->orderBy('income_date', 'desc')
                                ->get();
                        @endphp

                        @if($recurringIncome->count() > 0)
                            <div class="space-y-4">
                                @foreach($recurringIncome as $recurring)
                                    <div class="border rounded-lg p-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <h4 class="font-semibold">{{ $recurring->income_source }}</h4>
                                                <p class="text-sm text-gray-600">{{ $recurring->description }}</p>
                                                <div class="mt-2 space-y-1">
                                                    <p class="text-sm"><span class="font-medium">Amount:</span> {{ number_format($recurring->amount, 2) }}</p>
                                                    <p class="text-sm"><span class="font-medium">Frequency:</span> {{ ucfirst($recurring->recurring_frequency) }}</p>
                                                    <p class="text-sm"><span class="font-medium">Next Date:</span> {{ \Carbon\Carbon::parse($recurring->income_date)->format('d/m/Y') }}</p>
                                                    @if($recurring->recurring_end_date)
                                                        <p class="text-sm"><span class="font-medium">End Date:</span> {{ \Carbon\Carbon::parse($recurring->recurring_end_date)->format('d/m/Y') }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex space-x-2">
                                                <button wire:click="edit({{ $recurring->id }})" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button wire:click="cancelRecurring({{ $recurring->id }})" class="text-red-600 hover:text-red-900">
                                                    <i class="fas fa-times-circle"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-gray-500 text-center py-8">No recurring income setup found</p>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Create/Edit Modal --}}
    @if($showCreateModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">{{ $editMode ? 'Edit' : 'Record' }} Other Income</h3>
                    <button wire:click="$set('showCreateModal', false)" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form wire:submit.prevent="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Income Date --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Income Date <span class="text-red-500">*</span></label>
                            <input type="date" wire:model="income_date" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('income_date') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Income Category --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category <span class="text-red-500">*</span></label>
                            <select wire:model="income_category" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Category</option>
                                @foreach($incomeCategories as $key => $value)
                                    <option value="{{ $key }}">{{ $value }}</option>
                                @endforeach
                            </select>
                            @error('income_category') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Income Source --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Income Source <span class="text-red-500">*</span></label>
                            <input type="text" wire:model="income_source" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="e.g., ABC Company">
                            @error('income_source') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Amount --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Amount <span class="text-red-500">*</span></label>
                            <input type="number" step="0.01" wire:model="amount" wire:change="calculateNetAmount" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="0.00">
                            @error('amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Tax Amount --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tax Amount</label>
                            <input type="number" step="0.01" wire:model="tax_amount" wire:change="calculateNetAmount"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="0.00">
                            @error('tax_amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Net Amount --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Net Amount</label>
                            <input type="number" step="0.01" wire:model="net_amount" readonly
                                class="w-full rounded-md border-gray-300 bg-gray-100 shadow-sm"
                                placeholder="0.00">
                        </div>

                        {{-- Payment Method --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Payment Method <span class="text-red-500">*</span></label>
                            <select wire:model="payment_method" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cash">Cash</option>
                                <option value="cheque">Cheque</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="other">Other</option>
                            </select>
                            @error('payment_method') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Account Selection - Corrected Flow --}}
                    <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 mb-6 mt-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Account Selection</h3>
                        <p class="text-sm text-gray-600 mb-4">Select where to create the income account and the other account for double-entry posting</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="parent_account_number" class="block text-sm font-medium text-gray-700 mb-2">
                                    Parent Account (Create Income Under) *
                                </label>
                                <select wire:model="parent_account_number" id="parent_account_number" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <option value="">-- Select Parent Account --</option>
                                    @foreach($parentAccounts as $account)
                                        <option value="{{ $account->account_number }}">
                                            {{ $account->account_number }} - {{ $account->account_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">New income account will be created under this parent</p>
                            </div>
                            
                            <div>
                                <label for="other_account_id" class="block text-sm font-medium text-gray-700 mb-2">
                                    Other Account (Cash/Bank) *
                                </label>
                                <select wire:model="other_account_id" id="other_account_id" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                    <option value="">-- Select Cash/Bank Account --</option>
                                    @foreach($otherAccounts as $account)
                                        <option value="{{ $account->internal_mirror_account_number }}">
                                            {{ $account->bank_name }} - {{ $account->account_number }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Account to be debited (Cash/Bank receipt)</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        {{-- Bank Account --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Bank Account <span class="text-red-500">*</span></label>
                            <select wire:model="bank_account_id" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Account</option>
                                @foreach($bankAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_name }} - {{ $account->account_number }}</option>
                                @endforeach
                            </select>
                            @error('bank_account_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Income Account --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Income Account <span class="text-red-500">*</span></label>
                            <select wire:model="income_account_id" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select Account</option>
                                @foreach($incomeAccounts as $account)
                                    <option value="{{ $account->id }}">{{ $account->account_name }} - {{ $account->account_number }}</option>
                                @endforeach
                            </select>
                            @error('income_account_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Reference Number --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label>
                            <input type="text" wire:model="reference_number"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="e.g., INV-001">
                            @error('reference_number') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Receipt Number --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Number</label>
                            <input type="text" wire:model="receipt_number" readonly
                                class="w-full rounded-md border-gray-300 bg-gray-100 shadow-sm">
                        </div>

                        {{-- Received From --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Received From</label>
                            <input type="text" wire:model="received_from"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Person/Company name">
                            @error('received_from') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Description --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description <span class="text-red-500">*</span></label>
                            <textarea wire:model="description" rows="3" required
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Enter income description..."></textarea>
                            @error('description') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        {{-- Recurring Income --}}
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" wire:model="recurring" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <span class="ml-2 text-sm font-medium text-gray-700">This is recurring income</span>
                            </label>
                        </div>

                        @if($recurring)
                            {{-- Recurring Frequency --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Frequency</label>
                                <select wire:model="recurring_frequency"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="monthly">Monthly</option>
                                    <option value="quarterly">Quarterly</option>
                                    <option value="semi_annually">Semi-Annually</option>
                                    <option value="annually">Annually</option>
                                </select>
                            </div>

                            {{-- Recurring End Date --}}
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">End Date (Optional)</label>
                                <input type="date" wire:model="recurring_end_date"
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                        @endif

                        {{-- Notes --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Notes</label>
                            <textarea wire:model="notes" rows="2"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Additional notes (optional)"></textarea>
                        </div>

                        {{-- Receipt Attachment --}}
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Receipt Attachment</label>
                            <input type="file" wire:model="receipt_attachment" accept=".pdf,.jpg,.jpeg,.png"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            @error('receipt_attachment') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            @if($receipt_attachment)
                                <p class="text-sm text-gray-600 mt-1">File: {{ $receipt_attachment->getClientOriginalName() }}</p>
                            @endif
                        </div>
                    </div>

                    {{-- Form Actions --}}
                    <div class="flex justify-end space-x-3 mt-6">
                        <button type="button" wire:click="$set('showCreateModal', false)"
                            class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                            {{ $editMode ? 'Update' : 'Save' }} Income
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Details Modal --}}
    @if($showDetailsModal)
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold">Income Details</h3>
                    <button wire:click="$set('showDetailsModal', false)" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                @if($selectedIncome)
                    <div class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-gray-600">Receipt Number</p>
                                <p class="font-medium">{{ $selectedIncome->receipt_number }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Date</p>
                                <p class="font-medium">{{ \Carbon\Carbon::parse($selectedIncome->income_date)->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Category</p>
                                <p class="font-medium">{{ ucwords(str_replace('_', ' ', $selectedIncome->income_category)) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Source</p>
                                <p class="font-medium">{{ $selectedIncome->income_source }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Amount</p>
                                <p class="font-medium">{{ number_format($selectedIncome->amount, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Tax</p>
                                <p class="font-medium">{{ number_format($selectedIncome->tax_amount, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Net Amount</p>
                                <p class="font-medium text-green-600">{{ number_format($selectedIncome->net_amount, 2) }}</p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Payment Method</p>
                                <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $selectedIncome->payment_method)) }}</p>
                            </div>
                            @if($selectedIncome->reference_number)
                                <div>
                                    <p class="text-sm text-gray-600">Reference Number</p>
                                    <p class="font-medium">{{ $selectedIncome->reference_number }}</p>
                                </div>
                            @endif
                            @if($selectedIncome->received_from)
                                <div>
                                    <p class="text-sm text-gray-600">Received From</p>
                                    <p class="font-medium">{{ $selectedIncome->received_from }}</p>
                                </div>
                            @endif
                        </div>

                        <div>
                            <p class="text-sm text-gray-600">Description</p>
                            <p class="font-medium">{{ $selectedIncome->description }}</p>
                        </div>

                        @if($selectedIncome->notes)
                            <div>
                                <p class="text-sm text-gray-600">Notes</p>
                                <p class="font-medium">{{ $selectedIncome->notes }}</p>
                            </div>
                        @endif

                        @if($selectedIncome->recurring)
                            <div class="p-3 bg-blue-50 rounded">
                                <p class="text-sm font-medium text-blue-800">Recurring Income</p>
                                <p class="text-sm text-blue-600">
                                    Frequency: {{ ucfirst($selectedIncome->recurring_frequency) }}
                                    @if($selectedIncome->recurring_end_date)
                                        | Ends: {{ \Carbon\Carbon::parse($selectedIncome->recurring_end_date)->format('d/m/Y') }}
                                    @endif
                                </p>
                            </div>
                        @endif
                    </div>
                @endif

                <div class="flex justify-end mt-6">
                    <button wire:click="$set('showDetailsModal', false)"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- Success/Error Messages --}}
    @if (session()->has('message'))
        <div class="fixed bottom-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed bottom-4 right-4 bg-red-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
            {{ session('error') }}
        </div>
    @endif
</div>

@push('scripts')
<script>
    window.addEventListener('alert', event => {
        alert(event.detail.message);
    });

    // Auto-hide messages after 3 seconds
    setTimeout(function() {
        @this.call('clearMessages');
    }, 3000);
</script>
@endpush