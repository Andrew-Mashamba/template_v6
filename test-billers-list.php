<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\NbcBillsPaymentService;

echo "\n=====================================\n";
echo "NBC BILLERS LIST TEST\n";
echo "=====================================\n\n";

try {
    $service = new NbcBillsPaymentService();
    
    echo "Fetching available billers from NBC...\n\n";
    $billers = $service->getBillers();
    
    $allBillers = $billers['flat'] ?? [];
    
    echo "Total active billers found: " . count($allBillers) . "\n\n";
    
    if (count($allBillers) === 0) {
        echo "❌ No billers available in the NBC system\n";
    } else {
        echo "Available Billers:\n";
        echo "==================\n\n";
        foreach ($allBillers as $index => $biller) {
            echo ($index + 1) . ". Service: " . $biller['shortName'] . "\n";
            echo "   SP Code: " . $biller['spCode'] . "\n";
            echo "   Active: " . ($biller['active'] ? 'Yes' : 'No') . "\n";
            if (isset($biller['fullName'])) {
                echo "   Full Name: " . $biller['fullName'] . "\n";
            }
            if (isset($biller['category'])) {
                echo "   Category: " . $biller['category'] . "\n";
            }
            echo "\n";
        }
        
        // Group by category
        $grouped = $billers['grouped'] ?? [];
        if (!empty($grouped)) {
            echo "Billers by Category:\n";
            echo "====================\n\n";
            foreach ($grouped as $category => $categoryBillers) {
                echo "Category: " . $category . " (" . count($categoryBillers) . " billers)\n";
                foreach ($categoryBillers as $biller) {
                    echo "  - " . $biller['shortName'] . " (" . $biller['spCode'] . ")\n";
                }
                echo "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}

echo "=====================================\n";
echo "Test completed at: " . date('Y-m-d H:i:s') . "\n";
echo "=====================================\n\n";