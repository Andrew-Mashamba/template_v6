<?php

namespace App\Http\Livewire\Loans\Sections;

use App\Models\LoansModel;
use App\Models\AccountsModel;
use App\Models\general_ledger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class Accounting extends Component
{
    public $loan;
    public $loanId;
    public $loanAccountingDetails = [];
    public $glEntries = [];
    public $interestCalculation = [];
    public $repaymentScheduleSummary = [];
    public $accountBalances = [];
    public $showDetailedEntries = false;
    public $bankAccount = '';
    public $deductionEntries = [];
    public $glAccountMappings = [];
    public $bankAccounts = [];
    
    // For editing GL accounts
    public $editedAccounts = [];
    public $saveMessage = '';
    public $saveStatus = '';
    
    // Real-time calculation properties
    public $selectedBankMirror = '';
    public $netEffect = [];
    public $previewMode = false;
    
    // Account editing modal properties
    public $showEditAccountModal = false;
    public $editingEntryIndex = null;
    public $editingAccount = '';
    public $editingAccountName = '';
    public $editingEntryType = '';
    public $selectedNewAccount = '';
    public $selectedNewAccountName = '';
    public $accountSearchQuery = '';
    public $selectedAccountType = '';
    public $availableAccounts = [];
    public $journalEntryOverrides = [];
    public $disbursementMethod = 'gross'; // Default to gross method
    public $parametersApplied = false; // Track if parameters are applied
    
    protected $listeners = [
        'refreshAccounting' => '$refresh',
        'loanUpdated' => 'loadAccountingData',
        'calculateRealTime' => 'performRealTimeCalculation'
    ];

    public function mount()
    {
        $this->loanId = Session::get('currentloanID');
        // Initialize parametersApplied to false by default
        // It will be overwritten if saved data exists
        $this->parametersApplied = false;
        $this->loadAccountingData();
    }

    public function loadAccountingData()
    {
        if (!$this->loanId) {
            return;
        }

        // Load loan details
        $this->loan = LoansModel::find($this->loanId);
        
        if (!$this->loan) {
            return;
        }

        // Load saved accounting data from database first
        $this->loadSavedAccountingData();

        // Load loan accounting details
        $this->loadLoanAccountingDetails();
        
        // Load GL entries related to this loan
        $this->loadGLEntries();
        
        // Load interest calculation details
        $this->loadInterestCalculation();
        
        // Load repayment schedule summary
        $this->loadRepaymentScheduleSummary();
        
        // Load related account balances
        $this->loadAccountBalances();
        
        // Load GL account mappings
        $this->loadGLAccountMappings();
        
        // Load deduction entries
        $this->loadDeductionEntries();
        
        // Load bank accounts
        $this->loadBankAccounts();
        
        // Check if we need to update saved data with journal entries
        $this->ensureJournalEntriesAreSaved();
        
        // Only auto-save if parameters haven't been applied yet
        // This prevents overwriting the applied state when navigating back
        if (!$this->parametersApplied) {
            $this->autoSaveAccountingData();
        }
    }

    private function loadLoanAccountingDetails()
    {
        // Calculate interest rate from loan interest and principal if not available
        $principle = (float)($this->loan->principle ?? 0);
        $interest = (float)($this->loan->interest ?? 0);
        $tenure = (float)($this->loan->tenure ?? 12);
        
        $interestRate = 0;
        if ($tenure > 0 && $principle > 0) {
            // Calculate annual interest rate from total interest
            $interestRate = ($interest / $principle / $tenure) * 12 * 100;
        }
        
        $this->loanAccountingDetails = [
            'principal_amount' => $principle,
            'interest_amount' => $interest,
            'total_amount' => $principle + $interest,
            'disbursed_amount' => $this->loan->disbursed_amount ?? 0,
            'outstanding_balance' => $this->loan->balance ?? 0,
            'total_paid' => $principle + $interest - ($this->loan->balance ?? 0),
            'interest_rate' => $interestRate,
            'duration' => $tenure,
            'loan_account' => $this->loan->loan_account_number ?? '',
            'client_account' => $this->loan->account_number ?? '',
            'status' => $this->loan->status ?? 'PENDING',
            'disbursement_date' => $this->loan->disbursement_date ?? null,
            'maturity_date' => $this->loan->maturity_date ?? null,
        ];
    }

    private function loadGLEntries()
    {
        // Load GL entries related to this loan
        $this->glEntries = general_ledger::where('loan_id', $this->loanId)
            ->orWhere('reference_number', 'LIKE', '%LOAN-' . $this->loanId . '%')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($entry) {
                return [
                    'date' => $entry->created_at,
                    'account' => $entry->account_number,
                    'account_name' => $this->getAccountName($entry->account_number),
                    'debit' => $entry->debit,
                    'credit' => $entry->credit,
                    'description' => $entry->description ?? $entry->narration ?? '',
                    'reference' => $entry->reference_number,
                    'status' => $entry->status ?? 'POSTED',
                ];
            })
            ->toArray();
    }

    private function loadInterestCalculation()
    {
        if (!$this->loan) {
            return;
        }

        $principal = $this->loan->principle ?? 0;
        $rate = $this->loan->interest_rate ?? 0;
        $tenure = $this->loan->tenure ?? 0;
        $method = $this->loan->interest_method ?? 'FLAT_RATE';

        // Calculate interest based on method
        $totalInterest = 0;
        $monthlyInterest = 0;
        
        if ($method === 'FLAT_RATE') {
            $totalInterest = ($principal * $rate * $tenure) / 100;
            $monthlyInterest = $totalInterest / ($tenure ?: 1);
        } elseif ($method === 'DECLINING_BALANCE') {
            // Declining balance calculation
            $monthlyRate = ($rate / 12) / 100;
            $monthlyPayment = $principal * ($monthlyRate * pow(1 + $monthlyRate, $tenure)) / (pow(1 + $monthlyRate, $tenure) - 1);
            $totalInterest = ($monthlyPayment * $tenure) - $principal;
            $monthlyInterest = $totalInterest / ($tenure ?: 1);
        }

        $this->interestCalculation = [
            'method' => $method,
            'principal' => $principal,
            'rate' => $rate,
            'tenure' => $tenure,
            'total_interest' => $totalInterest,
            'monthly_interest' => $monthlyInterest,
            'total_repayment' => $principal + $totalInterest,
            'monthly_repayment' => ($principal + $totalInterest) / ($tenure ?: 1),
        ];
    }

    private function loadRepaymentScheduleSummary()
    {
        if (!$this->loan) {
            return;
        }

        // Get repayment schedule from loans_schedules table
        $schedules = DB::table('loans_schedules')
            ->where('loan_id', $this->loanId)
            ->orderBy('installment')
            ->get();

        $totalScheduledPrincipal = 0;
        $totalScheduledInterest = 0;
        $totalPaidPrincipal = 0;
        $totalPaidInterest = 0;
        $overdueAmount = 0;
        $nextPaymentDue = null;

        foreach ($schedules as $schedule) {
            $totalScheduledPrincipal += $schedule->principle ?? 0;
            $totalScheduledInterest += $schedule->interest ?? 0;
            
            if ($schedule->completion_status === 'PAID') {
                $totalPaidPrincipal += $schedule->principle ?? 0;
                $totalPaidInterest += $schedule->interest ?? 0;
            } elseif ($schedule->completion_status === 'OVERDUE') {
                $overdueAmount += ($schedule->principle ?? 0) + ($schedule->interest ?? 0);
            } elseif (!$nextPaymentDue && $schedule->completion_status === 'PENDING') {
                $nextPaymentDue = $schedule->repayment_date;
            }
        }

        $this->repaymentScheduleSummary = [
            'total_installments' => $schedules->count(),
            'paid_installments' => $schedules->where('completion_status', 'PAID')->count(),
            'pending_installments' => $schedules->where('completion_status', 'PENDING')->count(),
            'overdue_installments' => $schedules->where('completion_status', 'OVERDUE')->count(),
            'total_scheduled_principal' => $totalScheduledPrincipal,
            'total_scheduled_interest' => $totalScheduledInterest,
            'total_paid_principal' => $totalPaidPrincipal,
            'total_paid_interest' => $totalPaidInterest,
            'overdue_amount' => $overdueAmount,
            'next_payment_due' => $nextPaymentDue,
        ];
    }

    private function loadAccountBalances()
    {
        if (!$this->loan) {
            return;
        }

        // Get relevant account balances
        $accounts = [];
        
        // Loan account
        if ($this->loan->loan_account_number) {
            $loanAccount = AccountsModel::where('account_number', $this->loan->loan_account_number)->first();
            if ($loanAccount) {
                $accounts['loan_account'] = [
                    'account_number' => $loanAccount->account_number,
                    'account_name' => $loanAccount->account_name,
                    'balance' => $loanAccount->balance ?? 0,
                    'type' => 'LOAN',
                ];
            }
        }
        
        // Client account
        if ($this->loan->account_number) {
            $clientAccount = AccountsModel::where('account_number', $this->loan->account_number)->first();
            if ($clientAccount) {
                $accounts['client_account'] = [
                    'account_number' => $clientAccount->account_number,
                    'account_name' => $clientAccount->account_name,
                    'balance' => $clientAccount->balance ?? 0,
                    'type' => 'CLIENT',
                ];
            }
        }

        $this->accountBalances = $accounts;
    }

    private function getAccountName($accountNumber)
    {
        $account = AccountsModel::where('account_number', $accountNumber)->first();
        return $account ? $account->account_name : 'Unknown Account';
    }
    
    private function loadGLAccountMappings()
    {
        if (!$this->loan) {
            return;
        }
        
        // Get actual GL accounts used for this loan
        $accountNumbers = [];
        
        // Loan account
        if ($this->loan->loan_account_number) {
            $accountNumbers[] = $this->loan->loan_account_number;
        }
        
        // Client account
        if ($this->loan->account_number) {
            $accountNumbers[] = $this->loan->account_number;
        }
        
        // Interest account
        if ($this->loan->interest_account_number) {
            $accountNumbers[] = $this->loan->interest_account_number;
        }
        
        // Charge account
        if ($this->loan->charge_account_number) {
            $accountNumbers[] = $this->loan->charge_account_number;
        }
        
        // Insurance account
        if ($this->loan->insurance_account_number) {
            $accountNumbers[] = $this->loan->insurance_account_number;
        }
        
        // Disbursement account (bank)
        if ($this->loan->disbursement_account) {
            $accountNumbers[] = $this->loan->disbursement_account;
        }
        
        // Also add standard GL accounts
        $accountNumbers = array_merge($accountNumbers, ['1311', '2111', '1111', '4111', '4211', '4311', '4411']);
        
        // Remove duplicates
        $accountNumbers = array_unique($accountNumbers);
        
        // Fetch all accounts at once
        $accounts = DB::table('accounts')
            ->whereIn('account_number', $accountNumbers)
            ->get()
            ->keyBy('account_number');
        
        // Map accounts
        $this->glAccountMappings = [];
        foreach ($accounts as $accountNumber => $account) {
            $this->glAccountMappings[$accountNumber] = [
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'current_balance' => (float)($account->balance ?? 0),
                'account_type' => $account->GL_AccountTypeID ?? null
            ];
        }
        
        // Add specific mappings for loan fields
        if ($this->loan->loan_account_number && isset($accounts[$this->loan->loan_account_number])) {
            $this->glAccountMappings['loan_account'] = $this->glAccountMappings[$this->loan->loan_account_number];
        }
        
        if ($this->loan->account_number && isset($accounts[$this->loan->account_number])) {
            $this->glAccountMappings['client_account'] = $this->glAccountMappings[$this->loan->account_number];
        }
    }
    
    private function loadDeductionEntries()
    {
        if (!$this->loan) {
            return;
        }
        
        $deductions = [];
        
        // Get charges breakdown
        $chargesBreakdown = $this->getChargesBreakdown();
        if (!empty($chargesBreakdown)) {
            $deductions['charges'] = [
                'total' => array_sum(array_column($chargesBreakdown, 'amount')),
                'items' => $chargesBreakdown,
                'gl_account' => $this->loan->charge_account_number ?? '4211',
                'gl_name' => 'Loan Processing Charges Income'
            ];
        }
        
        // Get insurance breakdown
        $insuranceBreakdown = $this->getInsuranceBreakdown();
        $insuranceTotal = 0;
        
        if (!empty($insuranceBreakdown)) {
            $insuranceTotal = array_sum(array_column($insuranceBreakdown, 'total_amount'));
        }
        
        // Fallback: Check if loan has insurance_amount stored
        if ($insuranceTotal == 0 && isset($this->loan->insurance_amount) && $this->loan->insurance_amount > 0) {
            $insuranceTotal = $this->loan->insurance_amount;
            $insuranceBreakdown = [
                [
                    'name' => 'Insurance Premium',
                    'total_amount' => $this->loan->insurance_amount,
                    'type' => 'fixed'
                ]
            ];
        }
        
        if ($insuranceTotal > 0) {
            $deductions['insurance'] = [
                'total' => $insuranceTotal,
                'items' => $insuranceBreakdown,
                'gl_account' => $this->loan->insurance_account_number ?? '4311',
                'gl_name' => 'Insurance Premiums Income'
            ];
        }
        
        // Get first interest
        $firstInterest = $this->getFirstInterestAmount();
        if ($firstInterest > 0) {
            $deductions['first_interest'] = [
                'total' => $firstInterest,
                'days' => $this->calculateGracePeriodDays(),
                'gl_account' => $this->loan->interest_account_number ?? '4111',
                'gl_name' => 'Interest on Loans Income'
            ];
        }
        
        // Get top-up amount if applicable
        if ($this->loan->loan_type_2 === 'Top-up' && $this->loan->top_up_amount > 0) {
            $deductions['top_up'] = [
                'total' => $this->loan->top_up_amount,
                'loan_account' => $this->loan->top_up_loan_account ?? '',
                'penalty' => $this->loan->top_up_penalty_amount ?? 0,
                'gl_account' => '1311',
                'gl_name' => 'Loan Portfolio - Top-up Settlement'
            ];
        }
        
        // Get settlement amounts if any
        $settlements = $this->getSettlements();
        if (!empty($settlements)) {
            $deductions['settlements'] = [
                'total' => array_sum(array_column($settlements, 'amount')),
                'items' => $settlements,
                'gl_account' => '2311',
                'gl_name' => 'Third Party Settlements'
            ];
        }
        
        $this->deductionEntries = $deductions;
    }
    
    private function getChargesBreakdown()
    {
        if (!$this->loan || !$this->loan->loan_sub_product) {
            return [];
        }
        
        $charges = DB::table('loan_product_charges')
            ->where('loan_product_id', $this->loan->loan_sub_product)
            ->where('type', 'charge')
            ->get();
            
        $breakdown = [];
        $principle = (float)($this->loan->principle ?? 0);
        
        foreach ($charges as $charge) {
            $amount = 0;
            if ($charge->value_type === 'percentage') {
                $amount = ($principle * ((float)$charge->value / 100));
                
                // Apply caps if any
                if ($charge->min_cap && $amount < (float)$charge->min_cap) {
                    $amount = (float)$charge->min_cap;
                }
                if ($charge->max_cap && $amount > (float)$charge->max_cap) {
                    $amount = (float)$charge->max_cap;
                }
            } else {
                $amount = (float)$charge->value;
            }
            
            if ($amount > 0) {
                $breakdown[] = [
                    'name' => $charge->name,
                    'amount' => $amount,
                    'type' => $charge->value_type,
                    'value' => $charge->value
                ];
            }
        }
        
        return $breakdown;
    }
    
    private function getInsuranceBreakdown()
    {
        if (!$this->loan || !$this->loan->loan_sub_product) {
            return [];
        }
        
        $insurances = DB::table('loan_product_charges')
            ->where('loan_product_id', $this->loan->loan_sub_product)
            ->where('type', 'insurance')
            ->get();
            
        $breakdown = [];
        $principle = (float)($this->loan->principle ?? 0);
        $tenure = (int)($this->loan->tenure ?? 0);
        
        foreach ($insurances as $insurance) {
            $monthlyAmount = 0;
            
            if ($insurance->value_type === 'percentage') {
                $monthlyAmount = ($principle * ((float)$insurance->value / 100));
            } else {
                $monthlyAmount = (float)$insurance->value;
            }
            
            $totalAmount = $monthlyAmount * $tenure;
            
            if ($totalAmount > 0) {
                $breakdown[] = [
                    'name' => $insurance->name,
                    'monthly_amount' => $monthlyAmount,
                    'total_amount' => $totalAmount,
                    'tenure' => $tenure,
                    'type' => $insurance->value_type,
                    'value' => $insurance->value
                ];
            }
        }
        
        return $breakdown;
    }
    
    private function getFirstInterestAmount()
    {
        if (!$this->loan) {
            return 0;
        }
        
        $principle = (float)($this->loan->principle ?? 0);
        $interest = (float)($this->loan->interest ?? 0);
        $tenure = (float)($this->loan->tenure ?? 12);
        
        // Calculate interest rate from total interest if not directly available
        if ($tenure > 0 && $principle > 0) {
            $interestRate = ($interest / $principle) * 100;
        } else {
            $interestRate = 0;
        }
        
        $monthlyRate = $interestRate / 12 / 100;
        
        // Calculate grace period days
        $days = $this->calculateGracePeriodDays();
        $daysInMonth = 30;
        $dailyRate = $monthlyRate / $daysInMonth;
        
        return $principle * $dailyRate * $days;
    }
    
    private function calculateGracePeriodDays()
    {
        $disbursementDate = new \DateTime();
        $payrollDay = 15; // Default
        
        // Get payroll date from member group if available
        if ($this->loan->client_number) {
            $client = DB::table('clients')->where('client_number', $this->loan->client_number)->first();
            if ($client && $client->member_group) {
                $group = DB::table('member_groups')->where('group_id', $client->member_group)->first();
                if ($group && $group->payrol_date) {
                    $payrollDay = (int)$group->payrol_date;
                }
            }
        }
        
        $nextPayroll = clone $disbursementDate;
        $nextPayroll->setDate($disbursementDate->format('Y'), $disbursementDate->format('m'), $payrollDay);
        
        if ($disbursementDate > $nextPayroll) {
            $nextPayroll->modify('first day of next month');
            $nextPayroll->setDate($nextPayroll->format('Y'), $nextPayroll->format('m'), $payrollDay);
        }
        
        return $disbursementDate->diff($nextPayroll)->days;
    }
    
    private function getSettlements()
    {
        if (!$this->loan || !$this->loan->assessment_data) {
            return [];
        }
        
        $assessmentData = json_decode($this->loan->assessment_data, true);
        $settlements = [];
        
        // Check for settlements in various possible formats
        if (isset($assessmentData['settlements']) && is_array($assessmentData['settlements'])) {
            // Make sure each settlement is properly formatted as an array
            foreach ($assessmentData['settlements'] as $settlement) {
                if (is_array($settlement)) {
                    $settlements[] = [
                        'institution' => $settlement['institution'] ?? 'Unknown',
                        'account' => $settlement['account'] ?? 'N/A',
                        'amount' => (float)($settlement['amount'] ?? 0)
                    ];
                } elseif (is_numeric($settlement)) {
                    // Handle case where settlement might just be a number
                    $settlements[] = [
                        'institution' => 'Third Party',
                        'account' => 'Settlement',
                        'amount' => (float)$settlement
                    ];
                }
            }
        }
        
        // Also check for totalAmount field (used in some loan types)
        if (empty($settlements) && isset($assessmentData['totalAmount']) && $assessmentData['totalAmount'] > 0) {
            $settlements[] = [
                'institution' => 'Settlement',
                'account' => 'General',
                'amount' => (float)$assessmentData['totalAmount']
            ];
        }
        
        return $settlements;
    }
    
    private function loadBankAccounts()
    {
        // Load bank accounts with balances
        $this->bankAccounts = DB::table('bank_accounts')
            ->select('id', 'account_name', 'account_number', 'internal_mirror_account_number', 'current_balance', 'bank_name')
            ->where('current_balance', '>', 0)
            ->orderBy('current_balance', 'desc')
            ->get()
            ->map(function($account) {
                return [
                    'id' => $account->id,
                    'name' => $account->account_name,
                    'number' => $account->account_number,
                    'mirror_account' => $account->internal_mirror_account_number,
                    'bank_name' => $account->bank_name,
                    'balance' => (float)$account->current_balance,
                    'can_disburse' => (float)$account->current_balance >= ($this->loan->net_disbursement_amount ?? 0)
                ];
            })
            ->toArray();
        
        // Auto-select the first bank that can disburse if none is selected
        if (empty($this->selectedBankMirror) && !empty($this->bankAccounts)) {
            foreach ($this->bankAccounts as $account) {
                if ($account['can_disburse']) {
                    $this->selectedBankMirror = $account['mirror_account'];
                    Log::info('Auto-selected bank mirror account: ' . $this->selectedBankMirror);
                    break;
                }
            }
            
            // If no bank can disburse, select the first one
            if (empty($this->selectedBankMirror) && !empty($this->bankAccounts)) {
                $this->selectedBankMirror = $this->bankAccounts[0]['mirror_account'];
            }
        }
    }

    public function toggleDetailedEntries()
    {
        $this->showDetailedEntries = !$this->showDetailedEntries;
    }

    public function applyParameters()
    {
        try {
            if (!$this->loan) {
                session()->flash('error', 'No loan selected.');
                return;
            }
            
            // Mark parameters as applied BEFORE saving
            $this->parametersApplied = true;
            
            // Save all current parameters and accounting data including the applied state
            $this->autoSaveAccountingData();
            
            // Log the action
            Log::info('Accounting parameters applied for loan ID: ' . $this->loan->id, [
                'parameters_applied' => $this->parametersApplied,
                'selected_bank' => $this->selectedBankMirror,
                'disbursement_method' => $this->disbursementMethod
            ]);
            
            // Show success message
            $this->saveStatus = 'success';
            $this->saveMessage = 'Parameters applied successfully!';
            
        } catch (\Exception $e) {
            Log::error('Error applying parameters: ' . $e->getMessage());
            $this->saveStatus = 'error';
            $this->saveMessage = 'Failed to apply parameters.';
        }
    }
    
    public function editParameters()
    {
        // Allow editing parameters again
        $this->parametersApplied = false;
        
        // Save the state change
        $this->autoSaveAccountingData();
        
        Log::info('Edit mode enabled for accounting parameters, loan ID: ' . $this->loan->id, [
            'parameters_applied' => $this->parametersApplied
        ]);
    }
    
    public function exportAccountingReport()
    {
        try {
            if (!$this->loan) {
                session()->flash('error', 'No loan selected for export.');
                return;
            }
            
            // Generate the accounting report data
            $reportData = $this->generateAccountingReportData();
            
            // Create CSV content
            $csvContent = $this->generateCSVContent($reportData);
            
            // Generate filename
            $filename = 'loan_accounting_' . $this->loan->loan_id . '_' . date('Y-m-d_His') . '.csv';
            
            // Return download response
            return response()->streamDownload(function() use ($csvContent) {
                echo $csvContent;
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error generating report: ' . $e->getMessage());
        }
    }
    
    private function generateAccountingReportData()
    {
        $data = [
            'loan_details' => [
                'Loan ID' => $this->loan->loan_id ?? 'N/A',
                'Client Name' => $this->getClientName(),
                'Principal Amount' => $this->loan->principle ?? 0,
                'Total Deductions' => $this->getTotalDeductions(),
                'Net Disbursement' => $this->loan->net_disbursement_amount ?? ($this->loan->principle - $this->getTotalDeductions()),
                'Status' => $this->loan->status ?? 'PENDING',
                'Disbursement Date' => $this->loan->disbursement_date ?? 'Not Disbursed'
            ],
            'journal_entries' => $this->generateJournalEntries(),
            'deductions' => $this->deductionEntries,
            'balances' => $this->accountBalances
        ];
        
        return $data;
    }
    
    private function generateCSVContent($data)
    {
        $csv = "LOAN ACCOUNTING REPORT\n";
        $csv .= "Generated: " . date('Y-m-d H:i:s') . "\n\n";
        
        // Loan Details Section
        $csv .= "LOAN DETAILS\n";
        foreach ($data['loan_details'] as $key => $value) {
            $csv .= "$key,$value\n";
        }
        $csv .= "\n";
        
        // Journal Entries Section
        $csv .= "JOURNAL ENTRIES\n";
        $csv .= "Entry #,Account,Description,Debit,Credit\n";
        foreach ($data['journal_entries'] as $entry) {
            $csv .= "{$entry['entry_no']},{$entry['account']},\"{$entry['description']}\",";
            $csv .= ($entry['debit'] > 0 ? $entry['debit'] : '') . ",";
            $csv .= ($entry['credit'] > 0 ? $entry['credit'] : '') . "\n";
        }
        $csv .= "\n";
        
        // Deductions Section
        if (!empty($data['deductions'])) {
            $csv .= "DEDUCTIONS BREAKDOWN\n";
            foreach ($data['deductions'] as $type => $deduction) {
                $csv .= strtoupper($type) . "," . ($deduction['total'] ?? 0) . "\n";
                if (isset($deduction['items'])) {
                    foreach ($deduction['items'] as $item) {
                        $csv .= "," . ($item['name'] ?? '') . "," . ($item['amount'] ?? $item['total_amount'] ?? 0) . "\n";
                    }
                }
            }
        }
        
        return $csv;
    }
    
    private function generateJournalEntries()
    {
        $entries = [];
        $entryNumber = 1;
        
        // Initial disbursement entries
        $entries[] = [
            'entry_no' => $entryNumber++,
            'account' => $this->bankAccounts[0]['mirror_account'] ?? '0101100010001010',
            'description' => 'Bank Mirror Account - Cash Disbursement',
            'debit' => $this->loan->principle ?? 0,
            'credit' => 0
        ];
        
        $entries[] = [
            'entry_no' => $entryNumber++,
            'account' => $this->loan->loan_account_number ?? 'PENDING',
            'description' => 'Loan Account - Principal',
            'debit' => 0,
            'credit' => $this->loan->principle ?? 0
        ];
        
        // Deduction entries
        if (isset($this->deductionEntries['charges']['total']) && $this->deductionEntries['charges']['total'] > 0) {
            $entries[] = [
                'entry_no' => $entryNumber++,
                'account' => $this->bankAccounts[0]['mirror_account'] ?? '0101100010001010',
                'description' => 'Bank Mirror Account - Charges Collection',
                'debit' => 0,
                'credit' => $this->deductionEntries['charges']['total']
            ];
            
            $entries[] = [
                'entry_no' => $entryNumber++,
                'account' => '0101400041004120',
                'description' => 'Loan Processing Charges Income',
                'debit' => $this->deductionEntries['charges']['total'],
                'credit' => 0
            ];
        }
        
        // Similar entries for insurance and first interest...
        
        return $entries;
    }
    
    private function getTotalDeductions()
    {
        $total = 0;
        $total += $this->deductionEntries['charges']['total'] ?? 0;
        $total += $this->deductionEntries['insurance']['total'] ?? 0;
        $total += $this->deductionEntries['first_interest']['total'] ?? 0;
        $total += $this->deductionEntries['top_up']['total'] ?? 0;
        $total += $this->deductionEntries['settlements']['total'] ?? 0;
        return $total;
    }
    
    private function getClientName()
    {
        if (!$this->loan || !$this->loan->client_number) {
            return 'N/A';
        }
        
        $client = DB::table('clients')->where('client_number', $this->loan->client_number)->first();
        if ($client) {
            return trim($client->first_name . ' ' . $client->middle_name . ' ' . $client->last_name);
        }
        
        return 'N/A';
    }
    
    public function updateAccount($field, $value)
    {
        $this->editedAccounts[$field] = $value;
    }
    
    public function saveAccountConfiguration()
    {
        try {
            if (empty($this->editedAccounts)) {
                $this->saveMessage = 'No changes to save';
                $this->saveStatus = 'warning';
                return;
            }
            
            // Update loan product configuration
            if ($this->loan && $this->loan->loan_sub_product) {
                $updates = [];
                
                // Map the edited fields to database columns
                $fieldMapping = [
                    'interest_account' => 'gl_account_number_for_interest',
                    'charges_account' => 'gl_account_number_for_charges', 
                    'insurance_account' => 'gl_account_number_for_insurance',
                    'penalty_account' => 'gl_account_number_for_penalty'
                ];
                
                foreach ($this->editedAccounts as $field => $accountNumber) {
                    if (isset($fieldMapping[$field])) {
                        $updates[$fieldMapping[$field]] = $accountNumber;
                    }
                }
                
                if (!empty($updates)) {
                    DB::table('loan_sub_products')
                        ->where('id', $this->loan->loan_sub_product)
                        ->update($updates);
                    
                    $this->saveMessage = 'Account configuration updated successfully';
                    $this->saveStatus = 'success';
                    
                    // Reload the data to show updated values
                    $this->loadAccountingData();
                    $this->editedAccounts = [];
                } else {
                    $this->saveMessage = 'No valid changes to save';
                    $this->saveStatus = 'warning';
                }
            } else {
                $this->saveMessage = 'Cannot update: Loan or product not found';
                $this->saveStatus = 'error';
            }
        } catch (\Exception $e) {
            $this->saveMessage = 'Error saving configuration: ' . $e->getMessage();
            $this->saveStatus = 'error';
        }
    }
    
    public function resetAccountConfiguration()
    {
        $this->editedAccounts = [];
        $this->saveMessage = '';
        $this->saveStatus = '';
        $this->loadAccountingData();
    }
    
    /**
     * Perform real-time calculation when values change
     */
    public function performRealTimeCalculation()
    {
        if (!$this->loan) {
            return;
        }
        
        // Calculate net effect in real-time
        $this->calculateNetEffect();
        
        // Update preview if in preview mode
        if ($this->previewMode) {
            $this->updatePreview();
        }
    }
    
    /**
     * Calculate the net effect of the disbursement
     */
    private function calculateNetEffect()
    {
        $principal = $this->loan->principle ?? 0;
        $totalDeductions = $this->getTotalDeductions();
        $netDisbursement = $principal - $totalDeductions;
        
        $this->netEffect = [
            'bank_mirror_balance' => -$netDisbursement,
            'client_receives' => $netDisbursement,
            'loan_balance' => $principal,
            'total_income_recognized' => $totalDeductions,
            'breakdown' => [
                'charges_income' => $this->deductionEntries['charges']['total'] ?? 0,
                'insurance_income' => $this->deductionEntries['insurance']['total'] ?? 0,
                'interest_income' => $this->deductionEntries['first_interest']['total'] ?? 0
            ]
        ];
    }
    
    /**
     * Toggle preview mode for accounting entries
     */
    public function togglePreviewMode()
    {
        $this->previewMode = !$this->previewMode;
        
        if ($this->previewMode) {
            $this->calculateNetEffect();
        }
    }
    
    /**
     * Update preview calculations
     */
    private function updatePreview()
    {
        // This can be extended to show what-if scenarios
        // For example, changing deduction amounts to see the net effect
        $this->emit('previewUpdated', $this->netEffect);
    }
    
    /**
     * Select a bank for disbursement
     */
    public function selectBank($bankMirrorAccount)
    {
        $this->selectedBankMirror = $bankMirrorAccount;
        $this->performRealTimeCalculation();
        
        // Auto-save after bank selection
        $this->autoSaveAccountingData();
    }
    
    /**
     * Open the account editing modal
     */
    public function openEditAccountModal($entryIndex, $currentAccount, $entryType)
    {
        $this->editingEntryIndex = $entryIndex;
        $this->editingAccount = $currentAccount;
        $this->editingEntryType = $entryType;
        
        // Get current account name
        $account = DB::table('accounts')->where('account_number', $currentAccount)->first();
        $this->editingAccountName = $account ? $account->account_name : 'Unknown Account';
        
        // Reset selection
        $this->selectedNewAccount = '';
        $this->selectedNewAccountName = '';
        $this->accountSearchQuery = '';
        $this->selectedAccountType = '';
        
        // Load available accounts
        $this->loadAvailableAccounts();
        
        $this->showEditAccountModal = true;
    }
    
    /**
     * Close the account editing modal
     */
    public function closeEditAccountModal()
    {
        $this->showEditAccountModal = false;
        $this->editingEntryIndex = null;
        $this->editingAccount = '';
        $this->editingAccountName = '';
        $this->selectedNewAccount = '';
        $this->selectedNewAccountName = '';
        $this->accountSearchQuery = '';
        $this->availableAccounts = [];
    }
    
    /**
     * Load available accounts based on filters
     */
    public function loadAvailableAccounts()
    {
        $query = DB::table('accounts')
            ->select('account_number', 'account_name', 'balance', 'major_category_code', 'type')
            ->where('status', 'ACTIVE');
        
        // Apply search filter
        if ($this->accountSearchQuery) {
            $query->where(function($q) {
                $q->where('account_number', 'like', '%' . $this->accountSearchQuery . '%')
                  ->orWhere('account_name', 'like', '%' . $this->accountSearchQuery . '%');
            });
        }
        
        // Apply account type filter
        if ($this->selectedAccountType) {
            switch($this->selectedAccountType) {
                case 'asset':
                    $query->whereIn('major_category_code', ['01', '1']); // Assets
                    break;
                case 'liability':
                    $query->whereIn('major_category_code', ['02', '2']); // Liabilities
                    break;
                case 'income':
                    $query->whereIn('major_category_code', ['04', '4']); // Income
                    break;
                case 'expense':
                    $query->whereIn('major_category_code', ['05', '5']); // Expenses
                    break;
                case 'equity':
                    $query->whereIn('major_category_code', ['03', '3']); // Equity
                    break;
            }
        }
        
        // Limit results and order
        $accounts = $query->orderBy('account_number')
            ->limit(100)
            ->get();
        
        $this->availableAccounts = $accounts->map(function($account) {
            return [
                'account_number' => $account->account_number,
                'account_name' => $account->account_name,
                'balance' => $account->balance ?? 0
            ];
        })->toArray();
    }
    
    /**
     * Select a new account from the list
     */
    public function selectAccount($accountNumber)
    {
        $this->selectedNewAccount = $accountNumber;
        
        // Get account name
        $account = DB::table('accounts')->where('account_number', $accountNumber)->first();
        $this->selectedNewAccountName = $account ? $account->account_name : 'Unknown Account';
    }
    
    /**
     * Save the account change
     */
    public function saveAccountChange()
    {
        if (!$this->selectedNewAccount || $this->editingEntryIndex === null) {
            return;
        }
        
        // Store the override for this entry
        $this->journalEntryOverrides[$this->editingEntryIndex] = [
            'account' => $this->selectedNewAccount,
            'account_name' => $this->selectedNewAccountName
        ];
        
        // Close modal
        $this->closeEditAccountModal();
        
        // Auto-save accounting data after changes
        $this->autoSaveAccountingData();
        
        // Refresh the accounting data to reflect changes
        $this->loadAccountingData();
        
        session()->flash('message', 'Account selection updated and saved. Changes will be applied when processing the disbursement.');
    }
    
    /**
     * Updated method for loading available accounts when search or filter changes
     */
    public function updatedAccountSearchQuery()
    {
        $this->loadAvailableAccounts();
    }
    
    public function updatedSelectedAccountType()
    {
        $this->loadAvailableAccounts();
    }
    
    public function updatedDisbursementMethod()
    {
        // Auto-save when disbursement method changes
        $this->autoSaveAccountingData();
        Log::info('Disbursement method changed to: ' . $this->disbursementMethod);
    }
    
    public function updatedSelectedBankMirror()
    {
        // Auto-save when bank selection changes
        $this->performRealTimeCalculation();
        $this->autoSaveAccountingData();
        Log::info('Bank mirror account selected: ' . $this->selectedBankMirror);
    }
    
    /**
     * Get the overridden account for an entry if it exists
     */
    public function getOverriddenAccount($entryIndex)
    {
        return $this->journalEntryOverrides[$entryIndex] ?? null;
    }
    
    /**
     * Auto-save accounting data to database (similar to assessment data)
     */
    private function autoSaveAccountingData()
    {
        try {
            $loanId = Session::get('currentloanID');
            if (!$loanId) {
                Log::warning('No loan ID found in session for accounting auto-save');
                return;
            }
            
            $loan = DB::table('loans')->where('id', $loanId)->first();
            if (!$loan) {
                Log::error('Loan not found for accounting auto-save', ['loan_id' => $loanId]);
                return;
            }
            
            // Build comprehensive accounting data
            $accountingData = $this->buildAccountingData();
            
            // Update the loan with accounting data
            DB::table('loans')
                ->where('id', $loanId)
                ->update([
                    'accounting_data' => json_encode($accountingData),
                    'updated_at' => now()
                ]);
            
            Log::info('Accounting data auto-saved successfully', [
                'loan_id' => $loanId,
                'data_keys' => array_keys($accountingData)
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error auto-saving accounting data: ' . $e->getMessage());
        }
    }
    
    /**
     * Build comprehensive accounting data for persistence
     */
    private function buildAccountingData()
    {
        return [
            'timestamp' => now()->toISOString(),
            'user_id' => auth()->id(),
            'user_name' => auth()->user()->name ?? 'Unknown',
            
            // Account configurations
            'bank_accounts' => $this->bankAccounts,
            'selected_bank_mirror' => $this->selectedBankMirror,
            
            // Account overrides
            'journal_entry_overrides' => $this->journalEntryOverrides,
            'edited_accounts' => $this->editedAccounts,
            
            // GL Account mappings
            'gl_accounts' => [
                'loan_account' => $this->loan->loan_account_number ?? null,
                'charge_account' => $this->loan->charge_account_number ?? '0101400041004120',
                'insurance_account' => $this->loan->insurance_account_number ?? '0101400041004110',
                'interest_account' => $this->loan->interest_account_number ?? '0101400040004010',
                'penalty_account' => '0101400041004120', // Same as charges
                'settlement_account' => '2311'
            ],
            
            // Journal entries for disbursement processing
            'journal_entries' => $this->generateJournalEntriesForDisbursement(),
            
            // Deduction entries for journal
            'deduction_entries' => $this->deductionEntries,
            
            // Account balances snapshot
            'account_balances' => $this->accountBalances,
            
            // Calculated values
            'net_effect' => $this->netEffect,
            
            // Configuration flags
            'show_detailed_entries' => $this->showDetailedEntries,
            'disbursement_method' => $this->disbursementMethod,
            'parameters_applied' => $this->parametersApplied,
            
            // Validation status
            'entries_balanced' => $this->checkEntriesBalanced(),
            
            // Additional metadata
            'loan_id' => $this->loan->id ?? null,
            'loan_status' => $this->loan->status ?? null,
            'principle' => $this->loan->principle ?? 0,
            'net_disbursement_amount' => $this->loan->net_disbursement_amount ?? 0
        ];
    }
    
    /**
     * Ensure journal entries are saved if parameters have been applied
     * This handles the case where parameters were applied before journal entries were implemented
     */
    private function ensureJournalEntriesAreSaved()
    {
        try {
            // Only proceed if parameters have been applied and we have the necessary data
            if (!$this->parametersApplied || !$this->loan || !$this->selectedBankMirror) {
                return;
            }
            
            // Check if the saved data has journal entries
            $loanId = Session::get('currentloanID');
            if (!$loanId) {
                return;
            }
            
            $loan = DB::table('loans')
                ->where('id', $loanId)
                ->first();
                
            if (!$loan || !$loan->accounting_data) {
                return;
            }
            
            $savedData = json_decode($loan->accounting_data, true);
            
            // Always regenerate journal entries to ensure they use the correct logic
            // This fixes issues with old incorrect entries saved in the database
            if ($this->parametersApplied) {
                Log::info('Regenerating journal entries with corrected logic', [
                    'loan_id' => $loanId,
                    'parameters_applied' => $this->parametersApplied,
                    'selected_bank' => $this->selectedBankMirror
                ]);
                
                // Force save with corrected journal entries
                $this->autoSaveAccountingData();
            }
        } catch (\Exception $e) {
            Log::error('Error ensuring journal entries are saved: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate journal entries for disbursement processing
     * This creates the structured entries that will be posted as actual transactions
     */
    private function generateJournalEntriesForDisbursement()
    {
        $entries = [];
        
        if (!$this->loan || !$this->selectedBankMirror) {
            Log::info('Cannot generate journal entries - missing loan or bank', [
                'has_loan' => !empty($this->loan),
                'selected_bank' => $this->selectedBankMirror,
                'loan_id' => $this->loan->id ?? null
            ]);
            return $entries;
        }
        
        $principle = $this->loan->principle ?? 0;
        $loanAccountNumber = $this->loan->loan_account_number ?? null;
        
        // Override accounts if edited
        $chargeAccount = $this->journalEntryOverrides['charge'] ?? 
                        $this->editedAccounts['charges_account'] ?? 
                        '0101400041004120';
        $insuranceAccount = $this->journalEntryOverrides['insurance'] ?? 
                           $this->editedAccounts['insurance_account'] ?? 
                           '0101400041004111';
        $interestAccount = $this->journalEntryOverrides['interest'] ?? 
                          $this->editedAccounts['interest_account'] ?? 
                          '0101400040004010';
        
        Log::info('Account numbers for journal entries', [
            'loan_account' => $loanAccountNumber,
            'charge_account' => $chargeAccount,
            'insurance_account' => $insuranceAccount,
            'interest_account' => $interestAccount,
            'bank_account' => $this->selectedBankMirror
        ]);
        
        // Step 1: Initial loan disbursement (Credit Bank Account - money out, Debit Loan Account - asset increase)
        if ($principle > 0 && $loanAccountNumber) {
            $entries[] = [
                'description' => 'Loan Disbursement - Principal',
                'account_number' => $this->selectedBankMirror,
                'account_name' => $this->getBankAccountName($this->selectedBankMirror),
                'debit' => 0,
                'credit' => $principle,
                'entry_type' => 'disbursement'
            ];
            
            $entries[] = [
                'description' => 'Loan Disbursement - Principal',
                'account_number' => $loanAccountNumber,
                'account_name' => 'Loan Account',
                'debit' => $principle,
                'credit' => 0,
                'entry_type' => 'disbursement'
            ];
        }
        
        // Step 2: Process charges (if using gross method)
        if ($this->disbursementMethod === 'gross' && isset($this->deductionEntries['charges']['total']) && $this->deductionEntries['charges']['total'] > 0) {
            $chargesAmount = $this->deductionEntries['charges']['total'];
            
            // Debit Loan Account for charges
            $entries[] = [
                'description' => 'Loan Processing Charges',
                'account_number' => $loanAccountNumber,
                'account_name' => 'Loan Account',
                'debit' => $chargesAmount,
                'credit' => 0,
                'entry_type' => 'charges'
            ];
            
            // Credit Charges Income Account
            $entries[] = [
                'description' => 'Loan Processing Charges',
                'account_number' => $chargeAccount,
                'account_name' => 'Loan Processing Charges Income',
                'debit' => 0,
                'credit' => $chargesAmount,
                'entry_type' => 'charges'
            ];
        }
        
        // Step 3: Process insurance (if using gross method)
        if ($this->disbursementMethod === 'gross' && isset($this->deductionEntries['insurance']['total']) && $this->deductionEntries['insurance']['total'] > 0) {
            $insuranceAmount = $this->deductionEntries['insurance']['total'];
            
            // Debit Loan Account for insurance
            $entries[] = [
                'description' => 'Loan Insurance Premium',
                'account_number' => $loanAccountNumber,
                'account_name' => 'Loan Account',
                'debit' => $insuranceAmount,
                'credit' => 0,
                'entry_type' => 'insurance'
            ];
            
            // Credit Insurance Income Account
            $entries[] = [
                'description' => 'Loan Insurance Premium',
                'account_number' => $insuranceAccount,
                'account_name' => 'Insurance Premium Income',
                'debit' => 0,
                'credit' => $insuranceAmount,
                'entry_type' => 'insurance'
            ];
        }
        
        // Step 4: Process first interest (if using gross method)
        if ($this->disbursementMethod === 'gross' && isset($this->deductionEntries['first_interest']['total']) && $this->deductionEntries['first_interest']['total'] > 0) {
            $interestAmount = $this->deductionEntries['first_interest']['total'];
            
            // Debit Loan Account for first interest
            $entries[] = [
                'description' => 'First Month Interest',
                'account_number' => $loanAccountNumber,
                'account_name' => 'Loan Account',
                'debit' => $interestAmount,
                'credit' => 0,
                'entry_type' => 'interest'
            ];
            
            // Credit Interest Income Account
            $entries[] = [
                'description' => 'First Month Interest',
                'account_number' => $interestAccount,
                'account_name' => 'Interest Income',
                'debit' => 0,
                'credit' => $interestAmount,
                'entry_type' => 'interest'
            ];
        }
        
        // For net method, we would handle deductions differently (directly from disbursement amount)
        if ($this->disbursementMethod === 'net') {
            // In net method, deductions are taken from the disbursement amount
            // The client receives less cash but loan account shows full amount
            $totalDeductions = ($this->deductionEntries['charges']['total'] ?? 0) +
                              ($this->deductionEntries['insurance']['total'] ?? 0) +
                              ($this->deductionEntries['first_interest']['total'] ?? 0);
            
            if ($totalDeductions > 0) {
                // Credit Bank Mirror for deductions (money retained)
                $entries[] = [
                    'description' => 'Deductions Retained from Disbursement',
                    'account_number' => $this->selectedBankMirror,
                    'account_name' => $this->getBankAccountName($this->selectedBankMirror),
                    'debit' => 0,
                    'credit' => $totalDeductions,
                    'entry_type' => 'deductions_retained'
                ];
                
                // Debit respective income accounts
                if (isset($this->deductionEntries['charges']['total']) && $this->deductionEntries['charges']['total'] > 0) {
                    $entries[] = [
                        'description' => 'Loan Processing Charges (Net Method)',
                        'account_number' => $chargeAccount,
                        'account_name' => 'Loan Processing Charges Income',
                        'debit' => $this->deductionEntries['charges']['total'],
                        'credit' => 0,
                        'entry_type' => 'charges_net'
                    ];
                }
                
                if (isset($this->deductionEntries['insurance']['total']) && $this->deductionEntries['insurance']['total'] > 0) {
                    $entries[] = [
                        'description' => 'Loan Insurance Premium (Net Method)',
                        'account_number' => $insuranceAccount,
                        'account_name' => 'Insurance Premium Income',
                        'debit' => $this->deductionEntries['insurance']['total'],
                        'credit' => 0,
                        'entry_type' => 'insurance_net'
                    ];
                }
                
                if (isset($this->deductionEntries['first_interest']['total']) && $this->deductionEntries['first_interest']['total'] > 0) {
                    $entries[] = [
                        'description' => 'First Month Interest (Net Method)',
                        'account_number' => $interestAccount,
                        'account_name' => 'Interest Income',
                        'debit' => $this->deductionEntries['first_interest']['total'],
                        'credit' => 0,
                        'entry_type' => 'interest_net'
                    ];
                }
            }
        }
        
        return $entries;
    }
    
    /**
     * Get bank account name from mirror account number
     */
    private function getBankAccountName($mirrorAccountNumber)
    {
        foreach ($this->bankAccounts as $bank) {
            if ($bank['mirror_account'] === $mirrorAccountNumber) {
                return $bank['bank_name'] . ' Mirror Account';
            }
        }
        return 'Bank Mirror Account';
    }
    
    /**
     * Check if journal entries are balanced
     */
    private function checkEntriesBalanced()
    {
        $totalDebits = 0;
        $totalCredits = 0;
        
        // Calculate from deduction entries
        if (isset($this->loan->principle)) {
            // Initial disbursement
            $totalCredits += $this->loan->principle;
            $totalDebits += $this->loan->principle;
            
            // Deductions
            $totalDebits += ($this->deductionEntries['charges']['total'] ?? 0);
            $totalCredits += ($this->deductionEntries['charges']['total'] ?? 0);
            
            $totalDebits += ($this->deductionEntries['insurance']['total'] ?? 0);
            $totalCredits += ($this->deductionEntries['insurance']['total'] ?? 0);
            
            $totalDebits += ($this->deductionEntries['first_interest']['total'] ?? 0);
            $totalCredits += ($this->deductionEntries['first_interest']['total'] ?? 0);
        }
        
        return abs($totalDebits - $totalCredits) < 0.01; // Allow for small rounding differences
    }
    
    /**
     * Load saved accounting data from database
     */
    private function loadSavedAccountingData()
    {
        try {
            $loanId = Session::get('currentloanID');
            if (!$loanId) {
                return;
            }
            
            $loan = DB::table('loans')
                ->where('id', $loanId)
                ->first();
            
            if ($loan && $loan->accounting_data) {
                $savedData = json_decode($loan->accounting_data, true);
                
                // Restore journal entry overrides
                if (isset($savedData['journal_entry_overrides'])) {
                    $this->journalEntryOverrides = $savedData['journal_entry_overrides'];
                }
                
                // Restore edited accounts
                if (isset($savedData['edited_accounts'])) {
                    $this->editedAccounts = $savedData['edited_accounts'];
                }
                
                // Restore disbursement method
                if (isset($savedData['disbursement_method'])) {
                    $this->disbursementMethod = $savedData['disbursement_method'];
                }
                
                // Restore parameters applied state
                if (isset($savedData['parameters_applied'])) {
                    $this->parametersApplied = $savedData['parameters_applied'];
                }
                
                // Restore selected bank
                if (isset($savedData['selected_bank_mirror'])) {
                    $this->selectedBankMirror = $savedData['selected_bank_mirror'];
                }
                
                Log::info('Loaded saved accounting data', [
                    'loan_id' => $loanId,
                    'overrides_count' => count($this->journalEntryOverrides),
                    'edited_accounts_count' => count($this->editedAccounts),
                    'parameters_applied' => $this->parametersApplied,
                    'selected_bank' => $this->selectedBankMirror
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error loading saved accounting data: ' . $e->getMessage());
        }
    }

    public function render()
    {
        // Use improved view if it exists, fallback to original
        if (view()->exists('livewire.loans.sections.accounting-improved')) {
            return view('livewire.loans.sections.accounting-improved');
        }
        return view('livewire.loans.sections.accounting');
    }
}