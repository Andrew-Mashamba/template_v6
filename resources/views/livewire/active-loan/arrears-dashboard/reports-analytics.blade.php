{{-- Reports & Analytics Dashboard --}}
<div class="space-y-6">
    <!-- Report Generation Overview -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Total Reports Generated -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Reports Generated</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">1,247</p>
                    <p class="text-sm text-gray-500 mt-1">This month</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-blue-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="flex items-center text-green-500 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                    </svg>
                    <span class="ml-1">+12.5% from last month</span>
                </span>
            </div>
        </div>

        <!-- Most Popular Report -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Most Popular</p>
                    <p class="mt-1 text-2xl font-semibold text-gray-900">Daily Arrears</p>
                    <p class="text-sm text-gray-500 mt-1">Report type</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-sm text-gray-600">
                    456 downloads this month
                </p>
            </div>
        </div>

        <!-- Scheduled Reports -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Scheduled Reports</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">23</p>
                    <p class="text-sm text-gray-500 mt-1">Active schedules</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-purple-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-gray-500">Auto-generated reports</span>
            </div>
        </div>

        <!-- Export Formats -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Export Formats</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">5</p>
                    <p class="text-sm text-gray-500 mt-1">Available formats</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-orange-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <p class="text-sm text-gray-600">
                    PDF, Excel, CSV, JSON, XML
                </p>
            </div>
        </div>
    </div>

    <!-- Report Categories -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Standard Reports -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Standard Reports</h3>
            
            <div class="space-y-4">
                <!-- Daily Arrears Report -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Daily Arrears Report</p>
                            <p class="text-xs text-gray-500">Current arrears status and trends</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-900 text-sm">Generate</button>
                        <button class="text-green-600 hover:text-green-900 text-sm">Schedule</button>
                    </div>
                </div>
                
                <!-- Weekly Collection Report -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Weekly Collection Report</p>
                            <p class="text-xs text-gray-500">Collection performance and metrics</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-900 text-sm">Generate</button>
                        <button class="text-green-600 hover:text-green-900 text-sm">Schedule</button>
                    </div>
                </div>
                
                <!-- Monthly Portfolio Report -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Monthly Portfolio Report</p>
                            <p class="text-xs text-gray-500">Comprehensive portfolio analysis</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-900 text-sm">Generate</button>
                        <button class="text-green-600 hover:text-green-900 text-sm">Schedule</button>
                    </div>
                </div>
                
                <!-- Branch Performance Report -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Branch Performance Report</p>
                            <p class="text-xs text-gray-500">Branch-wise performance metrics</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-900 text-sm">Generate</button>
                        <button class="text-green-600 hover:text-green-900 text-sm">Schedule</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Analytics -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Advanced Analytics</h3>
            
            <div class="space-y-4">
                <!-- Risk Analysis Report -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Risk Analysis Report</p>
                            <p class="text-xs text-gray-500">Portfolio risk assessment</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-900 text-sm">Generate</button>
                        <button class="text-green-600 hover:text-green-900 text-sm">Schedule</button>
                    </div>
                </div>
                
                <!-- Trend Analysis Report -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 8v8m-4-5v5m-4-2v2m-2 4h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Trend Analysis Report</p>
                            <p class="text-xs text-gray-500">Historical trends and patterns</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-900 text-sm">Generate</button>
                        <button class="text-green-600 hover:text-green-900 text-sm">Schedule</button>
                    </div>
                </div>
                
                <!-- Forecasting Report -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-teal-100 rounded-full flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-teal-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Forecasting Report</p>
                            <p class="text-xs text-gray-500">Predictive analytics and forecasts</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-900 text-sm">Generate</button>
                        <button class="text-green-600 hover:text-green-900 text-sm">Schedule</button>
                    </div>
                </div>
                
                <!-- Custom Analytics -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-pink-100 rounded-full flex items-center justify-center mr-3">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-pink-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Custom Analytics</p>
                            <p class="text-xs text-gray-500">Build custom reports</p>
                        </div>
                    </div>
                    <div class="flex space-x-2">
                        <button class="text-blue-600 hover:text-blue-900 text-sm">Create</button>
                        <button class="text-green-600 hover:text-green-900 text-sm">Templates</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Reports -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Recent Reports</h3>
                <div class="flex space-x-2">
                    <select class="text-sm border border-gray-300 rounded-md px-3 py-1">
                        <option>All Reports</option>
                        <option>Daily Reports</option>
                        <option>Weekly Reports</option>
                        <option>Monthly Reports</option>
                        <option>Custom Reports</option>
                    </select>
                    <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">View All</button>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Report Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Generated By</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Format</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Size</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Sample recent reports -->
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Daily Arrears Report - Dec 14</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Daily
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">System Auto</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Dec 14, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">PDF</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2.3 MB</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button class="text-blue-600 hover:text-blue-900">Download</button>
                                <button class="text-green-600 hover:text-green-900">View</button>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Weekly Collection Report - Week 50</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Weekly
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">John Doe</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Dec 13, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Excel</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">1.8 MB</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button class="text-blue-600 hover:text-blue-900">Download</button>
                                <button class="text-green-600 hover:text-green-900">View</button>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Monthly Portfolio Analysis - Nov 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                Monthly
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Sarah Miller</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Dec 1, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">PDF</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">4.2 MB</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button class="text-blue-600 hover:text-blue-900">Download</button>
                                <button class="text-green-600 hover:text-green-900">View</button>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">Branch Performance Comparison</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                Custom
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Mike Johnson</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Nov 28, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Excel</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">3.1 MB</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button class="text-blue-600 hover:text-blue-900">Download</button>
                                <button class="text-green-600 hover:text-green-900">View</button>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-3 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Showing <span class="font-medium">1</span> to <span class="font-medium">4</span> of <span class="font-medium">1,247</span> results
                </div>
                <div class="flex space-x-2">
                    <button class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Previous</button>
                    <button class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Next</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Report Generation Tools -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Quick Report -->
        <button class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
            <div class="text-left">
                <p class="text-sm font-medium text-gray-900">Quick Report</p>
                <p class="text-xs text-gray-500">Generate instantly</p>
            </div>
        </button>
        
        <!-- Scheduled Reports -->
        <button class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <div class="text-left">
                <p class="text-sm font-medium text-gray-900">Scheduled Reports</p>
                <p class="text-xs text-gray-500">Auto-generation</p>
            </div>
        </button>
        
        <!-- Custom Builder -->
        <button class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <div class="text-left">
                <p class="text-sm font-medium text-gray-900">Custom Builder</p>
                <p class="text-xs text-gray-500">Build custom reports</p>
            </div>
        </button>
        
        <!-- Export Options -->
        <button class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            <div class="text-left">
                <p class="text-sm font-medium text-gray-900">Export Options</p>
                <p class="text-xs text-gray-500">Multiple formats</p>
            </div>
        </button>
    </div>

    <!-- Report Analytics -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Report Analytics</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Most Downloaded -->
            <div class="text-center">
                <div class="w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h4 class="font-medium text-gray-900 mb-2">Most Downloaded</h4>
                <p class="text-2xl font-bold text-blue-600 mb-1">456</p>
                <p class="text-sm text-gray-600">Daily Arrears Report</p>
                <p class="text-xs text-gray-500 mt-1">This month</p>
            </div>
            
            <!-- Average Generation Time -->
            <div class="text-center">
                <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <h4 class="font-medium text-gray-900 mb-2">Avg Generation Time</h4>
                <p class="text-2xl font-bold text-green-600 mb-1">2.3s</p>
                <p class="text-sm text-gray-600">Per report</p>
                <p class="text-xs text-gray-500 mt-1">System performance</p>
            </div>
            
            <!-- Storage Used -->
            <div class="text-center">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4" />
                    </svg>
                </div>
                <h4 class="font-medium text-gray-900 mb-2">Storage Used</h4>
                <p class="text-2xl font-bold text-purple-600 mb-1">2.4 GB</p>
                <p class="text-sm text-gray-600">Report storage</p>
                <p class="text-xs text-gray-500 mt-1">Last 30 days</p>
            </div>
        </div>
    </div>
</div>
