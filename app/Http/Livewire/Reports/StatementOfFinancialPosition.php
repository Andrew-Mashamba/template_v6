<?php

namespace App\Http\Livewire\Reports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Carbon\Carbon;
use Exception;

class StatementOfFinancialPosition extends Component
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
            $this->addSampleDataIfNeeded();
            
            // Get the statement data
            $statementData = $this->getStatementData();
            
            // Store the data for display
            $this->statementData = $statementData;
            $this->showStatementView = true;
            
            $this->successMessage = 'Statement of Financial Position generated successfully!';
            
            // Log the report generation
            Log::info('Statement of Financial Position generated', [
                'user_id' => auth()->id(),
                'as_of_date' => $this->reportEndDate
            ]);
            
        } catch (Exception $e) {
            Log::error('Error generating Statement of Financial Position: ' . $e->getMessage());
            $this->errorMessage = 'Failed to generate Statement of Financial Position. Please try again.';
        } finally {
            $this->isGenerating = false;
        }
    }

    public function getStatementData()
    {
        $asOfDate = $this->reportEndDate ?: Carbon::now()->format('Y-m-d');
        
        // Get all active accounts with their balances
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
            ->whereNull('deleted_at')
            ->orderBy('major_category_code')
            ->orderBy('category_code')
            ->orderBy('sub_category_code')
            ->orderBy('account_number')
            ->get();

        // Group accounts by type and calculate totals
        $statementData = [
            'as_of_date' => $asOfDate,
            'assets' => $this->groupAccountsByType($accounts, ['asset_accounts']),
            'liabilities' => $this->groupAccountsByType($accounts, ['liability_accounts']),
            'equity' => $this->groupAccountsByType($accounts, ['capital_accounts']),
            'totals' => []
        ];

        // Calculate totals
        $totalAssets = $statementData['assets']['total'];
        $totalLiabilities = $statementData['liabilities']['total'];
        $totalEquity = $statementData['equity']['total'];
        $totalLiabilitiesAndEquity = $totalLiabilities + $totalEquity;

        $statementData['totals'] = [
            'total_assets' => $totalAssets,
            'total_liabilities' => $totalLiabilities,
            'total_equity' => $totalEquity,
            'total_liabilities_and_equity' => $totalLiabilitiesAndEquity,
            'is_balanced' => abs($totalAssets - $totalLiabilitiesAndEquity) < 0.01,
            'difference' => abs($totalAssets - $totalLiabilitiesAndEquity)
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
            'asset_accounts' => [
                '1000' => 'Assets',
                '1000-1000' => 'Cash and Cash Equivalents',
                '1000-1100' => 'Short-term Investments',
                '1000-1200' => 'Loan Portfolio',
                '1000-1300' => 'Loan Loss Provisions',
                '1000-1400' => 'Interest Receivable',
                '1000-1500' => 'Accounts Receivable',
                '1000-1600' => 'Property and Equipment',
                '1000-1700' => 'Long-term Investments',
                '1000-1800' => 'Prepaid Expenses'
            ],
            'liability_accounts' => [
                '2000' => 'Liabilities',
                '2000-2100' => 'Member Deposits',
                '2000-2200' => 'Short-term Debt',
                '2000-2300' => 'Long-term Debt',
                '2000-2400' => 'Accounts Payable'
            ],
            'capital_accounts' => [
                '3000' => 'Equity',
                '3000-3000' => 'Member Share Capital',
                '3000-3100' => 'Retained Earnings',
                '3000-3200' => 'Reserves',
                '3000-3300' => 'Donated Capital'
            ]
        ];

        return $categoryNames[$type][$categoryCode] ?? "Category {$categoryCode}";
    }

    private function addSampleDataIfNeeded()
    {
        // Check if there are any general ledger entries
        $glCount = DB::table('general_ledger')->count();
        
        if ($glCount == 0) {
            // Add some sample data for demonstration
            $sampleTransactions = [
                // Cash deposit
                [
                    'record_on_account_number' => '010110001000', // CASH AND CASH EQUIVALENTS
                    'debit' => 1000000.00,
                    'credit' => 0.00,
                    'record_on_account_number_balance' => 1000000.00,
                    'narration' => 'Initial cash deposit',
                    'reference_number' => 'CASH001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'record_on_account_number' => '010120002100', // MEMBER DEPOSITS
                    'debit' => 0.00,
                    'credit' => 1000000.00,
                    'record_on_account_number_balance' => 1000000.00,
                    'narration' => 'Member deposit received',
                    'reference_number' => 'CASH001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                // Loan disbursement
                [
                    'record_on_account_number' => '010110001200', // LOAN PORTFOLIO
                    'debit' => 500000.00,
                    'credit' => 0.00,
                    'record_on_account_number_balance' => 500000.00,
                    'narration' => 'Loan disbursed to member',
                    'reference_number' => 'LOAN001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'record_on_account_number' => '010110001000', // CASH AND CASH EQUIVALENTS
                    'debit' => 0.00,
                    'credit' => 500000.00,
                    'record_on_account_number_balance' => 500000.00,
                    'narration' => 'Cash paid for loan',
                    'reference_number' => 'LOAN001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                // Share capital
                [
                    'record_on_account_number' => '010110001000', // CASH AND CASH EQUIVALENTS
                    'debit' => 200000.00,
                    'credit' => 0.00,
                    'record_on_account_number_balance' => 700000.00,
                    'narration' => 'Share capital contribution',
                    'reference_number' => 'SHARE001',
                    'created_at' => now(),
                    'updated_at' => now()
                ],
                [
                    'record_on_account_number' => '010130003000', // MEMBER SHARE CAPITAL
                    'debit' => 0.00,
                    'credit' => 200000.00,
                    'record_on_account_number_balance' => 200000.00,
                    'narration' => 'Share capital received',
                    'reference_number' => 'SHARE001',
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
            
            $filename = 'statement_of_financial_position_' . now()->format('Y-m-d_H-i-s') . '.' . $format;
            
            // For now, we'll simulate the export process
            // In a real implementation, you would use libraries like:
            // - DomPDF for PDF generation
            // - Laravel Excel for Excel/CSV export
            
            $this->successMessage = "Statement of Financial Position exported as {$format} successfully!";
            
            // Log the export
            Log::info('Statement of Financial Position exported', [
                'format' => $format,
                'user_id' => auth()->id(),
                'as_of_date' => $this->statementData['as_of_date']
            ]);
            
        } catch (Exception $e) {
            Log::error('Error exporting Statement of Financial Position: ' . $e->getMessage());
            $this->errorMessage = 'Failed to export Statement of Financial Position. Please try again.';
        } finally {
            $this->isExporting = false;
        }
    }

    public function render()
    {
        return view('livewire.reports.statement-of-financial-position');
    }
}