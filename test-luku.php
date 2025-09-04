<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\NbcPayments\LukuService;
use Illuminate\Support\Facades\Log;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "=== LUKU Service Test ===\n";
echo "Testing LUKU lookup functionality\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Create service instance
    $service = new LukuService();
    
    // Test data
    $meterNumber = '43026323915';
    $accountNumber = '011191000035';
    
    echo "Testing lookup for:\n";
    echo "- Meter Number: $meterNumber\n";
    echo "- Account Number: $accountNumber\n\n";
    
    // Perform lookup
    echo "Sending lookup request...\n";
    $result = $service->lookup($meterNumber, $accountNumber);
    
    echo "\n=== RESULT ===\n";
    echo json_encode($result, JSON_PRETTY_PRINT) . "\n";
    
    if (isset($result['error'])) {
        echo "\nError occurred: " . $result['error'] . "\n";
    } else {
        echo "\nLookup successful!\n";
        if (isset($result['customer_name'])) {
            echo "Customer: " . $result['customer_name'] . "\n";
        }
        if (isset($result['amount'])) {
            echo "Amount: " . $result['amount'] . "\n";
        }
    }
    
} catch (Exception $e) {
    echo "\nException occurred:\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "\n=== Test Complete ===\n";
echo "Check /var/www/html/template/storage/logs/laravel-" . date('Y-m-d') . ".log for detailed logs\n";