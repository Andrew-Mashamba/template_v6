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

    <div class="bg-white rounded-lg shadow-md">
        <!-- Header Section -->
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 space-y-4 md:space-y-0">
            <div class="flex items-center space-x-4">
                <h2 class="text-lg font-semibold text-gray-800">Active Members</h2>
                <span class="px-3 py-1 text-sm bg-green-100 text-green-800 rounded-full">
                    {{ $totalRecords }}
                </span>
            </div>
            
            <!-- Action Buttons -->
            <div class="flex items-center space-x-3 p-1">
                <button wire:click="exportTable" 
                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 ease-in-out">
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
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Primary Information Filters -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-gray-700">Primary Information</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Membership Type</label>
                        <select type="text" wire:model.live="filters.membership_type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Types</option>
                            <option value="Individual">Individual</option>
                            <option value="Business">Business</option>
                            <option value="Group">Group</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Branch</label>
                        <select type="text" wire:model.live="filters.branch_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Branches</option>
                            @foreach(App\Models\BranchesModel::all() as $branch)
                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Personal Information Filters -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-gray-700">Personal Information</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Gender</label>
                        <select type="text" wire:model.live="filters.gender" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Genders</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Marital Status</label>
                        <select type="text" wire:model.live="filters.marital_status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">All Status</option>
                            <option value="single">Single</option>
                            <option value="married">Married</option>
                            <option value="divorced">Divorced</option>
                            <option value="widowed">Widowed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Nationality</label>
                        <input type="text" wire:model.live.debounce.300ms="filters.nationality" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Search nationality...">
                    </div>
                </div>

                <!-- Location Filters -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-gray-700">Location</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <input type="text" wire:model.live.debounce.300ms="filters.address" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Search address...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Citizenship</label>
                        <input type="text" wire:model.live.debounce.300ms="filters.citizenship" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Search citizenship...">
                    </div>
                </div>

                <!-- Financial Information Filters -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-gray-700">Financial Information</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Income Source</label>
                        <input type="text" wire:model.live.debounce.300ms="filters.income_source" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Search income source...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">TIN Number</label>
                        <input type="text" wire:model.live.debounce.300ms="filters.tin_number" 
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            placeholder="Search TIN number...">
                    </div>
                </div>

                <!-- Date Range Filter -->
                <div class="space-y-4">
                    <h3 class="text-sm font-medium text-gray-700">Date Range</h3>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Registration Date</label>
                        <div class="mt-1 grid grid-cols-2 gap-2">
                            <div>
                                <input type="date" wire:model.live="filters.start_date" 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Start Date">
                            </div>
                            <div>
                                <input type="date" wire:model.live="filters.end_date" 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="End Date">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Clear Filters Button -->
            <div class="mt-4 flex justify-end">
                <button wire:click="$set('filters', { 
                    membership_type: '', 
                    branch_id: '',
                    gender: '',
                    marital_status: '',
                    nationality: '',
                    address: '',
                    citizenship: '',
                    income_source: '',
                    tin_number: '',
                    start_date: '', 
                    end_date: ''
                })" 
                    class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-md transition-colors duration-200">
                    <i class="fas fa-times mr-1"></i>Clear Filters
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
            <div class="space-y-4">
                <!-- Primary Fields -->
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Primary Fields</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach(['account_number', 'client_number', 'member_number'] as $column)
                            <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                    {{ ($columns[$column] ?? false) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Personal Information -->
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Personal Information</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach(['first_name', 'middle_name', 'last_name', 'date_of_birth', 'gender', 'marital_status', 'nationality', 'citizenship'] as $column)
                            <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                    {{ ($columns[$column] ?? false) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Contact Information -->
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Contact Information</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach(['phone_number', 'email', 'address'] as $column)
                            <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                    {{ ($columns[$column] ?? false) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Business Information -->
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Business Information</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach(['business_name', 'incorporation_number'] as $column)
                            <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                    {{ ($columns[$column] ?? false) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Financial Information -->
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Financial Information</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach(['income_available', 'income_source', 'tin_number', 'hisa', 'akiba', 'amana'] as $column)
                            <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                    {{ ($columns[$column] ?? false) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- System Fields -->
                <div>
                    <h3 class="text-sm font-medium text-gray-700 mb-2">System Fields</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                        @foreach(['membership_type', 'created_at'] as $column)
                            <label class="inline-flex items-center p-2 rounded-md hover:bg-gray-100 transition-colors duration-200">
                                <input type="checkbox" wire:click="toggleColumn('{{ $column }}')" 
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                    {{ ($columns[$column] ?? false) ? 'checked' : '' }}>
                                <span class="ml-2 text-sm text-gray-700">{{ ucfirst(str_replace('_', ' ', $column)) }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>
            </div>
            <!-- Reset Columns Button -->
            <div class="mt-4 flex justify-end">
                <button wire:click="$set('columns', { 
                    account_number: true,
                    client_number: true,
                    first_name: true,
                    last_name: true,
                    email: true,
                    phone_number: true,
                    membership_type: true,
                    created_at: true
                })" 
                    class="px-3 py-1 text-sm text-gray-600 hover:text-gray-800 hover:bg-gray-100 rounded-md transition-colors duration-200">
                    <i class="fas fa-undo mr-1"></i>Reset Columns
                </button>
            </div>
        </div>

        <!-- Export Options -->
        <div x-show="$wire.showExportOptions" 
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 transform -translate-y-2"
            x-transition:enter-end="opacity-100 transform translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-2"
            class="mb-6 p-4 bg-gray-50 rounded-lg shadow-sm">
            <div class="flex items-center space-x-4">
                <select type="text"  wire:model="exportFormat" class="rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="csv">CSV</option>
                    <option value="excel">Excel</option>
                    <option value="pdf">PDF</option>
                </select>
                <button wire:click="exportTable" 
                    class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200">
                    <i class="fas fa-download mr-2"></i>Export
                </button>
            </div>
            <!-- Export Info -->
            <div class="mt-2 text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Export will include {{ $this->totalRecords }} active members with currently visible columns
            </div>
        </div>--}}

        <!-- Search and Bulk Actions -->
        <div class="mb-6 flex flex-col md:flex-row justify-between items-start md:items-center space-y-4 md:space-y-0">
            <div class="relative w-full md:w-96">
                <input wire:model.live.debounce.300ms="search" type="text" placeholder="Search active members..." 
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
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
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-green-600"></div>
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
                                            @elseif($column === 'income_available' || $column === 'hisa' || $column === 'akiba' || $column === 'amana')
                                                {{ number_format($member->$column, 2) }}
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
                                No active members found
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
                    <select type="text"  wire:model.live="perPage" class="rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
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
        <div class="fixed inset-0 z-50 bg-black bg-opacity-30 overflow-y-auto">
            <div class="min-h-full flex items-start justify-center p-4">
                <div class="bg-white rounded-2xl w-full max-w-4xl shadow-lg mt-8 mb-8">
                    <!-- Header - Fixed -->
                    <div class="sticky top-0 bg-white border-b border-gray-200 p-4 rounded-t-2xl z-10">
                        <div class="flex justify-between items-center">
                            <h3 class="text-xl font-semibold text-gray-900">All Member Data</h3>
                            <div class="flex items-center space-x-2">
                                <button onclick="window.print()" class="text-gray-600 hover:text-gray-900 p-1 rounded hover:bg-gray-100">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                    </svg>
                                </button>
                                <button wire:click="closeAllDataModal" class="text-gray-400 hover:text-gray-500 p-1 rounded hover:bg-gray-100">
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Content - Scrollable -->
                    <div class="p-4">
                        <div class="grid grid-cols-5 gap-4 print:grid-cols-3">
                            @foreach($viewingMember->toArray() as $key => $value)
                            @if( $key !== 'id' && $key !== 'created_at' && $key !== 'updated_at' && $key !== 'deleted_at' && $key !== 'branch_id' && $key !== 'bills' && $key !== 'loans' && $key !== 'accounts' )
                                <div class="border-b border-gray-200 p-2">
                                    <div class="font-bold text-gray-700 text-xs">{{ ucfirst(str_replace('_', ' ', $key)) }}</div>
                                    <div class="text-gray-600 text-xs break-words">{{ is_array($value) ? json_encode($value) : $value }}</div>
                                </div>
                            @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <style>
            @media print {
                body * {
                    visibility: hidden;
                }
                .fixed.inset-0 {
                    position: absolute;
                    left: 0;
                    top: 0;
                    background: none !important;
                }
                .fixed.inset-0, .fixed.inset-0 * {
                    visibility: visible;
                }
                .bg-white {
                    box-shadow: none;
                }
                button {
                    display: none !important;
                }
                .sticky {
                    position: static !important;
                }
                .overflow-y-auto {
                    overflow: visible !important;
                }
            }
        </style>
    @endif

    @if($showEditModal && $editingMember)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-30">
            <div class="bg-white rounded-2xl w-full max-w-4xl shadow-none p-0 overflow-hidden">
                <div class="p-4">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-semibold text-gray-900">Edit Active Member</h3>
                        <button wire:click="closeEditModal" class="text-gray-400 hover:text-gray-500">
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="h-[90vh] overflow-y-auto">
                        <form wire:submit.prevent="saveMember" class="space-y-4">
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
                                            file:bg-green-50 file:text-green-700
                                            hover:file:bg-green-100">
                                        @error('photo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                @foreach($editingMember->getAttributes() as $key => $value)
                                    @if($key !== 'id' && $key !== 'created_at' && $key !== 'updated_at' && $key !== 'deleted_at' && $key !== 'branch_id' && $key !== 'bills' && $key !== 'loans' && $key !== 'accounts' && $key !== 'photo_url' && $key !== 'institution_id')
                                        @php
                                            $fieldConfig = $this->getFieldType($key);
                                            $isRequired = $fieldConfig['required'] ? 'required' : '';
                                            $isDisabled = isset($fieldConfig['disabled']) && $fieldConfig['disabled'] ? 'disabled' : '';
                                            $disabledClass = $isDisabled ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '';
                                        @endphp
                                        <div class="col-span-2 sm:col-span-1">
                                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                                {{ ucfirst(str_replace('_', ' ', $key)) }}
                                                @if($isRequired)
                                                    <span class="text-red-500">*</span>
                                                @endif
                                            </label>
                                            @if($fieldConfig['type'] === 'textarea')
                                                <textarea wire:model="editingMember.{{ $key }}" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm {{ $disabledClass }}"
                                                    {{ $isRequired }} {{ $isDisabled }}></textarea>
                                            @elseif($fieldConfig['type'] === 'select')
                                                <select wire:model="editingMember.{{ $key }}" 
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm {{ $disabledClass }}"
                                                    {{ $isRequired }} {{ $isDisabled }}>
                                                    <option value="">Select {{ ucfirst(str_replace('_', ' ', $key)) }}</option>
                                                    @foreach($fieldConfig['options'] as $optionValue => $optionLabel)
                                                        <option value="{{ $optionValue }}">{{ $optionLabel }}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input type="{{ $fieldConfig['type'] }}" 
                                                    wire:model="editingMember.{{ $key }}"
                                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500 sm:text-sm {{ $disabledClass }}"
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
                                <button type="button" wire:click="closeEditModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Cancel
                                </button>
                                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md shadow-sm hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
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
                        <input type="password" wire:model.defer="actionPassword" class="w-full rounded-md border-gray-300 focus:border-green-500 focus:ring-green-500" placeholder="Enter your password" required>
                        @if($actionError)
                            <div class="text-red-500 text-xs mt-1">{{ $actionError }}</div>
                        @endif                        

                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" wire:click="closeActionModal" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-md" @if(!$actionType) disabled @endif>Confirm</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @push('scripts')
    <script>
        // Listen for Livewire events
        Livewire.on('notify', (data) => {
            // Implement your notification system here
            console.log(data.message);
        });

        // Handle file downloads
        Livewire.on('download-file', (data) => {
            const link = document.createElement('a');
            link.href = data.url;
            link.download = data.filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    </script>
    @endpush
</div> 