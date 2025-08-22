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

                                    {{-- <div class="mb-4">
                                        <label for="user_id" class="block text-sm font-medium text-gray-700">User ID</label>
                                        <input type="number" wire:model="user_id" id="user_id" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div> --}}

                                    <div class="mb-4">
                                        <label for="source_account_id" class="block text-sm font-medium text-gray-700"> Category  </label>
                                        <select type="number" wire:model="source_account_id" id="source_account_id" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                                       <option value=""> select   </option>

                                       @foreach (DB::table('accounts')->whereIn('category_code',[2600,2700])->get() as $account )
                                           <option value="{{ $account->id }}">   {{ $account->account_name }} </option>
                                       @endforeach

                                        </select>
                                    </div>




                                    {{-- <div class="mb-4">
                                        <label for="source_account_id" class="block text-sm font-medium text-gray-700"> Category  </label>
                                        <select type="number" wire:model="destination_account_id" id="source_account_id" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                                       <option value=""> select   </option>

                                       @foreach (DB::table('other_income')->get() as $account )
                                           <option value="{{ $account->id }}">   {{ $account->account_name }} </option>
                                       @endforeach

                                        </select>
                                    </div> --}}


                                    {{-- <div class="mb-4">
                                        <label for="destination_account_id" class="block text-sm font-medium text-gray-700">Destination Account ID</label>
                                        <input type="number" wire:model="destination_account_id" id="destination_account_id" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div> --}}

                                    {{-- <div class="mb-4">
                                        <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                        <input type="text" wire:model="status" id="status" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div> --}}

                                    {{-- <div class="mb-4">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model="is_recognized" class="form-checkbox h-5 w-5 text-blue-600">
                                            <span class="ml-2 text-gray-700">Is Recognized</span>
                                        </label>
                                    </div> --}}

                                    {{-- <div class="mb-4">
                                        <label class="inline-flex items-center">
                                            <input type="checkbox" wire:model="is_delivery" class="form-checkbox h-5 w-5 text-blue-600">
                                            <span class="ml-2 text-gray-700">Is Delivery</span>
                                        </label>
                                    </div> --}}


                                    <div class="mb-4">
                                        <label for="name" class="block text-sm font-medium text-gray-700">Name of Payer</label>
                                        <input type="text" wire:model="name" id="name" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>


                                    <div class="mb-4">
                                        <label for="name" class="block text-sm font-medium text-gray-700"> Amount</label>
                                        <input type="number" wire:model="amount" id="name" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>


                                    <div class="mb-4">
                                        <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                        <input type="text" wire:model="address" id="address" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div class="mb-4">
                                        <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                        <input type="text" wire:model="phone" id="phone" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div class="mb-4">
                                        <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                        <input type="email" wire:model="email" id="email" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500">
                                    </div>

                                    <div class="mb-4">
                                        <label for="description" class="block text-sm font-medium text-gray-700">Description /Reasons </label>
                                        <textarea wire:model="description" id="description" class="mt-1 block w-full border border-gray-300 rounded-md p-2 focus:ring-blue-500 focus:border-blue-500"></textarea>
                                    </div>




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

            New deferred revenue
            </button>



    <livewire:accounting.unearned-table >


        </div>


</div>
