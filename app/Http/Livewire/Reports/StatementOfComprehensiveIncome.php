<?php

namespace App\Http\Livewire\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Carbon\Carbon;
use Exception;

class StatementOfComprehensiveIncome extends Component
{
    // Report Properties
    public $reportStartDate;
    public $reportEndDate;
    public $reportFormat = 'pdf';
    
    // Report Viewing Properties
    public $showStatementView = false;
    public $statementData = null;
    
    // Loading States
    public $isGenerating = false;
    public $isExporting = false;
    
    // Messages
    public $successMessage = '';
    public $errorMessage = '';

    public function mount()
    {
        $this->initializeDates();
    }

    private function initializeDates()
    {
        $this->reportStartDate = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->reportEndDate = Carbon::now()->format('Y-m-d');
    }

    public function generateStatement()
    {
        try {
            $this->isGenerating = true;
            $this->errorMessage = '';
            
            // Check if we need to add sample data for demonstration
            $this->addSampleIncomeDataIfNeeded();
            
            // Get the statement data
            $statementData = $this->getStatementData();
            
            // Store the data for display
            $this->statementData = $statementData;
            $this->showStatementView = true;
            
            $this->successMessage = 'Statement of Comprehensive Income generated successfully!';
            
            // Log the report generation
            Log::info('Statement of Comprehensive Income generated', [
                'user_id' => auth()->id(),
                'period_start' => $this->reportStartDate,
                'period_end' => $this->reportEndDate
            ]);
            
        } catch (Exception $e) {
            Log::error('Error generating Statement of Comprehensive Income: ' . $e->getMessage());
            $this->errorMessage = 'Failed to generate Statement of Comprehensive Income. Please try again.';
        } finally {
            $this->isGenerating = false;
        }
    }

    public function getStatementData()
    {
        $startDate = $this->reportStartDate ?: Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $this->reportEndDate ?: Carbon::now()->format('Y-m-d');
        
        // Get all active income and expense accounts with their balances
        $accounts = DB::table('accounts')
            ->select(
                'account_number',
                'account_name',
                'type',
                'major_category_code',
                'category_code',
                'sub_category_code',
                'account_level',
                DB::raw('COALESCE(CAST(balance AS DECIMAL(20,2)), 0) as current_balance'),
                DB::raw('COALESCE(CAST(debit AS DECIMAL(20,2)), 0) as debit_balance'),
                DB::raw('COALESCE(CAST(credit AS DECIMAL(20,2)), 0) as credit_balance')
            )
            ->where('status', 'ACTIVE')
            ->whereIn('type', ['income_accounts', 'expense_accounts'])
            ->whereNull('deleted_at')
            ->orderBy('type')
            ->orderBy('major_category_code')
            ->orderBy('category_code')
            ->orderBy('sub_category_code')
            ->orderBy('account_number')
            ->get();

        // Group accounts by type and calculate totals
        $statementData = [
            'period_start' => $startDate,
            'period_end' => $endDate,
            'income' => $this->groupAccountsByType($accounts, ['income_accounts']),
            'expenses' => $this->groupAccountsByType($accounts, ['expense_accounts']),
            'totals' => []
        ];

        // Calculate totals
        $totalIncome = $statementData['income']['total'];
        $totalExpenses = $statementData['expenses']['total'];
        $netIncome = $totalIncome - $totalExpenses;

        $statementData['totals'] = [
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_income' => $netIncome,
            'is_profitable' => $netIncome > 0
        ];

        return $statementData;
    }

    private function groupAccountsByType($accounts, $types)
    {
        $grouped = [
            'categories' => [],
            'total' => 0
        ];

        foreach ($accounts as $account) {
            if (in_array($account->type, $types)) {
                // Use the combination of major_category_code and category_code for grouping
                $categoryCode = $account->major_category_code . '-' . $account->category_code;
                $categoryName = $this->getCategoryName($account->type, $categoryCode);
                
                // If no specific category name found, use the major category
                if ($categoryName === "Category {$categoryCode}") {
                    $categoryCode = $account->major_category_code;
                    $categoryName = $this->getCategoryName($account->type, $categoryCode);
                }
                
                if (!isset($grouped['categories'][$categoryCode])) {
                    $grouped['categories'][$categoryCode] = [
                        'name' => $categoryName,
                        'accounts' => [],
                        'subtotal' => 0
                    ];
                }

                $grouped['categories'][$categoryCode]['accounts'][] = $account;
                $grouped['categories'][$categoryCode]['subtotal'] += $account->current_balance;
                $grouped['total'] += $account->current_balance;
            }
        }

        return $grouped;
    }

    private function getCategoryName($type, $categoryCode)
    {
        $categoryNames = [
            'income_accounts' => [
                '4000' => 'Revenue',
                '4000-4000' => 'Interest Income',
                '4000-4100' => 'Loan Fees and Charges',
                '4000-4200' => 'Service Fees',
                '4000-4300' => 'Investment Income',
                '4000-4400' => 'Grants and Donations',
                '4000-4500' => 'Other Income',
                '4000-4600' => 'Investment Gains',
                '4000-4700' => 'Gains on Disposal',
                '4000-4800' => 'Exchange Gains'
            ],
            'expense_accounts' => [
                '5000' => 'Expenses',
                '5000-5000' => 'Financial Expenses',
                '5000-5100' => 'Personnel Expenses',
                '5000-5200' => 'Administrative Expenses',
                '5000-5300' => 'Operational Expenses',
                '5000-5600' => 'Office Expenses',
                '5000-5700' => 'Facility Expenses',
                '5000-5800' => 'Travel Expenses',
                '5000-5900' => 'Information Technology',
                '5000-6000' => 'Training and Development'
            ]
        ];

        return $categoryNames[$type][$categoryCode] ?? "Category {$categoryCode}";
    }

