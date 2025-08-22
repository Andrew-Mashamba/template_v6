<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\File;

echo "=== REPLACING ALL MIGRATIONS WITH CONSOLIDATED VERSIONS ===\n\n";

// Create full backup first
$backupDir = storage_path('migration_backups/FULL_BACKUP_' . date('Y-m-d_His'));
$migrationsDir = database_path('migrations');
$consolidatedDir = database_path('migrations/consolidated_all');

// Step 1: Create full backup
echo "Step 1: Creating full backup of current migrations...\n";
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Copy all current migrations to backup (excluding consolidated directories)
$currentMigrations = glob($migrationsDir . '/*.php');
$backedUp = 0;

foreach ($currentMigrations as $migration) {
    $filename = basename($migration);
    if (copy($migration, $backupDir . '/' . $filename)) {
        $backedUp++;
    }
}

echo "✅ Backed up $backedUp migration files to: $backupDir\n\n";

// Step 2: Delete all current migrations
echo "Step 2: Deleting all current migration files...\n";
$deleted = 0;

foreach ($currentMigrations as $migration) {
    if (unlink($migration)) {
        $deleted++;
    }
}

echo "✅ Deleted $deleted migration files\n\n";

// Step 3: Copy consolidated migrations to main directory
echo "Step 3: Copying consolidated migrations to main directory...\n";
$consolidatedFiles = glob($consolidatedDir . '/*.php');
$copied = 0;

foreach ($consolidatedFiles as $consolidatedFile) {
    $filename = basename($consolidatedFile);
    
    // Remove '_consolidated' from filename
    $newFilename = str_replace('_consolidated.php', '.php', $filename);
    
    $destination = $migrationsDir . '/' . $newFilename;
    
    if (copy($consolidatedFile, $destination)) {
        echo "✅ Copied: $filename → $newFilename\n";
        $copied++;
    } else {
        echo "❌ Failed: $filename\n";
    }
}

echo "\n✅ Copied $copied consolidated migrations\n\n";

// Step 4: Remove the consolidated directories
echo "Step 4: Cleaning up consolidated directories...\n";

// Remove consolidated_all directory
if (File::deleteDirectory($consolidatedDir)) {
    echo "✅ Removed consolidated_all directory\n";
}

// Remove old consolidated directory if exists
$oldConsolidatedDir = database_path('migrations/consolidated');
if (file_exists($oldConsolidatedDir) && File::deleteDirectory($oldConsolidatedDir)) {
    echo "✅ Removed old consolidated directory\n";
}

echo "\n=== REPLACEMENT COMPLETE ===\n\n";

// Final statistics
$finalMigrations = glob($migrationsDir . '/*.php');
$finalCount = count($finalMigrations);

echo "Original migrations: $backedUp files\n";
echo "Consolidated migrations: $finalCount files\n";
echo "Reduction: " . ($backedUp - $finalCount) . " files (" . round((($backedUp - $finalCount) / $backedUp) * 100, 1) . "%)\n\n";

echo "Backup location: $backupDir\n";
echo "New migrations location: $migrationsDir\n\n";

// Create restore script for full backup
$restoreScript = <<<'PHP'
#!/usr/bin/env php
<?php
// Full restore script for all migrations

$backupDir = __DIR__;
$targetDir = dirname(dirname(__DIR__)) . '/database/migrations';

// First, delete all current migrations
$currentFiles = glob($targetDir . '/*.php');
foreach ($currentFiles as $file) {
    unlink($file);
}

// Then restore from backup
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
echo "Original migration structure has been restored.\n";
PHP;

file_put_contents($backupDir . '/restore_all.php', $restoreScript);
chmod($backupDir . '/restore_all.php', 0755);

echo "Restore script created: $backupDir/restore_all.php\n";
echo "\nIMPORTANT: The migrations table in your database still has records of the old migrations.\n";
echo "To use these new consolidated migrations, you'll need to:\n";
echo "1. Run: php artisan migrate:fresh --seed\n";
echo "   OR\n";
echo "2. Manually update the migrations table to match the new filenames\n";

// Save replacement report
$report = [
    'replacement_date' => date('Y-m-d H:i:s'),
    'original_count' => $backedUp,
    'consolidated_count' => $finalCount,
    'reduction_percentage' => round((($backedUp - $finalCount) / $backedUp) * 100, 1),
    'backup_location' => $backupDir,
    'final_migrations' => array_map('basename', $finalMigrations)
];

file_put_contents('migration_replacement_report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "\nReplacement report saved to: migration_replacement_report.json\n";