<?php

echo "=== FIXING CURRENT_TIMESTAMP ISSUES ===\n\n";

$migrationsDir = __DIR__ . '/database/migrations';
$migrations = glob($migrationsDir . '/*.php');

$fixedCount = 0;

foreach ($migrations as $migration) {
    $content = file_get_contents($migration);
    $originalContent = $content;
    
    // Replace CURRENT_TIMESTAMP with useCurrent()
    $content = preg_replace(
        '/->default\(CURRENT_TIMESTAMP\)/',
        '->useCurrent()',
        $content
    );
    
    if ($content !== $originalContent) {
        file_put_contents($migration, $content);
        $fixedCount++;
        echo "✅ Fixed " . basename($migration) . "\n";
    }
}

echo "\n=== FIX COMPLETE ===\n";
echo "Fixed $fixedCount files\n";