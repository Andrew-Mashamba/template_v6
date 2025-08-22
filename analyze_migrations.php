<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$migrationsPath = database_path('migrations');
$migrations = glob($migrationsPath . '/*.php');

// Group migrations by table name
$migrationsByTable = [];
$duplicates = [];
$emptyMigrations = [];

foreach ($migrations as $migration) {
    $filename = basename($migration);
    $content = file_get_contents($migration);
    
    // Check if migration is empty (no actual schema changes)
    if (preg_match('/Schema::table\([\'"](\w+)[\'"],\s*function.*?\{\s*\/\/\s*\}/s', $content)) {
        $emptyMigrations[] = $filename;
    }
    
    // Extract table names
    preg_match_all('/Schema::(create|table)\([\'"](\w+)[\'"]/', $content, $matches);
    
    if (!empty($matches[2])) {
        foreach ($matches[2] as $table) {
            if (!isset($migrationsByTable[$table])) {
                $migrationsByTable[$table] = [];
            }
            $migrationsByTable[$table][] = $filename;
        }
    }
}

echo "=== MIGRATION ANALYSIS ===\n\n";

echo "EMPTY MIGRATIONS (can be deleted):\n";
foreach ($emptyMigrations as $migration) {
    echo "  - $migration\n";
}

echo "\nTABLES WITH MULTIPLE MIGRATIONS:\n";
foreach ($migrationsByTable as $table => $migrations) {
    if (count($migrations) > 2) {
        echo "\n$table table (" . count($migrations) . " migrations):\n";
        foreach ($migrations as $migration) {
            $status = DB::table('migrations')->where('migration', pathinfo($migration, PATHINFO_FILENAME))->exists() ? '[RAN]' : '[PENDING]';
            echo "  $status $migration\n";
        }
    }
}

// Check for duplicate class names
echo "\nCHECKING FOR DUPLICATE CLASS NAMES:\n";
$classNames = [];
foreach ($migrations as $migration) {
    if (!file_exists($migration)) continue;
    $content = file_get_contents($migration);
    if (preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
        $className = $matches[1];
        if (!isset($classNames[$className])) {
            $classNames[$className] = [];
        }
        $classNames[$className][] = basename($migration);
    }
}

foreach ($classNames as $className => $files) {
    if (count($files) > 1) {
        echo "\nDuplicate class '$className' found in:\n";
        foreach ($files as $file) {
            echo "  - $file\n";
        }
    }
}

// Analyze clients table migrations specifically
echo "\n=== CLIENTS TABLE MIGRATION CONSOLIDATION PLAN ===\n";
$clientsMigrations = $migrationsByTable['clients'] ?? [];
if (!empty($clientsMigrations)) {
    echo "\nCurrent clients table migrations:\n";
    foreach ($clientsMigrations as $migration) {
        $status = DB::table('migrations')->where('migration', pathinfo($migration, PATHINFO_FILENAME))->exists() ? '[RAN]' : '[PENDING]';
        echo "  $status $migration\n";
    }
    
    echo "\nRecommended consolidation:\n";
    echo "  1. Keep the main create table migration\n";
    echo "  2. Consolidate all 'add fields' migrations into one\n";
    echo "  3. Delete empty migrations\n";
}