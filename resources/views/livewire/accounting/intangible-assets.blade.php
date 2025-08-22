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
                            <th class="p-2 border-r px-6 py-4  text-xs text-black ">Name</th>
                            <th class="p-2 border-r px-6 py-4  text-xs text-black ">Type</th>
                            <th class="p-2 border-r px-6 py-4  text-xs text-black text-right">Value</th>
                            <th class="p-2 border-r px-6 py-4  text-xs text-black ">Acquisition Date</th>
                            <th class="p-2 border-r px-6 py-4  text-xs text-black text-right">Actions</th>
                        </tr>
                        </thead>
                        <tbody class="block md:table-row-group">
                        @foreach($intangibleAssets as $asset)
                            <tr class="border md:border-none block md:table-row bg-gray-200 text-black uppercase w-full">
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-left  text-xs">{{ $asset->name }}</td>
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-left  text-xs">{{ $asset->type }}</td>
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-right  text-xs">{{ number_format($asset->value, 2) }}</td>
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-left  text-xs">{{ $asset->acquisition_date }}</td>
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-right  text-xs">
                                    <button wire:click="edit({{ $asset->id }})" class="bg-yellow-500 text-white px-2 py-1 rounded-md">Edit</button>
                                    <button wire:click="edit({{ $asset->id }})" class="bg-blue-900 text-white px-2 py-1 rounded-md">liquidate</button>
                                    <button wire:click="delete({{ $asset->id }})" class="bg-red-500 text-white px-2 py-1  rounded-md">Delete</button>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="w-1/4 bg-white rounded-md p-4">



                    <form wire:submit.prevent="{{ $isEdit ? 'update' : 'store' }}">

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Type of asset</label>
                            <select wire:model="type" class="w-full p-2 border bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full
                        p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="">Select Type Of Asset</option>
                                <option value="shortterm_assets">Short term</option>
                                <option value="longterm_assets">Long term</option>
                                <option value="intangible_assets">Intangible</option>

                            </select>
                            @error('type') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Name of the asset</label>
                            <input type="text" wire:model="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full
                        p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            @error('name') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>



                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Value of the asset</label>
                            <input type="number" wire:model="value" step="0.01" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full
                        p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            @error('value') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Cash Account</label>
                            <select wire:model="cash_account" class="w-full p-2 border bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full
                        p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="">Select Account</option>
                                @php
                                    $cashAccount = \Illuminate\Support\Facades\DB::table('setup_accounts')->where('item', 'cash')->first();
                                    $cash_code = $cashAccount->sub_category_code;
                                    $table_name = $cashAccount->table_name;
                                @endphp
                                @foreach(\Illuminate\Support\Facades\DB::table($table_name)->get() as $accounts)
                                <option value="{{$accounts->sub_category_code}}">{{$accounts->sub_category_name}}</option>
                                @endforeach

                            </select>
                            @error('source') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>


                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Asset Group</label>
                            <select wire:model="category_code" class="w-full p-2 border bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full
                        p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="">Select Account</option>
                                @php
                                    $cashAccount = \Illuminate\Support\Facades\DB::table('setup_accounts')->where('item', 'assets')->first();
                                    //$cash_code = $cashAccount->sub_category_code;
                                    $table_name = $cashAccount->table_name;
                                @endphp
                                @foreach(\Illuminate\Support\Facades\DB::table($table_name)->get() as $accounts)
                                    <option value="{{$accounts->category_code}}">{{$accounts->sub_category_name}}</option>
                                @endforeach

                            </select>
                            @error('source') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>

                        @if($this->category_code)
                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Asset Account Group</label>
                            <select wire:model="asset_sub_category_code" class="w-full p-2 border bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full
                        p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                                <option value="">Select Account</option>

                                @foreach(\Illuminate\Support\Facades\DB::table('accounts')->where('category_code', $this->category_code)->get() as $account)
                                    @if(!empty($account->sub_category_code) )
                                        <option value="{{ $account->sub_category_code }}">{{ $account->account_name }}</option>
                                    @endif
                                @endforeach

                            </select>
                            @error('source') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>

                        @endif

                        <div class="mb-4">
                            <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Acquisition Date:</label>
                            <input type="date" wire:model="acquisition_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full
                        p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            @error('acquisition_date') <span class="text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" class="bg-blue-900 text-white px-4 py-2 justify-end rounded-md">{{ $isEdit ? 'Update' : 'Register' }} Asset</button>
                    </form>


                </div>
            </div>






            <hr class="my-4">


        </div>
    </div>

</div>
