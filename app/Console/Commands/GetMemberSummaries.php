<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\McpDatabaseService;

class GetMemberSummaries extends Command
{
    protected $signature = 'members:summary';
    protected $description = 'Get comprehensive member summaries with account and loan details';

    private $mcpService;

    public function __construct()
    {
        parent::__construct();
        $this->mcpService = new McpDatabaseService();
    }

    public function handle()
    {
        $this->info('Fetching comprehensive member summaries...');
        $this->line(str_repeat('=', 60));
        
        // First, let's get the database schema to understand the structure
        $this->info("\n1. Getting database schema...");
        $schemaResult = $this->mcpService->listTables();
        
        if ($schemaResult['success']) {
            $this->info("Available tables: " . implode(', ', $schemaResult['tables']));
        } else {
            $this->error("Error getting tables: " . $schemaResult['error']);
            return Command::FAILURE;
        }

        // Query 1: Get all clients with basic information
        $this->info("\n2. Getting all clients with basic information...");
        $clientsQuery = "
            SELECT 
                client_number,
                first_name,
                last_name,
                full_name,
                client_status,
                phone_number as phone,
                email,
                registration_date
            FROM clients 
            ORDER BY client_number
        ";
        
        $clientsResult = $this->mcpService->executeQuery($clientsQuery, 'read');
        
        if (!$clientsResult['success']) {
            $this->error("Error fetching clients: " . $clientsResult['error']);
            return Command::FAILURE;
        }
        
        $clients = $clientsResult['data'];
        $this->info("Found " . count($clients) . " clients");

        // Query 2: Get account summaries grouped by client
        $this->info("\n3. Getting account summaries per client...");
        $accountSummaryQuery = "
            SELECT 
                client_number,
                COUNT(*) as total_accounts,
                SUM(CASE WHEN account_type IN ('SAVINGS', 'CURRENT') THEN balance ELSE 0 END) as total_savings_balance,
                SUM(CASE WHEN account_type = 'SHARES' THEN balance ELSE 0 END) as total_shares_balance,
                SUM(balance) as total_balance
            FROM accounts 
            WHERE status = 'ACTIVE'
            GROUP BY client_number
            ORDER BY client_number
        ";
        
        $accountSummaryResult = $this->mcpService->executeQuery($accountSummaryQuery, 'read');
        
        if (!$accountSummaryResult['success']) {
            $this->error("Error fetching account summaries: " . $accountSummaryResult['error']);
            return Command::FAILURE;
        }
        
        $accountSummaries = $accountSummaryResult['data'];
        $this->info("Found account summaries for " . count($accountSummaries) . " clients");

        // Query 3: Get loan summaries grouped by client
        $this->info("\n4. Getting loan summaries per client...");
        $loanSummaryQuery = "
            SELECT 
                client_number,
                COUNT(*) as total_loans,
                COUNT(CASE WHEN loan_status = 'ACTIVE' THEN 1 END) as active_loan_count,
                SUM(CASE WHEN loan_status = 'ACTIVE' THEN COALESCE(principle, 0) ELSE 0 END) as total_active_loan_amount,
                SUM(COALESCE(principle, 0)) as total_loan_amount
            FROM loans 
            WHERE client_number IS NOT NULL
            GROUP BY client_number
            ORDER BY client_number
        ";
        
        $loanSummaryResult = $this->mcpService->executeQuery($loanSummaryQuery, 'read');
        
        if (!$loanSummaryResult['success']) {
            $this->error("Error fetching loan summaries: " . $loanSummaryResult['error']);
            return Command::FAILURE;
        }
        
        $loanSummaries = $loanSummaryResult['data'];
        $this->info("Found loan summaries for " . count($loanSummaries) . " clients");

        // Combine all data and display comprehensive member summaries
        $this->info("\n5. Combining data and displaying comprehensive member summaries...");
        $this->line(str_repeat('=', 100));
        
        // Create lookup arrays for performance
        $accountLookup = [];
        foreach ($accountSummaries as $summary) {
            $accountLookup[$summary->client_number] = $summary;
        }
        
        $loanLookup = [];
        foreach ($loanSummaries as $summary) {
            $loanLookup[$summary->client_number] = $summary;
        }
        
        // Display combined results
        $grandTotalSavings = 0;
        $grandTotalShares = 0;
        $grandTotalLoans = 0;
        $totalActiveMembers = 0;
        
        $this->table(
            [
                'Client#', 'Name', 'Status', 'Phone', 'Email', 
                'Accounts', 'Savings Balance', 'Shares Balance', 
                'Active Loans', 'Total Loan Amount'
            ],
            collect($clients)->map(function ($client) use ($accountLookup, $loanLookup, &$grandTotalSavings, &$grandTotalShares, &$grandTotalLoans, &$totalActiveMembers) {
                $accountData = $accountLookup[$client->client_number] ?? null;
                $loanData = $loanLookup[$client->client_number] ?? null;
                
                $savingsBalance = $accountData->total_savings_balance ?? 0;
                $sharesBalance = $accountData->total_shares_balance ?? 0;
                $activeLoanAmount = $loanData->total_active_loan_amount ?? 0;
                
                $grandTotalSavings += $savingsBalance;
                $grandTotalShares += $sharesBalance;
                $grandTotalLoans += $activeLoanAmount;
                
                if ($client->client_status === 'ACTIVE') {
                    $totalActiveMembers++;
                }
                
                return [
                    $client->client_number,
                    $client->full_name ?? ($client->first_name . ' ' . $client->last_name),
                    $client->client_status,
                    $client->phone,
                    $client->email ?? 'N/A',
                    $accountData->total_accounts ?? 0,
                    'TZS ' . number_format($savingsBalance, 2),
                    'TZS ' . number_format($sharesBalance, 2),
                    $loanData->active_loan_count ?? 0,
                    'TZS ' . number_format($activeLoanAmount, 2)
                ];
            })->toArray()
        );

        // Display summary statistics
        $this->info("\n" . str_repeat('=', 60));
        $this->info("SUMMARY STATISTICS");
        $this->info(str_repeat('=', 60));
        $this->info("Total Members: " . count($clients));
        $this->info("Active Members: " . $totalActiveMembers);
        $this->info("Members with Accounts: " . count($accountSummaries));
        $this->info("Members with Loans: " . count($loanSummaries));
        $this->info("Total Savings Balance: TZS " . number_format($grandTotalSavings, 2));
        $this->info("Total Shares Balance: TZS " . number_format($grandTotalShares, 2));
        $this->info("Total Active Loan Amount: TZS " . number_format($grandTotalLoans, 2));
        $this->info("Combined Portfolio Value: TZS " . number_format($grandTotalSavings + $grandTotalShares, 2));

        return Command::SUCCESS;
    }
}