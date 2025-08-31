<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\McpDatabaseService;

class ViewSampleData extends Command
{
    protected $signature = 'db:sample';
    protected $description = 'View sample data from key tables';

    private $mcpService;

    public function __construct()
    {
        parent::__construct();
        $this->mcpService = new McpDatabaseService();
    }

    public function handle()
    {
        $this->info('Viewing sample data from key tables...');
        $this->line(str_repeat('=', 80));
        
        // Sample clients data
        $this->info("\n1. Sample Clients Data:");
        $clientsQuery = "SELECT client_number, full_name, client_status, phone_number, email FROM clients LIMIT 5";
        $clientsResult = $this->mcpService->executeQuery($clientsQuery, 'read');
        
        if ($clientsResult['success'] && count($clientsResult['data']) > 0) {
            $this->table(
                ['Client#', 'Name', 'Status', 'Phone', 'Email'],
                collect($clientsResult['data'])->map(function ($client) {
                    return [
                        $client->client_number,
                        $client->full_name,
                        $client->client_status,
                        $client->phone_number,
                        $client->email ?? 'N/A'
                    ];
                })->toArray()
            );
        } else {
            $this->error("No clients found or error occurred");
        }
        
        // Sample accounts data
        $this->info("\n2. Sample Accounts Data:");
        $accountsQuery = "SELECT account_number, client_number, account_name, balance, status, account_type FROM accounts LIMIT 10";
        $accountsResult = $this->mcpService->executeQuery($accountsQuery, 'read');
        
        if ($accountsResult['success'] && count($accountsResult['data']) > 0) {
            $this->table(
                ['Account#', 'Client#', 'Account Name', 'Balance', 'Status', 'Type'],
                collect($accountsResult['data'])->map(function ($account) {
                    return [
                        $account->account_number,
                        $account->client_number,
                        $account->account_name,
                        'TZS ' . number_format($account->balance, 2),
                        $account->status,
                        $account->account_type ?? 'N/A'
                    ];
                })->toArray()
            );
        } else {
            $this->error("No accounts found or error occurred");
        }
        
        // Sample loans data
        $this->info("\n3. Sample Loans Data:");
        $loansQuery = "SELECT loan_id, client_number, principle, loan_status, status, disbursement_date FROM loans WHERE client_number IS NOT NULL LIMIT 10";
        $loansResult = $this->mcpService->executeQuery($loansQuery, 'read');
        
        if ($loansResult['success'] && count($loansResult['data']) > 0) {
            $this->table(
                ['Loan ID', 'Client#', 'Principal', 'Loan Status', 'Status', 'Disbursement Date'],
                collect($loansResult['data'])->map(function ($loan) {
                    return [
                        $loan->loan_id ?? 'N/A',
                        $loan->client_number,
                        'TZS ' . number_format($loan->principle, 2),
                        $loan->loan_status,
                        $loan->status ?? 'N/A',
                        $loan->disbursement_date ?? 'N/A'
                    ];
                })->toArray()
            );
        } else {
            $this->error("No loans found or error occurred");
        }
        
        // Check account types distribution
        $this->info("\n4. Account Types Distribution:");
        $typesQuery = "SELECT account_type, COUNT(*) as count, SUM(balance) as total_balance FROM accounts WHERE account_type IS NOT NULL GROUP BY account_type ORDER BY count DESC";
        $typesResult = $this->mcpService->executeQuery($typesQuery, 'read');
        
        if ($typesResult['success'] && count($typesResult['data']) > 0) {
            $this->table(
                ['Account Type', 'Count', 'Total Balance'],
                collect($typesResult['data'])->map(function ($type) {
                    return [
                        $type->account_type,
                        $type->count,
                        'TZS ' . number_format($type->total_balance, 2)
                    ];
                })->toArray()
            );
        } else {
            $this->error("No account types data found");
        }

        return Command::SUCCESS;
    }
}