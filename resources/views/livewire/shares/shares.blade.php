{{-- Shares Management View --}}
<div class="container-fluid">
    {{-- Flash Messages --}}
    @if (session()->has('success'))
        <div class="fixed bottom-4 right-4 z-50" x-data="{ show: true }" x-show="show" x-transition>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" @click="show = false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </span>
            </div>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed bottom-4 right-4 z-50" x-data="{ show: true }" x-show="show" x-transition>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" @click="show = false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </span>
            </div>
        </div>
    @endif

    @if (session()->has('validation_errors'))
        <div class="fixed bottom-4 right-4 z-50" x-data="{ show: true }" x-show="show" x-transition>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <div class="font-bold mb-2">Validation Errors:</div>
                <ul class="list-disc list-inside">
                    @foreach (session('validation_errors') as $field => $errors)
                        @foreach ($errors as $error)
                            <li>{{ ucfirst(str_replace('_', ' ', $field)) }}: {{ $error }}</li>
                        @endforeach
                    @endforeach
                </ul>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" @click="show = false" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </span>
            </div>
        </div>
    @endif

    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 2px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #3b82f6;
            border-radius: 3px;
        }
        
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #2563eb;
        }
    </style>

    <div class="min-h-screen bg-gray-50 p-6">
      <!-- Dashboard Header -->
      <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Share Management</h1>
          <p class="text-gray-600 mt-1">Track and manage all share transactions</p>
        </div>
        <div class="flex gap-3 w-full sm:w-auto">
         
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Share Products Overview -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <div class="flex justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Share Products</p>
              <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $totalShareProducts }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-indigo-50 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
              </svg>
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm text-gray-500">
            <span class="flex items-center text-green-500">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
              </svg>
              <span class="ml-1">{{ $activeShareProducts }} Active</span>
            </span>
          </div>
        </div>

        <!-- Total Share Value -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <div class="flex justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Total Share Value</p>
              <p class="mt-1 text-3xl font-semibold text-gray-900">TZS {{ number_format($totalShareValue, 2) }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-green-50 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm text-gray-500">
            <span>Based on current share prices</span>
          </div>
        </div>

        <!-- Share Issuance Activity -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <div class="flex justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Share Issuance Activity</p>
              <p class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format($totalSharesIssued) }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-blue-50 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
              </svg>
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm text-gray-500">
            <span class="flex items-center text-green-500">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
              </svg>
              <span class="ml-1">{{ number_format($sharesIssuedToday) }} Today</span>
            </span>
          </div>
        </div>

        <!-- Pending Approvals -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
          <div class="flex justify-between">
            <div>
              <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Pending Approvals</p>
              <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $pendingShareIssuances }}</p>
            </div>
            <div class="h-12 w-12 rounded-full bg-amber-50 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
            </div>
          </div>
          <div class="mt-4 flex items-center text-sm text-gray-500">
            <span>Awaiting approval</span>
          </div>
        </div>
      </div>
              <!-- Header Section -->
              {{--<div class="mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="p-3 bg-blue-900 rounded-xl shadow-lg">
                        <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900 tracking-tight">Share Management</h1>
                        <p class="text-gray-600 mt-1">Track, manage, and analyze organizational shares</p>
                    </div>
                </div>
                
                <!-- Quick Stats -->
                <div class="flex items-center space-x-4">
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Shares</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $totalShareProducts }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-green-100 rounded-lg">
                                <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Shares Value</p>
                                <p class="text-lg font-semibold text-gray-900">{{ number_format($totalShareValue,2) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100">
                        <div class="flex items-center">
                            <div class="p-2 bg-red-100 rounded-lg">
                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium text-gray-500">Total Issued Shares</p>
                                <p class="text-lg font-semibold text-gray-900">{{number_format($totalSharesIssued) }}</p>
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
                                <p class="text-sm font-medium text-gray-500">Pending Approvals</p>
                                <p class="text-lg font-semibold text-gray-900">{{ $pendingShareIssuances }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>--}}

      <!-- Main Content -->
      <div class="flex flex-col lg:flex-row gap-6 w-full">
        <!-- Sidebar Navigation -->
        <div class="w-full max-w-xs shrink-0">
         <div class="bg-white p-6 rounded-2xl shadow-md border border-gray-100 bg-indigo-50 p-6 rounded-2xl shadow-md border border-gray-100">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
            <nav class="space-y-2">
            <button wire:click="homeDashboard" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium
             @if($this->sidebar_view == 'home_dashboard') text-white bg-blue-800 hover:bg-blue-50 @else text-gray-700 bg-gray-50 hover:bg-gray-100 hover:text-blue-900 @endif rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                <path  d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                </svg>
               Dashboard
            </button>

            <button wire:click="showAddSharesAccountModal" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium
             @if($this->sidebar_view == 'add_shares_account') text-white bg-blue-800 hover:bg-blue-50 @else text-gray-700 bg-gray-50 hover:bg-gray-100 hover:text-blue-900 @endif rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z" />
                </svg>
                Add Account
            </button>

            <button wire:click="showIssueSharesModal" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium
             @if($this->sidebar_view == 'issue_shares') text-white bg-blue-800 hover:bg-blue-50 @else text-gray-700 bg-gray-50 hover:bg-gray-100 hover:text-blue-900 @endif rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z" />
                </svg>
                Issue Shares
            </button>

            <button wire:click="showShareWithdrawalModal" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium
             @if($this->sidebar_view == 'share_withdrawal') text-white bg-blue-800 hover:bg-blue-50 @else text-gray-700 bg-gray-50 hover:bg-gray-100 hover:text-blue-900 @endif rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z" />
                </svg>
                Share Withdrawal
            </button>

            <button wire:click="showShareWithdrawalReport" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium
             @if($this->sidebar_view == 'share_withdrawal_report') text-white bg-blue-800 hover:bg-blue-50 @else text-gray-700 bg-gray-50 hover:bg-gray-100 hover:text-blue-900 @endif rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                <path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6z" />
                </svg>
                Share Withdrawal Report
            </button>

            <button wire:click="showShareTransferModal" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium
             @if($this->sidebar_view == 'share_transfer') text-white bg-blue-800 hover:bg-blue-50 @else text-gray-700 bg-gray-50 hover:bg-gray-100 hover:text-blue-900 @endif rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                Transfer Shares
            </button>

            <button wire:click="openShareTransfersReport" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium
             @if($this->sidebar_view == 'share_transfers_report') text-white bg-blue-800 hover:bg-blue-50 @else text-gray-700 bg-gray-50 hover:bg-gray-100 hover:text-blue-900 @endif rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M3 17a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm3.293-7.707a1 1 0 011.414 0L9 10.586V3a1 1 0 112 0v7.586l1.293-1.293a1 1 0 111.414 1.414l-3 3a1 1 0 01-1.414 0l-3-3a1 1 0 010-1.414z" clip-rule="evenodd" />
                </svg>
                Share Transfers Report
            </button>

            <button wire:click="showDividendOverview" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium
             @if($this->sidebar_view == 'dividend_overview') text-white bg-blue-800 hover:bg-blue-50 @else text-gray-700 bg-gray-50 hover:bg-gray-100 hover:text-blue-900 @endif rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" />
                </svg>
                Dividend Overview
            </button>
       
            <button wire:click="showBulkUpload" class="w-full flex items-center gap-3 px-4 py-3 text-sm font-medium
             @if($this->sidebar_view == 'bulk_upload') text-white bg-blue-800 hover:bg-blue-50 @else text-gray-700 bg-gray-50 hover:bg-gray-100 hover:text-blue-900 @endif rounded-lg transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                Bulk Upload
            </button>
       
            </nav>
        </div>
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 w-3/4">



        @if($showShareWithdrawalReport)
          @include('livewire.shares.share-withdrawal-report')
        @elseif($dividendOverview)
          <livewire:shares.dividend-overview />
        @elseif($showBulkUploadArea)
          <livewire:shares.bulk-upload />  
        @elseif($homeDashboard)

          <!-- Filters -->
          <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div>
                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                <div class="relative rounded-md shadow-sm">
                  <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                  </div>
                  <input 
                    type="text" 
                    wire:model.debounce.300ms="search" 
                    id="search" 
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" 
                    placeholder="Search accounts..."
                  >
                </div>
              </div>
              
              <div>
                <label for="shareProduct" class="block text-sm font-medium text-gray-700 mb-1">Share Type</label>
                <select 
                  wire:model="selectedShareProduct" 
                  id="shareProduct" 
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                >
                  <option value="">All Share Types</option>
                  @foreach($shareProducts as $product)
                    <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                  @endforeach
                </select>
              </div>
              
              <div>
                <label for="statusFilter" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select 
                  wire:model="statusFilter" 
                  id="statusFilter" 
                  class="mt-1 block w-full pl-3 pr-10 py-2 text-base border border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md"
                >
                  <option value="">All Statuses</option>
                  <option value="approved">Approved</option>
                  <option value="pending">Pending</option>
                  <option value="blocked">Blocked</option>
                </select>
              </div>
            </div>
          </div>

          <!-- Shares Table -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-visible">
            <div class="px-5 py-4 border-b border-gray-200 flex justify-between items-center">
              <h2 class="font-medium text-gray-900">Share Accounts</h2>
              <div class="flex items-center gap-3">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                  Total: {{ $shareAccounts->total() }}
                </span>
                {{--<button class="inline-flex items-center px-3 py-1.5 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                  <svg xmlns="http://www.w3.org/2000/svg" class="-ml-0.5 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                  </svg>
                  Refresh
                </button>--}}
              </div>
            </div>
            
            <div class="overflow-x-auto">
              <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                  <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account #</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Member</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Shares</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Share Value</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Total Value</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                  </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                  @forelse($shareAccounts as $account)
                    <tr class="hover:bg-gray-100 transition-colors">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">{{ $account->account_number }}</div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                          <div class="flex-shrink-0 h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                            <span class="text-indigo-600 font-medium">
                              {{ substr($account->first_name, 0, 1) }}{{ substr($account->last_name, 0, 1) }}
                            </span>
                          </div>
                          <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">
                              {{ trim($account->first_name . ' ' . $account->last_name) ?: 'N/A' }}
                            </div>
                            <div class="text-sm text-gray-500">{{ $account->email }}</div>
                          </div>
                        </div>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                        {{ number_format($account->number_of_shares ?? 0) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-900">
                        TZS {{ number_format($account->price ?? 0, 2) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium text-gray-900">
                      TZS {{ number_format($account->total_value ?? 0, 2) }}
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap">
                        @php
                          $statusColors = [
                            'approved' => 'bg-green-100 text-green-800',
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'blocked' => 'bg-red-100 text-red-800'
                          ];
                        @endphp
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$account->status] ?? 'bg-gray-100 text-gray-800' }}">
                          {{ ucfirst($account->status) }}
                        </span>
                      </td>
                      <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end space-x-2">                         
                          
                          <button 
                            wire:click="viewAccount({{ $account->id }})" 
                            class="p-2 text-green-600 hover:text-green-800 hover:bg-green-50 rounded-lg transition-colors duration-200"
                            title="View Account"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                          </button>
                          
                          <button 
                            wire:click="blockSharesAccountModal({{ $account->id }})" 
                            class="p-2 text-amber-600 hover:text-amber-800 hover:bg-amber-50 rounded-lg transition-colors duration-200"
                            title="{{ $account->status === 'blocked' ? 'Unblock Account' : 'Block Account' }}"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                          </button>
                          
                          {{--<button 
                            wire:click="deleteConfirm({{ $account->id }})" 
                            class="p-2 text-red-600 hover:text-red-800 hover:bg-red-50 rounded-lg transition-colors duration-200"
                            title="Delete Account"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                          </button>--}}
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="7" class="px-6 py-8 text-center">
                        <div class="flex flex-col items-center justify-center text-gray-400">
                          <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                          </svg>
                          <h3 class="text-lg font-medium text-gray-500 mb-1">No share accounts found</h3>
                          <p class="max-w-xs">Try adjusting your search or filter to find what you're looking for.</p>
                          <button 
                            wire:click="showAddSharesAccountModal" 
                            class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                          >
                            <svg xmlns="http://www.w3.org/2000/svg" class="-ml-1 mr-2 h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                              <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                            </svg>
                            Create New Account
                          </button>
                        </div>
                      </td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
              
              {{-- Pagination --}}
              <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                  @if($shareAccounts->onFirstPage())
                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-gray-50 cursor-not-allowed">
                      Previous
                    </span>
                  @else
                    <button wire:click="previousPage" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-100">
                      Previous
                    </button>
                  @endif

                  @if($shareAccounts->hasMorePages())
                    <button wire:click="nextPage" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-100">
                      Next
                    </button>
                  @else
                    <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-gray-50 cursor-not-allowed">
                      Next
                    </span>
                  @endif
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                  <div>
                    <p class="text-sm text-gray-700">
                      Showing
                      <span class="font-medium">{{ $shareAccounts->firstItem() }}</span>
                      to
                      <span class="font-medium">{{ $shareAccounts->lastItem() }}</span>
                      of
                      <span class="font-medium">{{ $shareAccounts->total() }}</span>
                      results
                    </p>
                  </div>
                  <div>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                      {{-- Previous Page Link --}}
                      @if($shareAccounts->onFirstPage())
                        <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-gray-50 text-sm font-medium text-gray-500 cursor-not-allowed">
                          <span class="sr-only">Previous</span>
                          <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                          </svg>
                        </span>
                      @else
                        <button wire:click="previousPage" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-100">
                          <span class="sr-only">Previous</span>
                          <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
                          </svg>
                        </button>
                      @endif

                      {{-- Pagination Elements --}}
                      @foreach($shareAccounts->getUrlRange(1, $shareAccounts->lastPage()) as $page => $url)
                        @if($page == $shareAccounts->currentPage())
                          <span class="relative inline-flex items-center px-4 py-2 border border-indigo-500 bg-indigo-50 text-sm font-medium text-indigo-600">
                            {{ $page }}
                          </span>
                        @else
                          <button wire:click="gotoPage({{ $page }})" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-100">
                            {{ $page }}
                          </button>
                        @endif
                      @endforeach

                      {{-- Next Page Link --}}
                      @if($shareAccounts->hasMorePages())
                        <button wire:click="nextPage" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-100">
                          <span class="sr-only">Next</span>
                          <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                          </svg>
                        </button>
                      @else
                        <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-gray-50 text-sm font-medium text-gray-500 cursor-not-allowed">
                          <span class="sr-only">Next</span>
                          <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                            <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
                          </svg>
                        </span>
                      @endif
                    </nav>
                  </div>
                </div>
              </div>
            </div>
          </div>

        @endif
	    </div>














	
</div>

</div>





    {{-- Modals --}}
    {{-- Issue New Shares Modal --}}
    @if($showIssueShares)
    <div class="overflow-y-auto scrollbar-hide fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Issue New Shares</h3>
                <button wire:click="closeShowIssueNewShares" class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form wire:submit.prevent="issueShares" class="p-4">
                {{-- Step 1: Share Product Selection --}}
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Step 1: Select Share Product</h3>
                    <div class="relative">
                        <select wire:model="product" 
                                wire:loading.attr="disabled"
                                class="w-full border-gray-300 rounded-lg p-2 focus:outline-none focus:ring focus:ring-blue-200">
                            <option value="">Select Share Product</option>
                            @forelse($shareProducts as $p)
                                <option value="{{ $p->id }}">{{ $p->product_name }}</option>
                            @empty
                                <option value="" disabled>No share products available</option>
                            @endforelse
                        </select>
                        <div wire:loading wire:target="product" class="absolute right-2 top-2">
                            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        @error('product') 
                            <span class="text-red-500 text-sm">{{ $message }}</span> 
                        @enderror
                    </div>

                    {{-- Product Details Card --}}
                    @if($selectedProduct)                    
                        <div class="mt-4 bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Product Details</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Product Name:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $selectedProduct['product_name'] }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Nominal Price:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ number_format($selectedProduct['nominal_price'], 2) }} TZS</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Available Shares:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ number_format($selectedProduct['available_shares']) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Shares Per Member:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ number_format($selectedProduct['shares_per_member']) }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Step 2: Member Selection --}}
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Step 2: Enter Member Details</h3>
                    <div class="relative">
                        <input type="text" 
                               wire:model="client_number" 
                               wire:keyup="checkClientNumberLength"
                               
                               maxlength="5"
                               pattern="[0-9]*"
                               inputmode="numeric"
                               placeholder="Enter 5-digit client number"
                               class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm {{ $errors->has('client_number') ? 'border-red-500' : '' }}">
                        <div wire:loading wire:target="client_number" class="absolute right-2 top-2">
                            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        @error('client_number') 
                            <span class="text-red-500 text-sm">{{ $message }}</span> 
                        @enderror
                        <p class="mt-1 text-sm text-gray-500">Enter 5-digit client number</p>
                    </div>

                    {{-- Member Information Display --}}
                    @if($memberDetails)
                        <div class="mt-4 bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Member Information</h4>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                    <p class="text-sm text-gray-600">Status:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $memberDetails['full_name'] }} </p>
                                </div>
                            <div>
                                    <p class="text-sm text-gray-600">Member Number:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $memberDetails['client_number'] }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Status:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $memberDetails['status'] }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Phone:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $memberDetails['phone_number'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Email:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $memberDetails['email'] ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Current Shares:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ number_format($memberDetails['current_shares'] ?? 0) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Join Date:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ $memberDetails['join_date'] ?? 'N/A' }}</p>
                                </div>
                            </div>
                        </div>
                    @elseif($client_number && strlen($client_number) === 5)
                        <div class="mt-4 p-4 bg-red-50 rounded-lg border border-red-200">
                            <p class="text-red-700">No member found with the provided member number.</p>
                        </div>
                    @endif
                </div>

                {{-- Step 3: Number of Shares --}}
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Step 3: Enter Number of Shares</h3>
                    <div class="relative">
                        <input type="number" 
                               wire:model.defer="number_of_shares" 
                            
                               min="1"
                               max="{{ $selectedProduct['shares_per_member'] ?? 0 }}"
                               class="w-full border-gray-300 rounded-lg p-2 focus:outline-none focus:ring focus:ring-blue-200">
                        @error('number_of_shares') 
                            <span class="text-red-500 text-sm">{{ $message }}</span> 
                        @enderror
                    </div>

                    {{-- Share Calculation Card --}}
                    @if($number_of_shares > 0 && $selectedProduct)
                        <div class="mt-4 bg-gray-50 p-4 rounded-lg">
                            <h4 class="text-sm font-medium text-gray-900 mb-2">Share Calculation</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Number of Shares:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ number_format($number_of_shares) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Price per Share:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ number_format($price_per_share, 2) }} TZS</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Value:</p>
                                    @php
                                        $this->total_value = $number_of_shares * $price_per_share;
                                    @endphp
                                    <p class="text-sm font-medium text-gray-900">{{ number_format($total_value, 2) }} TZS</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Current Member Shares:</p>
                                    <p class="text-sm font-medium text-gray-900">{{ number_format($memberDetails['current_shares'] ?? 0) }}</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Step 4: Linked Savings Account --}}
                @if($memberDetails)
                @php 
          
                @endphp
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Step 4: Select Linked Savings Account - {{$memberDetails['client_number']}}</h3>
                    <div class="relative">
                        <select wire:model="linked_savings_account" 
                                wire:loading.attr="disabled"
                                class="w-full border-gray-300 rounded-lg p-2 focus:outline-none focus:ring focus:ring-blue-200">
                            <option value="">Select Savings Account</option>
                            @forelse(DB::table('accounts')->where('client_number', $memberDetails['client_number'])->where('product_number', '2000')->get() as $account)
                                <option value="{{ $account->account_number }}">
                                    {{ $account->account_name }} ({{ $account->account_number }}) - Balance: {{ number_format($account->balance, 2) }} TZS
                                </option>
                            @empty
                                <option value="" disabled>No savings accounts available</option>
                            @endforelse
                        </select>
                        <div wire:loading wire:target="linked_savings_account" class="absolute right-2 top-2">
                            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        @error('linked_savings_account') 
                            <span class="text-red-500 text-sm">{{ $message }}</span> 
                        @enderror
                    </div>

                    {{-- Account Balance Warning --}}
                    @if(!empty($linked_savings_account) && isset($total_value) && $total_value > 0)
                        @php
                            $selectedAccount = DB::table('accounts')->where('account_number', $linked_savings_account)->first();
                            $hasSufficientBalance = $selectedAccount && isset($selectedAccount->balance) && $selectedAccount->balance >= $total_value;
                        @endphp
                        <div class="mt-2 p-3 rounded {{ $hasSufficientBalance ? 'bg-green-50 text-green-700' : 'bg-red-50 text-red-700' }}">
                            <p class="text-sm">
                                @if($hasSufficientBalance)
                                    ✓ Sufficient balance available
                                @else
                                    ⚠ Insufficient balance. Required: {{ number_format($total_value, 2) }} TZS
                                @endif
                            </p>
                        </div>
                    @endif

                </div>
                @endif


                {{-- Step 5: Share Account --}}
                @if($memberDetails)
                <div class="mb-6">
                    <h3 class="text-sm font-medium text-gray-700 mb-2">Step 5: Select Share Account</h3>
                    <div class="relative">
                        <select wire:model="share_account" 
                                wire:loading.attr="disabled"
                                class="w-full border-gray-300 rounded-lg p-2 focus:outline-none focus:ring focus:ring-blue-200">
                            <option value="">Select Share Account</option>
                            @forelse(DB::table('accounts')->where('client_number', $memberDetails['client_number'])->where('product_number', '1000')->get() as $account)
                                <option value="{{ $account->account_number }}">
                                    {{ $account->account_name }} ({{ $account->account_number }}) - Balance: {{ number_format($account->balance, 2) }} TZS
                                </option>
                            @empty
                                <option value="" disabled>No share accounts available</option>
                            @endforelse
                        </select>
                        <div wire:loading wire:target="share_account" class="absolute right-2 top-2">
                            <svg class="animate-spin h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </div>
                        @error('share_account') 
                            <span class="text-red-500 text-sm">{{ $message }}</span> 
                        @enderror
                    </div>
                </div>
                @endif

                {{-- Action Buttons --}}
                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" 
                            wire:click="closeShowIssueNewShares" 
                            wire:loading.attr="disabled"
                            class="px-4 py-2 rounded bg-gray-300 hover:bg-gray-400 text-gray-800">
                        Cancel
                    </button>
                    <button type="submit" 
                            wire:loading.attr="disabled"
                            class="px-4 py-2 rounded bg-blue-900 hover:bg-blue-50 text-white">
                        <span wire:loading.remove wire:target="issueShares">Issue Shares</span>
                        <span wire:loading wire:target="issueShares">
                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif               



    {{-- Add Share Account Modal --}}
    @if($showAddSharesAccount)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Share Account</h5>
                    <button type="button" class="btn-close" wire:click="closeShowAddSharesAccount"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="addSharesAccount">
                        <div class="mb-3">
                            <label for="member" class="block text-sm font-medium text-gray-700">Member Number</label>
                            <div class="mt-1">
                                <input type="text" 
                                    wire:model.defer="member" 
                                    wire:keyup="validateMemberNumber"
                                    id="member" 
                                    maxlength="5"
                                    placeholder="Enter 5-digit member number"
                                    class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                            </div>
                            @error('member') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        {{-- Member Information Display --}}
                        @if($memberDetails)
                            <div class="mt-4 bg-gray-50 p-4 rounded-md">
                                <h4 class="text-sm font-medium text-gray-900 mb-2">Member Information</h4>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <p class="text-sm text-gray-600">Name:</p>
                                        <p class="text-sm font-medium text-gray-900">{{ $memberDetails['first_name'] }} {{ $memberDetails['last_name'] }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Member Number:</p>
                                        <p class="text-sm font-medium text-gray-900">{{ $memberDetails['client_number'] }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Phone:</p>
                                        <p class="text-sm font-medium text-gray-900">{{ $memberDetails['phone_number'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Email:</p>
                                        <p class="text-sm font-medium text-gray-900">{{ $memberDetails['email'] ?? 'N/A' }}</p>
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- Selected Member Name Display --}}
                        @if($memberName)
                            <div class="bg-gray-50 p-3 rounded-md">
                                <p class="text-sm text-gray-600">Selected Member: <span class="font-medium text-gray-900">{{ $memberName }}</span></p>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Share Product</label>
                            <select wire:model="product" class="form-select">
                                <option value="">Select Product</option>
                                @foreach($shareProducts as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" wire:click="closeShowAddSharesAccount">Cancel</button>
                            <button type="submit" class="btn btn-primary">Create Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Declare Dividend Modal --}}
    @if($showDeclareDividend)
    <div class="overflow-y-auto scrollbar-hide fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl">
            <div class="flex justify-between items-center p-4 border-b">
                <h3 class="text-lg font-semibold text-gray-900">Declare Dividend</h3>
                <button wire:click="closeModal" class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
                <div class="p-4">
                    <form wire:submit.prevent="declareDividend">
                        <div class="mb-3">
                            <label class="form-label">Year</label>
                            <input type="number" wire:model="dividendYear" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dividend Rate (%)</label>
                            <input type="number" step="0.01" wire:model="dividendRate" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Mode</label>
                            <select wire:model="dividendPaymentMode" class="form-select">
                                <option value="cash">Cash</option>
                                <option value="bank">Bank Transfer</option>
                                <option value="shares">Reinvest in Shares</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Narration</label>
                            <textarea wire:model.defer="dividendNarration" class="form-control"></textarea>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Declare Dividend</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true"></div>
    @endif
                          
    {{-- Set Share Price Modal --}}
    @if($showSetSharePrice)
    <div class="modal fade show" style="display: block;" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Set Share Price</h5>
                    <button type="button" class="btn-close" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <form wire:submit.prevent="setSharePrice">
                        <div class="mb-3">
                            <label class="form-label">New Share Price</label>
                            <input type="number" step="0.01" wire:model.defer="newSharePrice" class="form-control">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Effective Date</label>
                            <input type="date" wire:model.defer="effectiveDate" class="form-control">
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-secondary" wire:click="closeModal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Set Price</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade show"></div>
    @endif

    {{-- Loading Indicator --}}
    @if($loading)
    <div class="position-fixed top-0 start-0 w-100 h-100 d-flex justify-content-center align-items-center" style="background: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="spinner-border text-light" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>
    @endif

    {{-- Create Share Account Modal --}}
    @if($showCreateShareAccount)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity"></div>

        <div class="flex min-h-full items-end justify-center p-4 text-centerx sm:items-center sm:p-0">
            <div class="relative transform overflow-hidden rounded-lg bg-white px-4 pb-4 pt-5 text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg sm:p-6">
                <div class="absolute right-0 top-0 pr-4 pt-4">
                    <button type="button" wire:click="closeModal" class="rounded-md bg-white text-gray-400 hover:text-gray-500 focus:outline-none">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="sm:flex sm:items-start">
                    <div class="mt-3 text-centerx sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium text-gray-900" id="modal-title">
                            Create New Share Account
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label for="member" class="block text-sm font-medium text-gray-700">Member Number</label>
                                <div class="mt-1">
                                    <input type="text" 
                                        wire:model.defer="member" 
                                        wire:keyup="validateMemberNumber"
                                        id="member" 
                                        maxlength="5"
                                        placeholder="Enter 5-digit member number"
                                        class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                </div>
                                @error('member') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            @if($memberDetails)
                                <div class="mt-4 bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <h4 class="text-gray-700 font-semibold">{{ $memberDetails['full_name'] }}</h4>
                                            <p class="text-sm text-gray-600">Member #: {{ $memberDetails['member_number'] }}</p>
                                            <p class="text-sm text-gray-600">Category: {{ $memberDetails['member_category'] }}</p>
                                            <p class="text-sm text-gray-600">Status: <span class="text-xs px-3 py-1 rounded-full {{ $memberDetails['status'] === 'ACTIVE' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $memberDetails['status'] }}</span></p>
                                        </div>
                                        <div>
                                            <p class="text-sm text-gray-600">Phone: {{ $memberDetails['phone_number'] }}</p>
                                            <p class="text-sm text-gray-600">Email: {{ $memberDetails['email'] }}</p>
                                            <p class="text-sm text-gray-600">Join Date: {{ $memberDetails['join_date'] }}</p>
                                            <p class="text-sm text-gray-600">Current Shares: {{ number_format($memberDetails['current_shares']) }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 pt-3 border-t border-gray-200">
                                        <p class="text-sm text-gray-600">Address: {{ $memberDetails['address'] }}</p>
                                        <p class="text-sm text-gray-600">Location: {{ $memberDetails['ward'] }}, {{ $memberDetails['district'] }}, {{ $memberDetails['region'] }}</p>
                                        <p class="text-sm text-gray-600">Occupation: {{ $memberDetails['occupation'] }}</p>
                                        <p class="text-sm text-gray-600">Income Source: {{ $memberDetails['income_source'] }}</p>
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label for="product" class="block text-sm font-medium text-gray-700">Share Product</label>
                                <div class="mt-1">
                                    <select wire:model="product" id="product" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="">Select Product</option>
                                        @foreach($availableProducts as $product)
                                            <option value="{{ $product->id }}">{{ $product->product_name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                @error('product') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 sm:mt-4 sm:flex sm:flex-row-reverse flex gap-4">
                    <button type="button" wire:click="addSharesAccount" class="inline-flex w-full justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:col-start-2 sm:text-sm">
                        Create Account
                    </button>
                    <button type="button" wire:click="closeModal" class="mt-3 inline-flex w-full justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:col-start-1 sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="fixed bottom-0 right-0 m-6">
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('message') }}</span>
            </div>
        </div>
    @endif

    @if (session()->has('message_fail'))
        <div class="fixed bottom-0 right-0 m-6">
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('message_fail') }}</span>
            </div>
        </div>
    @endif

    {{-- Share Withdrawal Modal --}}
    @if($showShareWithdrawal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4">
            <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="closeShareWithdrawalModal"></div>

            <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full z-50 p-6 relative">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold text-gray-800">Share Withdrawal Request</h2>
                    <button wire:click="closeShareWithdrawalModal" class="text-gray-500 hover:text-gray-700">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <form wire:submit.prevent="processShareWithdrawal" class="space-y-5">
                    {{-- Client Number Input --}}
                    <div>
                        <label class="block font-semibold text-sm text-gray-700 mb-1">Member Number</label>
                        <div class="relative">
                            <input type="text"
                                wire:model.live.debounce.300ms="client_number"
                                wire:keyup="validateMemberNumber2"
                                maxlength="5"
                                pattern="[0-9]*"
                                inputmode="numeric"
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                placeholder="Enter 5-digit client number"
                                class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm {{ $errors->has('client_number') ? 'border-red-500' : '' }}">
                            <div wire:loading wire:target="client_number" class="absolute right-3 top-2">
                                <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </div>
                        </div>
                        @error('client_number') 
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> 
                        @enderror
                        @if(strlen($client_number) === 5 && !$memberDetails)
                            <span class="text-yellow-500 text-xs mt-1 block">Searching...</span>
                        @endif
                    </div>

                    {{-- Member Information Section --}}
                    @if($memberDetails)
                    <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                        <div class="flex justify-between items-center mb-2">
                            <div>
                                <h4 class="text-gray-700 font-semibold">Member: {{ $memberDetails['first_name'] }} {{ $memberDetails['last_name'] }}</h4>
                                <p class="text-sm text-gray-600">Client #: {{ $memberDetails['client_number'] }}</p>
                            </div>
                            <span class="text-xs px-3 py-1 rounded-full {{ isset($memberDetails['status']) && $memberDetails['status'] === 'ACTIVE' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ isset($memberDetails['status']) ? ucfirst($memberDetails['status']) : 'N/A' }}
                            </span>
                        </div>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <p class="text-gray-600">Phone:</p>
                                <p class="font-medium">{{ $memberDetails['phone_number'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-gray-600">Email:</p>
                                <p class="font-medium">{{ $memberDetails['email'] ?? 'N/A' }}</p>
                            </div>
                            {{--<div>
                                <p class="text-gray-600">Current Shares:</p>
                                <p class="font-medium">{{ DB::table('share_registers')->where('member_id', $memberDetails['client_number'])->value('current_share_balance') }}</p>
                            </div>--}}
                            <div>
                                <p class="text-gray-600">Joined:</p>
                                <p class="font-medium">{{ isset($memberDetails['created_at']) ? \Carbon\Carbon::parse($memberDetails['created_at'])->format('M d, Y') : 'N/A' }}</p>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Source Account Selection --}}
                    <div class="mb-4">
                        <label class="block font-semibold text-sm text-gray-700 mb-1">Select Share Products</label>
                        <div class="space-y-2">                           
                            @foreach($sourceAccounts as $account)
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex-1">
                                        <h4 class="font-medium">{{ $account['product_name'] }}</h4>
                                        <p class="text-sm text-gray-600">
                                            Current Balance: {{ number_format($account['current_share_balance']) }} shares
                                            (TZS {{ number_format($account['total_share_value'], 2) }})
                                        </p>
                                    </div>
                                    @if(!isset($selectedShareProducts[$account['id']]))
                                        <button wire:click="addShareProduct({{ $account['id'] }})"
                                                class="px-3 py-1 bg-blue-900 text-white rounded hover:bg-blue-700">
                                            Add
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>

                    {{-- Selected Share Products for Withdrawal --}}
                    @if(count($selectedShareProducts) > 0)
                        <div class="mb-4">
                            <h3 class="font-semibold text-gray-700 mb-2">Selected Share Products</h3>
                            <div class="space-y-3">
                                @foreach($selectedShareProducts as $productId => $product)
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <div class="flex justify-between items-start mb-3">
                                            <div>
                                                <h4 class="font-medium">{{ $product['product_name'] }}</h4>
                                                <p class="text-sm text-gray-600">
                                                    Available: {{ number_format($product['current_balance']) }} shares
                                                </p>
                                            </div>
                                            <button wire:click="removeShareProduct({{ $productId }})"
                                                    class="text-red-600 hover:text-red-700">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                        <div>
                                            <label class="block text-sm text-gray-600 mb-1">Withdrawal Amount</label>
                                            <input type="number" 
                                                   wire:model="selectedShareProducts.{{ $productId }}.withdrawal_amount"
                                                   class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                                   min="1"
                                                   max="{{ $product['current_balance'] }}">
                                            @error("selectedShareProducts.{$productId}.withdrawal_amount")
                                                <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span>
                                            @enderror
                                        </div>
                                        <div class="mt-2 text-sm">
                                            <p class="text-gray-600">
                                                Value: TZS {{ number_format($product['total_value'], 2) }}
                                            </p>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Withdrawal Summary --}}
                        <div class="mb-4 p-4 bg-blue-50 rounded-lg">
                            <h3 class="font-semibold text-gray-700 mb-2">Withdrawal Summary</h3>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-gray-600">Total Shares</p>
                                    <p class="font-medium">{{ number_format($totalWithdrawalShares) }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Total Value</p>
                                    <p class="font-medium">TZS {{ number_format($totalWithdrawalValue, 2) }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    {{-- Receiving Account Selection --}}
                    <div>
                        <label class="block font-semibold text-sm text-gray-700 mb-1">Select Receiving Account</label>
                        <select wire:model.defer="selectedReceivingAccount"
                                class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                            <option value="">-- Select Account --</option>
                            @foreach($receivingAccounts as $account)
                                <option value="{{ $account['account_number'] }}">
                                    {{ $account['account_name'] }} - {{ $account['account_number'] }} 
                                    (TZS {{ number_format($account['balance'], 2) }})
                                </option>
                            @endforeach
                        </select>
                        @error('selectedReceivingAccount') 
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> 
                        @enderror
                    </div>

                    {{-- Reason Input --}}
                    <div>
                        <label class="block font-semibold text-sm text-gray-700 mb-1">Reason for Withdrawal</label>
                        <textarea wire:model.defer="withdrawalReason"
                                rows="3"
                                class="w-full border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500 shadow-sm"
                                placeholder="Explain why the member is withdrawing"></textarea>
                        @error('withdrawalReason') 
                            <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> 
                        @enderror
                    </div>


                    {{-- Buttons --}}
                    <div class="flex justify-end gap-3 pt-4">
                        <button type="button"
                                wire:click="closeShareWithdrawalModal"
                                class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-md shadow-sm">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-blue-900 hover:bg-blue-700 text-white rounded-md shadow-sm">
                            Submit Withdrawal
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- Share Transfer Modal --}}
    @if($showShareTransferModalx)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-gray-100 rounded-lg text-left overflow-hidden shadow-xl transform transition-all w-3/4">
                    {{-- Modal Header --}}
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900">
                                Transfer Shares
                            </h2>
                            <button wire:click="closeShareTransferModal" class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Content --}}
                    <div class="bg-gray-100 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[70vh] overflow-y-auto custom-scrollbar">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            {{-- Left Column: Sender Information --}}
                            <div class="space-y-6">
                                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                                    <div class="flex items-center space-x-2 mb-4">
                                        <div class="p-2 bg-blue-100 rounded-lg">
                                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900">Sender Information</h3>
                                    </div>
                                    <div>
                                        <label for="sender_client_number" class="block text-sm font-medium text-gray-700 mb-2">Client Number</label>
                                        <div class="relative">
                                            <input type="text" wire:model="sender_client_number" id="sender_client_number" class="shadow-sm focus:ring-blue-500 focus:border-blue-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Enter sender's client number">
                                            <div wire:loading wire:target="sender_client_number" class="absolute right-3 top-2">
                                                <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        @error('sender_client_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Sender Details Card --}}
                                    @if($senderMemberDetails)
                                    <div class="mt-4 p-4 bg-blue-50 rounded-lg border border-blue-200">
                                        <div class="grid grid-cols-1 gap-3">
                                            <div class="flex justify-between">
                                                <span class="text-sm font-medium text-gray-600">Name:</span>
                                                <span class="text-sm font-semibold text-gray-900">{{ $senderMemberDetails->first_name }} {{ $senderMemberDetails->last_name }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm font-medium text-gray-600">Status:</span>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $senderMemberDetails->status === 'ACTIVE' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $senderMemberDetails->status }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm font-medium text-gray-600">Membership Date:</span>
                                                <span class="text-sm text-gray-900">
                                                    @if($senderMemberDetails->registration_date)
                                                        {{ \Carbon\Carbon::parse($senderMemberDetails->registration_date)->format('d M Y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                {{-- Sender Share Type Selection --}}
                                @if($senderMemberDetails)
                                <div class="bg-white p-4 rounded-lg shadow">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Sender Share Product</h3>
                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <label for="sender_share_type" class="block text-sm font-medium text-gray-700">Share Type</label>
                                            <select wire:model="sender_share_type" id="sender_share_type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                                <option value="">Select a share type</option>
                                                @foreach(DB::table('share_registers')->where('member_number', $senderMemberDetails->client_number)->get() as $type)
                                                    <option value="{{ $type->id }}" class="py-2">
                                                        {{ $type->product_name }} 
                                                        <span class="text-sm text-gray-500">
                                                            - No. of Shares: {{ number_format($type->current_share_balance) }}
                                                        </span>                                                
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('sender_share_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                                            {{-- Share Type Details --}}
                                            @if($sender_share_type)
                                                @php
                                                    $selectedType = DB::table('share_registers')->where('id', $sender_share_type)->first();
                                                @endphp
                                                @if($selectedType)
                                                    <div class="mt-4 space-y-3 text-sm">
                                                        <div class="grid grid-cols-2 gap-4">
                                                            <div>
                                                                <p class="text-gray-600">Account Number:</p>
                                                                <p class="font-medium">{{ $selectedType->share_account_number }}</p>
                                                            </div>
                                                            <div>
                                                                <p class="text-gray-600">Share Type:</p>
                                                                <p class="font-medium">{{ strtoupper($selectedType->product_name) }}</p>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="grid grid-cols-2 gap-4">
                                                            <div>
                                                                <p class="text-gray-600">Current Balance:</p>
                                                                <p class="font-medium">{{ number_format($selectedType->current_share_balance) }} shares</p>
                                                            </div>
                                                            <div>
                                                                <p class="text-gray-600">Total Value:</p>
                                                                <p class="font-medium">TZS {{ number_format($selectedType->total_share_value, 2) }}</p>
                                                            </div>
                                                        </div>

                                                        <div class="grid grid-cols-2 gap-4">
                                                            <div>
                                                                <p class="text-gray-600">Last Activity:</p>
                                                                <p class="font-medium">{{ $selectedType->last_transaction_date ? \Carbon\Carbon::parse($selectedType->last_transaction_date)->format('d M Y') : 'N/A' }}</p>
                                                            </div>
                                                            <div>
                                                                <p class="text-gray-600">Opening Date:</p>
                                                                <p class="font-medium">{{ $selectedType->opening_date ? \Carbon\Carbon::parse($selectedType->opening_date)->format('d M Y') : 'N/A' }}</p>
                                                            </div>
                                                        </div>

                                                        @if($selectedType->is_restricted)
                                                            <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded">
                                                                <p class="text-yellow-700">
                                                                    <span class="font-medium">Note:</span> 
                                                                    {{ $selectedType->restriction_notes ?? 'This share type has transfer restrictions.' }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif
                            </div>

                            {{-- Right Column: Receiver Information --}}
                            <div class="space-y-6">
                                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                                    <div class="flex items-center space-x-2 mb-4">
                                        <div class="p-2 bg-green-100 rounded-lg">
                                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900">Receiver Information</h3>
                                    </div>
                                    <div>
                                        <label for="receiver_client_number" class="block text-sm font-medium text-gray-700 mb-2">Client Number</label>
                                        <div class="relative">
                                            <input type="text" wire:model="receiver_client_number" id="receiver_client_number" class="shadow-sm focus:ring-green-500 focus:border-green-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Enter receiver's client number">
                                            <div wire:loading wire:target="receiver_client_number" class="absolute right-3 top-2">
                                                <svg class="animate-spin h-5 w-5 text-gray-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                        </div>
                                        @error('receiver_client_number') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Receiver Details Card --}}
                                    @if($receiverMemberDetails)
                                    <div class="mt-4 p-4 bg-green-50 rounded-lg border border-green-200">
                                        <div class="grid grid-cols-1 gap-3">
                                            <div class="flex justify-between">
                                                <span class="text-sm font-medium text-gray-600">Name:</span>
                                                <span class="text-sm font-semibold text-gray-900">{{ $receiverMemberDetails->first_name }} {{ $receiverMemberDetails->last_name }}</span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm font-medium text-gray-600">Status:</span>
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $receiverMemberDetails->status === 'ACTIVE' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                    {{ $receiverMemberDetails->status }}
                                                </span>
                                            </div>
                                            <div class="flex justify-between">
                                                <span class="text-sm font-medium text-gray-600">Membership Date:</span>
                                                <span class="text-sm text-gray-900">
                                                    @if($receiverMemberDetails->membership_date)
                                                        {{ \Carbon\Carbon::parse($receiverMemberDetails->membership_date)->format('d M Y') }}
                                                    @else
                                                        N/A
                                                    @endif
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>

                                {{-- Receiver Share Type Selection --}}
                                @if($receiverMemberDetails)
                                <div class="bg-white p-4 rounded-lg shadow">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Receiver Share Product</h3>
                                    <div class="grid grid-cols-1 gap-4">
                                        <div>
                                            <label for="receiver_share_type" class="block text-sm font-medium text-gray-700">Share Account</label>
                                            <select wire:model="receiver_share_type" id="receiver_share_type" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                                <option value="">Select a share type</option>
                                                @foreach(DB::table('share_registers')->where('member_number', $receiverMemberDetails->client_number)->get() as $product)
                                                    <option value="{{ $product->id }}" class="py-2">
                                                        {{ $product->product_name }} 
                                                        <span class="text-sm text-gray-500">
                                                            ({{ DB::table('accounts')->where('account_number', $product->share_account_number)->first()->account_name }})
                                                        </span>
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error('receiver_share_type') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                                            {{-- Share Type Details --}}
                                            @if($receiver_share_type)
                                                @php
                                                $selectedReceiverShareType = DB::table('share_registers')->where('id', $receiver_share_type)->first();
                                                @endphp
                                                @if($selectedReceiverShareType)
                                                    <div class="mt-4 space-y-3 text-sm">
                                                        <div class="grid grid-cols-2 gap-4">
                                                            <div>
                                                                <p class="text-gray-600">Account Number:</p>
                                                                <p class="font-medium">{{ $selectedReceiverShareType->share_account_number }}</p>
                                                            </div>
                                                            <div>
                                                                <p class="text-gray-600">Share Type:</p>
                                                                <p class="font-medium">{{ strtoupper($selectedReceiverShareType->product_name) }}</p>
                                                            </div>
                                                        </div>
                                                        
                                                        <div class="grid grid-cols-2 gap-4">
                                                            <div>
                                                                <p class="text-gray-600">Current Balance:</p>
                                                                <p class="font-medium">{{ number_format($selectedReceiverShareType->current_share_balance) }} shares</p>
                                                            </div>
                                                            <div>
                                                                <p class="text-gray-600">Total Value:</p>
                                                                <p class="font-medium">TZS {{ number_format($selectedReceiverShareType->total_share_value, 2) }}</p>
                                                            </div>
                                                        </div>

                                                        <div class="grid grid-cols-2 gap-4">
                                                            <div>
                                                                <p class="text-gray-600">Last Activity:</p>
                                                                <p class="font-medium">{{ $selectedReceiverShareType->last_transaction_date ? \Carbon\Carbon::parse($selectedReceiverShareType->last_transaction_date)->format('d M Y') : 'N/A' }}</p>
                                                            </div>
                                                            <div>
                                                                <p class="text-gray-600">Opening Date:</p>
                                                                <p class="font-medium">{{ $selectedReceiverShareType->opening_date ? \Carbon\Carbon::parse($selectedReceiverShareType->opening_date)->format('d M Y') : 'N/A' }}</p>
                                                            </div>
                                                        </div>

                                                        @if($selectedReceiverShareType->is_restricted == 1)
                                                            <div class="mt-2 p-2 bg-yellow-50 border border-yellow-200 rounded">
                                                                <p class="text-yellow-700">
                                                                    <span class="font-medium">Note:</span> 
                                                                    {{ $selectedReceiverShareType->restriction_notes ?? 'This share type has transfer restrictions.' }}
                                                                </p>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                @endif

                                {{-- Transfer Details --}}
                                @if($selectedReceiverShareType)
                                <div class="bg-white p-4 rounded-lg shadow">
                                    <h3 class="text-lg font-medium text-gray-900 mb-4">Transfer Details</h3>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label for="transfer_shares" class="block text-sm font-medium text-gray-700">Number of Shares</label>
                                            <div class="mt-1">
                                                <input type="number" wire:model.defer="transfer_shares" id="transfer_shares" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Enter number of shares">
                                            </div>
                                            @error('transfer_shares') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                        <div>
                                            <label for="transfer_reason" class="block text-sm font-medium text-gray-700">Reason for Transfer</label>
                                            <div class="mt-1">
                                                <textarea wire:model.defer="transfer_reason" id="transfer_reason" rows="3" class="shadow-sm focus:ring-indigo-500 focus:border-indigo-500 block w-full sm:text-sm border-gray-300 rounded-md" placeholder="Enter reason for transfer"></textarea>
                                            </div>
                                            @error('transfer_reason') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    {{-- Share Value Calculation --}}
                                    @if($transfer_shares > 0)
                                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <span class="text-sm font-medium text-gray-500">Shares to Transfer:</span>
                                                <p class="text-sm text-gray-900">{{ number_format($transfer_shares) }}</p>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-gray-500">Price per Share:</span>
                                                <p class="text-sm text-gray-900">
                                                    TZS {{ number_format($selectedReceiverShareType->nominal_price, 2) }}
                                                </p>
                                            </div>
                                            <div>
                                                <span class="text-sm font-medium text-gray-500">Total Value:</span>
                                                <p class="text-sm text-gray-900">
                                                    TZS {{ number_format($transfer_shares * $selectedReceiverShareType->nominal_price, 2) }}
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button wire:click="processShareTransfer" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                            <span wire:loading wire:target="processShareTransfer" class="mr-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                            Transfer Shares
                        </button>
                        <button wire:click="closeShareTransferModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif


 

    {{-- Update the Share Transfers Report Modal --}}

    @if($showShareTransfersReport)           
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all w-4/5">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Share Transfers Report</h3>
                                <button type="button" wire:click="closeShareTransfersReport" class="text-gray-400 hover:text-gray-500">
                                    <span class="sr-only">Close</span>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            @if(session()->has('error'))
                            <div class="bg-red-50 border border-red-200 rounded-md p-4 mb-4">
                                <div class="flex">
                                    <div class="flex-shrink-0">
                                        <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                </svg>
                                </div>
                                    <div class="ml-3">
                                        <p class="text-sm text-red-700">
                                            {{ session('error') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            @endif

                            <!-- Filters -->
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Date Range</label>
                                    <select wire:model="transfer_date_range" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="today">Today</option>
                                        <option value="yesterday">Yesterday</option>
                                        <option value="this_week">This Week</option>
                                        <option value="last_week">Last Week</option>
                                        <option value="this_month">This Month</option>
                                        <option value="last_month">Last Month</option>
                                        <option value="custom">Custom Range</option>
                                    </select>
                                </div>
                                @if($transfer_date_range === 'custom')
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Start Date</label>
                                    <input type="date" wire:model="transfer_start_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">End Date</label>
                                    <input type="date" wire:model="transfer_end_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                </div>
                                @endif
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Status</label>
                                    <select wire:model="transfer_status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md">
                                        <option value="">All Status</option>
                                        <option value="PENDING">Pending</option>
                                        <option value="COMPLETED">Completed</option>
                                        <option value="REJECTED">Rejected</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Report Table -->
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sender</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Receiver</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Shares</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @forelse($shareTransfers as $transfer)
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $transfer->created_at->format('Y-m-d H:i') }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $transfer->transaction_reference }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $transfer->sender_member_name }}</div>
                                                <div class="text-sm text-gray-500">{{ $transfer->sender_client_number }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm text-gray-900">{{ $transfer->receiver_member_name }}</div>
                                                <div class="text-sm text-gray-500">{{ $transfer->receiver_client_number }}</div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($transfer->number_of_shares) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ number_format($transfer->total_value, 2) }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $transfer->status === 'COMPLETED' ? 'bg-green-100 text-green-800' : 
                                                       ($transfer->status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' : 
                                                       'bg-red-100 text-red-800') }}">
                                                    {{ $transfer->status }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                                <button class="text-indigo-600 hover:text-indigo-900 mr-2" wire:click="viewTransferDetails({{ $transfer->id }})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                @if($transfer->status === 'PENDING')
                                                <button class="text-green-600 hover:text-green-900 mr-2" wire:click="approveTransfer({{ $transfer->id }})">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="text-red-600 hover:text-red-900" wire:click="rejectTransfer({{ $transfer->id }})">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr>
                                            <td colspan="8" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">No transfers found</td>
                                        </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <div class="d-flex justify-content-end mt-3">
                                {!! $this->shareTransfersLinks !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" wire:click="exportTransfersReport" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-download mr-2"></i> Export Report
                    </button>
                    <button type="button" wire:click="closeShareTransfersReport" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif



    {{-- Update the Transfer Details Modal --}}
    @if($showTransferDetails && $selectedTransfer)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Background overlay -->
            <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true"></div>

            <!-- Modal panel -->
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                            <div class="flex justify-between items-center mb-4">
                                <h3 class="text-lg leading-6 font-medium text-gray-900">Transfer Details</h3>
                                <button type="button" wire:click="closeTransferDetails" class="text-gray-400 hover:text-gray-500">
                                    <span class="sr-only">Close</span>
                                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>

                            <div class="mt-4 space-y-4">
                                <!-- Transaction Reference -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Transaction Reference</h4>
                                    <p class="mt-1 text-sm text-gray-900">{{ $selectedTransfer->transaction_reference }}</p>
                                </div>

                                <!-- Sender Details -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Sender Details</h4>
                                    <div class="mt-1 bg-gray-50 p-3 rounded-md">
                                        <p class="text-sm text-gray-900 font-medium">{{ $selectedTransfer->sender_member_name }}</p>
                                        <p class="text-sm text-gray-500">Client Number: {{ $selectedTransfer->sender_client_number }}</p>
                                        <p class="text-sm text-gray-500">Share Type: {{ $selectedTransfer->sender_share_type }}</p>
                                    </div>
                                </div>

                                <!-- Receiver Details -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Receiver Details</h4>
                                    <div class="mt-1 bg-gray-50 p-3 rounded-md">
                                        <p class="text-sm text-gray-900 font-medium">{{ $selectedTransfer->receiver_member_name }}</p>
                                        <p class="text-sm text-gray-500">Client Number: {{ $selectedTransfer->receiver_client_number }}</p>
                                        <p class="text-sm text-gray-500">Share Type: {{ $selectedTransfer->receiver_share_type }}</p>
                                    </div>
                                </div>

                                <!-- Transfer Details -->
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Transfer Details</h4>
                                    <div class="mt-1 bg-gray-50 p-3 rounded-md">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-sm text-gray-500">Number of Shares</p>
                                                <p class="text-sm text-gray-900 font-medium">{{ number_format($selectedTransfer->number_of_shares) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Total Value</p>
                                                <p class="text-sm text-gray-900 font-medium">{{ number_format($selectedTransfer->total_value, 2) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Transfer Date</p>
                                                <p class="text-sm text-gray-900 font-medium">{{ $selectedTransfer->created_at->format('Y-m-d H:i') }}</p>
                                            </div>
                                            <div>
                                                <p class="text-sm text-gray-500">Status</p>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    {{ $selectedTransfer->status === 'COMPLETED' ? 'bg-green-100 text-green-800' : 
                                                       ($selectedTransfer->status === 'PENDING' ? 'bg-yellow-100 text-yellow-800' : 
                                                       'bg-red-100 text-red-800') }}">
                                                    {{ $selectedTransfer->status }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transfer Reason -->
                                @if($selectedTransfer->transfer_reason)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Transfer Reason</h4>
                                    <p class="mt-1 text-sm text-gray-900">{{ $selectedTransfer->transfer_reason }}</p>
                                </div>
                                @endif

                                <!-- Rejection Reason -->
                                @if($selectedTransfer->rejection_reason)
                                <div>
                                    <h4 class="text-sm font-medium text-gray-500">Rejection Reason</h4>
                                    <p class="mt-1 text-sm text-red-600">{{ $selectedTransfer->rejection_reason }}</p>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    @if($selectedTransfer->status === 'PENDING')
                    <button type="button" wire:click="approveTransfer({{ $selectedTransfer->id }})" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-check mr-2"></i> Approve Transfer
                    </button>
                    <button type="button" wire:click="rejectTransfer({{ $selectedTransfer->id }})" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-times mr-2"></i> Reject Transfer
                    </button>
                    @endif
                    <button type="button" wire:click="closeTransferDetails" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    @if($showViewAccountModal && $this->selectedAccountDetails)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true" wire:click.self="closeViewAccountModal">
    <div class="flex items-center justify-center min-h-screen px-4 py-6 text-center sm:p-0">
        
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true"></div>

        <!-- Modal -->
        <div class="relative bg-white rounded-xl shadow-2xl transform transition-all sm:max-w-4xl w-full text-left overflow-hidden" role="document">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-200 bg-gradient-to-r from-indigo-600 to-indigo-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div class="p-2 bg-white bg-opacity-20 rounded-lg">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-bold text-white" id="modal-title">Share Account Details</h3>
                            <p class="text-indigo-100 text-sm">Account #{{ $this->selectedAccountDetails->share_account_number }}</p>
                        </div>
                    </div>
                    <button wire:click="closeViewAccountModal" class="text-white hover:text-indigo-100 focus:outline-none transition-colors duration-200">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Body -->
            <div class="px-8 py-6 max-h-[70vh] overflow-y-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <!-- Account Info -->
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-gray-900">Account Information</h4>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Account Number</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 font-mono">{{ $this->selectedAccountDetails->share_account_number }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Share Type</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">{{ $this->selectedAccountDetails->product_name }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Status</span>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                        {{ $this->selectedAccountDetails->status === 'approved' ? 'bg-green-100 text-green-800 border border-green-200' : 
                                           ($this->selectedAccountDetails->status === 'pending' ? 'bg-yellow-100 text-yellow-800 border border-yellow-200' : 
                                           'bg-red-100 text-red-800 border border-red-200') }}">
                                        {{ ucfirst($this->selectedAccountDetails->status) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between py-2">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Opening Date</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">{{ $this->selectedAccountDetails->opening_date ? \Carbon\Carbon::parse($this->selectedAccountDetails->opening_date)->format('M d, Y') : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Member Info -->
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-green-50 to-emerald-50 border-b border-gray-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-gray-900">Member Information</h4>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Member Name</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">{{ $this->selectedAccountDetails->first_name . ' ' . $this->selectedAccountDetails->last_name }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Member Number</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900 font-mono">{{ $this->selectedAccountDetails->member_number }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Email</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">{{ $this->selectedAccountDetails->email ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Phone</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">{{ $this->selectedAccountDetails->phone_number ?? 'N/A' }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Member Status</span>
                                    </div>
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full 
                                        {{ $this->selectedAccountDetails->member_status === 'ACTIVE' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                        {{ ucfirst($this->selectedAccountDetails->member_status) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <!-- Share Details -->
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-purple-50 to-pink-50 border-b border-gray-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-gray-900">Share Details</h4>
                                </div>
                            </div>
                            <div class="p-6 space-y-4">
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Current Balance</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">{{ number_format($this->selectedAccountDetails->current_share_balance) }} shares</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Total Value</span>
                                    </div>
                                    <span class="text-sm font-semibold text-green-600">TZS {{ number_format($this->selectedAccountDetails->total_share_value, 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2 border-b border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Price per Share</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">TZS {{ number_format($this->selectedAccountDetails->current_price, 2) }}</span>
                                </div>
                                <div class="flex items-center justify-between py-2">
                                    <div class="flex items-center space-x-3">
                                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-gray-600">Last Transaction</span>
                                    </div>
                                    <span class="text-sm font-semibold text-gray-900">{{ $this->selectedAccountDetails->last_transaction_date ? \Carbon\Carbon::parse($this->selectedAccountDetails->last_transaction_date)->format('M d, Y') : 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Transactions -->
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm overflow-hidden">
                            <div class="px-6 py-4 bg-gradient-to-r from-orange-50 to-amber-50 border-b border-gray-200">
                                <div class="flex items-center space-x-2">
                                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <h4 class="text-lg font-semibold text-gray-900">Recent Transactions</h4>
                                </div>
                            </div>
                            <div class="p-6">
                                @php
                                    $transactions = DB::table('issued_shares')
                                        ->where('account_number', $this->selectedAccountDetails->share_account_number)
                                        ->orderBy('created_at', 'desc')
                                        ->limit(5)
                                        ->get();
                                @endphp

                                @if($transactions->count())
                                    <div class="space-y-3">
                                        @foreach($transactions as $transaction)
                                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                                <div class="flex items-center space-x-3">
                                                    <div class="p-2 bg-blue-100 rounded-lg">
                                                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                                        </svg>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-medium text-gray-900">{{ $transaction->transaction_type ?? 'Share Transaction' }}</p>
                                                        <p class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($transaction->created_at)->format('M d, Y H:i') }}</p>
                                                    </div>
                                                </div>
                                                <div class="text-right">
                                                    <p class="text-sm font-semibold text-gray-900">{{ number_format($transaction->number_of_shares) }} shares</p>
                                                    <p class="text-xs text-green-600 font-medium">TZS {{ number_format($transaction->total_value, 2) }}</p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="text-center py-8">
                                        <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                        </svg>
                                        <p class="text-sm text-gray-500">No recent transactions</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 px-8 py-4 border-t border-gray-200 flex justify-between items-center">
                <div class="text-sm text-gray-500">
                    Last updated: {{ now()->format('M d, Y H:i') }}
                </div>
                <div class="flex space-x-3">
                    <button wire:click="closeViewAccountModal" class="inline-flex items-center px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Close
                    </button>
                    {{--<button class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-colors duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Export Details
                    </button> --}}
                </div>
            </div>
        </div>
    </div>
</div>
@endif

        <!-- Modal for block/activate share account -->
    @if($showDeleteSharesAccount)
        <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" aria-hidden="true"></div>
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-gray-100 rounded-lg text-left overflow-hidden shadow-xl transform transition-all w-3/4">
                    {{-- Modal Header --}}
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="flex items-center justify-between">
                            <h2 class="text-lg font-medium text-gray-900">
                                @if($activationMode)
                                    <span class="flex items-center">
                                        <svg class="w-5 h-5 text-green-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Activate Share Account
                                    </span>
                                @else
                                    <span class="flex items-center">
                                        <svg class="w-5 h-5 text-red-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        Block Share Account
                                    </span>
                                @endif
                            </h2>
                            <button wire:click="closeShareAccountModal" class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Close</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Content --}}
                    <div class="bg-gray-100 px-4 pt-5 pb-4 sm:p-6 sm:pb-4 max-h-[70vh] overflow-y-auto custom-scrollbar">
                        <div class="flex flex-col gap-4">
                            {{-- Account Information --}}
                            <div class="space-y-6">
                                @php
                                    $issuedShares = DB::table('issued_shares')->where('id', $SharesAccountSelected)->first();
                                    $selectedAccount = DB::table('share_registers')->where('product_id', $issuedShares->product)
                                    ->where('member_number', $issuedShares->client_number)
                                    ->first();
                                    $memberDetails = null;
                                    if ($selectedAccount) {
                                        $memberDetails = DB::table('clients')->where('client_number', $selectedAccount->member_number)->first();
                                    }
                                @endphp
                                
                                @if($selectedAccount && $memberDetails)
                                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                                    <div class="flex items-center space-x-2 mb-4">
                                        <div class="p-2 {{ $activationMode ? 'bg-green-100' : 'bg-red-100' }} rounded-lg">
                                            <svg class="w-5 h-5 {{ $activationMode ? 'text-green-600' : 'text-red-600' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                            </svg>
                                        </div>
                                        <h3 class="text-lg font-medium text-gray-900">Share Account Information</h3>
                                    </div>
                                    
                                    {{-- Account Details --}}
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Member Information</label>
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Name:</span>
                                                    <span class="text-sm font-semibold text-gray-900">{{ $memberDetails->first_name }} {{ $memberDetails->last_name }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Client Number:</span>
                                                    <span class="text-sm font-semibold text-gray-900">{{ $memberDetails->client_number }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Member Status:</span>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full {{ $memberDetails->status === 'ACTIVE' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        {{ $memberDetails->status }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-2">Share Account Details</label>
                                            <div class="space-y-2">
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Account Number:</span>
                                                    <span class="text-sm font-semibold text-gray-900">{{ $selectedAccount->share_account_number }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Product:</span>
                                                    <span class="text-sm font-semibold text-gray-900">{{ $selectedAccount->product_name }}</span>
                                                </div>
                                                <div class="flex justify-between">
                                                    <span class="text-sm text-gray-600">Current Status:</span>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                                        {{ $selectedAccount->status === 'ACTIVE' ? 'bg-green-100 text-green-800' : 
                                                           ($selectedAccount->status === 'FROZEN' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                                        {{ $selectedAccount->status }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Share Holdings --}}
                                    <div class="mt-4 p-4 bg-gray-50 rounded-lg">
                                        <h4 class="text-sm font-medium text-gray-700 mb-3">Share Holdings</h4>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div class="text-center">
                                                <p class="text-2xl font-bold text-gray-900">{{ number_format($selectedAccount->current_share_balance) }}</p>
                                                <p class="text-sm text-gray-600">Current Shares</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-2xl font-bold text-gray-900">TZS {{ number_format($selectedAccount->total_share_value, 2) }}</p>
                                                <p class="text-sm text-gray-600">Total Value</p>
                                            </div>
                                            <div class="text-center">
                                                <p class="text-2xl font-bold text-gray-900">TZS {{ number_format($selectedAccount->current_price, 2) }}</p>
                                                <p class="text-sm text-gray-600">Price per Share</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    {{-- Warning/Info Message --}}
                                    <div class="mt-4 p-4 {{ $activationMode ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200' }} rounded-lg">
                                        <div class="flex">
                                            <div class="flex-shrink-0">
                                                @if($activationMode)
                                                    <svg class="h-5 w-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                    </svg>
                                                @else
                                                    <svg class="h-5 w-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                                    </svg>
                                                @endif
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-sm font-medium {{ $activationMode ? 'text-green-800' : 'text-red-800' }}">
                                                    @if($activationMode)
                                                        Activate Share Account
                                                    @else
                                                        Block Share Account
                                                    @endif
                                                </h3>
                                                <div class="mt-2 text-sm {{ $activationMode ? 'text-green-700' : 'text-red-700' }}">
                                                    @if($activationMode)
                                                        <p>This will activate the share account and allow all transactions including purchases, transfers, and withdrawals.</p>
                                                    @else
                                                        <p>This will block the share account and prevent all transactions including purchases, transfers, and withdrawals.</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200">
                                    <div class="text-center">
                                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                        </svg>
                                        <h3 class="mt-2 text-sm font-medium text-gray-900">Account Not Found</h3>
                                        <p class="mt-1 text-sm text-gray-500">The selected share account could not be found.</p>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        @if($selectedAccount && $memberDetails)
                        <button 
                            wire:click="{{ $activationMode ? 'activateSharesAccount' : 'blockSharesAccount' }}" 
                            type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 {{ $activationMode ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} text-base font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 {{ $activationMode ? 'focus:ring-green-500' : 'focus:ring-red-500' }} sm:ml-3 sm:w-auto sm:text-sm">
                            <span wire:loading wire:target="{{ $activationMode ? 'activateSharesAccount' : 'blockSharesAccount' }}" class="mr-2">
                                <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                            </span>
                            @if($activationMode)
                                Activate Account
                            @else
                                Block Account
                            @endif
                        </button>
                        @endif
                        <button wire:click="closeShareAccountModal" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancel
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif                               

</div>


