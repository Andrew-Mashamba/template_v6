<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Tables with multiple migrations that could be consolidated
$tablesToConsolidate = [
    'users' => [
        'description' => 'Users table with auth, 2FA, OTP, and department fields',
        'files' => [
            '2014_10_12_000000_create_users_table.php',
            '2014_10_12_200000_add_two_factor_columns_to_users_table.php',
            '2024_03_19_000003_update_users_table_department_to_department_code.php',
            '2024_03_21_000000_add_password_changed_at_to_users_table.php',
            '2025_05_20_154826_add_otp_fields_to_users_table.php'
        ]
    ],
    'institutions' => [
        'description' => 'Institutions table with all accounting and status fields',
        'files' => [
            '2024_03_13_100001_create_institutions_table.php',
            '2025_01_27_120000_add_depreciation_accounts_to_institutions_table.php',
            '2025_06_05_170000_add_missing_columns_to_institutions_table.php',
            '2025_06_05_180001_recreate_status_columns_in_institutions_table.php',
            '2025_07_09_181428_add_main_accounts_to_institutions_table.php'
        ]
    ],
    'departments' => [
        'description' => 'Departments table with hierarchy and branch fields',
        'files' => [
            '2024_03_13_100002_create_departments_table.php',
            '2025_05_09_130355_add_hierarchy_columns_to_departments.php',
            '2025_06_17_075658_add_branch_id_to_departments_table.php',
            '2025_07_04_130515_add_dashboard_type_to_departments_table.php'
        ]
    ],
    'loans' => [
        'description' => 'Loans table with all assessment and calculation fields',
        'files' => [
            '2024_03_13_create_loans_table.php',
            '2024_12_19_000004_improve_loans_table_structure.php',
            '2025_01_15_000000_add_disbursement_method_to_loans_table.php',
            '2025_06_27_000000_add_assessment_columns_to_loans_table.php',
            '2025_06_30_153053_add_loan_calculation_fields_to_loans_table.php',
            '2025_06_30_154242_add_missing_loan_fields_to_loans_table.php'
        ]
    ],
    'shares' => [
        'description' => 'Shares table with price, product, member, and status fields',
        'files' => [
            '2024_03_19_000002_create_shares_table.php',
            '2024_03_19_add_price_per_share_to_shares_table.php',
            '2024_03_19_add_share_product_id_to_shares_table.php',
            '2024_03_20_add_member_id_to_shares_table.php',
            '2025_06_10_035601_modify_shares_status_column_to_string.php',
            '2025_06_11_045218_add_summary_to_shares_table.php'
        ]
    ],
    'ppes' => [
        'description' => 'PPEs table with enhanced accounting and disposal fields',
        'files' => [
            '2024_03_13_create_ppes_table.php',
            '2025_07_03_084230_update_ppes_table_depreciation_for_month_to_decimal.php',
            '2025_07_03_101953_update_ppes_table_numeric_precision.php',
            '2025_07_03_102500_enhance_ppes_table_for_proper_accounting.php',
            '2025_07_03_151816_add_disposal_fields_to_ppes_table.php'
        ]
    ],
    'tellers' => [
        'description' => 'Tellers table with user, till, and management fields',
        'files' => [
            '2025_07_07_074411_create_tellers_table.php',
            '2025_07_07_102228_add_user_id_to_tellers_table.php',
            '2025_07_07_102725_add_missing_columns_to_tellers_table.php',
            '2025_07_07_123039_add_till_id_to_tellers_table.php',
            '2025_07_07_124336_change_employee_id_to_string_in_tellers_table.php'
        ]
    ]
];

// Create consolidated migrations directory
$consolidatedDir = database_path('migrations/consolidated');
if (!file_exists($consolidatedDir)) {
    mkdir($consolidatedDir, 0755, true);
}

echo "=== CREATING CONSOLIDATED MIGRATION EXAMPLES ===\n\n";

foreach ($tablesToConsolidate as $table => $info) {
    echo "Creating consolidated migration for: $table\n";
    echo "Description: {$info['description']}\n";
    echo "Combining " . count($info['files']) . " migration files\n";
    
    // Generate consolidated migration filename
    $timestamp = date('Y_m_d_His');
    $filename = "{$timestamp}_create_{$table}_table_consolidated.php";
    $filepath = $consolidatedDir . '/' . $filename;
    
    // Get actual table structure from database
    $columns = Schema::getColumnListing($table);
    
    if (!empty($columns)) {
        // Generate migration content based on actual table structure
        $migrationContent = generateConsolidatedMigration($table, $info['description']);
        file_put_contents($filepath, $migrationContent);
        echo "✅ Created: consolidated/$filename\n";
    } else {
        echo "⚠️  Skipped: Table '$table' not found in database\n";
    }
    
    echo "\n";
}

echo "Consolidated migration examples created in: database/migrations/consolidated/\n";
echo "\nNOTE: These are examples only. Since migrations have already run, these files are for reference.\n";
echo "In a new project, you would use these consolidated versions instead of the multiple separate files.\n";

function generateConsolidatedMigration($table, $description) {
    $className = 'Create' . str_replace('_', '', ucwords($table, '_')) . 'TableConsolidated';
    
    return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated migration for $table table
 * $description
 * 
 * This migration combines all the separate migrations for the $table table
 * into a single, comprehensive migration file.
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('$table', function (Blueprint \$table) {
            // [Consolidated table structure would go here]
            // This is an example - actual structure would be based on analyzing
            // all the individual migration files
            
            // The consolidated migration would include ALL columns from:
            // - The original create table migration
            // - All subsequent add column migrations
            // - All column modifications
            // - All indexes and constraints
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('$table');
    }
};
PHP;
}