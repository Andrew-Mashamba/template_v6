<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\File;

echo "=== KEEPING ONLY CONSOLIDATED MIGRATIONS ===\n\n";

$migrationsDir = database_path('migrations');
$allMigrations = glob($migrationsDir . '/*.php');

// Identify consolidated migrations (they start with 2025_07_27_10)
$consolidatedMigrations = [];
$originalMigrations = [];
$otherMigrations = []; // Non-duplicated tables

foreach ($allMigrations as $migration) {
    $filename = basename($migration);
    $content = file_get_contents($migration);
    
    // Check if it's a consolidated migration
    if (strpos($filename, '2025_07_27_10') === 0) {
        $consolidatedMigrations[$filename] = $migration;
        
        // Extract table name
        if (preg_match('/Schema::create\([\'"](\w+)[\'"]/', $content, $matches)) {
            $tableName = $matches[1];
            
            // Find all other migrations for this table
            foreach ($allMigrations as $otherMigration) {
                $otherFilename = basename($otherMigration);
                $otherContent = file_get_contents($otherMigration);
                
                // Skip the consolidated one
                if ($otherFilename === $filename) continue;
                
                // Check if this migration creates the same table
                if (preg_match('/Schema::create\([\'"]' . $tableName . '[\'"]/', $otherContent)) {
                    $originalMigrations[$otherFilename] = $otherMigration;
                }
            }
        }
    }
}

// Find migrations that are not duplicates of consolidated ones
foreach ($allMigrations as $migration) {
    $filename = basename($migration);
    
    // Skip if it's consolidated or marked as original
    if (isset($consolidatedMigrations[$filename]) || isset($originalMigrations[$filename])) {
        continue;
    }
    
    // This migration doesn't have a consolidated version, keep it
    $otherMigrations[$filename] = $migration;
}

echo "Found:\n";
echo "- " . count($consolidatedMigrations) . " consolidated migrations\n";
echo "- " . count($originalMigrations) . " original migrations to delete\n";
echo "- " . count($otherMigrations) . " other migrations to keep\n\n";

// Create backup
$backupDir = storage_path('migration_backups/ORIGINALS_' . date('Y-m-d_His'));
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

echo "Deleting original migrations...\n\n";

// Delete original migrations
$deleted = 0;
foreach ($originalMigrations as $filename => $path) {
    echo "❌ Deleting: $filename\n";
    
    // Backup first
    copy($path, $backupDir . '/' . $filename);
    
    if (unlink($path)) {
        $deleted++;
    }
}

// Also delete the old consolidated fields migration
$oldConsolidated = $migrationsDir . '/2025_07_27_085653_add_consolidated_fields_to_clients_table.php';
if (file_exists($oldConsolidated)) {
    echo "❌ Deleting: 2025_07_27_085653_add_consolidated_fields_to_clients_table.php\n";
    copy($oldConsolidated, $backupDir . '/2025_07_27_085653_add_consolidated_fields_to_clients_table.php');
    if (unlink($oldConsolidated)) {
        $deleted++;
    }
}

echo "\n✅ Deleted $deleted original migrations\n";
echo "Backup saved to: $backupDir\n\n";

// Show what remains
$remaining = glob($migrationsDir . '/*.php');
echo "=== FINAL MIGRATION STRUCTURE ===\n";
echo "Total migrations: " . count($remaining) . "\n\n";

echo "Consolidated migrations (" . count($consolidatedMigrations) . "):\n";
foreach ($consolidatedMigrations as $filename => $path) {
    echo "  ✅ $filename\n";
}

echo "\nOther migrations (first 20):\n";
$count = 0;
foreach ($remaining as $migration) {
    $filename = basename($migration);
    if (!isset($consolidatedMigrations[$filename])) {
        echo "  ✅ $filename\n";
        $count++;
        if ($count >= 20) {
            echo "  ... and " . (count($remaining) - count($consolidatedMigrations) - 20) . " more\n";
            break;
        }
    }
}

// Create final report
$report = [
    'date' => date('Y-m-d H:i:s'),
    'consolidated_count' => count($consolidatedMigrations),
    'deleted_count' => $deleted,
    'final_count' => count($remaining),
    'backup_location' => $backupDir
];

file_put_contents('final_consolidation_report.json', json_encode($report, JSON_PRETTY_PRINT));