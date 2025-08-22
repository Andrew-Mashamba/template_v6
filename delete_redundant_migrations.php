<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\File;

// Load list of migrations to delete
if (!file_exists('migrations_to_delete.json')) {
    echo "Error: migrations_to_delete.json not found. Run identify_redundant_migrations.php first.\n";
    exit(1);
}

$migrationsToDelete = json_decode(file_get_contents('migrations_to_delete.json'), true);

echo "=== DELETING REDUNDANT MIGRATIONS ===\n\n";
echo "Found " . count($migrationsToDelete) . " migrations to delete.\n\n";

// Create backup directory
$backupDir = storage_path('migration_backups/' . date('Y-m-d_His'));
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

echo "Creating backup in: $backupDir\n\n";

// Backup and delete each migration
$deleted = 0;
$failed = 0;

foreach ($migrationsToDelete as $migrationPath) {
    $filename = basename($migrationPath);
    
    // Skip if file doesn't exist
    if (!file_exists($migrationPath)) {
        echo "⚠️  Skipped (not found): $filename\n";
        continue;
    }
    
    // Create backup
    $backupPath = $backupDir . '/' . $filename;
    if (copy($migrationPath, $backupPath)) {
        // Delete original file
        if (unlink($migrationPath)) {
            echo "✅ Deleted: $filename\n";
            $deleted++;
        } else {
            echo "❌ Failed to delete: $filename\n";
            $failed++;
        }
    } else {
        echo "❌ Failed to backup: $filename\n";
        $failed++;
    }
}

echo "\n\n=== DELETION COMPLETE ===\n";
echo "Successfully deleted: $deleted migrations\n";
echo "Failed: $failed\n";
echo "Backup location: $backupDir\n";

// Verify consolidated migrations still exist
$consolidatedDir = database_path('migrations/consolidated_all');
$consolidatedCount = count(glob($consolidatedDir . '/*.php'));

echo "\nConsolidated migrations: $consolidatedCount files in $consolidatedDir\n";

// Create restoration script
$restoreScript = <<<'PHP'
#!/usr/bin/env php
<?php
// Restore script for migration backup

$backupDir = __DIR__;
$targetDir = dirname(dirname(__DIR__)) . '/database/migrations';

$files = glob($backupDir . '/*.php');

echo "Restoring " . count($files) . " migration files...\n";

$restored = 0;
foreach ($files as $file) {
    $filename = basename($file);
    $target = $targetDir . '/' . $filename;
    
    if (copy($file, $target)) {
        echo "Restored: $filename\n";
        $restored++;
    } else {
        echo "Failed: $filename\n";
    }
}

echo "\nRestored $restored files to $targetDir\n";
PHP;

file_put_contents($backupDir . '/restore.php', $restoreScript);
chmod($backupDir . '/restore.php', 0755);

echo "\nRestore script created: $backupDir/restore.php\n";
echo "To restore migrations, run: php $backupDir/restore.php\n";

// Save deletion report
$report = [
    'deletion_date' => date('Y-m-d H:i:s'),
    'deleted_count' => $deleted,
    'failed_count' => $failed,
    'backup_location' => $backupDir,
    'consolidated_migrations' => $consolidatedCount,
    'deleted_files' => array_map('basename', $migrationsToDelete)
];

file_put_contents('deletion_report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "\nDeletion report saved to: deletion_report.json\n";