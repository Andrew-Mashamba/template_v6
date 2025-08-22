<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

echo "=== MIGRATION CONSOLIDATION SCRIPT ===\n\n";

// Step 1: Delete empty migrations
$emptyMigrations = [
    '2025_05_24_093809_add_deleted_at_to_clients_table.php',
    '2025_06_10_023436_add_deleted_at_to_shares_table.php',
    '2025_06_16_080551_add_status_column_to_shares_table.php',
    '2025_06_30_154334_add_missing_loan_fields_to_loans_table.php',
    '2025_07_02_015459_add_status_column_to_expenses_table.php',
    '2025_07_07_103003_add_max_amount_to_tellers_table.php',
    '2025_07_17_124500_add_portal_access_fields_to_clients_table.php'
];

echo "STEP 1: Deleting empty migrations...\n";
foreach ($emptyMigrations as $migration) {
    $path = database_path('migrations/' . $migration);
    if (file_exists($path)) {
        unlink($path);
        echo "  ✓ Deleted: $migration\n";
        
        // Remove from migrations table if exists
        DB::table('migrations')
            ->where('migration', pathinfo($migration, PATHINFO_FILENAME))
            ->delete();
    }
}

// Step 2: Create consolidated migration for clients table
echo "\nSTEP 2: Creating consolidated migration for clients table...\n";

$consolidatedClientsContent = '<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if columns already exist before adding
        Schema::table(\'clients\', function (Blueprint $table) {
            $columnsToAdd = [];
            
            // From add_deleted_at_to_clients_table
            if (!Schema::hasColumn(\'clients\', \'deleted_at\')) {
                $columnsToAdd[\'deleted_at\'] = function() use ($table) {
                    $table->softDeletes();
                };
            }
            
            // From add_created_by_to_clients_table
            if (!Schema::hasColumn(\'clients\', \'created_by\')) {
                $columnsToAdd[\'created_by\'] = function() use ($table) {
                    $table->unsignedBigInteger(\'created_by\')->nullable()->after(\'updated_at\');
                };
            }
            
            // From add_employee_id_to_clients_table
            if (!Schema::hasColumn(\'clients\', \'employee_id\')) {
                $columnsToAdd[\'employee_id\'] = function() use ($table) {
                    $table->string(\'employee_id\')->nullable()->after(\'branch\');
                };
            }
            
            // From add_user_id_to_clients_table
            if (!Schema::hasColumn(\'clients\', \'user_id\')) {
                $columnsToAdd[\'user_id\'] = function() use ($table) {
                    $table->unsignedBigInteger(\'user_id\')->nullable()->after(\'employee_id\');
                };
            }
            
            // From add_payment_link_to_clients_table
            if (!Schema::hasColumn(\'clients\', \'payment_link\')) {
                $columnsToAdd[\'payment_link\'] = function() use ($table) {
                    $table->text(\'payment_link\')->nullable()->after(\'akiba\');
                };
            }
            
            // Apply all column additions
            foreach ($columnsToAdd as $column => $closure) {
                $closure();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table(\'clients\', function (Blueprint $table) {
            $columnsToDrop = [];
            
            if (Schema::hasColumn(\'clients\', \'payment_link\')) {
                $columnsToDrop[] = \'payment_link\';
            }
            if (Schema::hasColumn(\'clients\', \'user_id\')) {
                $columnsToDrop[] = \'user_id\';
            }
            if (Schema::hasColumn(\'clients\', \'employee_id\')) {
                $columnsToDrop[] = \'employee_id\';
            }
            if (Schema::hasColumn(\'clients\', \'created_by\')) {
                $columnsToDrop[] = \'created_by\';
            }
            if (Schema::hasColumn(\'clients\', \'deleted_at\')) {
                $columnsToDrop[] = \'deleted_at\';
            }
            
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
';

// Save consolidated migration
$timestamp = date('Y_m_d_His');
$consolidatedPath = database_path("migrations/{$timestamp}_add_consolidated_fields_to_clients_table.php");
file_put_contents($consolidatedPath, $consolidatedClientsContent);
echo "  ✓ Created: {$timestamp}_add_consolidated_fields_to_clients_table.php\n";

// Step 3: Delete the individual client migrations that have been consolidated
$clientMigrationsToDelete = [
    '2025_05_24_093000_add_deleted_at_to_clients_table.php',
    '2025_05_24_add_created_by_to_clients_table.php',
    '2025_06_08_add_employee_id_to_clients_table.php',
    '2025_06_08_add_user_id_to_clients_table.php',
    '2025_01_27_add_payment_link_to_clients_table.php'
];

echo "\nSTEP 3: Removing individual client migrations...\n";
foreach ($clientMigrationsToDelete as $migration) {
    $path = database_path('migrations/' . $migration);
    if (file_exists($path)) {
        // Check if it was already run
        $wasRun = DB::table('migrations')
            ->where('migration', pathinfo($migration, PATHINFO_FILENAME))
            ->exists();
            
        if (!$wasRun) {
            unlink($path);
            echo "  ✓ Deleted: $migration\n";
        } else {
            echo "  ⚠ Skipped (already run): $migration\n";
        }
    }
}

echo "\nConsolidation complete!\n";
echo "Next steps:\n";
echo "1. Run: php artisan migrate\n";
echo "2. Test your application\n";
echo "3. Commit the changes\n";