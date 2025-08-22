<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// Load consolidation data
$consolidationData = json_decode(file_get_contents('consolidation_needed.json'), true);

// Get all migration files
$migrationsPath = database_path('migrations');
$allMigrations = glob($migrationsPath . '/*.php');

echo "=== IDENTIFYING REDUNDANT MIGRATIONS ===\n\n";

$redundantMigrations = [];
$keepMigrations = [];
$consolidatedTables = [];

// First, identify all migrations that were consolidated
foreach ($consolidationData as $tableName => $migrations) {
    // Check if table exists and was consolidated
    $tableExists = DB::select("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = ?)", [$tableName])[0]->exists;
    
    if ($tableExists) {
        $consolidatedTables[] = $tableName;
        
        // Add all create and update migrations to redundant list
        foreach ($migrations['create'] as $migration) {
            $redundantMigrations[] = $migration;
        }
        
        foreach ($migrations['updates'] as $migration) {
            $redundantMigrations[] = $migration;
        }
    }
}

// Remove duplicates
$redundantMigrations = array_unique($redundantMigrations);

// Now scan all migrations to categorize them
$stats = [
    'total' => 0,
    'redundant' => 0,
    'keep' => 0,
    'empty' => 0
];

echo "MIGRATIONS TO DELETE (Redundant/Consolidated):\n";
echo "=============================================\n\n";

foreach ($allMigrations as $migrationPath) {
    $filename = basename($migrationPath);
    $stats['total']++;
    
    // Skip if it's in consolidated directory
    if (strpos($migrationPath, 'consolidated') !== false) {
        continue;
    }
    
    // Check if this migration is in our redundant list
    if (in_array($filename, $redundantMigrations)) {
        $stats['redundant']++;
        
        // Get file size
        $size = filesize($migrationPath);
        $sizeKb = round($size / 1024, 2);
        
        // Check which table this migration affects
        $content = file_get_contents($migrationPath);
        $affectedTable = '';
        
        if (preg_match('/Schema::create\([\'"](\w+)[\'"]/', $content, $matches)) {
            $affectedTable = $matches[1];
        } elseif (preg_match('/Schema::table\([\'"](\w+)[\'"]/', $content, $matches)) {
            $affectedTable = $matches[1];
        }
        
        echo "❌ $filename";
        if ($affectedTable) {
            echo " (table: $affectedTable)";
        }
        echo " - {$sizeKb}KB\n";
        
    } else {
        $stats['keep']++;
        $keepMigrations[] = $filename;
    }
}

echo "\n\nMIGRATIONS TO KEEP:\n";
echo "==================\n\n";

// Show first 20 migrations to keep
$keepCount = count($keepMigrations);
$showCount = min(20, $keepCount);

for ($i = 0; $i < $showCount; $i++) {
    echo "✅ {$keepMigrations[$i]}\n";
}

if ($keepCount > 20) {
    echo "... and " . ($keepCount - 20) . " more\n";
}

echo "\n\n=== SUMMARY ===\n";
echo "Total migration files: {$stats['total']}\n";
echo "Redundant (to delete): {$stats['redundant']}\n";
echo "Keep: {$stats['keep']}\n";
echo "Consolidated tables: " . count($consolidatedTables) . "\n";

// Save list of files to delete
$deleteList = [];
foreach ($allMigrations as $migrationPath) {
    $filename = basename($migrationPath);
    if (in_array($filename, $redundantMigrations)) {
        $deleteList[] = $migrationPath;
    }
}

file_put_contents('migrations_to_delete.json', json_encode($deleteList, JSON_PRETTY_PRINT));

echo "\nList of migrations to delete saved to: migrations_to_delete.json\n";
echo "\nTo proceed with deletion, run: php delete_redundant_migrations.php\n";