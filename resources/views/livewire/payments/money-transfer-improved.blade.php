<div class="max-w-6xl mx-auto p-6">
    {{-- Header --}}
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Funds Transfer</h1>
        <p class="mt-1 text-sm text-gray-600">Transfer funds to bank accounts, mobile wallets, or internal NBC accounts</p>
    </div>

    {{-- Phase Indicator --}}
    <div class="mb-8">
        <div class="flex items-center justify-center">
            <div class="flex items-center space-x-8">
                {{-- Step 1: Select & Fill --}}
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium
                        @if($currentPhase === 'form') border-2 border-blue-500 bg-blue-500 text-white
                        @elseif(in_array($currentPhase, ['verify', 'processing', 'complete'])) border-2 border-green-500 bg-green-500 text-white
                        @else border-2 border-gray-300 bg-gray-100 text-gray-500 @endif">
                        @if(in_array($currentPhase, ['verify', 'processing', 'complete']))
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            1
                        @endif
                    </div>
                    <span class="text-sm font-medium @if($currentPhase === 'form') text-blue-600 @elseif(in_array($currentPhase, ['verify', 'processing', 'complete'])) text-green-600 @else text-gray-500 @endif">
                        Details
                    </span>
                </div>

                <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>

                {{-- Step 2: Verify --}}
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium
                        @if($currentPhase === 'verify') border-2 border-blue-500 bg-blue-500 text-white
                        @elseif(in_array($currentPhase, ['processing', 'complete'])) border-2 border-green-500 bg-green-500 text-white
                        @else border-2 border-gray-300 bg-gray-100 text-gray-500 @endif">
                        @if(in_array($currentPhase, ['processing', 'complete']))
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            2
                        @endif
                    </div>
                    <span class="text-sm font-medium @if($currentPhase === 'verify') text-blue-600 @elseif(in_array($currentPhase, ['processing', 'complete'])) text-green-600 @else text-gray-500 @endif">
                        Verify
                    </span>
                </div>

                <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                </svg>

                {{-- Step 3: Complete --}}
                <div class="flex items-center space-x-2">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-sm font-medium
                        @if($currentPhase === 'complete') border-2 border-green-500 bg-green-500 text-white
                        @else border-2 border-gray-300 bg-gray-100 text-gray-500 @endif">
                        @if($currentPhase === 'complete')
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            3
                        @endif
                    </div>
                    <span class="text-sm font-medium @if($currentPhase === 'complete') text-green-600 @else text-gray-500 @endif">
                        Complete
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Alerts --}}
    @if($errorMessage)
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700">{{ $errorMessage }}</p>
                </div>
            </div>
        </div>
    @endif

    @if($successMessage)
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-green-700">{{ $successMessage }}</p>
                </div>
            </div>
        </div>
    @endif

    {{-- Main Content --}}
    <div class="bg-white rounded-lg shadow-lg border border-gray-200">
        <div class="p-6">
            @switch($currentPhase)
                @case('form')
                    {{-- FORM PHASE --}}
                    <div class="space-y-6">
                        {{-- Transfer Type Selection --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">Select Transfer Type</label>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                {{-- External Bank Transfer --}}
                                <button wire:click="$set('transferType', 'bank')"
                                    class="relative p-4 border-2 rounded-lg transition-all duration-200 hover:shadow-md
                                        @if($transferType === 'bank') border-blue-500 bg-blue-50 @else border-gray-200 hover:border-gray-300 @endif">
                                    <div class="flex flex-col items-center space-y-2">
                                        <svg class="w-8 h-8 @if($transferType === 'bank') text-blue-600 @else text-gray-400 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                        </svg>
                                        <div class="text-center">
                                            <div class="font-medium @if($transferType === 'bank') text-blue-900 @else text-gray-900 @endif">External Bank</div>
                                            <div class="text-xs text-gray-500 mt-1">Other banks via TIPS/TISS</div>
                                        </div>
                                    </div>
                                    @if($transferType === 'bank')
                                        <div class="absolute top-2 right-2">
                                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </button>

                                {{-- Mobile Wallet Transfer --}}
                                <button wire:click="$set('transferType', 'wallet')"
                                    class="relative p-4 border-2 rounded-lg transition-all duration-200 hover:shadow-md
                                        @if($transferType === 'wallet') border-blue-500 bg-blue-50 @else border-gray-200 hover:border-gray-300 @endif">
                                    <div class="flex flex-col items-center space-y-2">
                                        <svg class="w-8 h-8 @if($transferType === 'wallet') text-blue-600 @else text-gray-400 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                        <div class="text-center">
                                            <div class="font-medium @if($transferType === 'wallet') text-blue-900 @else text-gray-900 @endif">Mobile Wallet</div>
                                            <div class="text-xs text-gray-500 mt-1">M-Pesa, TigoPesa, etc</div>
                                        </div>
                                    </div>
                                    @if($transferType === 'wallet')
                                        <div class="absolute top-2 right-2">
                                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </button>

                                {{-- Internal NBC Transfer --}}
                                <button wire:click="$set('transferType', 'internal')"
                                    class="relative p-4 border-2 rounded-lg transition-all duration-200 hover:shadow-md
                                        @if($transferType === 'internal') border-blue-500 bg-blue-50 @else border-gray-200 hover:border-gray-300 @endif">
                                    <div class="flex flex-col items-center space-y-2">
                                        <svg class="w-8 h-8 @if($transferType === 'internal') text-blue-600 @else text-gray-400 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                                        </svg>
                                        <div class="text-center">
                                            <div class="font-medium @if($transferType === 'internal') text-blue-900 @else text-gray-900 @endif">Internal NBC</div>
                                            <div class="text-xs text-gray-500 mt-1">Within NBC accounts</div>
                                        </div>
                                    </div>
                                    @if($transferType === 'internal')
                                        <div class="absolute top-2 right-2">
                                            <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        </div>
                                    @endif
                                </button>
                            </div>
                        </div>

                        {{-- Transfer Form --}}
                        <form wire:submit.prevent="verifyBeneficiary" class="space-y-4">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                {{-- Source Account --}}
                                <div class="md:col-span-2">
                                    <label for="debitAccount" class="block text-sm font-medium text-gray-700 mb-1">From Account</label>
                                    <input type="text" wire:model.defer="debitAccount" id="debitAccount"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Enter source account number">
                                    @error('debitAccount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                {{-- Amount --}}
                                <div>
                                    <label for="amount" class="block text-sm font-medium text-gray-700 mb-1">Amount (TZS)</label>
                                    <input type="number" wire:model.defer="amount" id="amount" min="1000" step="0.01"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Minimum 1,000 TZS">
                                    @error('amount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    @if($transferType === 'wallet')
                                        <p class="text-xs text-gray-500 mt-1">Maximum: 20,000,000 TZS for wallet transfers</p>
                                    @endif
                                </div>

                                {{-- Charge Bearer --}}
                                <div>
                                    <label for="chargeBearer" class="block text-sm font-medium text-gray-700 mb-1">Charge Bearer</label>
                                    <select wire:model.defer="chargeBearer" id="chargeBearer"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="OUR">OUR (Sender pays)</option>
                                        <option value="BEN">BEN (Beneficiary pays)</option>
                                        <option value="SHA">SHA (Shared)</option>
                                    </select>
                                    @error('chargeBearer') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                </div>

                                {{-- Type-specific fields --}}
                                @if($transferType === 'bank')
                                    {{-- Beneficiary Account --}}
                                    <div>
                                        <label for="beneficiaryAccount" class="block text-sm font-medium text-gray-700 mb-1">To Account Number</label>
                                        <input type="text" wire:model.defer="beneficiaryAccount" id="beneficiaryAccount"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Enter beneficiary account">
                                        @error('beneficiaryAccount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Bank Selection --}}
                                    <div>
                                        <label for="bankCode" class="block text-sm font-medium text-gray-700 mb-1">Bank</label>
                                        <select wire:model.defer="bankCode" id="bankCode"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Select Bank</option>
                                            @foreach($availableBanks as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        @error('bankCode') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                @elseif($transferType === 'wallet')
                                    {{-- Phone Number --}}
                                    <div>
                                        <label for="phoneNumber" class="block text-sm font-medium text-gray-700 mb-1">Mobile Number</label>
                                        <input type="text" wire:model.defer="phoneNumber" id="phoneNumber"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="0715000000 or 255715000000">
                                        @error('phoneNumber') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Wallet Provider --}}
                                    <div>
                                        <label for="walletProvider" class="block text-sm font-medium text-gray-700 mb-1">Wallet Provider</label>
                                        <select wire:model.defer="walletProvider" id="walletProvider"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Select Provider</option>
                                            @foreach($availableWallets as $code => $name)
                                                <option value="{{ $code }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                        @error('walletProvider') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                @elseif($transferType === 'internal')
                                    {{-- Internal NBC Account --}}
                                    <div class="md:col-span-2">
                                        <label for="internalAccount" class="block text-sm font-medium text-gray-700 mb-1">To NBC Account</label>
                                        <input type="text" wire:model.defer="internalAccount" id="internalAccount"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                            placeholder="Enter NBC account number">
                                        @error('internalAccount') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    </div>
                                @endif

                                {{-- Remarks --}}
                                <div class="md:col-span-2">
                                    <label for="remarks" class="block text-sm font-medium text-gray-700 mb-1">Remarks</label>
                                    <textarea wire:model.defer="remarks" id="remarks" rows="2" maxlength="50"
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        placeholder="Transfer purpose (max 50 characters)"></textarea>
                                    @error('remarks') <span class="text-red-500 text-xs mt-1">{{ $message }}</span> @enderror
                                    <p class="text-xs text-gray-500 mt-1">{{ strlen($remarks) }}/50 characters</p>
                                </div>
                            </div>

                            {{-- Submit Button --}}
                            <div class="flex justify-end pt-4">
                                <button type="submit"
                                    class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="verifyBeneficiary">Verify Beneficiary</span>
                                    <span wire:loading wire:target="verifyBeneficiary" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Verifying...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                    @break

                @case('verify')
                    {{-- VERIFY PHASE --}}
                    <div class="space-y-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Verify Transfer Details</h3>

                        {{-- Beneficiary Verification Success --}}
                        @if(!empty($verificationData))
                            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                                <div class="flex items-center mb-4">
                                    <svg class="w-6 h-6 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <h4 class="text-lg font-medium text-green-900">Beneficiary Verified</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-green-700">Account Name</p>
                                        <p class="font-medium text-green-900">{{ $verificationData['account_name'] ?? 'N/A' }}</p>
                                    </div>
                                    @if($verificationData['type'] === 'bank')
                                        <div>
                                            <p class="text-sm text-green-700">Account Number</p>
                                            <p class="font-medium text-green-900">{{ $verificationData['account_number'] }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-green-700">Bank</p>
                                            <p class="font-medium text-green-900">{{ $verificationData['bank_name'] }}</p>
                                        </div>
                                    @elseif($verificationData['type'] === 'wallet')
                                        <div>
                                            <p class="text-sm text-green-700">Phone Number</p>
                                            <p class="font-medium text-green-900">{{ $verificationData['phone_number'] }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-green-700">Provider</p>
                                            <p class="font-medium text-green-900">{{ $verificationData['provider'] }}</p>
                                        </div>
                                    @elseif($verificationData['type'] === 'internal')
                                        <div>
                                            <p class="text-sm text-green-700">Account Number</p>
                                            <p class="font-medium text-green-900">{{ $verificationData['account_number'] }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-green-700">Branch</p>
                                            <p class="font-medium text-green-900">{{ $verificationData['branch'] }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        {{-- Transfer Summary --}}
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Transfer Summary</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Transfer Type</span>
                                    <span class="font-medium">
                                        @if($transferType === 'bank')
                                            External Bank Transfer
                                        @elseif($transferType === 'wallet')
                                            Mobile Wallet Transfer
                                        @else
                                            Internal NBC Transfer
                                        @endif
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">From Account</span>
                                    <span class="font-medium">{{ $debitAccount }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Amount</span>
                                    <span class="font-medium">{{ number_format($amount, 2) }} TZS</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Charge Bearer</span>
                                    <span class="font-medium">{{ $chargeBearer }}</span>
                                </div>
                                <hr class="border-gray-300">
                                <div class="flex justify-between font-semibold">
                                    <span class="text-gray-900">Total Debit</span>
                                    <span class="text-gray-900">{{ number_format($amount, 2) }} TZS</span>
                                </div>
                                <div>
                                    <span class="text-gray-600 block mb-1">Remarks</span>
                                    <span class="font-medium text-sm">{{ $remarks }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex justify-between pt-4">
                            <button type="button" wire:click="goBack"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to Edit
                            </button>

                            <button type="button" wire:click="executeTransfer"
                                class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="executeTransfer">Confirm Transfer</span>
                                <span wire:loading wire:target="executeTransfer" class="flex items-center">
                                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Processing Transfer...
                                </span>
                            </button>
                        </div>
                    </div>
                    @break

                @case('processing')
                    {{-- PROCESSING PHASE --}}
                    <div class="text-center py-12">
                        <svg class="animate-spin h-12 w-12 text-blue-600 mx-auto mb-4" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900">Processing Transfer</h3>
                        <p class="text-sm text-gray-500 mt-2">Please wait while we process your transfer...</p>
                    </div>
                    @break

                @case('complete')
                    {{-- COMPLETE PHASE --}}
                    <div class="space-y-6">
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Transfer Successful!</h3>
                            <p class="text-gray-600">{{ $successMessage }}</p>
                        </div>

                        {{-- Transaction Details --}}
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Transaction Details</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Date & Time</span>
                                    <span class="font-medium">{{ now()->format('d M Y, H:i:s') }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Status</span>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Successful
                                    </span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Amount Transferred</span>
                                    <span class="font-medium">{{ number_format($amount, 2) }} TZS</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Beneficiary</span>
                                    <span class="font-medium">{{ $beneficiaryName ?: 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex justify-center space-x-4 pt-4">
                            <button type="button" wire:click="resetForm"
                                class="inline-flex items-center px-4 py-2 border border-blue-600 rounded-md shadow-sm text-sm font-medium text-blue-600 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                New Transfer
                            </button>
                        </div>
                    </div>
                    @break
            @endswitch
        </div>
    </div>
</div>