<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

$migrationsPath = database_path('migrations');
$migrations = glob($migrationsPath . '/*.php');

// Group migrations by table
$tableMap = [];
$createTableMap = [];

foreach ($migrations as $migration) {
    $filename = basename($migration);
    $content = file_get_contents($migration);
    
    // Skip consolidated migrations
    if (strpos($migration, 'consolidated') !== false) {
        continue;
    }
    
    // Find create table statements
    if (preg_match_all('/Schema::create\([\'"](\w+)[\'"]/', $content, $createMatches)) {
        foreach ($createMatches[1] as $table) {
            if (!isset($createTableMap[$table])) {
                $createTableMap[$table] = [];
            }
            $createTableMap[$table][] = $filename;
        }
    }
    
    // Find table modifications (Schema::table)
    if (preg_match_all('/Schema::table\([\'"](\w+)[\'"]/', $content, $tableMatches)) {
        foreach ($tableMatches[1] as $table) {
            if (!isset($tableMap[$table])) {
                $tableMap[$table] = [];
            }
            $tableMap[$table][] = $filename;
        }
    }
}

echo "=== COMPREHENSIVE MIGRATION ANALYSIS ===\n\n";

// Combine create and update migrations
$allTableMigrations = [];
foreach ($createTableMap as $table => $creates) {
    $allTableMigrations[$table]['create'] = $creates;
    $allTableMigrations[$table]['updates'] = $tableMap[$table] ?? [];
}

// Add tables that only have updates (no create found)
foreach ($tableMap as $table => $updates) {
    if (!isset($allTableMigrations[$table])) {
        $allTableMigrations[$table]['create'] = [];
        $allTableMigrations[$table]['updates'] = $updates;
    }
}

// Sort by total number of migrations
uasort($allTableMigrations, function($a, $b) {
    $totalA = count($a['create']) + count($a['updates']);
    $totalB = count($b['create']) + count($b['updates']);
    return $totalB - $totalA;
});

// Display all tables with multiple migrations
$consolidationNeeded = [];
foreach ($allTableMigrations as $table => $migrations) {
    $total = count($migrations['create']) + count($migrations['updates']);
    if ($total > 1) {
        $consolidationNeeded[$table] = $migrations;
        echo "Table: $table (Total migrations: $total)\n";
        if (!empty($migrations['create'])) {
            echo "  CREATE:\n";
            foreach ($migrations['create'] as $file) {
                echo "    - $file\n";
            }
        }
        if (!empty($migrations['updates'])) {
            echo "  UPDATES:\n";
            foreach ($migrations['updates'] as $file) {
                echo "    - $file\n";
            }
        }
        echo "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Total tables needing consolidation: " . count($consolidationNeeded) . "\n";
echo "Total migrations to consolidate: " . array_sum(array_map(function($t) {
    return count($t['create']) + count($t['updates']);
}, $consolidationNeeded)) . "\n";

// Save the analysis for processing
file_put_contents('consolidation_needed.json', json_encode($consolidationNeeded, JSON_PRETTY_PRINT));
echo "\nAnalysis saved to consolidation_needed.json\n";