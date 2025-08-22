<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\File;

echo "=== RESTORING ALL MIGRATIONS FROM BACKUPS ===\n\n";

$migrationsDir = database_path('migrations');

// Create migrations directory if it doesn't exist
if (!file_exists($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
    echo "Created migrations directory\n";
}

// List of all backup directories
$backupDirs = [
    storage_path('migration_backups/FULL_BACKUP_2025-07-27_100400'),
    storage_path('migration_backups/MINIMAL_BACKUP_2025-07-27_101407'), 
    storage_path('migration_backups/ORIGINALS_2025-07-27_101533'),
    storage_path('migration_backups/2025-07-27_095012'),
    storage_path('migration_backups/DUPLICATES_2025-07-27_101606'),
];

// Also check in consolidated_all if it still exists
$consolidatedDir = database_path('migrations/consolidated_all');
if (file_exists($consolidatedDir)) {
    $backupDirs[] = $consolidatedDir;
}

echo "Searching in " . count($backupDirs) . " backup directories...\n\n";

// Get all unique migration files from all backups
$allMigrations = [];

foreach ($backupDirs as $backupDir) {
    if (!file_exists($backupDir)) {
        echo "⚠️  Directory not found: $backupDir\n";
        continue;
    }
    
    $files = glob($backupDir . '/*.php');
    foreach ($files as $file) {
        $filename = basename($file);
        
        // Skip restore scripts
        if (strpos($filename, 'restore') !== false) {
            continue;
        }
        
        // Store the file with its path, preferring consolidated versions
        if (!isset($allMigrations[$filename]) || strpos($filename, '2025_07_27_10') === 0) {
            $allMigrations[$filename] = $file;
        }
    }
}

echo "Found " . count($allMigrations) . " unique migration files\n\n";

// Sort by filename to maintain order
ksort($allMigrations);

// Copy all migrations to the migrations directory
$restored = 0;
$failed = 0;

foreach ($allMigrations as $filename => $sourcePath) {
    $destination = $migrationsDir . '/' . $filename;
    
    if (copy($sourcePath, $destination)) {
        echo "✅ Restored: $filename\n";
        $restored++;
    } else {
        echo "❌ Failed: $filename\n";
        $failed++;
    }
}

echo "\n=== RESTORATION COMPLETE ===\n";
echo "Successfully restored: $restored files\n";
echo "Failed: $failed files\n";

// Verify the restored migrations
$finalCount = count(glob($migrationsDir . '/*.php'));
echo "Total files in migrations directory: $finalCount\n\n";

// List the first 10 restored files
echo "Sample of restored migrations:\n";
$restoredFiles = glob($migrationsDir . '/*.php');
sort($restoredFiles);

for ($i = 0; $i < min(10, count($restoredFiles)); $i++) {
    echo "  - " . basename($restoredFiles[$i]) . "\n";
}

if (count($restoredFiles) > 10) {
    echo "  ... and " . (count($restoredFiles) - 10) . " more\n";
}

// Create restoration report
$report = [
    'restoration_date' => date('Y-m-d H:i:s'),
    'backup_dirs_searched' => array_map('basename', $backupDirs),
    'total_found' => count($allMigrations),
    'restored' => $restored,
    'failed' => $failed,
    'final_count' => $finalCount
];

file_put_contents('full_restoration_report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "\nRestoration report saved to: full_restoration_report.json\n";