<div>
    {{-- To attain knowledge, add things every day; To attain wisdom, subtract things every day. --}}

    <div class="flex w-full gap-2 mb-2 mb-2">
        <div class="ml-2">
            <label for="category" class="block mb-2  space-x-2 text-sm font-semibold spacing-sm text-slate-600">
                Start Date
            </label>
            <div class="relative ">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg aria-hidden="true" class="w-5 h-5 text-gray-500 " fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                    </svg>
                </div>
                <input  wire:model="startDate" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full pl-10 p-2.5       d" placeholder="Select date">
                {{-- <input datepicker="" datepicker-autohide="" wire:model="startDate" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full pl-10 p-2.5       d" placeholder="Select date"> --}}
            </div>
        </div>
        <div class="">
            <label for="category" class="block mb-2  space-x-2 text-sm font-semibold spacing-sm text-slate-600">
                End Date
            </label>
            <div class="relative ">
                <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                    <svg aria-hidden="true" class="w-5 h-5 text-gray-500 " fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                    </svg>
                </div>

                <input wire:model="endDate" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full pl-10 p-2.5       d" placeholder="Select date">

                {{-- <input datepicker="" datepicker-autohide="" wire:model="endDate" type="text" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full pl-10 p-2.5       d" placeholder="Select date"> --}}
            </div>
        </div>
        <div>
            <label for="nodes" class="block mb-2  space-x-2 text-sm font-semibold spacing-sm text-slate-600">
               Select Branch
            </label>

            <select wire:model.bounce="branch" name="nodes" id="nodes" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5      d">
                <option selected="" value="All">All </option>

                @foreach(DB::table('branches')->get() as $branch)
                <option  value="{{$branch->id}}">{{$branch->name}}</option>
                    @endforeach



            </select>
        </div>


{{--         not used--}}
        <div class="hidden">
            <label for="processorNodes" class="block mb-2  space-x-2 text-sm font-semibold spacing-sm text-slate-600">
                Processor Nodes
            </label>
            <select wire:model.bounce="processorNodes" name="processorNodes" id="processorNodes" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5      d">
                <option selected="" value="All">All </option>

                <option selected="" value=" ">NODE NAME</option>


            </select>

        </div>
        <div class="hidden">

            <label for="services" class="block mb-2  space-x-2 text-sm font-semibold spacing-sm text-slate-600">
                Service
            </label>
            <select wire:model.bounce="services" name="services" id="services" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5      d">
                <option selected="" value="All">All</option>

                <option selected="" value="">SERVICE NAME</option>


            </select>
        </div>
        <div class="hidden">
            <label for="channels" class="block mb-2  space-x-2 text-sm font-semibold spacing-sm text-slate-600">
                Channel
            </label>
            <select wire:model.bounce="channels" name="channels" id="channels" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-red-500 focus:border-red-500 block w-full p-2.5      d">
                <option selected="" value="All">All</option>

                <option selected="" value=""> NAME</option>


            </select>
        </div>
{{--     end--}}



        <div>
            <label for="type" class="block mb-2  space-x-2 text-sm font-semibold spacing-sm text-slate-600">
                PAR
            </label>

            <div class="flex items-center space-x-4">
                <!-- Text Input -->
{{--                <input wire:model="PAR" type="text" class="px-4 py-2 border rounded-md focus:outline-none focus:border-red-500" placeholder="eg range 1-5, eg above 2 ">--}}
                <div id="otp" class="flex flex-row justify-center text-center px-2 ">
                    <input wire:model="startRange" class="m-2 border h-10 w-10 text-center form-control rounded" type="text" id="otp5" />
                    <input  wire:model="endRange" class="m-2 border h-10 w-10 text-center form-control rounded" type="text" />
                </div>

                <!-- Button -->

            </div>

        </div>
    </div>
    {{-- <script>
        flatpickr('[datepicker]', {
            dateFormat: "Y-m-d"
            , autoHide: true
            , allowInput: true
            , minDate: "2000-01-01"
            , maxDate: new Date().fp_incr(14)
        });
    </script> --}}


    <livewire:reports.loan-delinquent />

</div>
