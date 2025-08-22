<div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50">
    <div class="p-6">
        <!-- Header Section -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-blue-900 rounded-xl shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Bank Reconciliation Manager</h1>
                        <p class="text-gray-600 mt-1">Upload, process, and reconcile bank statements with internal transactions</p>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="flex items-center space-x-4">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-blue-100 rounded-lg">
                                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Sessions</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $sessions->count() }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Reconciled</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $sessions->where('status', 'completed')->count() }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-yellow-100 rounded-lg">
                                <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Processing</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $sessions->where('status', 'processing')->count() }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex gap-6">
            <!-- Enhanced Sidebar -->
            <div class="w-80 bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <!-- Modern Upload Section -->
                <div class="p-6 border-b border-gray-100">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Upload Bank Statement</h3>
                    
                    <!-- Bank Selection -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Bank</label>
                        <select wire:model="bankStatement" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200">
                            <option value="">Choose Bank</option>
                            <option value="im">I&M Bank</option>
                            <option value="crdb">CRDB Bank</option>
                            <option value="nmb">NMB Bank</option>
                            <option value="abbsa">ABBSA Bank</option>
                        </select>
                        @error('bankStatement') 
                            <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Modern File Upload -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Upload PDF Statement</label>
                        
                        <!-- Drag & Drop Zone -->
                        <div 
                            x-data="{ 
                                isDropping: false,
                                isUploading: false
                            }"
                            x-on:dragover.prevent="isDropping = true"
                            x-on:dragleave.prevent="isDropping = false"
                            x-on:drop.prevent="
                                isDropping = false;
                                $wire.upload('pdfFile', $event.dataTransfer.files[0])
                            "
                            class="relative"
                        >
                            <div 
                                class="border-2 border-dashed rounded-lg p-6 text-center transition-all duration-200 cursor-pointer hover:border-blue-400 hover:bg-blue-50"
                                :class="isDropping ? 'border-blue-500 bg-blue-100' : 'border-gray-300'"
                                wire:click="$refs.fileInput.click()"
                            >
                                <div wire:loading.remove wire:target="pdfFile">
                                    <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                        <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                    <div class="text-sm text-gray-600">
                                        <span class="font-medium text-blue-600 hover:text-blue-500">Click to upload</span>
                                        or drag and drop
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1">PDF files only, max 10MB</p>
                                </div>
                                
                                <!-- Upload Progress -->
                                <div wire:loading wire:target="pdfFile" class="space-y-2">
                                    <div class="flex items-center justify-center">
                                        <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span class="text-sm text-gray-600">Uploading...</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Hidden File Input -->
                            <input 
                                type="file" 
                                wire:model="pdfFile" 
                                x-ref="fileInput"
                                accept=".pdf" 
                                class="hidden"
                            />
                        </div>
                        
                        @error('pdfFile') 
                            <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                        @enderror
                    </div>

                    <!-- Upload Button -->
                    <button
                        type="button"
                        wire:click="uploadFile()"
                        wire:loading.attr="disabled"
                        wire:target="uploadFile"
                        class="w-full px-4 py-3 bg-blue-900 text-white font-medium rounded-lg hover:bg-blue-900 transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        <!-- Default State -->
                        <div wire:loading.remove wire:target="uploadFile">
                            <div class="flex items-center justify-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                </svg>
                                <span>Upload & Process</span>
                            </div>
                        </div>
                        
                        <!-- Loading State -->
                        <div wire:loading wire:target="uploadFile">
                            <div class="flex items-center justify-center space-x-2">
                                <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span>Processing...</span>
                            </div>
                        </div>
                    </button>

                    <!-- Processing Status -->
                    <div wire:loading wire:target="uploadFile">
                        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <svg class="animate-spin h-4 w-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-sm text-blue-700">Processing bank statement...</span>
                            </div>
                        </div>
                    </div>

                    <!-- Messages -->
                    @if (session()->has('success'))
                        <div class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>{{ session('success') }}</span>
                            </div>
                        </div>
                    @endif

                    @if (session()->has('error'))
                        <div class="mt-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded-lg">
                            <div class="flex items-center space-x-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <span>{{ session('error') }}</span>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sessions List -->
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Analysis Sessions</h3>

                    @if($sessions->count() > 0)
                        <div class="space-y-3">
                            @foreach ($sessions as $session)
                                <div
                                    wire:click="setActiveSession({{ $session->id }})"
                                    class="p-4 border rounded-xl cursor-pointer transition-all duration-200 hover:shadow-md
                                        {{ $activeSessionId === $session->id ? 'bg-blue-50 border-blue-300 shadow-md' : 'hover:bg-gray-50 border-gray-200' }}"
                                >
                                    <div class="flex justify-between items-start mb-2">
                                        <div class="flex-1">
                                            <h4 class="font-medium text-gray-900 text-sm">{{ Str::limit($session->account_name, 25) }}</h4>
                                            <p class="text-xs text-gray-600">{{ $session->bank }}</p>
                                            <p class="text-xs text-gray-500">{{ $session->statement_period }}</p>
                                        </div>
                                        <div class="text-right">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
                                                {{ $session->status === 'completed' ? 'bg-green-100 text-green-800' : 
                                                   ($session->status === 'processing' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                                {{ ucfirst($session->status) }}
                                            </span>
                                        </div>
                                    </div>
                                    @if($session->total_transactions > 0)
                                        <div class="flex items-center justify-between text-xs text-gray-500">
                                            <span>{{ $session->total_transactions }} transactions</span>
                                            <span>{{ $session->created_at->format('M d, Y') }}</span>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p class="text-gray-500 text-sm">No analysis sessions found</p>
                            <p class="text-gray-400 text-xs mt-1">Upload a bank statement to get started</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Enhanced Main Content Area -->
            <div class="flex-1">
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                    <!-- Content Header -->
                    <div class="px-8 py-6 border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white">
                        <div class="flex items-center justify-between">
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">
                                    @if($activeSession && $showData)
                                        {{ $activeSession->account_name }}
                                    @else
                                        Bank Reconciliation
                                    @endif
                                </h2>
                                <p class="text-gray-600 mt-1">
                                    @if($activeSession && $showData)
                                        {{ $activeSession->bank }} - {{ $activeSession->statement_period }}
                                    @else
                                        Select a session to view transactions and run reconciliation
                                    @endif
                                </p>
                            </div>
                            
                            @if($activeSession && $showData)
                                <button
                                    wire:click="runReconciliation()"
                                    wire:loading.attr="disabled"
                                    class="px-6 py-3 bg-green-600 text-white font-medium rounded-lg hover:bg-green-700 transition-colors disabled:opacity-50 flex items-center space-x-2"
                                >
                                    <span wire:loading.remove wire:target="runReconciliation">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span>Run Reconciliation</span>
                                    </span>
                                    <span wire:loading wire:target="runReconciliation" class="flex items-center space-x-2">
                                        <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        <span>Reconciling...</span>
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="p-8">
                        @if($activeSession && $showData)
                            <!-- Reconciliation Summary -->
                            @if($reconciliationSummary)
                                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                                    <div class="bg-blue-50 p-6 rounded-xl border border-blue-200">
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="text-sm font-medium text-blue-700">Total Transactions</h3>
                                            <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-2xl font-bold text-blue-900">{{ $reconciliationSummary['total'] }}</div>
                                    </div>
                                    
                                    <div class="bg-green-50 p-6 rounded-xl border border-green-200">
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="text-sm font-medium text-green-700">Reconciled</h3>
                                            <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-2xl font-bold text-green-900">{{ $reconciliationSummary['reconciled'] + $reconciliationSummary['matched'] }}</div>
                                    </div>
                                    
                                    <div class="bg-yellow-50 p-6 rounded-xl border border-yellow-200">
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="text-sm font-medium text-yellow-700">Unreconciled</h3>
                                            <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-2xl font-bold text-yellow-900">{{ $reconciliationSummary['unreconciled'] }}</div>
                                    </div>
                                    
                                    <div class="bg-purple-50 p-6 rounded-xl border border-purple-200">
                                        <div class="flex items-center justify-between mb-2">
                                            <h3 class="text-sm font-medium text-purple-700">Reconciliation Rate</h3>
                                            <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                            </svg>
                                        </div>
                                        <div class="text-2xl font-bold text-purple-900">{{ $reconciliationSummary['reconciliation_rate'] }}%</div>
                                    </div>
                                </div>
                            @endif

                            <!-- Reconciliation Results -->
                            @if($reconciliationResults)
                                <div class="mb-6 p-6 bg-gray-50 rounded-xl border border-gray-200">
                                    <h3 class="font-medium text-gray-800 mb-4 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        Reconciliation Results
                                    </h3>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                                            <span class="text-gray-600">Total Processed:</span>
                                            <span class="font-semibold text-gray-900 ml-1">{{ $reconciliationResults['total_processed'] }}</span>
                                        </div>
                                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                                            <span class="text-gray-600">Matched:</span>
                                            <span class="font-semibold text-green-600 ml-1">{{ $reconciliationResults['matched'] }}</span>
                                        </div>
                                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                                            <span class="text-gray-600">Partial Matches:</span>
                                            <span class="font-semibold text-yellow-600 ml-1">{{ $reconciliationResults['partial_matches'] }}</span>
                                        </div>
                                        <div class="bg-white p-3 rounded-lg border border-gray-200">
                                            <span class="text-gray-600">Unmatched:</span>
                                            <span class="font-semibold text-red-600 ml-1">{{ $reconciliationResults['unmatched'] }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Bank Transactions Table -->
                            <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
                                <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                                    <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                                        <svg class="w-5 h-5 mr-2 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        Bank Transactions
                                    </h3>
                                </div>
                                
                                <div class="overflow-x-auto">
                                    <table class="w-full">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Narration</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Debit</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Credit</th>
                                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Balance</th>
                                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @forelse($bankTransactions as $transaction)
                                                <tr class="hover:bg-gray-50 transition-colors duration-150">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ $transaction->transaction_date->format('d/m/Y') }}
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-900">
                                                        <div class="max-w-xs truncate" title="{{ $transaction->narration }}">
                                                            {{ $transaction->narration }}
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                                        @if($transaction->withdrawal_amount > 0)
                                                            <span class="font-medium text-red-600">{{ number_format($transaction->withdrawal_amount, 2) }}</span>
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                                        @if($transaction->deposit_amount > 0)
                                                            <span class="font-medium text-green-600">{{ number_format($transaction->deposit_amount, 2) }}</span>
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                                                        @if($transaction->balance)
                                                            <span class="font-medium">{{ number_format($transaction->balance, 2) }}</span>
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                            {{ $transaction->reconciliation_status === 'reconciled' ? 'bg-green-100 text-green-800' : 
                                                               ($transaction->reconciliation_status === 'matched' ? 'bg-blue-100 text-blue-800' : 
                                                               ($transaction->reconciliation_status === 'partial' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')) }}">
                                                            {{ ucfirst($transaction->reconciliation_status) }}
                                                        </span>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                                        @if($transaction->reconciliation_status === 'unreconciled')
                                                            <button
                                                                wire:click="showManualReconciliation({{ $transaction->id }})"
                                                                class="text-blue-600 hover:text-blue-900 transition-colors duration-150"
                                                            >
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                            </button>
                                                        @elseif($transaction->matchedTransaction)
                                                            <span class="text-green-600 flex items-center justify-center">
                                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                                </svg>
                                                            </span>
                                                        @else
                                                            <span class="text-gray-400">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                                        <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                        </svg>
                                                        <p>No transactions found for this session</p>
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @else
                            <!-- Empty State -->
                            <div class="text-center py-12">
                                <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">No session selected</h3>
                                <p class="text-gray-600 mb-4">Select an analysis session from the sidebar to view transactions and run reconciliation.</p>
                                <div class="flex items-center justify-center space-x-4 text-sm text-gray-500">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Upload a bank statement
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Select a session
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        Run reconciliation
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
