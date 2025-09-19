<div class="space-y-4">
    {{-- Bank Accounts --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <h3 class="text-sm font-semibold mb-2">Bank Accounts for Disbursement</h3>
        <table class="min-w-full text-sm">
            <thead>
                <tr class="border-b">
                    <th class="text-left py-1">Account</th>
                    <th class="text-left py-1">Mirror Account</th>
                    <th class="text-right py-1">Balance</th>
                    <th class="text-center py-1">Status</th>
                    <th class="text-center py-1">Select</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bankAccounts as $account)
                <tr class="border-b">
                    <td class="py-1">{{ $account['name'] }}</td>
                    <td class="py-1 text-xs font-mono">{{ $account['mirror_account'] }}</td>
                    <td class="py-1 text-right {{ $account['can_disburse'] ? 'text-green-600' : 'text-red-600' }}">
                        {{ number_format($account['balance'], 2) }}
                    </td>
                    <td class="py-1 text-center">
                        {{ $account['can_disburse'] ? '✓' : '✗' }}
                    </td>
                    <td class="py-1 text-center">
                        <input type="radio" name="selected_bank" value="{{ $account['mirror_account'] }}" 
                               wire:model="selectedBankMirror"
                               {{ $account['can_disburse'] ? '' : 'disabled' }}>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Journal Entries with Correct Flow --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
        <h3 class="text-sm font-semibold mb-2">Journal Entries Configuration (Double-Entry Bookkeeping)</h3>
        
        @php
        // Get the configured accounts from loan_sub_products
        $loanProductAccount = $glAccountMappings['0101100012001295'] ?? null;
        $interestAccount = $glAccountMappings['0101400040004010'] ?? null;
        $chargesAccount = $glAccountMappings['0101400041004120'] ?? null;
        $insuranceAccount = $glAccountMappings['0101400041004110'] ?? null;
        
        // Selected bank mirror account (example: if first bank is selected)
        $selectedBankMirror = $bankAccounts[0]['mirror_account'] ?? '0101100010001010';
        $selectedBankBalance = $bankAccounts[0]['balance'] ?? 0;
        
        // Build journal entries based on the CORRECT accounting flow
        $journalEntries = [];
        $totalDebits = 0;
        $totalCredits = 0;
        $entryNumber = 1;
        
        // STEP 1: LOAN DISBURSEMENT
        // Debit Bank Mirror Account with Principal
        $journalEntries[] = [
            'step' => '1',
            'entry_no' => $entryNumber++,
            'account' => $selectedBankMirror,
            'name' => 'Bank Mirror Account (Selected Bank)',
            'type' => 'Loan Disbursement',
            'current_balance' => $selectedBankBalance,
            'debit' => $loan->principle ?? 0,
            'credit' => 0,
            'new_balance' => $selectedBankBalance - ($loan->principle ?? 0),
            'side' => 'DR',
            'editable' => false,
            'description' => 'Cash outflow from bank'
        ];
        $totalDebits += $loan->principle ?? 0;
        
        // Credit Loan Account (to be created)
        $journalEntries[] = [
            'step' => '1',
            'entry_no' => $entryNumber++,
            'account' => $loan->loan_account_number ?? 'BUS202596153',
            'name' => 'Loan Account (Under ' . ($loanProductAccount['account_name'] ?? 'MKOPO WA BIASHARA') . ')',
            'type' => 'Loan Disbursement',
            'current_balance' => 0, // New account
            'debit' => 0,
            'credit' => $loan->principle ?? 0,
            'new_balance' => -($loan->principle ?? 0), // Negative because it's a loan liability from client perspective
            'side' => 'CR',
            'editable' => false,
            'description' => 'Loan created for client'
        ];
        $totalCredits += $loan->principle ?? 0;
        
        // STEP 2: DEDUCTIONS
        // Process Charges
        if(isset($deductionEntries['charges']['total']) && $deductionEntries['charges']['total'] > 0) {
            // Credit Bank Mirror Account (money coming back for charges)
            $journalEntries[] = [
                'step' => '2a',
                'entry_no' => $entryNumber++,
                'account' => $selectedBankMirror,
                'name' => 'Bank Mirror Account (Selected Bank)',
                'type' => 'Processing Charges Collection',
                'current_balance' => $selectedBankBalance - ($loan->principle ?? 0),
                'debit' => 0,
                'credit' => $deductionEntries['charges']['total'],
                'new_balance' => $selectedBankBalance - ($loan->principle ?? 0) + $deductionEntries['charges']['total'],
                'side' => 'CR',
                'editable' => false,
                'description' => 'Charges collected back to bank'
            ];
            $totalCredits += $deductionEntries['charges']['total'];
            
            // Debit Charges Income Account
            $journalEntries[] = [
                'step' => '2a',
                'entry_no' => $entryNumber++,
                'account' => '0101400041004120',
                'name' => $chargesAccount['account_name'] ?? 'LOAN PROCESSING FEES',
                'type' => 'Processing Charges Income',
                'current_balance' => $chargesAccount['current_balance'] ?? 0,
                'debit' => $deductionEntries['charges']['total'],
                'credit' => 0,
                'new_balance' => ($chargesAccount['current_balance'] ?? 0) + $deductionEntries['charges']['total'],
                'side' => 'DR',
                'editable' => true,
                'description' => 'Charges income recognized'
            ];
            $totalDebits += $deductionEntries['charges']['total'];
        }
        
        // Process Insurance
        if(isset($deductionEntries['insurance']['total']) && $deductionEntries['insurance']['total'] > 0) {
            // Credit Bank Mirror Account (money coming back for insurance)
            $runningBankBalance = $selectedBankBalance - ($loan->principle ?? 0) + ($deductionEntries['charges']['total'] ?? 0);
            $journalEntries[] = [
                'step' => '2b',
                'entry_no' => $entryNumber++,
                'account' => $selectedBankMirror,
                'name' => 'Bank Mirror Account (Selected Bank)',
                'type' => 'Insurance Premium Collection',
                'current_balance' => $runningBankBalance,
                'debit' => 0,
                'credit' => $deductionEntries['insurance']['total'],
                'new_balance' => $runningBankBalance + $deductionEntries['insurance']['total'],
                'side' => 'CR',
                'editable' => false,
                'description' => 'Insurance collected back to bank'
            ];
            $totalCredits += $deductionEntries['insurance']['total'];
            
            // Debit Insurance Income Account
            $journalEntries[] = [
                'step' => '2b',
                'entry_no' => $entryNumber++,
                'account' => '0101400041004110',
                'name' => $insuranceAccount['account_name'] ?? 'LATE PAYMENT FEES',
                'type' => 'Insurance Premium Income',
                'current_balance' => $insuranceAccount['current_balance'] ?? 0,
                'debit' => $deductionEntries['insurance']['total'],
                'credit' => 0,
                'new_balance' => ($insuranceAccount['current_balance'] ?? 0) + $deductionEntries['insurance']['total'],
                'side' => 'DR',
                'editable' => true,
                'issue' => true,
                'issue_note' => 'Wrong account - should be Insurance Income',
                'description' => 'Insurance income recognized'
            ];
            $totalDebits += $deductionEntries['insurance']['total'];
        }
        
        // Process First Interest
        if(isset($deductionEntries['first_interest']['total']) && $deductionEntries['first_interest']['total'] > 0) {
            // Credit Bank Mirror Account (money coming back for interest)
            $runningBankBalance = $selectedBankBalance - ($loan->principle ?? 0) + ($deductionEntries['charges']['total'] ?? 0) + ($deductionEntries['insurance']['total'] ?? 0);
            $journalEntries[] = [
                'step' => '2c',
                'entry_no' => $entryNumber++,
                'account' => $selectedBankMirror,
                'name' => 'Bank Mirror Account (Selected Bank)',
                'type' => 'First Interest Collection (' . ($deductionEntries['first_interest']['days'] ?? 0) . ' days)',
                'current_balance' => $runningBankBalance,
                'debit' => 0,
                'credit' => $deductionEntries['first_interest']['total'],
                'new_balance' => $runningBankBalance + $deductionEntries['first_interest']['total'],
                'side' => 'CR',
                'editable' => false,
                'description' => 'First interest collected back to bank'
            ];
            $totalCredits += $deductionEntries['first_interest']['total'];
            
            // Debit Interest Income Account
            $journalEntries[] = [
                'step' => '2c',
                'entry_no' => $entryNumber++,
                'account' => '0101400040004010',
                'name' => $interestAccount['account_name'] ?? 'INTEREST INCOME - CURRENT',
                'type' => 'First Interest Income',
                'current_balance' => $interestAccount['current_balance'] ?? 0,
                'debit' => $deductionEntries['first_interest']['total'],
                'credit' => 0,
                'new_balance' => ($interestAccount['current_balance'] ?? 0) + $deductionEntries['first_interest']['total'],
                'side' => 'DR',
                'editable' => true,
                'description' => 'Interest income recognized'
            ];
            $totalDebits += $deductionEntries['first_interest']['total'];
        }
        
        // Available GL accounts for dropdown selection
        $availableAccounts = [
            '0101400040004010' => 'INTEREST INCOME - CURRENT',
            '0101400041004120' => 'LOAN PROCESSING FEES',
            '0101400041004110' => 'LATE PAYMENT FEES (Wrong for Insurance)',
            '0101400041004111' => 'INSURANCE INCOME (Correct for Insurance)',
            '0101400041004130' => 'OTHER LOAN INCOME',
            '0101100012001295' => 'MKOPO WA BIASHARA (Loan Portfolio)',
        ];
        
        // Calculate net effect on bank
        $totalDeductions = ($deductionEntries['charges']['total'] ?? 0) + 
                          ($deductionEntries['insurance']['total'] ?? 0) + 
                          ($deductionEntries['first_interest']['total'] ?? 0);
        $netBankOutflow = ($loan->principle ?? 0) - $totalDeductions;
        @endphp
        
        <div class="mb-3 p-2 bg-gray-50 border border-gray-200 rounded text-xs">
            <strong>Selected Bank Mirror Account:</strong> {{ $selectedBankMirror }} | 
            <strong>Net Bank Outflow:</strong> {{ number_format($netBankOutflow, 2) }} TZS | 
            <strong>Net to Client:</strong> {{ number_format(($loan->principle ?? 0) - $totalDeductions, 2) }} TZS
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full text-xs">
                <thead>
                    <tr class="border-b bg-gray-50">
                        <th class="text-center py-1 px-1">Step</th>
                        <th class="text-center py-1 px-1">#</th>
                        <th class="text-center py-1 px-1">Side</th>
                        <th class="text-left py-1 px-2">GL Account</th>
                        <th class="text-left py-1 px-2">Account Name</th>
                        <th class="text-left py-1 px-2">Transaction Type</th>
                        <th class="text-right py-1 px-2">Current Bal</th>
                        <th class="text-right py-1 px-2">Debit</th>
                        <th class="text-right py-1 px-2">Credit</th>
                        <th class="text-right py-1 px-2">New Balance</th>
                        <th class="text-center py-1 px-2">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $currentStep = '';
                    @endphp
                    @foreach($journalEntries as $index => $entry)
                    <tr class="border-b transition-all {{ isset($entry['issue']) && $entry['issue'] ? 'bg-red-50 hover:bg-red-100 animate-pulse' : 'hover:bg-gray-50' }}">
                        <td class="py-1 px-1 text-center font-semibold">
                            @if($currentStep != $entry['step'])
                                {{ $entry['step'] }}
                                @php $currentStep = $entry['step']; @endphp
                            @endif
                        </td>
                        <td class="py-1 px-1 text-center">{{ $entry['entry_no'] }}</td>
                        <td class="py-1 px-1 text-center">
                            @if($entry['side'] == 'DR')
                                <span class="text-red-600 font-semibold">DR</span>
                            @else
                                <span class="text-green-600 font-semibold">CR</span>
                            @endif
                        </td>
                        <td class="py-1 px-2">
                            @if($entry['editable'])
                                @php
                                $fieldName = '';
                                if(strpos($entry['type'], 'Charges') !== false) {
                                    $fieldName = 'charges_account';
                                } elseif(strpos($entry['type'], 'Insurance') !== false) {
                                    $fieldName = 'insurance_account';
                                } elseif(strpos($entry['type'], 'Interest') !== false) {
                                    $fieldName = 'interest_account';
                                }
                                @endphp
                                <select class="text-xs border rounded px-1 py-0.5 w-full {{ isset($entry['issue']) && $entry['issue'] ? 'border-red-500 bg-red-50 font-semibold' : '' }}" 
                                        wire:change="updateAccount('{{ $fieldName }}', $event.target.value)"
                                        value="{{ $entry['account'] }}"
                                        title="Select the appropriate GL account">
                                    @foreach($availableAccounts as $acctNum => $acctName)
                                        <option value="{{ $acctNum }}" {{ $entry['account'] == $acctNum ? 'selected' : '' }}
                                                title="{{ $acctName }}">
                                            {{ $acctNum }} {{ strpos($acctName, 'Correct') !== false ? '✓' : (strpos($acctName, 'Wrong') !== false ? '✗' : '') }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <span class="font-mono">{{ $entry['account'] }}</span>
                            @endif
                        </td>
                        <td class="py-1 px-2">
                            {{ $entry['name'] }}
                            @if(isset($entry['issue']) && $entry['issue'])
                                <span class="text-red-600 text-xs block">⚠ {{ $entry['issue_note'] }}</span>
                            @endif
                        </td>
                        <td class="py-1 px-2">{{ $entry['type'] }}</td>
                        <td class="py-1 px-2 text-right">{{ number_format($entry['current_balance'], 2) }}</td>
                        <td class="py-1 px-2 text-right {{ $entry['debit'] > 0 ? 'font-semibold text-red-600' : 'text-gray-400' }}">
                            {{ $entry['debit'] > 0 ? number_format($entry['debit'], 2) : '-' }}
                        </td>
                        <td class="py-1 px-2 text-right {{ $entry['credit'] > 0 ? 'font-semibold text-green-600' : 'text-gray-400' }}">
                            {{ $entry['credit'] > 0 ? number_format($entry['credit'], 2) : '-' }}
                        </td>
                        <td class="py-1 px-2 text-right font-semibold">{{ number_format($entry['new_balance'], 2) }}</td>
                        <td class="py-1 px-2 text-center">
                            @if($entry['editable'])
                                <span class="text-blue-600 text-xs">
                                    <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Editable
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">Fixed</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t bg-gray-100 font-semibold">
                        <td colspan="7" class="py-1 px-2 text-right">Total</td>
                        <td class="py-1 px-2 text-right text-red-600">{{ number_format($totalDebits, 2) }}</td>
                        <td class="py-1 px-2 text-right text-green-600">{{ number_format($totalCredits, 2) }}</td>
                        <td colspan="2" class="py-1 px-2 text-center">
                            <span class="{{ abs($totalDebits - $totalCredits) < 0.01 ? 'text-green-600' : 'text-red-600' }}">
                                {{ abs($totalDebits - $totalCredits) < 0.01 ? '✓ Balanced' : '✗ Diff: ' . number_format(abs($totalDebits - $totalCredits), 2) }}
                            </span>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
        
        {{-- Save/Reset Buttons --}}
        @if(count($editedAccounts) > 0)
        <div class="mt-3 flex justify-between items-center">
            <div class="text-xs text-gray-600">
                <span class="font-semibold">{{ count($editedAccounts) }}</span> account(s) modified
            </div>
            <div class="flex gap-2">
                <button wire:click="resetAccountConfiguration" 
                        class="px-3 py-1 bg-gray-100 text-gray-700 text-xs rounded hover:bg-gray-200">
                    Reset Changes
                </button>
                <button wire:click="saveAccountConfiguration" 
                        wire:loading.attr="disabled"
                        wire:loading.class="opacity-50 cursor-not-allowed"
                        class="px-3 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 inline-flex items-center">
                    <span wire:loading.remove>Save Configuration</span>
                    <span wire:loading class="inline-flex items-center">
                        <svg class="animate-spin -ml-1 mr-2 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Saving...
                    </span>
                </button>
            </div>
        </div>
        @endif
        
        {{-- Save Status Message --}}
        @if($saveMessage)
        <div class="mt-3 p-3 rounded {{ $saveStatus == 'success' ? 'bg-green-50 border border-green-200' : ($saveStatus == 'error' ? 'bg-red-50 border border-red-200' : 'bg-yellow-50 border border-yellow-200') }}">
            <p class="text-xs {{ $saveStatus == 'success' ? 'text-green-800' : ($saveStatus == 'error' ? 'text-red-800' : 'text-yellow-800') }}">
                {{ $saveMessage }}
            </p>
        </div>
        @endif
        
        {{-- Configuration Issues Alert --}}
        @php
        $hasIssues = false;
        foreach($journalEntries as $entry) {
            if(isset($entry['issue']) && $entry['issue']) {
                $hasIssues = true;
                break;
            }
        }
        @endphp
        
        @if($hasIssues)
        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded">
            <p class="text-xs text-yellow-800">
                <strong>⚠ Configuration Issues Detected:</strong><br>
                • Insurance is using "LATE PAYMENT FEES" account (0101400041004110) instead of Insurance Income account<br>
                • Use the dropdown to change to the correct Insurance Income account (0101400041004111) before disbursement<br>
                • Click "Save Configuration" after making changes
            </p>
        </div>
        @endif
        
        {{-- Accounting Flow Explanation --}}
        <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded">
            <h4 class="text-xs font-semibold mb-2">Accounting Flow Explanation:</h4>
            <div class="text-xs space-y-1">
                <div><strong>Step 1 - Loan Disbursement:</strong></div>
                <div class="ml-4">• DR: Bank Mirror Account ({{ $selectedBankMirror }}) - {{ number_format($loan->principle ?? 0, 2) }}</div>
                <div class="ml-4">• CR: Loan Account ({{ $loan->loan_account_number ?? 'BUS202596153' }}) - {{ number_format($loan->principle ?? 0, 2) }}</div>
                
                <div class="mt-2"><strong>Step 2 - Deductions Collection:</strong></div>
                <div class="ml-4">a) <strong>Charges:</strong></div>
                <div class="ml-8">• CR: Bank Mirror Account - {{ number_format($deductionEntries['charges']['total'] ?? 0, 2) }} (money back to bank)</div>
                <div class="ml-8">• DR: Charges Income (0101400041004120) - {{ number_format($deductionEntries['charges']['total'] ?? 0, 2) }}</div>
                
                <div class="ml-4">b) <strong>Insurance:</strong></div>
                <div class="ml-8">• CR: Bank Mirror Account - {{ number_format($deductionEntries['insurance']['total'] ?? 0, 2) }} (money back to bank)</div>
                <div class="ml-8">• DR: Insurance Income (0101400041004110) - {{ number_format($deductionEntries['insurance']['total'] ?? 0, 2) }}</div>
                
                <div class="ml-4">c) <strong>First Interest:</strong></div>
                <div class="ml-8">• CR: Bank Mirror Account - {{ number_format($deductionEntries['first_interest']['total'] ?? 0, 2) }} (money back to bank)</div>
                <div class="ml-8">• DR: Interest Income (0101400040004010) - {{ number_format($deductionEntries['first_interest']['total'] ?? 0, 2) }}</div>
                
                <div class="mt-2"><strong>Net Effect:</strong></div>
                <div class="ml-4">• Bank Mirror Account: -{{ number_format($netBankOutflow, 2) }} (net outflow)</div>
                <div class="ml-4">• Client receives: {{ number_format(($loan->principle ?? 0) - $totalDeductions, 2) }}</div>
                <div class="ml-4">• Loan Account Balance: {{ number_format($loan->principle ?? 0, 2) }} (full loan amount)</div>
            </div>
        </div>
    </div>
</div>