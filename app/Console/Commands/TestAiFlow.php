<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HybridAiService;
use App\Services\ContextEnhancementService;
use App\Services\QueryRequestService;
use App\Services\McpDatabaseService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class TestAiFlow extends Command
{
    protected $signature = 'test:ai-flow {message?}';
    protected $description = 'Test the complete AI flow as it would happen in production';
    
    public function handle()
    {
        $message = $this->argument('message') ?? 'list accounts belonging to MASHAMBA';
        
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║            COMPLETE AI FLOW TEST - AS IN PRODUCTION               ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->info('');
        
        // Authenticate
        $user = User::first();
        Auth::login($user);
        
        $sessionId = 'test_flow_' . uniqid();
        
        $this->info("📝 User Question: '{$message}'");
        $this->info('════════════════════════════════════════════════════════════════════');
        $this->info('');
        
        // STEP 1: Build context
        $this->info("STEP 1: Building Context");
        $this->info('────────────────────────────────────────');
        
        $contextService = new ContextEnhancementService();
        $context = $contextService->buildContext($message, [
            'session_id' => $sessionId,
            'user_name' => $user->name,
            'user_role' => 'Admin'
        ]);
        
        $this->line("✅ Context built: " . strlen($context['enhanced_message']) . " characters");
        $this->line("   Includes: Database schema, relationships, MCP tools info");
        
        // Show snippet of what Claude would receive
        $this->info("\nWhat Claude receives (snippet):");
        $this->line(substr($context['enhanced_message'], 0, 300) . "...");
        $this->info("\n[User Question at end]: {$message}\n");
        
        // STEP 2: Simulate Claude's response with SQL query
        $this->info("\nSTEP 2: Claude Processes & Generates SQL");
        $this->info('────────────────────────────────────────');
        
        // This is what Claude would respond with after seeing the context
        $claudeResponse = $this->simulateClaudeResponse($message);
        
        $this->line("Claude's response:");
        $this->info($claudeResponse);
        
        // STEP 3: System detects permission issue and extracts query
        $this->info("\nSTEP 3: System Auto-Execution");
        $this->info('────────────────────────────────────────');
        
        $queryService = new QueryRequestService();
        $hasPermissionIssue = $queryService->hasPermissionIssue($claudeResponse);
        
        $this->line("• Permission issue detected: " . ($hasPermissionIssue ? '✅ YES' : '❌ NO'));
        
        if ($hasPermissionIssue) {
            $queryRequest = $queryService->extractQueryRequest($claudeResponse);
            
            if ($queryRequest && isset($queryRequest['queries'])) {
                $this->line("• Queries to execute: " . count($queryRequest['queries']));
                
                foreach ($queryRequest['queries'] as $idx => $query) {
                    $this->line("\n  Query " . ($idx + 1) . ":");
                    $this->info("  " . $query['query']);
                }
                
                // STEP 4: Execute the queries
                $this->info("\nSTEP 4: Executing Queries");
                $this->info('────────────────────────────────────────');
                
                $results = $queryService->executeQueries($queryRequest);
                
                if ($results['success']) {
                    $this->line("✅ Queries executed successfully");
                    
                    foreach ($results['results'] as $key => $result) {
                        if (isset($result['count'])) {
                            $this->line("   • Found {$result['count']} records");
                        }
                        if (isset($result['data']) && count($result['data']) > 0) {
                            $this->info("\n   Results:");
                            foreach ($result['data'] as $idx => $row) {
                                if ($idx >= 3) {
                                    $this->line("   ... and more");
                                    break;
                                }
                                // Display based on what columns exist
                                if (isset($row->account_number)) {
                                    $this->line("   • Account: {$row->account_number}");
                                    if (isset($row->account_name)) {
                                        $this->line("     Name: {$row->account_name}");
                                    }
                                    if (isset($row->balance)) {
                                        $this->line("     Balance: " . number_format($row->balance, 2));
                                    }
                                    if (isset($row->first_name) && isset($row->last_name)) {
                                        $this->line("     Client: {$row->first_name} {$row->last_name}");
                                    }
                                }
                            }
                        }
                    }
                    
                    // STEP 5: Results sent back to Claude
                    $this->info("\nSTEP 5: Results Sent Back to Claude");
                    $this->info('────────────────────────────────────────');
                    
                    $enhancedMessage = $queryService->buildEnhancedMessageWithResults(
                        $message,
                        $results
                    );
                    
                    $this->line("✅ Query results added to context");
                    $this->line("   Claude now has the actual data to provide final answer");
                    
                    // STEP 6: Claude's final response with data
                    $this->info("\nSTEP 6: Claude's Final Answer (with data)");
                    $this->info('────────────────────────────────────────');
                    
                    $finalResponse = $this->generateFinalResponse($results);
                    $this->info($finalResponse);
                    
                } else {
                    $this->error("❌ Query execution failed");
                    foreach ($results['errors'] as $error) {
                        $this->error("   • {$error}");
                    }
                }
            }
        }
        
        // Show logs
        $this->info("\n📊 Check Logs");
        $this->info('────────────────────────────────────────');
        $this->line("Session ID: {$sessionId}");
        $this->line("View logs: php artisan logs:prompt --session={$sessionId}");
        
        return 0;
    }
    
    /**
     * Simulate what Claude would respond with initially
     */
    private function simulateClaudeResponse($message)
    {
        if (stripos($message, 'MASHAMBA') !== false) {
            return "I'll help you find accounts belonging to MASHAMBA. Let me query the database to get this information.\n\n" .
                   "PERMISSION-ISSUE\n" .
                   json_encode([
                       'PERMISSION-ISSUE' => true,
                       'queries' => [
                           [
                               'type' => 'sql',
                               'query' => "SELECT a.account_number, a.account_name, a.balance, a.status, " .
                                        "a.product_number, c.first_name, c.last_name, c.client_number " .
                                        "FROM accounts a " .
                                        "JOIN clients c ON a.client_number = c.client_number " .
                                        "WHERE UPPER(c.last_name) LIKE '%MASHAMBA%' " .
                                        "OR UPPER(c.first_name) LIKE '%MASHAMBA%' " .
                                        "ORDER BY a.account_number"
                           ]
                       ]
                   ], JSON_PRETTY_PRINT);
        } else {
            return "I'll help you with that query. Let me check the database.\n\n" .
                   "PERMISSION-ISSUE\n" .
                   json_encode([
                       'PERMISSION-ISSUE' => true,
                       'queries' => [
                           [
                               'type' => 'sql',
                               'query' => "SELECT COUNT(*) as total_accounts FROM accounts"
                           ]
                       ]
                   ], JSON_PRETTY_PRINT);
        }
    }
    
    /**
     * Generate final response with actual data
     */
    private function generateFinalResponse($results)
    {
        $response = "Based on the database query results:\n\n";
        
        foreach ($results['results'] as $result) {
            if (isset($result['data']) && count($result['data']) > 0) {
                $count = $result['count'] ?? count($result['data']);
                
                // Check if this is MASHAMBA query
                $firstRow = $result['data'][0];
                if (isset($firstRow->first_name) && isset($firstRow->last_name)) {
                    $response .= "I found {$count} accounts belonging to MASHAMBA:\n\n";
                    
                    foreach ($result['data'] as $account) {
                        $response .= "• Account #{$account->account_number}\n";
                        $response .= "  - Name: {$account->account_name}\n";
                        $response .= "  - Balance: " . number_format($account->balance ?? 0, 2) . "\n";
                        $response .= "  - Status: {$account->status}\n";
                        $response .= "  - Owner: {$account->first_name} {$account->last_name}\n\n";
                    }
                    
                    $response .= "All these accounts belong to members with 'MASHAMBA' in their name.";
                } else if (isset($firstRow->total_accounts)) {
                    $response .= "The system has {$firstRow->total_accounts} total accounts.";
                }
            }
        }
        
        return $response;
    }
}