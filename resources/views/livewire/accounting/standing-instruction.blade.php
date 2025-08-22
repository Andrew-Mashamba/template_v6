<div>
    {{-- The Master doesn't talk, he acts. --}}
    @if (in_array(23, session()->get('permissions')))

        @php
            $menuItems = [
                ['id' => 2, 'label' => ' New Standing Order'],

            ];
        @endphp


        @foreach ($menuItems as $menuItem)
            <button
                    wire:click="menuItemClicked"
                    class="flex hover:text-white text-center items-center
            @if ($this->tab_id == $menuItem['id']) bg-blue-900 @else bg-blue-900 @endif
                    @if ($this->tab_id == $menuItem['id']) text-white font-bold @else text-gray-400 font-semibold @endif
                            py-2 px-4 rounded-lg"
                    onmouseover="this.style.backgroundColor='#2D3D88'; this.style.color='white';"
                    onmouseout="this.style.backgroundColor=''; this.style.color='';"
            >
                <div wire:loading wire:target="menuItemClicked">
                    <svg aria-hidden="true" class="w-8 h-8 mr-2 text-gray-200 animate-spin dark:text-gray-900 fill-red-600" viewBox="0 0 100 101" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M100 50.5908C100 78.2051 77.6142 100.591 50 100.591C22.3858 100.591 0 78.2051 0 50.5908C0 22.9766 22.3858 0.59082 50 0.59082C77.6142 0.59082 100 22.9766 100 50.5908ZM9.08144 50.5908C9.08144 73.1895 27.4013 91.5094 50 91.5094C72.5987 91.5094 90.9186 73.1895 90.9186 50.5908C90.9186 27.9921 72.5987 9.67226 50 9.67226C27.4013 9.67226 9.08144 27.9921 9.08144 50.5908Z" fill="currentColor"/>
                        <path d="M93.9676 39.0409C96.393 38.4038 97.8624 35.9116 97.0079 33.5539C95.2932 28.8227 92.871 24.3692 89.8167 20.348C85.8452 15.1192 80.8826 10.7238 75.2124 7.41289C69.5422 4.10194 63.2754 1.94025 56.7698 1.05124C51.7666 0.367541 46.6976 0.446843 41.7345 1.27873C39.2613 1.69328 37.813 4.19778 38.4501 6.62326C39.0873 9.04874 41.5694 10.4717 44.0505 10.1071C47.8511 9.54855 51.7191 9.52689 55.5402 10.0491C60.8642 10.7766 65.9928 12.5457 70.6331 15.2552C75.2735 17.9648 79.3347 21.5619 82.5849 25.841C84.9175 28.9121 86.7997 32.2913 88.1811 35.8758C89.083 38.2158 91.5421 39.6781 93.9676 39.0409Z" fill="currentFill"/>
                    </svg>
                </div>
                <div wire:loading.remove wire:target="menuItemClicked">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="red"
                         class="w-4 h-4 mr-2 fill-current">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zM3.75 12h.007v.008H3.75V12zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm-.375 5.25h.007v.008H3.75v-.008zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                    </svg>
                </div>
                {{ $menuItem['label'] }}
            </button>
        @endforeach







    @endif
    <div class="mt-2">
        <livewire:accounting.standing-instruction-table />
    </div>

    @if($this->new_stannding_order)
        <div class="fixed z-10 inset-0 overflow-y-auto"  >
            <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
                <div class="fixed inset-0 transition-opacity">
                    <div class="absolute inset-0 bg-gray-500 opacity-0"></div>
                </div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen"></span>&#8203;
                <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                    <!-- Your form elements go here -->
                    <div class="p-4">
                        <div>
                            @if (session()->has('message'))
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

                            @if (session()->has('message_fail'))
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

                        </div>
                        <div class="header-elements text-center justify-center font-bold  stroke-current">
                            <h3 class="fw-bold  text-2xl ">
                                New Standing Instruction
                            </h3>
                        </div>

                        <div class="flex items-center justify-center my-4">
                            <div class="flex-grow border-t border-gray-300"></div>
                            <span class="px-4 text-lg font-semibold text-gray-700"> Account Holder Details </span>
                            <div class="flex-grow border-t border-gray-300"></div>
                        </div>


                        <div class="">

                            <p for="member_id" class="block mb-2 text-sm font-medium text-slate-600 dark:text-gray-400">
                                Select Member</p>
                                <div class="relative w-full" x-data="{ showDropdown: @entangle('showDropdown') }" @click.away="showDropdown = false">
                                <!-- Search Input -->
                                <input
                                    type="text"
                                    wire:model="search"
                                    wire:focus="showAllMembers"
                                    class="w-full p-2.5 border-gray-300 focus:border-red-500 focus:ring rounded-lg focus:ring-red-200 focus:ring-opacity-50 rounded-md shadow-sm text-sm"
                                    placeholder="Search Member..."
                                    autocomplete="off"
                                    value="{{ $selectedMemberId }}"
                                >

                                <!-- Dropdown Options -->
                                 <div x-show="showDropdown">
                                    @if($showDropdown && count($members) > 0)
                                        <ul class="absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded shadow-md max-h-60 overflow-auto">
                                            @foreach($members as $member)
                                                <li
                                                    wire:click="selectMember('{{ $member->id }}')"
                                                    class="px-4 py-2 cursor-pointer hover:bg-gray-200"
                                                >
                                                    {{ $member->first_name }} {{ $member->middle_name }} {{ $member->last_name }}
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                 </div>


                                <!-- Hidden Select Field for Form Submission -->
                                <input type="hidden" name="member_id" wire:model="selectedMemberId">
                            </div>
                            <!-- <select id="member_id" name="member_id" class="w-full border-gray-300 focus:border-red-500 focus:ring rounded-lg focus:ring-red-200 focus:ring-opacity-50 p-2.5 rounded-md shadow-sm text-sm" wire:model="selectedMemberId">
                                <option value="">Select Member</option>
                                @foreach(DB::table('clients')->get() as $member)
                                    <option value="{{ $member->id }}">{{ $member->first_name }} {{ $member->middle_name }} {{ $member->last_name }}</option>
                                @endforeach
                            </select> -->
                            @error('selectedMemberId')
                            <div class="border border-red-400 rounded-b bg-red-100 px-4 py-3 text-red-700 mt-1">
                                <p>{{ $message }}</p>
                            </div>
                            @enderror
                            <div class="mt-2"></div>

                            @if($selectedMemberId)
                                @php
                                    $member = DB::table('clients')->find($selectedMemberId);
                                @endphp
                                <div class="mt-1 mx-2">
                                    <div class="fw-bold ">
                                        <div class="max-w-md bg-blue-50 mx-auto bg-white p-4 rounded-lg shadow">
                                            <div class="fw-bold">Selected Member Details</div>
                                            <tr class="text-black">
                                                <th>Full Name: </th>
                                                <th>{{ $member->first_name }} {{ $member->middle_name }} {{ $member->last_name }}</th>
                                            </tr>
                                        </div>
                                    </div>
                                </div>
                            @endif




                        <div class="mt-2"></div>


                        <x-jet-label for="source_account_number" value="{{ __(' Account Number') }}" />
                        <x-jet-input id="source_account_number" wire:model="source_account_number" name="source_account_number"  type="text"  class="mt-1 block w-full"  autofocus />
                        @error('department_name')
                        <div class="border border-red-400 rounded-b bg-red-100 px-4 py-3 text-red-700 mt-1">
                            <p> Name is mandatory.</p>
                        </div>
                        @enderror



                        <x-jet-label for="source_bank_id" value="{{ __('Bank Name ') }}" />
                        <select wire:model="source_bank_id" name="source_bank_id" id="source_bank_id" class="mt-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value=""  unselected >Select ....</option>
                            @foreach ($bank_list as  $bank)
                               <option  value="{{ $bank->id }}" >{{ $bank->bank_name }} </option>
                            @endforeach

                        </select>

                        {{-- <div>
                            <label for="bank_name" class="block font-medium text-gray-700">Bank Name</label>
                            <input type="text" id="bank_name" name="bank_name" placeholder="Bank Name" class="mt-1 block w-full border border-gray-300 p-2 rounded-md">
                        </div> --}}


                        <div class="flex items-center justify-center my-4">
                            <div class="flex-grow border-t border-gray-300"></div>
                            <span class="px-4 text-lg font-semibold text-gray-700"> Beneficiary Details (Recipient) </span>
                            <div class="flex-grow border-t border-gray-300"></div>
                        </div>



                        <div>
                            <label for="beneficiary_name" class="block font-medium text-gray-700">Full Name</label>
                            <input type="text" id="beneficiary_name" wire:model="destination_account_name" name="beneficiary_name" disabled  class="mt-1 block w-full border border-gray-300 p-2 rounded-md">
                        </div>

                        <div>
                            <label for="beneficiary_name" class="block font-medium text-gray-700">Bank Name</label>
                            <input type="text" wire:model="bank" id="beneficiary_name" name="beneficiary_name" disabled value="{{ $bank_name }}" class="mt-1 block w-full border border-gray-300 p-2 rounded-md">
                        </div>



                        <x-jet-label for="account_id" value="{{ __('Account Number') }}" />
                        <select  wire:model="destination_account_id" name="destination_account_id" id="destination_account_id" class="mt-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value=""  unselected >Select ....</option>
                            @foreach (DB::table('accounts')->where('category_code',1000)->get() as  $saccoss_account)
                               <option  value="{{ $saccoss_account->id }} " >{{ $saccoss_account->account_name.' ('. $saccoss_account->account_number .')' }} </option>
                            @endforeach

                        </select>


                        <x-jet-label for="destination_type" value="{{ __('Branch Name ') }}" />
                        <select wire:model="saccos_branch_id" name="saccos_branch_id" id="saccos_branch_id" class="mt-2 bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value=""  unselected >Select ....</option>
                            @foreach ( DB::table('branches')->where('status',"ACTIVE")->get() as  $data)
                               <option  value="{{ $data->id }}" >{{ $data->name.' ('. $data->region.' - '. $data->wilaya  .')' }} </option>
                            @endforeach
                        </select>







                        <div class="flex items-center justify-center my-4">
                            <div class="flex-grow border-t border-gray-300"></div>
                            <span class="px-4 text-lg font-semibold text-gray-700"> Debit Instruction Details </span>
                            <div class="flex-grow border-t border-gray-300"></div>
                        </div>


                        <div>
                            <label for="amount" class="block font-medium text-gray-700">Amount</label>
                            <input wire:model="amount" type="text" id="amount" name="amount" placeholder="Amount to be Debited" class="mt-1 block w-full border border-gray-300 p-2 rounded-md">
                        </div>


                        <div>
                            <label for="frequency" class="block font-medium text-gray-700">Frequency</label>
                            <select wire:model="frequency" id="frequency" name="frequency" class="mt-1 block w-full border border-gray-300 p-2 rounded-md">
                                <option value="">Select Frequency</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                            </select>
                        </div>

                        <div>
                            <label for="start_date" class="block font-medium text-gray-700">Start Date</label>
                            <input  wire:model="start_date" type="date" id="start_date" name="start_date" class="mt-1 block w-full border border-gray-300 p-2 rounded-md">
                        </div>

                        <div>
                            <label for="end_date" class="block font-medium text-gray-700">End Date/Until Further Notice</label>
                            <input wire:model="end_date" type="date" id="end_date" name="end_date" class="mt-1 block w-full border border-gray-300 p-2 rounded-md">
                        </div>

                        <div>
                            <label for="payment_reference" class="block font-medium text-gray-700">Payment Reference</label>
                            <input wire:model="reference_number" type="text" disabled id="payment_reference"  name="payment_reference" placeholder="Payment Reference" class="mt-1 block w-full border border-gray-300 p-2 rounded-md">
                        </div>

                        <div class="flex items-center justify-center my-4">
                            <div class="flex-grow border-t border-gray-300"></div>
                            <span class="px-4 text-lg font-semibold text-gray-700"> Select Service</span>
                            <div class="flex-grow border-t border-gray-300"></div>
                        </div>



                        <div class="flex items-center mb-4">
                            <input id="savings-radio" wire:model="service" type="radio" value="Savings" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="savings-radio" class="ms-2 text-sm font-medium text-gray-400 dark:text-gray-500">Savings</label>
                        </div>

                        <div class="flex items-center">
                            <input id="deposit-radio" wire:model="service" type="radio" value="Deposit" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="deposit-radio" class="ms-2 text-sm font-medium text-gray-400 dark:text-gray-500">Deposit</label>
                        </div>

                        <div class="flex items-center">
                            <input id="loan-radio" wire:model="service" type="radio" value="Loan" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="loan-radio" class="ms-2 text-sm font-medium text-gray-400 dark:text-gray-500">Loan</label>
                        </div>

                        <div class="flex items-center">
                            <input id="share-radio" wire:model="service" type="radio" value="Share" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                            <label for="share-radio" class="ms-2 text-sm font-medium text-gray-400 dark:text-gray-500">Shares</label>
                        </div>



                            <label for="message" class="block mt-4 mb-2 text-sm font-medium text-gray-900 dark:text-white"> Description</label>
                            <textarea id="message" wire:model="description" rows="4" class="block p-2.5 w-full mb-4 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500" placeholder="Write your thoughts here..."></textarea>




                    </div>
                    <!-- Add more form fields as needed -->
                    <div class="flex items-center bg-gray-50 justify-end py-3 sm:px-6 sm:rounded-bl-lg sm:rounded-br-lg">
                        <button type="button" wire:click="$toggle('new_stannding_order')" class="mr-4 inline-flex justify-center px-4 py-2 text-sm font-medium   border border-transparent rounded-md  focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2  bg-white">
                            Cancel
                        </button>
                        <button type="submit" wire:click="register()" class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-blue-900 border border-transparent rounded-md  focus-visible:ring-2 focus-visible:ring-offset-2 ">
                            Proceed
                        </button>
                    </div>
                </div>
            </div>
        </div>




    @endif

</div>
