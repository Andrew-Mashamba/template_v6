<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="p-6">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-blue-900 rounded-xl shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">PPE Management</h1>
                        <p class="text-gray-600 mt-1">Track, manage, and analyze Property, Plant & Equipment</p>
                    </div>
                </div>
                <!-- Quick Stats -->
                <div class="flex items-center space-x-4">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total PPE Value</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format($this->totalPpeValue, 2) }} TZS</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Accumulated Depreciation</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format($this->totalAccumulatedDepreciation, 2) }} TZS</p>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Net Book Value</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format($this->netBookValue, 2) }} TZS</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex gap-6">
            <!-- Sidebar Navigation -->
            <div class="w-80 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="p-6 border-b border-gray-100">
                    <input type="text" wire:model="search" placeholder="Search PPE, category, location..." class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white focus:bg-white" aria-label="Search PPE" />
                </div>
                <div class="p-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4 px-2">Navigation</h3>
                    <nav class="space-y-2">
                        <button wire:click="selectMenu(1)" class="w-full flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenuItem == 1 ? 'bg-blue-900 text-white' : 'bg-gray-50 text-gray-700' }}">
                            <span class="font-medium text-sm">Dashboard</span>
                        </button>
                        <button wire:click="selectMenu(2)" class="w-full flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenuItem == 2 ? 'bg-blue-900 text-white' : 'bg-gray-50 text-gray-700' }}">
                            <span class="font-medium text-sm">Add PPE</span>
                        </button>
                        <button wire:click="selectMenu(3)" class="w-full flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenuItem == 3 ? 'bg-blue-900 text-white' : 'bg-gray-50 text-gray-700' }}">
                            <span class="font-medium text-sm">PPE List</span>
                        </button>
                        <button wire:click="selectMenu(4)" class="w-full flex items-center p-3 rounded-xl transition-all duration-200 {{ $selectedMenuItem == 4 ? 'bg-blue-900 text-white' : 'bg-gray-50 text-gray-700' }}">
                            <span class="font-medium text-sm">Categories</span>
                        </button>
                        <button wire:click="selectMenu(5)" 
                            class="w-full flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $selectedMenuItem == 5 ? 'bg-blue-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Reports
                        </button>
                        
                        <button wire:click="selectMenu(6)" 
                            class="w-full flex items-center px-4 py-2 text-sm font-medium rounded-lg transition-colors {{ $selectedMenuItem == 6 ? 'bg-blue-900 text-white' : 'text-gray-600 hover:bg-gray-100' }}">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Disposal Management
                        </button>
                    </nav>
                </div>
            </div>
            <!-- Main Content Area -->
            <div class="flex-1">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <h2 class="text-xl font-semibold text-gray-900">
                            {{-- Dynamic section title --}}
                            @switch($selectedMenuItem)
                                @case(1) Dashboard @break
                                @case(2) Add PPE @break
                                @case(3) PPE List @break
                                @case(4) Categories @break
                                @case(5) Reports @break
                                @case(6) Disposal Management @break
                                @default Dashboard
                            @endswitch
                        </h2>
                        <p class="text-gray-600 mt-1">
                            {{-- Dynamic section description --}}
                            @switch($selectedMenuItem)
                                @case(1) Overview of PPE assets and depreciation trends @break
                                @case(2) Add new PPE asset @break
                                @case(3) View and manage all PPE records @break
                                @case(4) Manage PPE categories @break
                                @case(5) Generate and export PPE reports @break
                                @case(6) Manage PPE disposal @break
                                @default Overview of PPE assets and depreciation trends
                            @endswitch
                        </p>
                    </div>
                    <div class="p-8">
                        {{-- Dynamic Content --}}
                        @switch($selectedMenuItem)
                            @case(1)
                                {{-- Dashboard Cards and Trends --}}
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                                    <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl p-6 border border-blue-200">
                                        <h3 class="text-lg font-semibold text-blue-900 mb-2">Recent PPE Additions</h3>
                                        {{-- List of recent PPEs --}}
                                        <ul>
                                            @foreach($this->recentPpes as $ppe)
                                                <li class="text-sm text-blue-900">{{ $ppe->name }} ({{ $ppe->category }}) - {{ number_format($ppe->purchase_price, 2) }} TZS</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                    <div class="bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl p-6 border border-yellow-200">
                                        <h3 class="text-lg font-semibold text-yellow-900 mb-4">Monthly Depreciation Trend</h3>
                                        <div class="h-32">
                                            <canvas id="depreciationChart" width="400" height="200"></canvas>
                                        </div>
                                        <div class="mt-3 text-center">
                                            <span class="text-sm text-yellow-700">Current Month: {{ number_format($this->monthlyDepreciation, 2) }} TZS</span>
                                        </div>
                                    </div>
                                    <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-xl p-6 border border-green-200">
                                        <h3 class="text-lg font-semibold text-green-900 mb-2">Pending Disposals</h3>
                                        <ul>
                                            @foreach($this->pendingDisposals as $ppe)
                                                <li class="text-sm text-green-900">{{ $ppe->name }} ({{ $ppe->category }})</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                                @break
                            @case(2)
                                {{-- PPE Add/Edit Form --}}
                                <div class="max-w-4xl">
                                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                            <h3 class="text-lg font-semibold text-gray-900">
                                                {{ $isEditMode ? 'Edit PPE Asset' : 'Add New PPE Asset' }}
                                            </h3>
                                            <p class="text-sm text-gray-600 mt-1">
                                                {{ $isEditMode ? 'Update asset information and depreciation settings' : 'Enter asset details and configure depreciation' }}
                                            </p>
                                        </div>
                                        <div class="p-6">
                                            @if (session()->has('message'))
                                                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                                                    {{ session('message') }}
                                                </div>
                                            @endif
                                   
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                    <div>
                                                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">PPE Name *</label>
                                                        <input wire:model="name" type="text" id="name" 
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            placeholder="Enter asset name" required>
                                                        @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <label for="categoryx" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                                                        <select wire:model="categoryx" id="categoryx" 
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                                            <option value="">Select Category</option>
                                                            @foreach($this->ppeCategories as $category)
                                                                <option value="{{ $category->account_number }}">{{ $category->account_name }}</option>
                                                            @endforeach
                                                        </select>
                                                        @error('categoryx') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <label for="purchase_price" class="block text-sm font-medium text-gray-700 mb-2">Purchase Price *</label>
                                                        <input wire:model="purchase_price" type="number" step="0.01" id="purchase_price" 
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            placeholder="Enter purchase price" required>
                                                        @error('purchase_price') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <label for="purchase_date" class="block text-sm font-medium text-gray-700 mb-2">Purchase Date *</label>
                                                        <input wire:model="purchase_date" type="date" id="purchase_date" 
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                                        @error('purchase_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <label for="salvage_value" class="block text-sm font-medium text-gray-700 mb-2">Salvage Value *</label>
                                                        <input wire:model="salvage_value" type="number" step="0.01" id="salvage_value" 
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            placeholder="Enter salvage value" required>
                                                        @error('salvage_value') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <label for="useful_life" class="block text-sm font-medium text-gray-700 mb-2">Useful Life (Years) *</label>
                                                        <input wire:model="useful_life" type="number" id="useful_life" 
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            placeholder="Enter useful life in years" required>
                                                        @error('useful_life') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <label for="quantity" class="block text-sm font-medium text-gray-700 mb-2">Quantity *</label>
                                                        <input wire:model="quantity" type="number" id="quantity" 
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            placeholder="Enter quantity" required>
                                                        @error('quantity') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <label for="location" class="block text-sm font-medium text-gray-700 mb-2">Location *</label>
                                                        <input wire:model="location" type="text" id="location" 
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                            placeholder="Enter location" required>
                                                        @error('location') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <label for="status" class="block text-sm font-medium text-gray-700 mb-2">Status *</label>
                                                        <select wire:model="status" id="status" 
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                                            <option value="active">Active</option>
                                                            <option value="disposed">Disposed</option>
                                                            <option value="under_repair">Under Repair</option>
                                                            <option value="pending_disposal">Pending Disposal</option>
                                                        </select>
                                                        @error('status') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                </div>
                                                                                                </div>
                                                
                                                <!-- Additional Costs Section -->
                                                <div class="border-t border-gray-200 p-6">
                                                    <h4 class="text-lg font-medium text-gray-900 mb-4">Additional Costs (Cost Capitalization)</h4>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                        <div>
                                                            <label for="legal_fees" class="block text-sm font-medium text-gray-700 mb-2">Legal Fees</label>
                                                            <input wire:model="legal_fees" type="number" step="0.01" id="legal_fees" 
                                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                placeholder="Enter legal fees">
                                                            @error('legal_fees') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label for="registration_fees" class="block text-sm font-medium text-gray-700 mb-2">Registration Fees</label>
                                                            <input wire:model="registration_fees" type="number" step="0.01" id="registration_fees" 
                                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                placeholder="Enter registration fees">
                                                            @error('registration_fees') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label for="renovation_costs" class="block text-sm font-medium text-gray-700 mb-2">Renovation Costs</label>
                                                            <input wire:model="renovation_costs" type="number" step="0.01" id="renovation_costs" 
                                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                placeholder="Enter renovation costs">
                                                            @error('renovation_costs') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label for="transportation_costs" class="block text-sm font-medium text-gray-700 mb-2">Transportation Costs</label>
                                                            <input wire:model="transportation_costs" type="number" step="0.01" id="transportation_costs" 
                                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                placeholder="Enter transportation costs">
                                                            @error('transportation_costs') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label for="installation_costs" class="block text-sm font-medium text-gray-700 mb-2">Installation Costs</label>
                                                            <input wire:model="installation_costs" type="number" step="0.01" id="installation_costs" 
                                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                placeholder="Enter installation costs">
                                                            @error('installation_costs') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label for="other_costs" class="block text-sm font-medium text-gray-700 mb-2">Other Costs</label>
                                                            <input wire:model="other_costs" type="number" step="0.01" id="other_costs" 
                                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                placeholder="Enter other costs">
                                                            @error('other_costs') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Payment Method Section -->
                                                <div class="border-t border-gray-200 p-6">
                                                    <h4 class="text-lg font-medium text-gray-900 mb-4">Payment Information</h4>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                                        <div>
                                                            <label for="payment_method" class="block text-sm font-medium text-gray-700 mb-2">Payment Method *</label>
                                                            <select wire:model="payment_method" id="payment_method" 
                                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                                                                <option value="cash">Cash Payment</option>
                                                                <option value="credit">Credit Purchase</option>
                                                                <option value="loan">Loan Financing</option>
                                                                <option value="lease">Lease Agreement</option>
                                                            </select>
                                                            @error('payment_method') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label for="supplier_name" class="block text-sm font-medium text-gray-700 mb-2">Supplier Name</label>
                                                            <input wire:model="supplier_name" type="text" id="supplier_name" 
                                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                placeholder="Enter supplier name">
                                                            @error('supplier_name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label for="invoice_number" class="block text-sm font-medium text-gray-700 mb-2">Invoice Number</label>
                                                            <input wire:model="invoice_number" type="text" id="invoice_number" 
                                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                                placeholder="Enter invoice number">
                                                            @error('invoice_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                        </div>
                                                        <div>
                                                            <label for="invoice_date" class="block text-sm font-medium text-gray-700 mb-2">Invoice Date</label>
                                                            <input wire:model="invoice_date" type="date" id="invoice_date" 
                                                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                                            @error('invoice_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Total Cost Summary -->
                                                <div class="p-6">
                                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 ">
                                                    <h4 class="text-lg font-medium text-gray-900 mb-3">Cost Summary</h4>
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Purchase Price:</span>
                                                            <span class="font-medium">{{ number_format((float)($purchase_price ?? 0), 2) }} TZS</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Legal Fees:</span>
                                                            <span class="font-medium">{{ number_format((float)($legal_fees ?? 0), 2) }} TZS</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Registration Fees:</span>
                                                            <span class="font-medium">{{ number_format((float)($registration_fees ?? 0), 2) }} TZS</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Renovation Costs:</span>
                                                            <span class="font-medium">{{ number_format((float)($renovation_costs ?? 0), 2) }} TZS</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Transportation Costs:</span>
                                                            <span class="font-medium">{{ number_format((float)($transportation_costs ?? 0), 2) }} TZS</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Installation Costs:</span>
                                                            <span class="font-medium">{{ number_format((float)($installation_costs ?? 0), 2) }} TZS</span>
                                                        </div>
                                                        <div class="flex justify-between">
                                                            <span class="text-gray-600">Other Costs:</span>
                                                            <span class="font-medium">{{ number_format((float)($other_costs ?? 0), 2) }} TZS</span>
                                                        </div>
                                                        <div class="flex justify-between border-t border-gray-300 pt-2">
                                                            <span class="text-lg font-semibold text-gray-900">Total Capitalized Cost:</span>
                                                            <span class="text-lg font-semibold text-blue-600">{{ number_format((float)($purchase_price ?? 0) + (float)($legal_fees ?? 0) + (float)($registration_fees ?? 0) + (float)($renovation_costs ?? 0) + (float)($transportation_costs ?? 0) + (float)($installation_costs ?? 0) + (float)($other_costs ?? 0), 2) }} TZS</span>
                                                        </div>
                                                    </div>
                                                </div>
                                                </div>

                                                <div class="p-6">
                                                    <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">Notes</label>
                                                    <textarea wire:model="notes" id="notes" rows="3"
                                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                                        placeholder="Enter any additional notes"></textarea>
                                                    @error('notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200 p-6">
                                                    <button type="button" wire:click="resetForm" 
                                                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors">
                                                        Cancel
                                                    </button>
                                                    <button wire:click="store"
                                                        class="px-4 py-2 bg-blue-900 text-white rounded-lg hover:bg-blue-800 transition-colors">
                                                        {{ $isEditMode ? 'Update PPE' : 'Add PPE' }}
                                                    </button>
                                                </div>
                                     
                                        </div>
                                    </div>
                                </div>
                                @break
                            @case(3)
                                {{-- PPE Table with search, sort, pagination, bulk actions --}}
                                <div class="space-y-4">
                                    <!-- Bulk Actions Bar -->
                                    <div class="flex items-center justify-between bg-gray-50 p-4 rounded-lg border border-gray-200">
                                        <div class="flex items-center space-x-4">
                                            <div class="flex items-center">
                                                <input type="checkbox" wire:model="selectAll" 
                                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                                    aria-label="Select all PPE assets">
                                                <label class="ml-2 text-sm text-gray-700">Select All ({{ count($selectedPpes) }} selected)</label>
                                            </div>
                                            @if(count($selectedPpes) > 0)
                                                <div class="flex space-x-2">
                                                    @can('delete-ppe')
                                                        <button wire:click="bulkDelete" 
                                                            class="px-3 py-1 bg-red-600 text-white text-sm rounded-lg hover:bg-red-700 transition-colors"
                                                            onclick="return confirm('Are you sure you want to delete selected assets?')">
                                                            Delete Selected
                                                        </button>
                                                    @endcan
                                                    <button wire:click="bulkExport" 
                                                        class="px-3 py-1 bg-green-600 text-white text-sm rounded-lg hover:bg-green-700 transition-colors">
                                                        Export Selected
                                                    </button>
                                                    <button wire:click="bulkDepreciation" 
                                                        class="px-3 py-1 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700 transition-colors">
                                                        Calculate Depreciation
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <select wire:model="statusFilter" class="text-sm border border-gray-300 rounded-lg px-2 py-1">
                                                <option value="">All Status</option>
                                                <option value="active">Active</option>
                                                <option value="disposed">Disposed</option>
                                                <option value="under_repair">Under Repair</option>
                                                <option value="pending_disposal">Pending Disposal</option>
                                            </select>
                                            <select wire:model="sortBy" class="text-sm border border-gray-300 rounded-lg px-2 py-1">
                                                <option value="name">Sort by Name</option>
                                                <option value="purchase_date">Purchase Date</option>
                                                <option value="initial_value">Initial Value</option>
                                                <option value="accumulated_depreciation">Depreciation</option>
                                            </select>
                                            <select wire:model="sortDirection" class="text-sm border border-gray-300 rounded-lg px-2 py-1">
                                                <option value="asc">Ascending</option>
                                                <option value="desc">Descending</option>
                                            </select>
                                        </div>
                                    </div>

                                    <!-- PPE Table -->
                                    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                            <input type="checkbox" wire:model="selectAll" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                        </th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" 
                                                            wire:click="sortBy('name')" aria-label="Sort by name">
                                                            Name
                                                            @if($sortBy === 'name')
                                                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                                            @endif
                                                        </th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100" 
                                                            wire:click="sortBy('initial_value')">
                                                            Initial Value
                                                            @if($sortBy === 'initial_value')
                                                                <span class="ml-1">{{ $sortDirection === 'asc' ? '↑' : '↓' }}</span>
                                                            @endif
                                                        </th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Accumulated Depreciation</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Book Value</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @forelse($ppes as $ppe)
                                                        <tr class="hover:bg-gray-50 transition-colors">
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <input type="checkbox" wire:model="selectedPpes" value="{{ $ppe->id }}" 
                                                                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <div class="flex items-center">
                                                                    <div>
                                                                        <div class="text-sm font-medium text-gray-900">{{ $ppe->name }}</div>
                                                                        <div class="text-sm text-gray-500">{{ $ppe->location ?? 'No location' }}</div>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ppe->category }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                {{ number_format($ppe->initial_value, 2) }} TZS
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-red-600">
                                                                {{ number_format($ppe->accumulated_depreciation, 2) }} TZS
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-green-600">
                                                                {{ number_format($ppe->closing_value, 2) }} TZS
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                                    @if($ppe->status == 'active') bg-green-100 text-green-800
                                                                    @elseif($ppe->status == 'disposed') bg-red-100 text-red-800
                                                                    @elseif($ppe->status == 'under_repair') bg-yellow-100 text-yellow-800
                                                                    @else bg-gray-100 text-gray-800 @endif">
                                                                    {{ ucfirst($ppe->status ?? 'active') }}
                                                                </span>
                                                            </td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                                <button wire:click="viewAsset({{ $ppe->id }})" 
                                                                    class="text-blue-600 hover:text-blue-900 transition-colors"
                                                                    aria-label="View asset details">View</button>
                                                                @can('edit-ppe')
                                                                    <button wire:click="editAsset({{ $ppe->id }})" 
                                                                        class="text-green-600 hover:text-green-900 transition-colors"
                                                                        aria-label="Edit asset">Edit</button>
                                                                @endcan
                                                                @can('delete-ppe')
                                                                    <button wire:click="deleteAsset({{ $ppe->id }})" 
                                                                        class="text-red-600 hover:text-red-900 transition-colors"
                                                                        onclick="return confirm('Are you sure?')"
                                                                        aria-label="Delete asset">Delete</button>
                                                                @endcan
                                                                
                                                                {{-- Disposal Actions --}}
                                                                @if($ppe->status === 'active')
                                                                    <button wire:click="initiateDisposal({{ $ppe->id }})" 
                                                                        class="text-orange-600 hover:text-orange-900 transition-colors"
                                                                        aria-label="Initiate disposal">Initiate Disposal</button>
                                                                @elseif($ppe->status === 'pending_disposal')
                                                                    <span class="text-yellow-600 text-xs">Pending Approval</span>
                                                                @elseif($ppe->status === 'approved_for_disposal')
                                                                    <button wire:click="showDisposalForm({{ $ppe->id }})" 
                                                                        class="text-blue-600 hover:text-blue-900 transition-colors"
                                                                        aria-label="Complete disposal">Complete Disposal</button>
                                                                @elseif($ppe->status === 'disposed')
                                                                    <span class="text-gray-500 text-xs">Disposed</span>
                                                                    @if($ppe->disposal_date)
                                                                        <div class="text-xs text-gray-400">
                                                                            {{ $ppe->disposal_date }}<br>
                                                                            {{ $ppe->disposal_method_display ?? $ppe->disposal_method }}
                                                                        </div>
                                                                    @endif
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr>
                                                            <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                                </svg>
                                                                <p>No PPE assets found. <button wire:click="selectMenu(2)" class="text-blue-600 hover:text-blue-800">Add your first asset</button></p>
                                                            </td>
                                                        </tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                        
                                        <!-- Pagination -->
                                        @if($ppes->hasPages())
                                            <div class="px-6 py-3 border-t border-gray-200">
                                                {{ $ppes->links() }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @break
                            @case(4)
                                {{-- Categories Management with CRUD --}}
                                <div class="space-y-6">
                                    <!-- Add New Category Form -->
                                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                                        <div class="flex items-center justify-between mb-6">
                                            <div>
                                                <h3 class="text-lg font-semibold text-gray-900">PPE Categories</h3>
                                                <p class="text-sm text-gray-600">Manage asset categories and their depreciation settings</p>
                                            </div>
                                            @can('create-category')
                                                <button wire:click="toggleCategoryForm" 
                                                    class="px-4 py-2 bg-blue-900 text-white rounded-lg hover:bg-blue-800 transition-colors">
                                                    {{ $showCategoryForm ? 'Cancel' : 'Add Category' }}
                                                </button>
                                            @endcan
                                        </div>

                                        @if($showCategoryForm)
                                            <div class="border border-gray-200 rounded-lg p-4 mb-6 bg-gray-50">
                                                <form wire:submit.prevent="saveCategory" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Category Name</label>
                                                        <input wire:model="categoryName" type="text" 
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                                            placeholder="e.g., Office Equipment" required>
                                                        @error('categoryName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div>
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Default Depreciation Rate (%)</label>
                                                        <input wire:model="categoryDepreciationRate" type="number" step="0.01" 
                                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                                                            placeholder="e.g., 10.00" required>
                                                        @error('categoryDepreciationRate') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                                    </div>
                                                    <div class="flex items-end">
                                                        <button type="submit" 
                                                            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                                            {{ $editingCategoryId ? 'Update' : 'Add' }} Category
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        @endif

                                        <!-- Categories Grid -->
                                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            @foreach($this->categories as $category)
                                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 hover:border-gray-300 transition-colors">
                                                    <div class="flex items-center justify-between mb-2">
                                                        <h4 class="font-medium text-gray-900">{{ $category['name'] }}</h4>
                                                        <div class="flex space-x-1">
                                                            @can('edit-category')
                                                                <button wire:click="editCategory('{{ $category['id'] }}')" 
                                                                    class="text-blue-600 hover:text-blue-800 p-1"
                                                                    aria-label="Edit category">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                                    </svg>
                                                                </button>
                                                            @endcan
                                                            @can('delete-category')
                                                                <button wire:click="deleteCategory('{{ $category['id'] }}')" 
                                                                    class="text-red-600 hover:text-red-800 p-1"
                                                                    onclick="return confirm('Are you sure?')"
                                                                    aria-label="Delete category">
                                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                                    </svg>
                                                                </button>
                                                            @endcan
                                                        </div>
                                                    </div>
                                                    <div class="text-sm text-gray-600 space-y-1">
                                                        <p>Depreciation Rate: <span class="font-medium">{{ $category['depreciation_rate'] }}%</span></p>
                                                        <p>Assets: <span class="font-medium">{{ $category['asset_count'] ?? 0 }}</span></p>
                                                        <p>Total Value: <span class="font-medium">{{ number_format($category['total_value'] ?? 0, 2) }} TZS</span></p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                                @break
                            @case(5)
                                {{-- Reports Section --}}
                                <div class="space-y-6">
                                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Generate PPE Reports</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Date Range</label>
                                                <select wire:model="reportDateRange" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="30">Last 30 Days</option>
                                                    <option value="90">Last 3 Months</option>
                                                    <option value="180">Last 6 Months</option>
                                                    <option value="365">This Year</option>
                                                    <option value="custom">Custom Range</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Category</label>
                                                <select wire:model="reportCategory" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="">All Categories</option>
                                                    @foreach($this->categories as $cat)
                                                        <option value="{{ $cat['name'] }}">{{ $cat['name'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                                                <select wire:model="reportStatus" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <option value="">All Status</option>
                                                    <option value="active">Active</option>
                                                    <option value="disposed">Disposed</option>
                                                    <option value="under_repair">Under Repair</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="mt-4 flex space-x-3">
                                            <button wire:click="generateReport" class="px-4 py-2 bg-blue-900 text-white rounded-lg hover:bg-blue-900 transition-colors">Generate Report</button>
                                            <button wire:click="exportExcel" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors">Export to Excel</button>
                                            <button wire:click="exportPdf" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">Export to PDF</button>
                                        </div>
                                    </div>
                                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Sample PPE Report</h3>
                                        <div class="overflow-x-auto">
                                            <table class="min-w-full divide-y divide-gray-200">
                                                <thead class="bg-gray-50">
                                                    <tr>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purchase Date</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Initial Value</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Accumulated Depreciation</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Book Value</th>
                                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @foreach($this->sampleReport as $ppe)
                                                        <tr>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ppe->name }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ppe->category }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ppe->purchase_date }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($ppe->initial_value, 2) }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($ppe->accumulated_depreciation, 2) }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($ppe->closing_value, 2) }}</td>
                                                            <td class="px-6 py-4 whitespace-nowrap">
                                                                <span class="px-2 py-1 text-xs font-medium rounded-full @if($ppe->status == 'active') bg-green-100 text-green-800 @elseif($ppe->status == 'disposed') bg-red-100 text-red-800 @else bg-gray-100 text-gray-800 @endif">{{ ucfirst($ppe->status) }}</span>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                                @break
                            @case(6)
                                {{-- Disposal Management Section --}}
                                <div class="space-y-6">
                                    <!-- Disposal Statistics -->
                                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                                        <div class="bg-white rounded-xl p-6 border border-gray-200">
                                            <div class="flex items-center">
                                                <div class="p-2 bg-orange-100 rounded-lg">
                                                    <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-gray-600">Pending Approval</p>
                                                    <p class="text-2xl font-semibold text-gray-900">{{ $this->pendingApprovalDisposals->count() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-white rounded-xl p-6 border border-gray-200">
                                            <div class="flex items-center">
                                                <div class="p-2 bg-green-100 rounded-lg">
                                                    <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-gray-600">Approved for Disposal</p>
                                                    <p class="text-2xl font-semibold text-gray-900">{{ $this->approvedForDisposal->count() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-white rounded-xl p-6 border border-gray-200">
                                            <div class="flex items-center">
                                                <div class="p-2 bg-red-100 rounded-lg">
                                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-gray-600">Rejected</p>
                                                    <p class="text-2xl font-semibold text-gray-900">{{ $this->rejectedDisposals->count() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="bg-white rounded-xl p-6 border border-gray-200">
                                            <div class="flex items-center">
                                                <div class="p-2 bg-gray-100 rounded-lg">
                                                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                    </svg>
                                                </div>
                                                <div class="ml-4">
                                                    <p class="text-sm font-medium text-gray-600">Completed</p>
                                                    <p class="text-2xl font-semibold text-gray-900">{{ $this->completedDisposals->count() }}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Pending Approval Disposals -->
                                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Pending Approval Disposals</h3>
                                        @if($this->pendingApprovalDisposals->count() > 0)
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Name</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Book Value</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requested By</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        @foreach($this->pendingApprovalDisposals as $ppe)
                                                            <tr>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $ppe->name }}</td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ppe->category }}</td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($ppe->closing_value, 2) }} TZS</td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ppe->disposalApproval->user->name ?? 'N/A' }}</td>
                                                                <td class="px-6 py-4 whitespace-nowrap">
                                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                                                        Pending Approval
                                                                    </span>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-8">
                                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p class="text-gray-500">No pending disposal approvals</p>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Approved for Disposal -->
                                    <div class="bg-white rounded-xl p-6 border border-gray-200">
                                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Approved for Disposal</h3>
                                        @if($this->approvedForDisposal->count() > 0)
                                            <div class="overflow-x-auto">
                                                <table class="min-w-full divide-y divide-gray-200">
                                                    <thead class="bg-gray-50">
                                                        <tr>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Asset Name</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Net Book Value</th>
                                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="bg-white divide-y divide-gray-200">
                                                        @foreach($this->approvedForDisposal as $ppe)
                                                            <tr>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $ppe->name }}</td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $ppe->category }}</td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ number_format($ppe->closing_value, 2) }} TZS</td>
                                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                                    <button wire:click="showDisposalForm({{ $ppe->id }})" 
                                                                        class="text-blue-600 hover:text-blue-900 transition-colors">Complete Disposal</button>
                                                                </td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @else
                                            <div class="text-center py-8">
                                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <p class="text-gray-500">No assets approved for disposal</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                @break
                            @default
                                <div class="text-center py-12">
                                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">Welcome to PPE Management</h3>
                                    <p class="text-gray-600">Select a section from the sidebar to get started</p>
                                </div>
                        @endswitch
                    </div>
                </div>
            </div>
        </div>

        <!-- Disposal Form Modal -->
        @if($showDisposalForm)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Complete Asset Disposal</h3>
                        <button wire:click="resetDisposalForm" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    
                    <form wire:submit.prevent="completeDisposal" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Disposal Date</label>
                                <input type="date" wire:model="disposal_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                @error('disposal_date') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Disposal Method</label>
                                <select wire:model="disposal_method" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                    <option value="sold">Sold</option>
                                    <option value="scrapped">Scrapped</option>
                                    <option value="donated">Donated</option>
                                    <option value="lost">Lost</option>
                                    <option value="stolen">Stolen</option>
                                    <option value="other">Other</option>
                                </select>
                                @error('disposal_method') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Disposal Proceeds</label>
                                <input type="number" step="0.01" wire:model="disposal_proceeds" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                                @error('disposal_proceeds') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Disposal Notes</label>
                            <textarea wire:model="disposal_notes" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500"></textarea>
                            @error('disposal_notes') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-4">
                            <button type="button" wire:click="resetDisposalForm" class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                Cancel
                            </button>
                            <button type="submit" class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                Complete Disposal
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
