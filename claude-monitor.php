#!/usr/bin/env php
<?php

/**
 * Claude Code Bridge Monitor
 * 
 * This script monitors for requests from Laravel and displays them for Claude Code to answer.
 * Run this in a terminal where Claude Code can see and respond to the prompts.
 * 
 * Usage: php claude-monitor.php
 */

// Increase memory limit for large responses
ini_set('memory_limit', '512M');

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "\033[1;36m";
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║           Claude Code Bridge Monitor - SACCOS System          ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\033[0m\n";

echo "\033[1;32m✓ Connected to SACCOS Core System\033[0m\n";
echo "\033[1;33m⚡ Monitoring for requests from Laravel...\033[0m\n\n";

$service = new \App\Services\LocalClaudeService();
$markerFile = storage_path('app/claude-bridge/claude-monitor.active');
$processedRequests = [];

// Signal handler for clean shutdown
pcntl_async_signals(true);
pcntl_signal(SIGINT, function() use ($markerFile) {
    echo "\n\033[1;31m✗ Shutting down Claude Bridge Monitor...\033[0m\n";
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
        echo "\033[1;35m📨 New Request from Laravel\033[0m\n";
        echo "\033[1;36m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n";
        echo "\033[1;32mRequest ID:\033[0m " . $request['id'] . "\n";
        echo "\033[1;32mUser:\033[0m " . ($request['context']['user_name'] ?? 'Unknown') . "\n";
        echo "\033[1;32mTimestamp:\033[0m " . ($request['context']['timestamp'] ?? 'N/A') . "\n";
        echo "\033[1;32mSession:\033[0m " . ($request['context']['session_id'] ?? 'N/A') . "\n";
        echo "\n\033[1;33mMessage:\033[0m\n";
        echo "\033[1;37m" . $request['message'] . "\033[0m\n";
        echo "\033[1;36m━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\033[0m\n";
        
        // Provide context to help Claude Code answer
        echo "\n\033[1;34m📌 Context for Claude Code:\033[0m\n";
        echo "This is a question from the SACCOS Laravel application.\n";
        echo "You have full knowledge of the project at: " . ($request['context']['project_path'] ?? 'N/A') . "\n";
        
        // Check if it's a database query
        if (stripos($request['message'], 'member') !== false || 
            stripos($request['message'], 'loan') !== false ||
            stripos($request['message'], 'account') !== false ||
            stripos($request['message'], 'how many') !== false) {
            
            echo "\n\033[1;33m💡 This looks like a database query. Here's some real data:\033[0m\n";
            
            try {
                // Get actual counts from database
                $members = DB::table('clients')->count();
                $loans = DB::table('loans')->count();
                $accounts = DB::table('accounts')->count();
                $branches = DB::table('branches')->count();
                $users = DB::table('users')->count();
                
                echo "- Members/Clients: " . $members . "\n";
                echo "- Loans: " . $loans . "\n";
                echo "- Accounts: " . $accounts . "\n";
                echo "- Branches: " . $branches . "\n";
                echo "- Users: " . $users . "\n";
            } catch (Exception $e) {
                echo "Could not fetch database stats: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n\033[1;35m📝 Please provide your response:\033[0m\n";
        echo "\033[1;33m(Copy your response here, then type 'END' on a new line and press Enter)\033[0m\n\n";
        
        // Collect multi-line response
        $response = '';
        while (true) {
            $line = readline();
            if ($line === 'END') {
                break;
            }
            $response .= $line . "\n";
        }
        
        // Send response back
        $service->sendResponse($request['id'], trim($response), [
            'answered_by' => 'Claude Code',
            'answered_at' => now()->toIso8601String()
        ]);
        
        echo "\033[1;32m✓ Response sent back to Laravel!\033[0m\n\n";
        
        // Keep only last 100 processed requests in memory
        if (count($processedRequests) > 100) {
            $processedRequests = array_slice($processedRequests, -100);
        }
    }
    
    // Brief pause before checking again
    usleep(500000); // 500ms
}