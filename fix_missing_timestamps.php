<?php

echo "=== FIXING MISSING TIMESTAMPS IN MIGRATIONS ===\n\n";

$migrationsDir = __DIR__ . '/database/migrations';
$migrations = glob($migrationsDir . '/*.php');

$fixedCount = 0;

foreach ($migrations as $migration) {
    $content = file_get_contents($migration);
    
    // Check if migration has an index on created_at but no timestamps()
    if (strpos($content, "index(['created_at']") !== false || 
        strpos($content, 'index(["created_at"]') !== false ||
        strpos($content, "index(['status', 'created_at']") !== false ||
        strpos($content, 'index(["status", "created_at"]') !== false) {
        
        if (strpos($content, '->timestamps()') === false) {
            echo "Found missing timestamps() in " . basename($migration) . "\n";
            
            // Find where to insert timestamps() - before the indexes
            $lines = explode("\n", $content);
            $insertLine = -1;
            
            for ($i = count($lines) - 1; $i >= 0; $i--) {
                if (strpos($lines[$i], '->index(') !== false && $insertLine == -1) {
                    // Found first index from bottom
                    $insertLine = $i;
                } elseif (strpos($lines[$i], 'table->') !== false && $insertLine != -1) {
                    // Found last column definition before indexes
                    $insertLine = $i + 1;
                    break;
                }
            }
            
            if ($insertLine > 0) {
                // Insert timestamps() line
                $indent = '            ';
                array_splice($lines, $insertLine, 0, [$indent . '$table->timestamps();']);
                
                // Write back
                file_put_contents($migration, implode("\n", $lines));
                $fixedCount++;
                echo "✅ Fixed " . basename($migration) . "\n";
            }
        }
    }
}

echo "\n=== TIMESTAMP FIX COMPLETE ===\n";
echo "Fixed $fixedCount files\n";