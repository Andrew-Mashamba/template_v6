<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Livewire\AiAgent\AiAgentChat;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class TestAiChat extends Command
{
    protected $signature = 'test:ai-chat {message?}';
    protected $description = 'Test the AI Agent Chat with a real message';
    
    public function handle()
    {
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║              AI AGENT CHAT - INTEGRATION TEST                     ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->info('');
        
        // Get test message
        $message = $this->argument('message') ?? 'List accounts belonging to MASHAMBA';
        
        $this->info("Testing with message: '{$message}'");
        $this->info('════════════════════════════════════════════════════════════════════');
        
        try {
            // Authenticate as first user for testing
            $user = User::first();
            if (!$user) {
                $this->error('No users found in database. Please seed the database first.');
                return 1;
            }
            
            Auth::login($user);
            $this->line("✅ Authenticated as: {$user->name} (ID: {$user->id})");
            
            // Create AI Agent Chat instance with dependencies
            $aiChat = new AiAgentChat();
            
            // Manually inject dependencies since we're not in Livewire context
            $aiChat->boot(
                app(\App\Services\AiAgentService::class),
                app(\App\Services\AiMemoryService::class),
                app(\App\Services\AiValidationService::class),
                app(\App\Services\ClaudeService::class),
                app(\App\Services\LocalClaudeService::class),
                app(\App\Services\ClaudeCliService::class),
                app(\App\Services\HybridAiService::class)
            );
            
            $aiChat->mount();
            
            $this->line("✅ AI Agent Chat initialized");
            $this->line("   • Session ID: " . substr($aiChat->sessionId, 0, 20) . "...");
            $this->line("   • AI Mode: " . ($aiChat->useHybridAi ? 'Hybrid AI' : 
                                           ($aiChat->useClaudeCli ? 'Claude CLI' : 
                                           ($aiChat->useLocalClaude ? 'Local Claude' : 
                                           ($aiChat->useClaudeDirectly ? 'Direct Claude' : 'Default')))));
            
            // Set the message
            $aiChat->newMessage = $message;
            
            // Send message
            $this->info("\n📤 Sending message...");
            $this->info('────────────────────────────────────────');
            
            $aiChat->sendMessage();
            
            // Wait for processing
            $maxWait = 30; // 30 seconds max
            $waited = 0;
            
            while ($aiChat->isProcessing && $waited < $maxWait) {
                $this->line("⏳ Processing... ({$waited}s)");
                sleep(1);
                $waited++;
            }
            
            // Check results
            if (!$aiChat->isProcessing) {
                $this->info("\n📥 Response received!");
                $this->info('────────────────────────────────────────');
                
                // Get the last message from conversation
                $lastMessage = end($aiChat->messages);
                
                if ($lastMessage && $lastMessage['role'] === 'assistant') {
                    $this->line("✅ AI Response:");
                    $this->info('');
                    
                    // Display response (truncate if too long)
                    $response = $lastMessage['content'];
                    if (strlen($response) > 500) {
                        $this->line(substr($response, 0, 500) . '...');
                        $this->info("\n[Response truncated - full length: " . strlen($response) . " chars]");
                    } else {
                        $this->line($response);
                    }
                    
                    // Check for specific indicators
                    $this->info("\n📊 Response Analysis:");
                    $this->info('────────────────────────────────────');
                    
                    $hasData = strpos($response, 'account') !== false || 
                               strpos($response, 'MASHAMBA') !== false;
                    $hasPermissionIssue = strpos($response, 'PERMISSION-ISSUE') !== false;
                    $hasError = strpos($response, 'error') !== false || 
                                strpos($response, 'Error') !== false;
                    
                    $this->line("• Contains relevant data: " . ($hasData ? '✅' : '❌'));
                    $this->line("• Has permission issue: " . ($hasPermissionIssue ? '⚠️ Yes' : '✅ No'));
                    $this->line("• Contains errors: " . ($hasError ? '❌ Yes' : '✅ No'));
                    
                    // Check logs
                    $this->checkLogs($aiChat->sessionId);
                    
                } else {
                    $this->error("❌ No AI response found in messages");
                }
            } else {
                $this->error("❌ Timeout: AI processing took longer than {$maxWait} seconds");
            }
            
        } catch (\Exception $e) {
            $this->error("❌ Test failed with exception:");
            $this->error($e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
        
        $this->info('');
        $this->info('Test completed!');
        
        return 0;
    }
    
    /**
     * Check logs for the session
     */
    private function checkLogs($sessionId)
    {
        $this->info("\n📝 Checking Logs:");
        $this->info('────────────────────────────────────────');
        
        $logFile = storage_path('logs/laravel-' . now()->format('Y-m-d') . '.log');
        
        if (File::exists($logFile)) {
            $content = File::get($logFile);
            $lines = explode("\n", $content);
            
            $promptChainLogs = [];
            foreach ($lines as $line) {
                if (strpos($line, '[PROMPT-CHAIN') !== false && 
                    strpos($line, $sessionId) !== false) {
                    $promptChainLogs[] = $line;
                }
            }
            
            if (count($promptChainLogs) > 0) {
                $this->line("✅ Found " . count($promptChainLogs) . " prompt chain logs for this session");
                
                // Extract steps
                $steps = [];
                foreach ($promptChainLogs as $log) {
                    if (preg_match('/"step":(\d+)/', $log, $matches)) {
                        $step = $matches[1];
                        if (!in_array($step, $steps)) {
                            $steps[] = $step;
                        }
                    }
                }
                
                sort($steps);
                $this->line("   • Steps logged: " . implode(', ', $steps));
                
                // Check for specific log points
                $hasStart = false;
                $hasContext = false;
                $hasQuery = false;
                $hasResponse = false;
                
                foreach ($promptChainLogs as $log) {
                    if (strpos($log, 'User Message Received') !== false) $hasStart = true;
                    if (strpos($log, 'Context Built') !== false) $hasContext = true;
                    if (strpos($log, 'Query auto-executed') !== false) $hasQuery = true;
                    if (strpos($log, 'Response') !== false) $hasResponse = true;
                }
                
                $this->line("   • User message logged: " . ($hasStart ? '✅' : '❌'));
                $this->line("   • Context built: " . ($hasContext ? '✅' : '❌'));
                $this->line("   • Query executed: " . ($hasQuery ? '✅' : '⚠️ N/A'));
                $this->line("   • Response logged: " . ($hasResponse ? '✅' : '❌'));
                
            } else {
                $this->warn("⚠️ No prompt chain logs found for session: {$sessionId}");
            }
        } else {
            $this->error("❌ Log file not found");
        }
    }
}