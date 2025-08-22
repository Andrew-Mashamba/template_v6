<?php

echo "=== FIXING DUPLICATE INDEXES IN MIGRATIONS ===\n\n";

$migrationsDir = __DIR__ . '/database/migrations';
$migrations = glob($migrationsDir . '/*.php');

$fixedCount = 0;

foreach ($migrations as $migration) {
    $content = file_get_contents($migration);
    $lines = explode("\n", $content);
    
    // Track indexes to find duplicates
    $indexes = [];
    $duplicates = [];
    
    // First pass: find duplicate indexes
    foreach ($lines as $lineNum => $line) {
        if (preg_match('/->index\(\[([\'"]?)(\w+)([\'"]?)\]\)/', $line, $matches)) {
            $indexName = $matches[2];
            if (isset($indexes[$indexName])) {
                $duplicates[$lineNum] = $indexName;
                echo "Found duplicate index '$indexName' in " . basename($migration) . "\n";
            } else {
                $indexes[$indexName] = $lineNum;
            }
        }
    }
    
    // Second pass: remove duplicate lines
    if (count($duplicates) > 0) {
        $newLines = [];
        foreach ($lines as $lineNum => $line) {
            if (!isset($duplicates[$lineNum])) {
                $newLines[] = $line;
            }
        }
        
        // Write back the fixed content
        file_put_contents($migration, implode("\n", $newLines));
        $fixedCount++;
        echo "✅ Fixed " . basename($migration) . "\n";
    }
}

echo "\n=== DUPLICATE INDEX FIX COMPLETE ===\n";
echo "Fixed $fixedCount files\n";