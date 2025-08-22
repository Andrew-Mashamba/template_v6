<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\File;

echo "=== REMOVING DUPLICATE TABLE MIGRATIONS ===\n\n";

$migrationsDir = database_path('migrations');
$allMigrations = glob($migrationsDir . '/*.php');

// Group migrations by table name
$tableMap = [];

foreach ($allMigrations as $migration) {
    $filename = basename($migration);
    $content = file_get_contents($migration);
    
    // Extract table name from create or table statements
    if (preg_match('/Schema::create\([\'"](\w+)[\'"]/', $content, $matches)) {
        $tableName = $matches[1];
        if (!isset($tableMap[$tableName])) {
            $tableMap[$tableName] = [];
        }
        $tableMap[$tableName][] = [
            'file' => $filename,
            'path' => $migration,
            'type' => 'create',
            'timestamp' => substr($filename, 0, 17) // Extract timestamp
        ];
    }
}

// Find tables with duplicates
$duplicates = [];
$toDelete = [];

foreach ($tableMap as $table => $migrations) {
    if (count($migrations) > 1) {
        // Sort by timestamp, keep the latest
        usort($migrations, function($a, $b) {
            return strcmp($b['timestamp'], $a['timestamp']);
        });
        
        echo "Table '$table' has " . count($migrations) . " create migrations:\n";
        $keep = null;
        
        foreach ($migrations as $index => $migration) {
            if ($index === 0) {
                // Keep the latest (or consolidated) one
                echo "  ✅ KEEP: {$migration['file']}\n";
                $keep = $migration['file'];
            } else {
                echo "  ❌ DELETE: {$migration['file']}\n";
                $toDelete[] = $migration['path'];
            }
        }
        echo "\n";
    }
}

// Also remove the old add_consolidated_fields_to_clients migration since we have the full consolidated one
$oldClientMigration = $migrationsDir . '/2025_07_27_085653_add_consolidated_fields_to_clients_table.php';
if (file_exists($oldClientMigration)) {
    echo "Also removing old partial consolidation:\n";
    echo "  ❌ DELETE: 2025_07_27_085653_add_consolidated_fields_to_clients_table.php\n\n";
    $toDelete[] = $oldClientMigration;
}

echo "Total duplicates to delete: " . count($toDelete) . "\n\n";

if (count($toDelete) === 0) {
    echo "No duplicates found!\n";
    exit(0);
}

// Create backup
$backupDir = storage_path('migration_backups/DUPLICATES_' . date('Y-m-d_His'));
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Delete duplicates
$deleted = 0;
foreach ($toDelete as $path) {
    $filename = basename($path);
    // Backup first
    copy($path, $backupDir . '/' . $filename);
    
    if (unlink($path)) {
        $deleted++;
    }
}

echo "\n✅ Deleted $deleted duplicate migrations\n";
echo "Backup saved to: $backupDir\n";

// Final count
$remaining = count(glob($migrationsDir . '/*.php'));
echo "\nFinal migration count: $remaining files\n";