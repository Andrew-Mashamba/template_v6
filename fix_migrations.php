<?php

/**
 * Script to fix migration files that use named classes instead of anonymous classes
 */

$migrationDir = __DIR__ . '/database/migrations';
$files = glob($migrationDir . '/2025_01_27_*.php');

echo "🔧 Fixing migration files...\n";

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Check if file uses named class
    if (preg_match('/^class\s+(\w+)\s+extends\s+Migration\s*\{/m', $content)) {
        echo "Fixing: " . basename($file) . "\n";
        
        // Replace named class with anonymous class
        $content = preg_replace(
            '/^class\s+(\w+)\s+extends\s+Migration\s*\{/m',
            'return new class extends Migration {',
            $content
        );
        
        // Replace closing brace with semicolon
        $content = preg_replace('/^\}\s*$/m', '};', $content);
        
        file_put_contents($file, $content);
        echo "  ✅ Fixed\n";
    }
}

echo "✅ All migration files fixed!\n";
