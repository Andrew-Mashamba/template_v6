<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

// Create consolidated migrations directory
$consolidatedDir = database_path('migrations/consolidated');
if (!file_exists($consolidatedDir)) {
    mkdir($consolidatedDir, 0755, true);
}

// Load the consolidation data
$consolidationData = json_decode(file_get_contents('consolidation_needed.json'), true);

echo "=== CREATING CONSOLIDATED MIGRATIONS FOR ALL TABLES ===\n\n";

$processedCount = 0;
$timestamp = date('Y_m_d_His', strtotime('-41 minutes')); // Start with earlier timestamp

foreach ($consolidationData as $tableName => $migrations) {
    $processedCount++;
    
    // Skip if no migrations to consolidate
    $totalMigrations = count($migrations['create']) + count($migrations['updates']);
    if ($totalMigrations <= 1) {
        continue;
    }
    
    echo "[$processedCount/41] Processing $tableName table ($totalMigrations migrations)...\n";
    
    // Get unique migration files (remove duplicates)
    $allMigrations = array_unique(array_merge($migrations['create'], $migrations['updates']));
    
    // Analyze each migration file to extract column definitions
    $tableStructure = analyzeTableMigrations($tableName, $allMigrations);
    
    // Generate consolidated migration
    $migrationContent = generateConsolidatedMigration($tableName, $allMigrations, $tableStructure);
    
    // Save the migration with incremented timestamp
    $migrationTimestamp = date('Y_m_d_His', strtotime($timestamp) + ($processedCount * 60));
    $filename = "{$migrationTimestamp}_create_{$tableName}_table_consolidated.php";
    file_put_contents($consolidatedDir . '/' . $filename, $migrationContent);
    
    echo "   ✅ Created: $filename\n\n";
}

echo "\n=== CONSOLIDATION COMPLETE ===\n";
echo "Created $processedCount consolidated migration files\n";
echo "Location: database/migrations/consolidated/\n";

function analyzeTableMigrations($tableName, $migrationFiles) {
    $structure = [
        'columns' => [],
        'indexes' => [],
        'foreign_keys' => [],
        'unique' => [],
        'primary' => null,
        'engine' => null,
        'charset' => null,
        'collation' => null
    ];
    
    $migrationsPath = database_path('migrations');
    
    foreach ($migrationFiles as $file) {
        $content = file_get_contents($migrationsPath . '/' . $file);
        
        // Extract column definitions
        if (preg_match_all('/\$table->(\w+)\(([^)]*)\)([^;]*);/m', $content, $matches)) {
            for ($i = 0; $i < count($matches[0]); $i++) {
                $method = $matches[1][$i];
                $params = $matches[2][$i];
                $modifiers = $matches[3][$i];
                
                // Parse column name from params
                if (preg_match('/[\'"](\w+)[\'"]/', $params, $nameMatch)) {
                    $columnName = $nameMatch[1];
                    
                    // Skip if it's not a column definition
                    if (in_array($method, ['foreign', 'index', 'unique', 'primary', 'dropColumn', 'dropIndex'])) {
                        continue;
                    }
                    
                    // Store column definition
                    $structure['columns'][$columnName] = [
                        'method' => $method,
                        'params' => $params,
                        'modifiers' => $modifiers,
                        'source' => basename($file)
                    ];
                }
            }
        }
        
        // Extract indexes
        if (preg_match_all('/\$table->index\(([^)]+)\)/m', $content, $indexMatches)) {
            foreach ($indexMatches[1] as $indexDef) {
                $structure['indexes'][] = $indexDef;
            }
        }
        
        // Extract foreign keys
        if (preg_match_all('/\$table->foreign\(([^)]+)\)([^;]+);/m', $content, $fkMatches)) {
            for ($i = 0; $i < count($fkMatches[0]); $i++) {
                $structure['foreign_keys'][] = [
                    'column' => $fkMatches[1][$i],
                    'references' => $fkMatches[2][$i]
                ];
            }
        }
    }
    
    return $structure;
}

function generateConsolidatedMigration($tableName, $migrationFiles, $structure) {
    $className = 'Create' . str_replace('_', '', ucwords($tableName, '_')) . 'TableConsolidated';
    $migrationsList = implode("\n * - ", $migrationFiles);
    
    $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated migration for $tableName table
 * 
 * This combines the following migrations:
 * - $migrationsList
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('$tableName', function (Blueprint \$table) {
PHP;

    // Add columns
    foreach ($structure['columns'] as $columnName => $columnDef) {
        $content .= "\n            \$table->{$columnDef['method']}({$columnDef['params']}){$columnDef['modifiers']}; // From {$columnDef['source']}";
    }
    
    // Add indexes
    foreach ($structure['indexes'] as $index) {
        $content .= "\n            \$table->index($index);";
    }
    
    // Add foreign keys
    foreach ($structure['foreign_keys'] as $fk) {
        $content .= "\n            \$table->foreign({$fk['column']}){$fk['references']};";
    }
    
    $content .= <<<PHP

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('$tableName');
    }
};
PHP;

    return $content;
}