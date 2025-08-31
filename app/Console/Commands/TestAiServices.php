<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HybridAiService;
use App\Services\ContextEnhancementService;
use App\Services\McpDatabaseService;
use App\Services\QueryRequestService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class TestAiServices extends Command
{
    protected $signature = 'test:ai-services {message?}';
    protected $description = 'Test AI services directly with a message';
    
    public function handle()
    {
        $message = $this->argument('message') ?? 'List accounts belonging to MASHAMBA';
        
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║               AI SERVICES - DIRECT TEST                           ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->info('');
        
        // Authenticate
        $user = User::first();
        Auth::login($user);
        $this->line("✅ Authenticated as: {$user->name}");
        
        $this->info("\n📨 Message: '{$message}'");
        $this->info('════════════════════════════════════════════════════════════════════');
        
        // Test 1: Context Enhancement
        $this->testContextEnhancement($message);
        
        // Test 2: Direct Database Query
        $this->testDirectDatabaseQuery($message);
        
        // Test 3: Hybrid AI Service
        $this->testHybridAiService($message);
        
        // Test 4: Check Logs
        $this->checkLogs();
        
        return 0;
    }
    
    /**
     * Test Context Enhancement
     */
    private function testContextEnhancement($message)
    {
        $this->info("\n📝 TEST 1: Context Enhancement");
        $this->info('────────────────────────────────────────');
        
        try {
            $contextService = new ContextEnhancementService();
            $context = $contextService->buildContext($message, [
                'session_id' => 'test_' . uniqid(),
                'user_name' => Auth::user()->name,
                'user_role' => 'Admin'
            ]);
            
            $this->line("✅ Context built successfully");
            $this->line("   • Enhanced message length: " . strlen($context['enhanced_message']));
            $this->line("   • Has database schema: " . (isset($context['database_schema']) ? 'Yes' : 'No'));
            $this->line("   • Has metadata: " . (isset($context['metadata']) ? 'Yes' : 'No'));
            
            // Show a snippet of the enhanced message
            $snippet = substr($context['enhanced_message'], 0, 200);
            $this->info("\n   Enhanced message snippet:");
            $this->line("   " . str_replace("\n", "\n   ", $snippet) . "...");
            
        } catch (\Exception $e) {
            $this->error("❌ Context enhancement failed: " . $e->getMessage());
        }
    }
    
    /**
     * Test Direct Database Query
     */
    private function testDirectDatabaseQuery($message)
    {
        $this->info("\n🔍 TEST 2: Direct Database Query");
        $this->info('────────────────────────────────────────');
        
        try {
            // If message mentions MASHAMBA, execute the query
            if (stripos($message, 'MASHAMBA') !== false) {
                $mcpService = new McpDatabaseService();
                
                $query = "SELECT a.*, c.first_name, c.last_name, c.client_number as client_num
                         FROM accounts a 
                         JOIN clients c ON a.client_number = c.client_number 
                         WHERE UPPER(c.last_name) LIKE '%MASHAMBA%' 
                         OR UPPER(c.first_name) LIKE '%MASHAMBA%'";
                
                $result = $mcpService->executeMcpTool('read_query', [
                    'query' => $query
                ]);
                
                if ($result['success']) {
                    $count = $result['count'] ?? 0;
                    $this->line("✅ Query executed successfully");
                    $this->line("   • Found {$count} accounts for MASHAMBA");
                    
                    if ($count > 0 && isset($result['data'])) {
                        $this->info("\n   Account details:");
                        foreach ($result['data'] as $index => $account) {
                            if ($index >= 3) {
                                $this->line("   ... and " . ($count - 3) . " more");
                                break;
                            }
                            $this->line("   • Account #{$account->account_number}: {$account->account_name}");
                            $this->line("     - Balance: " . number_format($account->balance ?? 0, 2));
                            $this->line("     - Status: {$account->status}");
                            $this->line("     - Client: {$account->first_name} {$account->last_name}");
                        }
                    }
                } else {
                    $this->error("❌ Query failed: " . ($result['error'] ?? 'Unknown error'));
                }
            } else {
                // Generic count query
                $mcpService = new McpDatabaseService();
                $result = $mcpService->executeMcpTool('read_query', [
                    'query' => 'SELECT COUNT(*) as count FROM accounts'
                ]);
                
                if ($result['success']) {
                    $count = $result['data'][0]->count ?? 0;
                    $this->line("✅ Total accounts in system: {$count}");
                }
            }
        } catch (\Exception $e) {
            $this->error("❌ Database query failed: " . $e->getMessage());
        }
    }
    
    /**
     * Test Hybrid AI Service
     */
    private function testHybridAiService($message)
    {
        $this->info("\n🤖 TEST 3: Hybrid AI Service (Mock)");
        $this->info('────────────────────────────────────────');
        
        try {
            $hybridService = new HybridAiService();
            
            // Create a mock response that simulates what Claude would return
            $mockResponse = $this->createMockResponse($message);
            
            $this->line("✅ Hybrid AI Service test:");
            $this->line("   • Service status: Operational");
            $this->line("   • Claude CLI available: " . ($hybridService->getStatus()['claude_cli_available'] ? 'Yes' : 'No'));
            
            // Test permission issue detection and handling
            $queryService = new QueryRequestService();
            
            // Simulate Claude's response with permission issue
            $claudeResponse = "I need to check the database for MASHAMBA accounts.\n\n";
            $claudeResponse .= "PERMISSION-ISSUE\n";
            $claudeResponse .= json_encode([
                'PERMISSION-ISSUE' => true,
                'queries' => [
                    [
                        'type' => 'sql',
                        'query' => "SELECT * FROM accounts a JOIN clients c ON a.client_id = c.id WHERE UPPER(c.lastname) LIKE '%MASHAMBA%'"
                    ]
                ]
            ]);
            
            $hasPermissionIssue = $queryService->hasPermissionIssue($claudeResponse);
            $this->line("   • Permission issue detected: " . ($hasPermissionIssue ? 'Yes' : 'No'));
            
            if ($hasPermissionIssue) {
                $queryRequest = $queryService->extractQueryRequest($claudeResponse);
                if ($queryRequest) {
                    $this->line("   • Query auto-execution: Would execute " . count($queryRequest['queries']) . " queries");
                }
            }
            
            $this->info("\n   Mock AI Response:");
            $this->line("   " . str_replace("\n", "\n   ", $mockResponse));
            
        } catch (\Exception $e) {
            $this->error("❌ Hybrid AI test failed: " . $e->getMessage());
        }
    }
    
    /**
     * Create mock response
     */
    private function createMockResponse($message)
    {
        if (stripos($message, 'MASHAMBA') !== false) {
            return "Based on the database query results:\n\n" .
                   "I found accounts belonging to MASHAMBA in the system:\n" .
                   "• The system would search for any client with 'MASHAMBA' in their name\n" .
                   "• Matching accounts would be displayed with their details\n" .
                   "• Account information includes balance, status, and account numbers\n\n" .
                   "Note: This is a mock response. With Claude CLI connected, you would get actual data.";
        } else {
            return "To answer your question about '{$message}':\n\n" .
                   "The SACCOS system contains comprehensive data about:\n" .
                   "• Clients (members)\n" .
                   "• Accounts (savings and loans)\n" .
                   "• Transactions\n" .
                   "• And much more\n\n" .
                   "With Claude CLI connected, I would provide specific data from the database.";
        }
    }
    
    /**
     * Check logs
     */
    private function checkLogs()
    {
        $this->info("\n📊 TEST 4: Check Logs");
        $this->info('────────────────────────────────────────');
        
        $logFile = storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log');
        
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            
            // Count different log types
            $promptChainLogs = substr_count($content, '[PROMPT-CHAIN');
            $mcpToolLogs = substr_count($content, '[MCP-TOOL]');
            $testLogs = substr_count($content, '[TEST]');
            
            $this->line("✅ Log file exists");
            $this->line("   • PROMPT-CHAIN logs: {$promptChainLogs}");
            $this->line("   • MCP-TOOL logs: {$mcpToolLogs}");
            $this->line("   • TEST logs: {$testLogs}");
            
            // Show recent prompt chain logs
            $lines = explode("\n", $content);
            $recentPromptLogs = [];
            
            foreach (array_reverse($lines) as $line) {
                if (strpos($line, '[PROMPT-CHAIN') !== false) {
                    $recentPromptLogs[] = $line;
                    if (count($recentPromptLogs) >= 3) break;
                }
            }
            
            if (count($recentPromptLogs) > 0) {
                $this->info("\n   Recent prompt chain logs:");
                foreach (array_reverse($recentPromptLogs) as $log) {
                    // Extract just the message part
                    if (preg_match('/\[PROMPT-CHAIN[^\]]*\] ([^{]+)/', $log, $matches)) {
                        $this->line("   • " . trim($matches[1]));
                    }
                }
            }
        } else {
            $this->warn("⚠️ Log file not found");
        }
    }
}