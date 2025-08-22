<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\AiAgentService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

echo "=== Testing Dynamic Field Extraction ===\n\n";

$aiService = new AiAgentService();

// Test 1: Check if we can get fields for known tables
echo "1. Testing field extraction for known tables:\n";
$knownTables = ['users', 'clients', 'accounts', 'loans', 'transactions'];

foreach ($knownTables as $table) {
    try {
        $fields = $aiService->getTableFields($table);
        echo "   {$table}: " . count($fields) . " fields\n";
        if (count($fields) <= 5) {
            echo "      Fields: " . implode(', ', $fields) . "\n";
        } else {
            echo "      Fields: " . implode(', ', array_slice($fields, 0, 5)) . "... (and " . (count($fields) - 5) . " more)\n";
        }
    } catch (Exception $e) {
        echo "   {$table}: ERROR - " . $e->getMessage() . "\n";
    }
}

echo "\n2. Checking for missing tables:\n";
$missingTables = $aiService->getMissingTables();
if (empty($missingTables)) {
    echo "   No missing tables found!\n";
} else {
    echo "   Found " . count($missingTables) . " missing tables:\n";
    foreach ($missingTables as $table) {
        echo "   - {$table}\n";
    }
}

echo "\n3. Testing complete table index:\n";
$completeIndex = $aiService->getCompleteTableIndex();
echo "   Total tables in index: " . count($completeIndex) . "\n";

// Show a few examples of dynamic fields
echo "\n4. Sample dynamic fields from complete index:\n";
$sampleTables = array_slice(array_keys($completeIndex), 0, 3);
foreach ($sampleTables as $table) {
    $info = $completeIndex[$table];
    echo "   {$table}:\n";
    echo "      Description: {$info['description']}\n";
    echo "      Fields: " . implode(', ', array_slice($info['fields'], 0, 8)) . "...\n";
    echo "      Keywords: " . implode(', ', $info['keywords']) . "\n";
    echo "\n";
}

echo "=== Test Complete ===\n"; 