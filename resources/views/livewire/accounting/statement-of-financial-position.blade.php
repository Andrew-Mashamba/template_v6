<div class="bg-white rounded-lg shadow-sm">
    <!-- Professional Header -->
    <div class="bg-gradient-to-r from-blue-900 to-blue-800 text-white px-4 py-3 rounded-t-lg">
        <div class="text-center">
            <h1 class="text-xl font-bold uppercase">{{ $companyName }}</h1>
            <h2 class="text-lg font-semibold">STATEMENT OF FINANCIAL POSITION</h2>
            <p class="text-sm">As at 31 December {{ $selectedYear }}</p>
        </div>
    </div>
    
    <!-- Control Panel -->
    <div class="px-4 py-2 bg-gray-50 border-b border-gray-200">
        <div class="flex justify-between items-center">
            <div class="flex items-center space-x-4">
                <div class="flex items-center space-x-2">
                    <label class="text-xs font-medium text-gray-700">Year:</label>
                    <select wire:model="selectedYear" class="text-xs border-gray-300 rounded-md shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                            <option value="{{ $year }}">{{ $year }}</option>
                        @endfor
                    </select>
                </div>
                <div class="flex items-center space-x-2">
                    <label class="text-xs font-medium text-gray-700">View Level:</label>
                    <select wire:model="viewLevel" wire:change="loadFinancialData" class="text-xs border-gray-300 rounded-md shadow-sm">
                        <option value="2">L2 - Summary</option>
                        <option value="3">L3 - Detailed</option>
                        <option value="4">L4 - Full Detail</option>
                    </select>
                </div>
            </div>
            <div class="flex space-x-2">
                <button wire:click="exportToExcel" class="px-3 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700">
                    Export Excel
                </button>
                <button wire:click="exportToPDF" class="px-3 py-1 bg-red-600 text-white text-xs rounded hover:bg-red-700">
                    Export PDF
                </button>
            </div>
        </div>
    </div>
    
    <!-- Currency Header -->
    <div class="px-4 py-2">
        <table class="ml-auto text-xs border-collapse border border-gray-300">
            <thead>
                <tr>
                    @foreach($comparisonYears as $year)
                    <th class="border border-gray-300 px-3 py-1 bg-gray-50">31.12.{{ $year }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                <tr>
                    @foreach($comparisonYears as $year)
                    <td class="border border-gray-300 px-3 py-1 text-center">TZS</td>
                    @endforeach
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Main Content Area -->
    <div class="p-4">
        
        <!-- ASSETS Section -->
        <div class="mb-6">
            <h3 class="text-sm font-bold uppercase bg-blue-900 text-white px-3 py-2 rounded">ASSETS</h3>
            
            <!-- Current Assets -->
            @if(count($assetsData['current']) > 0)
            <div class="mt-3">
                <h4 class="text-xs font-semibold text-gray-700 mb-2 px-2">Current Assets</h4>
                <table class="w-full text-xs border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="border border-gray-300 px-2 py-1 text-left w-2/5">Account</th>
                            <th class="border border-gray-300 px-2 py-1 text-center w-1/12">Note</th>
                            @foreach($comparisonYears as $year)
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/5">{{ $year }}</th>
                            @endforeach
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/6">Change %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assetsData['current'] as $index => $asset)
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-2 py-1 w-2/5">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $asset['account_name'] }}</span>
                                    <div class="flex space-x-1">
                                        @if(count($asset['children']) > 0 || DB::table('accounts')->where('parent_account_number', $asset['account_number'])->exists())
                                        <button wire:click="toggleCategory('{{ $asset['account_number'] }}')" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-3 h-3 transform {{ in_array($asset['account_number'], $expandedCategories) ? 'rotate-90' : '' }}" 
                                                 fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                                            </svg>
                                        </button>
                                        @endif
                                        <button wire:click="drillDown('{{ $asset['account_number'] }}', '{{ $asset['account_name'] }}', 2)" 
                                                class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="border border-gray-300 px-2 py-1 text-center w-1/12 text-gray-500">
                                {{ $index + 5 }}
                            </td>
                            @foreach($comparisonYears as $year)
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5 font-medium">
                                {{ $this->formatNumber($asset['years'][$year] ?? 0) }}
                            </td>
                            @endforeach
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6 {{ $this->calculateVariance($asset['years'][$comparisonYears[0]] ?? 0, $asset['years'][$comparisonYears[1]] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($this->calculateVariance($asset['years'][$comparisonYears[0]] ?? 0, $asset['years'][$comparisonYears[1]] ?? 0), 1) }}%
                            </td>
                        </tr>
                        
                        @if(in_array($asset['account_number'], $expandedCategories))
                            @foreach($asset['children'] as $l3)
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 px-2 py-1 pl-6 w-2/5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">{{ $l3['account_name'] }}</span>
                                        <div class="flex space-x-1">
                                            @if(count($l3['children']) > 0 || DB::table('accounts')->where('parent_account_number', $l3['account_number'])->exists())
                                            <button wire:click="toggleSubcategory('{{ $l3['account_number'] }}')" 
                                                    class="text-blue-500 hover:text-blue-700">
                                                <svg class="w-3 h-3 transform {{ in_array($l3['account_number'], $expandedSubcategories) ? 'rotate-90' : '' }}" 
                                                     fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                                                </svg>
                                            </button>
                                            @endif
                                            <button wire:click="showAccountDetails('{{ $l3['account_number'] }}', '{{ $l3['account_name'] }}')" 
                                                    class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                                @foreach($comparisonYears as $year)
                                <td class="border border-gray-300 px-2 py-1 text-right w-1/5 text-gray-600">
                                    {{ $this->formatNumber($l3['years'][$year] ?? 0) }}
                                </td>
                                @endforeach
                                <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                            </tr>
                            
                            @if(in_array($l3['account_number'], $expandedSubcategories))
                                @foreach($l3['children'] as $l4)
                                <tr class="bg-blue-50">
                                    <td class="border border-gray-300 px-2 py-1 pl-10 w-2/5">
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-500 text-xs">{{ $l4['account_name'] }}</span>
                                            <button wire:click="showAccountDetails('{{ $l4['account_number'] }}', '{{ $l4['account_name'] }}')" 
                                                    class="text-gray-400 hover:text-gray-600">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                    <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                                    @foreach($comparisonYears as $year)
                                    <td class="border border-gray-300 px-2 py-1 text-right w-1/5 text-xs text-gray-500">
                                        {{ $this->formatNumber($l4['years'][$year] ?? 0) }}
                                    </td>
                                    @endforeach
                                    <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                                </tr>
                                @endforeach
                            @endif
                            @endforeach
                        @endif
                        @endforeach
                        
                        <!-- Current Assets Subtotal -->
                        <tr class="bg-blue-100 font-semibold">
                            <td class="border border-gray-300 px-2 py-1 w-2/5">Total Current Assets</td>
                            <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                            @php
                                $currentAssetsTotal = [];
                                foreach($comparisonYears as $year) {
                                    $currentAssetsTotal[$year] = 0;
                                    foreach($assetsData['current'] as $asset) {
                                        $currentAssetsTotal[$year] += $asset['years'][$year] ?? 0;
                                    }
                                }
                            @endphp
                            @foreach($comparisonYears as $year)
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5">
                                {{ $this->formatNumber($currentAssetsTotal[$year]) }}
                            </td>
                            @endforeach
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif
            
            <!-- Non-Current Assets -->
            @if(count($assetsData['non_current']) > 0)
            <div class="mt-3">
                <h4 class="text-xs font-semibold text-gray-700 mb-2 px-2">Non-Current Assets</h4>
                <table class="w-full text-xs border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="border border-gray-300 px-2 py-1 text-left w-2/5">Account</th>
                            <th class="border border-gray-300 px-2 py-1 text-center w-1/12">Note</th>
                            @foreach($comparisonYears as $year)
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/5">{{ $year }}</th>
                            @endforeach
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/6">Change %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assetsData['non_current'] as $index => $asset)
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-2 py-1 w-2/5">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $asset['account_name'] }}</span>
                                    <div class="flex space-x-1">
                                        @if(count($asset['children']) > 0 || DB::table('accounts')->where('parent_account_number', $asset['account_number'])->exists())
                                        <button wire:click="toggleCategory('{{ $asset['account_number'] }}')" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-3 h-3 transform {{ in_array($asset['account_number'], $expandedCategories) ? 'rotate-90' : '' }}" 
                                                 fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                                            </svg>
                                        </button>
                                        @endif
                                        <button wire:click="drillDown('{{ $asset['account_number'] }}', '{{ $asset['account_name'] }}', 2)" 
                                                class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="border border-gray-300 px-2 py-1 text-center w-1/12 text-gray-500">
                                {{ count($assetsData['current']) + $index + 5 }}
                            </td>
                            @foreach($comparisonYears as $year)
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5 font-medium">
                                {{ $this->formatNumber($asset['years'][$year] ?? 0) }}
                            </td>
                            @endforeach
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6 {{ $this->calculateVariance($asset['years'][$comparisonYears[0]] ?? 0, $asset['years'][$comparisonYears[1]] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($this->calculateVariance($asset['years'][$comparisonYears[0]] ?? 0, $asset['years'][$comparisonYears[1]] ?? 0), 1) }}%
                            </td>
                        </tr>
                        
                        @if(in_array($asset['account_number'], $expandedCategories))
                            @foreach($asset['children'] as $l3)
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 px-2 py-1 pl-6 w-2/5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-gray-600">{{ $l3['account_name'] }}</span>
                                        <button wire:click="showAccountDetails('{{ $l3['account_number'] }}', '{{ $l3['account_name'] }}')" 
                                                class="text-gray-400 hover:text-gray-600">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                                <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                                @foreach($comparisonYears as $year)
                                <td class="border border-gray-300 px-2 py-1 text-right w-1/5 text-gray-600">
                                    {{ $this->formatNumber($l3['years'][$year] ?? 0) }}
                                </td>
                                @endforeach
                                <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                            </tr>
                            @endforeach
                        @endif
                        @endforeach
                        
                        <!-- Non-Current Assets Subtotal -->
                        <tr class="bg-blue-100 font-semibold">
                            <td class="border border-gray-300 px-2 py-1 w-2/5">Total Non-Current Assets</td>
                            <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                            @php
                                $nonCurrentAssetsTotal = [];
                                foreach($comparisonYears as $year) {
                                    $nonCurrentAssetsTotal[$year] = 0;
                                    foreach($assetsData['non_current'] as $asset) {
                                        $nonCurrentAssetsTotal[$year] += $asset['years'][$year] ?? 0;
                                    }
                                }
                            @endphp
                            @foreach($comparisonYears as $year)
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5">
                                {{ $this->formatNumber($nonCurrentAssetsTotal[$year]) }}
                            </td>
                            @endforeach
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif
            
            <!-- Total Assets -->
            <table class="w-full text-xs border-collapse border border-gray-300 mt-2">
                <tbody>
                    <tr class="bg-blue-900 text-white font-bold">
                        <td class="border border-gray-300 px-2 py-2 w-2/5">TOTAL ASSETS</td>
                        <td class="border border-gray-300 px-2 py-2 text-center w-1/12"></td>
                        @foreach($comparisonYears as $year)
                        <td class="border border-gray-300 px-2 py-2 text-right w-1/5">
                            {{ $this->formatNumber($assetsData['total'][$year] ?? 0) }}
                        </td>
                        @endforeach
                        <td class="border border-gray-300 px-2 py-2 text-right w-1/6">
                            {{ number_format($this->calculateVariance($assetsData['total'][$comparisonYears[0]] ?? 0, $assetsData['total'][$comparisonYears[1]] ?? 0), 1) }}%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- EQUITY AND LIABILITIES Section -->
        <div class="mb-6">
            <h3 class="text-sm font-bold uppercase bg-blue-900 text-white px-3 py-2 rounded">EQUITY AND LIABILITIES</h3>
            
            <!-- Equity -->
            <div class="mt-3">
                <h4 class="text-xs font-semibold text-gray-700 mb-2 px-2">Members' Equity</h4>
                <table class="w-full text-xs border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="border border-gray-300 px-2 py-1 text-left w-2/5">Account</th>
                            <th class="border border-gray-300 px-2 py-1 text-center w-1/12">Note</th>
                            @foreach($comparisonYears as $year)
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/5">{{ $year }}</th>
                            @endforeach
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/6">Change %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_merge($equityData['current'], $equityData['non_current']) as $index => $equity)
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-2 py-1 w-2/5">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $equity['account_name'] }}</span>
                                    <div class="flex space-x-1">
                                        @if(count($equity['children']) > 0 || DB::table('accounts')->where('parent_account_number', $equity['account_number'])->exists())
                                        <button wire:click="toggleCategory('{{ $equity['account_number'] }}')" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-3 h-3 transform {{ in_array($equity['account_number'], $expandedCategories) ? 'rotate-90' : '' }}" 
                                                 fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                                            </svg>
                                        </button>
                                        @endif
                                        <button wire:click="drillDown('{{ $equity['account_number'] }}', '{{ $equity['account_name'] }}', 2)" 
                                                class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="border border-gray-300 px-2 py-1 text-center w-1/12 text-gray-500">
                                {{ $index + 7 }}
                            </td>
                            @foreach($comparisonYears as $year)
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5 font-medium">
                                {{ $this->formatNumber($equity['years'][$year] ?? 0) }}
                            </td>
                            @endforeach
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6 {{ $this->calculateVariance($equity['years'][$comparisonYears[0]] ?? 0, $equity['years'][$comparisonYears[1]] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($this->calculateVariance($equity['years'][$comparisonYears[0]] ?? 0, $equity['years'][$comparisonYears[1]] ?? 0), 1) }}%
                            </td>
                        </tr>
                        
                        @if(in_array($equity['account_number'], $expandedCategories))
                            @foreach($equity['children'] as $l3)
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 px-2 py-1 pl-6 w-2/5">
                                    <span class="text-gray-600">{{ $l3['account_name'] }}</span>
                                </td>
                                <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                                @foreach($comparisonYears as $year)
                                <td class="border border-gray-300 px-2 py-1 text-right w-1/5 text-gray-600">
                                    {{ $this->formatNumber($l3['years'][$year] ?? 0) }}
                                </td>
                                @endforeach
                                <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                            </tr>
                            @endforeach
                        @endif
                        @endforeach
                        
                        <!-- Total Equity -->
                        <tr class="bg-green-100 font-semibold">
                            <td class="border border-gray-300 px-2 py-1 w-2/5">Total Equity</td>
                            <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                            @foreach($comparisonYears as $year)
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5">
                                {{ $this->formatNumber($equityData['total'][$year] ?? 0) }}
                            </td>
                            @endforeach
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Current Liabilities -->
            @if(count($liabilitiesData['current']) > 0)
            <div class="mt-3">
                <h4 class="text-xs font-semibold text-gray-700 mb-2 px-2">Current Liabilities</h4>
                <table class="w-full text-xs border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="border border-gray-300 px-2 py-1 text-left w-2/5">Account</th>
                            <th class="border border-gray-300 px-2 py-1 text-center w-1/12">Note</th>
                            @foreach($comparisonYears as $year)
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/5">{{ $year }}</th>
                            @endforeach
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/6">Change %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($liabilitiesData['current'] as $index => $liability)
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-2 py-1 w-2/5">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $liability['account_name'] }}</span>
                                    <div class="flex space-x-1">
                                        @if(count($liability['children']) > 0 || DB::table('accounts')->where('parent_account_number', $liability['account_number'])->exists())
                                        <button wire:click="toggleCategory('{{ $liability['account_number'] }}')" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-3 h-3 transform {{ in_array($liability['account_number'], $expandedCategories) ? 'rotate-90' : '' }}" 
                                                 fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                                            </svg>
                                        </button>
                                        @endif
                                        <button wire:click="drillDown('{{ $liability['account_number'] }}', '{{ $liability['account_name'] }}', 2)" 
                                                class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="border border-gray-300 px-2 py-1 text-center w-1/12 text-gray-500">
                                {{ $index + 6 }}
                            </td>
                            @foreach($comparisonYears as $year)
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5 font-medium">
                                {{ $this->formatNumber($liability['years'][$year] ?? 0) }}
                            </td>
                            @endforeach
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6 {{ $this->calculateVariance($liability['years'][$comparisonYears[0]] ?? 0, $liability['years'][$comparisonYears[1]] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($this->calculateVariance($liability['years'][$comparisonYears[0]] ?? 0, $liability['years'][$comparisonYears[1]] ?? 0), 1) }}%
                            </td>
                        </tr>
                        
                        @if(in_array($liability['account_number'], $expandedCategories))
                            @foreach($liability['children'] as $l3)
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 px-2 py-1 pl-6 w-2/5">
                                    <span class="text-gray-600">{{ $l3['account_name'] }}</span>
                                </td>
                                <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                                @foreach($comparisonYears as $year)
                                <td class="border border-gray-300 px-2 py-1 text-right w-1/5 text-gray-600">
                                    {{ $this->formatNumber($l3['years'][$year] ?? 0) }}
                                </td>
                                @endforeach
                                <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                            </tr>
                            @endforeach
                        @endif
                        @endforeach
                        
                        <!-- Current Liabilities Subtotal -->
                        <tr class="bg-yellow-100 font-semibold">
                            <td class="border border-gray-300 px-2 py-1 w-2/5">Total Current Liabilities</td>
                            <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                            @php
                                $currentLiabilitiesTotal = [];
                                foreach($comparisonYears as $year) {
                                    $currentLiabilitiesTotal[$year] = 0;
                                    foreach($liabilitiesData['current'] as $liability) {
                                        $currentLiabilitiesTotal[$year] += $liability['years'][$year] ?? 0;
                                    }
                                }
                            @endphp
                            @foreach($comparisonYears as $year)
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5">
                                {{ $this->formatNumber($currentLiabilitiesTotal[$year]) }}
                            </td>
                            @endforeach
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif
            
            <!-- Non-Current Liabilities -->
            @if(count($liabilitiesData['non_current']) > 0)
            <div class="mt-3">
                <h4 class="text-xs font-semibold text-gray-700 mb-2 px-2">Non-Current Liabilities</h4>
                <table class="w-full text-xs border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="border border-gray-300 px-2 py-1 text-left w-2/5">Account</th>
                            <th class="border border-gray-300 px-2 py-1 text-center w-1/12">Note</th>
                            @foreach($comparisonYears as $year)
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/5">{{ $year }}</th>
                            @endforeach
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/6">Change %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($liabilitiesData['non_current'] as $index => $liability)
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-2 py-1 w-2/5">
                                <div class="flex items-center justify-between">
                                    <span class="font-medium">{{ $liability['account_name'] }}</span>
                                    <div class="flex space-x-1">
                                        @if(count($liability['children']) > 0 || DB::table('accounts')->where('parent_account_number', $liability['account_number'])->exists())
                                        <button wire:click="toggleCategory('{{ $liability['account_number'] }}')" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-3 h-3 transform {{ in_array($liability['account_number'], $expandedCategories) ? 'rotate-90' : '' }}" 
                                                 fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" />
                                            </svg>
                                        </button>
                                        @endif
                                        <button wire:click="drillDown('{{ $liability['account_number'] }}', '{{ $liability['account_name'] }}', 2)" 
                                                class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="border border-gray-300 px-2 py-1 text-center w-1/12 text-gray-500">
                                {{ count($liabilitiesData['current']) + $index + 6 }}
                            </td>
                            @foreach($comparisonYears as $year)
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5 font-medium">
                                {{ $this->formatNumber($liability['years'][$year] ?? 0) }}
                            </td>
                            @endforeach
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6 {{ $this->calculateVariance($liability['years'][$comparisonYears[0]] ?? 0, $liability['years'][$comparisonYears[1]] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($this->calculateVariance($liability['years'][$comparisonYears[0]] ?? 0, $liability['years'][$comparisonYears[1]] ?? 0), 1) }}%
                            </td>
                        </tr>
                        
                        @if(in_array($liability['account_number'], $expandedCategories))
                            @foreach($liability['children'] as $l3)
                            <tr class="bg-gray-50">
                                <td class="border border-gray-300 px-2 py-1 pl-6 w-2/5">
                                    <span class="text-gray-600">{{ $l3['account_name'] }}</span>
                                </td>
                                <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                                @foreach($comparisonYears as $year)
                                <td class="border border-gray-300 px-2 py-1 text-right w-1/5 text-gray-600">
                                    {{ $this->formatNumber($l3['years'][$year] ?? 0) }}
                                </td>
                                @endforeach
                                <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                            </tr>
                            @endforeach
                        @endif
                        @endforeach
                        
                        <!-- Non-Current Liabilities Subtotal -->
                        <tr class="bg-yellow-100 font-semibold">
                            <td class="border border-gray-300 px-2 py-1 w-2/5">Total Non-Current Liabilities</td>
                            <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                            @php
                                $nonCurrentLiabilitiesTotal = [];
                                foreach($comparisonYears as $year) {
                                    $nonCurrentLiabilitiesTotal[$year] = 0;
                                    foreach($liabilitiesData['non_current'] as $liability) {
                                        $nonCurrentLiabilitiesTotal[$year] += $liability['years'][$year] ?? 0;
                                    }
                                }
                            @endphp
                            @foreach($comparisonYears as $year)
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5">
                                {{ $this->formatNumber($nonCurrentLiabilitiesTotal[$year]) }}
                            </td>
                            @endforeach
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            @endif
            
            <!-- Total Liabilities -->
            <table class="w-full text-xs border-collapse border border-gray-300 mt-2">
                <tbody>
                    <tr class="bg-orange-100 font-semibold">
                        <td class="border border-gray-300 px-2 py-1 w-2/5">Total Liabilities</td>
                        <td class="border border-gray-300 px-2 py-1 text-center w-1/12"></td>
                        @foreach($comparisonYears as $year)
                        <td class="border border-gray-300 px-2 py-1 text-right w-1/5">
                            {{ $this->formatNumber($liabilitiesData['total'][$year] ?? 0) }}
                        </td>
                        @endforeach
                        <td class="border border-gray-300 px-2 py-1 text-right w-1/6"></td>
                    </tr>
                </tbody>
            </table>
            
            <!-- Total Equity and Liabilities -->
            <table class="w-full text-xs border-collapse border border-gray-300 mt-2">
                <tbody>
                    <tr class="bg-blue-900 text-white font-bold">
                        <td class="border border-gray-300 px-2 py-2 w-2/5">TOTAL EQUITY AND LIABILITIES</td>
                        <td class="border border-gray-300 px-2 py-2 text-center w-1/12"></td>
                        @foreach($comparisonYears as $year)
                        <td class="border border-gray-300 px-2 py-2 text-right w-1/5">
                            {{ $this->formatNumber($summaryData[$year]['total_liabilities_equity'] ?? 0) }}
                        </td>
                        @endforeach
                        <td class="border border-gray-300 px-2 py-2 text-right w-1/6">
                            {{ number_format($this->calculateVariance(
                                $summaryData[$comparisonYears[0]]['total_liabilities_equity'] ?? 0, 
                                $summaryData[$comparisonYears[1]]['total_liabilities_equity'] ?? 0
                            ), 1) }}%
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Balance Check -->
        @if(abs($summaryData[$comparisonYears[0]]['balance_check'] ?? 0) > 0.01)
        <div class="mt-4 p-3 bg-red-50 border border-red-300 rounded">
            <p class="text-xs text-red-700">
                <strong>Warning:</strong> The balance sheet is not balanced. 
                Difference: {{ $this->formatNumber($summaryData[$comparisonYears[0]]['balance_check']) }}
            </p>
        </div>
        @endif
    </div>
    
    <!-- Drill-Down Modal -->
    @if($selectedCategory)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[80vh] overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 text-white px-4 py-3">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold">Account Details</h3>
                        <p class="text-xs">{{ $selectedCategory['name'] }} ({{ $selectedCategory['number'] }})</p>
                    </div>
                    <button wire:click="closeDrillDown" class="text-white hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-4 overflow-y-auto max-h-[calc(80vh-100px)]">
                <table class="w-full text-xs border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100">
                            <th class="border border-gray-300 px-2 py-1 text-left w-2/5">Account</th>
                            @foreach($comparisonYears as $year)
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/5">{{ $year }}</th>
                            @endforeach
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/5">Change %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($drillDownData as $account)
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-2 py-1 w-2/5">
                                <div class="flex items-center justify-between">
                                    <span>{{ $account['account_name'] }}</span>
                                    <div class="flex space-x-1">
                                        @if($account['has_children'])
                                        <button wire:click="drillDown('{{ $account['account_number'] }}', '{{ $account['account_name'] }}', {{ $account['account_level'] }})" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                        @endif
                                        <button wire:click="showAccountDetails('{{ $account['account_number'] }}', '{{ $account['account_name'] }}')" 
                                                class="text-gray-500 hover:text-gray-700">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            @foreach($comparisonYears as $year)
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5">
                                {{ $this->formatNumber($account['years'][$year] ?? 0) }}
                            </td>
                            @endforeach
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/5 {{ $this->calculateVariance($account['years'][$comparisonYears[0]] ?? 0, $account['years'][$comparisonYears[1]] ?? 0) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                {{ number_format($this->calculateVariance($account['years'][$comparisonYears[0]] ?? 0, $account['years'][$comparisonYears[1]] ?? 0), 1) }}%
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Account Detail Modal -->
    @if($showAccountDetail)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-xl max-w-5xl w-full max-h-[80vh] overflow-hidden">
            <div class="bg-gradient-to-r from-blue-900 to-blue-800 text-white px-4 py-3">
                <div class="flex justify-between items-center">
                    <div>
                        <h3 class="text-sm font-bold">General Ledger Transactions</h3>
                        <p class="text-xs">{{ $selectedAccountForDetail['name'] }} ({{ $selectedAccountForDetail['number'] }})</p>
                    </div>
                    <button wire:click="closeAccountDetail" class="text-white hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
            
            <div class="p-4 overflow-y-auto max-h-[calc(80vh-100px)]">
                <table class="w-full text-xs border-collapse border border-gray-300">
                    <thead>
                        <tr class="bg-gray-100 text-gray-700">
                            <th class="border border-gray-300 px-2 py-1 text-left w-1/6">Date</th>
                            <th class="border border-gray-300 px-2 py-1 text-left w-1/6">Reference</th>
                            <th class="border border-gray-300 px-2 py-1 text-left w-2/6">Description</th>
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/6">Debit</th>
                            <th class="border border-gray-300 px-2 py-1 text-right w-1/6">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($accountTransactions as $transaction)
                        <tr class="hover:bg-gray-50">
                            <td class="border border-gray-300 px-2 py-1 w-1/6">{{ $transaction['date'] }}</td>
                            <td class="border border-gray-300 px-2 py-1 w-1/6">{{ $transaction['reference'] }}</td>
                            <td class="border border-gray-300 px-2 py-1 w-2/6">{{ $transaction['description'] }}</td>
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6">
                                {{ $transaction['debit'] > 0 ? number_format($transaction['debit'], 2) : '-' }}
                            </td>
                            <td class="border border-gray-300 px-2 py-1 text-right w-1/6">
                                {{ $transaction['credit'] > 0 ? number_format($transaction['credit'], 2) : '-' }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>