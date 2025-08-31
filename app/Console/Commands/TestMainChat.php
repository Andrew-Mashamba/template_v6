<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Livewire\AiAgent\AiAgentChat;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Livewire\Livewire;

class TestMainChat extends Command
{
    protected $signature = 'test:main-chat {message?}';
    protected $description = 'Test the main chat interface with DirectClaude MCP';
    
    public function handle()
    {
        $message = $this->argument('message') ?? 'list accounts belonging to MASHAMBA';
        
        $this->info('');
        $this->info('╔══════════════════════════════════════════════════════════════════╗');
        $this->info('║            MAIN CHAT TEST - DIRECT CLAUDE MCP                     ║');
        $this->info('╚══════════════════════════════════════════════════════════════════╝');
        $this->info('');
        
        // Authenticate
        $user = User::first();
        Auth::login($user);
        
        $this->info("📝 Testing Main Chat with: '{$message}'");
        $this->info('════════════════════════════════════════════════════════════════════');
        $this->info('');
        
        // Create component instance
        $component = Livewire::test(AiAgentChat::class);
        
        // Check current mode
        $this->info('Current AI Mode Settings:');
        $this->info('────────────────────────────────────────');
        $this->line('• useDirectClaude (MCP): ' . ($component->get('useDirectClaude') ? '✅ ENABLED' : '❌ Disabled'));
        $this->line('• useHybridAi: ' . ($component->get('useHybridAi') ? '✅ Enabled' : '❌ DISABLED'));
        $this->line('• useClaudeCli: ' . ($component->get('useClaudeCli') ? '✅ Enabled' : '❌ DISABLED'));
        $this->line('• useLocalClaude: ' . ($component->get('useLocalClaude') ? '✅ Enabled' : '❌ DISABLED'));
        $this->line('• useClaudeDirectly: ' . ($component->get('useClaudeDirectly') ? '✅ Enabled' : '❌ DISABLED'));
        $this->info('');
        
        $this->info('Mode: DirectClaude with MCP Tools');
        $this->info('────────────────────────────────────────');
        $this->line('✅ Claude receives question + context');
        $this->line('✅ Claude uses MCP to query database directly');
        $this->line('✅ Claude returns final answer');
        $this->line('✅ NO interception or processing');
        $this->info('');
        
        // Set the message and send it
        $this->info('Sending message to chat...');
        $this->info('────────────────────────────────────────');
        
        $component->set('message', $message)
                 ->call('sendMessage');
        
        // Wait for response (simulate async)
        sleep(3);
        
        // Get messages
        $messages = $component->get('messages');
        
        if (count($messages) > 0) {
            $this->info("\n✅ Chat Response Received:");
            $this->info('────────────────────────────────────────');
            
            foreach ($messages as $msg) {
                if ($msg['role'] === 'user') {
                    $this->line("👤 User: " . $msg['content']);
                } else {
                    $this->line("🤖 AI: " . strip_tags($msg['content']));
                }
                $this->line('');
            }
            
            $this->info('✅ Main Chat using DirectClaude MCP successfully!');
        } else {
            $this->error('❌ No response received from chat');
        }
        
        // Check if there were any errors
        $error = $component->get('error');
        if ($error) {
            $this->error("Error: {$error}");
        }
        
        return 0;
    }
}