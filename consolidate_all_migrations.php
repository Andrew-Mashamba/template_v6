<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Create consolidated migrations directory
$consolidatedDir = database_path('migrations/consolidated_all');
if (!file_exists($consolidatedDir)) {
    mkdir($consolidatedDir, 0755, true);
}

// Load consolidation data
$consolidationData = json_decode(file_get_contents('consolidation_needed.json'), true);

echo "=== CONSOLIDATING ALL MIGRATIONS BY ACTUAL DATABASE STRUCTURE ===\n\n";

$processedCount = 0;
$baseTimestamp = strtotime('2025-07-27 10:00:00');

// Get all tables from the database
$tables = DB::select("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'");
$existingTables = array_map(function($t) { return $t->table_name; }, $tables);

foreach ($consolidationData as $tableName => $migrations) {
    $processedCount++;
    
    // Skip tables that don't exist in database
    if (!in_array($tableName, $existingTables)) {
        echo "[$processedCount/41] Skipping $tableName - table doesn't exist in database\n";
        continue;
    }
    
    $totalMigrations = count(array_unique(array_merge($migrations['create'], $migrations['updates'])));
    echo "[$processedCount/41] Processing $tableName table ($totalMigrations unique migrations)...\n";
    
    try {
        // Get actual table structure from database
        $columns = DB::select("
            SELECT 
                column_name,
                data_type,
                character_maximum_length,
                numeric_precision,
                numeric_scale,
                is_nullable,
                column_default,
                ordinal_position
            FROM information_schema.columns 
            WHERE table_name = ?
            ORDER BY ordinal_position
        ", [$tableName]);
        
        // Get indexes
        $indexes = DB::select("
            SELECT 
                indexname,
                indexdef
            FROM pg_indexes 
            WHERE tablename = ?
            AND indexname NOT LIKE '%_pkey'
        ", [$tableName]);
        
        // Get foreign keys
        $foreignKeys = DB::select("
            SELECT
                tc.constraint_name,
                kcu.column_name,
                ccu.table_name AS foreign_table_name,
                ccu.column_name AS foreign_column_name,
                rc.delete_rule
            FROM information_schema.table_constraints AS tc
            JOIN information_schema.key_column_usage AS kcu
                ON tc.constraint_name = kcu.constraint_name
                AND tc.table_schema = kcu.table_schema
            JOIN information_schema.constraint_column_usage AS ccu
                ON ccu.constraint_name = tc.constraint_name
                AND ccu.table_schema = tc.table_schema
            JOIN information_schema.referential_constraints AS rc
                ON rc.constraint_name = tc.constraint_name
            WHERE tc.constraint_type = 'FOREIGN KEY' 
            AND tc.table_name = ?
        ", [$tableName]);
        
        // Generate migration content
        $migrationContent = generateMigrationFromStructure($tableName, $columns, $indexes, $foreignKeys, $migrations);
        
        // Save migration
        $timestamp = date('Y_m_d_His', $baseTimestamp + ($processedCount * 60));
        $filename = "{$timestamp}_create_{$tableName}_table_consolidated.php";
        file_put_contents($consolidatedDir . '/' . $filename, $migrationContent);
        
        echo "   ✅ Created: $filename\n\n";
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "\n=== CONSOLIDATION COMPLETE ===\n";
echo "Created consolidated migrations in: database/migrations/consolidated_all/\n";

function generateMigrationFromStructure($tableName, $columns, $indexes, $foreignKeys, $migrationInfo) {
    $className = 'Create' . str_replace('_', '', ucwords($tableName, '_')) . 'TableConsolidated';
    
    // List original migrations
    $allMigrations = array_unique(array_merge($migrationInfo['create'], $migrationInfo['updates']));
    $migrationsList = implode("\n * - ", $allMigrations);
    
    $content = <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated migration for $tableName table
 * 
 * Combined from these migrations:
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
    foreach ($columns as $column) {
        $columnDef = generateColumnDefinition($column);
        if ($columnDef) {
            $content .= "\n            $columnDef";
        }
    }
    
    // Add indexes
    foreach ($indexes as $index) {
        // Parse index definition to extract column names
        if (preg_match('/\((.*?)\)/', $index->indexdef, $matches)) {
            $indexColumns = $matches[1];
            // Clean up column names
            $indexColumns = str_replace([' ', '"'], ['', ''], $indexColumns);
            $indexColumns = "'" . str_replace(',', "', '", $indexColumns) . "'";
            $content .= "\n            \$table->index([$indexColumns]);";
        }
    }
    
    // Add foreign keys
    foreach ($foreignKeys as $fk) {
        $onDelete = $fk->delete_rule == 'CASCADE' ? "->onDelete('cascade')" : "";
        $content .= "\n            \$table->foreign('{$fk->column_name}')->references('{$fk->foreign_column_name}')->on('{$fk->foreign_table_name}')$onDelete;";
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

function generateColumnDefinition($column) {
    $name = $column->column_name;
    $type = $column->data_type;
    $nullable = $column->is_nullable == 'YES' ? '->nullable()' : '';
    $default = '';
    
    if ($column->column_default !== null) {
        // Handle PostgreSQL default values
        if (strpos($column->column_default, 'nextval') !== false) {
            // This is an auto-increment field, skip default
        } elseif (strpos($column->column_default, '::') !== false) {
            // PostgreSQL type cast
            $defaultValue = explode('::', $column->column_default)[0];
            $defaultValue = trim($defaultValue, "'");
            $default = "->default('$defaultValue')";
        } else {
            $default = "->default({$column->column_default})";
        }
    }
    
    // Special handling for id, timestamps
    if ($name == 'id' && $type == 'bigint') {
        return '$table->id();';
    }
    
    if ($name == 'created_at' || $name == 'updated_at') {
        // These will be handled by timestamps()
        return null;
    }
    
    if ($name == 'deleted_at') {
        return '$table->softDeletes();';
    }
    
    if ($name == 'remember_token') {
        return '$table->rememberToken();';
    }
    
    // Map PostgreSQL types to Laravel methods
    switch ($type) {
        case 'bigint':
            return "\$table->bigInteger('$name')$nullable$default;";
        case 'integer':
            return "\$table->integer('$name')$nullable$default;";
        case 'smallint':
            return "\$table->smallInteger('$name')$nullable$default;";
        case 'character varying':
            $length = $column->character_maximum_length ?: 255;
            if ($length == 255) {
                return "\$table->string('$name')$nullable$default;";
            } else {
                return "\$table->string('$name', $length)$nullable$default;";
            }
        case 'text':
            return "\$table->text('$name')$nullable$default;";
        case 'numeric':
            $precision = $column->numeric_precision ?: 8;
            $scale = $column->numeric_scale ?: 2;
            return "\$table->decimal('$name', $precision, $scale)$nullable$default;";
        case 'timestamp without time zone':
            return "\$table->timestamp('$name')$nullable$default;";
        case 'date':
            return "\$table->date('$name')$nullable$default;";
        case 'time without time zone':
            return "\$table->time('$name')$nullable$default;";
        case 'boolean':
            return "\$table->boolean('$name')$nullable$default;";
        case 'json':
        case 'jsonb':
            return "\$table->json('$name')$nullable$default;";
        case 'uuid':
            return "\$table->uuid('$name')$nullable$default;";
        default:
            return "\$table->string('$name')$nullable$default; // Original type: $type";
    }
}