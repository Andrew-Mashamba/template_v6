<div>
    {{-- resources/views/livewire/accounting/loan-loss-reserve-manager.blade.php --}}
    <div class="w-full mx-auto bg-white shadow-md rounded-lg">
        <h1 class="text-lg font-bold text-center text-black-800 mb-6">Manage Loan Loss Reserves</h1>

        {{-- Success and Error Messages --}}
        @if (session()->has('message'))
            <div class="ml-4 mb-4 text-green-600 block mb-2 text-sm font-medium">
                {{ session('message') }}
            </div>
        @endif
        @if (session()->has('error'))
            <div class="ml-4 mb-4 text-red-600 block mb-2 text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif




        <div class="w-full flex gap-4 p-4">

            <div class="w-1/3">

                {{-- Loan Loss Reserve Form --}}
                <form wire:submit.prevent="{{ $editMode ? 'saveLLR' : 'allocateInitial' }}" class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                        <div>
                            <label for="profits" class="block text-sm font-medium text-gray-700">Profits (in TZS)</label>
                            <input wire:model="profits" type="number" id="profits" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500" min="0" />
                            @error('profits') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="percentage" class="block text-sm font-medium text-gray-700">Percentage (%)</label>
                            <input wire:model="percentage" type="number" id="percentage" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500" min="0" max="100" />
                            @error('percentage') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label for="reserve_amount" class="block text-sm font-medium text-gray-700">Reserve Amount</label>
                            <input wire:model="reserve_amount" type="text" id="reserve_amount" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500" readonly />
                            @error('reserve_amount') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="w-full mb-4">
                        <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Source of funds</label>
                        <select wire:model="source" class="w-full p-2 border bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full
                        p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                            <option value="">Select Account</option>
                            @php
                                $cash_table = DB::table('setup_accounts')->where('item','cash')->value('table_name');
                            @endphp
                            @foreach(\Illuminate\Support\Facades\DB::table($cash_table)->get() as $accounts)
                                <option value="{{$accounts->sub_category_code}}">{{$accounts->sub_category_name}}</option>
                            @endforeach

                        </select>
                        @error('source') <span class="text-red-500">{{ $message }}</span> @enderror
                    </div>


                    {{-- Year and Status for Initial Allocation --}}
                    @if (!$editMode)
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label for="year" class="block text-sm font-medium text-gray-700">Year</label>
                                <input wire:model="year" type="text" id="year" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500" readonly />
                            </div>
                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                                <input wire:model="status" type="text" id="status" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500" readonly />
                            </div>
                        </div>
                    @endif

                    {{-- Action Buttons --}}
                    <div class="flex justify-between mt-4">
                        @if ($editMode)
                            <button type="submit" class="px-4 py-2 bg-green-400 text-white rounded hover:bg-green-600">Save Changes</button>
                        @else
                            <button type="submit" class="px-4 py-2 bg-blue-900 text-white rounded hover:bg-blue-900">Allocate Initial Reserve</button>
                        @endif

                        @if ($editMode)
                            <button type="button" wire:click="resetForm" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600">Cancel Edit</button>
                        @endif
                    </div>
                </form>


                <hr class="boder-b-0 my-4"/>

                {{-- Adjustments Form --}}
                <form wire:submit.prevent="adjustReserve" class="mb-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="adjustmentAmount" class="block text-sm font-medium text-gray-700">Adjustment Amount (in TZS)</label>
                            <input wire:model="adjustments" type="number" id="adjustmentAmount" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500" min="0" />
                            @error('adjustments') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-yellow-500 text-white rounded hover:bg-yellow-600">Submit Adjustment</button>
                </form>


                <hr class="boder-b-0 my-4"/>

                {{-- Year-End Finalization --}}
                <form wire:submit.prevent="finalizeYearEnd">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="actualLoanLosses" class="block text-sm font-medium text-gray-700">Actual Loan Losses (in TZS)</label>
                            <input wire:model="actualLoanLosses" type="number" id="actualLoanLosses" class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500" min="0" />
                            @error('actualLoanLosses') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">Finalize Year-End</button>
                </form>

            </div>

            <div class="w-2/3">

                {{-- Loan Loss Reserve Summary --}}
                <div class="">
                    <label class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Current Loan Loss Reserve</label>
                    @if ($llr)
                        <table class="w-full text-sm text-left text-blue-100 mb-4">
                            <thead class="text-xs text-black bg-blue-100 dark:text-white w-full">
                            <tr class="border md:border-none block md:table-row w-full">
                                <th class="p-2 border-r px-6 py-4  text-xs text-black ">Year</th>
                                <th class="p-2 border-r px-6 py-4  text-xs text-black ">Initial Allocation</th>
                                <th class="p-2 border-r px-6 py-4  text-xs text-black ">Adjustments</th>
                                <th class="p-2 border-r px-6 py-4  text-xs text-black ">Total Allocation</th>
                            </tr>
                            </thead>
                            <tbody>
                            <tr class="border md:border-none block md:table-row bg-blue-50 text-black uppercase w-full">
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-left  text-xs">{{ $llr->year }}</td>
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-left  text-xs">{{ number_format($llr->initial_allocation, 2) }}</td>
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-left  text-xs">{{ number_format($llr->adjustments, 2) }}</td>
                                <td class="p-2 whitespace-nowrap px-6 py-2 text-left  text-xs">{{ number_format($llr->total_allocation, 2) }}</td>
                            </tr>
                            </tbody>
                        </table>
                    @else
                        <p class="text-gray-600">No allocations made yet for {{ $year }}.</p>
                    @endif
                </div>

            </div>

        </div>






    </div>
</div>
