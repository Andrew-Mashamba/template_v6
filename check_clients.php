<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Checking clients table...\n";

try {
    $clients = DB::table('clients')
        ->select('client_number', 'first_name', 'last_name')
        ->limit(10)
        ->get();
    
    echo "Found " . $clients->count() . " clients:\n";
    
    foreach ($clients as $client) {
        echo "  {$client->client_number} - {$client->first_name} {$client->last_name}\n";
    }
    
    // Check if M006 exists specifically
    $m006Exists = DB::table('clients')->where('client_number', 'M006')->exists();
    echo "\nDoes M006 exist? " . ($m006Exists ? 'YES' : 'NO') . "\n";
    
    if (!$m006Exists) {
        echo "\nAvailable client numbers for testing:\n";
        $availableNumbers = DB::table('clients')
            ->select('client_number')
            ->limit(5)
            ->pluck('client_number');
        
        foreach ($availableNumbers as $number) {
            echo "  {$number}\n";
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 