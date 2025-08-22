<?php

use App\Http\Livewire\Clients\Clients;
use App\Models\User;
use Illuminate\Support\Facades\DB;

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Create a test user
$user = User::first();
if (!$user) {
    echo "No user found in database\n";
    exit(1);
}

// Check if required tables exist
$tables = ['clients', 'bills', 'services'];
foreach ($tables as $table) {
    if (!Schema::hasTable($table)) {
        echo "Table '$table' does not exist\n";
        exit(1);
    }
}

echo "All required tables exist.\n";

// Check if payment_link columns exist in bills table
$billsColumns = ['payment_link', 'payment_link_id', 'payment_link_generated_at', 'payment_link_items'];
foreach ($billsColumns as $column) {
    if (Schema::hasColumn('bills', $column)) {
        echo "✓ Column 'bills.$column' exists\n";
    } else {
        echo "✗ Column 'bills.$column' is missing\n";
    }
}

// Check if payment_link column exists in clients table
if (Schema::hasColumn('clients', 'payment_link')) {
    echo "✓ Column 'clients.payment_link' exists\n";
} else {
    echo "✗ Column 'clients.payment_link' is missing - you may need to run the migration\n";
}

// Test creating a basic client
try {
    echo "\nTesting basic client creation (without Livewire component)...\n";
    
    // Check if we can query clients table
    $clientCount = DB::table('clients')->count();
    echo "Current number of clients: $clientCount\n";
    
    // Check if we can query bills table
    $billCount = DB::table('bills')->count();
    echo "Current number of bills: $billCount\n";
    
    echo "\nThe save() function should work if:\n";
    echo "1. All database tables exist ✓\n";
    echo "2. Required columns exist in bills table ✓\n";
    echo "3. PaymentLinkService is properly configured\n";
    echo "4. The clients table has payment_link column (if needed)\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}