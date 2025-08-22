<?php

echo "=== FIXING MIGRATION ORDER BASED ON DEPENDENCIES ===\n\n";

$migrationsDir = __DIR__ . '/database/migrations';

// Define the base order for tables with circular dependencies and core tables
$baseOrder = [
    // Laravel core tables (must be first)
    'password_resets' => '2014_10_12_100000',
    'failed_jobs' => '2019_08_19_000000', 
    'personal_access_tokens' => '2019_12_14_000001',
    'sessions' => '2022_03_23_163443',
    
    // Core independent tables (no dependencies)
    'datafeeds' => '2023_01_01_000001',
    'employeefiles' => '2023_01_01_000002',
    'banks' => '2023_01_01_000003',
    'currencies' => '2023_01_01_000004',
    'document_types' => '2023_01_01_000005',
    'member_categories' => '2023_01_01_000006',
    'payment_methods' => '2023_01_01_000007',
    'mnos' => '2023_01_01_000008',
    'cash_in_transit_providers' => '2023_01_01_000009',
    'complaint_categories' => '2023_01_01_000010',
    'complaint_statuses' => '2023_01_01_000011',
    'leaderships' => '2023_01_01_000012',
    
    // Level 1: Core entity tables (minimal dependencies)
    'institutions' => '2023_01_02_000001',
    'users' => '2023_01_02_000002',
    'permissions' => '2023_01_02_000003',
    
    // Level 2: Tables that depend on institutions/users
    'departments' => '2023_01_03_000001', // self-reference fixed
    'roles' => '2023_01_03_000002', // depends on departments
    'branches' => '2023_01_03_000003', // depends on cash_in_transit_providers
    'accounts' => '2023_01_03_000004', // depends on institutions
    'clients' => '2023_01_03_000005',
    'employees' => '2023_01_03_000006',
    'committees' => '2023_01_03_000007', // self-reference fixed
    'menus' => '2023_01_03_000008',
    'sub_products' => '2023_01_03_000009',
    'services' => '2023_01_03_000010',
    'vaults' => '2023_01_03_000011', // depends on branches
    'tills' => '2023_01_03_000012', // depends on branches, users
    
    // Level 3: Tables with complex dependencies
    'menu_actions' => '2023_01_04_000001', // depends on menus
    'sub_roles' => '2023_01_04_000002', // depends on roles
    'role_permissions' => '2023_01_04_000003', // depends on roles, permissions
    'bank_accounts' => '2023_01_04_000004', // depends on branches
    'loans' => '2023_01_04_000005',
    'transactions' => '2023_01_04_000006', // depends on accounts
    'tellers' => '2023_01_04_000007', // depends on users, tills
    'approvals' => '2023_01_04_000008', // depends on users
    'strongroom_ledgers' => '2023_01_04_000009', // depends on branches, vaults
    'budget_managements' => '2023_01_04_000010', // depends on accounts
    
    // Level 4: Tables that depend on level 3 tables
    'api_keys' => '2023_01_05_000001', // depends on users
    'user_roles' => '2023_01_05_000002', // depends on users, roles
    'user_profiles' => '2023_01_05_000003', // depends on users
    'cash_movements' => '2023_01_05_000004', // depends on multiple
    'expenses' => '2023_01_05_000005', // depends on approvals
    'bills' => '2023_01_05_000006', // depends on services, users
];

// Get all current migrations
$migrations = glob($migrationsDir . '/*.php');

// Function to extract table name
function getTableName($filename) {
    $basename = basename($filename);
    if (preg_match('/create_(\w+)_table/', $basename, $matches)) {
        return $matches[1];
    }
    return null;
}

// Backup before changes
$backupDir = __DIR__ . '/storage/migration_backups/FINAL_ORDER_' . date('Y-m-d_His');
if (!file_exists($backupDir)) {
    mkdir($backupDir, 0755, true);
}

foreach ($migrations as $migration) {
    copy($migration, $backupDir . '/' . basename($migration));
}

echo "Backed up to: $backupDir\n\n";

// Track renamed files
$renamed = 0;
$processed = [];

// First pass: Rename files in our base order
foreach ($migrations as $migration) {
    $tableName = getTableName($migration);
    if (!$tableName) continue;
    
    if (isset($baseOrder[$tableName])) {
        $oldName = basename($migration);
        $newName = $baseOrder[$tableName] . '_create_' . $tableName . '_table.php';
        $newPath = $migrationsDir . '/' . $newName;
        
        if ($oldName !== $newName && !isset($processed[$tableName])) {
            if (file_exists($migration)) {
                if (rename($migration, $newPath)) {
                    echo "✅ Renamed: $tableName → $newName\n";
                    $renamed++;
                    $processed[$tableName] = true;
                }
            }
        }
    }
}

// Second pass: Rename remaining files with later timestamps
$remainingStart = '2023_01_06_000001';
$counter = 1;

$migrations = glob($migrationsDir . '/*.php'); // Re-glob after renames
sort($migrations); // Sort to maintain some order

foreach ($migrations as $migration) {
    $tableName = getTableName($migration);
    if (!$tableName || isset($processed[$tableName])) continue;
    
    $oldName = basename($migration);
    
    // Skip if already has correct timestamp format
    if (preg_match('/^2023_01_0[1-6]_/', $oldName)) continue;
    
    $timestamp = date('Y_m_d_', strtotime('2023-01-06')) . str_pad($counter, 6, '0', STR_PAD_LEFT);
    $newName = $timestamp . '_create_' . $tableName . '_table.php';
    $newPath = $migrationsDir . '/' . $newName;
    
    if (rename($migration, $newPath)) {
        echo "✅ Renamed: $tableName → $newName\n";
        $renamed++;
        $counter++;
    }
}

echo "\n=== RENAMING COMPLETE ===\n";
echo "Renamed: $renamed files\n";
echo "Backup location: $backupDir\n";

// Verify no duplicates
$finalMigrations = glob($migrationsDir . '/*.php');
$seen = [];
$duplicates = [];

foreach ($finalMigrations as $migration) {
    $basename = basename($migration);
    if (isset($seen[$basename])) {
        $duplicates[] = $basename;
    }
    $seen[$basename] = true;
}

if (count($duplicates) > 0) {
    echo "\n⚠️  WARNING: Duplicate filenames detected:\n";
    foreach ($duplicates as $dup) {
        echo "  - $dup\n";
    }
}

echo "\nTotal migrations: " . count($finalMigrations) . "\n";