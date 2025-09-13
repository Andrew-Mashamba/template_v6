<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="p-6">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Loans Management</h1>
                        <p class="text-gray-600 mt-1">Manage loan applications, approvals, and monitoring</p>
                    </div>
                </div>
                
            
            </div>
        </div>

        <div class="flex gap-6">
            <!-- Enhanced Sidebar -->
            <div class="w-80 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <!-- Search Section -->
                <div class="p-6 border-b border-gray-100">
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input 
                            type="text" 
                            wire:model.debounce.300ms="search" 
                            placeholder="Search loans, clients, or accounts..."
                            class="block w-full pl-10 pr-3 py-3 border border-gray-200 rounded-xl text-sm placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 bg-gray-50 hover:bg-white focus:bg-white"
                            aria-label="Search loans"
                        />
                    </div>
                </div>

                <!-- Navigation Menu -->
                <div class="p-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-4 px-2">Navigation</h3>
                    
                    @php
                        $sub_sections = [
                            [
                                'id' => 1, 
                                'label' => 'Loan Status Summary', 
                                'icon' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
                                'description' => 'Overview and analytics'
                            ],
                            [
                                'id' => 2, 
                                'label' => 'Loan Applications', 
                                'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
                                'description' => 'Process new applications'
                            ],
                            [
                                'id' => 3, 
                                'label' => 'Declined Loans', 
                                'icon' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
                                'description' => 'View rejected applications'
                            ],
                            [
                                'id' => 4, 
                                'label' => 'Liquidation', 
                                'icon' => 'M9 12l3-3m0 0l3 3m-3-3v12m0-12a9 9 0 110 18 9 9 0 010-18z',
                                'description' => 'Full loan clearance'
                            ],
                        ];
                    @endphp

                    <nav class="space-y-2">
                        @foreach ($sub_sections as $sub_section)
                            @php
                                // Check permissions for each section
                                $showSection = true;
                                if ($sub_section['id'] == 1) {
                                    // Loan Status Summary - requires view permission
                                    $showSection = $permissions['canView'] ?? false;
                                }
                                if ($sub_section['id'] == 2) {
                                    // Loan Applications - requires create permission
                                    $showSection = $permissions['canCreate'] ?? false;
                                }
                                if ($sub_section['id'] == 3) {
                                    // Declined Loans - requires view permission
                                    $showSection = $permissions['canView'] ?? false;
                                }
                                if ($sub_section['id'] == 4) {
                                    // Liquidation - requires special liquidate permission or delete
                                    $showSection = ($permissions['canLiquidate'] ?? false) || ($permissions['canDelete'] ?? false);
                                }
                                
                                if ($sub_section['id'] == 3) {
                                    // For Declined Loans, count loans with REJECTED status
                                    $count = App\Models\LoansModel::where('status', 'REJECTED')->count();
                                } elseif ($sub_section['id'] == 4) {
                                    // For Liquidation, count active loans that can be liquidated
                                    $count = App\Models\LoansModel::where('status', 'ACTIVE')->count();
                                } else {
                                    $count = App\Models\LoansModel::where('loan_type_2', $sub_section['label'])
                                         ->where('status', '!=', 'ACTIVE')
                                         ->count();
                                }
                                $isActive = $this->selectedMenuItem == $sub_section['id'];
                            @endphp

                            @if($showSection)
                            <button
                                wire:click="selectedMenu({{ $sub_section['id'] }})"
                                class="relative w-full group transition-all duration-200"
                                aria-label="{{ $sub_section['label'] }}"
                            >
                                <div class="flex items-center p-3 rounded-xl transition-all duration-200
                                    @if ($isActive) 
                                        bg-blue-900 text-white shadow-lg 
                                    @else 
                                        bg-gray-50 hover:bg-gray-100 text-gray-700 hover:text-gray-900 
                                    @endif">
                                    
                                    <!-- Loading State -->
                                    <div wire:loading wire:target="selectedMenu({{ $sub_section['id'] }})" class="mr-3">
                                        <svg class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>

                                    <!-- Icon -->
                                    <div wire:loading.remove wire:target="selectedMenu({{ $sub_section['id'] }})" class="mr-3">
                                        <svg class="w-5 h-5 @if ($isActive) text-white @else text-gray-500 group-hover:text-gray-700 @endif" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $sub_section['icon'] }}"></path>
                                        </svg>
                                    </div>

                                    <!-- Content -->
                                    <div class="flex-1 text-left">
                                        <div class="font-medium text-sm">{{ $sub_section['label'] }} </div>
                                        <div class="text-xs opacity-75">{{ $sub_section['description'] }}</div>
                                    </div>

                                    <!-- Notification Badge -->
                                    @if ($count > 0)
                                        <div class="ml-2">
                                            <span class="inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white bg-red-500 rounded-full min-w-[20px] h-5">
                                                {{ $count > 99 ? '99+' : $count }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </button>
                            @endif
                        @endforeach
                    </nav>
                </div>




            </div>

            <!-- Enhanced Main Content Area -->
            <div class="flex-1">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <!-- Content Header -->
                    <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center justify-between">
               
                            
                            <!-- Breadcrumb -->
                            <nav class="flex" aria-label="Breadcrumb">
                                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                    <li class="inline-flex items-center">
                                        <a href="#" class="inline-flex items-center text-sm font-medium text-gray-700 hover:text-blue-600">
                                            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"></path>
                                            </svg>
                                            Loans
                                        </a>
                                    </li>
                                    <li>
                                        <div class="flex items-center">
                                            <svg class="w-6 h-6 text-gray-400" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path>
                                            </svg>
                                            <span class="ml-1 text-sm font-medium text-gray-500 md:ml-2">
                                                @switch($this->selectedMenuItem)
                                                    @case(1) Dashboard @break
                                                    @case(2) Applications @break
                                                    @case(3) Declined @break
                                                    @case(4) Liquidation @break
                                                    @case(5) Top-up @break
                                                    @case(6) Restructuring @break
                                                    @case(7) Deviations @break
                                                    @default Applications
                                                @endswitch
                                            </span>
                                        </div>
                                    </li>
                                </ol>
                            </nav>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="p-8">
                
                        <!-- Search Results -->
                        @if ($showDropdown && !empty($results))
                            <div class="mb-6">
                                <div class="bg-blue-50 border border-blue-200 rounded-xl p-4">
                                    <div class="flex items-center justify-between mb-4">
                                        <h3 class="text-lg font-semibold text-blue-900">Search Results</h3>
                                        <button wire:click="$set('showDropdown', false)" class="text-blue-600 hover:text-blue-800">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </div>
                                    <livewire:loans.upper-search />
                                </div>
                            </div>
                        @endif

                        <!-- Dynamic Content -->
                        <div wire:loading.remove wire:target="selectedMenu" class="min-h-[400px]">
                            @switch($this->selectedMenuItem)
                                @case(1)
                                    <livewire:loans.dashboard />
                                    @break
                                @case(2)
                                    <livewire:loans.new-loans />
                                    @break
                                @case(3)
                                    <livewire:loans.declined-loans />
                                    @break
                                @case(4)
                                    <livewire:loans.liquidation />
                                    @break
                                @case(5)
                                    <livewire:loans.top-up />
                                    @break
                                @case(6)
                                    <livewire:loans.restructure />
                                    @break
                                @case(7)
                                    <livewire:loans.deviation-queue />
                                    @break
                                @default
                                    <livewire:loans.new-loans />
                            @endswitch
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
