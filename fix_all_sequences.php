<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== FIXING ALL DATABASE SEQUENCES ===\n\n";

// Get all tables in the database
$tables = DB::select("
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema = 'public' 
    AND table_type = 'BASE TABLE'
    ORDER BY table_name
");

$fixedCount = 0;
$checkedCount = 0;

foreach ($tables as $tableInfo) {
    $table = $tableInfo->table_name;
    
    // Skip Laravel's internal tables
    if (in_array($table, ['migrations', 'password_resets', 'failed_jobs', 'personal_access_tokens'])) {
        continue;
    }
    
    // Check if table has an 'id' column
    if (!Schema::hasColumn($table, 'id')) {
        continue;
    }
    
    $checkedCount++;
    
    try {
        // Get max ID from table
        $maxId = DB::table($table)->max('id') ?: 0;
        
        // Check if sequence exists
        $sequenceName = $table . '_id_seq';
        $sequenceExists = DB::select("
            SELECT EXISTS (
                SELECT 1 
                FROM pg_sequences 
                WHERE schemaname = 'public' 
                AND sequencename = ?
            ) as exists
        ", [$sequenceName])[0]->exists;
        
        if (!$sequenceExists) {
            continue;
        }
        
        // Get current sequence value
        $currentValue = DB::select("SELECT last_value FROM $sequenceName")[0]->last_value;
        
        // Check if sequence needs fixing
        if ($maxId >= $currentValue) {
            $newValue = $maxId + 1;
            DB::statement("ALTER SEQUENCE $sequenceName RESTART WITH $newValue");
            echo "✓ Fixed $table: Max ID = $maxId, Sequence was $currentValue, now $newValue\n";
            $fixedCount++;
        } else {
            // Uncomment to see all tables
            // echo "- $table: OK (Max ID = $maxId, Sequence = $currentValue)\n";
        }
        
    } catch (\Exception $e) {
        echo "✗ Error checking $table: " . $e->getMessage() . "\n";
    }
}

echo "\n=== SUMMARY ===\n";
echo "Tables checked: $checkedCount\n";
echo "Sequences fixed: $fixedCount\n";

if ($fixedCount > 0) {
    echo "\nAll sequence issues have been resolved. You should now be able to create new records without primary key conflicts.\n";
} else {
    echo "\nNo sequence issues found. All sequences are properly synchronized.\n";
}