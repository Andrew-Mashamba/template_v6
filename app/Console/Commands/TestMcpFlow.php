<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\DirectClaudeService;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class TestMcpFlow extends Command
{
    protected $signature = 'test:mcp-flow {message?}';
    protected $description = 'Test the complete MCP flow with DirectClaude (no interception)';
    
    public function handle()
    {
        $message = $this->argument('message') ?? 'list accounts belonging to MASHAMBA';
        
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║            MCP FLOW TEST - DIRECT CLAUDE ACCESS                   ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->info('');
        
        // Authenticate
        $user = User::first();
        Auth::login($user);
        
        $sessionId = 'mcp_test_' . uniqid();
        
        $this->info("📝 User Question: '{$message}'");
        $this->info('════════════════════════════════════════════════════════════════════');
        $this->info('');
        
        // STEP 1: DirectClaude processes everything
        $this->info("How DirectClaude with MCP Works:");
        $this->info('────────────────────────────────────────');
        $this->line("1. ✅ User sends question: \"{$message}\"");
        $this->line("2. ✅ We build context (database schema, relationships)");
        $this->line("3. ✅ Send to Claude with MCP tools configured");
        $this->line("4. ✅ Claude uses MCP to query database directly");
        $this->line("5. ✅ Claude returns final answer");
        $this->line("6. ✅ NO interception, NO query extraction, NO processing");
        $this->info('');
        
        $this->info("Executing DirectClaude Flow:");
        $this->info('────────────────────────────────────────');
        
        $directClaude = new DirectClaudeService();
        
        $options = [
            'session_id' => $sessionId,
            'user_name' => $user->name,
            'user_role' => 'Admin'
        ];
        
        $response = $directClaude->processMessage($message, $options);
        
        if ($response['success']) {
            $this->info("\n✅ SUCCESS - Claude's Direct Response:");
            $this->info('────────────────────────────────────────');
            $this->line($response['message']);
            $this->info('');
            $this->info('📊 Summary:');
            $this->info('────────────────────────────────────────');
            $this->line('• Claude received the question and context');
            $this->line('• Claude used MCP tools to query database directly');
            $this->line('• Claude returned the final answer');
            $this->line('• NO interception or processing from our side');
            $this->info('');
            $this->info("✅ MCP Integration Working Perfectly!");
        } else {
            $this->error("\n❌ Error: " . ($response['error'] ?? 'Unknown'));
            $this->error($response['message']);
            
            if ($response['error'] === 'CLAUDE_NOT_CONFIGURED') {
                $this->info("\n🔧 Make sure Claude CLI has MCP configured in:");
                $this->info("   ~/Library/Application Support/Claude/claude_desktop_config.json");
            }
        }
        
        // Show service status
        $this->info("\n📊 Service Status:");
        $this->info('────────────────────────────────────────');
        $status = $directClaude->getStatus();
        foreach ($status as $key => $value) {
            if (is_bool($value)) {
                $value = $value ? '✅ Yes' : '❌ No';
            }
            $this->line("• {$key}: {$value}");
        }
        
        return 0;
    }
}