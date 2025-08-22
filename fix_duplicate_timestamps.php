<?php

echo "=== FIXING DUPLICATE TIMESTAMPS ===\n\n";

$migrationsDir = __DIR__ . '/database/migrations';
$migrations = glob($migrationsDir . '/*.php');

$fixedCount = 0;

foreach ($migrations as $migration) {
    $content = file_get_contents($migration);
    
    // Check if it has both explicit timestamp columns and timestamps() method
    if (preg_match_all('/\$table->timestamp\([\'"](?:created_at|updated_at)[\'"]/', $content, $matches) &&
        strpos($content, '->timestamps()') !== false) {
        
        echo "Found duplicate timestamps in " . basename($migration) . "\n";
        
        // Remove explicit created_at and updated_at lines
        $lines = explode("\n", $content);
        $newLines = [];
        
        foreach ($lines as $line) {
            if (!preg_match('/\$table->timestamp\([\'"](?:created_at|updated_at)[\'"]/', $line)) {
                $newLines[] = $line;
            }
        }
        
        file_put_contents($migration, implode("\n", $newLines));
        $fixedCount++;
        echo "✅ Fixed " . basename($migration) . "\n";
    }
}

echo "\n=== FIX COMPLETE ===\n";
echo "Fixed $fixedCount files\n";