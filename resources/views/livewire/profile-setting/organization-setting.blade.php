<div class="py-6 px-8 bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen">
    {{-- Quick Stats Row --}}
    <div class="mb-8 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white rounded-xl shadow p-6 flex items-center space-x-4 border border-gray-100">
            <div class="p-2 bg-blue-100 rounded-lg">
                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <div class="text-sm text-gray-500">Total Share Types</div>
                <div class="text-lg font-bold text-gray-900">{{ is_array($shares) ? count($shares) : 0 }}</div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-6 flex items-center space-x-4 border border-gray-100">
            <div class="p-2 bg-yellow-100 rounded-lg">
                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <div>
                <div class="text-sm text-gray-500">Pending Documents</div>
                <div class="text-lg font-bold text-gray-900">
                    {{
                        collect(['budget_approval_letter','summary_of_general_meeting','chairperson_report','audit_financial_report','revenue_and_expense'])
                        ->filter(fn($f) => !DB::table('institution_files')->where('file_id', array_search($f, array_keys($fileFields)))->exists())->count()
                    }}
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow p-6 flex items-center space-x-4 border border-gray-100">
            <div class="p-2 bg-green-100 rounded-lg">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
            </div>
            <div>
                <div class="text-sm text-gray-500">Current Financial Year</div>
                <div class="text-lg font-bold text-gray-900">
                    {{-- Fix: Add null check for $institution before accessing startDate property --}}
                    {{ $institution && $institution->startDate ? \Carbon\Carbon::parse($institution->startDate)->format('Y') : 'N/A' }}
                </div>
            </div>
        </div>
    </div>

    {{-- Flash Messages --}}
    <div class="mb-6">
        @if (session()->has('message'))
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-sm flex items-center" role="alert">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('message') }}</span>
            </div>
        @endif
        @if (session()->has('error'))
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-sm flex items-center" role="alert">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {{-- General Info & Shares --}}
        <div class="lg:col-span-2 space-y-8">
            {{-- General Info Card --}}
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.324.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 011.37.49l1.296 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.431.992a6.759 6.759 0 010 .255c-.007.378.138.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.57 6.57 0 01-.22.128c-.331.183-.581.495-.644.869l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 01-1.369-.49l-1.297-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.297-2.247a1.125 1.125 0 011.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.087.22-.128.332-.183.582-.495.644-.869l.214-1.281z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    General Institution Info
                </h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Left --}}
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
                            <input disabled value="TZS" class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-500 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Member Registration Fees</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">TZS</span>
                                <input type="number" wire:model.defer="institution.registration_fees" min="0" step="0.01" class="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            @error('institution.registration_fees')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Minimum Shares</label>
                            <input type="number" wire:model.defer="institution.min_shares" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('institution.min_shares')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Initial Shares</label>
                            <input type="number" wire:model.defer="institution.initial_shares" min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('institution.initial_shares')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Value per Share</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">TZS</span>
                                <input type="number" wire:model.defer="institution.value_per_share" min="0" step="0.01" class="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            @error('institution.value_per_share')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Member Onboarding Process</label>
                            <div class="space-y-2">
                                <label class="inline-flex items-center">
                                    <input type="radio" wire:model.defer="institution.onboarding_process" value="1" class="form-radio h-4 w-4 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Payment > Share > Membership</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" wire:model.defer="institution.onboarding_process" value="2" class="form-radio h-4 w-4 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm text-gray-700">Membership > Payment > Shares</span>
                                </label>
                            </div>
                            @error('institution.onboarding_process')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Petty Amount Limit</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-500">TZS</span>
                                <input type="number" wire:model.defer="institution.petty_amount_limit" min="0" step="0.01" class="w-full pl-12 pr-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            </div>
                            @error('institution.petty_amount_limit')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    {{-- Right --}}
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Institution Name</label>
                            <input type="text" wire:model.defer="institution.institution_name" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('institution.institution_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                            <input type="email" wire:model.defer="institution.manager_email" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('institution.manager_email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                            <input type="tel" wire:model.defer="institution.phone_number" pattern="[0-9]{10}" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('institution.phone_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Region</label>
                            <input type="text" wire:model.defer="institution.region" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('institution.region')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Wilaya</label>
                            <input type="text" wire:model.defer="institution.wilaya" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            @error('institution.wilaya')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Set Member Inactive After</label>
                            <div class="space-y-2">
                                @foreach([6, 12, 18, 24] as $months)
                                    <label class="inline-flex items-center">
                                        <input type="radio" wire:model.defer="institution.inactivity" value="{{ $months }}" class="form-radio h-4 w-4 text-blue-600 focus:ring-blue-500">
                                        <span class="ml-2 text-sm text-gray-700">{{ $months }} months of inactivity</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('institution.inactivity')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            {{-- Shares Card --}}
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-green-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Types of Shares
                </h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type of Share</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($shares ?? [] as $share)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $share['type'] }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        @if(isset($share['value']) && $share['value'] > 0)
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-blue-50 text-blue-700 font-semibold">TZS {{ number_format($share['value'], 0, ',', ',') }}</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded bg-gray-100 text-gray-500">N/A</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <button wire:click="editShare({{ $share['id'] }})" class="text-blue-600 hover:text-blue-900 font-semibold flex items-center">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            Edit
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No share types found. Please contact your administrator.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        {{-- Financial Year & Documents --}}
        <div class="space-y-8">
            {{-- Financial Year Card --}}
            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                    <svg class="w-6 h-6 mr-2 text-yellow-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Financial Year
                </h2>
                <label for="startDate" class="block text-sm font-medium text-gray-700 mb-2">Financial Year Date</label>
                <input type="date" wire:model.defer="institution.startDate" id="startDate" class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                @error('institution.startDate')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            {{-- Document Upload Cards --}}
            @foreach([
                'budget_approval_letter' => 'Budget Approval Letter',
                'summary_of_general_meeting' => 'Summary of General Meeting',
                'chairperson_report' => "Chairperson's Report",
                'audit_financial_report' => "External Auditor's Financial Report",
                'revenue_and_expense' => 'Revenue and Expense Analysis'
            ] as $field => $label)
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-8">
                    <h2 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-blue-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        {{ $label }}
                        @php
                            $fileId = array_search($field, array_keys($fileFields));
                            $hasFile = DB::table('institution_files')->where('file_id', $fileId)->exists();
                        @endphp
                        <span class="ml-2 inline-flex items-center px-2 py-1 rounded text-xs font-semibold {{ $hasFile ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                            {{ $hasFile ? 'Uploaded' : 'Missing' }}
                        </span>
                    </h2>
                    <form wire:submit.prevent="store" class="space-y-4">
                        <div class="flex items-center space-x-4">
                            <input type="file" wire:model="{{ $field }}" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100" accept=".pdf">
                            <button type="submit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-900 hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Upload
                            </button>
                        </div>
                        @error($field)
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </form>
                    {{-- File List --}}
                    <div class="mt-4">
                        <button type="button" wire:click="toggleFileList('{{ $field }}')" class="flex items-center justify-between w-full px-4 py-2 text-sm font-medium text-left text-gray-700 bg-gray-50 rounded-md hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                            <span>Show {{ $label }}</span>
                            <svg class="w-5 h-5 transform transition-transform duration-200 {{ $field === 'open' . ucfirst($field) ? 'rotate-180' : '' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        @if($field === 'open' . ucfirst($field))
                            <div class="mt-2 space-y-2">
                                @foreach(DB::table('institution_files')->where('file_id', $fileId)->get() as $file)
                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-md">
                                        <div class="flex items-center space-x-2">
                                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                                            <span class="text-sm text-gray-600">{{ $file->file_name }}</span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <button wire:click="download({{ $file->id }})" class="text-blue-600 hover:text-blue-800"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg></button>
                                            <button wire:click="deleteFile({{ $file->id }})" class="text-red-600 hover:text-red-800"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    {{-- Save Button --}}
    <div class="mt-8 flex justify-end">
        <button type="button" wire:click="institutionSetting" wire:loading.attr="disabled" wire:target="institutionSetting" aria-label="Save institution settings" aria-busy="false" aria-live="polite" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-blue-900 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-70 disabled:cursor-not-allowed transition-colors duration-200 ease-in-out">
            <span wire:loading.remove wire:target="institutionSetting" class="inline-flex items-center">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Save Changes
            </span>
            <span wire:loading wire:target="institutionSetting" >
                <div class="inline-flex items-center">
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                <span>Saving...</span>
                </div>
            </span>
        </button>
    </div>
</div>

