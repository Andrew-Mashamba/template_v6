<?php

echo "=== FIXING MIGRATION ORDER BASED ON FOREIGN KEY DEPENDENCIES ===\n\n";

$migrationsDir = __DIR__ . '/database/migrations';

// Define the order for base tables that others depend on
$baseTableOrder = [
    'users' => '2024_01_10_000001',
    'institutions' => '2024_01_10_000002', 
    'roles' => '2024_01_10_000003',
    'permissions' => '2024_01_10_000004',
    'departments' => '2024_01_10_000005',
    'branches' => '2024_01_10_000006',
    'accounts' => '2024_01_10_000007',
    'clients' => '2024_01_10_000008',
    'employees' => '2024_01_10_000009',
    'sub_products' => '2024_01_10_000010',
    'menus' => '2024_01_10_000011',
    'menu_actions' => '2024_01_10_000012',
    'committees' => '2024_01_10_000013',
    'services' => '2024_01_10_000014',
    'banks' => '2024_01_10_000015',
];

// Get all migration files
$migrations = glob($migrationsDir . '/*.php');

// Function to extract table name from migration file
function getTableName($filename) {
    $basename = basename($filename);
    if (preg_match('/create_(\w+)_table/', $basename, $matches)) {
        return $matches[1];
    }
    return null;
}

// Backup current migrations
$backupDir = __DIR__ . '/storage/migration_backups/ORDER_FIX_' . date('Y-m-d_His');
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

foreach ($migrations as $migration) {
    copy($migration, $backupDir . '/' . basename($migration));
}

echo "Backed up migrations to: $backupDir\n\n";

// Process migrations
$renamedCount = 0;

foreach ($migrations as $migration) {
    $tableName = getTableName($migration);
    
    if ($tableName && isset($baseTableOrder[$tableName])) {
        $oldName = basename($migration);
        $newName = $baseTableOrder[$tableName] . '_create_' . $tableName . '_table.php';
        $newPath = $migrationsDir . '/' . $newName;
        
        // Only rename if the names are different
        if ($oldName !== $newName) {
            if (rename($migration, $newPath)) {
                echo "✅ Renamed: $oldName → $newName\n";
                $renamedCount++;
            } else {
                echo "❌ Failed to rename: $oldName\n";
            }
        }
    }
}

echo "\n=== RENAMING COMPLETE ===\n";
echo "Renamed: $renamedCount files\n";
echo "Backup location: $backupDir\n";