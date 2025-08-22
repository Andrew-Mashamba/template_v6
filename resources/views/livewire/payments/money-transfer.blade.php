<div class="max-w-4xl mx-auto">
    {{-- Phase Indicator --}}
    <div class="mb-8">
        <div class="flex items-center justify-center">
            <div class="flex items-center space-x-8">
                {{-- Step 1: Form --}}
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 
                        @if($currentPhase === 'form') border-blue-500 bg-blue-500 text-white 
                        @elseif(in_array($currentPhase, ['verify', 'complete'])) border-green-500 bg-green-500 text-white 
                        @else border-gray-300 bg-gray-100 text-gray-500 @endif">
                        @if(in_array($currentPhase, ['verify', 'complete']))
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            1
                        @endif
                    </div>
                    <span class="text-sm font-medium 
                        @if($currentPhase === 'form') text-blue-600 
                        @elseif(in_array($currentPhase, ['verify', 'complete'])) text-green-600 
                        @else text-gray-500 @endif">Form</span>
                </div>

                {{-- Arrow --}}
                <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>

                {{-- Step 2: Verify --}}
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 
                        @if($currentPhase === 'verify') border-blue-500 bg-blue-500 text-white 
                        @elseif($currentPhase === 'complete') border-green-500 bg-green-500 text-white 
                        @else border-gray-300 bg-gray-100 text-gray-500 @endif">
                        @if($currentPhase === 'complete')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            2
                        @endif
                    </div>
                    <span class="text-sm font-medium 
                        @if($currentPhase === 'verify') text-blue-600 
                        @elseif($currentPhase === 'complete') text-green-600 
                        @else text-gray-500 @endif">Verify</span>
                </div>

                {{-- Arrow --}}
                <svg class="w-4 h-4 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                </svg>

                {{-- Step 3: Complete --}}
                <div class="flex items-center space-x-2">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center border-2 
                        @if($currentPhase === 'complete') border-green-500 bg-green-500 text-white 
                        @else border-gray-300 bg-gray-100 text-gray-500 @endif">
                        @if($currentPhase === 'complete')
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                            </svg>
                        @else
                            3
                        @endif
                    </div>
                    <span class="text-sm font-medium 
                        @if($currentPhase === 'complete') text-green-600 
                        @else text-gray-500 @endif">Complete</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Error and Success Messages --}}
    @if($errorMessage)
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-red-700 text-sm font-medium">{{ $errorMessage }}</span>
            </div>
        </div>
    @endif

    @if($successMessage)
        <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-green-700 text-sm font-medium">{{ $successMessage }}</span>
            </div>
        </div>
    @endif

    {{-- Main Content Card --}}
    <div class="bg-white rounded-lg shadow-lg border border-gray-200 overflow-hidden">
        {{-- Phase Content --}}
        <div class="p-6">
            @switch($currentPhase)
                @case('form')
                    {{-- FORM PHASE: Transfer Details Input --}}
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Transfer Details</h3>
                            
                            {{-- Transfer Type Selection --}}
                            <div class="mb-6">
                                <label class="block text-sm font-medium text-gray-700 mb-3">Transfer Type</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <button 
                                        wire:click="$set('transferType', 'bank')"
                                        class="relative p-4 border-2 rounded-lg transition-all duration-200 
                                            @if($transferType === 'bank') border-blue-500 bg-blue-50 @else border-gray-200 hover:border-gray-300 @endif"
                                    >
                                        <div class="flex items-center space-x-3">
                                            <svg class="w-6 h-6 @if($transferType === 'bank') text-blue-600 @else text-gray-400 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                            </svg>
                                            <div class="text-left">
                                                <div class="font-medium @if($transferType === 'bank') text-blue-900 @else text-gray-900 @endif">Bank Transfer</div>
                                                <div class="text-sm text-gray-500">Transfer to another bank account</div>
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

                                    <button 
                                        wire:click="$set('transferType', 'wallet')"
                                        class="relative p-4 border-2 rounded-lg transition-all duration-200 
                                            @if($transferType === 'wallet') border-blue-500 bg-blue-50 @else border-gray-200 hover:border-gray-300 @endif"
                                    >
                                        <div class="flex items-center space-x-3">
                                            <svg class="w-6 h-6 @if($transferType === 'wallet') text-blue-600 @else text-gray-400 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                            </svg>
                                            <div class="text-left">
                                                <div class="font-medium @if($transferType === 'wallet') text-blue-900 @else text-gray-900 @endif">Mobile Wallet</div>
                                                <div class="text-sm text-gray-500">Transfer to mobile money</div>
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
                                </div>
                            </div>

                            {{-- Transfer Form --}}
                            <form wire:submit.prevent="verifyBeneficiary" class="space-y-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    {{-- Source Account --}}
                                    <div>
                                        <label for="debitAccount" class="block text-sm font-medium text-gray-700">From Account *</label>
                                        <input type="text" wire:model.defer="debitAccount" id="debitAccount"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               placeholder="Your account number">
                                        @error('debitAccount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Transfer Amount --}}
                                    <div>
                                        <label for="amount" class="block text-sm font-medium text-gray-700">Amount (TZS) *</label>
                                        <input type="number" wire:model.defer="amount" id="amount" min="1000"
                                               class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                               placeholder="Minimum 1,000 TZS">
                                        @error('amount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    @if($transferType === 'bank')
                                        {{-- Beneficiary Account Number --}}
                                        <div>
                                            <label for="beneficiaryAccount" class="block text-sm font-medium text-gray-700">To Account Number *</label>
                                            <input type="text" wire:model.defer="beneficiaryAccount" id="beneficiaryAccount"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                   placeholder="Beneficiary account number">
                                            @error('beneficiaryAccount') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>

                                        {{-- Bank Selection --}}
                                        <div>
                                            <label for="bankCode" class="block text-sm font-medium text-gray-700">Bank *</label>
                                            <select wire:model.defer="bankCode" id="bankCode"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">Select Bank</option>
                                                @foreach($availableBanks as $code => $name)
                                                    <option value="{{ $code }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            @error('bankCode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    @else
                                        {{-- Mobile Number --}}
                                        <div>
                                            <label for="phoneNumber" class="block text-sm font-medium text-gray-700">Mobile Number *</label>
                                            <input type="text" wire:model.defer="phoneNumber" id="phoneNumber"
                                                   class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                   placeholder="255XXXXXXXXX">
                                            @error('phoneNumber') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>

                                        {{-- Wallet Provider --}}
                                        <div>
                                            <label for="walletProvider" class="block text-sm font-medium text-gray-700">Wallet Provider *</label>
                                            <select wire:model.defer="walletProvider" id="walletProvider"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                                <option value="">Select Provider</option>
                                                @foreach($availableWallets as $code => $name)
                                                    <option value="{{ $code }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                            @error('walletProvider') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    @endif

                                    {{-- Remarks --}}
                                    <div class="md:col-span-2">
                                        <label for="remarks" class="block text-sm font-medium text-gray-700">Remarks *</label>
                                        <textarea wire:model.defer="remarks" id="remarks" rows="3" maxlength="50"
                                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                  placeholder="Transfer purpose (max 50 characters)"></textarea>
                                        @error('remarks') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Submit Button --}}
                                <div class="flex justify-end pt-4">
                                    <button type="submit" 
                                            class="inline-flex items-center px-6 py-3 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                                            wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="verifyBeneficiary">Verify Details</span>
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
                    </div>
                    @break

                @case('verify')
                    {{-- VERIFY PHASE: Beneficiary Confirmation --}}
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Verify Transfer Details</h3>
                            
                            {{-- Beneficiary Details --}}
                            <div class="bg-green-50 border border-green-200 rounded-lg p-6 mb-6">
                                <div class="flex items-center mb-4">
                                    <svg class="w-6 h-6 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <h4 class="text-lg font-medium text-green-900">Beneficiary Verified</h4>
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-green-700">Name</p>
                                        <p class="font-medium text-green-900">{{ $beneficiaryName ?: 'Not Available' }}</p>
                                    </div>
                                    @if($transferType === 'bank')
                                        <div>
                                            <p class="text-sm text-green-700">Account Number</p>
                                            <p class="font-medium text-green-900">{{ $beneficiaryAccount }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-green-700">Bank</p>
                                            <p class="font-medium text-green-900">{{ $availableBanks[$bankCode] ?? $bankCode }}</p>
                                        </div>
                                    @else
                                        <div>
                                            <p class="text-sm text-green-700">Mobile Number</p>
                                            <p class="font-medium text-green-900">{{ $phoneNumber }}</p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-green-700">Wallet Provider</p>
                                            <p class="font-medium text-green-900">{{ $availableWallets[$walletProvider] ?? $walletProvider }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            {{-- Transfer Summary --}}
                            <div class="bg-gray-50 rounded-lg p-6">
                                <h4 class="text-md font-medium text-gray-900 mb-4">Transfer Summary</h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">From Account</span>
                                        <span class="font-medium">{{ $debitAccount }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Amount</span>
                                        <span class="font-medium">{{ number_format($amount, 2) }} TZS</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Transfer Fee</span>
                                        <span class="font-medium">0.00 TZS</span>
                                    </div>
                                    <hr class="border-gray-300">
                                    <div class="flex justify-between font-semibold">
                                        <span class="text-gray-900">Total Debit</span>
                                        <span class="text-gray-900">{{ number_format($amount, 2) }} TZS</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-gray-600">Remarks</span>
                                        <span class="font-medium">{{ $remarks }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Action Buttons --}}
                            <div class="flex justify-between pt-4">
                                <button type="button" 
                                        wire:click="backToForm"
                                        class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                    </svg>
                                    Back to Edit
                                </button>
                                
                                <button type="button" 
                                        wire:click="confirmTransfer"
                                        class="inline-flex items-center px-6 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-200"
                                        wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="confirmTransfer">Confirm Transfer</span>
                                    <span wire:loading wire:target="confirmTransfer" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Processing...
                                    </span>
                                </button>
                            </div>
                        </div>
                    </div>
                    @break

                @case('complete')
                    {{-- COMPLETE PHASE: Transfer Results --}}
                    <div class="space-y-6">
                        <div class="text-center">
                            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                                <svg class="w-8 h-8 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Transfer Successful!</h3>
                            <p class="text-gray-600">Your transfer has been processed successfully.</p>
                        </div>

                        {{-- Transaction Details --}}
                        <div class="bg-gray-50 rounded-lg p-6">
                            <h4 class="text-md font-medium text-gray-900 mb-4">Transaction Details</h4>
                            <div class="space-y-3">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Reference Number</span>
                                    <span class="font-medium font-mono">{{ $engineRef }}</span>
                                </div>
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
                            <button type="button" 
                                    wire:click="startNewTransfer"
                                    class="inline-flex items-center px-4 py-2 border border-blue-600 rounded-md shadow-sm text-sm font-medium text-blue-600 bg-white hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                New Transfer
                            </button>
                            
                            <button type="button" 
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                                Print Receipt
                            </button>
                        </div>
                    </div>
                    @break

                @default
                    {{-- Fallback --}}
                    <div class="text-center py-8">
                        <p class="text-gray-500">Invalid phase. Please refresh the page.</p>
                    </div>
            @endswitch
        </div>
    </div>
</div> 