<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\File;

echo "=== RESTORING MIGRATIONS FROM BACKUP ===\n\n";

// Load the migration tables list
$migrationData = json_decode(file_get_contents('migration_tables_list.json'), true);

if (!isset($migrationData['tables'])) {
    echo "Error: Could not find tables data in migration_tables_list.json\n";
    exit(1);
}

$tables = $migrationData['tables'];
$migrationsDir = database_path('migrations');

// Check if migrations directory exists
if (!file_exists($migrationsDir)) {
    mkdir($migrationsDir, 0755, true);
    echo "Created migrations directory\n";
}

// List of backup directories to search
$backupDirs = [
    storage_path('migration_backups/FULL_BACKUP_2025-07-27_100400'),
    storage_path('migration_backups/MINIMAL_BACKUP_2025-07-27_101407'),
    storage_path('migration_backups/ORIGINALS_2025-07-27_101533'),
    storage_path('migration_backups/2025-07-27_095012'),
    storage_path('migration_backups/DUPLICATES_2025-07-27_101606'),
];

echo "Searching for migration files in backup directories...\n\n";

$restored = 0;
$notFound = [];

// For each table and its migration file
foreach ($tables as $tableName => $migrationFile) {
    $found = false;
    
    // Search in all backup directories
    foreach ($backupDirs as $backupDir) {
        $backupFile = $backupDir . '/' . $migrationFile;
        
        if (file_exists($backupFile)) {
            // Copy the file to migrations directory
            $destination = $migrationsDir . '/' . $migrationFile;
            
            if (copy($backupFile, $destination)) {
                echo "✅ Restored: $migrationFile (table: $tableName)\n";
                $restored++;
                $found = true;
                break;
            } else {
                echo "❌ Failed to copy: $migrationFile\n";
            }
        }
    }
    
    if (!$found) {
        $notFound[] = [
            'table' => $tableName,
            'file' => $migrationFile
        ];
    }
}

echo "\n=== RESTORATION SUMMARY ===\n";
echo "Total tables: " . count($tables) . "\n";
echo "Successfully restored: $restored\n";
echo "Not found: " . count($notFound) . "\n";

if (count($notFound) > 0) {
    echo "\n=== MIGRATIONS NOT FOUND IN BACKUPS ===\n";
    foreach ($notFound as $missing) {
        echo "❌ {$missing['file']} (table: {$missing['table']})\n";
    }
    
    // Try to find these files in the consolidated directory
    echo "\nSearching in consolidated migrations...\n";
    
    $consolidatedFound = 0;
    foreach ($notFound as $missing) {
        // Check if it's a consolidated migration
        if (strpos($missing['file'], '2025_07_27_10') === 0) {
            echo "  This is a consolidated migration: {$missing['file']}\n";
            $consolidatedFound++;
        }
    }
    
    if ($consolidatedFound > 0) {
        echo "\n💡 Note: $consolidatedFound consolidated migrations may have been created during consolidation process.\n";
        echo "These need to be regenerated from the database structure.\n";
    }
}

// Also restore any migration files that were marked as modify-only
if (isset($migrationData['modify_only_migrations'])) {
    echo "\n=== RESTORING MODIFY-ONLY MIGRATIONS ===\n";
    
    foreach ($migrationData['modify_only_migrations'] as $modifyMigration) {
        $found = false;
        
        foreach ($backupDirs as $backupDir) {
            $backupFile = $backupDir . '/' . $modifyMigration;
            
            if (file_exists($backupFile)) {
                $destination = $migrationsDir . '/' . $modifyMigration;
                
                if (copy($backupFile, $destination)) {
                    echo "✅ Restored: $modifyMigration (modify-only)\n";
                    $restored++;
                    $found = true;
                    break;
                }
            }
        }
        
        if (!$found) {
            echo "❌ Not found: $modifyMigration\n";
        }
    }
}

echo "\n=== FINAL RESULT ===\n";
$finalCount = count(glob($migrationsDir . '/*.php'));
echo "Total migration files restored: $finalCount\n";

// Create a restoration report
$report = [
    'restoration_date' => date('Y-m-d H:i:s'),
    'total_tables' => count($tables),
    'restored_count' => $restored,
    'not_found' => $notFound,
    'final_count' => $finalCount,
    'backup_dirs_searched' => $backupDirs
];

file_put_contents('restoration_report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "\nRestoration report saved to: restoration_report.json\n";