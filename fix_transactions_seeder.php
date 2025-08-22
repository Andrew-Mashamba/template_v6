<?php

// Read the TransactionsSeeder file
$filePath = 'database/seeders/TransactionsSeeder.php';
$content = file_get_contents($filePath);

// Fix UUID fields
$content = str_replace("'transaction_uuid' => 1,", "'transaction_uuid' => Str::uuid(),", $content);
$content = str_replace("'transaction_uuid' => 2,", "'transaction_uuid' => Str::uuid(),", $content);
$content = str_replace("'correlation_id' => 1,", "'correlation_id' => Str::uuid(),", $content);
$content = str_replace("'correlation_id' => 2,", "'correlation_id' => Str::uuid(),", $content);
$content = str_replace("'session_id' => 1,", "'session_id' => Str::uuid(),", $content);
$content = str_replace("'session_id' => 2,", "'session_id' => Str::uuid(),", $content);
$content = str_replace("'batch_id' => 1,", "'batch_id' => Str::uuid(),", $content);
$content = str_replace("'batch_id' => 2,", "'batch_id' => Str::uuid(),", $content);
$content = str_replace("'process_id' => 1,", "'process_id' => Str::uuid(),", $content);
$content = str_replace("'process_id' => 2,", "'process_id' => Str::uuid(),", $content);
$content = str_replace("'job_id' => 1,", "'job_id' => Str::uuid(),", $content);
$content = str_replace("'job_id' => 2,", "'job_id' => Str::uuid(),", $content);

// Fix timestamp fields
$timestampFields = [
    'received_at', 'initiated_at', 'reversed_at', 'last_retry_at', 
    'next_retry_at', 'lookup_performed_at'
];
foreach ($timestampFields as $field) {
    $content = preg_replace("/'$field' => 'Sample {$field} \d+',/", "'$field' => null,", $content);
}

// Fix IP address field
$content = preg_replace("/'client_ip' => 'Sample client_ip \d+',/", "'client_ip' => '192.168.1.1',", $content);

// Fix integer fields that have string values
$content = preg_replace("/'processing_time_ms' => '\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}',/", "'processing_time_ms' => 100,", $content);
$content = preg_replace("/'lookup_processing_time_ms' => '\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}',/", "'lookup_processing_time_ms' => 50,", $content);

// Fix boolean fields with float values
$content = str_replace("'is_system_generated' => 5.5,", "'is_system_generated' => false,", $content);
$content = str_replace("'is_system_generated' => 10.5,", "'is_system_generated' => false,", $content);

// Add Str import if not present
if (!str_contains($content, 'use Illuminate\Support\Str;')) {
    $content = str_replace(
        "use Illuminate\Support\Facades\DB;",
        "use Illuminate\Support\Facades\DB;\nuse Illuminate\Support\Str;",
        $content
    );
}

// Add foreign key disable logic
if (!str_contains($content, 'session_replication_role')) {
    $content = str_replace(
        "    public function run()\n    {\n        // Clear existing data\n        DB::table('transactions')->truncate();",
        "    public function run()\n    {\n        // Disable foreign key checks\n        if (DB::getDriverName() === 'mysql') {\n            DB::statement('SET FOREIGN_KEY_CHECKS=0;');\n        } elseif (DB::getDriverName() === 'pgsql') {\n            DB::statement('SET session_replication_role = replica;');\n        }\n        \n        try {\n        // Clear existing data\n        DB::table('transactions')->truncate();",
        $content
    );
}

// Fix closing braces
if (substr_count($content, '{') > substr_count($content, '}')) {
    // Find the last foreach
    $lastForeach = strrpos($content, 'foreach ($data as $row) {');
    if ($lastForeach !== false) {
        $afterForeach = substr($content, $lastForeach);
        if (!str_contains($afterForeach, '} finally {')) {
            $content = preg_replace(
                '/(\s*foreach \(\$data as \$row\) \{\s*DB::table\(\'transactions\'\)->insert\(\$row\);\s*)\}\s*\}\s*\}$/s',
                '$1}
        
        } finally {
            // Re-enable foreign key checks
            if (DB::getDriverName() === \'mysql\') {
                DB::statement(\'SET FOREIGN_KEY_CHECKS=1;\');
            } elseif (DB::getDriverName() === \'pgsql\') {
                DB::statement(\'SET session_replication_role = DEFAULT;\');
            }
        }
    }
}',
                $content
            );
        }
    }
}

// Write the fixed content back
file_put_contents($filePath, $content);

echo "Fixed TransactionsSeeder.php\n";