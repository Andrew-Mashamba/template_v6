<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class FinancialReportingService
{
    protected $reportTypes = [
        'statement_of_financial_position' => [
            'id' => 37,
            'name' => 'Statement of Financial Position',
            'description' => 'Statement of Financial Position for the Month Ended',
            'template' => 'pdf.statement-of-financial-position',
            'category' => 'regulatory'
        ],
        'statement_of_comprehensive_income' => [
            'id' => 38,
            'name' => 'Statement of Comprehensive Income',
            'description' => 'Statement of Comprehensive Income for the Month Ended',
            'template' => 'pdf.statement-of-comprehensive-income',
            'category' => 'regulatory'
        ],
        'sectoral_classification_of_loans' => [
            'id' => 39,
            'name' => 'Sectoral Classification of Loans',
            'description' => 'Sectoral Classification of Loans',
            'template' => 'pdf.sectoral-classification-of-loans',
            'category' => 'regulatory'
        ],
        'balance_sheet' => [
            'id' => 1,
            'name' => 'Balance Sheet',
            'description' => 'Detailed Balance Sheet Report',
            'template' => 'pdf.balance-sheet',
            'category' => 'general'
        ],
        'profit_and_loss' => [
            'id' => 2,
            'name' => 'Profit & Loss Statement',
            'description' => 'Profit and Loss Statement',
            'template' => 'pdf.profit-and-loss',
            'category' => 'general'
        ],
        'cash_flow_statement' => [
            'id' => 3,
            'name' => 'Cash Flow Statement',
            'description' => 'Statement of Cash Flows',
            'template' => 'pdf.cash-flow-statement',
            'category' => 'general'
        ],
        'trial_balance' => [
            'id' => 4,
            'name' => 'Trial Balance',
            'description' => 'Trial Balance Report',
            'template' => 'pdf.trial-balance',
            'category' => 'general'
        ],
        'general_ledger' => [
            'id' => 5,
            'name' => 'General Ledger',
            'description' => 'General Ledger Report',
            'template' => 'pdf.general-ledger',
            'category' => 'general'
        ]
    ];

    protected $currencies = ['TZS', 'USD', 'EUR'];
    protected $periods = ['monthly', 'quarterly', 'annually', 'custom'];
    protected $formats = ['detailed', 'summary', 'comparative'];

    /**
     * Get all available report types
     */
    public function getReportTypes(): array
    {
        return $this->reportTypes;
    }

    /**
     * Get report types by category
     */
    public function getReportsByCategory(string $category): array
    {
        return array_filter($this->reportTypes, function($report) use ($category) {
            return $report['category'] === $category;
        });
    }

    /**
     * Get regulatory reports (BOT compliance)
     */
    public function getRegulatoryReports(): array
    {
        return $this->getReportsByCategory('regulatory');
    }

    /**
     * Get general reports
     */
    public function getGeneralReports(): array
    {
        return $this->getReportsByCategory('general');
    }

    /**
     * Generate financial data for any report type
     */
    public function generateFinancialData(string $reportType, array $params = []): array
    {
        $startDate = $params['startDate'] ?? Carbon::now()->startOfMonth()->format('Y-m-d');
        $endDate = $params['endDate'] ?? Carbon::now()->endOfMonth()->format('Y-m-d');
        $currency = $params['currency'] ?? 'TZS';

        switch ($reportType) {
            case 'statement_of_financial_position':
                return $this->generateBalanceSheetData($startDate, $endDate, $currency);
            
            case 'statement_of_comprehensive_income':
                return $this->generateIncomeStatementData($startDate, $endDate, $currency);
            
            case 'sectoral_classification_of_loans':
                return $this->generateSectoralLoansData($startDate, $endDate, $currency);
            
            case 'balance_sheet':
                return $this->generateBalanceSheetData($startDate, $endDate, $currency);
            
            case 'profit_and_loss':
                return $this->generateIncomeStatementData($startDate, $endDate, $currency);
            
            case 'cash_flow_statement':
                return $this->generateCashFlowData($startDate, $endDate, $currency);
            
            case 'trial_balance':
                return $this->generateTrialBalanceData($startDate, $endDate, $currency);
            
            case 'general_ledger':
                return $this->generateGeneralLedgerData($startDate, $endDate, $currency, $params);
            
            default:
                throw new \Exception("Unsupported report type: {$reportType}");
        }
    }

    /**
     * Generate Balance Sheet data (Assets, Liabilities, Equity)
     */
    protected function generateBalanceSheetData(string $startDate, string $endDate, string $currency): array
    {
        $assets = DB::table('accounts')
            ->where('major_category_code', '1000')
            ->select('account_name', 'balance', 'account_number')
            ->get();

        $liabilities = DB::table('accounts')
            ->where('major_category_code', '2000')
            ->select('account_name', 'balance', 'account_number')
            ->get();

        $equity = DB::table('accounts')
            ->where('major_category_code', '3000')
            ->select('account_name', 'balance', 'account_number')
            ->get();

        return [
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'totalAssets' => $assets->sum('balance'),
            'totalLiabilities' => $liabilities->sum('balance'),
            'totalEquity' => $equity->sum('balance'),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currency' => $currency,
            'reportDate' => now()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate Income Statement data (Income, Expenses)
     */
    protected function generateIncomeStatementData(string $startDate, string $endDate, string $currency): array
    {
        // Income (4000 series)
        $income = DB::table('accounts')
            ->where('major_category_code', '4000')
            ->select('account_name', 'balance', 'account_number', 'sub_category_code')
            ->get();

        // Expenses (5000 series)
        $expenses = DB::table('accounts')
            ->where('major_category_code', '5000')
            ->select('account_name', 'balance', 'account_number', 'sub_category_code')
            ->get();

        $totalIncome = $income->sum('balance');
        $totalExpenses = $expenses->sum('balance');
        $netIncome = $totalIncome - $totalExpenses;

        return [
            'income' => $income,
            'expenses' => $expenses,
            'totalIncome' => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'netIncome' => $netIncome,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currency' => $currency,
            'reportDate' => now()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate Sectoral Classification of Loans data
     */
    protected function generateSectoralLoansData(string $startDate, string $endDate, string $currency): array
    {
        $loans = DB::table('loans')
            ->join('clients', 'loans.client_id', '=', 'clients.id')
            ->leftJoin('loan_sub_products', 'loans.loan_sub_product', '=', 'loan_sub_products.sub_product_id')
            ->select(
                'loans.*',
                'clients.first_name',
                'clients.middle_name', 
                'clients.last_name',
                'clients.business_licence_number',
                'loan_sub_products.sub_product_name'
            )
            ->whereBetween('loans.loan_release_date', [$startDate, $endDate])
            ->get();

        // Group by sectors
        $sectoralData = $this->groupLoansBySector($loans);

        return [
            'loans' => $loans,
            'sectoralData' => $sectoralData,
            'totalLoans' => $loans->sum('principle'),
            'totalCount' => $loans->count(),
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currency' => $currency,
            'reportDate' => now()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate Cash Flow data
     */
    protected function generateCashFlowData(string $startDate, string $endDate, string $currency): array
    {
        // This would need to be implemented based on your cash flow requirements
        $operatingActivities = collect();
        $investingActivities = collect();
        $financingActivities = collect();

        return [
            'operatingActivities' => $operatingActivities,
            'investingActivities' => $investingActivities,
            'financingActivities' => $financingActivities,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currency' => $currency,
            'reportDate' => now()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate Trial Balance data
     */
    protected function generateTrialBalanceData(string $startDate, string $endDate, string $currency): array
    {
        $accounts = DB::table('accounts')
            ->select('account_name', 'account_number', 'balance', 'major_category_code', 'sub_category_code')
            ->orderBy('account_number')
            ->get();

        $totalDebits = $accounts->where('balance', '>', 0)->sum('balance');
        $totalCredits = $accounts->where('balance', '<', 0)->sum(function($account) {
            return abs($account->balance);
        });

        return [
            'accounts' => $accounts,
            'totalDebits' => $totalDebits,
            'totalCredits' => $totalCredits,
            'isBalanced' => abs($totalDebits - $totalCredits) < 0.01,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currency' => $currency,
            'reportDate' => now()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Generate General Ledger data
     */
    protected function generateGeneralLedgerData(string $startDate, string $endDate, string $currency, array $params): array
    {
        $accountFilter = $params['account_id'] ?? null;
        
        $query = DB::table('general_ledger')
            ->join('accounts', 'general_ledger.account_id', '=', 'accounts.id')
            ->select('general_ledger.*', 'accounts.account_name', 'accounts.account_number')
            ->whereBetween('general_ledger.created_at', [$startDate, $endDate]);

        if ($accountFilter) {
            $query->where('general_ledger.account_id', $accountFilter);
        }

        $transactions = $query->orderBy('general_ledger.created_at')->get();

        return [
            'transactions' => $transactions,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'currency' => $currency,
            'reportDate' => now()->format('Y-m-d H:i:s')
        ];
    }

    /**
     * Export report to PDF
     */
    public function exportToPDF(string $reportType, array $data): \Illuminate\Http\Response
    {
        $reportConfig = $this->reportTypes[$reportType] ?? null;
        
        if (!$reportConfig) {
            throw new \Exception("Report type '{$reportType}' not found");
        }

        $pdf = PDF::loadView($reportConfig['template'], $data);
        $filename = strtolower(str_replace(' ', '-', $reportConfig['name'])) . '-' . now()->format('Y-m-d') . '.pdf';
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Export report to Excel/CSV
     */
    public function exportToExcel(string $reportType, array $data): \Illuminate\Http\Response
    {
        $reportConfig = $this->reportTypes[$reportType] ?? null;
        
        if (!$reportConfig) {
            throw new \Exception("Report type '{$reportType}' not found");
        }

        try {
            $csvContent = $this->generateCSVContent($reportType, $data);
            $filename = strtolower(str_replace(' ', '-', $reportConfig['name'])) . '-' . now()->format('Y-m-d') . '.csv';
            
            return response()->streamDownload(function () use ($csvContent) {
                echo $csvContent;
            }, $filename, ['Content-Type' => 'text/csv']);
            
        } catch (\Exception $e) {
            throw new \Exception('Error generating Excel: ' . $e->getMessage());
        }
    }

    /**
     * Schedule a report for automated generation
     */
    public function scheduleReport(string $reportType, array $config, int $userId): bool
    {
        try {
            DB::table('scheduled_reports')->insert([
                'report_type' => $reportType,
                'report_config' => json_encode($config),
                'user_id' => $userId,
                'status' => 'scheduled',
                'created_at' => now(),
                'updated_at' => now()
            ]);
            
            return true;
        } catch (\Exception $e) {
            throw new \Exception('Error scheduling report: ' . $e->getMessage());
        }
    }

    /**
     * Get report history for a user
     */
    public function getReportHistory(int $userId, int $limit = 50): Collection
    {
        return DB::table('scheduled_reports')
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Generate CSV content based on report type
     */
    protected function generateCSVContent(string $reportType, array $data): string
    {
        $reportConfig = $this->reportTypes[$reportType];
        $csv = "{$reportConfig['name']}\n";
        $csv .= "Period: {$data['startDate']} to {$data['endDate']}\n";
        $csv .= "Currency: {$data['currency']}\n\n";

        switch ($reportType) {
            case 'statement_of_financial_position':
            case 'balance_sheet':
                return $this->generateBalanceSheetCSV($data, $csv);
            
            case 'statement_of_comprehensive_income':
            case 'profit_and_loss':
                return $this->generateIncomeStatementCSV($data, $csv);
            
            default:
                return $csv . "CSV export not implemented for this report type.\n";
        }
    }

    /**
     * Generate Balance Sheet CSV
     */
    protected function generateBalanceSheetCSV(array $data, string $csv): string
    {
        // Assets
        $csv .= "ASSETS\n";
        $csv .= "Account Name,Balance\n";
        foreach ($data['assets'] as $asset) {
            $accountName = $this->getProperty($asset, 'account_name');
            $balance = $this->getProperty($asset, 'balance');
            $csv .= "\"{$accountName}\",{$balance}\n";
        }
        $csv .= "TOTAL ASSETS,{$data['totalAssets']}\n\n";

        // Liabilities
        $csv .= "LIABILITIES\n";
        $csv .= "Account Name,Balance\n";
        foreach ($data['liabilities'] as $liability) {
            $accountName = $this->getProperty($liability, 'account_name');
            $balance = $this->getProperty($liability, 'balance');
            $csv .= "\"{$accountName}\",{$balance}\n";
        }
        $csv .= "TOTAL LIABILITIES,{$data['totalLiabilities']}\n\n";

        // Equity
        $csv .= "EQUITY\n";
        $csv .= "Account Name,Balance\n";
        foreach ($data['equity'] as $equity) {
            $accountName = $this->getProperty($equity, 'account_name');
            $balance = $this->getProperty($equity, 'balance');
            $csv .= "\"{$accountName}\",{$balance}\n";
        }
        $csv .= "TOTAL EQUITY,{$data['totalEquity']}\n\n";
        
        $csv .= "TOTAL LIABILITIES AND EQUITY," . ($data['totalLiabilities'] + $data['totalEquity']) . "\n";
        
        return $csv;
    }

    /**
     * Generate Income Statement CSV
     */
    protected function generateIncomeStatementCSV(array $data, string $csv): string
    {
        // Income
        $csv .= "INCOME\n";
        $csv .= "Account Name,Amount\n";
        foreach ($data['income'] as $income) {
            $accountName = $this->getProperty($income, 'account_name');
            $balance = $this->getProperty($income, 'balance');
            $csv .= "\"{$accountName}\",{$balance}\n";
        }
        $csv .= "TOTAL INCOME,{$data['totalIncome']}\n\n";

        // Expenses
        $csv .= "EXPENSES\n";
        $csv .= "Account Name,Amount\n";
        foreach ($data['expenses'] as $expense) {
            $accountName = $this->getProperty($expense, 'account_name');
            $balance = $this->getProperty($expense, 'balance');
            $csv .= "\"{$accountName}\",{$balance}\n";
        }
        $csv .= "TOTAL EXPENSES,{$data['totalExpenses']}\n\n";
        
        $csv .= "NET INCOME,{$data['netIncome']}\n";
        
        return $csv;
    }

    /**
     * Group loans by economic sectors
     */
    protected function groupLoansBySector(Collection $loans): array
    {
        // This would implement sector classification logic
        // For now, return a basic structure
        return [
            'agriculture' => $loans->where('business_licence_number', 'like', 'AGR%'),
            'manufacturing' => $loans->where('business_licence_number', 'like', 'MAN%'),
            'services' => $loans->where('business_licence_number', 'like', 'SER%'),
            'trade' => $loans->where('business_licence_number', 'like', 'TRA%'),
            'other' => $loans->where('business_licence_number', 'not like', 'AGR%')
                          ->where('business_licence_number', 'not like', 'MAN%')
                          ->where('business_licence_number', 'not like', 'SER%')
                          ->where('business_licence_number', 'not like', 'TRA%'),
        ];
    }

    /**
     * Get available currencies
     */
    public function getCurrencies(): array
    {
        return $this->currencies;
    }

    /**
     * Get available periods
     */
    public function getPeriods(): array
    {
        return $this->periods;
    }

    /**
     * Get available formats
     */
    public function getFormats(): array
    {
        return $this->formats;
    }

    /**
     * Set date range based on period type
     */
    public function setDateRangeByPeriod(string $period): array
    {
        $now = Carbon::now();
        
        switch ($period) {
            case 'monthly':
                return [
                    'startDate' => $now->startOfMonth()->format('Y-m-d'),
                    'endDate' => $now->endOfMonth()->format('Y-m-d')
                ];
            case 'quarterly':
                return [
                    'startDate' => $now->startOfQuarter()->format('Y-m-d'),
                    'endDate' => $now->endOfQuarter()->format('Y-m-d')
                ];
            case 'annually':
                return [
                    'startDate' => $now->startOfYear()->format('Y-m-d'),
                    'endDate' => $now->endOfYear()->format('Y-m-d')
                ];
            default:
                return [
                    'startDate' => $now->startOfMonth()->format('Y-m-d'),
                    'endDate' => $now->endOfMonth()->format('Y-m-d')
                ];
        }
    }

    /**
     * Helper method to safely get property from object or array
     */
    private function getProperty($item, string $property)
    {
        if (is_object($item)) {
            return $item->{$property} ?? '';
        } elseif (is_array($item)) {
            return $item[$property] ?? '';
        } else {
            return '';
        }
    }
} 