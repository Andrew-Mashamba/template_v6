<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class DirectDatabaseQueryService
{
    private $mcpDatabaseService;
    
    public function __construct()
    {
        $this->mcpDatabaseService = new McpDatabaseService();
    }
    
    /**
     * Process a natural language query and return actual database results
     */
    public function processQuery(string $question): array
    {
        try {
            $questionLower = strtolower($question);
            
            // Detect query type and build SQL
            $queryInfo = $this->buildSqlFromQuestion($questionLower);
            
            if (!$queryInfo['sql']) {
                return [
                    'success' => false,
                    'message' => "I couldn't understand your question. Please be more specific."
                ];
            }
            
            // Execute the query
            $result = $this->mcpDatabaseService->executeQuery($queryInfo['sql'], 'read');
            
            if (!$result['success']) {
                return [
                    'success' => false,
                    'message' => "Database query failed: " . ($result['error'] ?? 'Unknown error')
                ];
            }
            
            // Format the response based on query type
            return $this->formatResponse($queryInfo['type'], $result['data'], $question);
            
        } catch (Exception $e) {
            Log::error('DirectDatabaseQueryService error', [
                'question' => $question,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'message' => "An error occurred: " . $e->getMessage()
            ];
        }
    }
    
    /**
     * Build SQL query from natural language question
     */
    private function buildSqlFromQuestion(string $questionLower): array
    {
        $sql = null;
        $type = null;
        
        // Mashamba-specific queries
        if (strpos($questionLower, 'mashamba') !== false || strpos($questionLower, 'andrew') !== false) {
            if (strpos($questionLower, 'account') !== false) {
                $type = 'mashamba_accounts';
                $sql = "SELECT 
                        a.account_number,
                        a.account_name,
                        a.balance,
                        a.status,
                        a.account_type,
                        c.full_name,
                        c.client_number
                    FROM accounts a
                    JOIN clients c ON a.client_number = c.client_number
                    WHERE c.client_number = '00003' OR LOWER(c.full_name) LIKE '%mashamba%'";
            } else {
                $type = 'mashamba_info';
                $sql = "SELECT * FROM clients WHERE client_number = '00003' OR LOWER(full_name) LIKE '%mashamba%' LIMIT 1";
            }
        }
        // Count queries
        elseif (strpos($questionLower, 'how many') !== false || strpos($questionLower, 'count') !== false) {
            if (strpos($questionLower, 'account') !== false) {
                if (strpos($questionLower, 'total') !== false || strpos($questionLower, 'all') !== false) {
                    $type = 'total_accounts';
                    $sql = "SELECT COUNT(*) as total_accounts FROM accounts";
                } else {
                    $type = 'accounts_by_client';
                    $sql = "SELECT c.full_name, c.client_number, COUNT(a.id) as account_count 
                           FROM clients c 
                           LEFT JOIN accounts a ON c.client_number = a.client_number 
                           GROUP BY c.client_number, c.full_name 
                           ORDER BY account_count DESC 
                           LIMIT 20";
                }
            } elseif (strpos($questionLower, 'client') !== false || strpos($questionLower, 'member') !== false) {
                $type = 'total_clients';
                $sql = "SELECT COUNT(*) as total_clients FROM clients WHERE client_status = 'ACTIVE'";
            } elseif (strpos($questionLower, 'loan') !== false) {
                $type = 'total_loans';
                $sql = "SELECT COUNT(*) as total_loans, SUM(principal_amount) as total_amount FROM loans";
            }
        }
        // List queries
        elseif (strpos($questionLower, 'list') !== false || strpos($questionLower, 'show') !== false) {
            if (strpos($questionLower, 'client') !== false || strpos($questionLower, 'member') !== false) {
                $type = 'list_clients';
                $sql = "SELECT client_number, full_name, phone_number, client_status FROM clients LIMIT 10";
            } elseif (strpos($questionLower, 'account') !== false) {
                $type = 'list_accounts';
                $sql = "SELECT account_number, account_name, balance, status FROM accounts LIMIT 10";
            } elseif (strpos($questionLower, 'loan') !== false) {
                $type = 'list_loans';
                $sql = "SELECT loan_number, client_name, principal_amount, loan_status FROM loans LIMIT 10";
            }
        }
        // Balance queries
        elseif (strpos($questionLower, 'balance') !== false) {
            if (strpos($questionLower, 'total') !== false) {
                $type = 'total_balance';
                $sql = "SELECT SUM(balance) as total_balance, COUNT(*) as account_count FROM accounts WHERE status = 'ACTIVE'";
            }
        }
        
        return [
            'sql' => $sql,
            'type' => $type
        ];
    }
    
    /**
     * Format response based on query type
     */
    private function formatResponse(string $type, array $data, string $originalQuestion): array
    {
        if (empty($data)) {
            return [
                'success' => true,
                'message' => "No results found for your query."
            ];
        }
        
        $message = "";
        
        switch ($type) {
            case 'mashamba_accounts':
                $totalBalance = 0;
                $accountCount = count($data);
                $clientName = $data[0]->full_name ?? 'Andrew Mashamba';
                $clientNumber = $data[0]->client_number ?? '00003';
                
                $message = "{$clientName} (client #{$clientNumber}) has {$accountCount} account" . ($accountCount > 1 ? 's' : '') . ":\n\n";
                
                foreach ($data as $index => $account) {
                    $balance = $account->balance ?? 0;
                    $totalBalance += $balance;
                    
                    $message .= ($index + 1) . ". {$account->account_name}\n";
                    $message .= "   - Account Number: {$account->account_number}\n";
                    $message .= "   - Balance: TZS " . number_format($balance, 2) . "\n";
                    $message .= "   - Status: {$account->status}\n\n";
                }
                
                $message .= "Total Combined Balance: TZS " . number_format($totalBalance, 2);
                break;
                
            case 'mashamba_info':
                $client = $data[0];
                $message = "Client Information:\n";
                $message .= "Name: {$client->full_name}\n";
                $message .= "Client Number: {$client->client_number}\n";
                $message .= "Status: {$client->client_status}\n";
                $message .= "Phone: {$client->phone_number}\n";
                $message .= "Email: {$client->email}\n";
                $message .= "Registration Date: {$client->registration_date}";
                break;
                
            case 'total_accounts':
                $total = $data[0]->total_accounts ?? 0;
                $message = "There are {$total} accounts in the database.";
                break;
                
            case 'total_clients':
                $total = $data[0]->total_clients ?? 0;
                $message = "There are {$total} active clients in the system.";
                break;
                
            case 'total_loans':
                $count = $data[0]->total_loans ?? 0;
                $amount = $data[0]->total_amount ?? 0;
                $message = "There are {$count} loans in the system with a total principal amount of TZS " . number_format($amount, 2);
                break;
                
            case 'total_balance':
                $balance = $data[0]->total_balance ?? 0;
                $count = $data[0]->account_count ?? 0;
                $message = "Total balance across all {$count} active accounts: TZS " . number_format($balance, 2);
                break;
                
            case 'list_clients':
                $message = "First " . count($data) . " clients:\n\n";
                foreach ($data as $index => $client) {
                    $message .= ($index + 1) . ". {$client->full_name} (#{$client->client_number})\n";
                    $message .= "   Phone: {$client->phone_number}\n";
                    $message .= "   Status: {$client->client_status}\n\n";
                }
                break;
                
            case 'list_accounts':
                $message = "First " . count($data) . " accounts:\n\n";
                foreach ($data as $index => $account) {
                    $message .= ($index + 1) . ". {$account->account_name}\n";
                    $message .= "   Number: {$account->account_number}\n";
                    $message .= "   Balance: TZS " . number_format($account->balance, 2) . "\n";
                    $message .= "   Status: {$account->status}\n\n";
                }
                break;
                
            case 'accounts_by_client':
                $message = "Account distribution by client:\n\n";
                foreach ($data as $index => $client) {
                    $message .= ($index + 1) . ". {$client->full_name} (#{$client->client_number}): {$client->account_count} accounts\n";
                }
                break;
                
            default:
                // Generic response
                $message = "Query results (" . count($data) . " rows):\n\n";
                foreach ($data as $index => $row) {
                    $message .= "Row " . ($index + 1) . ":\n";
                    $rowData = is_object($row) ? (array) $row : $row;
                    foreach ($rowData as $key => $value) {
                        $displayValue = $value === null ? 'NULL' : (is_array($value) ? json_encode($value) : $value);
                        $message .= "  {$key}: {$displayValue}\n";
                    }
                    $message .= "\n";
                }
        }
        
        return [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'query_type' => $type
        ];
    }
}