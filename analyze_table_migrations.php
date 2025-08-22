<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$migrationsPath = database_path('migrations');
$migrations = glob($migrationsPath . '/*.php');

// Group migrations by table
$tableMap = [];
$createTableMap = [];

foreach ($migrations as $migration) {
    $filename = basename($migration);
    $content = file_get_contents($migration);
    
    // Check migration status
    $migrationName = pathinfo($filename, PATHINFO_FILENAME);
    $isRun = DB::table('migrations')->where('migration', $migrationName)->exists();
    
    // Find create table statements
    if (preg_match_all('/Schema::create\([\'"](\w+)[\'"]/', $content, $createMatches)) {
        foreach ($createMatches[1] as $table) {
            $createTableMap[$table] = [
                'file' => $filename,
                'status' => $isRun ? 'RAN' : 'PENDING'
            ];
        }
    }
    
    // Find table modifications
    if (preg_match_all('/Schema::table\([\'"](\w+)[\'"]/', $content, $tableMatches)) {
        foreach ($tableMatches[1] as $table) {
            if (!isset($tableMap[$table])) {
                $tableMap[$table] = [];
            }
            $tableMap[$table][] = [
                'file' => $filename,
                'status' => $isRun ? 'RAN' : 'PENDING'
            ];
        }
    }
}

echo "=== TABLES WITH MULTIPLE MIGRATIONS ===\n\n";

// Tables to consolidate
$consolidationCandidates = [];

foreach ($tableMap as $table => $migrations) {
    if (count($migrations) >= 2) {
        $pendingCount = count(array_filter($migrations, fn($m) => $m['status'] === 'PENDING'));
        $ranCount = count(array_filter($migrations, fn($m) => $m['status'] === 'RAN'));
        
        echo "$table table (" . count($migrations) . " update migrations):\n";
        
        if (isset($createTableMap[$table])) {
            echo "  CREATE: [{$createTableMap[$table]['status']}] {$createTableMap[$table]['file']}\n";
        }
        
        foreach ($migrations as $migration) {
            echo "  UPDATE: [{$migration['status']}] {$migration['file']}\n";
        }
        
        // Only consolidate if all are pending or mix of pending/ran
        if ($pendingCount > 0) {
            $consolidationCandidates[$table] = [
                'create' => $createTableMap[$table] ?? null,
                'updates' => $migrations,
                'pending_count' => $pendingCount,
                'ran_count' => $ranCount
            ];
        }
        
        echo "\n";
    }
}

echo "\n=== CONSOLIDATION RECOMMENDATIONS ===\n\n";

foreach ($consolidationCandidates as $table => $info) {
    echo "$table table:\n";
    if ($info['ran_count'] === 0) {
        echo "  ✅ Can consolidate all migrations (none have run yet)\n";
    } else {
        echo "  ⚠️  Can only consolidate PENDING migrations ({$info['pending_count']} pending, {$info['ran_count']} already ran)\n";
    }
}

// Top candidates for consolidation
echo "\n=== TOP CONSOLIDATION CANDIDATES ===\n";
$topCandidates = array_filter($consolidationCandidates, fn($info) => $info['pending_count'] >= 3);
foreach ($topCandidates as $table => $info) {
    echo "\n$table: {$info['pending_count']} migrations can be consolidated\n";
}