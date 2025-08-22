<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Load consolidation data
$consolidationData = json_decode(file_get_contents('consolidation_needed.json'), true);

echo "=== CONSOLIDATION VERIFICATION REPORT ===\n\n";

$consolidatedDir = database_path('migrations/consolidated_all');
$report = [];

foreach ($consolidationData as $tableName => $migrations) {
    // Check if table exists in database
    $tableExists = DB::select("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_name = ?)", [$tableName])[0]->exists;
    
    if (!$tableExists) {
        $report[$tableName] = [
            'status' => 'SKIPPED',
            'reason' => 'Table does not exist in database',
            'migrations' => count(array_unique(array_merge($migrations['create'], $migrations['updates'])))
        ];
        continue;
    }
    
    // Count columns in database
    $columnCount = DB::selectOne("SELECT COUNT(*) as count FROM information_schema.columns WHERE table_name = ?", [$tableName])->count;
    
    // Check if consolidated file exists
    $consolidatedFiles = glob($consolidatedDir . "/*_create_{$tableName}_table_consolidated.php");
    
    if (!empty($consolidatedFiles)) {
        $report[$tableName] = [
            'status' => 'CONSOLIDATED',
            'columns_in_db' => $columnCount,
            'migrations_consolidated' => count(array_unique(array_merge($migrations['create'], $migrations['updates']))),
            'file' => basename($consolidatedFiles[0])
        ];
    } else {
        $report[$tableName] = [
            'status' => 'MISSING',
            'columns_in_db' => $columnCount,
            'migrations' => count(array_unique(array_merge($migrations['create'], $migrations['updates'])))
        ];
    }
}

// Display report
$consolidated = 0;
$skipped = 0;
$missing = 0;

echo "STATUS SUMMARY:\n";
echo "==============\n\n";

foreach ($report as $table => $info) {
    if ($info['status'] == 'CONSOLIDATED') {
        $consolidated++;
        echo "✅ $table: {$info['columns_in_db']} columns from {$info['migrations_consolidated']} migrations → {$info['file']}\n";
    }
}

echo "\nSKIPPED TABLES:\n";
echo "===============\n\n";

foreach ($report as $table => $info) {
    if ($info['status'] == 'SKIPPED') {
        $skipped++;
        echo "⏭️  $table: {$info['reason']} ({$info['migrations']} migrations)\n";
    }
}

if ($missing > 0) {
    echo "\nMISSING CONSOLIDATIONS:\n";
    echo "=====================\n\n";
    
    foreach ($report as $table => $info) {
        if ($info['status'] == 'MISSING') {
            echo "❌ $table: {$info['columns_in_db']} columns, {$info['migrations']} migrations\n";
        }
    }
}

echo "\n=== FINAL STATISTICS ===\n";
echo "Total tables analyzed: " . count($report) . "\n";
echo "Successfully consolidated: $consolidated\n";
echo "Skipped (no table): $skipped\n";
echo "Missing consolidation: $missing\n";
echo "\nConsolidated migrations location: database/migrations/consolidated_all/\n";

// Save detailed report
file_put_contents('consolidation_verification_report.json', json_encode($report, JSON_PRETTY_PRINT));
echo "\nDetailed report saved to: consolidation_verification_report.json\n";