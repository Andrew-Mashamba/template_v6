<?php

// Bootstrap Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Initialize the app
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Test the DirectClaudeCliService
use App\Services\DirectClaudeCliService;

echo "=== Testing Direct Claude CLI Service ===\n\n";

try {
    $service = new DirectClaudeCliService();
    
    echo "✓ Service initialized successfully\n";
    echo "Claude Path: " . $service->getInfo()['claude_path'] . "\n\n";
    
    // Test a simple message
    echo "Sending message: How many users are in the system?\n";
    echo "Waiting for response...\n\n";
    
    $startTime = microtime(true);
    $response = $service->sendMessage("How many users are in the system? Please query the database and provide the count.", [
        'format' => 'text'
    ]);
    $duration = microtime(true) - $startTime;
    
    if ($response['success']) {
        echo "✓ Response received in " . round($duration, 2) . " seconds:\n";
        echo "----------------------------------------\n";
        echo $response['message'] . "\n";
        echo "----------------------------------------\n";
    } else {
        echo "✗ Error: " . $response['error'] . "\n";
    }
    
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";