<div class="w-full mt-8 p-4 bg-white rounded-lg shadow">
    <h2 class="text-xl font-semibold mb-4">Institution Accounts Setup</h2>
    
    {{-- Core Institution Accounts --}}
    <div class="mb-8">
        <h3 class="text-lg font-medium mb-4 text-gray-800 border-b border-gray-200 pb-2">Core Institution Accounts</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Operations Account -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Operations Account</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="operations_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account</option>
                        @foreach($this->getAccountsForCategory('1000', '1000') as $account)
                            <option value="{{ $account->account_number}}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="operations_account" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('operations_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Mandatory Shares Account -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Mandatory Shares Account</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="mandatory_shares_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account</option>
                        @foreach($this->getAccountsForCategory('3000', '3000') as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="mandatory_shares_account" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('mandatory_shares_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Mandatory Savings Account -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Mandatory Savings Account</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="mandatory_savings_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account</option>
                        @foreach($this->getAccountsForCategory('2000', '3000') as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="mandatory_savings_account" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('mandatory_savings_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Mandatory Deposits Account -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Mandatory Deposits Account</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="mandatory_deposits_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account</option>
                        @foreach($this->getAccountsForCategory('2000', '2100') as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="mandatory_deposits_account" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('mandatory_deposits_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    {{-- Main Cash Management Accounts --}}
    <div class="mb-8">
        <h3 class="text-lg font-medium mb-4 text-gray-800 border-b border-gray-200 pb-2">Main Cash Management Accounts</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <!-- Main Vaults Account -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Main Vaults Account</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="main_vaults_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account</option>
                        @foreach($this->getAccountsForCategory('1000', '1999') as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="main_vaults_account" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('main_vaults_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Main Till Account -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Main Till Account</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="main_till_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account</option>
                        @foreach($this->getAccountsForCategory('1000', '1999') as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="main_till_account" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('main_till_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Main Petty Cash Account -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Main Petty Cash Account</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="main_petty_cash_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account</option>
                        @foreach($this->getAccountsForCategory('1000', '1999') as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="main_petty_cash_account" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('main_petty_cash_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    {{-- Additional Institution Accounts --}}
    <div class="mb-8">
        <h3 class="text-lg font-medium mb-4 text-gray-800 border-b border-gray-200 pb-2">Additional Institution Accounts</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Members External Loans Clearance -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Members External Loans Clearance</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="members_external_loans_crealance" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account (Optional)</option>
                        @foreach($this->getAccountsForCategory('2000', '5999') as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="members_external_loans_crealance" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('members_external_loans_crealance')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Temporary Shares Holding Account -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Temporary Shares Holding Account</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="temp_shares_holding_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account (Optional)</option>
                        @foreach($this->getAccountsForCategory('2000', '5999') as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="temp_shares_holding_account" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('temp_shares_holding_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Property and Equipment Account -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Property and Equipment Account</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="property_and_equipment_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account (Optional)</option>
                        @foreach($this->getAccountsForCategory('1800', '1899') as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="property_and_equipment_account" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('property_and_equipment_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Depreciation Expense Account -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Depreciation Expense Account</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="depreciation_expense_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account (Optional)</option>
                        @foreach($this->getAccountsForCategory('4000', '4999') as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="depreciation_expense_account" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('depreciation_expense_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>

            <!-- Accumulated Depreciation Account -->
            <div class="p-4 border rounded-lg">
                <label class="block mb-2 text-sm font-medium text-gray-900">Accumulated Depreciation Account</label>
                <div class="relative">
                    <select wire:loading.attr="disabled" wire:model="accumulated_depreciation_account" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select Account (Optional)</option>
                        @foreach($this->getAccountsForCategory('2900', '2999') as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} ({{ $account->account_number }})
                            </option>
                        @endforeach
                    </select>
                    <div wire:loading wire:target="accumulated_depreciation_account" class="absolute right-2 top-2">
                        <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                </div>
                @error('accumulated_depreciation_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                @enderror
            </div>
        </div>
    </div>

    <div class="mt-6">
        <button wire:click="saveInstitutionAccounts" 
                wire:loading.attr="disabled"
                type="button" 
                class="text-white bg-blue-900 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 me-2 mb-2 dark:bg-blue-900 dark:hover:bg-blue-900 focus:outline-none dark:focus:ring-blue-800">
            <span wire:loading.remove wire:target="saveInstitutionAccounts">Save Institution Accounts</span>
            <span wire:loading wire:target="saveInstitutionAccounts">Saving...</span>
        </button>
    </div>

    @if (session()->has('message'))
        <div class="mt-4 p-4 text-sm text-green-700 bg-green-100 rounded-lg" role="alert">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="mt-4 p-4 text-sm text-red-700 bg-red-100 rounded-lg" role="alert">
            {{ session('error') }}
        </div>
    @endif
</div> 