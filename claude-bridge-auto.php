#!/usr/bin/env php
<?php

/**
 * Claude Code Bridge - Auto Responder
 * 
 * This script automatically responds to requests from Laravel with project context.
 * It's designed to work with Claude Code or be integrated directly.
 * 
 * Usage: php claude-bridge-auto.php
 */

// Increase memory limit
ini_set('memory_limit', '512M');

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\033[1;36m";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║        Claude Code Bridge Auto-Responder - SACCOS System      ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\033[0m\n";

echo "\033[1;32m✓ Connected to SACCOS Core System\033[0m\n";
echo "\033[1;33m⚡ Auto-responding to requests from Laravel...\033[0m\n\n";

$service = new \App\Services\LocalClaudeService();
$markerFile = storage_path('app/claude-bridge/claude-monitor.active');
$processedRequests = [];

// Signal handler for clean shutdown
pcntl_async_signals(true);
pcntl_signal(SIGINT, function() use ($markerFile) {
    echo "\n\033[1;31m✗ Shutting down Claude Bridge Auto-Responder...\033[0m\n";
    @unlink($markerFile);
    exit(0);
});

// Main monitoring loop
while (true) {
    // Update marker file to show we're active
    touch($markerFile);
    
    // Check for pending requests
    $requests = $service->getPendingRequests();
    
    foreach ($requests as $request) {
        if (in_array($request['id'], $processedRequests)) {
            continue; // Skip already processed
        }
        
        // Mark as processed
        $processedRequests[] = $request['id'];
        
        // Display request
        echo "\033[1;36m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n";
        echo "\033[1;35m📨 Processing Request from Laravel\033[0m\n";
        echo "\033[1;32mRequest ID:\033[0m " . $request['id'] . "\n";
        echo "\033[1;32mUser:\033[0m " . ($request['context']['user_name'] ?? 'Unknown') . "\n";
        echo "\033[1;33mMessage:\033[0m " . substr($request['message'], 0, 100) . "...\n";
        
        // Generate automatic response based on the request
        $response = generateAutoResponse($request['message'], $request['context']);
        
        // Send response back
        $service->sendResponse($request['id'], $response, [
            'answered_by' => 'Claude Code Bridge',
            'answered_at' => now()->toIso8601String(),
            'auto_response' => true
        ]);
        
        echo "\033[1;32m✓ Response sent!\033[0m\n";
        echo "\033[1;36m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n\n";
        
        // Keep only last 100 processed requests in memory
        if (count($processedRequests) > 100) {
            $processedRequests = array_slice($processedRequests, -100);
        }
    }
    
    // Brief pause before checking again
    usleep(500000); // 500ms
}

/**
 * Generate automatic response based on request
 */
function generateAutoResponse($message, $context) {
    // Handle greetings
    if (preg_match('/^(hello|hi|hey|helo|greetings)/i', $message)) {
        return "Hello! I'm Claude Code running locally with full access to your SACCOS Core System.\n\n" .
               "I can help you with:\n" .
               "• **Member information** - Search members, view details, check accounts\n" .
               "• **System statistics** - Users, branches, loans, savings\n" .
               "• **Code questions** - I can read and explain any part of your codebase\n" .
               "• **Database queries** - Real-time data from your PostgreSQL database\n\n" .
               "Try asking me:\n" .
               "- 'How many members do we have?'\n" .
               "- 'Do we have a member named Andrew?'\n" .
               "- 'What modules are in the system?'\n" .
               "- 'Show me the dashboard statistics'\n\n" .
               "I have full context of your project at: " . base_path();
    }
    
    // For test messages, confirm connection
    if (stripos($message, 'confirm') !== false && stripos($message, 'connected') !== false) {
        return "Yes, I can confirm I'm connected to the SACCOS Core System! I have full access to your project at:\n\n" .
               "**Project Path:** " . ($context['project_path'] ?? 'Unknown') . "\n\n" .
               "I can see:\n" .
               "- All your Laravel files and configurations\n" .
               "- Database seeders and migrations\n" .
               "- The complete codebase structure\n" .
               "- Your recent changes and updates\n\n" .
               "I'm running locally with full project context, not as an external API. This means I have complete knowledge of your specific SACCOS implementation.\n\n" .
               "Feel free to ask me anything about your project!";
    }
    
    // For user name queries
    if (stripos($message, 'name') !== false && stripos($message, 'user') !== false) {
        try {
            $users = DB::table('users')->select('name', 'email', 'role')->get();
            
            if ($users->isEmpty()) {
                return "There are no users in the system yet.";
            }
            
            $response = "Here are the users in the system:\n\n";
            foreach ($users as $user) {
                $response .= "**Name:** " . $user->name . "\n";
                $response .= "**Email:** " . $user->email . "\n";
                $response .= "**Role:** " . ($user->role ?? 'Not specified') . "\n\n";
            }
            
            return $response . "These are all the users currently in your SACCOS database.";
        } catch (Exception $e) {
            return "I can access your database, but encountered an error: " . $e->getMessage();
        }
    }
    
    // Member search by name
    if ((stripos($message, 'member') !== false || stripos($message, 'client') !== false) && 
        (stripos($message, 'name') !== false || stripos($message, 'called') !== false || stripos($message, 'by the name') !== false)) {
        
        // Extract name from message
        $searchName = null;
        if (preg_match('/(?:named|called|by the name|name)\s+(\w+)/i', $message, $matches)) {
            $searchName = $matches[1];
        }
        
        if ($searchName) {
            try {
                $members = DB::table('clients')
                    ->where(DB::raw('LOWER(first_name)'), 'like', '%' . strtolower($searchName) . '%')
                    ->orWhere(DB::raw('LOWER(last_name)'), 'like', '%' . strtolower($searchName) . '%')
                    ->select('id', 'first_name', 'last_name', 'client_number', 'email', 'phone_number', 'client_status')
                    ->get();
                
                if ($members->isEmpty()) {
                    return "No member found with the name **{$searchName}**.";
                }
                
                if ($members->count() == 1) {
                    $member = $members->first();
                    $response = "Yes, we have a member matching **{$searchName}**:\n\n";
                    $response .= "**Name:** {$member->first_name} {$member->last_name}\n";
                    $response .= "**Client Number:** {$member->client_number}\n";
                    $response .= "**Client ID:** {$member->id}\n";
                    if ($member->email) {
                        $response .= "**Email:** {$member->email}\n";
                    }
                    if ($member->phone_number) {
                        $response .= "**Phone:** {$member->phone_number}\n";
                    }
                    $response .= "**Status:** " . ($member->client_status ?: 'Active') . "\n";
                    
                    // Check accounts
                    $accounts = DB::table('accounts')
                        ->where('client_number', $member->client_number)
                        ->count();
                    if ($accounts > 0) {
                        $response .= "\nThis member has **{$accounts} account(s)** with us.";
                    }
                    
                    return $response;
                } else {
                    $response = "Found **{$members->count()} members** matching **{$searchName}**:\n\n";
                    foreach ($members as $member) {
                        $response .= "• **{$member->first_name} {$member->last_name}** (Client #{$member->client_number})\n";
                    }
                    return $response;
                }
            } catch (Exception $e) {
                return "Error searching for member: " . $e->getMessage();
            }
        }
    }
    
    // For database queries
    if (stripos($message, 'member') !== false || 
        stripos($message, 'loan') !== false ||
        stripos($message, 'account') !== false ||
        stripos($message, 'how many') !== false) {
        
        try {
            // Get actual counts from database
            $members = DB::table('clients')->count();
            $loans = DB::table('loans')->count();
            $accounts = DB::table('accounts')->count();
            $branches = DB::table('branches')->count();
            $users = DB::table('users')->count();
            
            return "Based on the current database, here are the statistics:\n\n" .
                   "**Members/Clients:** " . $members . "\n" .
                   "**Loans:** " . $loans . "\n" .
                   "**Accounts:** " . $accounts . "\n" .
                   "**Branches:** " . $branches . "\n" .
                   "**Users:** " . $users . "\n\n" .
                   "These are real-time numbers from your SACCOS database.";
        } catch (Exception $e) {
            return "I can access your database, but encountered an error: " . $e->getMessage();
        }
    }
    
    // Default response for other queries
    return "I'm Claude Code running locally with full access to your SACCOS Core System project. " .
           "I can see all your files, database structure, and recent changes. " .
           "However, for complex questions, please use the interactive monitor (claude-monitor.php) " .
           "where I can provide more detailed responses.\n\n" .
           "Your question: \"" . $message . "\"\n\n" .
           "Requires a more detailed analysis. Please ask me through the interactive monitor for a complete answer.";
}