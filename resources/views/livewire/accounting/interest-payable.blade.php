<div>





    @if($this->show_register_modal)

        <div class="fixed z-10 inset-0 overflow-y-auto"  >
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity">
                    <div class="absolute inset-0 bg-gray-500 opacity-0"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <!-- Your form elements go here -->
                    <div>




                            <div class="max-w-lg mx-auto mt-8 p-4 ">
                                @if (session()->has('message'))
                                    <div class="bg-green-500 text-white p-4 mb-4 rounded">
                                        {{ session('message') }}
                                    </div>
                                @endif

                                    <h2 class="text-2xl font-bold mb-4">Unearned/Deferred Revenue Form</h2>

                                    <div class="mb-4">
                                        <label for="member_id" class="block text-sm font-medium text-gray-700">Member ID</label>
                                        <input type="number" wire:model="member_id" id="member_id" class="mt-1 block w-full border-gray-300 rounded-md p-2">
                                        @error('member_id') <span class="text-red-500">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Account Type -->
                                    <div class="mb-4">
                                        <label for="account_type" class="block text-sm font-medium text-gray-700">Account Type</label>
                                        <input type="text" wire:model="account_type" id="account_type" class="mt-1 block w-full border-gray-300 rounded-md p-2">
                                        @error('account_type') <span class="text-red-500">{{ $message }}</span> @enderror
                                    </div>

                                    <!-- Category Type (Savings or Loan) -->
                                    <div class="mb-4">
                                        <label for="category_type" class="block text-sm font-medium text-gray-700">Category Type</label>
                                        <select wire:model="category_type" id="category_type" class="mt-1 block w-full border-gray-300 rounded-md p-2">
                                            <option value="Savings">Savings/Deposit</option>
                                            <option value="Loan">Loan</option>
                                        </select>
                                    </div>

                                    {{-- <div class="mb-4">
                                        <label for="amount" class="block text-sm font-medium text-gray-700"> Amount</label>
                                        <input type="number" wire:model="amount" id="deposit_amount" class="mt-1 block w-full border-gray-300 rounded-md p-2">
                                        @error('amount') <span class="text-red-500">{{ $message }}</span> @enderror
                                    </div> --}}

                                    <!-- Savings Fields -->
                                    @if($category_type === 'Savings')


                                        <div class="mb-4">
                                            <label for="interest_rate" class="block text-sm font-medium text-gray-700">Interest Rate (%)</label>
                                            <input type="number" wire:model="interest_rate" id="interest_rate" class="mt-1 block w-full border-gray-300 rounded-md p-2" step="0.01">
                                            @error('interest_rate') <span class="text-red-500">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="mb-4">
                                            <label for="deposit_date" class="block text-sm font-medium text-gray-700">Deposit Date</label>
                                            <input type="date" wire:model="deposit_date" id="deposit_date" class="mt-1 block w-full border-gray-300 rounded-md p-2">
                                            @error('deposit_date') <span class="text-red-500">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="mb-4">
                                            <label for="maturity_date" class="block text-sm font-medium text-gray-700">Maturity Date</label>
                                            <input type="date" wire:model="maturity_date" id="maturity_date" class="mt-1 block w-full border-gray-300 rounded-md p-2">
                                            @error('maturity_date') <span class="text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                    @endif

                                    <!-- Loan Fields -->
                                    @if($category_type === 'Loan')
                                        <div class="mb-4">
                                            <label for="loan_provider" class="block text-sm font-medium text-gray-700">Loan Provider</label>
                                            <input type="text" wire:model="loan_provider" id="loan_provider" class="mt-1 block w-full border-gray-300 rounded-md p-2">
                                            @error('loan_provider') <span class="text-red-500">{{ $message }}</span> @enderror
                                        </div>

                                        {{-- <div class="mb-4">
                                            <label for="loan_amount" class="block text-sm font-medium text-gray-700">Loan Amount</label>
                                            <input type="number" wire:model="loan_amount" id="loan_amount" class="mt-1 block w-full border-gray-300 rounded-md p-2">
                                            @error('loan_amount') <span class="text-red-500">{{ $message }}</span> @enderror
                                        </div> --}}

                                        <div class="mb-4">
                                            <label for="loan_interest_rate" class="block text-sm font-medium text-gray-700">Loan Interest Rate (%)</label>
                                            <input type="number" wire:model="loan_interest_rate" id="loan_interest_rate" class="mt-1 block w-full border-gray-300 rounded-md p-2" step="0.01">
                                            @error('loan_interest_rate') <span class="text-red-500">{{ $message }}</span> @enderror
                                        </div>

                                        <div class="mb-4">
                                            <label for="loan_start_date" class="block text-sm font-medium text-gray-700">Loan Start Date</label>
                                            <input type="date" wire:model="loan_start_date" id="loan_start_date" class="mt-1 block w-full border-gray-300 rounded-md p-2">
                                            @error('loan_start_date') <span class="text-red-500">{{ $message }}</span> @enderror
                                        </div>
                                    @endif





                            </div>



                    </div>


                    <!-- Add more form fields as needed -->
                    <div class="flex items-center bg-gray-200 justify-end py-3 sm:px-6 sm:rounded-bl-lg sm:rounded-br-lg">
                        <button type="button" wire:click="$toggle('show_register_modal')" class="mr-4 inline-flex justify-center px-4 py-2 text-sm font-medium   border border-transparent rounded-md  focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2  bg-white">
                            Cancel
                        </button>
                        <button type="submit" wire:click="register" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-blue-900 border border-transparent rounded-md  focus-visible:ring-2 focus-visible:ring-offset-2 ">
                            Proceed
                        </button>
                    </div>
                </div>
            </div>
        </div>

    @endif


    <div class="w-full p-2 mb-2 bg-white">

        <button wire:click="registerModal" type="button" class="text-white mt-4 mb-4 bg-[#3b5998] hover:bg-[#3b5998]/90 focus:ring-4 focus:outline-none focus:ring-[#3b5998]/50 font-medium rounded-lg text-sm px-5 py-2.5 text-center inline-flex items-center dark:focus:ring-[#3b5998]/55 me-2 mb-2">
            {{-- <svg  aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor" viewBox="0 0 8 19">
            <path fill-rule="evenodd" d="M6.135 3H8V0H6.135a4.147 4.147 0 0 0-4.142 4.142V6H0v3h2v9.938h3V9h2.021l.592-3H5V3.591A.6.6 0 0 1 5.592 3h.543Z" clip-rule="evenodd"/>
            </svg> --}}

            <svg data-slot="icon" class="w-4 h-4 me-2" fill="none" stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
              </svg>

            New Interest
            </button>



    <livewire:accounting.interest-payable-table  >


        </div>


</div>