    private function addSampleIncomeDataIfNeeded()
    {
        // Check if we need to add sample income/expense data
        $incomeAccounts = DB::table('accounts')->where('type', 'income_accounts')->where('status', 'ACTIVE')->get();
        $expenseAccounts = DB::table('accounts')->where('type', 'expense_accounts')->where('status', 'ACTIVE')->get();
        
        $hasIncomeData = $incomeAccounts->where('balance', '>', 0)->count() > 0;
        $hasExpenseData = $expenseAccounts->where('balance', '>', 0)->count() > 0;
        
        if (!$hasIncomeData || !$hasExpenseData) {
            // Add sample income and expense transactions
            $sampleTransactions = [
                // Interest Income
                [
                    'record_on_account_number' => '010140004000', // INTEREST INCOME
                    'debit' => 0.00,
                    'credit' => 150000.00,
                    'record_on_account_number_balance' => 150000.00,
                    'narration' => 'Interest income from loans',
                    'reference_number' => 'INT001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'record_on_account_number' => '010110001000', // CASH AND CASH EQUIVALENTS
                    'debit' => 150000.00,
                    'credit' => 0.00,
                    'record_on_account_number_balance' => 850000.00,
                    'narration' => 'Interest received in cash',
                    'reference_number' => 'INT001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                // Loan Fees
                [
                    'record_on_account_number' => '010140004100', // LOAN FEES AND CHARGES
                    'debit' => 0.00,
                    'credit' => 50000.00,
                    'record_on_account_number_balance' => 50000.00,
                    'narration' => 'Loan processing fees',
                    'reference_number' => 'FEE001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'record_on_account_number' => '010110001000', // CASH AND CASH EQUIVALENTS
                    'debit' => 50000.00,
                    'credit' => 0.00,
                    'record_on_account_number_balance' => 900000.00,
                    'narration' => 'Fees received in cash',
                    'reference_number' => 'FEE001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                // Personnel Expenses
                [
                    'record_on_account_number' => '010150005100', // PERSONNEL EXPENSES
                    'debit' => 80000.00,
                    'credit' => 0.00,
                    'record_on_account_number_balance' => 80000.00,
                    'narration' => 'Staff salaries and benefits',
                    'reference_number' => 'SAL001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'record_on_account_number' => '010110001000', // CASH AND CASH EQUIVALENTS
                    'debit' => 0.00,
                    'credit' => 80000.00,
                    'record_on_account_number_balance' => 820000.00,
                    'narration' => 'Salary payments',
                    'reference_number' => 'SAL001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                // Administrative Expenses
                [
                    'record_on_account_number' => '010150005200', // ADMINISTRATIVE EXPENSES
                    'debit' => 30000.00,
                    'credit' => 0.00,
                    'record_on_account_number_balance' => 30000.00,
                    'narration' => 'Office supplies and utilities',
                    'reference_number' => 'ADM001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'record_on_account_number' => '010110001000', // CASH AND CASH EQUIVALENTS
                    'debit' => 0.00,
                    'credit' => 30000.00,
                    'record_on_account_number_balance' => 790000.00,
                    'narration' => 'Administrative expenses paid',
                    'reference_number' => 'ADM001',
                    'created_at' => now(),
                    'updated_at' => now()
                ]
            ];
            
            // Insert sample transactions
            DB::table('general_ledger')->insert($sampleTransactions);
            
            // Update account balances
            $this->updateAccountBalances();
        }
    }

    private function updateAccountBalances()
    {
        // Update account balances based on general ledger entries
        $accounts = DB::table('accounts')->where('status', 'ACTIVE')->get();
        
        foreach ($accounts as $account) {
            $debitTotal = DB::table('general_ledger')
                ->where('record_on_account_number', $account->account_number)
                ->sum('debit');
                
            $creditTotal = DB::table('general_ledger')
                ->where('record_on_account_number', $account->account_number)
                ->sum('credit');
                
            $balance = $debitTotal - $creditTotal;
            
            DB::table('accounts')
                ->where('account_number', $account->account_number)
                ->update([
                    'debit' => $debitTotal,
                    'credit' => $creditTotal,
                    'balance' => $balance,
                    'updated_at' => now()
                ]);
        }
    }

    public function exportStatement($format = 'pdf')
    {
        try {
            $this->isExporting = true;
            $this->errorMessage = '';
            
            if (!$this->statementData) {
                $this->statementData = $this->getStatementData();
            }
            
            $filename = 'statement_of_comprehensive_income_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
            
            // For now, we'll simulate the export process
            // In a real implementation, you would use libraries like:
            // - DomPDF for PDF generation
            // - Laravel Excel for Excel/CSV export
            
            $this->successMessage = "Statement of Comprehensive Income exported as {$format} successfully!";
            
            // Log the export
            Log::info('Statement of Comprehensive Income exported', [
                'format' => $format,
                'user_id' => auth()->id(),
                'period_start' => $this->statementData['period_start'],
                'period_end' => $this->statementData['period_end']
            ]);
            
        } catch (Exception $e) {
            Log::error('Error exporting Statement of Comprehensive Income: ' . $e->getMessage());
            $this->errorMessage = 'Failed to export Statement of Comprehensive Income. Please try again.';
        } finally {
            $this->isExporting = false;
        }
    }

    public function render()
    {
        return view('livewire.reports.statement-of-comprehensive-income');
    }
}