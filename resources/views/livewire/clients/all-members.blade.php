<div>
    {{-- Session Flash Messages --}}
    @if(session()->has('notification'))
        @php
            $notification = session('notification');
            $type = $notification['type'] ?? 'info';
            $message = $notification['message'] ?? '';
            
            $bgColor = match($type) {
                'success' => 'bg-green-50 border-green-200 text-green-800',
                'error' => 'bg-red-50 border-red-200 text-red-800',
                'warning' => 'bg-yellow-50 border-yellow-200 text-yellow-800',
                'info' => 'bg-blue-50 border-blue-200 text-blue-800',
                default => 'bg-gray-50 border-gray-200 text-gray-800'
            };
            
            $icon = match($type) {
                'success' => 'fas fa-check-circle',
                'error' => 'fas fa-exclamation-circle',
                'warning' => 'fas fa-exclamation-triangle',
                'info' => 'fas fa-info-circle',
                default => 'fas fa-info-circle'
            };
        @endphp
        
        <div class="mb-4 p-4 border rounded-lg {{ $bgColor }} flex items-center justify-between">
            <div class="flex items-center">
                <i class="{{ $icon }} mr-3"></i>
                <span class="font-medium">{{ $message }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session()->has('success'))
        <div class="mb-4 p-4 border border-green-200 rounded-lg bg-green-50 text-green-800 flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-3"></i>
                <span class="font-medium">{{ session('success') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-green-400 hover:text-green-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session()->has('error'))
        <div class="mb-4 p-4 border border-red-200 rounded-lg bg-red-50 text-red-800 flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-3"></i>
                <span class="font-medium">{{ session('error') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-red-400 hover:text-red-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session()->has('warning'))
        <div class="mb-4 p-4 border border-yellow-200 rounded-lg bg-yellow-50 text-yellow-800 flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-exclamation-triangle mr-3"></i>
                <span class="font-medium">{{ session('warning') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-yellow-400 hover:text-yellow-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    @if(session()->has('info'))
        <div class="mb-4 p-4 border border-blue-200 rounded-lg bg-blue-50 text-blue-800 flex items-center justify-between">
            <div class="flex items-center">
                <i class="fas fa-info-circle mr-3"></i>
                <span class="font-medium">{{ session('info') }}</span>
            </div>
            <button onclick="this.parentElement.remove()" class="text-blue-400 hover:text-blue-600">
                <i class="fas fa-times"></i>
            </button>
        </div>
    @endif

    <div class="bg-white rounded-lg shadow-md">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-800">Total Members</h2>
                <span class="px-3 py-1 text-sm bg-blue-100 text-blue-800 rounded-full">
                    {{ $totalRecords }}
                </span>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center space-x-3 p-1">
                {{-- <button wire:click="$toggle('showFilters')" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 ease-in-out {{ $showFilters ? 'bg-blue-50 border-blue-500 text-blue-700' : '' }}">
                    <i class="fas fa-filter mr-2"></i>Filters
                    <span class="ml-1 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                        {{ $this->getActiveFiltersCount() }}
                    </span>
                </button>
                <button wire:click="$toggle('showColumnSelector')" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 ease-in-out {{ $showColumnSelector ? 'bg-blue-50 border-blue-500 text-blue-700' : '' }}">
                    <i class="fas fa-columns mr-2"></i>Columns
                    <span class="ml-1 text-xs bg-gray-100 text-gray-600 px-2 py-0.5 rounded-full">
                        {{ $this->getVisibleColumnsCount() }}
                    </span>
                </button>--}}
                <button wire:click="exportTable" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 ease-in-out">
                    <i class="fas fa-download mr-2"></i>Export to Excel
                </button>
            </div>
        </div>

        <!-- Filters Panel -->
        {{-- <div x-show="$wire.showFilters" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="mb-6 p-4 bg-gray-50 rounded-lg shadow-sm">
            
            <!-- Filter Presets -->
            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-2">Quick Filters</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($filterPresets as $preset => $label)
                        <button wire:click="applyFilterPreset('{{ $preset }}')" 
                            class="px-3 py-1 text-xs font-medium rounded-full transition-colors duration-200 {{ $activeFilterPreset === $preset ? 'bg-blue-100 text-blue-800 border border-blue-300' : 'bg-white text-gray-600 border border-gray-300 hover:bg-gray-50' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            </div>

            <!-- Common Filters (Always Visible) -->
            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Common Filters</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Membership Type</label>
                        <select wire:model.live.debounce.500ms="filters.membership_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="Individual">Individual</option>
                            <option value="Business">Business</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Client Status</label>
                        <select wire:model.live.debounce.500ms="filters.status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="ACTIVE">Active</option>
                            <option value="PENDING">Pending</option>
                            <option value="BLOCKED">Blocked</option>
                            <option value="NEW CLIENT">New Client</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gender</label>
                        <select wire:model.live.debounce.500ms="filters.gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Genders</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Advanced Filters (Collapsible) -->
            <div class="mb-4">
                <button @click="$dispatch('toggle-advanced-filters')" 
                    class="flex items-center justify-between w-full text-left text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md p-2 hover:bg-gray-100">
                    <span>Advanced Filters</span>
                    <svg class="w-4 h-4 transform transition-transform duration-200" :class="{ 'rotate-180': $wire.showAdvancedFilters }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                
                <div x-show="$wire.showAdvancedFilters" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 transform translate-y-0"
                    x-transition:leave-end="opacity-0 transform -translate-y-2"
                    class="mt-3 pt-3 border-t border-gray-200">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Personal Information -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Personal</h4>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Marital Status</label>
                                <select wire:model.live.debounce.500ms="filters.marital_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">All Status</option>
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Nationality</label>
                                <select wire:model.live.debounce.500ms="filters.nationality" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">All Nationalities</option>
                                    <option value="Tanzanian">Tanzanian</option>
                                    <option value="Kenyan">Kenyan</option>
                                    <option value="Ugandan">Ugandan</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Location -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Location</h4>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Country</label>
                                <select wire:model.live.debounce.500ms="filters.country" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">All Countries</option>
                                    <option value="Tanzania">Tanzania</option>
                                    <option value="Kenya">Kenya</option>
                                    <option value="Uganda">Uganda</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Region</label>
                                <select wire:model.live.debounce.500ms="filters.region" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">All Regions</option>
                                    <option value="Dar es Salaam">Dar es Salaam</option>
                                    <option value="Arusha">Arusha</option>
                                    <option value="Mwanza">Mwanza</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">District</label>
                                <select wire:model.live.debounce.500ms="filters.district" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">All Districts</option>
                                    <option value="Ilala">Ilala</option>
                                    <option value="Kinondoni">Kinondoni</option>
                                    <option value="Temeke">Temeke</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                        </div>

                        <!-- Education & Employment -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Education & Employment</h4>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Education Level</label>
                                <select wire:model.live.debounce.500ms="filters.education_level" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">All Levels</option>
                                    <option value="Primary">Primary</option>
                                    <option value="Secondary">Secondary</option>
                                    <option value="Diploma">Diploma</option>
                                    <option value="Degree">Degree</option>
                                    <option value="Masters">Masters</option>
                                    <option value="PhD">PhD</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Employment</label>
                                <select wire:model.live.debounce.500ms="filters.employment" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                                    <option value="">All Employment</option>
                                    <option value="Employed">Employed</option>
                                    <option value="Self-employed">Self-employed</option>
                                    <option value="Unemployed">Unemployed</option>
                                    <option value="Student">Student</option>
                                    <option value="Retired">Retired</option>
                                </select>
                            </div>
                        </div>

                        <!-- Date Range -->
                        <div class="space-y-3">
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide">Date Range</h4>
                            <div>
                                <label class="block text-xs font-medium text-gray-700">Registration Date</label>
                                <div class="mt-1 grid grid-cols-2 gap-2">
                                    <div>
                                        <input type="date" wire:model.live.debounce.500ms="filters.start_date" 
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                            placeholder="Start Date">
                                    </div>
                                    <div>
                                        <input type="date" wire:model.live.debounce.500ms="filters.end_date" 
                                            class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                                            placeholder="End Date">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Clear Filters Button -->
            <div class="mt-4 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    <span class="font-medium">{{ $this->getActiveFiltersCount() }}</span> active filters
                </div>
                <button wire:click="clearFilters" 
                    class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-md transition-colors duration-200">
                    <i class="fas fa-times mr-1"></i>Clear All Filters
                </button>
            </div>
        </div>

        <!-- Column Selector -->
        <div x-show="$wire.showColumnSelector" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="mb-6 p-4 bg-gray-50 rounded-lg shadow-sm">
            
            <!-- Common Columns (Always Visible) -->
            <div class="mb-4">
                <h3 class="text-sm font-medium text-gray-700 mb-3">Common Columns</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                    @foreach(['account_number', 'client_number', 'first_name', 'last_name', 'email', 'phone_number', 'status', 'created_at'] as $column)
                        <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                            <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                {{ $columns[$column] ? 'checked' : '' }}>
                            <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                        </label>
                    @endforeach
                </div>
            </div>

            <!-- Advanced Columns (Collapsible) -->
            <div class="mb-4">
                <button @click="$dispatch('toggle-advanced-columns')" 
                    class="flex items-center justify-between w-full text-left text-sm font-medium text-gray-700 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md p-2 hover:bg-gray-100">
                    <span>Advanced Columns</span>
                    <svg class="w-4 h-4 transform transition-transform duration-200" :class="{ 'rotate-180': $wire.showAdvancedColumns }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>
                
                <div x-show="$wire.showAdvancedColumns" 
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 transform -translate-y-2"
                    x-transition:enter-end="opacity-100 transform translate-y-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 transform translate-y-0"
                    x-transition:leave-end="opacity-0 transform -translate-y-2"
                    class="mt-3 pt-3 border-t border-gray-200">
                    
                    <div class="space-y-4">
                        <!-- Personal Information -->
                        <div>
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Personal Information</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach(['middle_name', 'date_of_birth', 'gender', 'marital_status', 'nationality', 'citizenship', 'country_of_birth', 'place_of_birth'] as $column)
                                    <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                        <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                            {{ $columns[$column] ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Contact Information -->
                        <div>
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Contact Information</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach(['mobile_phone_number', 'contact_number', 'address', 'main_address', 'street', 'city', 'region', 'district', 'country', 'postal_code', 'building_number', 'number_of_building', 'ward'] as $column)
                                    <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                        <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                            {{ $columns[$column] ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Business Information -->
                        <div>
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Business Information</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach(['business_name', 'trade_name', 'incorporation_number', 'registration_number', 'legal_form', 'establishment_date', 'registration_country', 'industry_sector'] as $column)
                                    <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                        <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                            {{ $columns[$column] ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Financial Information -->
                        <div>
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Financial Information</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach(['income_available', 'monthly_expenses', 'annual_income', 'basic_salary', 'gross_salary', 'tax_paid', 'pension', 'nhif', 'hisa', 'akiba', 'amana', 'amount'] as $column)
                                    <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                        <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                            {{ $columns[$column] ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Identification -->
                        <div>
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Identification</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach(['national_id', 'nida_number', 'tin_number', 'tax_identification_number', 'passport_number', 'driving_license_number', 'voters_id', 'custom_id_number_1', 'custom_id_number_2'] as $column)
                                    <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                        <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                            {{ $columns[$column] ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Employment & Education -->
                        <div>
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Employment & Education</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach(['employment', 'occupation', 'employer_name', 'education', 'education_level'] as $column)
                                    <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                        <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                            {{ $columns[$column] ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Family Information -->
                        <div>
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Family Information</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach(['number_of_spouse', 'number_of_children', 'dependent_count', 'present_surname', 'birth_surname'] as $column)
                                    <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                        <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                            {{ $columns[$column] ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- Guarantor Information -->
                        <div>
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">Guarantor Information</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach(['guarantor_first_name', 'guarantor_middle_name', 'guarantor_last_name', 'guarantor_phone', 'guarantor_email', 'guarantor_region', 'guarantor_ward', 'guarantor_district', 'guarantor_relationship', 'guarantor_membership_number'] as $column)
                                    <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                        <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                            {{ $columns[$column] ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <!-- System Fields -->
                        <div>
                            <h4 class="text-xs font-medium text-gray-500 uppercase tracking-wide mb-2">System Fields</h4>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                @foreach(['membership_type', 'updated_at'] as $column)
                                    <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                        <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                            {{ $columns[$column] ? 'checked' : '' }}>
                                        <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reset Columns Button -->
            <div class="mt-4 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    <span class="font-medium">{{ $this->getVisibleColumnsCount() }}</span> visible columns
                </div>
                <button wire:click="resetColumnsToDefault" 
                    class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-md transition-colors duration-200">
                    <i class="fas fa-undo mr-1"></i>Reset to Default
                </button>
            </div>
        </div>--}}

        <!-- Search and Bulk Actions -->
        <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
            <div class="relative w-full md:w-96">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search members..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <div class="absolute inset-y-0 right-0 flex items-center pr-3">
                    <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </div>
            </div>            
        </div>

        <!-- Table -->
        <div class="overflow-x-auto bg-white rounded-xl border border-gray-200 shadow-sm relative">
            @if($loading)
                <div class="absolute inset-0 bg-white bg-opacity-75 flex items-center justify-center z-10">
                    <div class="flex items-center space-x-2">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                        <span class="text-sm text-gray-600">Loading...</span>
                    </div>
                </div>
            @endif
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50 sticky top-0">
                    <tr>                        
                        @foreach($columns as $column => $visible)
                            @if($visible)
                                <th wire:click="sortBy('{{ $column }}')" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider cursor-pointer hover:bg-gray-100">
                                    {{ ucfirst(str_replace('_', ' ', $column)) }}
                                    @if($sortField === $column)
                                        @if($sortDirection === 'asc')
                                            <i class="fas fa-sort-up ml-1"></i>
                                        @else
                                            <i class="fas fa-sort-down ml-1"></i>
                                        @endif
                                    @endif
                                </th>
                            @endif
                        @endforeach
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($members as $member)
                        <tr wire:key="member-{{ $member->id }}" class="hover:bg-gray-50">                            
                            @foreach($columns as $column => $visible)
                                @if($visible)
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            @if($column === 'created_at')
                                                {{ $member->$column->format('M d, Y') }}
                                            @else
                                                {{ $member->$column }}
                                            @endif
                                        </div>
                                    </td>
                                @endif
                            @endforeach
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-3">
                                    <button wire:click="viewMember({{ $member->id }})" class="text-blue-900 hover:text-blue-900" title="View">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button wire:click="editMember({{ $member->id }})" class="text-blue-900 hover:text-blue-900" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="$emit('deleteMember', {{ $member->id }})" class="text-red-600 hover:text-red-900" title="Delete">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ count($columns) + 2 }}" class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                No members found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <select type="text"  wire:model.live="perPage" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="10">10 per page</option>
                        <option value="25">25 per page</option>
                        <option value="50">50 per page</option>
                        <option value="100">100 per page</option>
                    </select>
                </div>
                <div>
                    {{ $members->links() }}
                </div>
            </div>
        </div>
    </div>

    @if($showViewModal && $viewingMember)
        @include('livewire.clients.view-member', ['member' => $viewingMember])
    @endif

    @if($showAllDataModal && $viewingMember)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm">
            <div class="bg-white rounded-2xl w-3/4 h-screen shadow-2xl overflow-y-auto flex flex-col">
                {{-- Sticky Header --}}
                <div class="flex-shrink-0 bg-gradient-to-r from-gray-50 to-gray-100 border-b border-gray-200 p-6">
                    <div class="flex justify-between items-center">
                        <div class="flex items-center space-x-4">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold text-gray-900">All Member Data</h3>
                                <p class="text-sm text-gray-600">{{ $viewingMember->first_name }} {{ $viewingMember->last_name }} - {{ $viewingMember->client_number }}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-3">
                            {{-- PDF Export Button --}}
                            <button wire:click="exportMemberToPDF" class="inline-flex items-center px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                                </svg>
                                Export PDF
                            </button>                            
                           
                            {{-- Close Button --}}
                            <button wire:click="closeAllDataModal" class="inline-flex items-center justify-center w-10 h-10 bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Scrollable Content --}}
                <div class="flex-1 overflow-y-auto scrollbar-thin scrollbar-thumb-red-300 scrollbar-track-red-100">
                    <div class="overflow-y-auto scrollbar-thin scrollbar-thumb-red-300 scrollbar-track-red-100">
                        <div class="p-6">
                            {{-- Data Categories --}}
                            <div class="space-y-8">
                                {{-- Personal Information Section --}}
                                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                                        <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                            </svg>
                                            Personal Information
                                        </h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                                        @php
                                            $personalFields = ['first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender', 'marital_status', 'nationality', 'citizenship', 'national_id', 'nida_number', 'passport_number', 'driving_license'];
                                        @endphp
                                        @foreach($viewingMember->toArray() as $key => $value)
                                            @if(in_array($key, $personalFields) && $value !== null)
                                                <div class="space-y-1">
                                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                                    <div class="text-sm text-gray-900 font-medium">{{ is_array($value) ? json_encode($value) : $value }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Contact Information Section --}}
                                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                                        <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                            </svg>
                                            Contact Information
                                        </h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                                        @php
                                            $contactFields = ['email', 'phone_number', 'mobile_phone_number', 'contact_number', 'address', 'city', 'region', 'district', 'country', 'postal_code'];
                                        @endphp
                                        @foreach($viewingMember->toArray() as $key => $value)
                                            @if(in_array($key, $contactFields) && $value !== null)
                                                <div class="space-y-1">
                                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                                    <div class="text-sm text-gray-900 font-medium">{{ is_array($value) ? json_encode($value) : $value }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Employment & Financial Section --}}
                                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                                        <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2-2v2m8 0V6a2 2 0 012 2v6a2 2 0 01-2 2H8a2 2 0 01-2-2V8a2 2 0 012-2V6" />
                                            </svg>
                                            Employment & Financial
                                        </h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                                        @php
                                            $employmentFields = ['employment', 'employer_name', 'job_title', 'income_source', 'monthly_income', 'tin_number', 'business_name', 'trade_name', 'business_address', 'business_phone', 'business_email'];
                                        @endphp
                                        @foreach($viewingMember->toArray() as $key => $value)
                                            @if(in_array($key, $employmentFields) && $value !== null)
                                                <div class="space-y-1">
                                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                                    <div class="text-sm text-gray-900 font-medium">{{ is_array($value) ? json_encode($value) : $value }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Membership & System Section --}}
                                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                                        <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Membership & System
                                        </h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                                        @php
                                            $membershipFields = ['client_number', 'member_number', 'account_number', 'membership_type', 'category', 'status', 'branch_number', 'branch_name', 'guarantor_member_number', 'education_level'];
                                        @endphp
                                        @foreach($viewingMember->toArray() as $key => $value)
                                            @if(in_array($key, $membershipFields) && $value !== null)
                                                <div class="space-y-1">
                                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                                    <div class="text-sm text-gray-900 font-medium">{{ is_array($value) ? json_encode($value) : $value }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>

                                {{-- Additional Information Section --}}
                                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                    <div class="bg-gray-50 px-6 py-4 border-b border-gray-200">
                                        <h4 class="text-lg font-semibold text-gray-900 flex items-center">
                                            <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                            Additional Information
                                        </h4>
                                    </div>
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
                                        @php
                                            $excludedFields = ['id', 'created_at', 'updated_at', 'deleted_at', 'branch_id', 'bills', 'loans', 'accounts', 'photo_url', 'institution_id'];
                                            $personalFields = ['first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender', 'marital_status', 'nationality', 'citizenship', 'national_id', 'nida_number', 'passport_number', 'driving_license'];
                                            $contactFields = ['email', 'phone_number', 'mobile_phone_number', 'contact_number', 'address', 'city', 'region', 'district', 'country', 'postal_code'];
                                            $employmentFields = ['employment', 'employer_name', 'job_title', 'income_source', 'monthly_income', 'tin_number', 'business_name', 'trade_name', 'business_address', 'business_phone', 'business_email'];
                                            $membershipFields = ['client_number', 'member_number', 'account_number', 'membership_type', 'category', 'status', 'branch_number', 'branch_name', 'guarantor_member_number', 'education_level'];
                                            $allCategorizedFields = array_merge($personalFields, $contactFields, $employmentFields, $membershipFields);
                                        @endphp
                                        @foreach($viewingMember->toArray() as $key => $value)
                                            @if(!in_array($key, $excludedFields) && !in_array($key, $allCategorizedFields) && $value !== null)
                                                <div class="space-y-1">
                                                    <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                                    <div class="text-sm text-gray-900 font-medium">{{ is_array($value) ? json_encode($value) : $value }}</div>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Sticky Footer with Navigation --}}
                <div class="flex-shrink-0 bg-gray-50 border-t border-gray-200 p-4">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            <span class="font-medium">Total Fields:</span> 
                            @php
                                $totalFields = count(array_filter($viewingMember->toArray(), function($value, $key) use ($excludedFields) {
                                    return !in_array($key, $excludedFields) && $value !== null;
                                }, ARRAY_FILTER_USE_BOTH));
                            @endphp
                            {{ $totalFields }} data points
                        </div>                        
                    </div>
                </div>
            </div>
        </div>

        <style>
            /* Custom scrollbar styles */
            .scrollbar-thin::-webkit-scrollbar {
                width: 6px;
            }
            .scrollbar-thin::-webkit-scrollbar-track {
                background: #f3f4f6;
                border-radius: 3px;
            }
            .scrollbar-thin::-webkit-scrollbar-thumb {
                background: #d1d5db;
                border-radius: 3px;
            }
            .scrollbar-thin::-webkit-scrollbar-thumb:hover {
                background: #9ca3af;
            }

            /* Print styles */
            @media print {
                body * {
                    visibility: hidden;
                }
                .fixed.inset-0 {
                    position: absolute !important;
                    left: 0 !important;
                    top: 0 !important;
                    width: 100% !important;
                    height: 100% !important;
                    overflow: visible !important;
                }
                .fixed.inset-0, .fixed.inset-0 * {
                    visibility: visible !important;
                }
                .fixed.inset-0 {
                    background: none !important;
                }
                .bg-white {
                    box-shadow: none !important;
                }
                button, .flex-shrink-0:last-child {
                    display: none !important;
                }
                .overflow-hidden {
                    overflow: visible !important;
                }
                .h-\[95vh\] {
                    height: auto !important;
                }
            }
        </style>
    @endif

    @if($showEditModal && $editingMember)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30 p-4">
            <div class="bg-white rounded-2xl w-3/4 h-full max-h-[95vh] shadow-none p-0 overflow-hidden flex flex-col">
                <div class="flex-shrink-0 p-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h3 class="text-xl font-semibold text-gray-900">Edit Member</h3>
                        <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto p-4">
                    <form wire:submit.prevent="saveMember" id="edit-member-form" class="space-y-4">
                            <!-- Photo Upload Section -->
                            <div class="mb-6">
                                <div class="flex items-center space-x-4">
                                    <div class="w-24 h-24 rounded-full overflow-hidden bg-gray-100">
                                        @if($tempPhotoUrl)
                                            <img src="{{ $tempPhotoUrl }}" alt="Member photo" class="w-full h-full object-cover">
                                        @else
                                            <div class="w-full h-full flex items-center justify-center text-gray-400">
                                                <i class="fas fa-user text-4xl"></i>
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Member Photo</label>
                                        <input type="file" wire:model="photo" accept="image/*" class="block w-full text-sm text-gray-500
                                            file:mr-4 file:py-2 file:px-4
                                            file:rounded-full file:border-0
                                            file:text-sm file:font-semibold
                                            file:bg-indigo-50 file:text-blue-900
                                            hover:file:bg-indigo-100">
                                        @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-3 gap-4">
                                @foreach($editingMember->toArray() as $key => $value)
                                    @if($key !== 'id' && $key !== 'created_at' && $key !== 'updated_at' && $key !== 'deleted_at' && $key !== 'branch_id' && $key !== 'bills' && $key !== 'loans' && $key !== 'accounts' && $key !== 'photo_url' && $key !== 'institution_id')
                                        @php
                                            $fieldConfig = $this->getFieldType($key);
                                            $isRequired = $fieldConfig['required'] ? 'required' : '';
                                            $isDisabled = isset($fieldConfig['disabled']) && $fieldConfig['disabled'] ? 'disabled' : '';
                                            $disabledClass = $isDisabled ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '';
                                        @endphp
                                        <div class="col-span-3 sm:col-span-1">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                {{ ucfirst(str_replace('_', ' ', $key)) }}
                                                @if($isRequired)
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </label>
                                            @if($fieldConfig['type'] === 'textarea')
                                                <textarea wire:model.defer="editingMember.{{ $key }}" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm {{ $disabledClass }}"
                                                    {{ $isRequired }} {{ $isDisabled }}></textarea>
                                            @elseif($fieldConfig['type'] === 'select')
                                                <select wire:model="editingMember.{{ $key }}" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm {{ $disabledClass }}"
                                                    {{ $isRequired }} {{ $isDisabled }}>
                                                    <option value="">Select {{ ucfirst(str_replace('_', ' ', $key)) }}</option>
                                                    @foreach($fieldConfig['options'] as $optionValue => $optionLabel)
                                                        <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="{{ $fieldConfig['type'] }}" 
                                                    wire:model.defer="editingMember.{{ $key }}"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm {{ $disabledClass }}"
                                                    {{ $isRequired }} {{ $isDisabled }}>
                                            @endif
                                            @error("editingMember.$key") 
                                                <span class="text-red-500 text-xs">{{ $message ?? 'Validation error' }}</span>
                                            @enderror
                                        </div>
                                    @endif
                                @endforeach
                            </div>

                            <div class="flex justify-end space-x-3 mt-6">
                                <button type="button" wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                    
                </div>
            </div>
        </div>
    @endif

    @if($showActionModal && $actionMember)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 relative">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-semibold text-gray-900">Member Action</h3>
                    <button wire:click="closeActionModal" class="text-gray-400 hover:text-gray-600">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="mb-4">
                    <div class="text-sm text-gray-700 mb-1">Member:</div>
                    <div class="font-medium text-gray-900">{{ $actionMember->first_name }} {{ $actionMember->last_name }} ({{ $actionMember->client_number }})</div>
                </div>
                <div class="mb-4">
                    <div class="text-sm text-gray-700 mb-2">Select Action:</div>
                    <div class="flex space-x-2">
                        <button type="button" wire:click="setActionType('block')" class="px-3 py-1 rounded-md border text-sm focus:outline-none {{ $actionType === 'block' ? 'bg-yellow-100 border-yellow-400 text-yellow-800' : 'bg-white border-gray-300 text-gray-700 hover:bg-yellow-50' }}">Block</button>
                        <button type="button" wire:click="setActionType('activate')" class="px-3 py-1 rounded-md border text-sm focus:outline-none {{ $actionType === 'activate' ? 'bg-green-100 border-green-400 text-green-800' : 'bg-white border-gray-300 text-gray-700 hover:bg-green-50' }}">Activate</button>
                        <button type="button" wire:click="setActionType('delete')" class="px-3 py-1 rounded-md border text-sm focus:outline-none {{ $actionType === 'delete' ? 'bg-red-100 border-red-400 text-red-800' : 'bg-white border-gray-300 text-gray-700 hover:bg-red-50' }}">Delete</button>
                    </div>
                </div>
                <form wire:submit.prevent="confirmAction">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password Confirmation</label>
                        <input type="password" wire:model.defer="actionPassword" class="w-full rounded-md border-gray-300 focus:border-blue-500 focus:ring-blue-500" placeholder="Enter your password" required>
                        @if($actionError)
                            <div class="text-red-500 text-xs mt-1">{{ $actionError }}</div>
                        @endif                        

                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="closeActionModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-900 rounded-md" @if(!$actionType) disabled @endif>Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        // Initialize date range picker
        flatpickr("input[type='text'][wire\\:model='filters.date_range']", {
            mode: "range",
            dateFormat: "Y-m-d",
        });

        // Listen for Livewire events
        Livewire.on('notify', (data) => {
            // Implement your notification system here
            console.log(data.message);
        });

        // Handle file downloads - now handled by Laravel's built-in download response

        // Handle Alpine.js events for toggling advanced sections
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle advanced filters
            document.addEventListener('toggle-advanced-filters', function() {
                @this.set('showAdvancedFilters', !@this.showAdvancedFilters);
            });

            // Toggle advanced columns
            document.addEventListener('toggle-advanced-columns', function() {
                @this.set('showAdvancedColumns', !@this.showAdvancedColumns);
            });
        });
    </script>
    @endpush
</div> 