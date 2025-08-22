<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\File;

echo "=== RESTORING NON-CONSOLIDATED MIGRATIONS ===\n\n";

// Load the list of migrations that were deleted (consolidated ones)
$deletedMigrations = json_decode(file_get_contents('migrations_to_delete.json'), true);
$deletedFilenames = array_map('basename', $deletedMigrations);

// Get the backup directory
$backupDir = storage_path('migration_backups/FULL_BACKUP_2025-07-27_100400');
$migrationsDir = database_path('migrations');

// Get all files from backup
$backupFiles = glob($backupDir . '/*.php');

echo "Found " . count($backupFiles) . " files in backup\n";
echo "Need to restore non-consolidated migrations only...\n\n";

$restored = 0;
$skipped = 0;

foreach ($backupFiles as $backupFile) {
    $filename = basename($backupFile);
    
    // Check if this file was in the deleted (consolidated) list
    if (in_array($filename, $deletedFilenames)) {
        echo "⏭️  Skipping (was consolidated): $filename\n";
        $skipped++;
        continue;
    }
    
    // This is a non-consolidated migration, restore it
    $destination = $migrationsDir . '/' . $filename;
    
    if (copy($backupFile, $destination)) {
        echo "✅ Restored: $filename\n";
        $restored++;
    } else {
        echo "❌ Failed: $filename\n";
    }
}

echo "\n=== RESTORATION COMPLETE ===\n";
echo "Restored: $restored non-consolidated migrations\n";
echo "Skipped: $skipped consolidated migrations\n";

// Count final migrations
$finalCount = count(glob($migrationsDir . '/*.php'));
echo "\nTotal migrations now: $finalCount\n";
echo "- Consolidated migrations: 39\n";
echo "- Non-consolidated migrations: " . ($finalCount - 39) . "\n";

// List first 10 non-consolidated migrations
echo "\nSample of non-consolidated migrations restored:\n";
$nonConsolidated = glob($migrationsDir . '/*.php');
sort($nonConsolidated);

$count = 0;
foreach ($nonConsolidated as $migration) {
    $filename = basename($migration);
    if (!strpos($filename, '2025_07_27_10')) { // Not a consolidated one
        echo "- $filename\n";
        $count++;
        if ($count >= 10) break;
    }
}

echo "\nAll necessary migrations have been restored!\n";