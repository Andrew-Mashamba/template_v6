<div class="bg-white rounded rounded-lg shadow-md p-4">

    @if (session()->has('message'))
        <div class="alert alert-success text-green-600">
            {{ session('message') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="alert alert-danger text-red-600">
            {{ session('error') }}
        </div>
    @endif





















        <div class="flex items-center text-sm mb-2 font-semibold text-slate-600">
        POST A TRANSACTION
    </div>

    <div class="flex items-stretch p-2 bg-gray-100 gap-2 rounded-md">
        <!-- Debit Section -->
        <div class="w-1/2  rounded-md p-4 bg-white">
            <div class="flex items-center text-sm mb-2 font-semibold text-slate-600">
                DEBIT
            </div>
            <hr class="boder-b-0 my-6"/>
            <label for="debit_category" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-400">
                Select Debit Category
            </label>


            {{-- <select wire:model="debit_category" id="debit_category"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                <option value="">Select</option>
                @foreach($gl_accounts as $account)
                    <option value="{{ $account->account_name }}">{{ $account->account_name }}</option>
                @endforeach
            </select> --}}

            <div class="p-4 relative">
                <!-- Search Input -->
                <input type="text" wire:model.debounce.300ms="search" placeholder="Search accounts..."
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" />

                <!-- Search Dropdown -->
                @if ($showDropdown && !empty($results))
                <div class="absolute bg-white border rounded shadow mt-1 w-full z-10 max-h-60 overflow-y-auto">
                    <ul>
                        @foreach ($results as $result)
                            <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                                wire:click="selectAccount('{{ $result->id }}', '{{ $result->source }}')">
                                <div>{{ $result->account_name }} ({{ $result->account_number }})</div>
                                <div class="text-sm text-gray-500">Source: {{ ucfirst($result->source) }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif


                <!-- Display Selected Account Details -->
                @if (!empty($selectedAccount))
                    <div class="mt-6 p-4 border rounded bg-gray-50">
                        <h3 class="text-lg font-semibold mb-2">Account Details</h3>
                        <p><strong>Account Name:</strong> {{ $selectedAccount['account_name'] }}</p>
                        <p><strong>Account Number:</strong> {{ $selectedAccount['account_number'] }}</p>
                        <p><strong>Balance:</strong> {{ number_format($selectedAccount['balance'] ?? 0, 2) }}</p>
                        <p><strong>Status:</strong> {{ $selectedAccount['status'] }}</p>
                        <p><strong>Created At:</strong> {{ $selectedAccount['created_at'] ?? 'N/A' }}</p>
                        <p><strong>Updated At:</strong> {{ $selectedAccount['updated_at'] ?? 'N/A' }}</p>
                    </div>
                @endif
            </div>

{{--
            @error('debit_category')
            <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror

            @if($debit_category)
                <!-- Debit Sub Category -->
                <div class="mt-2">
                    <label for="debit_category_code" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-400">
                        Select Debit Sub Category
                    </label>
                    <select wire:model="debit_category_code" id="debit_category_code"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select</option>
                        @foreach($debit_sub_categories as $category)
                            <option value="{{ $category->category_code }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                    @error('debit_category_code')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            @endif

            @if($debit_category_code)
                <!-- Debit Account -->
                <div class="mt-2">
                    <label for="debit_account" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-400">
                        Select Debit Account
                    </label>
                    <select wire:model="debit_account" id="debit_account"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select</option>
                        @foreach($debit_accounts as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} - {{ $account->account_number }}
                            </option>
                        @endforeach
                    </select>
                    @error('debit_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            @endif


        @if($this->debit_account)
            <hr class="boder-b-0 my-6"/>



            <p for="stability" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-400"> Summary </p>
            <div id="stability" class="w-full bg-gray-50 rounded rounded-lg shadow-sm   p-1 mb-4" >
                <div class="w-full bg-white rounded rounded-lg shadow-sm  p-2 " >




                    <table class="w-full ">

                        <tbody>





                        <tr class=" border-slate-100 border-b p-4">
                            <td class="text-xs text-slate-400 dark:text-white capitalize  text-left">
                                <p> Account Name </p>
                            </td>
                            <td class="text-xs text-slate-400 dark:text-white text-right">

                                <p class="text-red-500 font-bold  ">
                                    {{DB::table('accounts')->where('account_number', $this->debit_account)->value('account_name')}}
                                </p>


                            </td>
                        </tr>

                        <tr class=" border-slate-100 border-b p-4">
                            <td class="text-xs text-slate-400 dark:text-white capitalize  text-left">
                                <p> Account Number</p>
                            </td>
                            <td class="text-xs text-slate-400 dark:text-white text-right">

                                <p class="text-red-500 font-bold  ">
                                    {{$this->debit_account}}
                                </p>


                            </td>
                        </tr>

                        <tr class=" border-slate-100 border-b p-4">
                            <td class="text-xs text-slate-400 dark:text-white capitalize  text-left">
                                <p> Account Balance </p>
                            </td>
                            <td class="text-xs text-slate-400 dark:text-white text-right">

                                <p class="text-red-500 font-bold  ">
                                    {{DB::table('accounts')->where('account_number', $this->debit_account)->value('balance')}}
                                </p>


                            </td>
                        </tr>



                        </tbody>
                    </table>

                </div>
            </div>

        @endif --}}


        </div>

        <!-- Credit Section -->
        <div class="w-1/2  rounded-md p-4 bg-white">
            <div class="flex items-center text-sm mb-2 font-semibold text-slate-600">
                CREDIT
            </div>
            <hr class="boder-b-0 my-6"/>
            <label for="credit_category" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-400">
                Select Credit Category
            </label>



            <div class="p-4 relative">
                <!-- Search Input -->
                <input type="text" wire:model.debounce.300ms="searchTwo" placeholder="Search accounts..."
                       class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" />

                <!-- Search Dropdown -->
                @if ($showDropdownTwo && !empty($resultsTwo))
                <div class="absolute bg-white border rounded shadow mt-1 w-full z-10 max-h-60 overflow-y-auto">
                    <ul>
                        @foreach ($resultsTwo as $result)
                            <li class="px-4 py-2 hover:bg-gray-100 cursor-pointer"
                                wire:click="selectAccountTwo('{{ $result->id }}', '{{ $result->source }}')">
                                <div>{{ $result->account_name }} ({{ $result->account_number }})</div>
                                <div class="text-sm text-gray-500">Source: {{ ucfirst($result->source) }}</div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif


                <!-- Display Selected Account Details -->
                @if (!empty($selectedAccountTwo))
                    <div class="mt-6 p-4 border rounded bg-gray-50">
                        <h3 class="text-lg font-semibold mb-2">Account Details</h3>
                        <p><strong>Account Name:</strong> {{ $selectedAccountTwo['account_name'] }}</p>
                        <p><strong>Account Number:</strong> {{ $selectedAccountTwo['account_number'] }}</p>
                        <p><strong>Balance:</strong> {{ number_format($selectedAccountTwo['balance'] ?? 0, 2) }}</p>
                         <p><strong>Status:</strong> {{ $selectedAccountTwo['status'] }}</p>
                        <p><strong>Created At:</strong> {{ $selectedAccountTwo['created_at'] ?? 'N/A' }}</p>
                        <p><strong>Updated At:</strong> {{ $selectedAccountTwo['updated_at'] ?? 'N/A' }}</p>
                    </div>
                @endif
            </div>



            {{-- <select wire:model="credit_category" id="credit_category"
                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                <option value="">Select</option>
                @foreach($gl_accounts as $account)
                    <option value="{{ $account->account_name }}">{{ $account->account_name }}</option>
                @endforeach
            </select>
            @error('credit_category')
            <span class="text-red-500 text-sm">{{ $message }}</span>
            @enderror --}}
            {{-- @if($credit_category)
                <!-- Credit Sub Category -->
                <div class="mt-2">
                    <label for="credit_category_code" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-400">
                        Select Credit Sub Category
                    </label>
                    <select wire:model="credit_category_code" id="credit_category_code"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select</option>
                        @foreach($credit_sub_categories as $category)
                            <option value="{{ $category->category_code }}">{{ $category->category_name }}</option>
                        @endforeach
                    </select>
                    @error('credit_category_code')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            @endif
            @if($credit_category_code)
                <!-- Credit Account -->
                <div class="mt-2">
                    <label for="credit_account" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-400">
                        Select Credit Account
                    </label>
                    <select wire:model="credit_account" id="credit_account"
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5">
                        <option value="">Select</option>
                        @foreach($credit_accounts as $account)
                            <option value="{{ $account->account_number }}">
                                {{ $account->account_name }} - {{ $account->account_number }}
                            </option>
                        @endforeach
                    </select>
                    @error('credit_account')
                    <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            @endif --}}

            {{-- @if($this->credit_account)
            <hr class="boder-b-0 my-6"/>
            <p for="stability" class="block mb-2 text-sm font-medium text-gray-900 dark:text-gray-400"> Summary </p>
            <div id="stability" class="w-full bg-gray-50 rounded rounded-lg shadow-sm   p-1 mb-4" >
                <div class="w-full bg-white rounded rounded-lg shadow-sm  p-2 " >




                    <table class="w-full ">

                        <tbody>





                        <tr class=" border-slate-100 border-b p-4">
                            <td class="text-xs text-slate-400 dark:text-white capitalize  text-left">
                                <p> Account Name </p>
                            </td>
                            <td class="text-xs text-slate-400 dark:text-white text-right">

                                <p class="text-red-500 font-bold  ">
                                    {{DB::table('accounts')->where('account_number', $this->credit_account)->value('account_name')}}
                                </p>


                            </td>
                        </tr>

                        <tr class=" border-slate-100 border-b p-4">
                            <td class="text-xs text-slate-400 dark:text-white capitalize  text-left">
                                <p> Account Number</p>
                            </td>
                            <td class="text-xs text-slate-400 dark:text-white text-right">

                                <p class="text-red-500 font-bold  ">
                                    {{$this->credit_account}}
                                </p>


                            </td>
                        </tr>

                        <tr class=" border-slate-100 border-b p-4">
                            <td class="text-xs text-slate-400 dark:text-white capitalize  text-left">
                                <p> Account Balance </p>
                            </td>
                            <td class="text-xs text-slate-400 dark:text-white text-right">

                                <p class="text-red-500 font-bold  ">
                                    {{DB::table('accounts')->where('account_number', $this->credit_account)->value('balance')}}
                                </p>


                            </td>
                        </tr>



                        </tbody>
                    </table>

                </div>
            </div>

            @endif --}}


        </div>


    </div>


    <div class="flex items-stretch p-2 bg-gray-100 gap-2 rounded-md">

        <div class="rounded-md p-4 bg-white w-full">


            <div class="grid gap-2 mb-2 grid-cols-3 w-full">
                <div>
                    <label class="block mb-2 text-xs font-medium text-gray-900 dark:text-white">Amount</label>
                    <input wire:model="amount" type="number" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500
                            focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500
                            dark:focus:border-blue-500" placeholder="" required />
                </div>
                <div>
                    <label class="block mb-2 text-xs font-medium text-gray-900 dark:text-white">Narration</label>
                    <input wire:model="narration" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-xs rounded-lg focus:ring-blue-500
                            focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500
                            dark:focus:border-blue-500" placeholder="" required />
                </div>
                <div >
                    <!-- Submit Button -->
                    <div class="flex justify-end mt-4">
                        <div wire:loading wire:target="post">
                            <button class="text-white bg-blue-400 hover:bg-blue-400 font-medium rounded-lg text-sm px-4 py-2" disabled>
                                <div class="flex items-center">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="animate-spin h-5 w-5 mr-2" fill="white" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v4m0 4v8m4-8H8m12 0a8 8 0 11-16 0 8 8 0 0116 0z" />
                                    </svg>
                                    Posting...
                                </div>
                            </button>
                        </div>
                        <button wire:loading.remove wire:click="post" class="text-white bg-blue-900 hover:bg-blue-800 font-medium rounded-lg text-sm px-4 py-2">
                            Post Transaction
                        </button>
                    </div>


                </div>
            </div>



        </div>

    </div>



</div>
