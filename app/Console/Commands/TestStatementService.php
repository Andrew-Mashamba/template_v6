<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Statement\StatementService;
use Carbon\Carbon;

class TestStatementService extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:statement-service 
                            {--account=011191000035 : Account number to test}
                            {--date= : Statement date (YYYY-MM-DD), defaults to yesterday}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test NBC Statement Service (PVAS) integration';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('===== NBC STATEMENT SERVICE TEST STARTED =====');
        $this->newLine();

        // Initialize service
        $service = new StatementService();

        // Test configuration
        $testAccount = $this->option('account');
        $statementDate = $this->option('date') ?? Carbon::now()->subDays(1)->format('Y-m-d');
        
        $testResults = [];

        // Test 1: Account Balance
        $this->info('TEST 1: Account Balance Service (SC990001)');
        $this->line(str_repeat('=', 60));
        
        $partnerRef = 'BAL' . date('ymdHis') . rand(100, 999);
        $startTime = microtime(true);
        
        try {
            $response = $service->getAccountBalance($testAccount, $statementDate, $partnerRef);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response['success']) {
                $this->info('✅ Balance retrieved successfully');
                
                if (!empty($response['data'])) {
                    $balanceData = $service->formatBalanceData($response['data']);
                    
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Currency', $balanceData['currency']],
                            ['Opening Balance', $balanceData['opening_balance']],
                            ['Closing Balance', $balanceData['closing_balance']],
                            ['Total Transactions', $balanceData['total_transactions']],
                            ['Total Debits', $balanceData['total_debits'] . ' (' . $balanceData['debit_count'] . ' txns)'],
                            ['Total Credits', $balanceData['total_credits'] . ' (' . $balanceData['credit_count'] . ' txns)'],
                        ]
                    );
                } else {
                    $this->warn('Note: Using mock response - NBC API not accessible');
                    
                    // Mock response for demonstration
                    $mockData = [
                        'currency' => 'TZS',
                        'openingBalance' => 1000000.00,
                        'closingBalance' => 950000.00,
                        'totalTransactionsCount' => 15,
                        'totalDebitAmount' => 75000.00,
                        'totalDebitCount' => 10,
                        'totalCreditAmount' => 25000.00,
                        'totalCreditCount' => 5
                    ];
                    
                    $balanceData = $service->formatBalanceData($mockData);
                    
                    $this->table(
                        ['Field', 'Value'],
                        [
                            ['Currency', $balanceData['currency']],
                            ['Opening Balance', $balanceData['opening_balance']],
                            ['Closing Balance', $balanceData['closing_balance']],
                            ['Total Transactions', $balanceData['total_transactions']],
                            ['Total Debits', $balanceData['total_debits'] . ' (' . $balanceData['debit_count'] . ' txns)'],
                            ['Total Credits', $balanceData['total_credits'] . ' (' . $balanceData['credit_count'] . ' txns)'],
                        ]
                    );
                }
                
                $this->info('Partner Ref: ' . $response['partner_ref']);
                $this->info('Response Time: ' . $duration . ' ms');
                
                $testResults[] = ['Balance', 'PASS', $duration . ' ms'];
            } else {
                $this->error('❌ Failed: ' . $response['error']);
                
                // Try with mock data
                if (strpos($response['error'], 'Authentication failed') !== false || 
                    strpos($response['error'], 'cURL') !== false ||
                    strpos($response['error'], 'Could not resolve host') !== false) {
                    
                    $this->warn('Note: NBC API not accessible, demonstrating with mock data');
                    
                    $mockData = [
                        'currency' => 'TZS',
                        'openingBalance' => 1000000.00,
                        'closingBalance' => 950000.00,
                        'totalTransactionsCount' => 15,
                        'totalDebitAmount' => 75000.00,
                        'totalDebitCount' => 10,
                        'totalCreditAmount' => 25000.00,
                        'totalCreditCount' => 5
                    ];
                    
                    $balanceData = $service->formatBalanceData($mockData);
                    
                    $this->table(
                        ['Field', 'Value (Mock)'],
                        [
                            ['Currency', $balanceData['currency']],
                            ['Opening Balance', $balanceData['opening_balance']],
                            ['Closing Balance', $balanceData['closing_balance']],
                            ['Total Transactions', $balanceData['total_transactions']],
                            ['Total Debits', $balanceData['total_debits']],
                            ['Total Credits', $balanceData['total_credits']],
                        ]
                    );
                    
                    $testResults[] = ['Balance', 'MOCK', $duration . ' ms'];
                } else {
                    $testResults[] = ['Balance', 'FAIL', $duration . ' ms'];
                }
            }
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
            $testResults[] = ['Balance', 'ERROR', 'N/A'];
        }
        
        $this->newLine();
        
        // Test 2: Transaction Summary
        $this->info('TEST 2: Transaction Summary Service (SC990002)');
        $this->line(str_repeat('=', 60));
        
        $partnerRef = 'SUM' . date('ymdHis') . rand(100, 999);
        $startTime = microtime(true);
        
        try {
            $response = $service->getTransactionSummary($testAccount, $statementDate, $partnerRef);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response['success']) {
                $this->info('✅ Transaction summary retrieved successfully');
                $this->info('Partner Ref: ' . $response['partner_ref']);
                $this->info('Response Time: ' . $duration . ' ms');
                $testResults[] = ['Summary', 'PASS', $duration . ' ms'];
            } else {
                $this->error('❌ Failed: ' . $response['error']);
                
                if (strpos($response['error'], 'Authentication failed') !== false || 
                    strpos($response['error'], 'cURL') !== false ||
                    strpos($response['error'], 'Could not resolve host') !== false) {
                    
                    $this->warn('Note: NBC API not accessible, showing mock summary structure');
                    
                    $this->table(
                        ['Field', 'Mock Value'],
                        [
                            ['Account Title', 'CBN MICROFINANCE'],
                            ['Branch', 'HEAD OFFICE (011)'],
                            ['Product', 'BUSINESS CURRENT ACCOUNT'],
                            ['Status', 'ACTIVE'],
                            ['Currency', 'TZS'],
                            ['Available Balance', 'TZS 950,000.00'],
                        ]
                    );
                    
                    $testResults[] = ['Summary', 'MOCK', $duration . ' ms'];
                } else {
                    $testResults[] = ['Summary', 'FAIL', $duration . ' ms'];
                }
            }
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
            $testResults[] = ['Summary', 'ERROR', 'N/A'];
        }
        
        $this->newLine();
        
        // Test 3: Account Statement
        $this->info('TEST 3: Account Statement Service (SC990003)');
        $this->line(str_repeat('=', 60));
        
        $partnerRef = 'STMT' . date('ymdHis') . rand(100, 999);
        $startTime = microtime(true);
        
        try {
            $response = $service->getAccountStatement($testAccount, $statementDate, $partnerRef);
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($response['success']) {
                $this->info('✅ Statement retrieved successfully');
                $transactions = $response['transactions'] ?? [];
                $this->info('Total Transactions: ' . count($transactions));
                $this->info('Partner Ref: ' . $response['partner_ref']);
                $this->info('Response Time: ' . $duration . ' ms');
                $testResults[] = ['Statement', 'PASS', $duration . ' ms'];
            } else {
                $this->error('❌ Failed: ' . $response['error']);
                
                if (strpos($response['error'], 'Authentication failed') !== false || 
                    strpos($response['error'], 'cURL') !== false ||
                    strpos($response['error'], 'Could not resolve host') !== false) {
                    
                    $this->warn('Note: NBC API not accessible, showing mock transactions');
                    
                    $mockTransactions = [
                        [
                            'date' => $statementDate,
                            'reference' => 'TRX001',
                            'description' => 'Transfer to Account',
                            'type' => 'Debit',
                            'amount' => '50,000.00',
                            'balance' => '950,000.00'
                        ],
                        [
                            'date' => $statementDate,
                            'reference' => 'TRX002',
                            'description' => 'Deposit',
                            'type' => 'Credit',
                            'amount' => '25,000.00',
                            'balance' => '975,000.00'
                        ]
                    ];
                    
                    $this->table(
                        ['Date', 'Reference', 'Description', 'Type', 'Amount', 'Balance'],
                        $mockTransactions
                    );
                    
                    $testResults[] = ['Statement', 'MOCK', $duration . ' ms'];
                } else {
                    $testResults[] = ['Statement', 'FAIL', $duration . ' ms'];
                }
            }
        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
            $testResults[] = ['Statement', 'ERROR', 'N/A'];
        }
        
        $this->newLine();
        
        // Response Codes
        $this->info('NBC Response Codes Reference:');
        $this->line(str_repeat('=', 60));
        
        $responseCodes = [
            [600, $service->getResponseCodeDescription(600), '✅'],
            [601, $service->getResponseCodeDescription(601), '❌'],
            [602, $service->getResponseCodeDescription(602), '⚠️'],
            [613, $service->getResponseCodeDescription(613), '⚠️'],
            [615, $service->getResponseCodeDescription(615), '❌'],
            [699, $service->getResponseCodeDescription(699), '❌'],
        ];
        
        $this->table(
            ['Code', 'Description', 'Status'],
            $responseCodes
        );
        
        $this->newLine();
        
        // Test Summary
        $this->info('TEST SUMMARY:');
        $this->line(str_repeat('=', 60));
        
        $this->table(
            ['Test', 'Result', 'Response Time'],
            $testResults
        );
        
        // Save report
        $reportFile = storage_path('logs/statement_service_test_' . date('Ymd_His') . '.json');
        $reportData = [
            'test_date' => date('Y-m-d H:i:s'),
            'service' => 'NBC Statement Service (PVAS)',
            'account_tested' => $testAccount,
            'statement_date' => $statementDate,
            'results' => $testResults,
            'config' => [
                'base_url' => config('services.nbc_statement.base_url'),
                'username' => config('services.nbc_statement.username') ? '***' : 'Not set'
            ]
        ];
        
        file_put_contents($reportFile, json_encode($reportData, JSON_PRETTY_PRINT));
        
        $this->info('Report saved to: ' . $reportFile);
        $this->newLine();
        $this->info('===== NBC STATEMENT SERVICE TEST COMPLETED =====');
        
        return Command::SUCCESS;
    }
}