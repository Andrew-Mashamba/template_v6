<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== KEEPING ONLY MIGRATIONS FROM migration_tables_list.json ===\n\n";

// Read the migration list file properly
$jsonContent = file_get_contents('migration_tables_list.json');

// The file appears to be a partial JSON, let's extract the table mappings
$lines = explode("\n", $jsonContent);
$tables = [];

foreach ($lines as $line) {
    // Extract table name and file from lines like: "table_name": "filename.php",
    if (preg_match('/"([^"]+)":\s*"([^"]+)"/', $line, $matches)) {
        $tables[$matches[1]] = $matches[2];
    }
}

echo "Found " . count($tables) . " tables in migration_tables_list.json\n\n";

$migrationsDir = database_path('migrations');
$currentMigrations = glob($migrationsDir . '/*.php');

echo "Current migrations in directory: " . count($currentMigrations) . "\n\n";

// Get list of files we should keep
$filesToKeep = array_unique(array_values($tables));

// Also keep Laravel framework migrations that might not be in the list
$essentialMigrations = [
    'create_password_resets_table',
    'create_failed_jobs_table',
    'create_personal_access_tokens_table',
    'create_sessions_table',
    'create_jobs_table'
];

// Add essential migrations to keep list
foreach ($currentMigrations as $migration) {
    $filename = basename($migration);
    foreach ($essentialMigrations as $essential) {
        if (strpos($filename, $essential) !== false && !in_array($filename, $filesToKeep)) {
            $filesToKeep[] = $filename;
            echo "Adding essential migration: $filename\n";
        }
    }
}

echo "\nFiles to keep: " . count($filesToKeep) . "\n\n";

// Create a backup before deleting
$backupDir = storage_path('migration_backups/CLEANUP_' . date('Y-m-d_His'));
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Process each current migration
$kept = 0;
$deleted = 0;

foreach ($currentMigrations as $migration) {
    $filename = basename($migration);
    
    if (in_array($filename, $filesToKeep)) {
        echo "✅ Keeping: $filename\n";
        $kept++;
    } else {
        // Backup and delete
        copy($migration, $backupDir . '/' . $filename);
        if (unlink($migration)) {
            echo "❌ Deleted: $filename\n";
            $deleted++;
        }
    }
}

echo "\n=== CLEANUP COMPLETE ===\n";
echo "Kept: $kept files\n";
echo "Deleted: $deleted files\n";
echo "Backup of deleted files: $backupDir\n";

// Final verification
$finalCount = count(glob($migrationsDir . '/*.php'));
echo "\nFinal migration count: $finalCount files\n";

// Check for missing migrations
$missingMigrations = [];
foreach ($filesToKeep as $shouldExist) {
    if (!file_exists($migrationsDir . '/' . $shouldExist)) {
        $missingMigrations[] = $shouldExist;
    }
}

if (count($missingMigrations) > 0) {
    echo "\n⚠️  WARNING: The following migrations are missing:\n";
    foreach ($missingMigrations as $missing) {
        echo "  - $missing\n";
    }
}

// Save cleanup report
$report = [
    'cleanup_date' => date('Y-m-d H:i:s'),
    'initial_count' => count($currentMigrations),
    'kept' => $kept,
    'deleted' => $deleted,
    'final_count' => $finalCount,
    'expected_count' => count($filesToKeep),
    'missing' => $missingMigrations
];

file_put_contents('cleanup_report.json', json_encode($report, JSON_PRETTY_PRINT));