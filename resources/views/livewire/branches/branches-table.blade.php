<div class="space-y-8">

    <!-- Search and Pagination Controls -->
    <div class="flex justify-between items-center">
        <div class="flex items-center space-x-4">
            <div class="relative">
                <input type="text" wire:model.debounce.500ms="search"
                    class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 w-64"
                    placeholder="Search branches..." />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </div>
            </div>
        </div>
        <div class="flex items-center space-x-2">
            <label for="perPage" class="text-sm font-medium text-gray-700">Show:</label>
            <select wire:model="perPage" id="perPage"
                class="border border-gray-300 rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="5">5 per page</option>
                <option value="10">10 per page</option>
                <option value="25">25 per page</option>
            </select>
        </div>
    </div>

    <!-- Branches Table -->
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-x-auto branches-table-scroll">
        <table class="max-w-2/3 divide-y divide-gray-200" aria-label="Branches Table">
            <thead class="bg-gray-50">
                <tr>
                    @foreach ([
                            'branch_number' => 'Branch Number',
                            'name' => 'Branch Name',
                            'region' => 'Region',
                            'wilaya' => 'District',
                            'status' => 'Status',
                            'branch_type' => 'Type',
                            'branch_manager' => 'Manager',
                            'created_at' => 'Created At',
                        ] as $field => $label)
                        <th scope="col"
                            class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider cursor-pointer hover:bg-gray-100 transition-colors duration-150"
                            wire:click="sortBy('{{ $field }}')">
                            <div class="flex items-center space-x-1">
                                <span>{{ $label }}</span>
                                @if ($sortField === $field)
                                    @if ($sortDirection === 'asc')
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 15l7-7 7 7"></path>
                                        </svg>
                                    @else
                                        <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7"></path>
                                        </svg>
                                    @endif
                                @endif
                            </div>
                        </th>
                    @endforeach
                    <th scope="col"
                        class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse ($branches as $branch)
                    <tr class="hover:bg-blue-50 focus-within:bg-blue-100 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap align-top">
                            <div class="text-sm font-semibold text-gray-900">#{{ $branch->branch_number }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap align-top">
                            <div class="text-sm font-semibold text-gray-900">{{ $branch->name }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap align-top">
                            <div class="text-sm text-gray-900">{{ $branch->region }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap align-top">
                            <div class="text-sm text-gray-900">{{ $branch->wilaya }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap align-top">
                            @php
                                $statusColors = [
                                    'ACTIVE' => 'bg-green-100 text-green-800',
                                    'INACTIVE' => 'bg-red-100 text-red-800',
                                    'PENDING' => 'bg-yellow-100 text-yellow-800',
                                    'SUSPENDED' => 'bg-gray-100 text-gray-800',
                                ];
                                $statusColor = $statusColors[strtoupper($branch->status)] ?? 'bg-gray-100 text-gray-800';
                            @endphp
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusColor }}"
                                aria-label="Status: {{ ucfirst(strtolower($branch->status)) }}">
                                {{ ucfirst(strtolower($branch->status)) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap align-top">
                            <div class="text-sm text-gray-900">{{ $branch->branch_type }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap align-top">
                            <div class="flex items-center">
                                <div class="h-8 w-8 rounded-full bg-blue-100 flex items-center justify-center"
                                    aria-hidden="true">
                                    <svg class="h-4 w-4 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $branch->branch_manager ?? 'Not Assigned' }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap align-top text-sm text-gray-700">
                            <span>{{ $branch->created_at->format('M d, Y') }}</span>
                            <div class="text-xs text-gray-400 mt-1">{{ $branch->created_at->diffForHumans() }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap align-top text-sm font-medium">
                            @include('livewire.branches.branch-actions', [
                                'id' => $branch->id,
                                'canView' => $canView,
                                'canEdit' => $canEdit,
                                'canDelete' => $canDelete,
                            ])
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="px-6 py-8 text-center text-gray-400 text-base">No branches found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $branches->links() }}
    </div>

    <!-- Custom scrollbar styles -->
    <style>
        /* Custom red vertical scrollbar for branches table */
        .branches-table-scroll::-webkit-scrollbar {
            width: 2px;
        }
        .branches-table-scroll::-webkit-scrollbar-thumb {
            background: #e3342f;
            border-radius: 2px;
        }
        .branches-table-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        /* For Firefox */
        .branches-table-scroll {
            scrollbar-width: thin;
            scrollbar-color: #e3342f transparent;
        }
    </style>
</div>
