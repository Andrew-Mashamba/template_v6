{{-- Collection Management Dashboard --}}
<div class="space-y-6">
    <!-- Collection Performance Metrics -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <!-- Collection Rate -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Collection Rate</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">87.3%</p>
                    <p class="text-sm text-gray-500 mt-1">This month</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-green-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="flex items-center text-green-500 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                    </svg>
                    <span class="ml-1">+2.1% from last month</span>
                </span>
            </div>
        </div>

        <!-- Amount Collected -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Amount Collected</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">TZS 2.4M</p>
                    <p class="text-sm text-gray-500 mt-1">This month</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-blue-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="flex items-center text-green-500 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                    </svg>
                    <span class="ml-1">+15.2% from last month</span>
                </span>
            </div>
        </div>

        <!-- Active Collection Cases -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Active Cases</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">156</p>
                    <p class="text-sm text-gray-500 mt-1">Collection cases</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-orange-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-gray-500">Across all branches</span>
            </div>
        </div>

        <!-- Success Rate -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Success Rate</p>
                    <p class="mt-1 text-3xl font-semibold text-gray-900">73.2%</p>
                    <p class="text-sm text-gray-500 mt-1">Collection success</p>
                </div>
                <div class="h-12 w-12 rounded-full bg-purple-50 flex items-center justify-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="flex items-center text-green-500 text-sm">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M12 7a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0V8.414l-4.293 4.293a1 1 0 01-1.414 0L8 10.414l-4.293 4.293a1 1 0 01-1.414-1.414l5-5a1 1 0 011.414 0L11 10.586 14.586 7H12z" clip-rule="evenodd" />
                    </svg>
                    <span class="ml-1">+5.8% from last month</span>
                </span>
            </div>
        </div>
    </div>

    <!-- Collection Actions and Workflow -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Collection Workflow -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Collection Workflow</h3>
            
            <div class="space-y-4">
                <!-- Step 1: Initial Contact -->
                <div class="flex items-center p-4 bg-blue-50 rounded-lg">
                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center mr-4">
                        <span class="text-white text-sm font-medium">1</span>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Initial Contact</h4>
                        <p class="text-sm text-gray-600">Phone call and SMS reminder</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">24-48 hours</p>
                        <p class="text-xs text-gray-500">After due date</p>
                    </div>
                </div>
                
                <!-- Step 2: Follow-up -->
                <div class="flex items-center p-4 bg-yellow-50 rounded-lg">
                    <div class="w-8 h-8 bg-yellow-500 rounded-full flex items-center justify-center mr-4">
                        <span class="text-white text-sm font-medium">2</span>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Follow-up</h4>
                        <p class="text-sm text-gray-600">Personal visit and negotiation</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">7-14 days</p>
                        <p class="text-xs text-gray-500">After initial contact</p>
                    </div>
                </div>
                
                <!-- Step 3: Escalation -->
                <div class="flex items-center p-4 bg-orange-50 rounded-lg">
                    <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center mr-4">
                        <span class="text-white text-sm font-medium">3</span>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Escalation</h4>
                        <p class="text-sm text-gray-600">Manager intervention and restructuring</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">30-60 days</p>
                        <p class="text-xs text-gray-500">After follow-up</p>
                    </div>
                </div>
                
                <!-- Step 4: Legal Action -->
                <div class="flex items-center p-4 bg-red-50 rounded-lg">
                    <div class="w-8 h-8 bg-red-500 rounded-full flex items-center justify-center mr-4">
                        <span class="text-white text-sm font-medium">4</span>
                    </div>
                    <div class="flex-1">
                        <h4 class="font-medium text-gray-900">Legal Action</h4>
                        <p class="text-sm text-gray-600">Legal proceedings and collateral recovery</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-medium text-gray-900">90+ days</p>
                        <p class="text-xs text-gray-500">After escalation</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Collection Team Performance -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-900 mb-6">Collection Team Performance</h3>
            
            <div class="space-y-4">
                <!-- Team Member 1 -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-sm font-medium text-blue-600">JD</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">John Doe</p>
                            <p class="text-xs text-gray-500">Senior Collector</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">92%</p>
                        <p class="text-xs text-gray-500">Success Rate</p>
                    </div>
                </div>
                
                <!-- Team Member 2 -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-green-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-sm font-medium text-green-600">SM</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Sarah Miller</p>
                            <p class="text-xs text-gray-500">Collection Officer</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">87%</p>
                        <p class="text-xs text-gray-500">Success Rate</p>
                    </div>
                </div>
                
                <!-- Team Member 3 -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-sm font-medium text-purple-600">MJ</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Mike Johnson</p>
                            <p class="text-xs text-gray-500">Collection Officer</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">79%</p>
                        <p class="text-xs text-gray-500">Success Rate</p>
                    </div>
                </div>
                
                <!-- Team Member 4 -->
                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="flex items-center">
                        <div class="w-10 h-10 bg-orange-100 rounded-full flex items-center justify-center mr-3">
                            <span class="text-sm font-medium text-orange-600">LW</span>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Lisa Wilson</p>
                            <p class="text-xs text-gray-500">Junior Collector</p>
                        </div>
                    </div>
                    <div class="text-right">
                        <p class="text-sm font-semibold text-gray-900">73%</p>
                        <p class="text-xs text-gray-500">Success Rate</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Collection Cases -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100">
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-900">Active Collection Cases</h3>
                <div class="flex space-x-2">
                    <select class="text-sm border border-gray-300 rounded-md px-3 py-1">
                        <option>All Cases</option>
                        <option>Initial Contact</option>
                        <option>Follow-up</option>
                        <option>Escalation</option>
                        <option>Legal Action</option>
                    </select>
                    <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">Add New Case</button>
                </div>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Case ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Loan ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Client</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stage</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assigned To</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Next Action</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <!-- Sample collection cases -->
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">CC-001</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">LN-2024-001</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">John Smith</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">TZS 150,000</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                Initial Contact
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">John Doe</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Dec 15, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button class="text-blue-600 hover:text-blue-900">Update</button>
                                <button class="text-green-600 hover:text-green-900">Contact</button>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">CC-002</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">LN-2024-002</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Mary Johnson</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">TZS 275,000</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                Follow-up
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Sarah Miller</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Dec 18, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button class="text-blue-600 hover:text-blue-900">Update</button>
                                <button class="text-green-600 hover:text-green-900">Visit</button>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">CC-003</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">LN-2024-003</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Robert Brown</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">TZS 450,000</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                Escalation
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Mike Johnson</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Dec 20, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button class="text-blue-600 hover:text-blue-900">Update</button>
                                <button class="text-purple-600 hover:text-purple-900">Restructure</button>
                            </div>
                        </td>
                    </tr>
                    
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">CC-004</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">LN-2024-004</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Emily Davis</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">TZS 750,000</td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Legal Action
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Lisa Wilson</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Dec 22, 2024</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                            <div class="flex space-x-2">
                                <button class="text-blue-600 hover:text-blue-900">Update</button>
                                <button class="text-red-600 hover:text-red-900">Legal</button>
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
                    Showing <span class="font-medium">1</span> to <span class="font-medium">4</span> of <span class="font-medium">156</span> results
                </div>
                <div class="flex space-x-2">
                    <button class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Previous</button>
                    <button class="px-3 py-1 text-sm border border-gray-300 rounded-md hover:bg-gray-50">Next</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Collection Tools and Actions -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Bulk SMS -->
        <button class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-blue-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
            </svg>
            <div class="text-left">
                <p class="text-sm font-medium text-gray-900">Bulk SMS</p>
                <p class="text-xs text-gray-500">Send reminders</p>
            </div>
        </button>
        
        <!-- Phone Calls -->
        <button class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
            </svg>
            <div class="text-left">
                <p class="text-sm font-medium text-gray-900">Phone Calls</p>
                <p class="text-xs text-gray-500">Make calls</p>
            </div>
        </button>
        
        <!-- Field Visits -->
        <button class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-purple-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
            <div class="text-left">
                <p class="text-sm font-medium text-gray-900">Field Visits</p>
                <p class="text-xs text-gray-500">Schedule visits</p>
            </div>
        </button>
        
        <!-- Legal Actions -->
        <button class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600 mr-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
            <div class="text-left">
                <p class="text-sm font-medium text-gray-900">Legal Actions</p>
                <p class="text-xs text-gray-500">Initiate proceedings</p>
            </div>
        </button>
    </div>

    <!-- Collection Reports -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Collection Reports</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <button class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-blue-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-sm font-medium text-gray-700">Daily Collection Report</span>
            </button>
            
            <button class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-green-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-sm font-medium text-gray-700">Weekly Performance</span>
            </button>
            
            <button class="flex items-center justify-center p-4 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-purple-600 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span class="text-sm font-medium text-gray-700">Monthly Summary</span>
            </button>
        </div>
    </div>
</div>
