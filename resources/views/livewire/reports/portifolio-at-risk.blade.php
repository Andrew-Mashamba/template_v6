<div>
    {{-- Success is as dangerous as failure. --}}

    <div class="grid gap-4 grid-cols-1 grid-cols-4 my-2 w-full">

        <div class="metric-card @if($this->selected==10) rounded-lg p-4  bg-blue-900 text-white @else   dark:bg-gray-900 border  bg-white  border-gray-200 dark:border-gray-800  rounded-lg p-4   w-full @endif ">

            <div class="flex justify-between items-center w-full">
                <div class="flex items-center">
                    <div wire:loading="" wire:target="visit(10)">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="animate-spin  h-5 w-5 mr-2 stroke-gray-400" fill="white" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <p>Please wait...</p>
                        </div>

                    </div>
                    <div wire:loading.remove="" wire:target="visit(10)">
                        <div class="flex items-center text-l font-semibold spacing-sm text-slate-600"> PAR 1 - 10 (Days)
                        </div>
                    </div>

                </div>
                <div class="flex items-center space-x-5">

                    <svg wire:click="visit(10)" xmlns="http://www.w3.org/2000/svg" class="cursor-pointer h-9 bg-slate-50 rounded-full stroke-slate-400 p-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>


                </div>
            </div>
            <table>

                <tbody><tr>
                    <td class="mt-2 text-sm font-semibold    text-slate-400  dark:text-white capitalize  ">Number of Loans </td>
                    <td class="pl-2 mt-2 text-sm font-semibold spacing-sm   text-slate-400   dark:text-white text-right">

                        {{ $count10 }}
                    </td>
                </tr>

                <tr>
                    <td class="mt-2 text-sm font-semibold    text-slate-400  dark:text-white capitalize  ">Total amount </td>
                    <td class="pl-2 mt-2 text-sm font-semibold spacing-sm   text-slate-400   dark:text-white text-right">

                        {{ number_format($below10,1) }} TZS
                    </td>
                </tr>


                </tbody>
            </table>
        </div>

        <div class="metric-card  @if($this->selected==30) rounded-lg p-4  bg-blue-900 text-white @else   dark:bg-gray-900 border  bg-white  border-gray-200 dark:border-gray-800  rounded-lg p-4  w-full  @endif ">
            <div class="flex justify-between items-center w-full">
                <div class="flex items-center">
                    <div wire:loading="" wire:target="visit(30)">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="animate-spin  h-5 w-5 mr-2 stroke-gray-400" fill="white" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                            </svg>
                            <p>Please wait...</p>
                        </div>
                     </div>
                    <div wire:loading.remove="" wire:target="visit(30)">
                        <div class="flex items-center text-l font-semibold spacing-sm text-slate-600"> PAR 10 - 30 (Days)
                        </div>
                    </div>
                </div>
                <div class="flex items-center space-x-5">
                    <svg wire:click="visit(30)" xmlns="http://www.w3.org/2000/svg" class="cursor-pointer h-9 bg-slate-50 rounded-full stroke-slate-400 p-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <table>
                <tbody>
                    <tr>
                    <td class="mt-2 text-sm font-semibold    text-slate-400  dark:text-white capitalize  "> Total Loan </td>
                    <td class="pl-2 mt-2 text-sm font-semibold spacing-sm   text-slate-400   dark:text-white text-right">
                     {{$count30}}
                    </td>
                </tr>

                <tr>
                    <td class="mt-2 text-sm font-semibold    text-slate-400  dark:text-white capitalize  ">Total amount</td>
                    <td class="pl-2 mt-2 text-sm font-semibold spacing-sm   text-slate-400   dark:text-white text-right">

                       {{ number_format($below30,1) }} TZS
                    </td>
                </tr>
            </tbody>
        </table>
        </div>


        <div class="metric-card @if($this->selected==40) rounded-lg p-4  bg-blue-900 text-white @else   dark:bg-gray-900 border  bg-white  border-gray-200 dark:border-gray-800  rounded-lg p-4   w-full @endif ">

            <div class="flex justify-between items-center w-full">
                <div class="flex items-center">
                    <div wire:loading="" wire:target="visit(40)">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="animate-spin  h-5 w-5 mr-2 stroke-gray-400" fill="white" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>

                            </svg>


                            <p>Please wait...</p>
                        </div>

                    </div>
                    <div wire:loading.remove="" wire:target="visit(40)">


                        <div class="flex items-center text-l font-semibold spacing-sm text-slate-600"> PAR 30 - 90 (Days)

                        </div>

                    </div>

                </div>
                <div class="flex items-center space-x-5">

                    <svg wire:click="visit(40)" xmlns="http://www.w3.org/2000/svg" class="cursor-pointer h-9 bg-slate-50 rounded-full stroke-slate-400 p-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>


                </div>
            </div>


            <table>

                <tbody><tr>
                    <td class="mt-2 text-sm font-semibold    text-slate-400  dark:text-white capitalize  ">Total Number</td>
                    <td class="pl-2 mt-2 text-sm font-semibold spacing-sm   text-slate-400   dark:text-white text-right">

                       {{$count60 }}
                    </td>
                </tr>

                <tr>
                    <td class="mt-2 text-sm font-semibold    text-slate-400  dark:text-white capitalize  ">Total  Amount </td>
                    <td class="pl-2 mt-2 text-sm font-semibold spacing-sm   text-slate-400   dark:text-white text-right">

                       {{  number_format($below60,1) }} TZS
                    </td>
                </tr>
            </tbody>
        </table>

    </div>

        <div class="metric-card  @if($this->selected==50) rounded-lg p-4  bg-blue-900 text-white @else   dark:bg-gray-900 border  bg-white  border-gray-200 dark:border-gray-800  rounded-lg p-4   w-full @endif ">

            <div class="flex justify-between items-center w-full">
                <div class="flex items-center">
                    <div wire:loading="" wire:target="visit(50)">
                        <div class="flex items-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="animate-spin  h-5 w-5 mr-2 stroke-gray-400" fill="white" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>

                            </svg>
                            <p>Please wait...</p>
                        </div>

                    </div>
                    <div wire:loading.remove="" wire:target="visit(50)">

                        <div class="flex items-center text-l font-semibold spacing-sm text-slate-600"> PAR Above 90 (Days)

                        </div>

                    </div>

                </div>
                <div class="flex items-center space-x-5">

                    <svg wire:click="visit(50)" xmlns="http://www.w3.org/2000/svg" class="cursor-pointer h-9 bg-slate-50 rounded-full stroke-slate-400 p-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M3 14h18m-9-4v8m-7 0h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>


                </div>
            </div>


            <table>
                <tbody><tr>
                    <td class="mt-2 text-sm font-semibold    text-slate-400  dark:text-white capitalize  "> Total Number </td>
                    <td class="pl-2 mt-2 text-sm font-semibold spacing-sm   text-slate-400   dark:text-white text-right">

                       {{ $count90 }}
                    </td>
                </tr>

                <tr>
                    <td class="mt-2 text-sm font-semibold    text-slate-400  dark:text-white capitalize  ">Total Amont  </td>
                    <td class="pl-2 mt-2 text-sm font-semibold spacing-sm   text-slate-400   dark:text-white text-right">

                        {{ number_format($below90,1) }} TZS
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    </div>
    
    <hr class="w-full"/>


    <livewire:reports.portifolio-at-risk-table />


</div>
