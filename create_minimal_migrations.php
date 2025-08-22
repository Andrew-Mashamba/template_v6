<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "=== CREATING MINIMAL MIGRATION SET ===\n\n";

// Get all tables that actually exist in the database
$existingTables = DB::select("
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema = 'public' 
    AND table_type = 'BASE TABLE'
    AND table_name != 'migrations'
");

$tableNames = array_map(function($table) {
    return $table->table_name;
}, $existingTables);

echo "Found " . count($tableNames) . " tables in database\n\n";

// Essential Laravel migrations to always keep
$essentialMigrations = [
    'create_password_resets_table',
    'create_failed_jobs_table',
    'create_personal_access_tokens_table',
    'create_sessions_table',
    'create_jobs_table', // For queues
];

// Get all current migrations
$migrationsDir = database_path('migrations');
$allMigrations = glob($migrationsDir . '/*.php');

echo "Current migrations: " . count($allMigrations) . " files\n\n";

// Create another backup before aggressive cleanup
$backupDir = storage_path('migration_backups/MINIMAL_BACKUP_' . date('Y-m-d_His'));
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

// Categorize migrations
$keep = [];
$delete = [];

foreach ($allMigrations as $migration) {
    $filename = basename($migration);
    $content = file_get_contents($migration);
    
    // Backup everything first
    copy($migration, $backupDir . '/' . $filename);
    
    $shouldKeep = false;
    
    // Check if it's an essential Laravel migration
    foreach ($essentialMigrations as $essential) {
        if (strpos($filename, $essential) !== false) {
            $shouldKeep = true;
            $keep[$filename] = 'Essential Laravel migration';
            break;
        }
    }
    
    if (!$shouldKeep) {
        // Check if this migration creates or modifies a table that exists
        if (preg_match_all('/Schema::(create|table)\([\'"](\w+)[\'"]/', $content, $matches)) {
            foreach ($matches[2] as $tableName) {
                if (in_array($tableName, $tableNames)) {
                    $shouldKeep = true;
                    $keep[$filename] = "Table exists: $tableName";
                    break;
                }
            }
        }
    }
    
    if (!$shouldKeep) {
        $delete[$filename] = $migration;
    }
}

echo "=== MIGRATIONS TO KEEP (" . count($keep) . ") ===\n\n";
foreach ($keep as $file => $reason) {
    echo "✅ $file - $reason\n";
}

echo "\n=== MIGRATIONS TO DELETE (" . count($delete) . ") ===\n\n";
$count = 0;
foreach ($delete as $file => $path) {
    if ($count < 20) {
        echo "❌ $file\n";
    }
    $count++;
}
if ($count > 20) {
    echo "... and " . ($count - 20) . " more\n";
}

// Ask for confirmation
echo "\n⚠️  This will delete " . count($delete) . " migration files!\n";
echo "Backup created at: $backupDir\n";
echo "\nProceed with deletion? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = fgets($handle);
$answer = trim($line);
fclose($handle);

if ($answer !== 'yes') {
    echo "\nAborted. No files were deleted.\n";
    exit(0);
}

// Delete unnecessary migrations
echo "\nDeleting unnecessary migrations...\n";
$deleted = 0;
foreach ($delete as $file => $path) {
    if (unlink($path)) {
        $deleted++;
    }
}

echo "\n✅ Deleted $deleted migration files\n";

// Final count
$remaining = count(glob($migrationsDir . '/*.php'));
echo "\n=== FINAL MIGRATION COUNT ===\n";
echo "Started with: " . count($allMigrations) . " files\n";
echo "Kept: " . count($keep) . " files\n";
echo "Deleted: $deleted files\n";
echo "Final count: $remaining files\n";

// Create report
$report = [
    'cleanup_date' => date('Y-m-d H:i:s'),
    'original_count' => count($allMigrations),
    'kept_count' => count($keep),
    'deleted_count' => $deleted,
    'final_count' => $remaining,
    'backup_location' => $backupDir,
    'existing_tables' => count($tableNames),
    'kept_migrations' => $keep,
    'deleted_migrations' => array_keys($delete)
];

file_put_contents('minimal_migration_report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "\nReport saved to: minimal_migration_report.json\n";