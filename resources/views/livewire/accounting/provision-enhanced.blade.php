<div>
    <!-- New System Alert -->
    @if($showNewSystem)
    <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6">
        <div class="flex">
            <div class="py-1">
                <svg class="fill-current h-6 w-6 text-blue-500 mr-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                    <path d="M2.93 17.07A10 10 0 1 1 17.07 2.93 10 10 0 0 1 2.93 17.07zm12.73-1.41A8 8 0 1 0 4.34 4.34a8 8 0 0 0 11.32 11.32zM9 11V9h2v6H9v-4zm0-6h2v2H9V5z"/>
                </svg>
            </div>
            <div>
                <p class="font-bold">New IFRS 9 ECL Provision System Available!</p>
                <p class="text-sm">We've implemented a comprehensive loan provision management system based on IFRS 9 Expected Credit Loss model with:</p>
                <ul class="text-sm mt-2 ml-4">
                    <li>• 3-Stage ECL Model (IFRS 9 compliant)</li>
                    <li>• Forward-looking provisions with economic scenarios</li>
                    <li>• Automated GL posting and journal entries</li>
                    <li>• Comprehensive analytics and reporting</li>
                </ul>
                <div class="mt-3">
                    <button onclick="window.location.href='/accounting/provision-management'" 
                            class="bg-blue-500 text-white font-bold py-2 px-4 rounded hover:bg-blue-700 transition">
                        Access New System →
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Legacy System (Current Implementation) -->
    <div class="bg-white p-4 mb-4">
        <div class="container mx-auto mt-8">
            <div class="flex justify-between items-center mb-4">
                <h4 class="text-xl font-bold p-4">LOAN PROVISION - {{ now()->format("Y-M-d") }}, NBC SACCOS LTD</h4>
                <div class="flex gap-2">
                    <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded">Legacy System</span>
                    <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Traditional Method</span>
                </div>
            </div>
            
            <div class="overflow-x-auto mt-4">
                <table class="min-w-full bg-white border border-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left bg-blue-100 border border-gray-200">S/N</th>
                            <th class="px-4 py-2 text-left bg-blue-100 border border-gray-200">Name</th>
                            <th class="px-4 py-2 text-left bg-blue-100 border border-gray-200">Loan Amount</th>
                            <th class="px-4 py-2 text-right bg-blue-100 border border-gray-200">Last Payment</th>
                            <th class="px-4 py-2 text-right bg-blue-100 border border-gray-200">PROVISION (%)</th>
                            <th class="px-4 py-2 text-right bg-blue-100 border border-gray-200">Outstanding</th>
                            <th class="px-4 py-2 text-right bg-blue-100 border border-gray-200">Amount Provided</th>
                            <th class="px-4 py-2 text-right bg-blue-100 border border-gray-200">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($loan as $data)
                        <tr class="{{ $loop->even ? 'bg-gray-50' : 'bg-white' }}">
                            <td class="px-4 py-2 border-blue-200 border">{{ $loop->iteration }}</td>
                            <td class="px-4 py-2 border-blue-200 border">{{ $data->name }}</td>
                            <td class="px-4 py-2 border-blue-200 border">{{ number_format($data->loan_amount, 2) }} TZS</td>
                            <td class="px-4 py-2 border-blue-200 border text-right">{{ $data->date }}</td>
                            <td class="px-4 py-2 border-blue-200 border text-right">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    {{ $data->provision_rate <= 5 ? 'bg-green-100 text-green-800' : 
                                       ($data->provision_rate <= 25 ? 'bg-yellow-100 text-yellow-800' : 
                                       ($data->provision_rate <= 50 ? 'bg-orange-100 text-orange-800' : 'bg-red-100 text-red-800')) }}">
                                    {{ $data->provision_rate ?? 0 }}%
                                </span>
                            </td>
                            <td class="px-4 py-2 border-blue-200 border text-right">{{ number_format($data->out_standing_amount, 2) }} TZS</td>
                            <td class="px-4 py-2 border-blue-200 border text-right font-medium">
                                {{ number_format($data->out_standing_amount * ($data->provision_rate ?? 0) / 100, 2) }} TZS
                            </td>
                            <td class="px-4 py-2 text-right border-blue-200 border">
                                <button type="button" class="text-white bg-purple-700 hover:bg-purple-800 focus:outline-none focus:ring-4 focus:ring-purple-300 font-medium rounded-full text-sm px-4 py-2 text-center">
                                    Provide
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="bg-blue-100">
                            <td colspan="6" class="px-4 py-2 text-right font-bold border border-gray-200">Total Provision Required</td>
                            <td colspan="2" class="px-4 py-2 text-right font-bold border border-gray-200">
                                {{ number_format($summary, 2) }} TZS
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Comparison Section -->
    <div class="bg-white p-6 mb-4 rounded-lg shadow">
        <h3 class="text-lg font-bold mb-4">System Comparison</h3>
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Current System -->
            <div class="border border-gray-200 rounded-lg p-4">
                <h4 class="font-semibold text-gray-700 mb-3">Current System (Traditional)</h4>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-start">
                        <span class="text-gray-400 mr-2">•</span>
                        <span>Fixed provision rates based on days in arrears</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-gray-400 mr-2">•</span>
                        <span>Simple classification: Performing, Watch, Substandard, Doubtful, Loss</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-gray-400 mr-2">•</span>
                        <span>Backward-looking approach</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-gray-400 mr-2">•</span>
                        <span>Manual journal entries</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-gray-400 mr-2">•</span>
                        <span>Basic reporting</span>
                    </li>
                </ul>
            </div>

            <!-- New System -->
            <div class="border border-green-200 bg-green-50 rounded-lg p-4">
                <h4 class="font-semibold text-green-700 mb-3">New System (IFRS 9 ECL)</h4>
                <ul class="space-y-2 text-sm">
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">✓</span>
                        <span>3-Stage ECL model (12-month & Lifetime ECL)</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">✓</span>
                        <span>Forward-looking with economic scenarios</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">✓</span>
                        <span>PD × LGD × EAD calculation methodology</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">✓</span>
                        <span>Automated GL posting with audit trail</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">✓</span>
                        <span>Comprehensive analytics and reporting</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">✓</span>
                        <span>Stage migration tracking</span>
                    </li>
                    <li class="flex items-start">
                        <span class="text-green-500 mr-2">✓</span>
                        <span>Regulatory compliance (IFRS 9, Basel III)</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Journal Entry Section (Legacy) -->
    <div class="bg-white mb-4">
        <div class="container mx-auto mt-8">
            <h4 class="text-lg font-bold mb-4 p-4">Journal Entry (Traditional Method)</h4>
            <div class="overflow-x-auto mt-4">
                <table class="min-w-full bg-white border border-gray-200 text-sm">
                    <thead>
                        <tr>
                            <th class="px-4 py-2 text-left bg-blue-100 border border-gray-200" colspan="2"></th>
                            <th class="px-4 py-2 text-right bg-blue-100 border border-gray-200" colspan="3">JV/{{ now()->format('Y') }}</th>
                        </tr>
                        <tr>
                            <th class="px-4 py-2 text-left bg-blue-100 border border-gray-200">S/N</th>
                            <th class="px-4 py-2 text-left bg-blue-100 border border-gray-200">Account</th>
                            <th class="px-4 py-2 text-left bg-blue-100 border border-gray-200">Folio</th>
                            <th class="px-4 py-2 text-right bg-blue-100 border border-gray-200">DR</th>
                            <th class="px-4 py-2 text-right bg-blue-100 border border-gray-200">CR</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="px-4 py-2 border-blue-200 border">1</td>
                            <td class="px-4 py-2 border-blue-200 border">PROFIT AND LOSS</td>
                            <td class="px-4 py-2 border-blue-200 border">5010</td>
                            <td class="px-4 py-2 border-blue-200 border text-right">{{ number_format($summary, 2) }} TZS</td>
                            <td class="px-4 py-2 text-right border-blue-200 border">-</td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2 border-blue-200 border">2</td>
                            <td class="px-4 py-2 border-blue-200 border">PROVISION FOR DOUBTFUL DEBT</td>
                            <td class="px-4 py-2 border-blue-200 border">1290</td>
                            <td class="px-4 py-2 border-blue-200 border text-right">-</td>
                            <td class="px-4 py-2 text-right border-blue-200 border">{{ number_format($summary, 2) }} TZS</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="bg-blue-100">
                            <td colspan="3" class="px-4 py-2 text-right font-bold border border-gray-200">Total</td>
                            <td class="px-4 py-2 text-right font-bold border border-gray-200">{{ number_format($summary, 2) }} TZS</td>
                            <td class="px-4 py-2 text-right font-bold border border-gray-200">{{ number_format($summary, 2) }} TZS</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- Migration Call to Action -->
    <div class="bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg p-8 text-white text-center">
        <h3 class="text-2xl font-bold mb-4">Ready to Upgrade?</h3>
        <p class="mb-6">Switch to the IFRS 9 ECL model for more accurate provisioning and regulatory compliance.</p>
        <div class="flex justify-center gap-4">
            <button onclick="window.location.href='/accounting/provision-management'" 
                    class="bg-white text-blue-600 font-bold py-3 px-6 rounded-lg hover:bg-gray-100 transition">
                Start Using IFRS 9 System
            </button>
            <button onclick="alert('The new IFRS 9 ECL system provides:\n\n• 3-Stage classification (Performing, SICR, Non-performing)\n• PD × LGD × EAD calculation methodology\n• Forward-looking economic scenarios\n• Automated GL posting\n• Full regulatory compliance\n\nClick Start Using IFRS 9 System to begin!')" 
                    class="bg-blue-700 text-white font-bold py-3 px-6 rounded-lg hover:bg-blue-800 transition">
                Learn More
            </button>
        </div>
    </div>
</div>