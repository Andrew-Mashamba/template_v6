<div>
    <div>
        <div>
            @if (session()->has('message'))
                <div class="bg-green-500 text-white p-2 mb-3">
                    {{ session('message') }}
                </div>
            @endif

            <div class="w-full flex gap-4 p-4">
                <div class="w-3/4 bg-white rounded-md p-2">
                    <table class="w-full text-sm text-left text-blue-100 mb-4">
                        <thead class="text-xs text-black bg-blue-100 dark:text-white w-full">
                        <tr class="border md:border-none block md:table-row w-full">
                            <th class="p-2 border-r px-6 py-4  text-xs text-black ">Payee</th>
                            <th class="p-2 border-r px-6 py-4  text-xs text-black ">Invoice Number</th>
                            <th class="p-2 border-r px-6 py-4  text-xs text-black text-right">Amount</th>
                            <th class="p-2 border-r px-6 py-4  text-xs text-black ">Due Date</th>
                            <th class="p-2 border-r px-6 py-4  text-xs text-black text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="block md:table-row-group">
                        @foreach($accountsPayable as $account)
                            <tr class="border md:border-none block md:table-row bg-gray-200 text-black uppercase w-full {{ $account->is_paid ? 'bg-green-200' : '' }}">
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-left text-xs">{{ $account->customer_name }}</td>
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-left text-xs">{{ $account->invoice_number }}</td>
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-right text-xs">{{ number_format($account->amount, 2) }}</td>
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-left text-xs">{{ $account->due_date }}</td>
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-right text-xs">
                                    @if($account->is_paid)

                                        <div class="flex gap-2 ">
                                            <div class="mt-2">

                                                Paid
                                            </div>

                                            <button class="bg-blue-900  text-white px-2 py-1 rounded-md" >
                                                <svg data-slot="icon" fill="none" class="w-6 h-6 " stroke-width="1.5" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z"></path>
                                                </svg>

                                            </button>

                                        </div>
                                    @else




                                        <button wire:click="edit({{ $account->id }})" class="bg-yellow-500 text-white px-2 py-1 rounded-md">Edit</button>
                                        <button wire:click="markAsPaid({{ $account->id }})" class="bg-green-500 text-white px-2 py-1 rounded-md">Pay </button>
                                        <button wire:click="delete({{ $account->id }})" class="bg-red-500 text-white px-2 py-1 rounded-md">Delete</button>
                                    @endif

                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="w-1/4 bg-white rounded-md p-4">
                    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}">
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Payee Name</label>
                            <input type="text" wire:model="customer_name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            @error('customer_name') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Invoice Number</label>
                            <input type="text" wire:model="invoice_number" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            @error('invoice_number') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Amount</label>
                            <input type="number" wire:model="amount" step="0.01" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            @error('amount') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Due Date</label>
                            <input type="date" wire:model="due_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            @error('due_date') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>





                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Payment Type</label>
                            <select wire:model="payment_type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                                <option value="">Select Payment Type</option>
                                <option value="cash">Cash</option>
                                <option value="non-cash">Non-Cash</option>
                            </select>
                        </div>

                        <!-- Liability Account Selection - Constant -->
                        @php
                            $payableAccount = \Illuminate\Support\Facades\DB::table('setup_accounts')->where('item', 'liability_accounts_payable')->first();
                            $this->liability_account_code = $payableAccount->sub_category_code;
                        @endphp
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Select Liability Account</label>
                        <select wire:model="liability_accounts_payable_code" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                            <option value="">Select</option>
                            @foreach(DB::table('accounts')->where('category_code', $this->liability_account_code)->get() as $account)
                                <option value="{{ $account->sub_category_code }}">{{ $account->account_name }}</option>
                            @endforeach
                        </select>

                        <!-- Conditional Account Selection Based on Payment Type -->
                        <div class="mt-4">
                            @if ($payment_type === 'cash')
                                <!-- Cash Account Selection -->
                                @php
                                    $cashAccount = \Illuminate\Support\Facades\DB::table('setup_accounts')->where('item', 'cash')->first();
                                    $this->cash_code = $cashAccount->sub_category_code;
                                @endphp
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Select Cash Account</label>
                                <select wire:model="cash_code" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                                    <option value="">Select</option>
                                    @foreach(DB::table('accounts')->where('category_code', $this->cash_code)->get() as $account)
                                        <option value="{{ $account->sub_category_code }}">{{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            @elseif ($payment_type === 'non-cash')
                                <!-- Expense Account Selection -->
                                @php
                                    $expenseAccount = \Illuminate\Support\Facades\DB::table('setup_accounts')->where('item', 'expense_accounts_payable')->first();
                                    $this->expense_account_code = $expenseAccount->sub_category_code;
                                @endphp
                                <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Select Expense Account</label>
                                <select wire:model="expense_accounts_payable_code" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                                    <option value="">Select</option>
                                    @foreach(DB::table('accounts')->where('category_code', $this->expense_account_code)->get() as $account)
                                        <option value="{{ $account->sub_category_code }}">{{ $account->account_name }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

















                        <div class="mt-2" >  </div>






                        <button type="submit" class="bg-blue-900 text-white px-4 py-2 justify-end rounded-md">{{ $isEdit ? 'Update' : 'Register' }} Payable</button>
                    </form>
                </div>
            </div>
            <hr class="my-4">
        </div>
    </div>




    @if($this->payModal)

        <div class="fixed z-10 inset-0 overflow-y-auto"  >
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity">
                    <div class="absolute inset-0 bg-gray-500 opacity-0"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <!-- Your form elements go here -->
                    <div>
                        @if (session()->has('message'))
                            @if (session('alert-class') == 'alert-success')
                                <div class="bg-teal-100 border-t-4 border-teal-500 rounded-b text-teal-900 px-4 py-3 shadow-md mb-8" role="alert">
                                    <div class="flex">
                                        <div class="py-1"><svg class="fill-current h-6 w-6 text-teal-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/></svg></div>
                                        <div>
                                            <p class="font-bold">The process is completed</p>
                                            <p class="text-sm">{{ session('message') }} </p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endif

                        <div class="bg-white p-4">

                            <div class="mb-4">
                                <h5 >
                                    Repayment
                                </h5>
                            </div>




                            <div >
                                <!-- Name -->
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="account_name" value="{{ __('Amount') }}" />
                                    <x-jet-input id="account_name" type="number" class="mt-1 block w-full" wire:model.defer="amount" autocomplete="account_name" />

                                    <x-jet-input-error for="account_name" class="mt-2" />
                                </div>
                            </div>


                            <div >
                                <!-- Name -->
                                <div class="col-span-6 sm:col-span-4">
                                    <x-jet-label for="description" value="{{ __('Description') }}" />
                                    <x-jet-input id="description" type="text" class="mt-1 block w-full" wire:model.defer="description" autocomplete="account_name" />

                                    <x-jet-input-error for="description" class="mt-2" />
                                </div>
                            </div>



                        </div>
                    </div>


                    <!-- Add more form fields as needed -->
                    <div class="flex items-center bg-gray-200 justify-end py-3 sm:px-6 sm:rounded-bl-lg sm:rounded-br-lg">
                        <button type="button" wire:click="$toggle('payModal')" class="mr-4 inline-flex justify-center px-4 py-2 text-sm font-medium   border border-transparent rounded-md  focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2  bg-white">
                            Cancel
                        </button>

                        <button type="submit" wire:click="makePayment" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-blue-400 border border-transparent rounded-md  focus-visible:ring-2 focus-visible:ring-offset-2 ">
                            Proceed
                        </button>

                    </div>
                </div>
            </div>
        </div>

    @endif



</div>
